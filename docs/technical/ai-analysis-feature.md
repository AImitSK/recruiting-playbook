# KI-Analyse Feature

## Übersicht

Das **Killer-Feature** des Plugins: KI-gestützte Bewerber-Analyse für intelligentes Job-Matching.

```
┌─────────────────────────────────────────────────────────────────┐
│                      KI-ANALYSE SYSTEM                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│                     BEWERBER UPLOAD                             │
│                          │                                      │
│        ┌─────────────────┼─────────────────┐                   │
│        │                 │                 │                    │
│        ▼                 ▼                 ▼                    │
│   ┌─────────┐      ┌─────────┐      ┌─────────┐               │
│   │  MODUS  │      │  MODUS  │      │  MODUS  │               │
│   │    A    │      │    B    │      │    C    │               │
│   │         │      │         │      │         │               │
│   │  Job-   │      │  Job-   │      │ Chancen │               │
│   │  Match  │      │ Finder  │      │  Check  │               │
│   └─────────┘      └─────────┘      └─────────┘               │
│        │                 │                 │                    │
│        ▼                 ▼                 ▼                    │
│   "Passe ich      "Welche Jobs      "Wie stehen               │
│    zu diesem       passen zu         meine                     │
│    Job?"           mir?"             Chancen?"                  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Die drei KI-Modi

| Modus | Name | Frage | Wo verfügbar |
|-------|------|-------|--------------|
| **A** | Job-Match | "Passe ich zu diesem Job?" | Stellen-Einzelseite |
| **B** | Job-Finder | "Welche Jobs passen zu mir?" | Karriere-Seite, Widget |
| **C** | Chancen-Check | "Wie stehen meine Chancen?" | Nach Upload, vor Bewerbung |

---

## Modus A: Job-Match

> "Passe ich zu diesem Job?"

Der Bewerber ist auf einer **konkreten Stellenanzeige** und möchte wissen, ob er/sie qualifiziert ist.

### User Flow

```
┌─────────────────────────────────────────────────────────────────┐
│               STELLEN-EINZELSEITE                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  # Fachkrankenpfleger Intensiv (m/w/d)                         │
│  📍 Bielefeld | ⏰ Vollzeit | 💰 ab 35€/Std                    │
│                                                                 │
│  [Beschreibung...]                                              │
│                                                                 │
│  ═══════════════════════════════════════════════════════════   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  🤖 KI-QUALIFIKATIONSCHECK                              │   │
│  │                                                          │   │
│  │  Finden Sie in Sekunden heraus, ob diese Stelle         │   │
│  │  zu Ihnen passt!                                         │   │
│  │                                                          │   │
│  │  📄 Lebenslauf hochladen (PDF, DOC)                     │   │
│  │  ┌─────────────────────────────────────────────────┐   │   │
│  │  │                                                  │   │   │
│  │  │         Dateien hier ablegen                    │   │   │
│  │  │              oder klicken                        │   │   │
│  │  │                                                  │   │   │
│  │  └─────────────────────────────────────────────────┘   │   │
│  │                                                          │   │
│  │  Optional: Zeugnisse, Zertifikate                       │   │
│  │  [+ Weitere Datei]                                       │   │
│  │                                                          │   │
│  │  [✓] Ich stimme der einmaligen Analyse zu               │   │
│  │                                                          │   │
│  │  [🔍 QUALIFIKATION PRÜFEN]                              │   │
│  │                                                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Ergebnis

