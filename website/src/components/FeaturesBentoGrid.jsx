import Image from 'next/image'
import { SparklesIcon } from '@heroicons/react/24/solid'

import { Container } from '@/components/Container'

import screenshotGrid from '../../public/screenshots/grid.png'
import screenshotKanban from '../../public/screenshots/kanban-board.png'

export function FeaturesBentoGrid() {
  return (
    <section className="py-20 sm:py-32">
      <Container>
        <div className="mx-auto max-w-2xl text-center">
          <h2 className="font-display text-3xl tracking-tight text-slate-900 sm:text-4xl">
            Die wichtigsten Features
          </h2>
          <p className="mt-4 text-lg tracking-tight text-slate-700">
            Drei Kernfunktionen, die Ihr Recruiting auf das nächste Level heben.
          </p>
        </div>

        <div className="mx-auto mt-16 grid max-w-5xl grid-cols-1 gap-4 lg:grid-cols-3 lg:grid-rows-2">
          {/* Google for Jobs — Large cell (2 cols, 2 rows) */}
          <div className="relative overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-slate-200 lg:col-span-2 lg:row-span-2">
            <div className="p-8 pb-0 sm:p-10 sm:pb-0">
              <h3 className="font-display text-2xl tracking-tight text-slate-900">
                Stellenanzeigen, die gefunden werden
              </h3>
              <p className="mt-2 max-w-lg text-sm text-slate-600">
                Automatisches Google for Jobs Schema-Markup. Ihre Stellen
                erscheinen direkt in der Google-Suche — ohne technisches Wissen.
              </p>
            </div>
            <div className="relative mt-6 px-4 sm:px-6">
              <div className="overflow-hidden rounded-t-xl bg-slate-50 ring-1 ring-slate-200">
                <Image
                  className="w-full"
                  src={screenshotGrid}
                  alt="Stellenanzeigen Grid-Ansicht mit Google for Jobs"
                  sizes="(min-width: 1024px) 60vw, 100vw"
                />
              </div>
            </div>
          </div>

          {/* Kanban-Board — Small top-right cell */}
          <div className="relative overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-slate-200">
            <div className="p-6">
              <div className="flex items-center gap-x-2">
                <h3 className="font-display text-lg tracking-tight text-slate-900">
                  Kanban-Board
                </h3>
                <span className="inline-flex items-center rounded-full bg-[#1d71b8] px-2 py-0.5 text-xs font-semibold text-white">
                  Pro
                </span>
              </div>
              <p className="mt-1 text-sm text-slate-600">
                Bewerber per Drag & Drop durch Ihre Pipeline bewegen.
              </p>
            </div>
            <div className="px-4 pb-4">
              <div className="overflow-hidden rounded-lg bg-slate-50 ring-1 ring-slate-200">
                <Image
                  className="w-full"
                  src={screenshotKanban}
                  alt="Kanban-Board mit Bewerbungen"
                  sizes="(min-width: 1024px) 20vw, 100vw"
                />
              </div>
            </div>
          </div>

          {/* KI-Analyse — Small bottom-right cell with gradient */}
          <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1d71b8] to-[#36a9e1] shadow-lg">
            <div className="p-6">
              <div className="flex items-center gap-x-2">
                <h3 className="font-display text-lg tracking-tight text-white">
                  KI-Analyse
                </h3>
                <span className="inline-flex items-center rounded-full bg-white/20 px-2 py-0.5 text-xs font-semibold text-white">
                  Pro
                </span>
              </div>
              <p className="mt-1 text-sm text-white/80">
                Bewerbungen automatisch analysieren und Kandidaten intelligent
                bewerten.
              </p>
            </div>
            <div className="flex items-center justify-center pb-8">
              <SparklesIcon className="h-20 w-20 text-white/30" />
            </div>
          </div>
        </div>
      </Container>
    </section>
  )
}
