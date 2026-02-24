import { Footer } from '@/components/Footer'
import { Header } from '@/components/Header'
import { Container } from '@/components/Container'
import { Button } from '@/components/Button'
import { SectionCta } from '@/components/SectionCta'

export const metadata = {
  title: 'Vergleich: Recruiting Playbook vs. Wettbewerber',
  description:
    'Recruiting Playbook vs. WP Job Manager, MatadorJobs und andere WordPress Recruiting Plugins im direkten Vergleich. Features, Preise, DSGVO.',
}

function CheckIcon() {
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

function XIcon() {
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

function CellValue({ value }) {
  if (value === true) return <CheckIcon />
  if (value === false) return <XIcon />
  return <span className="text-sm text-slate-600">{value}</span>
}

const competitors = [
  { key: 'rp', name: 'Recruiting Playbook', highlight: true },
  { key: 'wpjm', name: 'WP Job Manager' },
  { key: 'matador', name: 'MatadorJobs' },
  { key: 'jetstaffing', name: 'JetEngine (Staffing)' },
]

const categories = [
  {
    name: 'Preismodell',
    rows: [
      { feature: 'Kostenlose Version', rp: true, wpjm: true, matador: false, jetstaffing: false },
      { feature: 'Preis Pro/Premium', rp: '149 € einmalig', wpjm: '159 €/Jahr', matador: '799 €/Jahr', jetstaffing: '49 €/Jahr' },
      { feature: 'Lifetime-Lizenz', rp: true, wpjm: false, matador: false, jetstaffing: false },
      { feature: 'Kosten nach 3 Jahren', rp: '149 €', wpjm: '477 €', matador: '2.397 €', jetstaffing: '147 €' },
    ],
  },
  {
    name: 'Kernfunktionen',
    rows: [
      { feature: 'Google for Jobs Schema', rp: 'Kostenlos', wpjm: '39 €/Jahr Add-on', matador: '799 €/Jahr', jetstaffing: false },
      { feature: 'Kanban-Board', rp: true, wpjm: false, matador: true, jetstaffing: false },
      { feature: 'KI-Bewerberanalyse', rp: true, wpjm: false, matador: false, jetstaffing: false },
      { feature: 'Bewerbungsformular', rp: true, wpjm: 'Add-on', matador: true, jetstaffing: false },
      { feature: 'E-Mail-Templates', rp: true, wpjm: false, matador: true, jetstaffing: false },
      { feature: 'REST API', rp: true, wpjm: true, matador: false, jetstaffing: false },
    ],
  },
  {
    name: 'DSGVO & Datenschutz',
    rows: [
      { feature: 'DSGVO-konform ab Werk', rp: true, wpjm: false, matador: 'Teilweise', jetstaffing: false },
      { feature: 'Automatische Löschfristen', rp: true, wpjm: false, matador: false, jetstaffing: false },
      { feature: 'Consent-Protokollierung', rp: true, wpjm: false, matador: false, jetstaffing: false },
      { feature: 'Datenexport pro Bewerber', rp: true, wpjm: false, matador: false, jetstaffing: false },
      { feature: 'Deutsche Dokumentation', rp: true, wpjm: false, matador: false, jetstaffing: false },
    ],
  },
  {
    name: 'Page Builder & Integration',
    rows: [
      { feature: 'Gutenberg-Blöcke', rp: true, wpjm: false, matador: false, jetstaffing: true },
      { feature: 'Elementor Widgets', rp: true, wpjm: 'Community', matador: false, jetstaffing: true },
      { feature: 'Avada/Fusion Builder', rp: true, wpjm: false, matador: false, jetstaffing: false },
      { feature: 'Webhooks (Zapier, Make)', rp: true, wpjm: false, matador: false, jetstaffing: false },
    ],
  },
]

export default function VergleichPage() {
  return (
    <>
      <Header />
      <main>
        {/* Hero */}
        <Container className="pt-20 pb-16 lg:pt-32">
          <div className="mx-auto max-w-3xl text-center">
            <h1 className="font-display text-4xl font-medium tracking-tight text-slate-900 sm:text-5xl">
              WordPress Recruiting Plugins im Vergleich
            </h1>
            <p className="mx-auto mt-6 max-w-2xl text-lg text-slate-700">
              Recruiting Playbook vs. WP Job Manager, MatadorJobs und
              JetEngine. Einmalpreis, DSGVO, KI und Google for Jobs —
              alles auf einen Blick.
            </p>
          </div>
        </Container>

        {/* Kostenvergleich-Highlight */}
        <section className="bg-slate-50 py-16">
          <Container>
            <div className="mx-auto max-w-4xl">
              <div className="grid gap-6 sm:grid-cols-3">
                <div className="rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200">
                  <p className="text-sm font-medium text-slate-500">Nach 1 Jahr</p>
                  <p className="mt-2 font-display text-3xl font-light text-[#1d71b8]">149 €</p>
                  <p className="mt-1 text-sm text-slate-500">vs. 159–799 € bei Wettbewerbern</p>
                </div>
                <div className="rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200">
                  <p className="text-sm font-medium text-slate-500">Nach 3 Jahren</p>
                  <p className="mt-2 font-display text-3xl font-light text-[#2fac66]">149 €</p>
                  <p className="mt-1 text-sm text-slate-500">vs. 477–2.397 € bei Wettbewerbern</p>
                </div>
                <div className="rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200">
                  <p className="text-sm font-medium text-slate-500">Ersparnis nach 3 Jahren</p>
                  <p className="mt-2 font-display text-3xl font-light text-[#2fac66]">bis 2.248 €</p>
                  <p className="mt-1 text-sm text-slate-500">dank Einmal-Lizenz</p>
                </div>
              </div>
            </div>
          </Container>
        </section>

        {/* Vergleichstabelle */}
        <section className="py-20 sm:py-32">
          <Container>
            <div className="mx-auto max-w-5xl">
              <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
                Feature-Vergleich im Detail
              </h2>
              <div className="mt-12 overflow-x-auto">
                <table className="w-full min-w-[700px] text-left text-sm">
                  <thead>
                    <tr className="border-b border-slate-200">
                      <th className="pb-4 pr-4 font-semibold text-slate-900">Feature</th>
                      {competitors.map((c) => (
                        <th
                          key={c.key}
                          className={`pb-4 text-center font-semibold ${c.highlight ? 'text-[#1d71b8]' : 'text-slate-600'}`}
                        >
                          {c.name}
                        </th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {categories.map((cat) => (
                      <>
                        <tr key={cat.name}>
                          <td
                            colSpan={competitors.length + 1}
                            className="pt-8 pb-3 font-semibold text-slate-900"
                          >
                            {cat.name}
                          </td>
                        </tr>
                        {cat.rows.map((row) => (
                          <tr key={row.feature} className="border-b border-slate-100">
                            <td className="py-3 pr-4 text-slate-700">{row.feature}</td>
                            {competitors.map((c) => (
                              <td key={c.key} className="py-3 text-center">
                                <CellValue value={row[c.key]} />
                              </td>
                            ))}
                          </tr>
                        ))}
                      </>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </Container>
        </section>

        {/* Vorteile */}
        <section className="bg-slate-50 py-20 sm:py-32">
          <Container>
            <div className="mx-auto max-w-3xl">
              <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
                Warum Recruiting Playbook?
              </h2>
              <div className="mt-10 space-y-8">
                {[
                  {
                    title: 'Einmalpreis statt Jahres-Abo',
                    text: '149 € einmalig für die Pro-Lizenz. Kein Abo, keine wiederkehrenden Kosten. Nach dem Kauf gehört die Lizenz Ihnen — auch nach Ablauf des Support-Zeitraums.',
                  },
                  {
                    title: 'DSGVO-konform für den DACH-Markt',
                    text: 'Consent-Protokollierung, automatische Löschfristen, Datenexport und Anonymisierung. Recruiting Playbook ist das einzige WordPress-Plugin, das DSGVO-Compliance eingebaut hat — nicht als nachträgliches Add-on.',
                  },
                  {
                    title: 'KI-Bewerberanalyse inklusive',
                    text: 'Match-Score, Job-Finder und KI-Stellentexte — direkt in der Pro-Version enthalten. Kein anderes WordPress Recruiting Plugin bietet integrierte KI-Analyse.',
                  },
                  {
                    title: 'Google for Jobs kostenlos',
                    text: 'Automatisches Schema-Markup ab der kostenlosen Version. Bei WP Job Manager kostet das gleiche Feature 39 €/Jahr extra, bei MatadorJobs ist es im 799 €/Jahr-Paket versteckt.',
                  },
                ].map((item) => (
                  <div key={item.title}>
                    <h3 className="font-display text-lg text-slate-900">{item.title}</h3>
                    <p className="mt-2 text-slate-700">{item.text}</p>
                  </div>
                ))}
              </div>
            </div>
          </Container>
        </section>

        <SectionCta
          headline="Bereit zum Wechsel?"
          text="Starten Sie kostenlos mit unbegrenzten Stellenanzeigen und Google for Jobs. Upgraden Sie jederzeit auf Pro."
          cta="Kostenlos herunterladen"
        />
      </main>
      <Footer />
    </>
  )
}
