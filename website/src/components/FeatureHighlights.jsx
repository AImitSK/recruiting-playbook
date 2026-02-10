import Image from 'next/image'
import clsx from 'clsx'

import { Container } from '@/components/Container'

import screenshotGrid from '../../public/screenshots/grid.png'
import screenshotKanban from '../../public/screenshots/kanban-board.png'
import screenshotDetails from '../../public/screenshots/bewerbungen-details.png'
import screenshotEmail from '../../public/screenshots/email-templates.png'
import screenshotFormBuilder from '../../public/screenshots/formular-builder.png'
import screenshotReports from '../../public/screenshots/berichte-conversion.png'
import screenshotBranding from '../../public/screenshots/eistellungen-branding-typografie.png'

const sections = [
  {
    title: 'Stellenanzeigen, die gefunden werden',
    text: 'Erstellen Sie unbegrenzt Stellenanzeigen als WordPress Custom Post Type. Automatisches Google for Jobs Schema-Markup sorgt dafür, dass Ihre Stellen direkt in der Google-Suche erscheinen — ohne technisches Wissen.',
    image: screenshotGrid,
    imageAlt: 'Stellenanzeigen Grid-Ansicht',
    imagePosition: 'left',
    badge: null,
    bullets: [
      'Unbegrenzte Stellenanzeigen (auch in Free)',
      'Google for Jobs Schema automatisch generiert',
      'Kategorien, Standorte und Beschäftigungsarten als Filter',
      'Gehalt, Ansprechpartner, Bewerbungsfrist',
      'Responsive Grid-Layout mit konfigurierbaren Spalten',
    ],
  },
  {
    title: 'Bewerbungen visuell managen',
    text: 'Behalten Sie den Überblick über alle Bewerbungen mit dem Kanban-Board. Ziehen Sie Bewerber per Drag & Drop durch Ihre Pipeline — von der neuen Bewerbung bis zur Einstellung.',
    image: screenshotKanban,
    imageAlt: 'Kanban-Board mit Bewerbungen',
    imagePosition: 'right',
    badge: 'Pro',
    bullets: [
      'Drag & Drop zwischen Status-Spalten',
      'Neu → Screening → Interview → Angebot → Eingestellt',
      'Quick-Actions direkt auf der Karte',
      'Filter nach Stelle, Zeitraum und Status',
      'Statusänderungen werden automatisch protokolliert',
    ],
  },
  {
    title: 'Alle Informationen auf einen Blick',
    text: 'Jede Bewerbung hat eine eigene Detailseite mit Tabs für Dokumente, Notizen, Verlauf und E-Mails. Bewerten Sie Bewerber mit Sternen und hinterlassen Sie interne Notizen für Ihr Team.',
    image: screenshotDetails,
    imageAlt: 'Bewerbung Detailansicht',
    imagePosition: 'left',
    badge: 'Pro',
    bullets: [
      'Kandidaten-Details mit allen Kontaktdaten',
      'Dokumente-Tab mit sicheren Download-Links',
      'Internes Notizen-System mit Autor und Zeitstempel',
      'Sterne-Bewertung pro Bewerber',
      'Vollständiger Aktivitäts-Verlauf',
      'Talent-Pool für vielversprechende Kandidaten',
    ],
  },
  {
    title: 'Professionelle E-Mail-Kommunikation',
    text: 'Versenden Sie Eingangsbestätigungen, Absagen und Interview-Einladungen mit anpassbaren Templates. Platzhalter wie Name, Stelle und Firma werden automatisch ersetzt.',
    image: screenshotEmail,
    imageAlt: 'E-Mail Templates Verwaltung',
    imagePosition: 'right',
    badge: 'Pro',
    bullets: [
      'Anpassbare E-Mail-Templates mit WYSIWYG-Editor',
      'Platzhalter: {vorname}, {stelle}, {firma} und mehr',
      'Automatische E-Mail Workflows',
      'Manueller Versand direkt aus der Bewerbung',
      'Komplette E-Mail-Historie pro Bewerber',
      'Queued Delivery für zuverlässigen Versand',
    ],
  },
  {
    title: 'Formulare nach Ihren Wünschen',
    text: 'Bauen Sie mehrstufige Bewerbungsformulare per Drag & Drop. Fügen Sie eigene Felder hinzu, definieren Sie Pflichtfelder und nutzen Sie Conditional Logic für dynamische Formulare.',
    image: screenshotFormBuilder,
    imageAlt: 'Formular-Builder',
    imagePosition: 'left',
    badge: 'Pro',
    bullets: [
      'Drag & Drop Formular-Editor',
      'Feldtypen: Text, Textarea, Select, Checkbox, Radio, Date',
      'Conditional Logic (Felder ein-/ausblenden)',
      'Mehrere Dokument-Uploads mit Dateivalidierung',
      'Vierstufiger Spam-Schutz (Honeypot, Time-Check, Rate Limiting, Captcha)',
      'DSGVO-Checkboxen mit Consent-Protokollierung',
    ],
  },
  {
    title: 'Datenbasierte Recruiting-Entscheidungen',
    text: 'Verstehen Sie, welche Stellen gut laufen und wo Bewerber abspringen. Conversion-Rates, Zeiträume und Trends auf einen Blick.',
    image: screenshotReports,
    imageAlt: 'Berichte und Analytics Dashboard',
    imagePosition: 'right',
    badge: 'Pro',
    bullets: [
      'Bewerbungen pro Stelle und Zeitraum',
      'Conversion-Rates durch die Pipeline',
      'Time-to-Hire Berechnung',
      'Trend-Analyse über Zeiträume',
      'CSV-Export für eigene Auswertungen',
    ],
  },
  {
    title: 'Ihr Design. Ihre Marke.',
    text: 'Passen Sie das Aussehen des Plugins komplett an Ihr Corporate Design an. Farben, Typografie, Cards und Buttons — alles konfigurierbar. Oder nutzen Sie einfach Ihre Theme-Einstellungen.',
    image: screenshotBranding,
    imageAlt: 'Design und Branding Einstellungen',
    imagePosition: 'left',
    badge: 'Pro',
    bullets: [
      'Primärfarbe und Button-Design anpassen',
      'Typografie (Überschriften, Fließtext, Labels)',
      'Card-Design (Eckenradius, Schatten, Rahmen)',
      'Theme-Farben automatisch übernehmen',
      '"Powered by" Badge entfernen',
    ],
  },
]

