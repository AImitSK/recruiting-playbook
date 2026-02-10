import { CodeBlock } from './CodeBlock'
import { Callout } from './Callout'

function extractText(children) {
  if (typeof children === 'string') return children
  if (Array.isArray(children)) return children.map(extractText).join('')
  if (children?.props?.children) return extractText(children.props.children)
  return ''
}

function headingId(children) {
  return extractText(children)
    .toLowerCase()
    .replace(/[^\w\s-]/g, '')
    .replace(/\s+/g, '-')
}

export function mdxComponents(highlighter) {
  return {
    Callout,
    h2: ({ children }) => (
      <h2
        id={headingId(children)}
        className="mt-10 scroll-mt-24 text-xl font-semibold tracking-tight text-slate-900 first:mt-0"
      >
        {children}
      </h2>
    ),
    h3: ({ children }) => (
      <h3
        id={headingId(children)}
        className="mt-8 scroll-mt-24 text-lg font-semibold tracking-tight text-slate-900"
      >
        {children}
      </h3>
    ),
    p: ({ children }) => (
      <p className="mt-4 text-base leading-7 text-slate-700">{children}</p>
    ),
    ul: ({ children }) => (
      <ul className="mt-4 list-disc space-y-2 pl-6 text-slate-700">
        {children}
      </ul>
    ),
    ol: ({ children }) => (
      <ol className="mt-4 list-decimal space-y-2 pl-6 text-slate-700">
        {children}
      </ol>
    ),
    li: ({ children }) => <li className="text-base leading-7">{children}</li>,
    a: ({ href, children }) => (
      <a
        href={href}
        className="font-medium text-blue-600 underline decoration-blue-400/30 underline-offset-2 hover:decoration-blue-600"
        target={href?.startsWith('http') ? '_blank' : undefined}
        rel={href?.startsWith('http') ? 'noopener noreferrer' : undefined}
      >
        {children}
      </a>
    ),
    strong: ({ children }) => (
      <strong className="font-semibold text-slate-900">{children}</strong>
    ),
    code: ({ children }) => (
      <code className="rounded bg-slate-100 px-1.5 py-0.5 text-sm font-medium text-slate-800">
        {children}
      </code>
    ),
    pre: ({ children }) => {
      const codeEl = children?.props
      const code = codeEl?.children || ''
      const className = codeEl?.className || ''
      const langMatch = className.match(/language-(\w+)/)
      const lang = langMatch ? langMatch[1] : null

      return (
        <div className="my-6">
          <CodeBlock highlighter={highlighter} code={code.trim()} lang={lang} />
        </div>
      )
    },
    table: ({ children }) => (
      <div className="mt-4 overflow-x-auto">
        <table className="w-full text-left text-sm">{children}</table>
      </div>
    ),
    thead: ({ children }) => (
      <thead className="border-b border-slate-200 text-slate-900">
        {children}
      </thead>
    ),
    tbody: ({ children }) => (
      <tbody className="divide-y divide-slate-100">{children}</tbody>
    ),
    tr: ({ children }) => <tr>{children}</tr>,
    th: ({ children }) => (
      <th className="px-3 py-2 font-semibold first:pl-0 last:pr-0">
        {children}
      </th>
    ),
    td: ({ children }) => (
      <td className="px-3 py-2 text-slate-700 first:pl-0 last:pr-0">
        {children}
      </td>
    ),
    img: ({ src, alt, ...props }) => (
      <figure className="my-6">
        <img
          src={src}
          alt={alt || ''}
          className="w-full rounded-lg border border-slate-200 shadow-sm"
          loading="lazy"
          {...props}
        />
        {alt && (
          <figcaption className="mt-2 text-center text-sm text-slate-500">
            {alt}
          </figcaption>
        )}
      </figure>
    ),
    hr: () => <hr className="my-8 border-slate-200" />,
    blockquote: ({ children }) => (
      <blockquote className="mt-4 border-l-2 border-slate-300 pl-4 italic text-slate-600">
        {children}
      </blockquote>
    ),
  }
}
