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
    libgl1 \
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
