# KI-Matching: Phase 2 - Presidio Anonymisierung

> **Voraussetzung:** [Phase 1 abgeschlossen](./ki-matching-phase-1-infrastructure.md)

## Ziel dieser Phase

Aufsetzen des Presidio Service zur Anonymisierung von Lebensläufen:
- Text-Anonymisierung (PDFs mit Textebene, DOCX)
- Bild-Anonymisierung (Scans, Fotos)
- API-Endpoint für den Cloudflare Worker
- Deployment auf **Google Cloud Run** (EU-Region, DSGVO-konform)

---

## Architektur

```
┌─────────────────────────────────────────────────────────────────────┐
│                      PRESIDIO SERVICE                                │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Cloudflare Worker (api.recruiting-playbook.com)                    │
│        │                                                             │
│        │  POST /api/v1/anonymize                                    │
│        │  Header: X-API-Key                                         │
│        ▼                                                             │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │            GOOGLE CLOUD RUN (europe-west3)                   │   │
│  │            presidio.recruiting-playbook.com                  │   │
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

### Warum Google Cloud Run?

| Aspekt | Vorteil |
|--------|---------|
| **Zuverlässigkeit** | Google Infrastruktur, 99.95% SLA |
| **DSGVO** | EU-Region `europe-west3` (Frankfurt) |
| **Kosten** | Pay-per-use, 2 Mio. Requests/Monat kostenlos |
| **Skalierung** | Automatisch 0 → ∞ |
| **Wartung** | Keine Server-Administration |

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
├── docker-compose.yml       # Lokale Entwicklung
├── cloudbuild.yaml          # Google Cloud Build
├── requirements.txt
└── README.md
```

---

## 2. Requirements

### requirements.txt

```txt
# Web Framework (Stand: Januar 2026)
fastapi==0.128.0
uvicorn[standard]==0.40.0
gunicorn==23.0.0
python-multipart==0.0.18

# Presidio (PII Detection & Anonymization)
presidio-analyzer==2.2.360
presidio-anonymizer==2.2.360
presidio-image-redactor==0.0.56

# NLP & OCR
pytesseract==0.3.13
pillow==11.1.0

# Document Processing
pypdf2==3.0.1
python-docx==1.1.2
pdfplumber==0.11.9
pdf2image==1.17.0

# Config
pydantic-settings==2.7.1
```

> **Hinweis:** Versionen regelmäßig mit `pip list --outdated` prüfen.

---

## 3. Konfiguration

### app/config.py

