'use client'

import { Header } from '@/components/Header'
import { useEffect } from 'react'

export default function AccountPage() {
  useEffect(() => {
    // Load Freemius Members JavaScript
    const script = document.createElement('script')
    script.src = 'https://customers.freemius.com/js/v1/'
    script.async = true

    script.onload = () => {
      // Configure Freemius Members Portal
      if (window.FS && window.FS.Members) {
        window.FS.Members.configure({
          store_id: 11769,
          public_key: 'pk_65d66d3095ac20b7ae1924f8f8fff',
          css: {
            position: 'fixed',
            top: '80px',
            right: '0',
            bottom: '0',
            left: '0',
            zIndex: 999
          }
        })
      }
    }

    document.body.appendChild(script)

    return () => {
      // Cleanup script on unmount
      if (script.parentNode) {
        script.parentNode.removeChild(script)
      }
    }
  }, [])

  return (
    <div className="flex min-h-screen flex-col">
      <Header />
      <div id="fs-members-portal" className="flex-1"></div>
    </div>
  )
}
