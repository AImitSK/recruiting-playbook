import { Header } from '@/components/Header'
import { Footer } from '@/components/Footer'
import { Container } from '@/components/Container'
import { ApiSidebar } from '@/components/api/ApiSidebar'

export const metadata = {
  title: {
    template: '%s - API-Referenz - Recruiting Playbook',
    default: 'API-Referenz - Recruiting Playbook',
  },
  description:
    'REST API Dokumentation für das Recruiting Playbook WordPress Plugin. Endpoints für Jobs, Bewerbungen, Webhooks und Berichte.',
}

export default function ApiLayout({ children }) {
  return (
    <>
      <Header />
      <main className="flex-1">
        <Container>
          <div className="flex flex-col lg:flex-row lg:gap-10 py-10 lg:py-16">
            <ApiSidebar />
            <div className="min-w-0 flex-1">{children}</div>
          </div>
        </Container>
      </main>
      <Footer />
    </>
  )
}