```
┌─────────────────────────────────────────────────────────────────┐
│                    JOB-MATCH ERGEBNIS                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│              ┌─────────────────────────┐                       │
│              │                         │                       │
│              │   ████████████████░░    │                       │
│              │                         │                       │
│              │         82%             │                       │
│              │                         │                       │
│              │     MATCH-SCORE         │                       │
│              └─────────────────────────┘                       │
│                                                                 │
│   Sie sind gut qualifiziert für diese Position!                │
│                                                                 │
│   ───────────────────────────────────────────────────────────  │
│                                                                 │
│   ✅ Erfüllte Anforderungen:                                   │
│                                                                 │
│   • Examinierte Pflegekraft ✓                                  │
│   • Berufserfahrung: 8 Jahre (gefordert: 3+) ✓                │
│   • Fachweiterbildung Intensivpflege ✓                        │
│   • Schichtbereitschaft ✓                                      │
│                                                                 │
│   ⚠️ Teilweise erfüllt:                                        │
│                                                                 │
│   • Erfahrung mit Beatmungspatienten                           │
│     → In Ihren Unterlagen nicht eindeutig erkennbar            │
│                                                                 │
│   ❌ Fehlend:                                                   │
│                                                                 │
│   • Führungserfahrung                                          │
│     → Optional, aber von Vorteil                               │
│                                                                 │
│   ───────────────────────────────────────────────────────────  │
│                                                                 │
│   💡 Empfehlung:                                               │
│   Bewerben Sie sich! Ihre Qualifikation passt sehr gut.        │
│   Erwähnen Sie im Anschreiben Ihre Intensivpflege-Erfahrung.  │
│                                                                 │
│   ───────────────────────────────────────────────────────────  │
│                                                                 │
│   [JETZT BEWERBEN - Daten werden übernommen]                   │
│                                                                 │
│   ┌─────────────────────────────────────────────────────────┐  │
│   │ 📋 Erkannte Daten (vorausgefüllt):                      │  │
│   │                                                          │  │
│   │ Name: Max Mustermann                                     │  │
│   │ E-Mail: m.mustermann@email.de                           │  │
│   │ Telefon: 0170 1234567                                    │  │
│   │ Beruf: Fachkrankenpfleger Intensivpflege               │  │
│   │                                                          │  │
│   │ [Daten bearbeiten]                                       │  │
│   └─────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Modus B: Job-Finder

> "Welche Jobs passen zu mir?"

Der Bewerber ist auf der **Karriere-Übersichtsseite** oder nutzt ein Widget und möchte passende Stellen finden.

### User Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    KARRIERE-SEITE                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  # Karriere bei Samaritano                                     │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                                                          │   │
│  │  🤖 KI-JOB-FINDER                                       │   │
│  │                                                          │   │
│  │  Laden Sie Ihren Lebenslauf hoch und wir finden        │   │
│  │  die perfekten Stellen für Sie!                         │   │
│  │                                                          │   │
│  │  ┌─────────────────────────────────────────────────┐   │   │
│  │  │         📄 Lebenslauf hochladen                 │   │   │
│  │  └─────────────────────────────────────────────────┘   │   │
│  │                                                          │   │
│  │  [🔍 PASSENDE STELLEN FINDEN]                           │   │
│  │                                                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ─────────── ODER MANUELL SUCHEN ───────────                   │
│                                                                 │
│  [Alle Stellenangebote anzeigen]                               │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Ergebnis

```
┌─────────────────────────────────────────────────────────────────┐
│                   JOB-FINDER ERGEBNIS                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  👤 Erkanntes Profil:                                          │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Max Mustermann                                          │   │
│  │  🎓 Examinierter Altenpfleger                           │   │
│  │  📅 12 Jahre Berufserfahrung                            │   │
│  │  ⭐ Zusatzqualifikation: Intensivpflege, Wundmanagement │   │
│  │  📍 Wohnort: Minden                                     │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ═══════════════════════════════════════════════════════════   │
│                                                                 │
│  🎯 TOP-MATCHES FÜR SIE (5 von 12 Stellen):                   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                                                          │   │
│  │  ██████████ 98%   Fachkrankenpfleger Intensiv (m/w/d)  │   │
│  │                    📍 Bielefeld | 💰 35€/Std            │   │
│  │                    ⏰ Vollzeit, Teilzeit                 │   │
│  │                                                          │   │
│  │  Warum dieser Match:                                     │   │
│  │  • Ihre Intensivpflege-Weiterbildung passt perfekt      │   │
│  │  • Ihre Erfahrung übertrifft die Anforderungen          │   │
│  │                                                          │   │
│  │  [DETAILS ANSEHEN]  [DIREKT BEWERBEN]                   │   │
│  │                                                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                                                          │   │
│  │  █████████░ 92%   Altenpfleger (m/w/d)                  │   │
│  │                    📍 Hannover | 💰 32€/Std             │   │
│  │                    ⏰ Vollzeit, Teilzeit, Minijob        │   │
│  │                                                          │   │
│  │  Warum dieser Match:                                     │   │
│  │  • Exakt Ihr Ausbildungsberuf                           │   │
│  │  • Wundmanagement-Zertifikat ist ein Plus              │   │
│  │                                                          │   │
│  │  [DETAILS ANSEHEN]  [DIREKT BEWERBEN]                   │   │
│  │                                                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                                                          │   │
│  │  ████████░░ 85%   Pflegefachkraft Nachtdienst          │   │
│  │                    📍 Minden | 💰 30€/Std + Zulagen     │   │
│  │                    ⏰ Teilzeit                           │   │
│  │                                                          │   │
│  │  [DETAILS ANSEHEN]  [DIREKT BEWERBEN]                   │   │
│  │                                                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  [Weitere Matches anzeigen (2)]                                │
│                                                                 │
│  ───────────────────────────────────────────────────────────   │
│                                                                 │
│  📧 Keine passende Stelle dabei?                               │
│  [Im Talent-Pool registrieren - Wir melden uns!]              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Modus C: Chancen-Check

> "Wie stehen meine Chancen?"

Nach dem Upload (in Modus A oder B) kann der Bewerber eine **tiefere Analyse** seiner Einstellungschancen anfordern.

### Ergebnis

