import { Footer } from '@/components/Footer'
import { Header } from '@/components/Header'
import { Container } from '@/components/Container'
import { Button } from '@/components/Button'
import { SectionCta } from '@/components/SectionCta'

export const metadata = {
  title: 'Google for Jobs WordPress Plugin',
  description:
    'Stellenanzeigen automatisch in der Google-Jobsuche anzeigen. Kostenloses Schema-Markup mit Recruiting Playbook — ohne technisches Wissen.',
}

function CheckIcon() {
  return (
    <svg
      className="mt-0.5 h-5 w-5 flex-none text-[#2fac66]"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth={2}
        d="M5 13l4 4L19 7"
      />
    </svg>
  )
}

function XIcon() {
  return (
    <svg
      className="mt-0.5 h-5 w-5 flex-none text-red-400"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth={2}
        d="M6 18L18 6M6 6l12 12"
      />
    </svg>
  )
}

export default function GoogleForJobsPage() {
  return (
    <>
      <Header />
      <main>
        {/* Hero */}
        <Container className="pt-20 pb-16 lg:pt-32">
          <div className="mx-auto max-w-3xl text-center">
            <p className="text-sm font-semibold uppercase tracking-wide text-[#1d71b8]">
              Kostenlos in der Free-Version
            </p>
            <h1 className="mt-4 font-display text-4xl font-medium tracking-tight text-slate-900 sm:text-6xl">
              Ihre Stellen automatisch in der{' '}
              <span className="text-[#1d71b8]">Google-Jobsuche</span>
            </h1>
            <p className="mx-auto mt-6 max-w-2xl text-lg text-slate-700">
              Recruiting Playbook generiert automatisch das korrekte
              Schema-Markup (JobPosting), damit Ihre Stellenanzeigen in
              Google for Jobs erscheinen. Ohne Zusatzkosten, ohne technisches
              Wissen.
            </p>
            <div className="mt-10 flex justify-center gap-x-6">
              <Button href="/recruiting-playbook.zip" color="blue" download>
                Kostenlos herunterladen
              </Button>
              <Button href="/docs/google-for-jobs" variant="outline">
                Dokumentation lesen
              </Button>
            </div>
          </div>
        </Container>

        {/* Was ist Google for Jobs */}
        <section className="bg-slate-50 py-20 sm:py-32">
          <Container>
            <div className="mx-auto max-w-3xl">
              <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
                Was ist Google for Jobs?
              </h2>
              <p className="mt-6 text-lg text-slate-700">
                Google for Jobs ist ein spezieller Bereich in der
                Google-Suche, der Stellenanzeigen prominent oberhalb der
                normalen Suchergebnisse anzeigt. Wer nach "Pflegekraft
                Berlin" oder "Entwickler München" sucht, sieht zuerst die
                Google-Jobbox mit allen relevanten Stellen.
              </p>
              <div className="mt-10 grid gap-6 sm:grid-cols-3">
                {[
                  { value: '50%', label: 'aller Jobsuchen starten bei Google' },
                  { value: '2-5x', label: 'mehr Sichtbarkeit als klassische Stellenbörsen' },
                  { value: '0 €', label: 'Kosten für die Platzierung' },
                ].map((stat) => (
                  <div
                    key={stat.label}
                    className="rounded-2xl bg-white p-6 text-center ring-1 ring-slate-200"
                  >
                    <p className="font-display text-3xl font-light text-[#1d71b8]">
                      {stat.value}
                    </p>
                    <p className="mt-2 text-sm text-slate-600">{stat.label}</p>
                  </div>
                ))}
              </div>
            </div>
          </Container>
        </section>

        {/* So funktioniert es */}
        <section className="py-20 sm:py-32">
          <Container>
            <div className="mx-auto max-w-3xl">
              <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
                So funktioniert es
              </h2>
              <div className="mt-10 space-y-8">
                {[
                  {
                    step: '1',
                    title: 'Stelle erstellen',
                    text: 'Erstellen Sie eine Stellenanzeige im WordPress-Editor. Titel, Beschreibung, Standort und Gehalt ausfüllen.',
                  },
                  {
                    step: '2',
                    title: 'Schema wird automatisch generiert',
                    text: 'Recruiting Playbook erzeugt automatisch das korrekte JSON-LD Schema-Markup (JobPosting) mit allen Pflichtfeldern.',
                  },
                  {
                    step: '3',
                    title: 'Google indexiert Ihre Stelle',
                    text: 'Googles Crawler erkennt das Markup und zeigt Ihre Stelle in der Jobsuche an. Typisch innerhalb weniger Tage.',
                  },
                ].map((item) => (
                  <div key={item.step} className="flex gap-x-6">
                    <div className="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-[#1d71b8] text-lg font-bold text-white">
                      {item.step}
                    </div>
                    <div>
                      <h3 className="font-display text-lg text-slate-900">
                        {item.title}
                      </h3>
                      <p className="mt-2 text-slate-700">{item.text}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </Container>
        </section>

        {/* Schema-Felder */}
        <section className="bg-slate-50 py-20 sm:py-32">
          <Container>
            <div className="mx-auto max-w-3xl">
              <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
                Alle Schema-Felder automatisch
              </h2>
              <p className="mt-4 text-lg text-slate-700">
                Recruiting Playbook mappt Ihre Stellendaten automatisch auf
                alle von Google geforderten und empfohlenen Felder:
              </p>
              <ul className="mt-8 space-y-3">
                {[
                  'Stellentitel und Beschreibung',
                  'Unternehmen (Name, Logo, URL)',
                  'Standort und Remote-Option',
                  'Gehaltsangaben (Spanne und Zeitraum)',
                  'Beschäftigungsart (Vollzeit, Teilzeit, Minijob etc.)',
                  'Veröffentlichungs- und Ablaufdatum',
                  'Branche und Qualifikationen',
                ].map((item) => (
                  <li key={item} className="flex gap-x-3 text-slate-700">
                    <CheckIcon />
                    {item}
                  </li>
                ))}
              </ul>
            </div>
          </Container>
        </section>

        {/* Vergleich mit Wettbewerb */}
        <section className="py-20 sm:py-32">
          <Container>
            <div className="mx-auto max-w-3xl">
              <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
                Kostenlos statt 799 €/Jahr
              </h2>
              <p className="mt-4 text-lg text-slate-700">
                Bei anderen WordPress-Plugins ist Google for Jobs ein
                kostenpflichtiges Add-on. Bei Recruiting Playbook ist es
                von Anfang an kostenlos enthalten.
              </p>
              <div className="mt-10 overflow-x-auto">
                <table className="w-full text-left text-sm">
                  <thead>
                    <tr className="border-b border-slate-200">
                      <th className="pb-3 pr-6 font-semibold text-slate-900">Feature</th>
                      <th className="pb-3 pr-6 text-center font-semibold text-[#1d71b8]">Recruiting Playbook</th>
                      <th className="pb-3 text-center font-semibold text-slate-500">Wettbewerber</th>
                    </tr>
                  </thead>
                  <tbody className="text-slate-700">
                    <tr className="border-b border-slate-100">
                      <td className="py-3 pr-6">Google for Jobs Schema</td>
                      <td className="py-3 pr-6 text-center text-[#2fac66] font-semibold">Kostenlos</td>
                      <td className="py-3 text-center">199-799 €/Jahr</td>
                    </tr>
                    <tr className="border-b border-slate-100">
                      <td className="py-3 pr-6">Automatisches Markup</td>
                      <td className="py-3 pr-6 text-center"><CheckIcon /></td>
                      <td className="py-3 text-center">Teilweise</td>
                    </tr>
                    <tr className="border-b border-slate-100">
                      <td className="py-3 pr-6">Gehalts-Schema</td>
                      <td className="py-3 pr-6 text-center"><CheckIcon /></td>
                      <td className="py-3 text-center"><XIcon /></td>
                    </tr>
                    <tr className="border-b border-slate-100">
                      <td className="py-3 pr-6">Remote-Option</td>
                      <td className="py-3 pr-6 text-center"><CheckIcon /></td>
                      <td className="py-3 text-center"><XIcon /></td>
                    </tr>
                    <tr className="border-b border-slate-100">
                      <td className="py-3 pr-6">Schema-Validierung</td>
                      <td className="py-3 pr-6 text-center"><CheckIcon /></td>
                      <td className="py-3 text-center">Teilweise</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </Container>
        </section>

        <SectionCta
          headline="Starten Sie jetzt mit Google for Jobs"
          text="Installieren Sie Recruiting Playbook, erstellen Sie eine Stelle und erscheinen Sie in der Google-Jobsuche. Kostenlos."
          cta="Kostenlos herunterladen"
        />
      </main>
      <Footer />
    </>
  )
}
