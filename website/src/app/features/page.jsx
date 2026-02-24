import { Footer } from '@/components/Footer'
import { Header } from '@/components/Header'
import { FeaturesHeroBadges } from '@/components/FeaturesHeroBadges'
import { FeaturesBentoGrid } from '@/components/FeaturesBentoGrid'
import { FeaturesAiSection } from '@/components/FeaturesAiSection'
import { FeaturesAlternating } from '@/components/FeaturesAlternating'
import { FeaturesIconGrid } from '@/components/FeaturesIconGrid'
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
        <FeaturesHeroBadges />
        <FeaturesBentoGrid />
        <FeaturesAiSection />
        <FeaturesAlternating />
        <FeaturesIconGrid />
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