```
┌─────────────────────────────────────────────────────────────────┐
│                    CHANCEN-CHECK                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│         Ihre Einstellungschancen für diese Stelle:             │
│                                                                 │
│                    ┌─────────────┐                             │
│                    │             │                             │
│                    │     87%     │                             │
│                    │             │                             │
│                    └─────────────┘                             │
│                    SEHR GUT! 🎉                                │
│                                                                 │
│  ═══════════════════════════════════════════════════════════   │
│                                                                 │
│  📊 DETAILANALYSE                                              │
│                                                                 │
│  ───────────────────────────────────────────────────────────   │
│                                                                 │
│  ✅ Das spricht FÜR Sie (Score: +45):                         │
│                                                                 │
│  │ Fachliche Qualifikation          ████████████████ +20     │
│  │ • Examinierte Pflegekraft (Pflicht erfüllt)               │
│  │ • Fachweiterbildung Intensivpflege (Bonus!)               │
│  │                                                            │
│  │ Berufserfahrung                  ██████████████░░ +15     │
│  │ • 12 Jahre (gefordert: 3 Jahre)                           │
│  │ • Erfahrung in vergleichbarer Position                    │
│  │                                                            │
│  │ Soft Skills (aus Unterlagen)     ████████░░░░░░░░ +10     │
│  │ • Teamfähigkeit erkennbar                                 │
│  │ • Belastbarkeit (Schichtdienst-Erfahrung)                │
│                                                                 │
│  ───────────────────────────────────────────────────────────   │
│                                                                 │
│  ⚠️ Das könnte GEGEN Sie sprechen (Score: -8):                │
│                                                                 │
│  │ Führungserfahrung                ████░░░░░░░░░░░░ -3      │
│  │ • Nicht erkennbar (optional, aber gewünscht)              │
│  │                                                            │
│  │ Spezielle Kenntnisse             ██████░░░░░░░░░░ -5      │
│  │ • Beatmungspflege nicht explizit genannt                  │
│                                                                 │
│  ───────────────────────────────────────────────────────────   │
│                                                                 │
│  💡 SO VERBESSERN SIE IHRE CHANCEN:                           │
│                                                                 │
│  1. Erwähnen Sie im Anschreiben:                              │
│     → Ihre Erfahrung mit Beatmungspatienten (falls vorhanden) │
│     → Konkrete Beispiele für Teamführung/Anleitung            │
│                                                                 │
│  2. Heben Sie hervor:                                          │
│     → Ihre Intensivpflege-Weiterbildung (großes Plus!)        │
│     → Ihre überdurchschnittliche Berufserfahrung              │
│                                                                 │
│  3. Wohnort-Vorteil:                                           │
│     → Sie wohnen nur 25km vom Einsatzort entfernt ✓           │
│                                                                 │
│  ═══════════════════════════════════════════════════════════   │
│                                                                 │
│  🎯 FAZIT:                                                     │
│                                                                 │
│  Mit 87% Einstellungschance gehören Sie zu den Top-Kandidaten │
│  für diese Position. Wir empfehlen eine Bewerbung!            │
│                                                                 │
│  ───────────────────────────────────────────────────────────   │
│                                                                 │
│       [🚀 JETZT MIT OPTIMIERTEM PROFIL BEWERBEN]              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Technische Architektur

```
┌─────────────────────────────────────────────────────────────────┐
│                    SYSTEM-ARCHITEKTUR                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  FRONTEND (Browser)                                             │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Alpine.js Component                                     │   │
│  │  • Drag & Drop Upload                                    │   │
│  │  • Progress-Anzeige                                      │   │
│  │  • Ergebnis-Darstellung                                  │   │
│  └──────────────────────────┬──────────────────────────────┘   │
│                             │                                   │
│                             │ REST API                          │
│                             ▼                                   │
│  WORDPRESS PLUGIN                                               │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                                                          │   │
│  │  ┌──────────────┐    ┌──────────────┐                   │   │
│  │  │   Upload     │    │   Analyse    │                   │   │
│  │  │   Handler    │───▶│   Service    │                   │   │
│  │  └──────────────┘    └──────┬───────┘                   │   │
│  │                             │                            │   │
│  │  ┌──────────────┐    ┌──────▼───────┐                   │   │
│  │  │   Limit      │◀───│   AI        │                    │   │
│  │  │   Checker    │    │   Client    │                    │   │
│  │  └──────────────┘    └──────┬───────┘                   │   │
│  │                             │                            │   │
│  └─────────────────────────────┼────────────────────────────┘   │
│                                │                                │
│                                │ API Call                       │
│                                ▼                                │
│  ANTHROPIC CLAUDE API                                           │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  • PDF/DOC Text-Extraktion                              │   │
│  │  • Profil-Analyse                                        │   │
│  │  • Job-Matching                                          │   │
│  │  • Chancen-Berechnung                                    │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Komponenten

```php
<?php
// src/AI/Services/AnalysisService.php

namespace RecruitingPlaybook\AI\Services;

use RecruitingPlaybook\AI\Client\ClaudeClient;
use RecruitingPlaybook\AI\Parser\DocumentParser;
use RecruitingPlaybook\AI\Analyzer\ProfileAnalyzer;
use RecruitingPlaybook\AI\Analyzer\JobMatcher;
use RecruitingPlaybook\AI\Analyzer\ChanceCalculator;

class AnalysisService {
    
    private ClaudeClient $client;
    private DocumentParser $parser;
    private ProfileAnalyzer $profile_analyzer;
    private JobMatcher $job_matcher;
    private ChanceCalculator $chance_calculator;
    private LimitChecker $limit_checker;
    
    /**
     * Modus A: Job-Match
     * 
     * Prüft ob ein Bewerber zu einer konkreten Stelle passt
     */
    public function analyze_job_match( array $files, int $job_id ): JobMatchResult {
        // Limit prüfen
        $this->limit_checker->check_and_decrement();
        
        // 1. Dokumente parsen
        $document_text = $this->parser->extract_text( $files );
        
        // 2. Job-Anforderungen laden
        $job = get_post( $job_id );
        $requirements = $this->get_job_requirements( $job_id );
        
        // 3. AI-Analyse
        $prompt = $this->build_job_match_prompt( $document_text, $job, $requirements );
        $response = $this->client->analyze( $prompt );
        
        // 4. Ergebnis strukturieren
        return new JobMatchResult( $response );
    }
    
    /**
     * Modus B: Job-Finder
     * 
     * Findet passende Stellen für einen Bewerber
     */
    public function find_matching_jobs( array $files, int $limit = 5 ): JobFinderResult {
        // Limit prüfen
        $this->limit_checker->check_and_decrement();
        
        // 1. Dokumente parsen
        $document_text = $this->parser->extract_text( $files );
        
        // 2. Alle aktiven Stellen laden
        $jobs = $this->get_all_active_jobs();
        
        // 3. AI-Analyse
        $prompt = $this->build_job_finder_prompt( $document_text, $jobs );
        $response = $this->client->analyze( $prompt );
        
        // 4. Ergebnis strukturieren & sortieren
        return new JobFinderResult( $response, $limit );
    }
    
    /**
     * Modus C: Chancen-Check
     * 
     * Berechnet Einstellungschancen für eine konkrete Stelle
     */
    public function calculate_chances( array $files, int $job_id ): ChanceResult {
        // Limit prüfen
        $this->limit_checker->check_and_decrement();
        
        // 1. Dokumente parsen
        $document_text = $this->parser->extract_text( $files );
        
        // 2. Job-Details laden
        $job = get_post( $job_id );
        $requirements = $this->get_job_requirements( $job_id );
        
        // 3. AI-Analyse (detaillierter als Job-Match)
        $prompt = $this->build_chance_prompt( $document_text, $job, $requirements );
        $response = $this->client->analyze( $prompt );
        
        // 4. Ergebnis strukturieren
        return new ChanceResult( $response );
    }
    
    /**
     * Profil aus Dokumenten extrahieren (für Formular-Vorausfüllung)
     */
    public function extract_profile( array $files ): ProfileData {
        $document_text = $this->parser->extract_text( $files );
        
        $prompt = $this->build_profile_extraction_prompt( $document_text );
        $response = $this->client->analyze( $prompt );
        
        return new ProfileData( $response );
    }
}
```

