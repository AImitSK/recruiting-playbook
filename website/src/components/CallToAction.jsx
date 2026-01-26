import Image from 'next/image'

import { Button } from '@/components/Button'
import { Container } from '@/components/Container'
import backgroundImage from '@/images/background-call-to-action.jpg'

export function CallToAction() {
  return (
    <section
      id="get-started-today"
      className="relative overflow-hidden bg-[#1d71b8] py-32"
    >
      <Image
        className="absolute top-1/2 left-1/2 max-w-none -translate-x-1/2 -translate-y-1/2"
        src={backgroundImage}
        alt=""
        width={2347}
        height={1244}
        unoptimized
      />
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
