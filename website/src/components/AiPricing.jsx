import { Button } from '@/components/Button'
import { Container } from '@/components/Container'

const features = [
  'Benötigt Pro-Lizenz (149 € einmalig)',
  'Job-Match Score (0–100%)',
  'Job-Finder für Bewerber',
  'Chancen-Check mit Stärken/Schwächen',
  'KI-Stellentexte generieren & optimieren',
  'SEO-Vorschläge',
  '100 Analysen/Monat inklusive',
  'Extra-Paket: 9 € / 50 Analysen',
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

export function AiPricing() {
  return (
    <section className="py-20 sm:py-32">
      <Container>
        <div className="mx-auto max-w-lg">
          <div className="rounded-3xl bg-slate-50 p-8 ring-1 ring-slate-200 sm:p-12">
            <div className="text-center">
              <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
                KI-Addon
              </h2>
              <p className="mt-4 text-lg tracking-tight text-slate-700">
                Intelligente Bewerberanalyse und Textgenerierung.
              </p>
              <p className="mt-6 font-display text-5xl font-light tracking-tight text-[#1d71b8]">
                19 €<span className="text-2xl text-slate-500">/Monat</span>
              </p>
              <p className="mt-2 text-sm text-slate-500">
                oder 179 €/Jahr (2 Monate gratis)
              </p>
            </div>
            <ul role="list" className="mt-8 flex flex-col gap-y-3">
              {features.map((feature) => (
                <li
                  key={feature}
                  className="flex items-start gap-x-3 text-sm text-slate-700"
                >
                  <CheckIcon />
                  {feature}
                </li>
              ))}
            </ul>
            <div className="mt-8 flex flex-col gap-y-3">
              <Button href="#" color="blue" className="w-full justify-center">
                KI-Addon aktivieren
              </Button>
              <Button
                href="/pricing"
                variant="outline"
                className="w-full justify-center"
              >
                Alle Preise vergleichen
              </Button>
            </div>
          </div>
        </div>
      </Container>
    </section>
  )
}
