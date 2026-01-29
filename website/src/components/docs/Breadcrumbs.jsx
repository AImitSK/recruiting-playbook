import Link from 'next/link'

export function Breadcrumbs({ current }) {
  return (
    <nav className="mb-6 flex text-sm text-slate-500" aria-label="Breadcrumb">
      <ol className="flex items-center gap-1.5">
        <li>
          <Link href="/" className="hover:text-slate-700">
            Startseite
          </Link>
        </li>
        <li aria-hidden="true">
          <svg
            className="h-4 w-4"
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
        </li>
        <li>
          <Link href="/docs" className="hover:text-slate-700">
            Dokumentation
          </Link>
        </li>
        {current && (
          <>
            <li aria-hidden="true">
              <svg
                className="h-4 w-4"
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
            </li>
            <li>
              <span className="font-medium text-slate-900">{current}</span>
            </li>
          </>
        )}
      </ol>
    </nav>
  )
}