### Claude Client

```php
<?php
// src/AI/Client/ClaudeClient.php

namespace RecruitingPlaybook\AI\Client;

class ClaudeClient {
    
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL = 'claude-3-5-sonnet-20241022';
    
    private string $api_key;
    
    public function __construct() {
        $settings = get_option( 'rp_settings' );
        $this->api_key = $settings['ai']['anthropic_api_key'] ?? '';
    }
    
    /**
     * Analyse durchführen
     */
    public function analyze( string $prompt, array $options = [] ): array {
        if ( empty( $this->api_key ) ) {
            throw new \Exception( __( 'Anthropic API Key nicht konfiguriert.', 'recruiting-playbook' ) );
        }
        
        $response = wp_remote_post( self::API_URL, [
            'timeout' => 60,
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $this->api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode( [
                'model'      => $options['model'] ?? self::MODEL,
                'max_tokens' => $options['max_tokens'] ?? 4096,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ] ),
        ] );
        
        if ( is_wp_error( $response ) ) {
            throw new \Exception( $response->get_error_message() );
        }
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $body['error'] ) ) {
            throw new \Exception( $body['error']['message'] ?? 'Unknown API error' );
        }
        
        return $body;
    }
}
```

### Document Parser

```php
<?php
// src/AI/Parser/DocumentParser.php

namespace RecruitingPlaybook\AI\Parser;

class DocumentParser {
    
    /**
     * Text aus Dokumenten extrahieren
     */
    public function extract_text( array $files ): string {
        $texts = [];
        
        foreach ( $files as $file ) {
            $extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
            
            switch ( $extension ) {
                case 'pdf':
                    $texts[] = $this->extract_from_pdf( $file['tmp_name'] );
                    break;
                    
                case 'doc':
                case 'docx':
                    $texts[] = $this->extract_from_word( $file['tmp_name'] );
                    break;
                    
                case 'txt':
                    $texts[] = file_get_contents( $file['tmp_name'] );
                    break;
            }
        }
        
        return implode( "\n\n---\n\n", $texts );
    }
    
    /**
     * PDF Text-Extraktion
     */
    private function extract_from_pdf( string $path ): string {
        // Option 1: pdftotext (Server-Tool)
        if ( $this->command_exists( 'pdftotext' ) ) {
            $output = shell_exec( sprintf( 'pdftotext -layout %s -', escapeshellarg( $path ) ) );
            return $output ?: '';
        }
        
        // Option 2: Smalot/PdfParser (PHP Library)
        if ( class_exists( '\Smalot\PdfParser\Parser' ) ) {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile( $path );
            return $pdf->getText();
        }
        
        throw new \Exception( __( 'PDF-Extraktion nicht verfügbar.', 'recruiting-playbook' ) );
    }
    
    /**
     * Word Text-Extraktion
     */
    private function extract_from_word( string $path ): string {
        // PhpWord Library
        if ( class_exists( '\PhpOffice\PhpWord\IOFactory' ) ) {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load( $path );
            $text = '';
            
            foreach ( $phpWord->getSections() as $section ) {
                foreach ( $section->getElements() as $element ) {
                    if ( method_exists( $element, 'getText' ) ) {
                        $text .= $element->getText() . "\n";
                    }
                }
            }
            
            return $text;
        }
        
        throw new \Exception( __( 'Word-Extraktion nicht verfügbar.', 'recruiting-playbook' ) );
    }
    
    private function command_exists( string $command ): bool {
        return ! empty( shell_exec( "which $command 2>/dev/null" ) );
    }
}
```

### Limit Checker

