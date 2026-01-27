import { compileMDX } from 'next-mdx-remote/rsc'
import remarkGfm from 'remark-gfm'
import { createHighlighter } from 'shiki'
import { mdxComponents } from '@/components/docs/MDXComponents'

let highlighterPromise = null

function getHighlighter() {
  if (!highlighterPromise) {
    highlighterPromise = createHighlighter({
      themes: ['github-light'],
      langs: ['php', 'javascript', 'json', 'bash', 'html', 'xml', 'css', 'python'],
    })
  }
  return highlighterPromise
}

export async function compileMdx(source, extraComponents = {}) {
  const highlighter = await getHighlighter()

  const { content } = await compileMDX({
    source,
    components: { ...mdxComponents(highlighter), ...extraComponents },
    options: {
      parseFrontmatter: true,
      mdxOptions: {
        remarkPlugins: [remarkGfm],
      },
    },
  })

  return content
}
