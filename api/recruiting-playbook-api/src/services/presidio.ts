export interface AnonymizeResult {
  type: 'text' | 'image';
  anonymizedText?: string;
  anonymizedImageBase64?: string;
  imageMimeType?: string;
  originalType: string;
  piiFound?: number;
}

export class PresidioService {
  constructor(
    private baseUrl: string,
    private apiKey?: string
  ) {}

  /**
   * Dokument anonymisieren
   */
  async anonymize(
    fileBuffer: ArrayBuffer,
    filename: string,
    outputFormat: 'text' | 'auto' = 'auto'
  ): Promise<AnonymizeResult> {
    const formData = new FormData();
    formData.append('file', new Blob([fileBuffer]), filename);
    formData.append('output_format', outputFormat);
    formData.append('language', 'de');

    const headers: Record<string, string> = {};
    if (this.apiKey) {
      headers['X-API-Key'] = this.apiKey;
    }

    const response = await fetch(`${this.baseUrl}/api/v1/anonymize`, {
      method: 'POST',
      body: formData,
      headers,
    });

    if (!response.ok) {
      const error = await response.text();
      throw new Error(`Presidio error: ${response.status} - ${error}`);
    }

    const contentType = response.headers.get('content-type');

    // JSON Response (Text)
    if (contentType?.includes('application/json')) {
      const data = (await response.json()) as {
        anonymized_text?: string;
        original_type?: string;
        pii_found?: number;
      };
      return {
        type: 'text',
        anonymizedText: data.anonymized_text,
        originalType: data.original_type || 'unknown',
        piiFound: data.pii_found,
      };
    }

    // Binary Response (Bild)
    const buffer = await response.arrayBuffer();
    const base64 = this.arrayBufferToBase64(buffer);

    return {
      type: 'image',
      anonymizedImageBase64: base64,
      imageMimeType: contentType || 'image/png',
      originalType: response.headers.get('X-Original-Type') || 'unknown',
    };
  }

  /**
   * ArrayBuffer zu Base64 konvertieren (Worker-kompatibel)
   */
  private arrayBufferToBase64(buffer: ArrayBuffer): string {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
      binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary);
  }
}