```php
<?php
// src/AI/Services/LimitChecker.php

namespace RecruitingPlaybook\AI\Services;

class LimitChecker {
    
    private const MONTHLY_LIMIT = 100;
    private const OPTION_KEY = 'rp_ai_usage';
    
    /**
     * Prüft und reduziert das Limit
     */
    public function check_and_decrement(): void {
        $usage = $this->get_usage();
        
        // Monat prüfen
        if ( $usage['month'] !== date( 'Y-m' ) ) {
            // Neuer Monat: Reset
            $usage = [
                'month' => date( 'Y-m' ),
                'count' => 0,
                'extra' => $usage['extra'] ?? 0, // Extra-Pakete bleiben
            ];
        }
        
        $available = $this->get_available( $usage );
        
        if ( $available <= 0 ) {
            throw new \Exception( 
                __( 'Analyse-Limit erreicht. Bitte kaufen Sie ein Extra-Paket.', 'recruiting-playbook' )
            );
        }
        
        // Verbrauch erhöhen
        $usage['count']++;
        
        // Extra-Paket angreifen wenn Basis aufgebraucht
        if ( $usage['count'] > self::MONTHLY_LIMIT && $usage['extra'] > 0 ) {
            $usage['extra']--;
            $usage['count'] = self::MONTHLY_LIMIT; // Basis bleibt am Limit
        }
        
        update_option( self::OPTION_KEY, $usage );
    }
    
    /**
     * Verfügbare Analysen
     */
    public function get_available( ?array $usage = null ): int {
        $usage = $usage ?? $this->get_usage();
        
        // Monatsprüfung
        if ( $usage['month'] !== date( 'Y-m' ) ) {
            return self::MONTHLY_LIMIT + ( $usage['extra'] ?? 0 );
        }
        
        $remaining_monthly = max( 0, self::MONTHLY_LIMIT - $usage['count'] );
        $extra = $usage['extra'] ?? 0;
        
        return $remaining_monthly + $extra;
    }
    
    /**
     * Extra-Paket hinzufügen
     */
    public function add_extra_pack( int $amount = 50 ): void {
        $usage = $this->get_usage();
        $usage['extra'] = ( $usage['extra'] ?? 0 ) + $amount;
        update_option( self::OPTION_KEY, $usage );
    }
    
    /**
     * Usage-Status für Dashboard
     */
    public function get_status(): array {
        $usage = $this->get_usage();
        
        // Reset wenn neuer Monat
        if ( $usage['month'] !== date( 'Y-m' ) ) {
            $usage = [
                'month' => date( 'Y-m' ),
                'count' => 0,
                'extra' => $usage['extra'] ?? 0,
            ];
        }
        
        return [
            'used'      => $usage['count'],
            'limit'     => self::MONTHLY_LIMIT,
            'extra'     => $usage['extra'] ?? 0,
            'available' => $this->get_available( $usage ),
            'month'     => $usage['month'],
            'percent'   => min( 100, round( ( $usage['count'] / self::MONTHLY_LIMIT ) * 100 ) ),
        ];
    }
    
    private function get_usage(): array {
        return get_option( self::OPTION_KEY, [
            'month' => date( 'Y-m' ),
            'count' => 0,
            'extra' => 0,
        ] );
    }
}
```

### Prompts

```php
<?php
// src/AI/Prompts/AnalysisPrompts.php

namespace RecruitingPlaybook\AI\Prompts;

class AnalysisPrompts {
    
    /**
     * Job-Match Prompt (Modus A)
     */
    public static function job_match( string $cv_text, string $job_title, string $job_description, array $requirements ): string {
        $requirements_text = implode( "\n", array_map( fn( $r ) => "- $r", $requirements ) );
        
        return <<<PROMPT
Du bist ein erfahrener HR-Experte. Analysiere den folgenden Lebenslauf und prüfe die Eignung für die Stellenanzeige.

## STELLENANZEIGE
Titel: {$job_title}

Beschreibung:
{$job_description}

Anforderungen:
{$requirements_text}

## LEBENSLAUF DES BEWERBERS
{$cv_text}

## AUFGABE
Analysiere die Eignung des Bewerbers für diese Stelle und gib eine strukturierte Antwort im folgenden JSON-Format:

```json
{
    "match_score": 0-100,
    "summary": "Kurze Zusammenfassung der Eignung (1-2 Sätze)",
    "profile": {
        "name": "Name falls erkennbar",
        "email": "E-Mail falls erkennbar",
        "phone": "Telefon falls erkennbar",
        "profession": "Aktueller Beruf/Position",
        "experience_years": 0
    },
    "fulfilled": [
        {
            "requirement": "Anforderung",
            "detail": "Wie erfüllt"
        }
    ],
    "partial": [
        {
            "requirement": "Anforderung",
            "detail": "Was fehlt oder unklar ist"
        }
    ],
    "missing": [
        {
            "requirement": "Anforderung",
            "optional": true/false,
            "detail": "Erklärung"
        }
    ],
    "recommendation": "Bewerbungsempfehlung und Tipps (2-3 Sätze)"
}
```

Antworte NUR mit dem JSON, keine zusätzlichen Erklärungen.
PROMPT;
    }
    
    /**
     * Job-Finder Prompt (Modus B)
     */
    public static function job_finder( string $cv_text, array $jobs ): string {
        $jobs_text = '';
        foreach ( $jobs as $job ) {
            $jobs_text .= "ID: {$job['id']}\n";
            $jobs_text .= "Titel: {$job['title']}\n";
            $jobs_text .= "Standort: {$job['location']}\n";
            $jobs_text .= "Anforderungen: {$job['requirements']}\n";
            $jobs_text .= "---\n";
        }
        
        return <<<PROMPT
Du bist ein erfahrener HR-Experte und Karriereberater. Analysiere den Lebenslauf und finde die am besten passenden Stellen.

## LEBENSLAUF
{$cv_text}

## VERFÜGBARE STELLEN
{$jobs_text}

## AUFGABE
Analysiere die Eignung des Bewerbers für ALLE Stellen und gib eine Rangliste der besten Matches.

Antworte im folgenden JSON-Format:

```json
{
    "profile": {
        "name": "Name",
        "profession": "Beruf",
        "experience_years": 0,
        "key_skills": ["Skill 1", "Skill 2"],
        "location": "Wohnort falls erkennbar"
    },
    "matches": [
        {
            "job_id": 123,
            "score": 0-100,
            "reasons": ["Grund 1", "Grund 2"]
        }
    ]
}
```

Sortiere "matches" absteigend nach Score. Nur Stellen mit Score >= 60 aufnehmen.
Antworte NUR mit dem JSON.
PROMPT;
    }
    
    /**
     * Chancen-Check Prompt (Modus C)
     */
    public static function chance_check( string $cv_text, string $job_title, string $job_description, array $requirements ): string {
        $requirements_text = implode( "\n", array_map( fn( $r ) => "- $r", $requirements ) );
        
        return <<<PROMPT
Du bist ein erfahrener Headhunter mit 20 Jahren Erfahrung im Recruiting. Analysiere die Einstellungschancen dieses Bewerbers.

## STELLENANZEIGE
Titel: {$job_title}

Beschreibung:
{$job_description}

Anforderungen:
{$requirements_text}

## LEBENSLAUF DES BEWERBERS
{$cv_text}

## AUFGABE
Berechne die realistische Einstellungschance (0-100%) basierend auf:
- Fachliche Qualifikation (40% Gewichtung)
- Berufserfahrung (30% Gewichtung)  
- Zusatzqualifikationen (20% Gewichtung)
- Soft Skills / Cultural Fit (10% Gewichtung)

Antworte im JSON-Format:

```json
{
    "chance_percent": 0-100,
    "chance_label": "Sehr gut / Gut / Moderat / Gering",
    "factors_positive": [
        {
            "category": "Fachliche Qualifikation",
            "points": 0-20,
            "details": ["Detail 1", "Detail 2"]
        }
    ],
    "factors_negative": [
        {
            "category": "Kategorie",
            "points": -X,
            "details": ["Was fehlt"],
            "improvable": true/false
        }
    ],
    "tips": [
        {
            "type": "anschreiben",
            "tip": "Konkreter Tipp"
        }
    ],
    "conclusion": "Fazit und Empfehlung (2-3 Sätze)"
}
```

Sei realistisch aber ermutigend. Antworte NUR mit dem JSON.
PROMPT;
    }
    
    /**
     * Profil-Extraktion (für Formular-Vorausfüllung)
     */
    public static function profile_extraction( string $cv_text ): string {
        return <<<PROMPT
Extrahiere die Kontaktdaten und das Berufsprofil aus diesem Lebenslauf.

## LEBENSLAUF
{$cv_text}

## AUFGABE
Extrahiere folgende Informationen falls vorhanden:

```json
{
    "first_name": "",
    "last_name": "",
    "email": "",
    "phone": "",
    "address": {
        "street": "",
        "zip": "",
        "city": ""
    },
    "profession": "Aktuelle Berufsbezeichnung",
    "experience_years": 0,
    "highest_education": "Höchster Abschluss",
    "skills": ["Skill 1", "Skill 2"],
    "languages": [
        {"language": "Deutsch", "level": "Muttersprache"}
    ],
    "available_from": "Datum falls genannt"
}
```

Nur Felder befüllen, die im Text erkennbar sind. Antworte NUR mit dem JSON.
PROMPT;
    }
}
```

