import { Button } from '@/components/Button'
import { Container } from '@/components/Container'

export function SectionCta({
  headline = 'Bereit loszulegen?',
  text = 'Installieren Sie das Plugin, erstellen Sie Ihre erste Stelle und empfangen Sie Bewerbungen. Noch heute.',
  cta = 'Kostenlos herunterladen',
  href = '/recruiting-playbook.zip',
  download = true,
}) {
  return (
    <section
      className="relative overflow-hidden py-20 sm:py-32"
      style={{ background: 'linear-gradient(45deg, #2fac66, #36a9e1)' }}
    >
      <Container className="relative">
        <div className="mx-auto max-w-lg text-center">
          <h2 className="font-display text-3xl tracking-tight text-white sm:text-4xl">
            {headline}
          </h2>
          <p className="mt-4 text-lg tracking-tight text-white">{text}</p>
          <Button
            href={href}
            color="white"
            className="mt-10"
            {...(download ? { download: true } : {})}
          >
            {cta}
          </Button>
        </div>
      </Container>
    </section>
  )
}