```python
from pydantic_settings import BaseSettings
from typing import List
import logging

# Logging Level aus Environment
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s"
)


class Settings(BaseSettings):
    # API Settings
    api_key: str = ""
    debug: bool = False

    # Presidio Settings
    supported_languages: List[str] = ["de", "en"]
    default_language: str = "de"

    # SpaCy Modelle (md = medium für Cloud Run)
    spacy_model_de: str = "de_core_news_md"
    spacy_model_en: str = "en_core_web_md"

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
from contextlib import asynccontextmanager
from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
import logging

from app.config import settings

# Logging konfigurieren
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


@asynccontextmanager
async def lifespan(app: FastAPI):
    """
    Lifecycle Management für FastAPI.
    Lazy Loading der ML-Modelle NACH dem Start für schnellere Cold Starts.
    """
    logger.info("Starting Presidio Service...")

    # Modelle werden lazy geladen beim ersten Request
    # (nicht hier, um Cold Start zu beschleunigen)

    yield

    logger.info("Shutting down Presidio Service...")


app = FastAPI(
    title="Presidio Anonymization Service",
    description="PII Detection and Anonymization for CV Matching",
    version="1.0.0",
    lifespan=lifespan,
)

# CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "https://api.recruiting-playbook.com",
        "https://recruiting-playbook.com",
    ],
    allow_methods=["POST", "GET", "OPTIONS"],
    allow_headers=["*"],
)


# API Key Middleware
@app.middleware("http")
async def verify_api_key(request: Request, call_next):
    # Health-Check und Startup-Probe ohne Auth
    if request.url.path in ["/health", "/ready"]:
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


# Health Check (für Load Balancer)
@app.get("/health")
async def health_check():
    return {"status": "ok", "service": "presidio"}


# Readiness Check (für Cloud Run Startup Probe)
@app.get("/ready")
async def readiness_check():
    """
    Prüft ob der Service bereit ist, Requests zu verarbeiten.
    Wird von Cloud Run für die Startup Probe verwendet.
    """
    try:
        # Lazy import um zu prüfen ob Modelle geladen werden können
        from app.services.text_anonymizer import get_anonymizer
        anonymizer = get_anonymizer()
        return {"status": "ready", "models_loaded": anonymizer is not None}
    except Exception as e:
        logger.error(f"Readiness check failed: {e}")
        return JSONResponse(
            status_code=503,
            content={"status": "not_ready", "error": str(e)}
        )


# Routes (lazy import)
from app.routes import anonymize
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
import logging

from app.services.text_anonymizer import get_anonymizer
from app.services.image_anonymizer import get_image_anonymizer
from app.services.pdf_processor import PDFProcessor
from app.utils.file_detector import detect_file_type, FileType
from app.config import settings

router = APIRouter()
logger = logging.getLogger(__name__)

# Services werden lazy geladen (Singleton Pattern)
pdf_processor = PDFProcessor()  # Leichtgewichtig, kann sofort geladen werden


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

    # Lazy Load der Services
    text_anonymizer = get_anonymizer()
    image_anonymizer = get_image_anonymizer()

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

    image_anonymizer = get_image_anonymizer()

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

    text_anonymizer = get_anonymizer()

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
    text_anonymizer = get_anonymizer()

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
from presidio_analyzer import AnalyzerEngine
from presidio_analyzer.nlp_engine import NlpEngineProvider
from presidio_anonymizer import AnonymizerEngine
from presidio_anonymizer.entities import OperatorConfig
from typing import Optional
import logging
import threading

from app.config import settings

logger = logging.getLogger(__name__)

# Singleton Pattern für Lazy Loading
_anonymizer_instance: Optional["TextAnonymizer"] = None
_anonymizer_lock = threading.Lock()


def get_anonymizer() -> "TextAnonymizer":
    """
    Lazy Loading Singleton für TextAnonymizer.
    Modelle werden erst beim ersten Aufruf geladen.
    Thread-safe.
    """
    global _anonymizer_instance

    if _anonymizer_instance is None:
        with _anonymizer_lock:
            # Double-check locking
            if _anonymizer_instance is None:
                logger.info("Loading NLP models (this may take a moment)...")
                _anonymizer_instance = TextAnonymizer()
                logger.info("NLP models loaded successfully")

    return _anonymizer_instance


class TextAnonymizer:
    """
    PII-Erkennung und Anonymisierung für Text.
    Verwendet Microsoft Presidio mit deutschen SpaCy-Modellen.
    """

    def __init__(self):
        # NLP Engine für Deutsch konfigurieren
        configuration = {
            "nlp_engine_name": "spacy",
            "models": [
                {"lang_code": "de", "model_name": settings.spacy_model_de},
                {"lang_code": "en", "model_name": settings.spacy_model_en},
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
        replacement: str = "██████████",
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
        if not text or not text.strip():
            return text

        # PII erkennen
        results = self.analyzer.analyze(
            text=text,
            language=language,
            entities=settings.entities_to_anonymize,
        )

        self.last_pii_count = len(results)
        logger.info(f"Found {len(results)} PII entities")

        if not results:
            return text

        # Anonymisieren mit spezifischen Operatoren
        anonymized = self.anonymizer.anonymize(
            text=text,
            analyzer_results=results,
            operators={
                "DEFAULT": OperatorConfig("replace", {"new_value": replacement}),
                "PERSON": OperatorConfig("replace", {"new_value": "[PERSON]"}),
                "EMAIL_ADDRESS": OperatorConfig("replace", {"new_value": "[E-MAIL]"}),
                "PHONE_NUMBER": OperatorConfig("replace", {"new_value": "[TELEFON]"}),
                "LOCATION": OperatorConfig("replace", {"new_value": "[ORT]"}),
                "IBAN_CODE": OperatorConfig("replace", {"new_value": "[IBAN]"}),
            },
        )

        return anonymized.text
```

---

## 7. Image Anonymizer Service

### app/services/image_anonymizer.py

