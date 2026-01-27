import { notFound } from 'next/navigation'
import { apiNav } from '@/lib/api-nav'
import { getApiDocBySlug } from '@/lib/api-docs'
import { compileMdx } from '@/lib/mdx'
import { ApiBreadcrumbs } from '@/components/api/ApiBreadcrumbs'
import { ApiPagination } from '@/components/api/ApiPagination'
import { EndpointHeader } from '@/components/api/EndpointHeader'
import { HttpMethod } from '@/components/api/HttpMethod'

export function generateStaticParams() {
  return apiNav.map(({ slug }) => ({ slug: [slug] }))
}

export function generateMetadata({ params }) {
  const slug = params.slug?.[0]
  const nav = apiNav.find((item) => item.slug === slug)
  if (!nav) return {}
  return {
    title: nav.title,
    description: `${nav.title} - Recruiting Playbook API Reference`,
  }
}

export default async function ApiDocPage({ params }) {
  const slug = params.slug?.[0]

  if (!slug || !apiNav.find((item) => item.slug === slug)) {
    notFound()
  }

  const { frontmatter, content: rawContent } = getApiDocBySlug(slug)
  const content = await compileMdx(rawContent, { EndpointHeader, HttpMethod })

  const nav = apiNav.find((item) => item.slug === slug)

  return (
    <article>
      <ApiBreadcrumbs current={frontmatter.title || nav.title} />
      <h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
        {frontmatter.title || nav.title}
      </h1>
      {frontmatter.description && (
        <p className="mt-2 text-base text-slate-600">
          {frontmatter.description}
        </p>
      )}
      <div className="mt-8">{content}</div>
      <ApiPagination slug={slug} />
    </article>
  )
}
