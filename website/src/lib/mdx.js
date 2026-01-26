import { compileMDX } from 'next-mdx-remote/rsc'
import { createHighlighter } from 'shiki'
import { mdxComponents } from '@/components/docs/MDXComponents'

let highlighterPromise = null

function getHighlighter() {
  if (!highlighterPromise) {
    highlighterPromise = createHighlighter({
      themes: ['github-light'],
      langs: ['php', 'javascript', 'json', 'bash', 'html', 'xml', 'css'],
    })
  }
  return highlighterPromise
}

export async function compileMdx(source) {
  const highlighter = await getHighlighter()

  const { content } = await compileMDX({
    source,
    components: mdxComponents(highlighter),
    options: {
      parseFrontmatter: true,
    },
  })

  return content
}
