import { SparklesIcon } from '@heroicons/react/24/solid'

import { Container } from '@/components/Container'

const modes = [
  {
    name: 'Job-Match',
    question: 'Passe ich zu diesem Job?',
    description:
      'Bewerber laden ihren Lebenslauf auf einer Stellen-Einzelseite hoch. Die KI analysiert die Qualifikation und liefert einen Match-Score mit erfüllten und fehlenden Anforderungen.',
    features: [
      'Match-Score (0\u2013100%)',
      'Erf\u00fcllte & fehlende Anforderungen',
      'Automatische Formular-Vorausf\u00fcllung',
    ],
    icon: (
      <svg
        className="h-6 w-6 text-white"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={1.5}
          d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
        />
      </svg>
    ),
  },
  {
    name: 'Job-Finder',
    question: 'Welche Jobs passen zu mir?',
    description:
      'Bewerber laden ihren Lebenslauf auf der Karriere-Seite hoch. Die KI analysiert alle offenen Stellen und zeigt die Top 5 passenden Jobs mit Match-Score.',
    features: [
      'Top 5 passende Stellen',
      'Ranking nach Match-Score',
      'Ein-Klick-Bewerbung',
    ],
    icon: (
      <svg
        className="h-6 w-6 text-white"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={1.5}
          d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
        />
      </svg>
    ),
  },
  {
    name: 'Chancen-Check',
    question: 'Wie stehen meine Chancen?',
    description:
      'Tiefenanalyse nach dem Upload. Die KI berechnet realistische Einstellungschancen, zeigt St\u00e4rken und Schw\u00e4chen und gibt konkrete Tipps zur Verbesserung.',
    features: [
      'Einstellungschance (0\u2013100%)',
      'St\u00e4rken & Schw\u00e4chen-Analyse',
      'Konkrete Verbesserungstipps',
    ],
    icon: (
      <svg
        className="h-6 w-6 text-white"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={1.5}
          d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"
        />
      </svg>
    ),
  },
]

const textFeatures = [
  {
    title: 'Stellentexte generieren',
    description:
      'Jobtitel, Branche und Stichpunkte eingeben \u2014 die KI erstellt eine komplette Stellenausschreibung mit Einleitung, Aufgaben, Anforderungen und Benefits.',
  },
  {
    title: 'Texte optimieren & umschreiben',
    description:
      'Bestehende Stellentexte k\u00fcrzer, l\u00e4nger, formeller oder lockerer machen. Per Knopfdruck den Tonfall anpassen.',
  },
  {
    title: 'SEO-Vorschl\u00e4ge',
    description:
      'Die KI analysiert Ihre Stellenanzeige und schl\u00e4gt Optimierungen f\u00fcr bessere Sichtbarkeit in Suchmaschinen und Google for Jobs vor.',
  },
  {
    title: 'Branchenspezifische Textbausteine',
    description:
      'Vorlagen und Best-Practice-Formulierungen f\u00fcr Pflege, Handwerk, B\u00fcro und weitere Branchen. Sofort einsetzbar.',
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

export function FeaturesAiSection() {
  return (
    <section id="ki" className="bg-[#1d71b8] py-20 sm:py-32">
      <Container>
        {/* Header */}
        <div className="mx-auto max-w-2xl text-center">
          <div className="flex items-center justify-center gap-x-2">
            <SparklesIcon className="h-6 w-6 text-white/60" />
            <span className="text-sm font-semibold text-blue-200">
              Pro-Feature
            </span>
          </div>
          <h2 className="mt-4 font-display text-3xl tracking-tight text-white sm:text-4xl">
            KI-gest\u00fctztes Recruiting
          </h2>
          <p className="mt-4 text-lg tracking-tight text-blue-100">
            Intelligente Bewerberanalyse und automatische Textgenerierung \u2014
            direkt im Plugin.
          </p>
        </div>

        {/* 3 KI-Modi */}
        <div className="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-6 lg:max-w-none lg:grid-cols-3">
          {modes.map((mode) => (
            <div
              key={mode.name}
              className="flex flex-col rounded-2xl bg-white/10 p-6 backdrop-blur-sm"
            >
              <div className="flex items-center gap-x-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-white/20">
                  {mode.icon}
                </div>
                <h3 className="font-display text-lg text-white">
                  {mode.name}
                </h3>
              </div>
              <p className="mt-2 text-sm font-medium text-blue-200">
                \u201e{mode.question}\u201c
              </p>
              <p className="mt-3 flex-1 text-sm text-blue-100">
                {mode.description}
              </p>
              <ul role="list" className="mt-4 flex flex-col gap-y-2">
                {mode.features.map((feature) => (
                  <li
                    key={feature}
                    className="flex items-start gap-x-2 text-sm text-white"
                  >
                    <CheckIcon />
                    {feature}
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        {/* KI-Stellentexte */}
        <div className="mx-auto mt-20 max-w-4xl">
          <h3 className="text-center font-display text-2xl tracking-tight text-white sm:text-3xl">
            KI-Stellentexte auf Knopfdruck
          </h3>
          <p className="mt-4 text-center text-base text-blue-100">
            Professionelle Stellenausschreibungen in Sekunden statt Stunden.
          </p>
          <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2">
            {textFeatures.map((feature) => (
              <div key={feature.title} className="flex gap-x-3">
                <CheckIcon />
                <div>
                  <h4 className="text-sm font-semibold text-white">
                    {feature.title}
                  </h4>
                  <p className="mt-1 text-sm text-blue-100">
                    {feature.description}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </Container>
    </section>
  )
}
