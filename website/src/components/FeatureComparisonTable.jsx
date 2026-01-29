import { Fragment } from 'react'

import { Container } from '@/components/Container'

const categories = [
  {
    name: 'Stellenanzeigen',
    features: [
      { name: 'Unbegrenzte aktive Stellen', free: true, pro: true, ai: true },
      { name: 'Custom Post Type', free: true, pro: true, ai: true },
      { name: 'Google for Jobs Schema', free: true, pro: true, ai: true },
      { name: 'SEO-Meta-Felder', free: 'Basic', pro: true, ai: true },
      { name: 'Stellen-Templates', free: '2', pro: '5+', ai: '5+' },
      { name: 'Custom Fields', free: false, pro: true, ai: true },
      { name: 'Archivieren & Duplizieren', free: false, pro: true, ai: true },
    ],
  },
  {
    name: 'Bewerbungsformular',
    features: [
      { name: 'Standard-Formular', free: true, pro: true, ai: true },
      { name: 'Lebenslauf-Upload', free: true, pro: true, ai: true },
      { name: 'DSGVO-Checkboxen', free: true, pro: true, ai: true },
      { name: 'Mehrere Dokumente', free: false, pro: true, ai: true },
      { name: 'Custom Fields', free: false, pro: true, ai: true },
      { name: 'Conditional Logic', free: false, pro: true, ai: true },
    ],
  },
  {
    name: 'Bewerbermanagement',
    features: [
      { name: 'Bewerber-Liste', free: 'Basic', pro: true, ai: true },
      { name: 'E-Mail-Benachrichtigung', free: true, pro: true, ai: true },
      { name: 'Kanban-Board', free: false, pro: true, ai: true },
      { name: 'Bewerber-Detailansicht', free: false, pro: true, ai: true },
      { name: 'Status-Tracking', free: false, pro: true, ai: true },
      { name: 'Notizen & Bewertungen', free: false, pro: true, ai: true },
      { name: 'Suche & Filter', free: false, pro: true, ai: true },
    ],
  },
  {
    name: 'E-Mails & Templates',
    features: [
      { name: 'Benachrichtigung bei Bewerbung', free: true, pro: true, ai: true },
      { name: 'E-Mail-Templates', free: false, pro: true, ai: true },
      { name: 'Automatische Eingangsbestätigung', free: false, pro: true, ai: true },
      { name: 'Manuelle E-Mail an Bewerber', free: false, pro: true, ai: true },
    ],
  },
  {
    name: 'Integrationen & API',
    features: [
      { name: 'WordPress Shortcodes', free: true, pro: true, ai: true },
      { name: 'REST API', free: false, pro: true, ai: true },
      { name: 'Webhooks (Zapier/Make)', free: false, pro: true, ai: true },
      { name: 'CSV-Export', free: false, pro: true, ai: true },
    ],
  },
  {
    name: 'KI-Features',
    features: [
      { name: 'Job-Match Score', free: false, pro: false, ai: true },
      { name: 'Job-Finder', free: false, pro: false, ai: true },
      { name: 'Chancen-Check', free: false, pro: false, ai: true },
      { name: 'Stellentexte generieren', free: false, pro: false, ai: true },
      { name: 'SEO-Vorschläge', free: false, pro: false, ai: true },
    ],
  },
  {
    name: 'Design & Branding',
    features: [
      { name: 'Theme-Farben erben', free: true, pro: true, ai: true },
      { name: 'Custom Primärfarbe', free: false, pro: true, ai: true },
      { name: 'Typografie anpassen', free: false, pro: true, ai: true },
      { name: 'Card-Design anpassen', free: false, pro: true, ai: true },
      { name: 'Branding entfernen', free: false, pro: true, ai: true },
    ],
  },
  {
    name: 'Support & Updates',
    features: [
      { name: 'Community-Support', free: true, pro: true, ai: true },
      { name: 'Premium E-Mail-Support', free: false, pro: '1 Jahr', ai: true },
      { name: 'Updates', free: true, pro: '1 Jahr', ai: true },
    ],
  },
]

function CellValue({ value }) {
  if (value === true) {
    return (
      <svg
        className="mx-auto h-5 w-5 text-[#2fac66]"
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
  if (value === false) {
    return (
      <svg
        className="mx-auto h-5 w-5 text-slate-300"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M6 18L18 6M6 6l12 12"
        />
      </svg>
    )
  }
  return <span className="text-sm text-slate-600">{value}</span>
}

export function FeatureComparisonTable() {
  return (
    <section className="py-20 sm:py-32">
      <Container>
        <div className="mx-auto max-w-2xl md:text-center">
          <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
            Feature-Vergleich im Detail
          </h2>
          <p className="mt-4 text-lg tracking-tight text-slate-700">
            Sehen Sie auf einen Blick, welche Features in welcher Version
            enthalten sind.
          </p>
        </div>
        <div className="mt-16 overflow-x-auto">
          <table className="w-full min-w-[600px] text-left">
            <thead>
              <tr className="border-b border-slate-200">
                <th className="pb-4 pr-4 text-sm font-semibold text-slate-900">
                  Feature
                </th>
                <th className="pb-4 text-center text-sm font-semibold text-slate-900">
                  Free
                </th>
                <th className="pb-4 text-center text-sm font-semibold text-[#1d71b8]">
                  Pro
                </th>
                <th className="pb-4 text-center text-sm font-semibold text-[#2fac66]">
                  KI-Addon
                </th>
              </tr>
            </thead>
            <tbody>
              {categories.map((category) => (
                <Fragment key={category.name}>
                  <tr>
                    <td
                      colSpan={4}
                      className="pt-8 pb-3 text-sm font-semibold text-slate-900"
                    >
                      {category.name}
                    </td>
                  </tr>
                  {category.features.map((feature) => (
                    <tr
                      key={feature.name}
                      className="border-b border-slate-100"
                    >
                      <td className="py-3 pr-4 text-sm text-slate-700">
                        {feature.name}
                      </td>
                      <td className="py-3 text-center">
                        <CellValue value={feature.free} />
                      </td>
                      <td className="py-3 text-center">
                        <CellValue value={feature.pro} />
                      </td>
                      <td className="py-3 text-center">
                        <CellValue value={feature.ai} />
                      </td>
                    </tr>
                  ))}
                </Fragment>
              ))}
            </tbody>
          </table>
        </div>
      </Container>
    </section>
  )
}
