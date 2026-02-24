import {
  MagnifyingGlassIcon,
  ShieldCheckIcon,
  SparklesIcon,
  CurrencyEuroIcon,
} from '@heroicons/react/20/solid'

import { Button } from '@/components/Button'
import { Container } from '@/components/Container'

const badges = [
  { label: 'Google for Jobs', icon: MagnifyingGlassIcon },
  { label: 'DSGVO-konform', icon: ShieldCheckIcon },
  { label: 'KI-Analyse', icon: SparklesIcon },
  { label: 'Kein Abo', icon: CurrencyEuroIcon },
]

export function FeaturesHeroBadges() {
  return (
    <Container className="pt-20 pb-16 text-center lg:pt-32">
      <h1 className="mx-auto max-w-4xl font-display text-5xl font-medium tracking-tight text-slate-900 sm:text-7xl">
        Alles was Sie für{' '}
        <span className="text-[#1d71b8]">professionelles Recruiting</span>{' '}
        brauchen
      </h1>
      <p className="mx-auto mt-6 max-w-2xl text-lg tracking-tight text-slate-700">
        Von der Stellenanzeige bis zur Einstellung. Ein Plugin, das mit Ihren
        Anforderungen wächst.
      </p>

      <div className="mt-8 flex flex-wrap justify-center gap-3">
        {badges.map((badge) => (
          <span
            key={badge.label}
            className="inline-flex items-center gap-x-2 rounded-full bg-[#1d71b8]/10 px-4 py-2 text-sm font-semibold text-[#1d71b8] ring-1 ring-[#1d71b8]/20 ring-inset"
          >
            <badge.icon className="h-4 w-4" aria-hidden="true" />
            {badge.label}
          </span>
        ))}
      </div>

      <div className="mt-10 flex justify-center gap-x-6">
        <Button href="/recruiting-playbook.zip" color="blue" download>
          Kostenlos herunterladen
        </Button>
        <Button href="/pricing" variant="outline">
          Preise vergleichen
        </Button>
      </div>
    </Container>
  )
}
