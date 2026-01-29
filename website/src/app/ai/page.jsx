import { Footer } from '@/components/Footer'
import { Header } from '@/components/Header'
import { AiHero } from '@/components/AiHero'
import { AiModes } from '@/components/AiModes'
import { AiTextGeneration } from '@/components/AiTextGeneration'
import { AiBenefits } from '@/components/AiBenefits'
import { AiPricing } from '@/components/AiPricing'
import { SectionCta } from '@/components/SectionCta'

export const metadata = {
  title: 'KI-Addon',
  description:
    'Der erste WordPress-Recruiter mit eingebauter KI. Match-Score, Job-Finder, Chancen-Check und KI-Stellentexte.',
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
        <AiPricing />
        <SectionCta
          headline="Testen Sie die KI-Analyse"
          text="Aktivieren Sie das KI-Addon und erleben Sie, wie intelligente Bewerberanalyse Ihr Recruiting verändert."
          cta="KI-Addon aktivieren"
          href="#"
          download={false}
        />
      </main>
      <Footer />
    </>
  )
}
