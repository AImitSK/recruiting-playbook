import fs from 'fs'
import path from 'path'
import matter from 'gray-matter'
import { docsNav } from './docs-nav'

const contentDir = path.join(process.cwd(), 'content', 'docs')

export function getDocBySlug(slug) {
  const filePath = path.join(contentDir, `${slug}.mdx`)
  const source = fs.readFileSync(filePath, 'utf8')
  const { data: frontmatter, content } = matter(source)
  return { slug, frontmatter, content }
}

export function getAllDocs() {
  return docsNav.map(({ slug }) => {
    const { frontmatter } = getDocBySlug(slug)
    return { slug, ...frontmatter }
  })
}

export function getTableOfContents(content) {
  const headings = []
  const regex = /^(#{2,3})\s+(.+)$/gm
  let match
  while ((match = regex.exec(content)) !== null) {
    const level = match[1].length
    const text = match[2].trim()
    const id = text
      .toLowerCase()
      .replace(/[^\w\s-]/g, '')
      .replace(/\s+/g, '-')
    headings.push({ level, text, id })
  }
  return headings
}
