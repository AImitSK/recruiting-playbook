import clsx from 'clsx'

const styles = {
  info: {
    container: 'border-blue-200 bg-blue-50',
    icon: 'text-blue-600',
    title: 'text-blue-900',
    body: 'text-blue-800',
  },
  warning: {
    container: 'border-amber-200 bg-amber-50',
    icon: 'text-amber-600',
    title: 'text-amber-900',
    body: 'text-amber-800',
  },
  tip: {
    container: 'border-emerald-200 bg-emerald-50',
    icon: 'text-emerald-600',
    title: 'text-emerald-900',
    body: 'text-emerald-800',
  },
}

const icons = {
  info: (
    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
      <path strokeLinecap="round" strokeLinejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
    </svg>
  ),
  warning: (
    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
      <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
    </svg>
  ),
  tip: (
    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
      <path strokeLinecap="round" strokeLinejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
    </svg>
  ),
}

const titles = {
  info: 'Hinweis',
  warning: 'Achtung',
  tip: 'Tipp',
}

export function Callout({ type = 'info', title, children }) {
  const s = styles[type] || styles.info

  return (
    <div className={clsx('my-6 rounded-lg border p-4', s.container)}>
      <div className="flex items-center gap-2">
        <span className={s.icon}>{icons[type]}</span>
        <p className={clsx('text-sm font-semibold', s.title)}>
          {title || titles[type]}
        </p>
      </div>
      <div className={clsx('mt-2 text-sm', s.body)}>{children}</div>
    </div>
  )
}