---

## REST API Endpoints

```php
<?php
// src/AI/Api/AnalysisController.php

namespace RecruitingPlaybook\AI\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class AnalysisController {
    
    public function register_routes(): void {
        register_rest_route( 'recruiting/v1', '/ai/job-match', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'job_match' ],
            'permission_callback' => '__return_true', // Öffentlich für Bewerber
        ] );
        
        register_rest_route( 'recruiting/v1', '/ai/job-finder', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'job_finder' ],
            'permission_callback' => '__return_true',
        ] );
        
        register_rest_route( 'recruiting/v1', '/ai/chance-check', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'chance_check' ],
            'permission_callback' => '__return_true',
        ] );
        
        register_rest_route( 'recruiting/v1', '/ai/usage', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_usage' ],
            'permission_callback' => [ $this, 'check_admin' ],
        ] );
    }
    
    /**
     * Modus A: Job-Match
     */
    public function job_match( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        // AI-Addon Lizenz prüfen
        if ( ! rp_has_ai() ) {
            return new WP_Error( 'ai_not_licensed', 'AI-Addon nicht aktiviert', [ 'status' => 403 ] );
        }
        
        $files = $request->get_file_params();
        $job_id = $request->get_param( 'job_id' );
        
        if ( empty( $files['documents'] ) ) {
            return new WP_Error( 'no_files', 'Keine Dokumente hochgeladen', [ 'status' => 400 ] );
        }
        
        if ( ! $job_id ) {
            return new WP_Error( 'no_job', 'Keine Stelle angegeben', [ 'status' => 400 ] );
        }
        
        try {
            $service = new \RecruitingPlaybook\AI\Services\AnalysisService();
            $result = $service->analyze_job_match( $files['documents'], $job_id );
            
            return new WP_REST_Response( [
                'success' => true,
                'data'    => $result->to_array(),
            ] );
            
        } catch ( \Exception $e ) {
            return new WP_Error( 'analysis_failed', $e->getMessage(), [ 'status' => 500 ] );
        }
    }
    
    /**
     * Modus B: Job-Finder
     */
    public function job_finder( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        if ( ! rp_has_ai() ) {
            return new WP_Error( 'ai_not_licensed', 'AI-Addon nicht aktiviert', [ 'status' => 403 ] );
        }
        
        $files = $request->get_file_params();
        $limit = $request->get_param( 'limit' ) ?? 5;
        
        if ( empty( $files['documents'] ) ) {
            return new WP_Error( 'no_files', 'Keine Dokumente hochgeladen', [ 'status' => 400 ] );
        }
        
        try {
            $service = new \RecruitingPlaybook\AI\Services\AnalysisService();
            $result = $service->find_matching_jobs( $files['documents'], $limit );
            
            return new WP_REST_Response( [
                'success' => true,
                'data'    => $result->to_array(),
            ] );
            
        } catch ( \Exception $e ) {
            return new WP_Error( 'analysis_failed', $e->getMessage(), [ 'status' => 500 ] );
        }
    }
    
    /**
     * Modus C: Chancen-Check
     */
    public function chance_check( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        if ( ! rp_has_ai() ) {
            return new WP_Error( 'ai_not_licensed', 'AI-Addon nicht aktiviert', [ 'status' => 403 ] );
        }
        
        $files = $request->get_file_params();
        $job_id = $request->get_param( 'job_id' );
        
        try {
            $service = new \RecruitingPlaybook\AI\Services\AnalysisService();
            $result = $service->calculate_chances( $files['documents'], $job_id );
            
            return new WP_REST_Response( [
                'success' => true,
                'data'    => $result->to_array(),
            ] );
            
        } catch ( \Exception $e ) {
            return new WP_Error( 'analysis_failed', $e->getMessage(), [ 'status' => 500 ] );
        }
    }
    
    /**
     * Usage-Status
     */
    public function get_usage(): WP_REST_Response {
        $checker = new \RecruitingPlaybook\AI\Services\LimitChecker();
        
        return new WP_REST_Response( [
            'success' => true,
            'data'    => $checker->get_status(),
        ] );
    }
}
```

