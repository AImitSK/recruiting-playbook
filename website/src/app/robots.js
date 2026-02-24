export default function robots() {
  return {
    rules: [
      {
        userAgent: '*',
        allow: '/',
        disallow: ['/account'],
      },
    ],
    sitemap: 'https://recruiting-playbook.com/sitemap.xml',
  }
}
