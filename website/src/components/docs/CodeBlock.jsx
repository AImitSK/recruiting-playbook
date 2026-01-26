export function CodeBlock({ highlighter, code, lang }) {
  if (!highlighter || !lang) {
    return (
      <pre className="overflow-x-auto rounded-lg bg-slate-900 p-4 text-sm leading-relaxed text-slate-50">
        <code>{code}</code>
      </pre>
    )
  }

  const html = highlighter.codeToHtml(code, {
    lang,
    theme: 'github-light',
  })

  return (
    <div
      className="[&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:border [&_pre]:border-slate-200 [&_pre]:p-4 [&_pre]:text-sm [&_pre]:leading-relaxed"
      dangerouslySetInnerHTML={{ __html: html }}
    />
  )
}
