import { Container } from '@/components/Container'

const features = [
  {
    title: 'Stellentexte generieren',
    description:
      'Geben Sie Jobtitel, Branche und Stichpunkte ein — die KI erstellt eine komplette Stellenausschreibung mit Einleitung, Aufgaben, Anforderungen und Benefits.',
  },
  {
    title: 'Texte optimieren & umschreiben',
    description:
      'Bestehende Stellentexte kürzer, länger, formeller oder lockerer machen. Per Knopfdruck den Tonfall anpassen.',
  },
  {
    title: 'SEO-Vorschläge',
    description:
      'Die KI analysiert Ihre Stellenanzeige und schlägt Optimierungen für bessere Sichtbarkeit in Suchmaschinen und Google for Jobs vor.',
  },
  {
    title: 'Branchenspezifische Textbausteine',
    description:
      'Vorlagen und Best-Practice-Formulierungen für Pflege, Handwerk, Büro und weitere Branchen. Sofort einsetzbar.',
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

export function AiTextGeneration() {
  return (
    <section className="py-20 sm:py-32">
      <Container>
        <div className="mx-auto max-w-2xl lg:mx-0">
          <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
            KI-Stellentexte auf Knopfdruck
          </h2>
          <p className="mt-4 text-lg tracking-tight text-slate-700">
            Gute Stellenanzeigen zu schreiben dauert. Mit der KI generieren Sie
            professionelle Texte in Sekunden.
          </p>
        </div>
        <div className="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-8 lg:max-w-none lg:grid-cols-2">
          {features.map((feature) => (
            <div key={feature.title} className="flex gap-x-4">
              <CheckIcon />
              <div>
                <h3 className="font-display text-lg text-slate-900">
                  {feature.title}
                </h3>
                <p className="mt-2 text-sm text-slate-700">
                  {feature.description}
                </p>
              </div>
            </div>
          ))}
        </div>
      </Container>
    </section>
  )
}
