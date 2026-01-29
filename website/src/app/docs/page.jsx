import Link from 'next/link'
import { docsNav } from '@/lib/docs-nav'
import { Breadcrumbs } from '@/components/docs/Breadcrumbs'

export const metadata = {
  title: 'Dokumentation',
  description:
    'Vollstaendige Dokumentation fuer das Recruiting Playbook WordPress-Plugin. Installation, Shortcodes, Templates, Hooks und mehr.',
}

export default function DocsIndex() {
  return (
    <div>
      <Breadcrumbs />
      <h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
        Dokumentation
      </h1>
      <p className="mt-4 text-base leading-7 text-slate-700">
        Willkommen zur Dokumentation von Recruiting Playbook. Hier finden Sie
        alles, was Sie fuer die Einrichtung und den Betrieb des Plugins
        brauchen.
      </p>

      <div className="mt-10 grid gap-4 sm:grid-cols-2">
        {docsNav.map(({ slug, title }) => (
          <Link
            key={slug}
            href={`/docs/${slug}`}
            className="group rounded-lg border border-slate-200 p-5 transition-colors hover:border-blue-200 hover:bg-blue-50/50"
          >
            <h2 className="text-sm font-semibold text-slate-900 group-hover:text-blue-700">
              {title}
            </h2>
            <p className="mt-1 text-sm text-slate-500">
              Mehr erfahren &rarr;
            </p>
          </Link>
        ))}
      </div>
    </div>
  )
}
