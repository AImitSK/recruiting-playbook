import clsx from 'clsx'

const methodStyles = {
  GET: 'bg-emerald-100 text-emerald-800',
  POST: 'bg-blue-100 text-blue-800',
  PUT: 'bg-amber-100 text-amber-800',
  PATCH: 'bg-amber-100 text-amber-800',
  DELETE: 'bg-red-100 text-red-800',
}

export function HttpMethod({ method }) {
  return (
    <span
      className={clsx(
        'inline-block rounded px-2 py-0.5 text-xs font-bold uppercase tracking-wide',
        methodStyles[method] || 'bg-slate-100 text-slate-800',
      )}
    >
      {method}
    </span>
  )
}
