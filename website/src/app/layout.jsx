import { Inter, Lexend } from 'next/font/google'
import clsx from 'clsx'

import '@/styles/tailwind.css'

export const metadata = {
  title: {
    template: '%s - Recruiting Playbook',
    default:
      'Recruiting Playbook: Das WordPress Recruiting Plugin',
  },
  description:
    'Erstelle Stellenanzeigen mit Google Jobs Integration & KI-Analyse. Profi-Recruiting für WordPress zum Einmalpreis statt Abo.',
  manifest: '/manifest.webmanifest',
  metadataBase: new URL('https://recruiting-playbook.com'),
  openGraph: {
    type: 'website',
    locale: 'de_DE',
    siteName: 'Recruiting Playbook',
    title: 'Recruiting Playbook - WordPress Recruiting Plugin mit Google for Jobs & KI',
    description:
      'Stellenanzeigen erstellen, automatisch in Google for Jobs erscheinen, Bewerbungen DSGVO-konform verwalten. Kostenlos starten, 149 € Pro einmalig.',
    images: [
      {
        url: '/screenshots/bewerbungen-liste.png',
        width: 1200,
        height: 630,
        alt: 'Recruiting Playbook - Bewerbungsmanagement in WordPress',
      },
    ],
  },
  twitter: {
    card: 'summary_large_image',
    title: 'Recruiting Playbook - WordPress Recruiting Plugin',
    description:
      'Google for Jobs, DSGVO-konform, KI-Analyse. Das Recruiting-Plugin für WordPress. Kostenlos starten.',
    images: ['/screenshots/bewerbungen-liste.png'],
  },
}

const inter = Inter({
  subsets: ['latin'],
  display: 'swap',
  variable: '--font-inter',
})

const lexend = Lexend({
  subsets: ['latin'],
  display: 'swap',
  variable: '--font-lexend',
})

export default function RootLayout({ children }) {
  return (
    <html
      lang="de"
      className={clsx(
        'h-full scroll-smooth bg-white antialiased',
        inter.variable,
        lexend.variable,
      )}
    >
      <body className="flex h-full flex-col">{children}</body>
    </html>
  )
}
