import Link from 'next/link'

import { Header } from '@/components/Header'
import { Footer } from '@/components/Footer'
import { Container } from '@/components/Container'
import { Button } from '@/components/Button'

export const metadata = {
  title: 'Kundenportal',
  description:
    'Verwalten Sie Ihre Lizenzen, Abonnements und Rechnungen im Recruiting Playbook Kundenportal.',
}

const portalFeatures = [
  {
    title: 'Lizenzen verwalten',
    description: 'Aktivieren, deaktivieren und übertragen Sie Ihre Lizenzen zwischen Domains.',
    icon: (
      <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
      </svg>
    ),
  },
  {
    title: 'Rechnungen & Zahlungen',
    description: 'Laden Sie Rechnungen herunter und verwalten Sie Ihre Zahlungsmethoden.',
    icon: (
      <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
      </svg>
    ),
  },
  {
    title: 'Plugin herunterladen',
    description: 'Laden Sie die aktuelle Version des Plugins als ZIP-Datei herunter.',
    icon: (
      <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
      </svg>
    ),
  },
  {
    title: 'Abonnements',
    description: 'KI-Addon und Wartungsverlängerungen verwalten, kündigen oder upgraden.',
    icon: (
      <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
      </svg>
    ),
  },
]

export default function AccountPage() {
  return (
    <>
      <Header />
      <main>
        <Container className="py-16 sm:py-24">
          <div className="mx-auto max-w-2xl text-center">
            <h1 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
              Kundenportal
            </h1>
            <p className="mt-4 text-lg text-slate-600">
              Verwalten Sie Ihre Lizenzen, laden Sie das Plugin herunter und
              greifen Sie auf Ihre Rechnungen zu.
            </p>
            <div className="mt-8">
              <Button
                href="https://customers.freemius.com/store/11342"
                color="blue"
              >
                Zum Kundenportal
              </Button>
            </div>
          </div>

          <div className="mx-auto mt-20 grid max-w-2xl grid-cols-1 gap-6 sm:grid-cols-2">
            {portalFeatures.map((feature) => (
              <div
                key={feature.title}
                className="flex gap-x-4 rounded-2xl border border-slate-200 p-6"
              >
                <div className="flex-none text-[#1d71b8]">
                  {feature.icon}
                </div>
                <div>
                  <h3 className="font-display text-sm font-semibold text-slate-900">
                    {feature.title}
                  </h3>
                  <p className="mt-1 text-sm text-slate-600">
                    {feature.description}
                  </p>
                </div>
              </div>
            ))}
          </div>

          <div className="mx-auto mt-16 max-w-2xl rounded-2xl bg-slate-50 p-8 text-center">
            <h2 className="font-display text-lg text-slate-900">
              Noch kein Konto?
            </h2>
            <p className="mt-2 text-sm text-slate-600">
              Beim Kauf einer Pro- oder Agentur-Lizenz wird automatisch ein
              Konto erstellt. Sie erhalten eine E-Mail mit Ihren Zugangsdaten.
            </p>
            <div className="mt-6 flex justify-center gap-x-4">
              <Button href="/pricing" variant="outline">
                Preise ansehen
              </Button>
              <Button href="/support" variant="outline">
                Support kontaktieren
              </Button>
            </div>
          </div>
        </Container>
      </main>
      <Footer />
    </>
  )
}
