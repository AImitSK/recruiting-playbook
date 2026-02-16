'use client'

import { useEffect } from 'react'

/**
 * Freemius Checkout SDK Integration
 *
 * Lädt das Freemius Checkout SDK und bietet eine openCheckout Funktion
 * für den Overlay-Checkout.
 *
 * @see https://freemius.com/help/documentation/checkout/integration/freemius-checkout-buy-button/
 */

// Freemius Product & Plan Configuration
const FREEMIUS_CONFIG = {
  productId: '23533', // Recruiting Playbook Product ID
  plans: {
    pro: process.env.NEXT_PUBLIC_FREEMIUS_PRO_PLAN_ID || 'YOUR_PRO_PLAN_ID',
    agency: process.env.NEXT_PUBLIC_FREEMIUS_AGENCY_PLAN_ID || 'YOUR_AGENCY_PLAN_ID',
  },
}

/**
 * Load Freemius Checkout SDK
 */
export function useFreemiusCheckout() {
  useEffect(() => {
    // Prüfen ob SDK bereits geladen
    if (window.FS && window.FS.Checkout) {
      return
    }

    // Freemius Checkout SDK laden
    const script = document.createElement('script')
    script.src = 'https://checkout.freemius.com/checkout.min.js'
    script.async = true
    script.onload = () => {
      console.log('Freemius Checkout SDK loaded')
    }
    script.onerror = () => {
      console.error('Failed to load Freemius Checkout SDK')
    }

    document.body.appendChild(script)

    return () => {
      // Cleanup bei Unmount (optional)
      if (document.body.contains(script)) {
        document.body.removeChild(script)
      }
    }
  }, [])
}

/**
 * Open Freemius Checkout Overlay
 *
 * @param {Object} options - Checkout options
 * @param {string} options.planType - 'pro' oder 'agency'
 * @param {number} options.licenses - Anzahl Lizenzen (default: 1)
 * @param {string} options.currency - Währung (default: 'eur')
 * @param {string} options.billingCycle - 'annual' oder 'lifetime' (default: 'lifetime')
 * @param {Function} options.onSuccess - Success Callback
 * @param {Function} options.onCancel - Cancel Callback
 */
export function openFreemiusCheckout({
  planType,
  licenses = 1,
  currency = 'eur',
  billingCycle = 'lifetime',
  onSuccess,
  onCancel,
}) {
  // Warten bis SDK geladen ist
  if (!window.FS || !window.FS.Checkout) {
    console.error('Freemius Checkout SDK not loaded yet')
    // Fallback: Redirect zu Hosted Checkout
    const planId = FREEMIUS_CONFIG.plans[planType]
    window.location.href = `https://checkout.freemius.com/product/${FREEMIUS_CONFIG.productId}/plan/${planId}/licenses/${licenses}/currency/${currency}/`
    return
  }

  const planId = FREEMIUS_CONFIG.plans[planType]

  if (!planId || planId.startsWith('YOUR_')) {
    console.error('Plan ID not configured. Please set NEXT_PUBLIC_FREEMIUS_PRO_PLAN_ID and NEXT_PUBLIC_FREEMIUS_AGENCY_PLAN_ID in .env.local')
    alert('Checkout-Konfiguration fehlt. Bitte kontaktieren Sie den Support.')
    return
  }

  try {
    const checkout = new window.FS.Checkout({
      product_id: FREEMIUS_CONFIG.productId,
      plan_id: planId,
      billing_cycle: billingCycle,
      currency: currency,
    })

    checkout.open({
      licenses: licenses,

      // Success Callback
      success: function (purchaseData) {
        console.log('Purchase completed!', purchaseData)

        // Default Success Handler
        if (onSuccess) {
          onSuccess(purchaseData)
        } else {
          // Redirect zu Thank You Page
          window.location.href = '/thank-you?purchase=success'
        }
      },

      // Purchase Completed (nach Bestätigung)
      purchaseCompleted: function (purchaseData) {
        console.log('Purchase confirmed', purchaseData)
      },

      // Cancel Callback
      cancel: function () {
        console.log('Checkout cancelled')
        if (onCancel) {
          onCancel()
        }
      },

      // Tracking Events (optional)
      track: function (event, data) {
        console.log('Checkout event:', event, data)
        // Hier könnte Google Analytics / Matomo Integration
      },
    })
  } catch (error) {
    console.error('Error opening checkout:', error)
    alert('Fehler beim Öffnen des Checkouts. Bitte versuchen Sie es erneut.')
  }
}

/**
 * Freemius Checkout Provider Component
 *
 * Wrap your pricing page with this component to ensure SDK is loaded.
 */
export function FreemiusCheckoutProvider({ children }) {
  useFreemiusCheckout()
  return <>{children}</>
}
