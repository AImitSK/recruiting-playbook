import fs from 'fs'
import path from 'path'
import matter from 'gray-matter'
import { apiNav } from './api-nav'

const contentDir = path.join(process.cwd(), 'content', 'api')

export function getApiDocBySlug(slug) {
  const filePath = path.join(contentDir, `${slug}.mdx`)
  const source = fs.readFileSync(filePath, 'utf8')
  const { data: frontmatter, content } = matter(source)
  return { slug, frontmatter, content }
}

export function getAllApiDocs() {
  return apiNav.map(({ slug }) => {
    const { frontmatter } = getApiDocBySlug(slug)
    return { slug, ...frontmatter }
  })
}