```python
from presidio_image_redactor import ImageRedactorEngine
from PIL import Image
import pytesseract
from typing import List, Optional
import logging
import threading

from app.config import settings

logger = logging.getLogger(__name__)

# Singleton Pattern für Lazy Loading
_image_anonymizer_instance: Optional["ImageAnonymizer"] = None
_image_anonymizer_lock = threading.Lock()


def get_image_anonymizer() -> "ImageAnonymizer":
    """Lazy Loading Singleton für ImageAnonymizer."""
    global _image_anonymizer_instance

    if _image_anonymizer_instance is None:
        with _image_anonymizer_lock:
            if _image_anonymizer_instance is None:
                logger.info("Loading Image Redactor...")
                _image_anonymizer_instance = ImageAnonymizer()
                logger.info("Image Redactor loaded")

    return _image_anonymizer_instance


class ImageAnonymizer:
    """
    PII-Erkennung und Schwärzung in Bildern.
    Verwendet Presidio Image Redactor mit Tesseract OCR.
    """

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

        logger.info(f"Anonymizing image with language: {ocr_lang}")

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

## 8. File Detector Utility

### app/utils/file_detector.py

```python
from enum import Enum
import io


class FileType(Enum):
    PDF = "pdf"
    IMAGE = "image"
    DOCX = "docx"
    TEXT = "text"
    UNKNOWN = "unknown"


def detect_file_type(content: bytes, filename: str = "") -> FileType:
    """
    Dateityp anhand von Magic Bytes und Extension erkennen.
    """
    # Magic Bytes prüfen
    if content[:4] == b'%PDF':
        return FileType.PDF

    if content[:8] == b'\x89PNG\r\n\x1a\n':
        return FileType.IMAGE

    if content[:2] == b'\xff\xd8':  # JPEG
        return FileType.IMAGE

    if content[:4] == b'GIF8':
        return FileType.IMAGE

    # DOCX ist ein ZIP mit speziellem Inhalt
    if content[:4] == b'PK\x03\x04':
        if filename.lower().endswith('.docx'):
            return FileType.DOCX

    # Extension-basierte Erkennung als Fallback
    ext = filename.lower().split('.')[-1] if '.' in filename else ''

    extension_map = {
        'pdf': FileType.PDF,
        'png': FileType.IMAGE,
        'jpg': FileType.IMAGE,
        'jpeg': FileType.IMAGE,
        'gif': FileType.IMAGE,
        'webp': FileType.IMAGE,
        'docx': FileType.DOCX,
        'txt': FileType.TEXT,
    }

    return extension_map.get(ext, FileType.UNKNOWN)
```

---

## 9. PDF Processor Service

### app/services/pdf_processor.py

```python
from PIL import Image
from pdf2image import convert_from_bytes
import pdfplumber
import io
from typing import List


class PDFProcessor:
    """PDF-Verarbeitung: Text-Extraktion und Bild-Konvertierung."""

    def extract_text(self, pdf_bytes: bytes) -> str:
        """
        Text aus PDF extrahieren.

        Returns:
            Extrahierter Text oder leerer String bei Scans.
        """
        try:
            with pdfplumber.open(io.BytesIO(pdf_bytes)) as pdf:
                texts = []
                for page in pdf.pages:
                    text = page.extract_text()
                    if text:
                        texts.append(text)
                return "\n\n".join(texts)
        except Exception as e:
            print(f"PDF text extraction error: {e}")
            return ""

    def pdf_to_images(
        self,
        pdf_bytes: bytes,
        dpi: int = 200,
    ) -> List[Image.Image]:
        """
        PDF-Seiten in Bilder konvertieren.

        Args:
            pdf_bytes: PDF als Bytes
            dpi: Auflösung (höher = besser OCR, langsamer)

        Returns:
            Liste von PIL Images
        """
        try:
            images = convert_from_bytes(pdf_bytes, dpi=dpi)
            return images
        except Exception as e:
            print(f"PDF to image conversion error: {e}")
            return []

    def images_to_pdf(self, images: List[Image.Image]) -> bytes:
        """
        Bilder zurück in PDF konvertieren.

        Args:
            images: Liste von PIL Images

        Returns:
            PDF als Bytes
        """
        if not images:
            return b""

        pdf_buffer = io.BytesIO()

        # Erstes Bild speichern, Rest anhängen
        if len(images) == 1:
            images[0].save(pdf_buffer, format="PDF")
        else:
            images[0].save(
                pdf_buffer,
                format="PDF",
                save_all=True,
                append_images=images[1:],
            )

        return pdf_buffer.getvalue()
```

---

## 10. Dockerfile

### Dockerfile

```dockerfile
# Python 3.12 slim für beste Kompatibilität mit Presidio
FROM python:3.12-slim

