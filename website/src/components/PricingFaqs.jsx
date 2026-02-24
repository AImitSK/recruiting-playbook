'use client'

import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/react'

import { Container } from '@/components/Container'

const faqs = [
  {
    question: 'Was bedeutet "Einmalig"?',
    answer:
      'Sie zahlen einmalig und erhalten die aktuelle Version dauerhaft plus 12 Monate Updates und Support. Danach funktioniert Ihr Plugin weiter \u2014 Sie erhalten nur keine neuen Updates mehr. Optional k\u00f6nnen Sie die Wartung verl\u00e4ngern.',
  },
  {
    question: 'Was ist der Unterschied zwischen Pro und Agentur?',
    answer:
      'Pro ist f\u00fcr eine einzelne Website. Die Agentur-Lizenz enth\u00e4lt alle Pro-Features f\u00fcr bis zu 3 Websites, eine zentrale Lizenzverwaltung und Priorit\u00e4ts-Support.',
  },
  {
    question: 'Kann ich die Domain meiner Lizenz wechseln?',
    answer:
      'Ja. Sie k\u00f6nnen eine Lizenz im Kundenportal deaktivieren und auf einer neuen Domain aktivieren. Bei der Agentur-Lizenz k\u00f6nnen Sie bis zu 3 Domains gleichzeitig nutzen.',
  },
  {
    question: 'Gibt es eine Testversion von Pro?',
    answer:
      'Wir bieten eine 14-Tage-Geld-zur\u00fcck-Garantie. Testen Sie Pro und wenn es nicht passt, erhalten Sie Ihr Geld zur\u00fcck \u2014 ohne Fragen.',
  },
  {
    question: 'Was passiert nach den 12 Monaten Updates?',
    answer:
      'Ihr Plugin funktioniert weiterhin ohne Einschr\u00e4nkungen. Sie erhalten nur keine neuen Features und Sicherheitsupdates mehr. Die Wartungsverl\u00e4ngerung gibt Ihnen wieder Zugang zu allen Updates.',
  },
  {
    question: 'Wie funktionieren die KI-Funktionen?',
    answer:
      'Die KI-Analyse ist in der Pro- und Agentur-Lizenz enthalten. Sie erhalten 100 KI-Analysen pro Monat (Match-Score, Job-Finder, Chancen-Check). Zus\u00e4tzliche Analysen k\u00f6nnen bei Bedarf nachgebucht werden.',
  },
  {
    question: 'Welche Zahlungsmethoden werden akzeptiert?',
    answer:
      'Wir akzeptieren Kreditkarte, PayPal und SEPA-Lastschrift. Pro und Agentur sind Einmalzahlungen.',
  },
  {
    question: 'Gibt es Rabatte f\u00fcr gemeinn\u00fctzige Organisationen?',
    answer:
      'Ja, gemeinn\u00fctzige Organisationen und Bildungseinrichtungen erhalten 30% Rabatt auf Pro- und Agentur-Lizenzen. Kontaktieren Sie uns mit einem Nachweis.',
  },
]

function ChevronIcon({ className }) {
  return (
    <svg
      className={className}
      fill="none"
      viewBox="0 0 24 24"
      strokeWidth={2}
      stroke="currentColor"
    >
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="m19.5 8.25-7.5 7.5-7.5-7.5"
      />
    </svg>
  )
}

export function PricingFaqs() {
  return (
    <section className="bg-slate-50 py-20 sm:py-32">
      <Container>
        <div className="mx-auto max-w-2xl text-center">
          <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
            H\u00e4ufige Fragen zu Preisen & Lizenzen
          </h2>
          <p className="mt-4 text-lg tracking-tight text-slate-700">
            Alles was Sie \u00fcber unsere Preismodelle wissen m\u00fcssen.
          </p>
        </div>
        <div className="mx-auto mt-16 max-w-3xl divide-y divide-slate-200">
          {faqs.map((faq) => (
            <Disclosure as="div" key={faq.question} className="py-4">
              {({ open }) => (
                <>
                  <DisclosureButton className="group flex w-full items-center justify-between text-left">
                    <span className="font-display text-lg/7 text-slate-900 group-hover:text-[#1d71b8]">
                      {faq.question}
                    </span>
                    <ChevronIcon
                      className={`ml-4 h-5 w-5 flex-none text-slate-400 transition-transform duration-200 ${open ? 'rotate-180' : ''}`}
                    />
                  </DisclosureButton>
                  <DisclosurePanel className="mt-3 pr-12 text-sm/6 text-slate-700">
                    {faq.answer}
                  </DisclosurePanel>
                </>
              )}
            </Disclosure>
          ))}
        </div>
      </Container>
    </section>
  )
}
