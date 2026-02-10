from PIL import Image
from pdf2image import convert_from_bytes
import pdfplumber
import io
from typing import List
import logging

logger = logging.getLogger(__name__)


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
            logger.error(f"PDF text extraction error: {e}")
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
            logger.error(f"PDF to image conversion error: {e}")
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
