import { docsNav } from '@/lib/docs-nav'
import { apiNav } from '@/lib/api-nav'

const BASE_URL = 'https://recruiting-playbook.com'

export default function sitemap() {
  const now = new Date().toISOString()

  // Static pages
  const staticPages = [
    { url: BASE_URL, changeFrequency: 'weekly', priority: 1.0 },
    { url: `${BASE_URL}/features`, changeFrequency: 'monthly', priority: 0.9 },
    { url: `${BASE_URL}/pricing`, changeFrequency: 'monthly', priority: 0.9 },
    { url: `${BASE_URL}/ai`, changeFrequency: 'monthly', priority: 0.8 },
    { url: `${BASE_URL}/google-for-jobs`, changeFrequency: 'monthly', priority: 0.9 },
    { url: `${BASE_URL}/vergleich`, changeFrequency: 'monthly', priority: 0.8 },
    { url: `${BASE_URL}/support`, changeFrequency: 'monthly', priority: 0.6 },
    { url: `${BASE_URL}/changelog`, changeFrequency: 'weekly', priority: 0.5 },
    { url: `${BASE_URL}/docs`, changeFrequency: 'weekly', priority: 0.7 },
    { url: `${BASE_URL}/api`, changeFrequency: 'monthly', priority: 0.6 },
    { url: `${BASE_URL}/legal/imprint`, changeFrequency: 'yearly', priority: 0.3 },
    { url: `${BASE_URL}/legal/privacy`, changeFrequency: 'yearly', priority: 0.3 },
    { url: `${BASE_URL}/legal/terms`, changeFrequency: 'yearly', priority: 0.3 },
  ]

  // Doc pages
  const docPages = docsNav.map((doc) => ({
    url: `${BASE_URL}/docs/${doc.slug}`,
    changeFrequency: 'monthly',
    priority: 0.7,
  }))

  // API doc pages
  const apiPages = apiNav.map((doc) => ({
    url: `${BASE_URL}/api/${doc.slug}`,
    changeFrequency: 'monthly',
    priority: 0.6,
  }))

  return [...staticPages, ...docPages, ...apiPages].map((page) => ({
    ...page,
    lastModified: now,
  }))
}
