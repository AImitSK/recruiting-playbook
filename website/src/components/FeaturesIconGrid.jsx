import {
  ShieldCheckIcon,
  UserGroupIcon,
  CodeBracketIcon,
  PuzzlePieceIcon,
  CommandLineIcon,
  ArrowDownTrayIcon,
  CogIcon,
  GlobeAltIcon,
  UserIcon,
} from '@heroicons/react/24/outline'

import { Container } from '@/components/Container'

const features = [
  {
    name: 'DSGVO-konform',
    description:
      'Einwilligungsmanagement, automatische Datenlöschung und Anonymisierung. Datenschutz ist kein Nachgedanke, sondern eingebaut.',
    icon: ShieldCheckIcon,
  },
  {
    name: 'Benutzerrollen',
    description:
      'HR-Manager, Recruiter und Hiring-Manager mit granularen Berechtigungen. Jeder sieht nur, was er sehen darf.',
    icon: UserGroupIcon,
  },
  {
    name: 'REST API',
    description:
      'Vollständige REST API zum Anbinden externer Systeme, Jobportale und eigener Integrationen.',
    icon: CodeBracketIcon,
  },
  {
    name: 'Page Builder',
    description:
      'Native Widgets für Elementor und Avada. Stellenanzeigen und Bewerbungsformulare per Drag & Drop platzieren.',
    icon: PuzzlePieceIcon,
  },
  {
    name: 'Shortcodes',
    description:
      'Flexible Shortcodes für Joblisten, Einzelstellen und Bewerbungsformulare. Funktioniert in jedem Theme.',
    icon: CommandLineIcon,
  },
  {
    name: 'Backup & Export',
    description:
      'Bewerber- und Stellendaten als CSV exportieren. Regelmäßige Backups für Compliance und Archivierung.',
    icon: ArrowDownTrayIcon,
  },
  {
    name: 'Setup-Wizard',
    description:
      'In 5 Minuten startklar. Der Einrichtungsassistent führt Sie durch Grundeinstellungen, E-Mails und Design.',
    icon: CogIcon,
  },
  {
    name: 'Mehrsprachig',
    description:
      'Übersetzungsfertig mit .po/.mo-Dateien. Kompatibel mit WPML und Polylang für mehrsprachige Karriereseiten.',
    icon: GlobeAltIcon,
  },
  {
    name: 'Bewerber-Details',
    description:
      'Tabs für Dokumente, Notizen, Verlauf und E-Mails. Sterne-Bewertung und Talent-Pool für vielversprechende Kandidaten.',
    icon: UserIcon,
  },
]

export function FeaturesIconGrid() {
  return (
    <section className="bg-slate-50 py-20 sm:py-32">
      <Container>
        <div className="mx-auto max-w-2xl text-center">
          <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
            Und noch viel mehr
          </h2>
          <p className="mt-4 text-lg tracking-tight text-slate-700">
            Alles, was ein professionelles ATS braucht — direkt in WordPress.
          </p>
        </div>

        <div className="mx-auto mt-16 grid max-w-5xl grid-cols-1 gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
          {features.map((feature) => (
            <div key={feature.name} className="relative">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-[#1d71b8]/10">
                <feature.icon
                  className="h-6 w-6 text-[#1d71b8]"
                  aria-hidden="true"
                />
              </div>
              <h3 className="mt-4 text-base font-semibold text-slate-900">
                {feature.name}
              </h3>
              <p className="mt-2 text-sm text-slate-600">
                {feature.description}
              </p>
            </div>
          ))}
        </div>
      </Container>
    </section>
  )
}
