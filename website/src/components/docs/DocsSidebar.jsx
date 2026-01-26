'use client'

import Link from 'next/link'
import { usePathname } from 'next/navigation'
import {
  Popover,
  PopoverButton,
  PopoverPanel,
  PopoverBackdrop,
} from '@headlessui/react'
import clsx from 'clsx'
import { docsNav } from '@/lib/docs-nav'

function NavList({ onSelect }) {
  const pathname = usePathname()

  return (
    <ul className="space-y-1">
      {docsNav.map(({ slug, title }) => {
        const href = `/docs/${slug}`
        const active = pathname === href

        return (
          <li key={slug}>
            <Link
              href={href}
              onClick={onSelect}
              className={clsx(
                'block rounded-md px-3 py-1.5 text-sm',
                active
                  ? 'bg-blue-50 font-semibold text-blue-700'
                  : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900',
              )}
            >
              {title}
            </Link>
          </li>
        )
      })}
    </ul>
  )
}

export function DocsSidebar() {
  return (
    <>
      {/* Desktop */}
      <nav className="hidden lg:block lg:w-56 lg:shrink-0">
        <div className="sticky top-24">
          <p className="mb-3 text-sm font-semibold text-slate-900">
            Dokumentation
          </p>
          <NavList />
        </div>
      </nav>

      {/* Mobile */}
      <div className="mb-6 lg:hidden">
        <Popover className="relative">
          <PopoverButton className="flex w-full items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
            <span>Navigation</span>
            <svg
              className="h-4 w-4 text-slate-400"
              fill="none"
              viewBox="0 0 24 24"
              strokeWidth={2}
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="m19.5 8.25-7.5 7.5-7.5-7.5"
              />
            </svg>
          </PopoverButton>
          <PopoverBackdrop className="fixed inset-0 z-40 bg-slate-900/20" />
          <PopoverPanel className="absolute left-0 z-50 mt-2 w-64 rounded-lg bg-white p-3 shadow-lg ring-1 ring-slate-900/5">
            {({ close }) => <NavList onSelect={() => close()} />}
          </PopoverPanel>
        </Popover>
      </div>
    </>
  )
}
