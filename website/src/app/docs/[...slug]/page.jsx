import { notFound } from 'next/navigation'
import { docsNav } from '@/lib/docs-nav'
import { getDocBySlug, getTableOfContents } from '@/lib/docs'
import { compileMdx } from '@/lib/mdx'
import { Breadcrumbs } from '@/components/docs/Breadcrumbs'
import { TableOfContents } from '@/components/docs/TableOfContents'
import { DocsPagination } from '@/components/docs/DocsPagination'

export function generateStaticParams() {
  return docsNav.map(({ slug }) => ({ slug: [slug] }))
}

export function generateMetadata({ params }) {
  const slug = params.slug?.[0]
  const nav = docsNav.find((item) => item.slug === slug)
  if (!nav) return {}
  return {
    title: nav.title,
    description: `${nav.title} - Recruiting Playbook Dokumentation`,
  }
}

export default async function DocPage({ params }) {
  const slug = params.slug?.[0]

  if (!slug || !docsNav.find((item) => item.slug === slug)) {
    notFound()
  }

  const { frontmatter, content: rawContent } = getDocBySlug(slug)
  const headings = getTableOfContents(rawContent)
  const content = await compileMdx(rawContent)

  const nav = docsNav.find((item) => item.slug === slug)

  return (
    <div className="flex gap-10">
      <article className="min-w-0 flex-1">
        <Breadcrumbs current={frontmatter.title || nav.title} />
        <h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
          {frontmatter.title || nav.title}
        </h1>
        {frontmatter.description && (
          <p className="mt-2 text-base text-slate-600">
            {frontmatter.description}
          </p>
        )}
        <div className="docs-prose mt-8">{content}</div>
        <DocsPagination slug={slug} />
      </article>
      <TableOfContents headings={headings} />
    </div>
  )
}
