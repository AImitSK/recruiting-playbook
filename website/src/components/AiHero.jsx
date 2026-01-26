import { Button } from '@/components/Button'
import { Container } from '@/components/Container'

export function AiHero() {
  return (
    <Container className="pt-20 pb-16 lg:pt-32">
      <div className="mx-auto max-w-4xl text-center">
        <h1 className="font-display text-5xl font-medium tracking-tight text-slate-900 sm:text-7xl">
          Der erste WordPress-Recruiter mit{' '}
          <span className="text-[#1d71b8]">eingebauter KI</span>
        </h1>
        <p className="mx-auto mt-6 max-w-2xl text-lg tracking-tight text-slate-700">
          Bewerber laden ihren Lebenslauf hoch und wissen in Sekunden, wie ihre
          Chancen stehen. Mehr qualifizierte Bewerbungen, weniger
          Zeitverschwendung.
        </p>
        <div className="mt-10 flex justify-center gap-x-6">
          <Button href="#" color="blue">
            KI-Addon aktivieren
          </Button>
          <Button href="/pricing" variant="outline">
            Preise ansehen
          </Button>
        </div>
      </div>
      <div className="mx-auto mt-20 max-w-lg">
        <div className="rounded-3xl bg-slate-50 p-8 text-center ring-1 ring-slate-200">
          <p className="text-sm font-medium text-slate-500">Match-Score</p>
          <p className="mt-2 font-display text-7xl font-light tracking-tight text-[#2fac66]">
            85%
          </p>
          <p className="mt-2 text-sm text-slate-600">
            Pflegefachkraft (m/w/d) — Beispiel-Analyse
          </p>
          <div className="mt-6 space-y-3">
            <div className="flex items-center gap-x-3 text-left text-sm">
              <svg
                className="h-5 w-5 flex-none text-[#2fac66]"
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
              <span className="text-slate-700">
                Examinierte Pflegefachkraft
              </span>
            </div>
            <div className="flex items-center gap-x-3 text-left text-sm">
              <svg
                className="h-5 w-5 flex-none text-[#2fac66]"
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
              <span className="text-slate-700">3+ Jahre Berufserfahrung</span>
            </div>
            <div className="flex items-center gap-x-3 text-left text-sm">
              <svg
                className="h-5 w-5 flex-none text-[#2fac66]"
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
              <span className="text-slate-700">Führerschein Klasse B</span>
            </div>
            <div className="flex items-center gap-x-3 text-left text-sm">
              <svg
                className="h-5 w-5 flex-none text-amber-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                />
              </svg>
              <span className="text-slate-700">
                Wundmanagement-Zertifikat fehlt
              </span>
            </div>
          </div>
        </div>
      </div>
    </Container>
  )
}
