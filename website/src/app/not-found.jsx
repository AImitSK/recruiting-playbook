import Link from 'next/link'

import { Button } from '@/components/Button'
import { Logo } from '@/components/Logo'
import { SlimLayout } from '@/components/SlimLayout'

export default function NotFound() {
  return (
    <SlimLayout>
      <div className="flex">
        <Link href="/" aria-label="Home">
          <Logo className="h-10 w-auto" />
        </Link>
      </div>
      <p className="mt-20 text-sm font-medium text-gray-700">404</p>
      <h1 className="mt-3 text-lg font-semibold text-gray-900">
        Seite nicht gefunden
      </h1>
      <p className="mt-3 text-sm text-gray-700">
        Die angeforderte Seite konnte leider nicht gefunden werden.
      </p>
      <Button href="/" className="mt-10">
        Zur Startseite
      </Button>
    </SlimLayout>
  )
}
