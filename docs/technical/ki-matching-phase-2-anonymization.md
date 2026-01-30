# KI-Matching: Phase 2 - Presidio Anonymisierung

> **Voraussetzung:** [Phase 1 abgeschlossen](./ki-matching-phase-1-infrastructure.md)

## Ziel dieser Phase

Aufsetzen des Presidio Service zur Anonymisierung von Lebensläufen:
- Text-Anonymisierung (PDFs mit Textebene, DOCX)
- Bild-Anonymisierung (Scans, Fotos)
- API-Endpoint für den Cloudflare Worker

---

## Architektur

```
┌─────────────────────────────────────────────────────────────────────┐
│                      PRESIDIO SERVICE                                │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Cloudflare Worker                                                   │
│        │                                                             │
│        │  POST /anonymize                                            │
│        │  Content-Type: multipart/form-data                         │
│        ▼                                                             │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    PRESIDIO API                              │   │
│  │                   (Python FastAPI)                           │   │
│  │                                                              │   │
│  │   ┌─────────────┐     ┌─────────────┐     ┌─────────────┐  │   │
│  │   │   Router    │────▶│   Detect    │────▶│  Anonymize  │  │   │
│  │   │             │     │  File Type  │     │             │  │   │
│  │   └─────────────┘     └─────────────┘     └──────┬──────┘  │   │
│  │                                                   │         │   │
│  │                    ┌──────────────────────────────┼─────┐  │   │
│  │                    │              │               │     │  │   │
│  │                    ▼              ▼               ▼     │  │   │
│  │              ┌──────────┐  ┌──────────┐  ┌──────────┐  │  │   │
│  │              │   Text   │  │   PDF    │  │  Image   │  │  │   │
│  │              │ Analyzer │  │ Extractor│  │ Redactor │  │  │   │
│  │              └──────────┘  └──────────┘  └──────────┘  │  │   │
│  │                                                         │  │   │
│  │                    ┌───────────────────────────────────┘  │   │
│  │                    │                                       │   │
│  │                    ▼                                       │   │
│  │              ┌──────────┐                                  │   │
│  │              │ Tesseract│  (OCR für Scans)                │   │
│  │              └──────────┘                                  │   │
│  │                                                              │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 1. Projekt-Struktur

```
presidio-service/
├── app/
│   ├── __init__.py
│   ├── main.py              # FastAPI Entry Point
│   ├── config.py            # Konfiguration
│   ├── routes/
│   │   ├── __init__.py
│   │   └── anonymize.py     # /anonymize Endpoint
│   ├── services/
│   │   ├── __init__.py
│   │   ├── text_anonymizer.py
│   │   ├── image_anonymizer.py
│   │   └── pdf_processor.py
│   └── utils/
│       ├── __init__.py
│       └── file_detector.py
├── Dockerfile
├── docker-compose.yml
├── requirements.txt
└── README.md
```

---

## 2. Requirements

### requirements.txt

```txt
fastapi==0.109.0
uvicorn[standard]==0.27.0
python-multipart==0.0.6
presidio-analyzer==2.2.354
presidio-anonymizer==2.2.354
presidio-image-redactor==0.0.53
pytesseract==0.3.10
pillow==10.2.0
pypdf2==3.0.1
python-docx==1.1.0
pdfplumber==0.10.3
```

---

## 3. Konfiguration

### app/config.py

```python
from pydantic_settings import BaseSettings
from typing import List


class Settings(BaseSettings):
    # API Settings
    api_key: str = ""
    debug: bool = False

    # Presidio Settings
    supported_languages: List[str] = ["de", "en"]
    default_language: str = "de"

    # PII Entities die erkannt werden sollen
    entities_to_anonymize: List[str] = [
        "PERSON",
        "EMAIL_ADDRESS",
        "PHONE_NUMBER",
        "LOCATION",
        "DATE_TIME",
        "IBAN_CODE",
        "CREDIT_CARD",
        "IP_ADDRESS",
        "URL",
    ]

    # Entities die NICHT anonymisiert werden (beruflich relevant)
    entities_to_keep: List[str] = [
        "ORGANIZATION",  # Firmennahmen behalten
        "NRP",           # Nationalitäten behalten (kann relevant sein)
    ]

    # OCR Settings
    tesseract_lang: str = "deu+eng"

    # File Limits
    max_file_size_mb: int = 10
    max_pages: int = 20

    class Config:
        env_file = ".env"


