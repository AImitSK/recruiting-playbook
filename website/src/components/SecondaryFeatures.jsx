import { PlusIcon, SparklesIcon } from '@heroicons/react/16/solid'

import { Container } from '@/components/Container'
import { Button } from '@/components/Button'

const tiers = [
  {
    name: 'Free',
    description: 'Alles Wichtige. Kostenlos und ohne Limits.',
    href: '/recruiting-playbook.zip',
    download: true,
    cta: 'Kostenlos herunterladen',
    highlights: [
      'Unbegrenzte Stellenanzeigen',
      'Mehrstufiges Bewerbungsformular',
      'Google for Jobs Schema',
      'Dokument-Upload',
      'E-Mail-Benachrichtigungen',
    ],
  },
  {
    name: 'Pro',
    description: 'Bewerbermanagement mit KI-Features.',
    href: '/pricing',
    cta: 'Pro freischalten',
    highlights: [
      'Erweitertes Bewerbermanagement',
      'Kanban-Board (Drag & Drop)',
      'Formular Builder',
      'Integrations Library',
      'REST API & Webhooks',
    ],
  },
  {
    name: 'KI-Analyse',
    icon: SparklesIcon,
    description: 'Intelligente Bewerberanalyse in Pro inkl.',
    href: '/features#ki',
    cta: 'Mehr erfahren',
    highlights: [
      'Job-Match Score (0–100%)',
      'Job-Finder: Passende Jobs finden',
      'Chancen-Check für Bewerber',
      'Stärken/Schwächen-Analyse',
      '100 KI-Analysen/Monat inklusive',
    ],
  },
]

export function SecondaryFeatures() {
  return (
    <section
      id="tiers"
      aria-label="Feature-Übersicht"
      className="py-20 sm:py-32"
    >
      <Container>
        <div className="mx-auto max-w-2xl text-center">
          <h2 className="text-base/7 font-semibold text-[#1d71b8]">
            Preise
          </h2>
          <p className="mt-2 text-4xl font-semibold tracking-tight text-balance text-gray-900 sm:text-5xl">
            Kostenlos starten. Bei Bedarf upgraden.
          </p>
          <p className="mt-6 text-lg/8 text-gray-600">
            Free für unbegrenzte Stellenanzeigen. Pro für Bewerbermanagement,
            KI-Analyse und Premium-Support. Einmalpreis, kein Abo.
          </p>
        </div>
      </Container>

      {/* Pricing Cards */}
      <div className="relative py-16 sm:py-24">
        <div className="absolute inset-x-0 top-48 h-[32rem] bg-[radial-gradient(circle_at_center_center,#1d71b8,#0f3d6b,#030712_70%)]" />
        <div className="relative mx-auto max-w-2xl px-6 lg:max-w-7xl lg:px-8">
          <div className="grid grid-cols-1 gap-10 lg:grid-cols-3">
            {tiers.map((tier) => (
              <div
                key={tier.name}
                className="-m-2 grid grid-cols-1 rounded-[2rem] shadow-[inset_0_0_2px_1px_#ffffff4d] ring-1 ring-black/5 max-lg:mx-auto max-lg:w-full max-lg:max-w-md"
              >
                <div className="grid grid-cols-1 rounded-[2rem] p-2 shadow-md shadow-black/5">
                  <div className="flex flex-col rounded-3xl bg-white p-10 pb-9 shadow-2xl ring-1 ring-black/5">
                    <h3 className="flex items-center gap-2 font-display text-2xl font-semibold text-gray-950">
                      {tier.icon && <tier.icon className="size-6 text-[#1d71b8]" />}
                      {tier.name}
                    </h3>
                    <p className="mt-2 text-sm/6 text-pretty text-gray-600">
                      {tier.description}
                    </p>
                    <div className="mt-8">
                      <Button
                        href={tier.href}
                        color="blue"
                        {...(tier.download ? { download: true } : {})}
                      >
                        {tier.cta}
                      </Button>
                    </div>
                    <div className="mt-8 flex-1">
                      <h4 className="text-sm/6 font-medium text-gray-950">
                        Enthalten:
                      </h4>
                      <ul className="mt-3 space-y-3">
                        {tier.highlights.map((highlight) => (
                          <li
                            key={highlight}
                            className="flex items-start gap-4 text-sm/6 text-gray-600"
                          >
                            <span className="inline-flex h-6 items-center">
                              <PlusIcon
                                aria-hidden="true"
                                className="size-4 fill-gray-400"
                              />
                            </span>
                            {highlight}
                          </li>
                        ))}
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}
