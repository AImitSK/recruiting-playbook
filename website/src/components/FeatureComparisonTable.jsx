import { Fragment } from 'react'

import { Container } from '@/components/Container'

const categories = [
  {
    name: 'Stellenanzeigen',
    features: [
      { name: 'Unbegrenzte aktive Stellen', free: true, pro: true, agentur: true },
      { name: 'Google for Jobs Schema', free: true, pro: true, agentur: true },
      { name: 'SEO-Meta-Felder', free: 'Basic', pro: true, agentur: true },
      { name: 'Custom Fields für Stellen', free: false, pro: true, agentur: true },
      { name: 'Stellen archivieren & duplizieren', free: false, pro: true, agentur: true },
    ],
  },
  {
    name: 'Bewerbungsformular',
    features: [
      { name: 'Bewerbungsformular', free: true, pro: true, agentur: true },
      { name: 'Lebenslauf-Upload', free: true, pro: true, agentur: true },
      { name: 'DSGVO-Consent-Checkboxen', free: true, pro: true, agentur: true },
      { name: 'Formular-Builder mit Custom Fields', free: false, pro: true, agentur: true },
    ],
  },
  {
    name: 'Bewerbermanagement',
    features: [
      { name: 'Bewerber-Liste im Backend', free: 'Basic', pro: true, agentur: true },
      { name: 'E-Mail-Benachrichtigung bei neuer Bewerbung', free: true, pro: true, agentur: true },
      { name: 'Kanban-Board (Drag & Drop)', free: false, pro: true, agentur: true },
      { name: 'Bewerber-Detailansicht mit Timeline', free: false, pro: true, agentur: true },
      { name: 'Konfigurierbarer Status-Workflow', free: false, pro: true, agentur: true },
      { name: 'Notizen & Sterne-Bewertungen', free: false, pro: true, agentur: true },
      { name: 'Talent-Pool', free: false, pro: true, agentur: true },
    ],
  },
  {
    name: 'E-Mails & Templates',
    features: [
      { name: 'Benachrichtigung bei neuer Bewerbung', free: true, pro: true, agentur: true },
      { name: 'Anpassbare E-Mail-Templates', free: false, pro: true, agentur: true },
      { name: 'Automatische E-Mail Workflows', free: false, pro: true, agentur: true },
      { name: 'Manueller E-Mail-Versand an Bewerber', free: false, pro: true, agentur: true },
      { name: 'E-Mail-Historie pro Bewerber', free: false, pro: true, agentur: true },
    ],
  },
  {
    name: 'Berichte & Analytics',
    features: [
      { name: 'Bewerbungen pro Stelle', free: false, pro: true, agentur: true },
      { name: 'Conversion-Rates', free: false, pro: true, agentur: true },
      { name: 'Time-to-Hire', free: false, pro: true, agentur: true },
      { name: 'CSV-Export', free: false, pro: true, agentur: true },
    ],
  },
  {
    name: 'Integrationen & API',
    features: [
      { name: 'WordPress Shortcodes', free: true, pro: true, agentur: true },
      { name: 'Gutenberg-Blöcke', free: true, pro: true, agentur: true },
      { name: 'Avada/Fusion Builder Elemente', free: false, pro: true, agentur: true },
      { name: 'Elementor Builder Elemente', free: false, pro: true, agentur: true },
      { name: 'REST API', free: false, pro: true, agentur: true },
      { name: 'Webhooks (Zapier, Make)', free: false, pro: true, agentur: true },
    ],
  },
  {
    name: 'Design & Branding',
    features: [
      { name: 'Theme-Farben automatisch erben', free: true, pro: true, agentur: true },
      { name: 'Custom Primärfarbe & Buttons', free: false, pro: true, agentur: true },
      { name: 'Typografie anpassen', free: false, pro: true, agentur: true },
      { name: 'Card-Design anpassen', free: false, pro: true, agentur: true },
      { name: '"Powered by" Badge entfernen', free: false, pro: true, agentur: true },
    ],
  },
  {
    name: 'DSGVO & Datenschutz',
    features: [
      { name: 'Consent-Checkbox mit Zeitstempel', free: true, pro: true, agentur: true },
      { name: 'Bewerber manuell löschen', free: true, pro: true, agentur: true },
      { name: 'Automatische Löschfristen', free: false, pro: true, agentur: true },
      { name: 'Daten-Export pro Bewerber', free: false, pro: true, agentur: true },
      { name: 'Anonymisierung (Soft-Delete)', free: false, pro: true, agentur: true },
    ],
  },
  {
    name: 'Lizenz & Support',
    features: [
      { name: 'Community-Support', free: true, pro: true, agentur: true },
      { name: 'Premium E-Mail-Support', free: false, pro: '1 Jahr', agentur: '1 Jahr' },
      { name: 'Updates', free: true, pro: '1 Jahr', agentur: '1 Jahr' },
      { name: 'Anzahl Websites', free: '1', pro: '1', agentur: '3' },
      { name: 'Zentrale Lizenzverwaltung', free: false, pro: false, agentur: true },
      { name: 'Prioritäts-Support', free: false, pro: false, agentur: true },
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
                <th className="pb-4 text-center text-sm font-semibold text-slate-600">
                  Agentur
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
                        <CellValue value={feature.agentur} />
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
