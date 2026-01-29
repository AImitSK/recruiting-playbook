import { Container } from '@/components/Container'

const employerBenefits = [
  {
    title: 'Vorqualifizierte Bewerber',
    description:
      'Bewerber, die sich über den Match-Score informiert haben, wissen ob sie passen. Das bedeutet weniger unpassende Bewerbungen.',
  },
  {
    title: 'Weniger Fehlbewerbungen',
    description:
      'Der Chancen-Check zeigt Bewerbern ehrlich, wo sie stehen. Nur motivierte Kandidaten bewerben sich.',
  },
  {
    title: 'Zeitsparende Vorauswahl',
    description:
      'Match-Scores und automatisch vorausgefüllte Formulare beschleunigen die Sichtung erheblich.',
  },
]

const applicantBenefits = [
  {
    title: 'Sofort wissen, ob es passt',
    description:
      'Kein Rätselraten mehr. Der Match-Score zeigt in Sekunden, wie gut Qualifikation und Stelle zusammenpassen.',
  },
  {
    title: 'Keine Bewerbung ins Leere',
    description:
      'Bewerber sehen vorher, ob sich eine Bewerbung lohnt. Das spart Zeit und Frustration auf beiden Seiten.',
  },
  {
    title: 'Konkrete Verbesserungstipps',
    description:
      'Die KI zeigt, welche Qualifikationen fehlen und wie Bewerber ihre Chancen verbessern können.',
  },
]

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

export function AiBenefits() {
  return (
    <section className="bg-slate-50 py-20 sm:py-32">
      <Container>
        <div className="mx-auto max-w-2xl md:text-center">
          <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
            Vorteile für beide Seiten
          </h2>
          <p className="mt-4 text-lg tracking-tight text-slate-700">
            Die KI-Analyse schafft Transparenz — für Arbeitgeber und Bewerber
            gleichermaßen.
          </p>
        </div>
        <div className="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-16 lg:max-w-none lg:grid-cols-2">
          <div>
            <h3 className="font-display text-xl tracking-tight text-slate-900">
              Für Arbeitgeber
            </h3>
            <ul role="list" className="mt-8 flex flex-col gap-y-8">
              {employerBenefits.map((benefit) => (
                <li key={benefit.title} className="flex gap-x-4">
                  <CheckIcon />
                  <div>
                    <p className="font-display text-lg text-slate-900">
                      {benefit.title}
                    </p>
                    <p className="mt-2 text-sm text-slate-700">
                      {benefit.description}
                    </p>
                  </div>
                </li>
              ))}
            </ul>
          </div>
          <div>
            <h3 className="font-display text-xl tracking-tight text-slate-900">
              Für Bewerber
            </h3>
            <ul role="list" className="mt-8 flex flex-col gap-y-8">
              {applicantBenefits.map((benefit) => (
                <li key={benefit.title} className="flex gap-x-4">
                  <CheckIcon />
                  <div>
                    <p className="font-display text-lg text-slate-900">
                      {benefit.title}
                    </p>
                    <p className="mt-2 text-sm text-slate-700">
                      {benefit.description}
                    </p>
                  </div>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </Container>
    </section>
  )
}
