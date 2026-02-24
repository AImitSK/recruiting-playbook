'use client'

import { Fragment } from 'react'
import { CheckIcon, MinusIcon } from '@heroicons/react/16/solid'
import { Tab, TabGroup, TabList, TabPanel, TabPanels } from '@headlessui/react'

import { Container } from '@/components/Container'
import { Button } from '@/components/Button'

const tiers = [
  {
    name: 'Free',
    price: '0 €',
    description: 'Alles Wichtige. Kostenlos und ohne Limits.',
    cta: 'Kostenlos herunterladen',
    href: '/recruiting-playbook.zip',
    featured: false,
  },
  {
    name: 'Pro',
    price: '149 €',
    description: 'Bewerbermanagement, KI-Analyse und Premium-Support.',
    cta: 'Pro freischalten',
    href: '/pricing',
    featured: true,
  },
]

const sections = [
  {
    name: 'Stellenanzeigen',
    features: [
      { name: 'Unbegrenzte Stellenanzeigen', tiers: { Free: true, Pro: true } },
      { name: 'Google for Jobs Schema', tiers: { Free: true, Pro: true } },
      { name: 'WordPress Shortcodes & Blöcke', tiers: { Free: true, Pro: true } },
      { name: 'KI-Stellentexte generieren', tiers: { Free: false, Pro: true } },
      { name: 'SEO-Optimierung für Stellenanzeigen', tiers: { Free: false, Pro: true } },
    ],
  },
  {
    name: 'Bewerbungen',
    features: [
      { name: 'Mehrstufiges Bewerbungsformular', tiers: { Free: true, Pro: true } },
      { name: 'Dokument-Upload (Lebenslauf, Zeugnisse)', tiers: { Free: true, Pro: true } },
      { name: 'E-Mail-Benachrichtigungen', tiers: { Free: true, Pro: true } },
      { name: 'Kanban-Board (Drag & Drop)', tiers: { Free: false, Pro: true } },
      { name: 'Erweiterte E-Mail-Templates', tiers: { Free: false, Pro: true } },
      { name: 'Status-Workflow Anpassung', tiers: { Free: false, Pro: true } },
    ],
  },
  {
    name: 'KI & Analyse',
    features: [
      { name: 'Job-Match Score (0–100%)', tiers: { Free: false, Pro: true } },
      { name: 'Job-Finder: „Welche Jobs passen zu mir?"', tiers: { Free: false, Pro: true } },
      { name: 'Chancen-Check für Bewerber', tiers: { Free: false, Pro: true } },
      { name: 'Stärken/Schwächen-Analyse', tiers: { Free: false, Pro: true } },
      { name: '100 KI-Analysen / Monat', tiers: { Free: false, Pro: true } },
    ],
  },
  {
    name: 'Datenschutz & Integration',
    features: [
      { name: 'DSGVO-Consent & Datenschutz', tiers: { Free: true, Pro: true } },
      { name: 'CSV-Export', tiers: { Free: 'Basis', Pro: 'Erweitert' } },
      { name: 'Automatische Löschfristen (DSGVO)', tiers: { Free: false, Pro: true } },
      { name: 'REST API Zugang', tiers: { Free: false, Pro: true } },
      { name: 'Webhook-System', tiers: { Free: false, Pro: true } },
      { name: 'Premium Support (1 Jahr)', tiers: { Free: false, Pro: true } },
    ],
  },
]

function classNames(...classes) {
  return classes.filter(Boolean).join(' ')
}

function TierValue({ value }) {
  if (value === true) {
    return <CheckIcon aria-hidden="true" className="inline-block size-4 text-[#1d71b8]" />
  }
  if (value === false || value === undefined) {
    return <MinusIcon aria-hidden="true" className="inline-block size-4 text-gray-400" />
  }
  return <span className="text-sm/6 text-gray-950">{value}</span>
}

