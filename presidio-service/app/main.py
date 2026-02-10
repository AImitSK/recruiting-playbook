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
