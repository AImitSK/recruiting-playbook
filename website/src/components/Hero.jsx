import Image from 'next/image'
import { ChevronRightIcon } from '@heroicons/react/20/solid'

import { Button } from '@/components/Button'
import { Container } from '@/components/Container'
import screenshotJobs from '../../public/screenshots/bewerbungen-liste.png'

const logos = [
  { name: 'WordPress', src: '/trust/WordPress_logo.svg', height: 28 },
  { name: 'Gutenberg', src: '/trust/gutenberg-logo.svg', height: 28 },
  { name: 'Elementor', src: '/trust/elementor-logo.svg', height: 24 },
  { name: 'Avada', src: '/trust/avada-logo-svg.svg', height: 24 },
]

export function Hero() {
  return (
    <>
      {/* Hero */}
      <div className="relative isolate overflow-hidden bg-white">
        {/* Grid pattern background */}
        <svg
          aria-hidden="true"
          className="absolute inset-0 -z-10 size-full mask-[radial-gradient(100%_100%_at_top_right,white,transparent)] stroke-gray-200"
        >
          <defs>
            <pattern
              x="50%"
              y={-1}
              id="hero-grid-pattern"
              width={200}
              height={200}
              patternUnits="userSpaceOnUse"
            >
              <path d="M.5 200V.5H200" fill="none" />
            </pattern>
          </defs>
          <rect
            fill="url(#hero-grid-pattern)"
            width="100%"
            height="100%"
            strokeWidth={0}
          />
        </svg>

        <div className="mx-auto max-w-7xl px-6 pt-10 pb-24 sm:pb-32 lg:flex lg:px-8 lg:py-40">
          {/* Left — Text content */}
          <div className="mx-auto max-w-2xl lg:mx-0 lg:shrink-0 lg:pt-8">
            <div className="mt-24 sm:mt-32 lg:mt-16">
              <a href="#features" className="inline-flex space-x-6">
                <span className="rounded-full bg-[#1d71b8]/10 px-3 py-1 text-sm/6 font-semibold text-[#1d71b8] ring-1 ring-[#1d71b8]/20 ring-inset">
                  WordPress Plugin
                </span>
                <span className="inline-flex items-center space-x-2 text-sm/6 font-medium text-gray-600">
                  <span>DSGVO-konform ab Werk</span>
                  <ChevronRightIcon
                    aria-hidden="true"
                    className="size-5 text-gray-400"
                  />
                </span>
              </a>
            </div>
            <h1 className="mt-10 text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl lg:text-6xl">
              Das Recruiting-Plugin{' '}
              <span className="relative whitespace-nowrap text-[#1d71b8]">
                <svg
                  aria-hidden="true"
                  viewBox="0 0 418 42"
                  className="absolute top-2/3 left-0 h-[0.58em] w-full fill-[#36a9e1]/40"
                  preserveAspectRatio="none"
                >
                  <path d="M203.371.916c-26.013-2.078-76.686 1.963-124.73 9.946L67.3 12.749C35.421 18.062 18.2 21.766 6.004 25.934 1.244 27.561.828 27.778.874 28.61c.07 1.214.828 1.121 9.595-1.176 9.072-2.377 17.15-3.92 39.246-7.496C123.565 7.986 157.869 4.492 195.942 5.046c7.461.108 19.25 1.696 19.17 2.582-.107 1.183-7.874 4.31-25.75 10.366-21.992 7.45-35.43 12.534-36.701 13.884-2.173 2.308-.202 4.407 4.442 4.734 2.654.187 3.263.157 15.593-.78 35.401-2.686 57.944-3.488 88.365-3.143 46.327.526 75.721 2.23 130.788 7.584 19.787 1.924 20.814 1.98 24.557 1.332l.066-.011c1.201-.203 1.53-1.825.399-2.335-2.911-1.31-4.893-1.604-22.048-3.261-57.509-5.556-87.871-7.36-132.059-7.842-23.239-.254-33.617-.116-50.627.674-11.629.54-42.371 2.494-46.696 2.967-2.359.259 8.133-3.625 26.504-9.81 23.239-7.825 27.934-10.149 28.304-14.005.417-4.348-3.529-6-16.878-7.066Z" />
                </svg>
                <span className="relative">für WordPress.</span>
              </span>
            </h1>
            <p className="mt-8 text-lg font-medium text-pretty text-gray-500 sm:text-xl/8">
              Stellenanzeigen erstellen, automatisch in der Google-Jobsuche
              erscheinen, Bewerbungen verwalten. Mit KI-Analyse und Einmalpreis
              statt Abo.
            </p>
            <div className="mt-10 flex items-center gap-x-6">
              <Button href="/recruiting-playbook.zip" color="blue" download>
                Kostenlos herunterladen
              </Button>
              <a
                href="#features"
                className="text-sm/6 font-semibold text-gray-900"
              >
                Features entdecken <span aria-hidden="true">&rarr;</span>
              </a>
            </div>
          </div>

          {/* Right — Screenshot */}
          <div className="mx-auto mt-16 flex max-w-2xl sm:mt-24 lg:mt-0 lg:mr-0 lg:ml-10 lg:max-w-none lg:flex-none xl:ml-32">
            <div className="max-w-3xl flex-none sm:max-w-5xl lg:max-w-none">
              <div className="-m-2 rounded-xl bg-gray-900/5 p-2 ring-1 ring-gray-900/10 ring-inset lg:-m-4 lg:rounded-2xl lg:p-4">
                <Image
                  className="w-[76rem] rounded-md shadow-2xl ring-1 ring-gray-900/10"
                  src={screenshotJobs}
                  alt="Recruiting Playbook - Bewerbungen Übersicht"
                  priority
                  sizes="(min-width: 1024px) 76rem, 100vw"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Logo Cloud */}
      <section className="bg-white py-16 sm:py-20">
        <Container>
          <h2 className="text-center text-lg/8 font-semibold text-gray-900">
            Funktioniert mit Ihrem Page Builder
          </h2>
          <div className="mx-auto mt-10 grid max-w-lg grid-cols-2 items-center gap-x-8 gap-y-10 sm:max-w-xl sm:gap-x-10 lg:mx-0 lg:max-w-none lg:grid-cols-4">
            {logos.map((logo) => (
              <img
                key={logo.name}
                alt={logo.name}
                src={logo.src}
                width={158}
                height={48}
                className="col-span-1 max-h-8 w-full object-contain grayscale opacity-40 transition duration-300 hover:grayscale-0 hover:opacity-100"
              />
            ))}
          </div>
        </Container>
      </section>

    </>
  )
}
