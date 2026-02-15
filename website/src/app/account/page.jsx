'use client'

import { Header } from '@/components/Header'
import { useEffect } from 'react'

export default function AccountPage() {
  useEffect(() => {
    // Load jQuery first (required by Freemius)
    const jqueryScript = document.createElement('script')
    jqueryScript.src = 'https://code.jquery.com/jquery-3.7.1.min.js'
    jqueryScript.integrity = 'sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo='
    jqueryScript.crossOrigin = 'anonymous'

    jqueryScript.onload = () => {
      // Load Freemius Members JavaScript after jQuery
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
    }

    document.body.appendChild(jqueryScript)

    return () => {
      // Cleanup scripts on unmount
      const scripts = document.querySelectorAll('script[src*="jquery"], script[src*="freemius"]')
      scripts.forEach(s => s.parentNode?.removeChild(s))
    }
  }, [])

  return (
    <div className="flex min-h-screen flex-col">
      <Header />
      <div id="fs-members-portal" className="flex-1"></div>
    </div>
  )
}
