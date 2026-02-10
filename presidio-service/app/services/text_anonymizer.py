from presidio_analyzer import AnalyzerEngine, Pattern, PatternRecognizer
from presidio_analyzer.nlp_engine import NlpEngineProvider
from presidio_analyzer.context_aware_enhancers import LemmaContextAwareEnhancer
from presidio_anonymizer import AnonymizerEngine
from presidio_anonymizer.entities import OperatorConfig
from typing import Optional, List
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


def create_german_address_recognizers() -> List[PatternRecognizer]:
    """
    Erstellt Custom Recognizers für deutsche Adressen nach Presidio Best Practices:
    - Niedrige Startkonfidenz für schwache Muster
    - Kontextwörter zur Konfidenzverstärkung
    """
    recognizers = []

    # 1. Deutsche Postleitzahl (PLZ) - 5-stellig
    # SCHWACHES MUSTER: Niedrige Konfidenz (0.01), Kontext erhöht auf 0.4+
    plz_recognizer = PatternRecognizer(
        supported_entity="DE_PLZ",
        patterns=[
            Pattern(
                name="german_plz_weak",
                regex=r"\b\d{5}\b",
                score=0.01,  # Niedrig wegen vieler False Positives
            )
        ],
        context=[
            # Deutsche Kontextwörter für PLZ
            "PLZ", "Postleitzahl", "postleitzahl",
            "wohnhaft", "wohnt", "Adresse", "adresse",
            "Anschrift", "anschrift", "Ort", "ort",
        ],
        supported_language="de",
    )
    recognizers.append(plz_recognizer)

    # 2. Deutsche Straßennamen mit Hausnummer
    # MITTELSTARKES MUSTER: Straßensuffixe sind spezifisch
    street_recognizer = PatternRecognizer(
        supported_entity="DE_STREET_ADDRESS",
        patterns=[
            # Straße/Weg/Platz mit Hausnummer: "Musterstraße 11", "Hauptstr. 5a"
            Pattern(
                name="german_street_with_number",
                regex=r"\b[A-ZÄÖÜ][a-zäöüß]+(?:straße|strasse|str\.|weg|platz|gasse|allee|ring|damm|ufer|chaussee)\s*\d+\s*[a-zA-Z]?\b",
                score=0.6,  # Mittel - Suffix ist spezifisch
            ),
            # Präfix-Straßen: "Am Markt 3", "An der Mühle 5"
            Pattern(
                name="german_street_prefix",
                regex=r"\b(?:Am|An der|Auf der|Im|In der|Zur|Zum)\s+[A-ZÄÖÜ][a-zäöüß]+(?:\s+[a-zäöüß]+)?\s*\d+\s*[a-zA-Z]?\b",
                score=0.5,
            ),
        ],
        context=[
            "Straße", "straße", "Adresse", "adresse",
            "wohnhaft", "wohnt", "Anschrift",
        ],
        supported_language="de",
    )
    recognizers.append(street_recognizer)

    # 3. PLZ + Stadtname Kombination
    # STARKES MUSTER: PLZ direkt gefolgt von Großbuchstabe = sehr wahrscheinlich Adresse
    plz_city_recognizer = PatternRecognizer(
        supported_entity="DE_ADDRESS_FULL",
        patterns=[
            Pattern(
                name="german_plz_city",
                regex=r"\b\d{5}\s+[A-ZÄÖÜ][a-zäöüß]+(?:\s+[A-ZÄÖÜ][a-zäöüß]+)*\b",
                score=0.85,  # Hoch - sehr spezifisches Muster
            ),
        ],
        context=[
            "PLZ", "Postleitzahl", "wohnhaft", "Adresse", "Ort",
        ],
        supported_language="de",
    )
    recognizers.append(plz_city_recognizer)

    return recognizers


class TextAnonymizer:
    """
    PII-Erkennung und Anonymisierung für Text.
    Verwendet Microsoft Presidio mit deutschen SpaCy-Modellen.

    Best Practices:
    - Niedrige Konfidenz für schwache Muster (z.B. PLZ = 5 Ziffern)
    - Kontextwörter erhöhen Konfidenz automatisch
    - LemmaContextAwareEnhancer für intelligente Kontexterkennung
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

        # Context Enhancer konfigurieren
        # Erhöht Konfidenz wenn Kontextwörter in der Nähe gefunden werden
        context_enhancer = LemmaContextAwareEnhancer(
            context_similarity_factor=0.45,      # Wie stark Kontext die Konfidenz erhöht
            min_score_with_context_similarity=0.4,  # Minimale Konfidenz mit Kontext
        )

        # Analyzer mit Context Enhancer
        self.analyzer = AnalyzerEngine(
            nlp_engine=nlp_engine,
            supported_languages=settings.supported_languages,
            context_aware_enhancer=context_enhancer,
        )

        # Custom Recognizers für deutsche Adressen hinzufügen
        for recognizer in create_german_address_recognizers():
            self.analyzer.registry.add_recognizer(recognizer)
            logger.info(f"Added custom recognizer: {recognizer.supported_entities}")

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

        # PII erkennen mit Mindest-Konfidenz
        # Niedrige Threshold (0.35) erlaubt auch schwache Muster mit Kontext
        results = self.analyzer.analyze(
            text=text,
            language=language,
            entities=settings.entities_to_anonymize,
            score_threshold=0.35,  # Erlaubt kontextverstärkte schwache Muster
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
                "DE_ADDRESS_FULL": OperatorConfig("replace", {"new_value": "[ADRESSE]"}),
                "DE_PLZ": OperatorConfig("replace", {"new_value": "[PLZ]"}),
                "DE_STREET_ADDRESS": OperatorConfig("replace", {"new_value": "[ADRESSE]"}),
                "IBAN_CODE": OperatorConfig("replace", {"new_value": "[IBAN]"}),
                "DATE_TIME": OperatorConfig("replace", {"new_value": "[DATUM]"}),
            },
        )

        return anonymized.text