# Best Practice: Logs sofort ausgeben (nicht puffern)
ENV PYTHONUNBUFFERED=1
ENV PYTHONDONTWRITEBYTECODE=1

# System-Dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    tesseract-ocr \
    tesseract-ocr-deu \
    tesseract-ocr-eng \
    poppler-utils \
    libgl1-mesa-glx \
    libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Arbeitsverzeichnis
WORKDIR /app

# Python Dependencies zuerst (für Docker Cache)
COPY requirements.txt .
RUN pip install --no-cache-dir --upgrade pip \
    && pip install --no-cache-dir -r requirements.txt

# SpaCy Modelle herunterladen (md = medium, guter Kompromiss)
# Diese werden im Image gespeichert für schnelleren Start
RUN python -m spacy download de_core_news_md \
    && python -m spacy download en_core_web_md

# App kopieren
COPY app/ ./app/

# Non-root User für Sicherheit
RUN useradd --create-home --shell /bin/bash appuser \
    && chown -R appuser:appuser /app
USER appuser

# Port (Cloud Run setzt $PORT)
ENV PORT=8080
EXPOSE 8080

# Gunicorn mit Uvicorn Workers für Produktion
# - workers: 1 (Cloud Run skaliert horizontal)
# - threads: 4 (für I/O-bound Tasks)
# - timeout: 300 (für große PDFs)
CMD exec gunicorn app.main:app \
    --bind 0.0.0.0:$PORT \
    --worker-class uvicorn.workers.UvicornWorker \
    --workers 1 \
    --threads 4 \
    --timeout 300 \
    --keep-alive 30 \
    --access-logfile - \
    --error-logfile -
```

### Best Practices im Dockerfile:

| Practice | Umsetzung |
|----------|-----------|
| **Python 3.12** | Beste Presidio-Kompatibilität |
| **PYTHONUNBUFFERED** | Logs sofort sichtbar in Cloud Logging |
| **Non-root User** | Sicherheit (nicht als root laufen) |
| **Gunicorn + Uvicorn** | Produktions-ready ASGI Server |
| **Single Worker** | Cloud Run skaliert horizontal |
| **Docker Layer Caching** | requirements.txt vor Code kopieren |

**SpaCy Modelle:**
- `de_core_news_md`: ~42MB (gut für deutsche PII)
- `en_core_web_md`: ~40MB (gut für englische PII)
- Gesamt: ~82MB statt ~1GB bei `_lg` Modellen

---

## 11. Docker Compose (lokale Entwicklung)

### docker-compose.yml

```yaml
services:
  presidio:
    build: .
    ports:
      - "8000:8080"
    environment:
      - API_KEY=${PRESIDIO_API_KEY:-}
      - DEBUG=true
      - PORT=8080
    volumes:
      - ./app:/app/app  # Hot reload bei Entwicklung
    restart: unless-stopped
```

---

## 12. Google Cloud Run Deployment

### Voraussetzungen

1. Google Cloud Account: [console.cloud.google.com](https://console.cloud.google.com)
2. Neues Projekt erstellen: `recruiting-playbook`
3. Billing aktivieren (Free Tier reicht für Start)
4. Cloud Run API aktivieren

### gcloud CLI installieren

```bash
# macOS
brew install google-cloud-sdk

# Oder Download von: https://cloud.google.com/sdk/docs/install

# Login
gcloud auth login

# Projekt setzen
gcloud config set project recruiting-playbook
```

### Deployment

```bash
# 1. In das presidio-service Verzeichnis wechseln
cd presidio-service

# 2. Docker Image bauen und zu Artifact Registry pushen (empfohlen statt GCR)
gcloud builds submit --tag europe-west3-docker.pkg.dev/recruiting-playbook/presidio/presidio-service

# 3. Auf Cloud Run deployen (EU-Region Frankfurt)
gcloud run deploy presidio-service \
  --image europe-west3-docker.pkg.dev/recruiting-playbook/presidio/presidio-service \
  --platform managed \
  --region europe-west3 \
  --memory 2Gi \
  --cpu 2 \
  --timeout 300 \
  --concurrency 10 \
  --min-instances 0 \
  --max-instances 5 \
  --cpu-boost \
  --startup-probe-path /ready \
  --startup-probe-initial-delay 10 \
  --startup-probe-timeout 240 \
  --startup-probe-period 10 \
  --set-env-vars "API_KEY=$(openssl rand -hex 32)" \
  --allow-unauthenticated
