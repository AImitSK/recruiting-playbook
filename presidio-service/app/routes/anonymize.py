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

    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Processing error: {e}")
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
