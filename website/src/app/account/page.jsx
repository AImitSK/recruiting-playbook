'use client'

import { Header } from '@/components/Header'

export default function AccountPage() {
  return (
    <div className="flex min-h-screen flex-col">
      <Header />
      <div className="flex-1">
        <iframe
          src="https://customers.freemius.com/store/11769/?public_key=pk_65d66d3095ac20b7ae1924f8f8fff"
          title="Kundenportal — Recruiting Playbook"
          className="h-full w-full border-0"
          style={{ minHeight: 'calc(100vh - 140px)' }}
          allow="payment"
        />
      </div>
    </div>
  )
}
