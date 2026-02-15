'use client'

import { useEffect } from 'react'

export default function AccountPage() {
  useEffect(() => {
    // Redirect directly to Freemius customer portal
    window.location.href = 'https://customers.freemius.com/store/11769/websites'
  }, [])

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50">
      <div className="text-center">
        <div className="mb-4 inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-blue-600 border-r-transparent"></div>
        <p className="text-lg text-slate-700">Weiterleitung zum Kundenportal...</p>
      </div>
    </div>
  )
}
