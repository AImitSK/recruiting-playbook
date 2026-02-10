import { Header } from '@/components/Header'
import { Footer } from '@/components/Footer'
import { Container } from '@/components/Container'
import { DocsSidebar } from '@/components/docs/DocsSidebar'

export const metadata = {
  title: {
    template: '%s - Dokumentation - Recruiting Playbook',
    default: 'Dokumentation - Recruiting Playbook',
  },
  description:
    'Technische Dokumentation fuer das Recruiting Playbook WordPress-Plugin.',
}

export default function DocsLayout({ children }) {
  return (
    <>
      <Header />
      <main className="flex-1">
        <Container>
          <div className="flex flex-col lg:flex-row lg:gap-10 py-10 lg:py-16">
            <DocsSidebar />
            <div className="min-w-0 flex-1">{children}</div>
          </div>
        </Container>
      </main>
      <Footer />
    </>
  )
}
