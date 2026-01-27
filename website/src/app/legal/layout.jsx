import { Container } from '@/components/Container'
import { Header } from '@/components/Header'
import { Footer } from '@/components/Footer'

export default function LegalLayout({ children }) {
  return (
    <>
      <Header />
      <main>
        <Container className="py-16 sm:py-24">
          <div className="max-w-2xl">{children}</div>
        </Container>
      </main>
      <Footer />
    </>
  )
}
