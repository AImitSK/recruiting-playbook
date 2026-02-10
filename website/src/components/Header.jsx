'use client'

import Link from 'next/link'
import {
  Popover,
  PopoverButton,
  PopoverBackdrop,
  PopoverPanel,
} from '@headlessui/react'
import clsx from 'clsx'

import { Button } from '@/components/Button'
import { Container } from '@/components/Container'
import { Logo } from '@/components/Logo'
import { NavLink } from '@/components/NavLink'

const resourceLinks = [
  { href: '/docs', label: 'Dokumentation', description: 'Anleitungen & Konfiguration' },
  { href: '/api', label: 'API', description: 'REST API Referenz' },
  { href: '/support', label: 'Support', description: 'Hilfe & Kontakt' },
  {
    href: 'https://github.com/AImitSK/recruiting-playbook',
    label: 'GitHub',
    description: 'Quellcode & Issues',
    external: true,
  },
]

function ChevronDownIcon({ className }) {
  return (
    <svg className={className} fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
      <path strokeLinecap="round" strokeLinejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
    </svg>
  )
}

function ResourcesDropdown() {
  return (
    <Popover className="relative">
      {({ open }) => (
        <>
          <PopoverButton className="inline-flex items-center gap-x-1 rounded-lg px-2 py-1 text-sm text-slate-700 hover:bg-slate-100 hover:text-slate-900 focus:outline-none">
            Ressourcen
            <ChevronDownIcon
              className={clsx(
                'h-4 w-4 transition',
                open && 'rotate-180',
              )}
            />
          </PopoverButton>
          <PopoverPanel
            transition
            className="absolute left-1/2 z-50 mt-3 w-64 -translate-x-1/2 rounded-xl bg-white p-2 shadow-lg ring-1 ring-slate-900/5 data-closed:translate-y-1 data-closed:opacity-0 data-enter:duration-200 data-enter:ease-out data-leave:duration-150 data-leave:ease-in"
          >
            {resourceLinks.map((link) => (
              <PopoverButton
                key={link.href}
                as={Link}
                href={link.href}
                className="flex flex-col rounded-lg px-3 py-2.5 hover:bg-slate-50"
                {...(link.external
                  ? { target: '_blank', rel: 'noopener noreferrer' }
                  : {})}
              >
                <span className="text-sm font-medium text-slate-900">
                  {link.label}
                  {link.external && (
                    <svg className="ml-1 inline h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                  )}
                </span>
                <span className="text-xs text-slate-500">{link.description}</span>
              </PopoverButton>
            ))}
          </PopoverPanel>
        </>
      )}
    </Popover>
  )
}

function MobileNavLink({ href, children, ...props }) {
  return (
    <PopoverButton as={Link} href={href} className="block w-full p-2" {...props}>
      {children}
    </PopoverButton>
  )
}

function MobileNavIcon({ open }) {
  return (
    <svg
      aria-hidden="true"
      className="h-3.5 w-3.5 overflow-visible stroke-slate-700"
      fill="none"
      strokeWidth={2}
      strokeLinecap="round"
    >
      <path
        d="M0 1H14M0 7H14M0 13H14"
        className={clsx(
          'origin-center transition',
          open && 'scale-90 opacity-0',
        )}
      />
      <path
        d="M2 2L12 12M12 2L2 12"
        className={clsx(
          'origin-center transition',
          !open && 'scale-90 opacity-0',
        )}
      />
    </svg>
  )
}

function MobileNavigation() {
  return (
    <Popover>
      <PopoverButton
        className="relative z-10 flex h-8 w-8 items-center justify-center focus:not-data-focus:outline-hidden"
        aria-label="Navigation umschalten"
      >
        {({ open }) => <MobileNavIcon open={open} />}
      </PopoverButton>
      <PopoverBackdrop
        transition
        className="fixed inset-0 bg-slate-300/50 duration-150 data-closed:opacity-0 data-enter:ease-out data-leave:ease-in"
      />
      <PopoverPanel
        transition
        className="absolute inset-x-0 top-full mt-4 flex origin-top flex-col rounded-2xl bg-white p-4 text-lg tracking-tight text-slate-900 shadow-xl ring-1 ring-slate-900/5 data-closed:scale-95 data-closed:opacity-0 data-enter:duration-150 data-enter:ease-out data-leave:duration-100 data-leave:ease-in"
      >
        <MobileNavLink href="/features">Features</MobileNavLink>
        <MobileNavLink href="/ai">KI-Addon</MobileNavLink>
        <MobileNavLink href="/pricing">Preise</MobileNavLink>
        <hr className="m-2 border-slate-300/40" />
        <MobileNavLink href="/docs">Dokumentation</MobileNavLink>
        <MobileNavLink href="/api">API</MobileNavLink>
        <MobileNavLink href="/support">Support</MobileNavLink>
        <MobileNavLink href="/account">Kundenportal</MobileNavLink>
        <hr className="m-2 border-slate-300/40" />
        <MobileNavLink
          href="https://github.com/AImitSK/recruiting-playbook"
          target="_blank"
          rel="noopener noreferrer"
        >
          GitHub
        </MobileNavLink>
      </PopoverPanel>
    </Popover>
  )
}

export function Header() {
  return (
    <header className="py-10">
      <Container>
        <nav className="relative z-50 flex justify-between">
          <div className="flex items-center md:gap-x-12">
            <Link href="/" aria-label="Startseite">
              <Logo className="h-10 w-auto" />
            </Link>
            <div className="hidden md:flex md:items-center md:gap-x-6">
              <NavLink href="/features">Features</NavLink>
              <NavLink href="/ai">KI-Addon</NavLink>
              <NavLink href="/pricing">Preise</NavLink>
              <ResourcesDropdown />
            </div>
          </div>
          <div className="flex items-center gap-x-5 md:gap-x-8">
            <div className="hidden md:block">
              <NavLink href="/account">Kundenportal</NavLink>
            </div>
            <Button href="/recruiting-playbook.zip" color="blue" download>
              <span>
                Kostenlos starten
              </span>
            </Button>
            <div className="-mr-1 md:hidden">
              <MobileNavigation />
            </div>
          </div>
        </nav>
      </Container>
    </header>
  )
}
