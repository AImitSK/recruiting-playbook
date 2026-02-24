import { Container } from '@/components/Container'

const badges = [
  { label: '14-Tage-Geld-zurück', icon: 'refresh' },
  { label: 'Keine versteckten Kosten', icon: 'check' },
  { label: 'Sofort einsatzbereit', icon: 'bolt' },
]

function BadgeIcon({ type }) {
  if (type === 'shield') {
    return (
      <svg className="h-4 w-4 text-[#2fac66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
      </svg>
    )
  }
  if (type === 'refresh') {
    return (
      <svg className="h-4 w-4 text-[#2fac66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
      </svg>
    )
  }
  if (type === 'bolt') {
    return (
      <svg className="h-4 w-4 text-[#2fac66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
      </svg>
    )
  }
  return (
    <svg className="h-4 w-4 text-[#2fac66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  )
}

export function PricingHero() {
  return (
    <Container className="pt-20 pb-16 text-center lg:pt-32">
      <h1 className="mx-auto max-w-4xl font-display text-5xl font-medium tracking-tight text-slate-900 sm:text-7xl">
        Einfache, faire{' '}
        <span className="text-[#1d71b8]">Preise</span>
      </h1>
      <p className="mx-auto mt-6 max-w-2xl text-lg tracking-tight text-slate-700">
        Starten Sie kostenlos mit unbegrenzten Stellen. Upgraden Sie, wenn Sie
        professionelles Bewerbermanagement brauchen.
      </p>
      <div className="mt-10 flex flex-wrap items-center justify-center gap-x-8 gap-y-4">
        {badges.map((badge) => (
          <span
            key={badge.label}
            className="inline-flex items-center gap-x-2 rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700"
          >
            <BadgeIcon type={badge.icon} />
            {badge.label}
          </span>
        ))}
      </div>
    </Container>
  )
}
