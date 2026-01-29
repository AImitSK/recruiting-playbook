/** @type {import('next').NextConfig} */
const nextConfig = {
  async redirects() {
    return [
      {
        source: '/impressum',
        destination: '/legal/imprint',
        permanent: true,
      },
    ]
  },
}

module.exports = nextConfig
