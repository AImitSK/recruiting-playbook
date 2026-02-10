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

/**
 * Job-Daten für Job-Finder (Mode B) mit ID und URLs
 */
export interface JobFinderJobData {
  id: number;
  title: string;
  url: string;
  applyUrl: string;
  description: string;
  requirements: string[];
  niceToHave: string[];
}

/**
 * Einzelnes Match-Ergebnis für Job-Finder
 */
export interface JobFinderMatch {
  jobId: number;
  jobTitle: string;
  jobUrl: string;
  applyUrl: string;
  score: number;
  category: 'high' | 'medium' | 'low';
  message: string;
  matchedSkills: string[];
  missingSkills: string[];
}

/**
 * Gesamtergebnis für Job-Finder (Mode B)
 */
export interface JobFinderResult {
  mode: 'job-finder';
  profile: {
    extractedSkills: string[];
    experienceYears: number | null;
    education: string | null;
  };
  matches: JobFinderMatch[];
  totalJobsAnalyzed: number;
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
   * Multi-Job-Analyse durchführen (Mode B: Job-Finder)
   *
   * Analysiert einen CV gegen mehrere Jobs und gibt die besten Matches zurück.
   */
  async analyzeJobFinder(
    cvText: string,
    jobs: JobFinderJobData[],
    limit: number
  ): Promise<JobFinderResult> {
    const systemPrompt = `Du bist ein KI-Recruiting-Assistent. Deine Aufgabe ist es, einen anonymisierten Lebenslauf gegen mehrere Stellenanzeigen zu analysieren und die besten Matches zu finden.

WICHTIG:
- Analysiere den Lebenslauf objektiv
- Vergleiche die extrahierten Qualifikationen mit JEDER Stelle
- Bewerte jede Stelle mit einem Score von 0-100
- Kategorisiere: high (>=70), medium (40-69), low (<40)
- Gib für jede Stelle spezifische Gründe an
- Sortiere nach Score (absteigend)
- Gib nur die Top-${limit} besten Matches zurück
- Die Antwort ist für den Bewerber selbst bestimmt. Sprich den Bewerber direkt mit "Sie" an (z.B. "Sie erfüllen...", "Ihre Erfahrung..."). Sprich NICHT über "den Kandidaten" in der dritten Person.

EXTRAKTIONS-SCHRITTE:
1. Extrahiere aus dem CV: Fähigkeiten, Erfahrungsjahre, Ausbildung
2. Für jede Stelle: Vergleiche Anforderungen mit CV-Profil
3. Score: Pflichtanforderungen (70%) + Nice-to-have (20%) + Branchenerfahrung (10%)

ANTWORT-FORMAT (JSON):
{
  "profile": {
    "extractedSkills": ["Skill 1", "Skill 2"],
    "experienceYears": 5,
    "education": "Ausbildung/Studium"
  },
  "matches": [
    {
      "jobId": 123,
      "score": 85,
      "category": "high",
      "message": "Kurze Erklärung in Sie-Form warum diese Stelle zu Ihnen passt",
      "matchedSkills": ["Skill A", "Skill B"],
      "missingSkills": ["Skill C"]
    }
  ],
  "totalJobsAnalyzed": ${jobs.length}
}`;

    const jobsText = jobs
      .map(
        (job) => `
---
ID: ${job.id}
Titel: ${job.title}
Beschreibung: ${job.description?.substring(0, 500) || 'Keine Beschreibung'}
Anforderungen: ${(job.requirements || []).join(', ') || 'Keine angegeben'}
Nice-to-have: ${(job.niceToHave || []).join(', ') || 'Keine angegeben'}
---`
      )
      .join('\n');

    const userPrompt = `ANONYMISIERTER LEBENSLAUF:
${cvText}

VERFÜGBARE STELLEN (${jobs.length} Stellen):
${jobsText}

Analysiere den Lebenslauf und finde die ${limit} besten Matches.
Antworte NUR mit validem JSON im angegebenen Format.`;

    // Claude API aufrufen
    const response = await this.client.messages.create({
      model: this.model,
      max_tokens: 2048, // Mehr Tokens für Multi-Job-Response
      system: systemPrompt,
      messages: [
        {
          role: 'user',
          content: userPrompt,
        },
      ],
    });

    // Response extrahieren
    const content = response.content[0];
    if (content.type !== 'text') {
      throw new Error('Unexpected response type');
    }

    // JSON parsen (mit Markdown-Block-Handling)
    let jsonStr = content.text.trim();
    const jsonMatch = jsonStr.match(/```(?:json)?\s*([\s\S]*?)```/);
    if (jsonMatch) {
      jsonStr = jsonMatch[1].trim();
    }

    const result = JSON.parse(jsonStr);

    // Job-URLs hinzufügen
    result.matches = result.matches.map((match: { jobId: number }) => {
      const job = jobs.find((j) => j.id === match.jobId);
      return {
        ...match,
        jobTitle: job?.title || 'Unbekannt',
        jobUrl: job?.url || '#',
        applyUrl: job?.applyUrl || '#',
      };
    });

    result.mode = 'job-finder';
    result.totalJobsAnalyzed = jobs.length;

    return result as JobFinderResult;
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

Analysiere, wie gut der Lebenslauf zur Stelle passt. Beachte:
1. MUSS-Anforderungen sind wichtiger als KANN-Anforderungen
2. Berufserfahrung in ähnlichen Positionen ist ein Plus
3. Fehlende Anforderungen sind nicht automatisch ein Ausschluss

WICHTIG: Die Antwort ist für den Bewerber selbst bestimmt. Sprich den Bewerber direkt mit "Sie" an (z.B. "Sie erfüllen..." oder "Ihre Erfahrung..."). Sprich NICHT über "den Kandidaten" in der dritten Person.

Antworte NUR mit einem validen JSON-Objekt in diesem Format:

{
  "score": <Zahl 0-100>,
  "category": "<low|medium|high>",
  "message": "<Kurze Begründung auf Deutsch in Sie-Form, max 2 Sätze>",
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