settings = Settings()
```

---

## 4. FastAPI Main

### app/main.py

```python
from fastapi import FastAPI, Request, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse

from app.config import settings
from app.routes import anonymize

app = FastAPI(
    title="Presidio Anonymization Service",
    description="PII Detection and Anonymization for CV Matching",
    version="1.0.0",
)

# CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # In Produktion einschränken
    allow_methods=["POST", "GET"],
    allow_headers=["*"],
)


# API Key Middleware
@app.middleware("http")
async def verify_api_key(request: Request, call_next):
    # Health-Check ohne Auth
    if request.url.path == "/health":
        return await call_next(request)

    # API Key prüfen
    if settings.api_key:
        api_key = request.headers.get("X-API-Key")
        if api_key != settings.api_key:
            return JSONResponse(
                status_code=401,
                content={"error": "Invalid API key"}
            )

    return await call_next(request)


# Health Check
@app.get("/health")
async def health_check():
    return {"status": "ok", "service": "presidio"}


# Routes
app.include_router(anonymize.router, prefix="/api/v1")
```

---

## 5. Anonymize Endpoint

### app/routes/anonymize.py

```python
from fastapi import APIRouter, UploadFile, File, HTTPException, Form
from fastapi.responses import JSONResponse, Response
from typing import Optional
import io

from app.services.text_anonymizer import TextAnonymizer
from app.services.image_anonymizer import ImageAnonymizer
from app.services.pdf_processor import PDFProcessor
from app.utils.file_detector import detect_file_type, FileType
from app.config import settings

router = APIRouter()

text_anonymizer = TextAnonymizer()
image_anonymizer = ImageAnonymizer()
pdf_processor = PDFProcessor()


@router.post("/anonymize")
async def anonymize_document(
    file: UploadFile = File(...),
    output_format: Optional[str] = Form("auto"),  # auto, text, image
    language: Optional[str] = Form("de"),
):
    """
    Anonymisiert ein Dokument (PDF, Bild, DOCX).

    - **file**: Das hochgeladene Dokument
    - **output_format**:
        - "auto": Gleiches Format wie Input
        - "text": Nur anonymisierter Text
        - "image": Anonymisiertes Bild (bei Scans)
    - **language**: Sprache für PII-Erkennung (de, en)

    Returns:
        - Bei Text-Output: JSON mit anonymisiertem Text
        - Bei Bild-Output: Anonymisiertes Bild als Binary
    """

    # Dateigröße prüfen
    content = await file.read()
    size_mb = len(content) / (1024 * 1024)

    if size_mb > settings.max_file_size_mb:
        raise HTTPException(
            status_code=413,
            detail=f"File too large. Maximum: {settings.max_file_size_mb}MB"
        )

    # Dateityp erkennen
    file_type = detect_file_type(content, file.filename)

    try:
        if file_type == FileType.PDF:
            return await process_pdf(content, output_format, language)

        elif file_type == FileType.IMAGE:
            return await process_image(content, output_format, language)

        elif file_type == FileType.DOCX:
            return await process_docx(content, language)

        elif file_type == FileType.TEXT:
            return await process_text(content.decode('utf-8'), language)

        else:
            raise HTTPException(
                status_code=415,
                detail=f"Unsupported file type: {file.filename}"
            )

    except Exception as e:
        raise HTTPException(
            status_code=500,
            detail=f"Processing error: {str(e)}"
        )


