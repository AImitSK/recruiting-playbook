import { Footer } from '@/components/Footer'
import { Header } from '@/components/Header'
import { PricingHero } from '@/components/PricingHero'
import { PricingCards } from '@/components/PricingCards'
import { FeatureComparisonTable } from '@/components/FeatureComparisonTable'
import { PricingFaqs } from '@/components/PricingFaqs'
import { SectionCta } from '@/components/SectionCta'

export const metadata = {
  title: 'Preise: Einmalzahlung statt Abo',
  description: 'Recruiting Playbook',
}

export default function PricingPage() {
  return (
    <>
      <Header />
      <main>
        <PricingHero />
        <PricingCards />
        <FeatureComparisonTable />
        <PricingFaqs />
        <SectionCta
          headline="Bereit für professionelles Recruiting?"
          text="Starten Sie kostenlos mit unbegrenzten Stellenanzeigen. Upgraden Sie jederzeit auf Pro."
          cta="Kostenlos herunterladen"
        />
      </main>
      <Footer />
    </>
  )
}
