import { Container } from '@/components/Container'

const faqs = [
  [
    {
      question: 'Was bedeutet "Lifetime-Lizenz"?',
      answer:
        'Sie zahlen einmalig 149 € und erhalten die aktuelle Version dauerhaft plus 12 Monate Updates und Support. Danach funktioniert Ihr Plugin weiter — Sie erhalten nur keine neuen Updates mehr. Optional: 49 €/Jahr für weitere Updates.',
    },
    {
      question: 'Brauche ich Pro für das KI-Addon?',
      answer:
        'Ja, das KI-Addon setzt eine aktive Pro-Lizenz voraus. Die KI-Analyse nutzt die erweiterten Datenstrukturen der Pro-Version.',
    },
    {
      question: 'Kann ich die Domain meiner Lizenz wechseln?',
      answer:
        'Ja. Sie können eine Lizenz im Kundenportal deaktivieren und auf einer neuen Domain aktivieren. Bei der Agency-Lizenz bis zu 5 gleichzeitig.',
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
        'Ihr Plugin funktioniert weiterhin ohne Einschränkungen. Sie erhalten nur keine neuen Features und Sicherheitsupdates mehr. Die Wartungsverlängerung für 49 €/Jahr gibt Ihnen Zugang zu allen Updates.',
    },
    {
      question: 'Wie funktioniert die Agency-Lizenz?',
      answer:
        'Die Agency-Lizenz für 249 € erlaubt die Installation auf bis zu 5 Websites. Ideal für Agenturen, die das Plugin für mehrere Kunden einsetzen.',
    },
  ],
  [
    {
      question: 'Was passiert wenn meine KI-Analysen aufgebraucht sind?',
      answer:
        'Sie werden bei 80% Verbrauch benachrichtigt. Danach können Sie jederzeit Extra-Pakete (9 € / 50 Analysen) nachbuchen. Ungenutzte Extra-Analysen verfallen nicht.',
    },
    {
      question: 'Welche Zahlungsmethoden werden akzeptiert?',
      answer:
        'Wir akzeptieren Kreditkarte, PayPal und SEPA-Lastschrift. Für das KI-Addon wird monatlich oder jährlich abgerechnet.',
    },
    {
      question: 'Gibt es Rabatte für gemeinnützige Organisationen?',
      answer:
        'Ja, gemeinnützige Organisationen und Bildungseinrichtungen erhalten 30% Rabatt auf Pro und Agency-Lizenzen. Kontaktieren Sie uns mit einem Nachweis.',
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
