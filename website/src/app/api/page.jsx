import Link from 'next/link'
import { apiNav } from '@/lib/api-nav'
import { ApiBreadcrumbs } from '@/components/api/ApiBreadcrumbs'

const descriptions = {
  authentication: 'API keys, WordPress Application Passwords, and permissions.',
  jobs: 'Create, read, update, and delete job listings.',
  applications: 'Manage applications, status updates, notes, ratings, and documents.',
  roles: 'Manage user roles and capabilities for recruiters and hiring managers.',
  'job-assignments': 'Assign users to specific job listings and manage assignments.',
  webhooks: 'Register webhooks, available events, payloads, and signature validation.',
  reports: 'Recruiting overview and time-to-hire analytics.',
  errors: 'HTTP status codes, error codes, rate limiting, and versioning.',
}

export const metadata = {
  title: 'API-Referenz',
  description:
    'Complete REST API reference for the Recruiting Playbook WordPress plugin. Endpoints for jobs, applications, webhooks, and reports.',
}

export default function ApiIndex() {
  return (
    <div>
      <ApiBreadcrumbs />
      <h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
        API-Referenz
      </h1>
      <p className="mt-4 text-base leading-7 text-slate-700">
        The Recruiting Playbook REST API lets you integrate job listings and
        applicant management into any system. All endpoints use the{' '}
        <code className="rounded bg-slate-100 px-1.5 py-0.5 text-sm font-medium text-slate-800">
          /wp-json/recruiting/v1/
        </code>{' '}
        namespace.
      </p>

      <div className="mt-10 grid gap-4 sm:grid-cols-2">
        {apiNav.map(({ slug, title }) => (
          <Link
            key={slug}
            href={`/api/${slug}`}
            className="group rounded-lg border border-slate-200 p-5 transition-colors hover:border-blue-200 hover:bg-blue-50/50"
          >
            <h2 className="text-sm font-semibold text-slate-900 group-hover:text-blue-700">
              {title}
            </h2>
            <p className="mt-1 text-sm text-slate-500">
              {descriptions[slug]}
            </p>
          </Link>
        ))}
      </div>
    </div>
  )
}