```

### Deployment-Parameter erklärt:

| Parameter | Wert | Begründung |
|-----------|------|------------|
| `--memory 2Gi` | 2 GB RAM | SpaCy + Presidio brauchen ~1.5GB |
| `--cpu 2` | 2 vCPUs | Für parallele Verarbeitung |
| `--cpu-boost` | Aktiviert | Schnellerer Cold Start |
| `--startup-probe-path` | `/ready` | Wartet bis Modelle geladen sind |
| `--startup-probe-timeout` | 240s | SpaCy-Modelle brauchen Zeit |
| `--concurrency 10` | 10 Requests | Pro Instance gleichzeitig |
| `--min-instances 0` | 0 | Scale-to-zero für Kosten |

### Custom Domain einrichten

```bash
# 1. Domain Mapping erstellen
gcloud run domain-mappings create \
  --service presidio-service \
  --domain presidio.recruiting-playbook.com \
  --region europe-west3

# 2. DNS-Eintrag in Cloudflare hinzufügen (wird angezeigt)
# Typ: CNAME
# Name: presidio
# Wert: ghs.googlehosted.com
```

### cloudbuild.yaml (CI/CD)

```yaml
steps:
  # Docker Image bauen
  - name: 'gcr.io/cloud-builders/docker'
    args: ['build', '-t', 'gcr.io/$PROJECT_ID/presidio-service', '.']

  # Image pushen
  - name: 'gcr.io/cloud-builders/docker'
    args: ['push', 'gcr.io/$PROJECT_ID/presidio-service']

  # Cloud Run deployen
  - name: 'gcr.io/google.com/cloudsdktool/cloud-sdk'
    entrypoint: gcloud
    args:
      - 'run'
      - 'deploy'
      - 'presidio-service'
      - '--image'
      - 'gcr.io/$PROJECT_ID/presidio-service'
      - '--region'
      - 'europe-west3'
      - '--platform'
      - 'managed'

images:
  - 'gcr.io/$PROJECT_ID/presidio-service'
```

---

## 13. Cloudflare Worker Integration

Nach dem Deployment muss der Cloudflare Worker die Presidio URL kennen.

### Secret setzen

```bash
cd api/recruiting-playbook-api

# Presidio API Key setzen (den gleichen wie bei Cloud Run)
wrangler secret put PRESIDIO_API_KEY

# PRESIDIO_URL ist bereits in wrangler.jsonc konfiguriert
# Falls die URL sich ändert:
# wrangler.jsonc → vars → PRESIDIO_URL
```

### Worker Code für Presidio-Aufruf (Phase 3)

```typescript
// Beispiel: Presidio Service aufrufen
async function anonymizeDocument(
  file: File,
  env: Bindings
): Promise<AnonymizedResult> {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('language', 'de');
  formData.append('output_format', 'text');

  const response = await fetch(`${env.PRESIDIO_URL}/api/v1/anonymize`, {
    method: 'POST',
    headers: {
      'X-API-Key': env.PRESIDIO_API_KEY,
    },
    body: formData,
  });

  if (!response.ok) {
    throw new Error(`Presidio error: ${response.status}`);
  }

  return response.json();
}
```

---

## Kosten-Übersicht (Google Cloud Run)

| Ressource | Free Tier | Danach |
|-----------|-----------|--------|
| Requests | 2 Mio/Monat | $0.40/Mio |
| CPU | 180.000 vCPU-Sekunden | $0.00002400/vCPU-Sekunde |
| Speicher | 360.000 GB-Sekunden | $0.00000250/GB-Sekunde |
| Netzwerk | 1 GB/Monat | $0.12/GB |

**Geschätzte Kosten bei 1.000 CV-Analysen/Monat:** ~$0-5

---

## Ergebnis dieser Phase

Nach Abschluss habt ihr:

- ✅ Presidio Service mit FastAPI
- ✅ Text-Anonymisierung (PDF, DOCX, TXT)
- ✅ Bild-Anonymisierung (Scans, Fotos)
- ✅ Docker Setup für lokale Entwicklung
- ✅ Deployment auf Google Cloud Run (EU, DSGVO-konform)
- ✅ Custom Domain `presidio.recruiting-playbook.com`

---

## Nächste Phase

→ [Phase 3: Claude API Integration](./ki-matching-phase-3-analysis.md)
