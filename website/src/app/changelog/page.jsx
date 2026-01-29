import { Header } from '@/components/Header'
import { Footer } from '@/components/Footer'
import { Container } from '@/components/Container'

export const metadata = {
  title: 'Changelog',
  description:
    'Versionshistorie von Recruiting Playbook. Alle Änderungen, neue Features und Bugfixes im Überblick.',
}

const changelog = [
  {
    version: '1.0.0',
    date: '2025-03-01',
    label: 'Erster Release',
    sections: [
      {
        type: 'added',
        items: [
          'Custom Post Type „Stellenanzeige" mit Kategorien, Standorten und Beschäftigungsarten',
          'Mehrstufiges Bewerbungsformular mit Datei-Upload (Lebenslauf, Anschreiben, Zeugnisse)',
          'Google for Jobs Schema-Markup (JSON-LD) für alle Stellenanzeigen',
          'Shortcodes: [rp_jobs], [rp_job], [rp_application_form]',
          'E-Mail-Benachrichtigungen bei neuen Bewerbungen',
          'Bewerbungsübersicht im WordPress-Admin mit Statusverwaltung',
          'DSGVO-konforme Datenverarbeitung mit Einwilligungsmanagement',
          'Template-System mit überschreibbaren Templates im Theme',
          'Sichere Dokumentenverwaltung außerhalb des öffentlichen Upload-Verzeichnisses',
          'Vollständig responsive Frontend-Darstellung',
        ],
      },
    ],
  },
]

const typeConfig = {
  added: { label: 'Hinzugefügt', color: 'bg-emerald-100 text-emerald-800' },
  changed: { label: 'Geändert', color: 'bg-blue-100 text-blue-800' },
  fixed: { label: 'Behoben', color: 'bg-amber-100 text-amber-800' },
  removed: { label: 'Entfernt', color: 'bg-red-100 text-red-800' },
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('de-DE', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

export default function ChangelogPage() {
  return (
    <>
      <Header />
      <main>
        <Container className="py-16 sm:py-24">
          <div className="max-w-2xl">
            <h1 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
              Changelog
            </h1>
            <p className="mt-4 text-lg text-slate-600">
              Alle Änderungen an Recruiting Playbook — neue Features,
              Verbesserungen und Bugfixes.
            </p>
          </div>

          <div className="mt-16 max-w-2xl space-y-16">
            {changelog.map((release) => (
              <article key={release.version}>
                <div className="flex items-center gap-4">
                  <h2 className="font-display text-2xl tracking-tight text-slate-900">
                    {release.version}
                  </h2>
                  {release.label && (
                    <span className="rounded-full bg-[#1d71b8]/10 px-3 py-1 text-sm font-medium text-[#1d71b8]">
                      {release.label}
                    </span>
                  )}
                </div>
                <time
                  dateTime={release.date}
                  className="mt-1 block text-sm text-slate-500"
                >
                  {formatDate(release.date)}
                </time>

                <div className="mt-6 space-y-6">
                  {release.sections.map((section) => (
                    <div key={section.type}>
                      <span
                        className={`inline-block rounded-full px-3 py-1 text-xs font-semibold ${typeConfig[section.type].color}`}
                      >
                        {typeConfig[section.type].label}
                      </span>
                      <ul className="mt-3 space-y-2 text-base text-slate-700">
                        {section.items.map((item, i) => (
                          <li key={i} className="flex gap-3">
                            <span className="mt-1.5 h-1.5 w-1.5 flex-none rounded-full bg-slate-400" />
                            <span>{item}</span>
                          </li>
                        ))}
                      </ul>
                    </div>
                  ))}
                </div>
              </article>
            ))}
          </div>
        </Container>
      </main>
      <Footer />
    </>
  )
}
