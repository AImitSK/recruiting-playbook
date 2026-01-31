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
