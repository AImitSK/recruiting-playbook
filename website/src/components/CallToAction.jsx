import { Button } from '@/components/Button'
import { Container } from '@/components/Container'

export function CallToAction() {
  return (
    <section
      id="get-started-today"
      className="relative overflow-hidden py-32"
      style={{ background: 'linear-gradient(45deg, #2fac66, #36a9e1)' }}
    >
      <Container className="relative">
        <div className="mx-auto max-w-lg text-center">
          <h2 className="font-display text-3xl tracking-tight text-white sm:text-4xl">
            In 5 Minuten startklar.
          </h2>
          <p className="mt-4 text-lg tracking-tight text-white">
            Installieren Sie das Plugin, erstellen Sie Ihre erste Stelle und
            empfangen Sie Bewerbungen — noch heute.
          </p>
          <Button href="#" color="white" className="mt-10">
            Kostenlos herunterladen
          </Button>
        </div>
      </Container>
    </section>
  )
}
