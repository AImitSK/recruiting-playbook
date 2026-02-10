import { Container } from '@/components/Container'

const features = [
  {
    name: 'DSGVO-konform',
    description: 'Consent-Checkboxen, Löschfristen, Datenexport, Anonymisierung',
  },
  {
    name: 'Benutzerrollen',
    description: 'Admin, Recruiter, Hiring Manager mit unterschiedlichen Rechten',
  },
  {
    name: 'REST API & Webhooks',
    description: 'Vollständige API für Integrationen mit Zapier, Make und eigenen Tools',
  },
  {
    name: 'Page Builder Support',
    description: 'Native Elemente für Gutenberg und Avada oder Elementor',
  },
  {
    name: 'Shortcodes',
    description: '9 Shortcodes für flexible Einbindung auf jeder Seite',
  },
  {
    name: 'Backup & Export',
    description: 'JSON-Backup aller Plugin-Daten, CSV-Export für Bewerbungen',
  },
  {
    name: 'Setup-Wizard',
    description: 'Geführte Erstkonfiguration in 5 Schritten',
  },
  {
    name: 'Mehrsprachig',
    description: 'Komplett übersetzbar, .pot-Datei enthalten',
  },
]

export function FeaturesByTier() {
  return (
    <section className="bg-slate-50 py-20 sm:py-32">
      <Container>
        <div className="mx-auto max-w-2xl md:text-center">
          <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
            Und noch viel mehr.
          </h2>
          <p className="mt-4 text-lg tracking-tight text-slate-700">
            Features, die den Unterschied machen.
          </p>
        </div>
        <div className="mx-auto mt-16 max-w-3xl">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {features.map((feature) => (
              <div
                key={feature.name}
                className="flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
              >
                <h3 className="font-display text-base font-semibold text-slate-900">
                  {feature.name}
                </h3>
                <p className="mt-2 text-sm text-slate-600">
                  {feature.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </Container>
    </section>
  )
}
