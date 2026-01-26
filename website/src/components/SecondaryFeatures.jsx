import { Container } from '@/components/Container'

const features = [
  {
    name: 'Free',
    summary: 'Alles Wichtige — kostenlos und ohne Limits.',
    features: [
      'Unbegrenzte Stellenanzeigen',
      'Mehrstufiges Bewerbungsformular',
      'Google for Jobs Schema',
      'Dokument-Upload (Lebenslauf, Zeugnisse)',
      'E-Mail-Benachrichtigungen',
      'DSGVO-Consent-Checkbox',
      'CSV-Export (Basis)',
      'WordPress Shortcodes',
    ],
    color: 'bg-slate-600',
  },
  {
    name: 'Pro',
    summary: 'Professionelles Bewerbermanagement für Teams.',
    features: [
      'Kanban-Board (Drag & Drop)',
      'Erweiterte E-Mail-Templates',
      'REST API Zugang',
      'Webhook-System',
      'Automatische Löschfristen (DSGVO)',
      'Bewerber-Datenexport',
      'Status-Workflow Anpassung',
      'Premium Support (1 Jahr)',
    ],
    color: 'bg-[#1d71b8]',
  },
  {
    name: 'KI-Addon',
    summary: 'Intelligente Bewerberanalyse mit Claude AI.',
    features: [
      'Job-Match Score (0-100%)',
      'Job-Finder: "Welche Jobs passen zu mir?"',
      'Chancen-Check für Bewerber',
      'KI-Stellentexte generieren',
      'SEO-Optimierung für Stellenanzeigen',
      'Stärken/Schwächen-Analyse',
      'Verbesserungsvorschläge',
      'Fair-Use: 100 Analysen/Monat',
    ],
    color: 'bg-[#2fac66]',
  },
]

export function SecondaryFeatures() {
  return (
    <section
      id="tiers"
      aria-label="Feature-Übersicht nach Version"
      className="pt-20 pb-14 sm:pt-32 sm:pb-20 lg:pb-32"
    >
      <Container>
        <div className="mx-auto max-w-2xl md:text-center">
          <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
            Drei Versionen — ein Plugin.
          </h2>
          <p className="mt-4 text-lg tracking-tight text-slate-700">
            Starten Sie kostenlos und erweitern Sie bei Bedarf. Kein Vendor
            Lock-in, Ihre Daten bleiben in WordPress.
          </p>
        </div>
        <div className="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-8 lg:max-w-none lg:grid-cols-3">
          {features.map((tier) => (
            <div
              key={tier.name}
              className="flex flex-col rounded-3xl bg-slate-50 p-8"
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
              <p className="mt-4 text-sm text-slate-600">{tier.summary}</p>
              <ul role="list" className="mt-6 flex flex-col gap-y-2">
                {tier.features.map((feature) => (
                  <li
                    key={feature}
                    className="flex items-start gap-x-3 text-sm text-slate-700"
                  >
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
                    {feature}
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </Container>
    </section>
  )
}
