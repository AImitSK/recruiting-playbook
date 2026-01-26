import { Inter, Lexend } from 'next/font/google'
import clsx from 'clsx'

import '@/styles/tailwind.css'

export const metadata = {
  title: {
    template: '%s - Recruiting Playbook',
    default:
      'Recruiting Playbook - Professionelles Bewerbermanagement für WordPress',
  },
  description:
    'Stellenanzeigen erstellen, Bewerbungen verwalten, Bewerber einstellen. Direkt auf deiner WordPress-Website. Kostenlos, DSGVO-konform, mit optionaler KI.',
  manifest: '/manifest.webmanifest',
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
