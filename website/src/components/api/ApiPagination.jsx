import Link from 'next/link'
import { apiNav } from '@/lib/api-nav'

export function ApiPagination({ slug }) {
  const index = apiNav.findIndex((item) => item.slug === slug)
  const prev = index > 0 ? apiNav[index - 1] : null
  const next = index < apiNav.length - 1 ? apiNav[index + 1] : null

  if (!prev && !next) return null

  return (
    <div className="mt-12 flex items-center justify-between border-t border-slate-200 pt-6">
      {prev ? (
        <Link
          href={`/api/${prev.slug}`}
          className="group flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
        >
          <svg
            className="h-4 w-4 transition-transform group-hover:-translate-x-0.5"
            fill="none"
            viewBox="0 0 24 24"
            strokeWidth={2}
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M15.75 19.5 8.25 12l7.5-7.5"
            />
          </svg>
          {prev.title}
        </Link>
      ) : (
        <span />
      )}
      {next ? (
        <Link
          href={`/api/${next.slug}`}
          className="group flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900"
        >
          {next.title}
          <svg
            className="h-4 w-4 transition-transform group-hover:translate-x-0.5"
            fill="none"
            viewBox="0 0 24 24"
            strokeWidth={2}
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="m8.25 4.5 7.5 7.5-7.5 7.5"
            />
          </svg>
        </Link>
      ) : (
        <span />
      )}
    </div>
  )
}
