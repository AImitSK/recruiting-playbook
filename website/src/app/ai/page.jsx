import { Footer } from '@/components/Footer'
import { Header } from '@/components/Header'
import { AiHero } from '@/components/AiHero'
import { AiModes } from '@/components/AiModes'
import { AiTextGeneration } from '@/components/AiTextGeneration'
import { AiBenefits } from '@/components/AiBenefits'
import { SectionCta } from '@/components/SectionCta'

export const metadata = {
  title: 'KI-Funktionen',
  description:
    'Der erste WordPress-Recruiter mit eingebauter KI. Match-Score, Job-Finder, Chancen-Check und KI-Stellentexte – inklusive in der Pro-Version.',
}

export default function AiPage() {
  return (
    <>
      <Header />
      <main>
        <AiHero />
        <AiModes />
        <AiTextGeneration />
        <AiBenefits />
        <SectionCta
          headline="KI-Analyse inklusive in Pro"
          text="100 KI-Analysen pro Monat sind in der Pro-Version enthalten. Erleben Sie, wie intelligente Bewerberanalyse Ihr Recruiting verändert."
          cta="Pro kaufen – 149 €"
          href="/pricing"
          download={false}
        />
      </main>
      <Footer />
    </>
  )
}
