import './globals.css'

export const metadata = {
  title: 'Recruiting Playbook – WordPress Job Board Plugin',
  description: 'Professional job posting and applicant management for WordPress. Free, open-source, and GDPR-compliant.',
}

export default function RootLayout({ children }) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  )
}
