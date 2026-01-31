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
