'use client'

import { useEffect, useState } from 'react'
import clsx from 'clsx'

export function TableOfContents({ headings }) {
  const [activeId, setActiveId] = useState('')

  useEffect(() => {
    if (headings.length === 0) return

    const observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (entry.isIntersecting) {
            setActiveId(entry.target.id)
          }
        }
      },
      { rootMargin: '-80px 0px -70% 0px' },
    )

    for (const { id } of headings) {
      const el = document.getElementById(id)
      if (el) observer.observe(el)
    }

    return () => observer.disconnect()
  }, [headings])

  if (headings.length === 0) return null

  return (
    <nav className="hidden xl:block xl:w-48 xl:shrink-0">
      <div className="sticky top-24">
        <p className="mb-3 text-sm font-semibold text-slate-900">
          Auf dieser Seite
        </p>
        <ul className="space-y-2">
          {headings.map(({ id, text, level }) => (
            <li key={id}>
              <a
                href={`#${id}`}
                className={clsx(
                  'block text-sm leading-snug transition-colors',
                  level === 3 && 'pl-3',
                  activeId === id
                    ? 'font-medium text-blue-600'
                    : 'text-slate-500 hover:text-slate-800',
                )}
              >
                {text}
              </a>
            </li>
          ))}
        </ul>
      </div>
    </nav>
  )
}
