from enum import Enum


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
