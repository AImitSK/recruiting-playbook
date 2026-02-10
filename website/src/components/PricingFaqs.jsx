import { Container } from '@/components/Container'

const faqs = [
  [
    {
      question: 'Was bedeutet "Lifetime-Lizenz"?',
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
  ],
  [
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
      question: 'Brauche ich Pro für das KI-Addon?',
      answer:
        'Ja, das KI-Addon setzt eine aktive Pro- oder Agentur-Lizenz voraus. Die KI-Analyse nutzt die erweiterten Datenstrukturen der Pro-Version. Details finden Sie auf der KI-Addon-Seite.',
    },
  ],
  [
    {
      question: 'Wie funktioniert das KI-Addon?',
      answer:
        'Das KI-Addon ist ein separates Abonnement ab 19 €/Monat (oder 179 €/Jahr). Sie erhalten 100 Analysen pro Monat. Extra-Pakete können jederzeit nachgebucht werden.',
    },
    {
      question: 'Welche Zahlungsmethoden werden akzeptiert?',
      answer:
        'Wir akzeptieren Kreditkarte, PayPal und SEPA-Lastschrift. Pro und Agentur sind Einmalzahlungen, das KI-Addon wird monatlich oder jährlich abgerechnet.',
    },
    {
      question: 'Gibt es Rabatte für gemeinnützige Organisationen?',
      answer:
        'Ja, gemeinnützige Organisationen und Bildungseinrichtungen erhalten 30% Rabatt auf Pro- und Agentur-Lizenzen. Kontaktieren Sie uns mit einem Nachweis.',
    },
  ],
]

export function PricingFaqs() {
  return (
    <section className="bg-slate-50 py-20 sm:py-32">
      <Container>
        <div className="mx-auto max-w-2xl lg:mx-0">
          <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
            Häufige Fragen zu Preisen & Lizenzen
          </h2>
          <p className="mt-4 text-lg tracking-tight text-slate-700">
            Alles was Sie über unsere Preismodelle wissen müssen.
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
