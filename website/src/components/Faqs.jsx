'use client'

import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/react'
import { MinusSmallIcon, PlusSmallIcon } from '@heroicons/react/24/outline'

import { Container } from '@/components/Container'

const faqs = [
  {
    question: 'Ist das Plugin wirklich kostenlos?',
    answer:
      'Ja. Die Free-Version hat keine Limits bei der Anzahl der Stellenanzeigen und enthält alle Kern-Features. Pro und KI-Addon sind optionale Upgrades.',
  },
  {
    question: 'Was bedeutet "Lifetime-Lizenz"?',
    answer:
      'Sie zahlen einmalig 149 € und erhalten die aktuelle Version dauerhaft plus 12 Monate Updates und Support. Danach optional 49 €/Jahr für weitere Updates.',
  },
  {
    question: 'Ist das Plugin DSGVO-konform?',
    answer:
      'Ja. Eingebaute Consent-Checkboxen, konfigurierbare Löschfristen, Datenexport pro Bewerber und Soft-Delete Anonymisierung sind standardmäßig enthalten.',
  },
  {
    question: 'Brauche ich Pro für das KI-Addon?',
    answer:
      'Ja, das KI-Addon setzt eine aktive Pro-Lizenz voraus. Die KI-Analyse nutzt die erweiterten Datenstrukturen der Pro-Version.',
  },
  {
    question: 'Funktioniert das Plugin mit meinem Theme?',
    answer:
      'Ja. Recruiting Playbook verwendet WordPress Shortcodes und passt sich Ihrem Theme an. Templates können bei Bedarf individuell überschrieben werden.',
  },
  {
    question: 'Kann ich die Domain meiner Lizenz wechseln?',
    answer:
      'Ja. Sie können eine Lizenz im Kundenportal deaktivieren und auf einer neuen Domain aktivieren. Bei der Agentur-Lizenz bis zu 3 gleichzeitig.',
  },
  {
    question: 'Welche KI wird für die Analyse verwendet?',
    answer:
      'Wir nutzen Claude von Anthropic. Es werden keine personenbezogenen Daten an die KI übermittelt, sondern nur Stellenanforderungen und anonymisierte Qualifikationen.',
  },
  {
    question: 'Gibt es eine REST API?',
    answer:
      'Ja, die Pro-Version bietet eine vollständige REST API unter dem Namespace recruiting/v1 mit Endpoints für Jobs, Bewerbungen, Berichte und Webhooks.',
  },
  {
    question: 'Wie bekomme ich Support?',
    answer:
      'Free-Nutzer erhalten Support über GitHub Issues und die Dokumentation. Pro-Kunden haben 1 Jahr E-Mail-Support inklusive.',
  },
]

export function Faqs() {
  return (
    <section
      id="faq"
      aria-labelledby="faq-title"
      className="bg-white py-24 sm:py-32"
    >
      <Container>
        <div className="mx-auto max-w-4xl">
          <h2
            id="faq-title"
            className="text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl"
          >
            Häufig gestellte Fragen
          </h2>
          <p className="mt-4 text-lg/8 text-gray-600">
            Sie finden keine Antwort? Schreiben Sie uns, wir helfen gerne
            weiter.
          </p>
          <dl className="mt-16 divide-y divide-gray-900/10">
            {faqs.map((faq) => (
              <Disclosure key={faq.question} as="div" className="py-6 first:pt-0 last:pb-0">
                <dt>
                  <DisclosureButton className="group flex w-full items-start justify-between text-left text-gray-900">
                    <span className="text-base/7 font-semibold">{faq.question}</span>
                    <span className="ml-6 flex h-7 items-center">
                      <PlusSmallIcon aria-hidden="true" className="size-6 group-data-[open]:hidden" />
                      <MinusSmallIcon aria-hidden="true" className="size-6 [.group:not([data-open])_&]:hidden" />
                    </span>
                  </DisclosureButton>
                </dt>
                <DisclosurePanel as="dd" className="mt-2 pr-12">
                  <p className="text-base/7 text-gray-600">{faq.answer}</p>
                </DisclosurePanel>
              </Disclosure>
            ))}
          </dl>
        </div>
      </Container>
    </section>
  )
}