---

## Frontend Component (Alpine.js)

```javascript
// assets/js/ai-analyzer.js

document.addEventListener('alpine:init', () => {
    Alpine.data('aiAnalyzer', (mode = 'job-match', jobId = null) => ({
        // State
        mode: mode, // 'job-match', 'job-finder', 'chance-check'
        jobId: jobId,
        files: [],
        analyzing: false,
        progress: 0,
        result: null,
        error: null,
        
        // File handling
        onFileSelect(event) {
            this.files = Array.from(event.target.files);
            this.error = null;
            this.result = null;
        },
        
        onFileDrop(event) {
            event.preventDefault();
            this.files = Array.from(event.dataTransfer.files);
            this.error = null;
            this.result = null;
        },
        
        removeFile(index) {
            this.files.splice(index, 1);
        },
        
        // Analysis
        async analyze() {
            if (this.files.length === 0) {
                this.error = 'Bitte laden Sie mindestens einen Lebenslauf hoch.';
                return;
            }
            
            this.analyzing = true;
            this.progress = 0;
            this.error = null;
            
            // Progress simulation
            const progressInterval = setInterval(() => {
                if (this.progress < 90) {
                    this.progress += Math.random() * 15;
                }
            }, 500);
            
            try {
                const formData = new FormData();
                
                this.files.forEach(file => {
                    formData.append('documents[]', file);
                });
                
                if (this.jobId) {
                    formData.append('job_id', this.jobId);
                }
                
                const endpoint = this.getEndpoint();
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-WP-Nonce': window.rpData.nonce,
                    },
                });
                
                const data = await response.json();
                
                clearInterval(progressInterval);
                this.progress = 100;
                
                if (data.success) {
                    this.result = data.data;
                } else {
                    this.error = data.message || 'Analyse fehlgeschlagen.';
                }
                
            } catch (err) {
                clearInterval(progressInterval);
                this.error = 'Verbindungsfehler. Bitte versuchen Sie es erneut.';
                console.error(err);
            } finally {
                this.analyzing = false;
            }
        },
        
        getEndpoint() {
            const base = window.rpData.restUrl;
            
            switch (this.mode) {
                case 'job-match':
                    return `${base}ai/job-match`;
                case 'job-finder':
                    return `${base}ai/job-finder`;
                case 'chance-check':
                    return `${base}ai/chance-check`;
            }
        },
        
        // Result helpers
        getScoreColor(score) {
            if (score >= 80) return 'green';
            if (score >= 60) return 'yellow';
            return 'red';
        },
        
        getScoreLabel(score) {
            if (score >= 90) return 'Ausgezeichnet';
            if (score >= 80) return 'Sehr gut';
            if (score >= 70) return 'Gut';
            if (score >= 60) return 'Moderat';
            return 'Gering';
        },
        
        // Apply to form
        applyToForm() {
            if (!this.result?.profile) return;
            
            const profile = this.result.profile;
            const form = document.querySelector('.rp-application-form');
            
            if (!form) return;
            
            // Felder befüllen
            const fields = {
                'first_name': profile.first_name,
                'last_name': profile.last_name,
                'email': profile.email,
                'phone': profile.phone,
            };
            
            for (const [name, value] of Object.entries(fields)) {
                if (value) {
                    const input = form.querySelector(`[name="${name}"]`);
                    if (input) {
                        input.value = value;
                        input.dispatchEvent(new Event('input'));
                    }
                }
            }
            
            // Scroll zum Formular
            form.scrollIntoView({ behavior: 'smooth' });
        },
        
        // Reset
        reset() {
            this.files = [];
            this.result = null;
            this.error = null;
            this.progress = 0;
        },
    }));
});
```

---

## Admin Dashboard Widget

