import { Button } from '@/components/Button'
import { Container } from '@/components/Container'

export function FeaturesHero() {
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