export function SecondaryFeatures() {
  return (
    <section
      id="tiers"
      aria-label="Feature-Vergleich"
      className="pt-20 pb-14 sm:pt-32 sm:pb-20 lg:pb-32"
    >
      <Container>
        <div className="mx-auto max-w-2xl text-center">
          <h2 className="text-base/7 font-semibold text-[#1d71b8]">
            Preise
          </h2>
          <p className="mt-2 text-4xl font-semibold tracking-tight text-balance text-gray-900 sm:text-5xl">
            Kostenlos starten. Bei Bedarf upgraden.
          </p>
          <p className="mt-6 text-lg/8 text-gray-600">
            Free für unbegrenzte Stellenanzeigen. Pro für Bewerbermanagement,
            KI-Analyse und Premium-Support. Einmalpreis, kein Abo.
          </p>
        </div>

        {/* Mobile: Tabs */}
        <div className="mx-auto mt-12 max-w-md lg:hidden">
          <TabGroup>
            <TabList className="grid grid-cols-2">
              {tiers.map((tier) => (
                <Tab
                  key={tier.name}
                  className={classNames(
                    'border-b-2 py-4 text-sm/6 font-semibold focus:outline-none data-[selected]:border-[#1d71b8] data-[selected]:text-[#1d71b8]',
                    'border-transparent text-gray-500 hover:text-gray-700',
                  )}
                >
                  {tier.name}
                </Tab>
              ))}
            </TabList>
            <TabPanels className="mt-4">
              {tiers.map((tier) => (
                <TabPanel key={tier.name}>
                  <div className="rounded-3xl p-8 ring-1 ring-gray-900/10">
                    <p className="text-sm/6 font-semibold text-gray-900">
                      {tier.name}
                    </p>
                    <p className="mt-2 flex items-baseline gap-x-1">
                      <span className="text-4xl font-semibold tracking-tight text-gray-900">
                        {tier.price}
                      </span>
                      {tier.name === 'Pro' && (
                        <span className="text-sm/6 text-gray-500">einmalig</span>
                      )}
                    </p>
                    <p className="mt-4 text-sm/6 text-gray-600">
                      {tier.description}
                    </p>
                    <Button
                      href={tier.href}
                      color={tier.featured ? 'blue' : 'slate'}
                      className="mt-6 w-full"
                      {...(tier.name === 'Free' ? { download: true } : {})}
                    >
                      {tier.cta}
                    </Button>
                    <ul role="list" className="mt-8 space-y-4">
                      {sections.map((section) => (
                        <Fragment key={section.name}>
                          <li>
                            <p className="text-sm/6 font-semibold text-gray-950">
                              {section.name}
                            </p>
                          </li>
                          {section.features.map((feature) => (
                            <li key={feature.name} className="flex items-center gap-x-3">
                              <TierValue value={feature.tiers[tier.name]} />
                              <span
                                className={classNames(
                                  feature.tiers[tier.name]
                                    ? 'text-gray-600'
                                    : 'text-gray-400',
                                  'text-sm/6',
                                )}
                              >
                                {feature.name}
                              </span>
                            </li>
                          ))}
                        </Fragment>
                      ))}
                    </ul>
                  </div>
                </TabPanel>
              ))}
            </TabPanels>
          </TabGroup>
        </div>

        {/* Desktop: Table */}
        <div className="isolate mx-auto mt-20 hidden max-w-2xl lg:block lg:max-w-none">
          <table className="w-full table-fixed text-left">
            <caption className="sr-only">Feature-Vergleich</caption>
            <colgroup>
              <col className="w-2/4" />
              <col className="w-1/4" />
              <col className="w-1/4" />
            </colgroup>
            <thead>
              <tr>
                <td className="p-0" />
                {tiers.map((tier) => (
                  <th
                    key={tier.name}
                    scope="col"
                    className="p-0"
                  >
                    <div
                      className={classNames(
                        tier.featured
                          ? 'rounded-t-2xl ring-1 ring-[#1d71b8]'
                          : 'rounded-t-2xl ring-1 ring-gray-200',
                        'px-6 pt-6 pb-4',
                      )}
                    >
                      <p className="text-sm/6 font-semibold text-gray-900">
                        {tier.name}
                      </p>
                      <p className="mt-1 flex items-baseline gap-x-1">
                        <span className="text-4xl font-semibold tracking-tight text-gray-900">
                          {tier.price}
                        </span>
                        {tier.name === 'Pro' && (
                          <span className="text-sm/6 text-gray-500">
                            einmalig
                          </span>
                        )}
                      </p>
                      <p className="mt-3 text-sm/6 text-gray-600">
                        {tier.description}
                      </p>
                      <Button
                        href={tier.href}
                        color={tier.featured ? 'blue' : 'slate'}
                        className="mt-6 w-full"
                        {...(tier.name === 'Free' ? { download: true } : {})}
                      >
                        {tier.cta}
                      </Button>
                    </div>
                  </th>
                ))}
              </tr>
            </thead>
            {sections.map((section) => (
              <tbody key={section.name} className="group">
                <tr>
                  <th
                    scope="colgroup"
                    colSpan={3}
                    className="px-0 pb-0 pt-10 group-first-of-type:pt-6"
                  >
                    <p className="-mx-0 rounded-lg bg-gray-50 px-6 py-3 text-sm/6 font-semibold text-gray-950">
                      {section.name}
                    </p>
                  </th>
                </tr>
                {section.features.map((feature) => (
                  <tr key={feature.name}>
                    <th
                      scope="row"
                      className="px-6 py-4 text-sm/6 font-normal text-gray-600"
                    >
                      {feature.name}
                    </th>
                    {tiers.map((tier) => (
                      <td
                        key={tier.name}
                        className={classNames(
                          tier.featured
                            ? 'ring-1 ring-[#1d71b8]'
                            : 'ring-1 ring-gray-200',
                          'px-6 py-4 text-center text-sm/6',
                        )}
                      >
                        <TierValue value={feature.tiers[tier.name]} />
                        <span className="sr-only">{tier.name}</span>
                      </td>
                    ))}
                  </tr>
                ))}
              </tbody>
            ))}
            <tfoot>
              <tr>
                <th scope="row" className="p-0">
                  <span className="sr-only">Auswählen</span>
                </th>
                {tiers.map((tier) => (
                  <td
                    key={tier.name}
                    className={classNames(
                      tier.featured
                        ? 'rounded-b-2xl ring-1 ring-[#1d71b8]'
                        : 'rounded-b-2xl ring-1 ring-gray-200',
                      'px-6 pt-4 pb-6',
                    )}
                  >
                    <Button
                      href={tier.href}
                      color={tier.featured ? 'blue' : 'slate'}
                      className="w-full"
                      {...(tier.name === 'Free' ? { download: true } : {})}
                    >
                      {tier.cta}
                    </Button>
                  </td>
                ))}
              </tr>
            </tfoot>
          </table>
        </div>
      </Container>
    </section>
  )
}