async def process_pdf(content: bytes, output_format: str, language: str):
    """PDF verarbeiten - Text-PDF oder Scan erkennen."""

    # Prüfen ob PDF Text enthält
    text = pdf_processor.extract_text(content)

    if text and len(text.strip()) > 100:
        # Text-PDF: Text anonymisieren
        anonymized_text = text_anonymizer.anonymize(text, language)

        return JSONResponse({
            "type": "text",
            "original_type": "pdf_text",
            "anonymized_text": anonymized_text,
            "pii_found": text_anonymizer.last_pii_count,
        })

    else:
        # Scan-PDF: Bild-Anonymisierung
        images = pdf_processor.pdf_to_images(content)
        anonymized_images = []

        for img in images[:settings.max_pages]:
            anonymized = image_anonymizer.anonymize(img, language)
            anonymized_images.append(anonymized)

        if output_format == "text":
            # OCR auf anonymisiertem Bild
            text = image_anonymizer.extract_text_from_images(anonymized_images)
            return JSONResponse({
                "type": "text",
                "original_type": "pdf_scan",
                "anonymized_text": text,
                "pages_processed": len(anonymized_images),
            })

        else:
            # Anonymisiertes PDF zurückgeben
            pdf_bytes = pdf_processor.images_to_pdf(anonymized_images)
            return Response(
                content=pdf_bytes,
                media_type="application/pdf",
                headers={
                    "X-Original-Type": "pdf_scan",
                    "X-Pages-Processed": str(len(anonymized_images)),
                }
            )


async def process_image(content: bytes, output_format: str, language: str):
    """Bild verarbeiten."""
    from PIL import Image

    img = Image.open(io.BytesIO(content))
    anonymized = image_anonymizer.anonymize(img, language)

    if output_format == "text":
        text = image_anonymizer.extract_text(anonymized)
        return JSONResponse({
            "type": "text",
            "original_type": "image",
            "anonymized_text": text,
        })

    else:
        # Anonymisiertes Bild zurückgeben
        img_bytes = io.BytesIO()
        anonymized.save(img_bytes, format="PNG")

        return Response(
            content=img_bytes.getvalue(),
            media_type="image/png",
            headers={"X-Original-Type": "image"}
        )


async def process_docx(content: bytes, language: str):
    """DOCX verarbeiten."""
    from docx import Document

    doc = Document(io.BytesIO(content))
    full_text = "\n".join([para.text for para in doc.paragraphs])

    anonymized_text = text_anonymizer.anonymize(full_text, language)

    return JSONResponse({
        "type": "text",
        "original_type": "docx",
        "anonymized_text": anonymized_text,
        "pii_found": text_anonymizer.last_pii_count,
    })


async def process_text(text: str, language: str):
    """Plain Text verarbeiten."""
    anonymized_text = text_anonymizer.anonymize(text, language)

    return JSONResponse({
        "type": "text",
        "original_type": "text",
        "anonymized_text": anonymized_text,
        "pii_found": text_anonymizer.last_pii_count,
    })
```

---

## 6. Text Anonymizer Service

### app/services/text_anonymizer.py

```python
from presidio_analyzer import AnalyzerEngine, RecognizerRegistry
from presidio_analyzer.nlp_engine import NlpEngineProvider
from presidio_anonymizer import AnonymizerEngine
from presidio_anonymizer.entities import OperatorConfig
from typing import Optional

from app.config import settings


class TextAnonymizer:
    def __init__(self):
        # NLP Engine für Deutsch konfigurieren
        configuration = {
            "nlp_engine_name": "spacy",
            "models": [
                {"lang_code": "de", "model_name": "de_core_news_lg"},
                {"lang_code": "en", "model_name": "en_core_web_lg"},
            ],
        }

        provider = NlpEngineProvider(nlp_configuration=configuration)
        nlp_engine = provider.create_engine()

        # Analyzer mit deutschem Support
        self.analyzer = AnalyzerEngine(
            nlp_engine=nlp_engine,
            supported_languages=settings.supported_languages,
        )

        self.anonymizer = AnonymizerEngine()
        self.last_pii_count = 0

    def anonymize(
        self,
        text: str,
        language: str = "de",
        replacement: str = "█" * 10,
    ) -> str:
        """
        Text anonymisieren.

        Args:
            text: Zu anonymisierender Text
            language: Sprache (de, en)
            replacement: Ersetzungszeichen für PII

        Returns:
            Anonymisierter Text
        """

        # PII erkennen
        results = self.analyzer.analyze(
            text=text,
            language=language,
            entities=settings.entities_to_anonymize,
        )

        self.last_pii_count = len(results)

        if not results:
            return text

        # Anonymisieren
        anonymized = self.anonymizer.anonymize(
            text=text,
            analyzer_results=results,
            operators={
                "DEFAULT": OperatorConfig("replace", {"new_value": replacement}),
                # E-Mail speziell behandeln
                "EMAIL_ADDRESS": OperatorConfig("replace", {"new_value": "[E-MAIL]"}),
                # Telefon speziell behandeln
                "PHONE_NUMBER": OperatorConfig("replace", {"new_value": "[TELEFON]"}),
            },
        )

        return anonymized.text
