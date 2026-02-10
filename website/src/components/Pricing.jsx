import clsx from 'clsx'

import { Button } from '@/components/Button'
import { Container } from '@/components/Container'

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

function Plan({ name, price, priceDetail, description, href, features, featured = false, cta, download = false }) {
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
          <p className={clsx('mt-1 text-sm', featured ? 'text-blue-200' : 'text-slate-400')}>
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
            <CheckIcon className={featured ? 'text-white' : 'text-slate-400'} />
            <span className="ml-4">{feature}</span>
          </li>
        ))}
      </ul>
      <Button
        href={href}
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

export function Pricing() {
  return (
    <section
      id="pricing"
      aria-label="Preise"
      className="bg-slate-900 py-20 sm:py-32"
    >
      <Container>
        <div className="md:text-center">
          <h2 className="font-display text-3xl tracking-tight text-white sm:text-4xl">
            Transparent und fair.
          </h2>
          <p className="mt-4 text-lg text-slate-400">
            Starten Sie kostenlos mit unbegrenzten Stellen. Upgraden Sie, wenn
            Sie professionelles Bewerbermanagement brauchen.
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
              'DSGVO-Consent-Checkbox',
            ]}
          />
          <Plan
            featured
            name="Pro"
            price="149 €"
            priceDetail="Einmalig. Lifetime-Lizenz (1 Website)"
            description="Professionelles Bewerbermanagement für Teams und Agenturen."
            href="#"
            cta="Pro kaufen"
            features={[
              'Alles aus Free',
              'Kanban-Board (Drag & Drop)',
              'Erweiterte E-Mail-Templates',
              'REST API & Webhooks',
              'Automatische DSGVO-Löschfristen',
              'Bewerber-Datenexport',
              '1 Jahr Updates & Support',
            ]}
          />
          <Plan
            name="Agentur"
            price="249 €"
            priceDetail="Einmalig. Lifetime-Lizenz (3 Websites)"
            description="Für Agenturen und Unternehmen mit mehreren Standorten."
            href="#"
            cta="Agentur kaufen"
            features={[
              'Alles aus Pro',
              '3 Website-Lizenzen',
              'Zentrale Lizenzverwaltung',
              'Prioritäts-Support',
              'Erweiterte API-Limits',
              '1 Jahr Updates & Support',
            ]}
          />
        </div>
      </Container>
    </section>
  )
}
