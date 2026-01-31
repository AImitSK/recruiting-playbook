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