```php
<?php
// src/Admin/Widgets/AIUsageWidget.php

class AIUsageWidget {
    
    public function render(): void {
        if ( ! rp_has_ai() ) {
            $this->render_upgrade_prompt();
            return;
        }
        
        $checker = new \RecruitingPlaybook\AI\Services\LimitChecker();
        $status = $checker->get_status();
        
        ?>
        <div class="rp-widget rp-ai-usage">
            <h3><?php esc_html_e( 'KI-Analysen', 'recruiting-playbook' ); ?></h3>
            
            <div class="rp-usage-meter">
                <div class="rp-usage-bar">
                    <div 
                        class="rp-usage-bar__fill" 
                        style="width: <?php echo esc_attr( $status['percent'] ); ?>%"
                    ></div>
                </div>
                <div class="rp-usage-numbers">
                    <span class="rp-usage-used"><?php echo esc_html( $status['used'] ); ?></span>
                    <span class="rp-usage-separator">/</span>
                    <span class="rp-usage-limit"><?php echo esc_html( $status['limit'] ); ?></span>
                    <span class="rp-usage-label"><?php esc_html_e( 'Analysen', 'recruiting-playbook' ); ?></span>
                </div>
            </div>
            
            <?php if ( $status['extra'] > 0 ) : ?>
                <p class="rp-usage-extra">
                    + <?php echo esc_html( $status['extra'] ); ?> 
                    <?php esc_html_e( 'Extra-Analysen verfügbar', 'recruiting-playbook' ); ?>
                </p>
            <?php endif; ?>
            
            <p class="rp-usage-reset">
                <?php printf(
                    esc_html__( 'Erneuert am: %s', 'recruiting-playbook' ),
                    date_i18n( 'j. F Y', strtotime( 'first day of next month' ) )
                ); ?>
            </p>
            
            <?php if ( $status['available'] < 20 ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-ai-upgrade' ) ); ?>" class="button">
                    <?php esc_html_e( 'Extra-Paket kaufen', 'recruiting-playbook' ); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private function render_upgrade_prompt(): void {
        ?>
        <div class="rp-widget rp-ai-upgrade">
            <h3><?php esc_html_e( 'KI-Analysen', 'recruiting-playbook' ); ?></h3>
            
            <p><?php esc_html_e( 'Aktivieren Sie das AI-Addon für intelligentes Bewerber-Matching!', 'recruiting-playbook' ); ?></p>
            
            <ul class="rp-feature-list">
                <li>🎯 <?php esc_html_e( 'Job-Match: Passt der Bewerber?', 'recruiting-playbook' ); ?></li>
                <li>🔍 <?php esc_html_e( 'Job-Finder: Passende Stellen finden', 'recruiting-playbook' ); ?></li>
                <li>📊 <?php esc_html_e( 'Chancen-Check: Einstellungschancen berechnen', 'recruiting-playbook' ); ?></li>
            </ul>
            
            <a href="<?php echo esc_url( rp_upgrade_url( 'AI_ADDON' ) ); ?>" class="button button-primary">
                <?php esc_html_e( 'AI-Addon aktivieren', 'recruiting-playbook' ); ?>
            </a>
        </div>
        <?php
    }
}
```

---

## DSGVO / Datenschutz

```php
<?php
// src/AI/Privacy/AIPrivacy.php

class AIPrivacy {
    
    /**
     * Datenschutz-Hinweise für AI-Feature
     */
    public static function get_consent_text(): string {
        return __( 
            'Ich willige ein, dass meine hochgeladenen Dokumente einmalig durch eine KI analysiert werden, 
            um meine Eignung für Stellenangebote zu prüfen. Die Dokumente werden nach der Analyse 
            nicht gespeichert und nicht an Dritte weitergegeben.', 
            'recruiting-playbook' 
        );
    }
    
    /**
     * Privacy Policy Text
     */
    public static function get_policy_text(): string {
        return __( '
## KI-gestützte Bewerber-Analyse

Unser Bewerbungsformular bietet optional eine KI-gestützte Analyse Ihrer Bewerbungsunterlagen.

### Was passiert bei der Analyse?
- Ihre hochgeladenen Dokumente (Lebenslauf, Zeugnisse) werden an den KI-Dienst Anthropic Claude übermittelt
- Die KI analysiert Ihre Qualifikationen und vergleicht sie mit den Stellenanforderungen
- Sie erhalten eine Einschätzung Ihrer Eignung

### Datenverarbeitung
- Die Dokumente werden nur für die einmalige Analyse verwendet
- Nach der Analyse werden die Dokumente sofort gelöscht
- Es werden keine personenbezogenen Daten dauerhaft bei Anthropic gespeichert
- Die Analyse erfolgt auf Basis Ihrer ausdrücklichen Einwilligung

### Anthropic (Claude AI)
Anthropic ist der Anbieter des KI-Dienstes. Die Datenverarbeitung erfolgt gemäß deren Datenschutzrichtlinien: https://www.anthropic.com/privacy

### Widerruf
Sie können die Analyse jederzeit ablehnen. Die Bewerbung ist auch ohne KI-Analyse möglich.
', 'recruiting-playbook' );
    }
}

// Privacy Policy Hook
add_action( 'admin_init', function() {
    wp_add_privacy_policy_content(
        'Recruiting Playbook - KI-Analyse',
        \RecruitingPlaybook\AI\Privacy\AIPrivacy::get_policy_text()
    );
} );
```

---

## Zusammenfassung

| Feature | Beschreibung |
|---------|--------------|
| **Modus A: Job-Match** | Bewerber prüft Eignung für konkrete Stelle |
| **Modus B: Job-Finder** | KI findet passende Stellen aus allen Angeboten |
| **Modus C: Chancen-Check** | Detaillierte Einstellungschancen-Analyse |
| **Auto-Fill** | Erkannte Daten werden ins Formular übernommen |
| **Limit-System** | 100 Analysen/Monat, Extra-Pakete kaufbar |
| **DSGVO-konform** | Einwilligung, keine Speicherung, Transparenz |

---

*Letzte Aktualisierung: Januar 2025*
