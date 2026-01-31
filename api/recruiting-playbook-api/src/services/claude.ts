import Anthropic from '@anthropic-ai/sdk';

// OpenRouter Konfiguration
// Wichtig: OHNE /v1 - das Anthropic SDK fügt /v1/messages automatisch hinzu
const OPENROUTER_BASE_URL = 'https://openrouter.ai/api';

// Modell-Konstante (OpenRouter Model ID)
// Siehe: https://openrouter.ai/models
const DEFAULT_MODEL = 'anthropic/claude-haiku-4.5'; // $1/$5 pro MTok, 200K Context

export interface MatchResult {
  score: number; // 0-100
  category: 'low' | 'medium' | 'high';
  message: string;
  details?: {
    matchedSkills: string[];
    missingSkills: string[];
    recommendations: string[];
  };
}

export interface JobData {
  title: string;
  description: string;
  requirements: string[];
  niceToHave?: string[];
  location?: string;
  employmentType?: string;
}

export class ClaudeService {
  private client: Anthropic;
  private model: string;

  /**
   * @param apiKey - OpenRouter API Key
   * @param model - Optional: Modell überschreiben (default: claude-haiku-4.5)
   */
  constructor(apiKey: string, model?: string) {
    this.client = new Anthropic({
      apiKey,
      baseURL: OPENROUTER_BASE_URL,
    });
    this.model = model || DEFAULT_MODEL;
  }

  /**
   * CV mit Job matchen
   */
  async analyzeMatch(anonymizedCV: string, jobData: JobData): Promise<MatchResult> {
    const prompt = this.buildPrompt(anonymizedCV, jobData);

    const response = await this.client.messages.create({
      model: this.model,
      max_tokens: 1024,
      messages: [
        {
          role: 'user',
          content: prompt,
        },
      ],
    });

    // Response parsen
    const content = response.content[0];
    if (content.type !== 'text') {
      throw new Error('Unexpected response type');
    }

    return this.parseResponse(content.text);
  }

  /**
   * CV mit Job matchen (Vision für Bilder)
   */
  async analyzeMatchWithImage(
    imageBase64: string,
    mimeType: string,
    jobData: JobData
  ): Promise<MatchResult> {
    const prompt = this.buildPrompt('', jobData, true);

    const response = await this.client.messages.create({
      model: this.model,
      max_tokens: 1024,
      messages: [
        {
          role: 'user',
          content: [
            {
              type: 'image',
              source: {
                type: 'base64',
                media_type: mimeType as 'image/jpeg' | 'image/png' | 'image/webp' | 'image/gif',
                data: imageBase64,
              },
            },
            {
              type: 'text',
              text: prompt,
            },
          ],
        },
      ],
    });

    const content = response.content[0];
    if (content.type !== 'text') {
      throw new Error('Unexpected response type');
    }

    return this.parseResponse(content.text);
  }

  /**
   * Prompt für Matching-Analyse bauen
   */
  private buildPrompt(cvText: string, jobData: JobData, isImage: boolean = false): string {
    const cvSection = isImage
      ? 'Der Lebenslauf ist als Bild angehängt. Analysiere den sichtbaren Inhalt.'
      : `## Lebenslauf (anonymisiert)\n\n${cvText}`;

    return `Du bist ein erfahrener Recruiting-Experte. Deine Aufgabe ist es, einen Lebenslauf mit einer Stellenausschreibung zu vergleichen und einen Match-Score zu berechnen.

## Stellenausschreibung

**Titel:** ${jobData.title}

**Beschreibung:**
${jobData.description}

**Anforderungen (MUSS):**
${jobData.requirements.map((r) => `- ${r}`).join('\n')}

${jobData.niceToHave?.length ? `**Wünschenswert (KANN):**\n${jobData.niceToHave.map((r) => `- ${r}`).join('\n')}` : ''}

${cvSection}

## Aufgabe

Analysiere, wie gut der Kandidat zur Stelle passt. Beachte:
1. MUSS-Anforderungen sind wichtiger als KANN-Anforderungen
2. Berufserfahrung in ähnlichen Positionen ist ein Plus
3. Fehlende Anforderungen sind nicht automatisch ein Ausschluss

Antworte NUR mit einem validen JSON-Objekt in diesem Format:

{
  "score": <Zahl 0-100>,
  "category": "<low|medium|high>",
  "message": "<Kurze Begründung auf Deutsch, max 2 Sätze>",
  "matchedSkills": ["<Erfüllte Anforderung 1>", "<Erfüllte Anforderung 2>"],
  "missingSkills": ["<Fehlende Anforderung 1>"],
  "recommendations": ["<Tipp für Bewerbung>"]
}

Kategorien:
- "low" (0-40%): Eher nicht passend
- "medium" (41-70%): Teilweise passend
- "high" (71-100%): Gute Übereinstimmung

Antworte NUR mit dem JSON, ohne Erklärungen davor oder danach.`;
  }

  /**
   * Claude Response parsen
   */
  private parseResponse(text: string): MatchResult {
    // JSON aus Response extrahieren
    let jsonStr = text.trim();

    // Falls in Markdown Code-Block
    const jsonMatch = jsonStr.match(/```(?:json)?\s*([\s\S]*?)```/);
    if (jsonMatch) {
      jsonStr = jsonMatch[1].trim();
    }

    try {
      const data = JSON.parse(jsonStr);

      // Validierung
      const score = Math.max(0, Math.min(100, Number(data.score) || 0));
      const category = this.scoreToCategory(score);

      return {
        score,
        category,
        message: String(data.message || this.getDefaultMessage(category)),
        details: {
          matchedSkills: Array.isArray(data.matchedSkills) ? data.matchedSkills : [],
          missingSkills: Array.isArray(data.missingSkills) ? data.missingSkills : [],
          recommendations: Array.isArray(data.recommendations) ? data.recommendations : [],
        },
      };
    } catch {
      // Fallback bei Parse-Fehler
      console.error('Failed to parse Claude response:', text);

      return {
        score: 50,
        category: 'medium',
        message: 'Die Analyse konnte nicht vollständig durchgeführt werden.',
      };
    }
  }

  /**
   * Score zu Kategorie mappen
   */
  private scoreToCategory(score: number): 'low' | 'medium' | 'high' {
    if (score <= 40) return 'low';
    if (score <= 70) return 'medium';
    return 'high';
  }

  /**
   * Default-Nachrichten pro Kategorie
   */
  private getDefaultMessage(category: 'low' | 'medium' | 'high'): string {
    const messages = {
      low: 'Diese Stelle passt wahrscheinlich nicht zu Ihrem Profil.',
      medium: 'Sie erfüllen einige Anforderungen. Eine Bewerbung könnte sich lohnen.',
      high: 'Ihr Profil passt gut zu dieser Stelle!',
    };
    return messages[category];
  }
}
