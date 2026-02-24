import { Header } from '@/components/Header'
import { Footer } from '@/components/Footer'
import { Container } from '@/components/Container'

export const metadata = {
  title: 'Changelog & Updates',
  description: 'Recruiting Playbook',
}

const changelog = [
  {
    version: '1.2.21',
    date: '2026-02-23',
    label: 'Aktuell',
    sections: [
      {
        type: 'added',
        items: [
          'Checkbox zum Hervorheben von Stellen im Job-Editor',
          'Übersetzungen für ES, FR, IT, NL',
          'Deutsche Übersetzungen für 429 Admin-UI-Strings',
        ],
      },
      {
        type: 'fixed',
        items: [
          'JSON-Übersetzungen für webpack-Bundles korrekt zusammengeführt',
        ],
      },
    ],
  },
  {
    version: '1.2.20',
    date: '2026-02-20',
    label: 'i18n',
    sections: [
      {
        type: 'changed',
        items: [
          'React Admin-UI auf englische Source-Strings umgestellt (Basis für Mehrsprachigkeit)',
          'POT-Datei mit neuen englischen Source-Strings regeneriert',
        ],
      },
      {
        type: 'added',
        items: [
          'Listenansicht für [rp_job_categories] Shortcode',
        ],
      },
      {
        type: 'fixed',
        items: [
          'Bewerbungsformular aus Page-Buildern entfernt (wird automatisch eingebunden)',
          'Job-Kategorien 404-Fehler behoben',
        ],
      },
    ],
  },
  {
    version: '1.2.16',
    date: '2026-02-10',
    sections: [
      {
        type: 'added',
        items: [
          'Export/Import-Feature mit ID-Mapping',
          'Taxonomie-Verwaltung (Kategorien, Standorte, Beschäftigungsarten) im Admin-Menü',
          'Löschen-Button in Bewerbungsliste',
          'E-Mail-Vorlagen im Menü vor Einstellungen',
          'Stellenangebote als eigener Menüpunkt',
        ],
      },
      {
        type: 'fixed',
        items: [
          'WordPress Admin Notices Header-Layout auf React-Seiten',
          'E-Mail-Signaturen bei Auto-E-Mails',
          'E-Mail-Begrüßung zeigt vollen Namen',
        ],
      },
    ],
  },
  {
    version: '1.2.14',
    date: '2026-02-01',
    sections: [
      {
        type: 'changed',
        items: [
          'KI-Analyse-Modell auf Claude Sonnet 4.6 aktualisiert',
        ],
      },
      {
        type: 'fixed',
        items: [
          'KI-Matching Scoring-Kalibrierung und Requirements-Extraktion',
          'KI-Analyse Tab und E-Mail-Automatisierung',
          'E-Mail-Automatisierung und KI Feature-Toggle',
        ],
      },
    ],
  },
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
