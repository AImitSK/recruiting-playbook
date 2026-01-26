import { Button } from '@/components/Button'
import { Container } from '@/components/Container'

const tiers = [
  {
    name: 'Free',
    description: 'Alles Wichtige. Kostenlos und ohne Limits bei Stellenanzeigen.',
    color: 'bg-slate-600',
    cta: 'Herunterladen',
    href: '/recruiting-playbook.zip',
    download: true,
    features: [
      'Unbegrenzte Stellenanzeigen (Custom Post Type)',
      'Standard-Bewerbungsformular',
      'Lebenslauf-Upload',
      'E-Mail-Benachrichtigung bei neuen Bewerbungen',
      'Einfache Bewerber-Liste im Backend',
      'Google for Jobs Schema',
      'DSGVO-Consent-Checkbox',
      'Responsive Darstellung',
      'WordPress Shortcodes',
      '2 Standard-Templates',
      'Theme-Farben werden automatisch übernommen',
    ],
  },
  {
    name: 'Pro',
    description: 'Professionelles Bewerbermanagement für Teams und Agenturen.',
    color: 'bg-[#1d71b8]',
    cta: 'Pro kaufen',
    href: '#',
    features: [
      'Alles aus Free, plus:',
      'Kanban-Board (Drag & Drop)',
      'Bewerber-Detailansicht mit Timeline',
      'Notizen & Bewertungen pro Bewerber',
      'Konfigurierbarer Status-Workflow',
      'Benutzerrollen (Admin, Recruiter, Viewer)',
      'Erweiterte Formulare mit Custom Fields',
      'Mehrere Dokument-Uploads',
      'E-Mail-Templates (Bestätigung, Absage, Einladung)',
      'REST API & Webhooks',
      'Reporting (Time-to-Hire, Conversion-Rates)',
      'CSV-Export',
      'Automatische DSGVO-Löschfristen',
      '5+ Premium-Templates',
      'Custom Branding (Farben, Typo, Buttons)',
      '"Powered by" Badge entfernen',
      '1 Jahr Updates & Premium-Support',
    ],
  },
  {
    name: 'KI-Addon',
    description: 'Intelligente Bewerberanalyse und Textgenerierung mit Claude AI.',
    color: 'bg-[#2fac66]',
    cta: 'Mehr erfahren',
    href: '/ai',
    features: [
      'Benötigt Pro-Lizenz, plus:',
      'Job-Match Score (0–100%)',
      'Job-Finder: passende Stellen für Bewerber',
      'Chancen-Check mit Stärken/Schwächen',
      'Konkrete Verbesserungstipps für Bewerber',
      'Formular-Vorausfüllung aus Lebenslauf',
      'KI-Stellentexte generieren',
      'Texte optimieren & umschreiben',
      'SEO-Vorschläge für Stellenanzeigen',
      'Branchenspezifische Textbausteine',
      '100 Analysen/Monat inklusive',
      'Extra-Pakete jederzeit nachbuchbar',
    ],
  },
]

function CheckIcon() {
  return (
    <svg
      className="mt-0.5 h-5 w-5 flex-none text-[#2fac66]"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth={2}
        d="M5 13l4 4L19 7"
      />
    </svg>
  )
}

export function FeaturesByTier() {
  return (
    <section className="bg-slate-50 py-20 sm:py-32">
      <Container>
        <div className="mx-auto max-w-2xl md:text-center">
          <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
            Drei Versionen. Ein Plugin.
          </h2>
          <p className="mt-4 text-lg tracking-tight text-slate-700">
            Starten Sie kostenlos und erweitern Sie bei Bedarf. Alle Features im
            Detail.
          </p>
        </div>
        <div className="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-8 lg:max-w-none lg:grid-cols-3">
          {tiers.map((tier) => (
            <div
              key={tier.name}
              className="flex flex-col rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200"
            >
              <div className="flex items-center gap-x-3">
                <span
                  className={`inline-flex h-8 w-8 items-center justify-center rounded-lg ${tier.color} text-sm font-bold text-white`}
                >
                  {tier.name[0]}
                </span>
                <h3 className="font-display text-lg text-slate-900">
                  {tier.name}
                </h3>
              </div>
              <p className="mt-4 text-sm text-slate-600">{tier.description}</p>
              <ul role="list" className="mt-6 flex flex-col gap-y-2">
                {tier.features.map((feature) => (
                  <li
                    key={feature}
                    className="flex items-start gap-x-3 text-sm text-slate-700"
                  >
                    <CheckIcon />
                    {feature}
                  </li>
                ))}
              </ul>
              <Button
                href={tier.href}
                color="blue"
                className="mt-8"
                {...(tier.download ? { download: true } : {})}
              >
                {tier.cta}
              </Button>
            </div>
          ))}
        </div>
      </Container>
    </section>
  )
}
