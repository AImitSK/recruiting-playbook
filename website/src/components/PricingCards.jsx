'use client'

import clsx from 'clsx'

import { Button } from '@/components/Button'
import { Container } from '@/components/Container'
import { useFreemiusCheckout, openFreemiusCheckout } from '@/components/FreemiusCheckout'

function CheckIcon({ className, ...props }) {
  return (
    <svg
      aria-hidden="true"
      className={clsx(
        'h-6 w-6 flex-none fill-current stroke-current',
        className,
      )}
      {...props}
    >
      <path
        d="M9.307 12.248a.75.75 0 1 0-1.114 1.004l1.114-1.004ZM11 15.25l-.557.502a.75.75 0 0 0 1.15-.043L11 15.25Zm4.844-5.041a.75.75 0 0 0-1.188-.918l1.188.918Zm-7.651 3.043 2.25 2.5 1.114-1.004-2.25-2.5-1.114 1.004Zm3.4 2.457 4.25-5.5-1.187-.918-4.25 5.5 1.188.918Z"
        strokeWidth={0}
      />
      <circle
        cx={12}
        cy={12}
        r={8.25}
        fill="none"
        strokeWidth={1.5}
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  )
}

function Plan({
  name,
  price,
  priceDetail,
  description,
  href,
  features,
  extras,
  featured = false,
  cta,
  download = false,
  freemiusLicenses,
}) {
  const handleCheckoutClick = (e) => {
    if (freemiusLicenses) {
      e.preventDefault()
      openFreemiusCheckout({
        licenses: freemiusLicenses,
      })
    }
  }

  return (
    <section
      className={clsx(
        'flex flex-col rounded-3xl px-6 sm:px-8',
        featured ? 'order-first bg-[#1d71b8] py-8 lg:order-0' : 'lg:py-8',
      )}
    >
      <h3 className="mt-5 font-display text-lg text-white">{name}</h3>
      <p
        className={clsx(
          'mt-2 text-base',
          featured ? 'text-white' : 'text-slate-400',
        )}
      >
        {description}
      </p>
      <div className="order-first">
        <p className="font-display text-5xl font-light tracking-tight text-white">
          {price}
        </p>
        {priceDetail && (
          <p
            className={clsx(
              'mt-1 text-sm',
              featured ? 'text-blue-200' : 'text-slate-400',
            )}
          >
            {priceDetail}
          </p>
        )}
      </div>
      <ul
        role="list"
        className={clsx(
          'order-last mt-10 flex flex-col gap-y-3 text-sm',
          featured ? 'text-white' : 'text-slate-200',
        )}
      >
        {features.map((feature) => (
          <li key={feature} className="flex">
            <CheckIcon
              className={featured ? 'text-white' : 'text-slate-400'}
            />
            <span className="ml-4">{feature}</span>
          </li>
        ))}
      </ul>
      {extras && (
        <div
          className={clsx(
            'order-last mt-6 border-t pt-6 text-sm',
            featured
              ? 'border-white/20 text-blue-200'
              : 'border-slate-700 text-slate-400',
          )}
        >
          {extras.map((extra) => (
            <p key={extra} className="mt-1">
              {extra}
            </p>
          ))}
        </div>
      )}
      <Button
        href={href}
        onClick={handleCheckoutClick}
        variant={featured ? 'solid' : 'outline'}
        color="white"
        className="mt-8"
        aria-label={`${cta || 'Jetzt starten'} - ${name}`}
        {...(download ? { download: true } : {})}
      >
        {cta || 'Jetzt starten'}
      </Button>
    </section>
  )
}

export function PricingCards() {
  // Freemius Checkout SDK laden
  useFreemiusCheckout()

  return (
    <section
      aria-label="Preise"
      className="bg-slate-900 py-20 sm:py-32"
    >
      <Container>
        <div className="md:text-center">
          <h2 className="font-display text-3xl tracking-tight text-white sm:text-4xl">
            Transparent und fair.
          </h2>
          <p className="mt-4 text-lg text-slate-400">
            Keine versteckten Kosten. Starten Sie kostenlos, upgraden Sie bei
            Bedarf.
          </p>
        </div>
        <div className="-mx-4 mt-16 grid max-w-2xl grid-cols-1 gap-y-10 sm:mx-auto lg:-mx-8 lg:max-w-none lg:grid-cols-3 xl:mx-0 xl:gap-x-8">
          <Plan
            name="Free"
            price="0 €"
            priceDetail="Für immer kostenlos"
            description="Perfekt zum Starten. Ohne Limits bei Stellenanzeigen."
            href="/recruiting-playbook.zip"
            cta="Herunterladen"
            download
            features={[
              'Unbegrenzte Stellenanzeigen',
              'Mehrstufiges Bewerbungsformular',
              'Google for Jobs Schema',
              'Dokument-Upload',
              'E-Mail-Benachrichtigungen',
            ]}
            extras={[
              'Ideal für Einzelunternehmer und kleine Teams',
              'Keine Kreditkarte nötig',
            ]}
          />
          <Plan
            featured
            name="Pro"
            price="149 €"
            priceDetail="Einmalig (1 Website)"
            description="Professionelles Bewerbermanagement mit KI-Features."
            href="#"
            cta="Pro kaufen"
            freemiusLicenses={1}
            features={[
              'Alles aus Free',
              'Kanban-Board (Drag & Drop)',
              'KI-Analyse: Match-Score & Job-Finder',
              '100 KI-Analysen/Monat inklusive',
              'Erweiterte E-Mail-Templates',
              'REST API & Webhooks',
              'Automatische DSGVO-Löschfristen',
              '1 Jahr Updates & Support',
            ]}
            extras={[
              'Wartungsverlängerung: 49 €/Jahr',
              '14-Tage-Geld-zurück-Garantie',
            ]}
          />
          <Plan
            name="Agentur"
            price="249 €"
            priceDetail="Einmalig (3 Websites)"
            description="Für Agenturen und Unternehmen mit mehreren Standorten."
            href="#"
            cta="Agentur kaufen"
            freemiusLicenses={3}
            features={[
              'Alles aus Pro',
              '3 Website-Lizenzen',
              'Zentrale Lizenzverwaltung',
              'Prioritäts-Support',
              'Erweiterte API-Limits',
              '1 Jahr Updates & Support',
            ]}
            extras={[
              'Wartungsverlängerung: 79 €/Jahr',
              '14-Tage-Geld-zurück-Garantie',
            ]}
          />
        </div>
      </Container>
    </section>
  )
}
