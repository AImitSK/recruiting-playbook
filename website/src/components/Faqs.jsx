import Image from 'next/image'

import { Container } from '@/components/Container'
import backgroundImage from '@/images/background-faqs.jpg'

const faqs = [
  [
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
  ],
  [
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
  ],
  [
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
  ],
]

export function Faqs() {
  return (
    <section
      id="faq"
      aria-labelledby="faq-title"
      className="relative overflow-hidden bg-slate-50 py-20 sm:py-32"
    >
      <Image
        className="absolute top-0 left-1/2 max-w-none translate-x-[-30%] -translate-y-1/4"
        src={backgroundImage}
        alt=""
        width={1558}
        height={946}
        unoptimized
      />
      <Container className="relative">
        <div className="mx-auto max-w-2xl lg:mx-0">
          <h2
            id="faq-title"
            className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl"
          >
            Häufig gestellte Fragen
          </h2>
          <p className="mt-4 text-lg tracking-tight text-slate-700">
            Sie finden keine Antwort? Schreiben Sie uns, wir helfen gerne
            weiter.
          </p>
        </div>
        <ul
          role="list"
          className="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-8 lg:max-w-none lg:grid-cols-3"
        >
          {faqs.map((column, columnIndex) => (
            <li key={columnIndex}>
              <ul role="list" className="flex flex-col gap-y-8">
                {column.map((faq, faqIndex) => (
                  <li key={faqIndex}>
                    <h3 className="font-display text-lg/7 text-slate-900">
                      {faq.question}
                    </h3>
                    <p className="mt-4 text-sm text-slate-700">{faq.answer}</p>
                  </li>
                ))}
              </ul>
            </li>
          ))}
        </ul>
      </Container>
    </section>
  )
}
