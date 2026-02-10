from presidio_image_redactor import ImageRedactorEngine, ImageAnalyzerEngine
from presidio_analyzer import AnalyzerEngine, Pattern, PatternRecognizer
from presidio_analyzer.nlp_engine import NlpEngineProvider
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


def create_german_address_recognizers() -> List[PatternRecognizer]:
    """
    Erstellt Custom Recognizers für deutsche Adressen.
    Diese fangen Adressen ab, die SpaCy's NER nicht erkennt.
    """
    recognizers = []

    # 1. Deutsche Postleitzahl (PLZ) - 5-stellig
    plz_pattern = Pattern(
        name="german_plz",
        regex=r"\b\d{5}\b",
        score=0.7,
    )
    plz_recognizer = PatternRecognizer(
        supported_entity="DE_PLZ",
        patterns=[plz_pattern],
        supported_language="de",
    )
    recognizers.append(plz_recognizer)

    # 2. Deutsche Straßennamen mit Hausnummer
    # Matches: "Musterstraße 11", "Hauptstr. 5a", "Am Markt 3"
    street_patterns = [
        Pattern(
            name="german_street_full",
            regex=r"\b[A-ZÄÖÜ][a-zäöüß]+(?:straße|strasse|str\.|weg|platz|gasse|allee|ring|damm|ufer|chaussee)\s*\d+\s*[a-zA-Z]?\b",
            score=0.85,
        ),
        Pattern(
            name="german_street_prefix",
            regex=r"\b(?:Am|An der|Auf der|Im|In der|Zur|Zum)\s+[A-ZÄÖÜ][a-zäöüß]+\s*\d+\s*[a-zA-Z]?\b",
            score=0.8,
        ),
    ]
    street_recognizer = PatternRecognizer(
        supported_entity="DE_STREET_ADDRESS",
        patterns=street_patterns,
        supported_language="de",
    )
    recognizers.append(street_recognizer)

    return recognizers


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
        # NLP Engine mit konfigurierten SpaCy-Modellen (md statt lg)
        configuration = {
            "nlp_engine_name": "spacy",
            "models": [
                {"lang_code": "de", "model_name": settings.spacy_model_de},
                {"lang_code": "en", "model_name": settings.spacy_model_en},
            ],
        }
        provider = NlpEngineProvider(nlp_configuration=configuration)
        nlp_engine = provider.create_engine()

        # Analyzer mit konfigurierter NLP Engine
        analyzer = AnalyzerEngine(
            nlp_engine=nlp_engine,
            supported_languages=settings.supported_languages,
        )

        # Custom Recognizers für deutsche Adressen hinzufügen
        for recognizer in create_german_address_recognizers():
            analyzer.registry.add_recognizer(recognizer)
            logger.info(f"ImageAnonymizer: Added custom recognizer: {recognizer.supported_entities}")

        # ImageAnalyzerEngine mit custom Analyzer erstellen
        image_analyzer = ImageAnalyzerEngine(analyzer_engine=analyzer)

        # Image Redactor mit custom ImageAnalyzerEngine
        self.redactor = ImageRedactorEngine(image_analyzer_engine=image_analyzer)

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
