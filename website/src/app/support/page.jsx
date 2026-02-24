import Link from 'next/link'
import { Header } from '@/components/Header'
import { Footer } from '@/components/Footer'
import { Container } from '@/components/Container'

export const metadata = {
  title: 'Hilfe & Support',
  description: 'Recruiting Playbook',
}

const supportChannels = [
  {
    title: 'Dokumentation',
    description:
      'Anleitungen zu Installation, Konfiguration, Shortcodes, Templates und DSGVO-Funktionen.',
    href: '/docs',
    linkText: 'Zur Dokumentation',
    icon: (
      <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
      </svg>
    ),
  },
  {
    title: 'FAQ',
    description:
      'Antworten auf häufig gestellte Fragen zu Installation, Kompatibilität und Konfiguration.',
    href: '/docs/faq',
    linkText: 'Häufige Fragen',
    icon: (
      <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
      </svg>
    ),
  },
  {
    title: 'GitHub Issues',
    description:
      'Bugs melden, Feature-Wünsche einreichen oder Diskussionen starten — offen für alle Nutzer.',
    href: 'https://github.com/AImitSK/recruiting-playbook/issues',
    linkText: 'Issues auf GitHub',
    external: true,
    icon: (
      <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 12.75c1.148 0 2.278.08 3.383.237 1.037.146 1.866.966 1.866 2.013 0 3.728-2.35 6.75-5.25 6.75S6.75 18.728 6.75 15c0-1.046.83-1.867 1.866-2.013A24.204 24.204 0 0 1 12 12.75Zm0 0c2.883 0 5.647.508 8.207 1.44a23.91 23.91 0 0 1-1.152-6.135c-.117-1.397-1.022-2.536-2.276-2.888a11.94 11.94 0 0 0-9.558 0c-1.254.352-2.159 1.49-2.276 2.888A23.91 23.91 0 0 1 3.793 14.19 24.232 24.232 0 0 1 12 12.75ZM2.695 18.885a23.877 23.877 0 0 0 5.124-2.186M18.181 16.7a23.877 23.877 0 0 0 3.124 2.186" />
      </svg>
    ),
  },
]

export default function SupportPage() {
  return (
    <>
      <Header />
      <main>
        <Container className="py-16 sm:py-24">
          <div className="max-w-2xl">
            <h1 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
              Support
            </h1>
            <p className="mt-4 text-lg text-slate-600">
              Finden Sie Hilfe in unserer Dokumentation, stellen Sie Fragen
              auf GitHub oder kontaktieren Sie uns direkt.
            </p>
          </div>

          {/* Support-Kanäle */}
          <div className="mt-16 grid gap-8 sm:grid-cols-3">
            {supportChannels.map((channel) => (
              <div
                key={channel.title}
                className="rounded-2xl border border-slate-200 p-8"
              >
                <div className="text-[#1d71b8]">{channel.icon}</div>
                <h2 className="mt-4 font-display text-lg text-slate-900">
                  {channel.title}
                </h2>
                <p className="mt-2 text-sm text-slate-600">
                  {channel.description}
                </p>
                <Link
                  href={channel.href}
                  className="mt-4 inline-flex items-center gap-1 text-sm font-medium text-[#1d71b8] hover:text-[#1a63a3]"
                  {...(channel.external
                    ? { target: '_blank', rel: 'noopener noreferrer' }
                    : {})}
                >
                  {channel.linkText}
                  <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                  </svg>
                </Link>
              </div>
            ))}
          </div>

          {/* Direkter Kontakt */}
          <div className="mt-20 max-w-2xl rounded-2xl border border-slate-200 p-8">
            <h2 className="font-display text-2xl tracking-tight text-slate-900">
              Direkter Kontakt
            </h2>
            <p className="mt-4 text-base text-slate-600">
              Sie haben eine Frage, die in der Dokumentation nicht beantwortet
              wird? Pro- und Agentur-Kunden erreichen uns per E-Mail:
            </p>
            <a
              href="mailto:info@sk-online-marketing.de"
              className="mt-4 inline-flex items-center gap-2 text-base font-medium text-[#1d71b8] hover:text-[#1a63a3]"
            >
              <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
              </svg>
              info@sk-online-marketing.de
            </a>
            <p className="mt-6 text-sm text-slate-500">
              Bitte nennen Sie in Ihrer E-Mail Ihren Lizenzschlüssel, damit
              wir Ihre Anfrage schneller zuordnen können.
            </p>
          </div>

          {/* Kundenportal */}
          <div className="mt-12 max-w-2xl">
            <h2 className="font-display text-2xl tracking-tight text-slate-900">
              Lizenzen & Abonnements verwalten
            </h2>
            <p className="mt-4 text-base text-slate-600">
              Im Kundenportal können Sie Ihre Lizenzen einsehen,
              Abonnements verwalten, Rechnungen herunterladen und das
              Plugin als ZIP-Datei laden.
            </p>
            <Link
              href="/account"
              className="mt-4 inline-flex items-center gap-2 text-base font-medium text-[#1d71b8] hover:text-[#1a63a3]"
            >
              Zum Kundenportal
              <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
              </svg>
            </Link>
          </div>
        </Container>
      </main>
      <Footer />
    </>
  )
}
