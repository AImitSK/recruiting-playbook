'use client'

import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/react'

import { Container } from '@/components/Container'

const faqs = [
  {
    question: 'Was bedeutet "Einmalig"?',
    answer:
      'Sie zahlen einmalig und erhalten die aktuelle Version dauerhaft plus 12 Monate Updates und Support. Danach funktioniert Ihr Plugin weiter — Sie erhalten nur keine neuen Updates mehr. Optional können Sie die Wartung verlängern.',
  },
  {
    question: 'Was ist der Unterschied zwischen Pro und Agentur?',
    answer:
      'Pro ist für eine einzelne Website. Die Agentur-Lizenz enthält alle Pro-Features für bis zu 3 Websites, eine zentrale Lizenzverwaltung und Prioritäts-Support.',
  },
  {
    question: 'Kann ich die Domain meiner Lizenz wechseln?',
    answer:
      'Ja. Sie können eine Lizenz im Kundenportal deaktivieren und auf einer neuen Domain aktivieren. Bei der Agentur-Lizenz können Sie bis zu 3 Domains gleichzeitig nutzen.',
  },
  {
    question: 'Gibt es eine Testversion von Pro?',
    answer:
      'Wir bieten eine 14-Tage-Geld-zurück-Garantie. Testen Sie Pro und wenn es nicht passt, erhalten Sie Ihr Geld zurück — ohne Fragen.',
  },
  {
    question: 'Was passiert nach den 12 Monaten Updates?',
    answer:
      'Ihr Plugin funktioniert weiterhin ohne Einschränkungen. Sie erhalten nur keine neuen Features und Sicherheitsupdates mehr. Die Wartungsverlängerung gibt Ihnen wieder Zugang zu allen Updates.',
  },
  {
    question: 'Wie funktionieren die KI-Funktionen?',
    answer:
      'Die KI-Analyse ist in der Pro- und Agentur-Lizenz enthalten. Sie erhalten 100 KI-Analysen pro Monat (Match-Score, Job-Finder, Chancen-Check). Zusätzliche Analysen können bei Bedarf nachgebucht werden.',
  },
  {
    question: 'Welche Zahlungsmethoden werden akzeptiert?',
    answer:
      'Wir akzeptieren Kreditkarte, PayPal und SEPA-Lastschrift. Pro und Agentur sind Einmalzahlungen.',
  },
  {
    question: 'Gibt es Rabatte für gemeinnützige Organisationen?',
    answer:
      'Ja, gemeinnützige Organisationen und Bildungseinrichtungen erhalten 30% Rabatt auf Pro- und Agentur-Lizenzen. Kontaktieren Sie uns mit einem Nachweis.',
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
            Häufige Fragen zu Preisen & Lizenzen
          </h2>
          <p className="mt-4 text-lg tracking-tight text-slate-700">
            Alles was Sie über unsere Preismodelle wissen müssen.
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