```

---

## 7. Image Anonymizer Service

### app/services/image_anonymizer.py

```python
from presidio_image_redactor import ImageRedactorEngine, ImageAnalyzerEngine
from presidio_analyzer import AnalyzerEngine
from PIL import Image
import pytesseract
from typing import List
import io

from app.config import settings


class ImageAnonymizer:
    def __init__(self):
        self.redactor = ImageRedactorEngine()

    def anonymize(
        self,
        image: Image.Image,
        language: str = "de",
        fill: str = "black",
    ) -> Image.Image:
        """
        Bild anonymisieren (PII schwärzen).

        Args:
            image: PIL Image
            language: Sprache für OCR
            fill: Füllfarbe ("black", "white", oder Hex)

        Returns:
            Anonymisiertes PIL Image
        """

        # Tesseract Sprache mappen
        ocr_lang = "deu" if language == "de" else "eng"

        # Bild anonymisieren
        redacted = self.redactor.redact(
            image=image,
            fill=fill,
            ocr_kwargs={"lang": ocr_lang},
            entities=settings.entities_to_anonymize,
        )

        return redacted

    def extract_text(self, image: Image.Image, language: str = "de") -> str:
        """Text aus Bild extrahieren (OCR)."""
        ocr_lang = "deu" if language == "de" else "eng"
        return pytesseract.image_to_string(image, lang=ocr_lang)

    def extract_text_from_images(
        self,
        images: List[Image.Image],
        language: str = "de",
    ) -> str:
        """Text aus mehreren Bildern extrahieren."""
        texts = []
        for i, img in enumerate(images):
            text = self.extract_text(img, language)
            texts.append(f"--- Seite {i + 1} ---\n{text}")

        return "\n\n".join(texts)
```

---

## 8. Dockerfile

### Dockerfile

```dockerfile
FROM python:3.11-slim

# System-Dependencies
RUN apt-get update && apt-get install -y \
    tesseract-ocr \
    tesseract-ocr-deu \
    tesseract-ocr-eng \
    poppler-utils \
    libgl1-mesa-glx \
    libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/*

# Arbeitsverzeichnis
WORKDIR /app

# Python Dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# SpaCy Modelle herunterladen
RUN python -m spacy download de_core_news_lg
RUN python -m spacy download en_core_web_lg

# App kopieren
COPY app/ ./app/

# Port
EXPOSE 8000

# Start
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
```

---

## 9. Docker Compose (für lokale Entwicklung)

### docker-compose.yml

```yaml
version: '3.8'

services:
  presidio:
    build: .
    ports:
      - "8000:8000"
    environment:
      - API_KEY=${PRESIDIO_API_KEY:-}
      - DEBUG=true
    volumes:
      - ./app:/app/app  # Hot reload
    restart: unless-stopped
```

---

## 10. Deployment (Railway.app)

### railway.json

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "DOCKERFILE",
    "dockerfilePath": "Dockerfile"
  },
  "deploy": {
    "startCommand": "uvicorn app.main:app --host 0.0.0.0 --port $PORT",
    "healthcheckPath": "/health",
    "healthcheckTimeout": 30,
    "restartPolicyType": "ON_FAILURE"
  }
}
```

**Deployment:**

```bash
# Railway CLI installieren
npm install -g @railway/cli

# Login & Deploy
railway login
railway init
railway up
```

---

## Ergebnis dieser Phase

Nach Abschluss habt ihr:

- ✅ Presidio Service mit FastAPI
- ✅ Text-Anonymisierung (PDF, DOCX, TXT)
- ✅ Bild-Anonymisierung (Scans, Fotos)
- ✅ Docker Setup für lokale Entwicklung
- ✅ Deployment auf Railway.app

---

## Nächste Phase

→ [Phase 3: Claude API Integration](./ki-matching-phase-3-analysis.md)
