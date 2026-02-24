import { Container } from '@/components/Container'

const stats = [
  { value: '∞', label: 'Unbegrenzte Stellen' },
  { value: '149 €', label: 'Einmalig, kein Abo' },
  { value: '0 €', label: 'Google for Jobs' },
  { value: '100/Mo', label: 'KI-Analysen inklusive' },
]

export function Stats() {
  return (
    <section className="bg-white py-10 sm:py-12">
      <Container>
        <div className="mx-auto max-w-2xl lg:max-w-none">
          <dl className="grid grid-cols-1 gap-0.5 overflow-hidden rounded-2xl text-center sm:grid-cols-2 lg:grid-cols-4">
            {stats.map((stat) => (
              <div
                key={stat.label}
                className="flex flex-col bg-gray-400/5 p-8"
              >
                <dt className="text-sm/6 font-semibold text-gray-600">
                  {stat.label}
                </dt>
                <dd className="order-first text-3xl font-semibold tracking-tight text-gray-900">
                  {stat.value}
                </dd>
              </div>
            ))}
          </dl>
        </div>
      </Container>
    </section>
  )
}
