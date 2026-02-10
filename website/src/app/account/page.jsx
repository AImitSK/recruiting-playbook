'use client'

import { Header } from '@/components/Header'

export default function AccountPage() {
  return (
    <div className="flex min-h-screen flex-col">
      <Header />
      <div className="flex-1">
        <iframe
          src="https://customers.freemius.com/store/11342/?public_key=pk_3669ed73da9edbfbaa66011012de3"
          title="Kundenportal — Recruiting Playbook"
          className="h-full w-full border-0"
          style={{ minHeight: 'calc(100vh - 140px)' }}
          allow="payment"
        />
      </div>
    </div>
  )
}