function CheckIcon() {
  return (
    <svg
      className="mt-0.5 h-5 w-5 flex-none text-[#2fac66]"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth={2}
        d="M5 13l4 4L19 7"
      />
    </svg>
  )
}

function FeatureSection({ section, index }) {
  const isImageLeft = section.imagePosition === 'left'

  return (
    <div
      className={clsx(
        'flex flex-col items-center gap-12 lg:flex-row lg:gap-16',
        index > 0 && 'mt-24 sm:mt-32',
      )}
    >
      {/* Image */}
      <div
        className={clsx(
          'w-full lg:w-1/2',
          !isImageLeft && 'lg:order-2',
        )}
      >
        <div className="overflow-hidden rounded-xl bg-slate-50 shadow-xl shadow-slate-900/10 ring-1 ring-slate-200">
          <Image
            className="w-full"
            src={section.image}
            alt={section.imageAlt}
            sizes="(min-width: 1024px) 50vw, 100vw"
          />
        </div>
      </div>

      {/* Text */}
      <div
        className={clsx(
          'w-full lg:w-1/2',
          !isImageLeft && 'lg:order-1',
        )}
      >
        <div className="flex items-center gap-x-3">
          <h3 className="font-display text-2xl tracking-tight text-slate-900 sm:text-3xl">
            {section.title}
          </h3>
          {section.badge && (
            <span className="inline-flex items-center rounded-full bg-[#1d71b8] px-2.5 py-0.5 text-xs font-semibold text-white">
              {section.badge}
            </span>
          )}
        </div>
        <p className="mt-4 text-base text-slate-600">
          {section.text}
        </p>
        <ul role="list" className="mt-6 flex flex-col gap-y-2">
          {section.bullets.map((bullet) => (
            <li
              key={bullet}
              className="flex items-start gap-x-3 text-sm text-slate-700"
            >
              <CheckIcon />
              {bullet}
            </li>
          ))}
        </ul>
      </div>
    </div>
  )
}

export function FeatureHighlights() {
  return (
    <section className="py-20 sm:py-32">
      <Container>
        {sections.map((section, index) => (
          <FeatureSection key={section.title} section={section} index={index} />
        ))}
      </Container>
    </section>
  )
}
