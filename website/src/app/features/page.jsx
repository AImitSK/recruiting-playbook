import { Footer } from '@/components/Footer'
import { Header } from '@/components/Header'
import { FeaturesHero } from '@/components/FeaturesHero'
import { FeatureHighlights } from '@/components/FeatureHighlights'
import { FeaturesByTier } from '@/components/FeaturesByTier'
import { FeatureComparisonTable } from '@/components/FeatureComparisonTable'
import { SectionCta } from '@/components/SectionCta'

export const metadata = {
  title: 'Features',
  description:
    'Alle Features von Recruiting Playbook im Überblick. Stellenanzeigen, Bewerbermanagement, KI-Analyse und mehr.',
}

export default function FeaturesPage() {
  return (
    <>
      <Header />
      <main>
        <FeaturesHero />
        <FeatureHighlights />
        <FeaturesByTier />
        <FeatureComparisonTable />
        <SectionCta
          headline="Bereit loszulegen?"
          text="Installieren Sie das Plugin und erstellen Sie Ihre erste Stellenanzeige. Kostenlos und in wenigen Minuten."
          cta="Kostenlos herunterladen"
        />
      </main>
      <Footer />
    </>
  )
}
