#!/usr/bin/env node
/**
 * convert-formal.js — Converts de_DE (informal) to de_DE_formal (Sie-Form)
 */
const fs = require('fs');
const path = require('path');

const data = JSON.parse(fs.readFileSync(path.join(__dirname, 'translations-de_DE.json'), 'utf-8'));
const result = {};

function toFormal(str) {
  if (!str || typeof str !== 'string') return str;

  let s = str;

  // === Possessivpronomen (vor Substantiven) ===
  // Muss VOR den einfachen Pronomen-Ersetzungen kommen
  s = s.replace(/\bdeine([rsnm]?)\b/gi, (m) => {
    const lower = m.toLowerCase();
    if (lower === 'dein') return m[0] === 'D' ? 'Ihr' : 'Ihr';
    if (lower === 'deine') return m[0] === 'D' ? 'Ihre' : 'Ihre';
    if (lower === 'deinem') return m[0] === 'D' ? 'Ihrem' : 'Ihrem';
    if (lower === 'deinen') return m[0] === 'D' ? 'Ihren' : 'Ihren';
    if (lower === 'deiner') return m[0] === 'D' ? 'Ihrer' : 'Ihrer';
    if (lower === 'deines') return m[0] === 'D' ? 'Ihres' : 'Ihres';
    if (lower === 'deins') return m[0] === 'D' ? 'Ihres' : 'Ihres';
    return m;
  });

  // === Personalpronomen ===
  s = s.replace(/\bDich\b/g, 'Sie');
  s = s.replace(/\bdich\b/g, 'Sie');
  s = s.replace(/\bDir\b/g, 'Ihnen');
  s = s.replace(/\bdir\b/g, 'Ihnen');

  // "du" als Subjekt → "Sie" (Achtung: nicht in Wortteilen wie "durch")
  s = s.replace(/\bDu\b/g, 'Sie');
  s = s.replace(/\bdu\b/g, 'Sie');

  // === Verben (2. Person Singular → 3. Person Plural / Sie-Form) ===
  // Häufige unregelmäßige Verben
  const verbMap = [
    [/\bbist\b/g, 'sind'],
    [/\bBist\b/g, 'Sind'],
    [/\bhast\b/g, 'haben'],
    [/\bHast\b/g, 'Haben'],
    [/\bkannst\b/g, 'können'],
    [/\bKannst\b/g, 'Können'],
    [/\bwillst\b/g, 'möchten'],
    [/\bWillst\b/g, 'Möchten'],
    [/\bmusst\b/g, 'müssen'],
    [/\bMusst\b/g, 'Müssen'],
    [/\bsollst\b/g, 'sollten'],
    [/\bSollst\b/g, 'Sollten'],
    [/\bwirst\b/g, 'werden'],
    [/\bWirst\b/g, 'Werden'],
    [/\bdarfst\b/g, 'dürfen'],
    [/\bDarfst\b/g, 'Dürfen'],
    [/\bweißt\b/g, 'wissen'],
    [/\bWeißt\b/g, 'Wissen'],
    [/\bsiehst\b/g, 'sehen'],
    [/\bSiehst\b/g, 'Sehen'],
    [/\bgibst\b/g, 'geben'],
    [/\bGibst\b/g, 'Geben'],
    [/\bnimmst\b/g, 'nehmen'],
    [/\bNimmst\b/g, 'Nehmen'],
    [/\bfindest\b/g, 'finden'],
    [/\bFindest\b/g, 'Finden'],
    [/\bgehst\b/g, 'gehen'],
    [/\bGehst\b/g, 'Gehen'],
    [/\bkommst\b/g, 'kommen'],
    [/\bKommst\b/g, 'Kommen'],
    [/\bbrauchst\b/g, 'brauchen'],
    [/\bBrauchst\b/g, 'Brauchen'],
    [/\bmöchtest\b/g, 'möchten'],
    [/\bMöchtest\b/g, 'Möchten'],
  ];

  for (const [pattern, replacement] of verbMap) {
    s = s.replace(pattern, replacement);
  }

  // === Imperativ → Sie-Form ===
  // "Klicke auf" → "Klicken Sie auf"
  const imperatives = [
    [/\bKlicke\b/g, 'Klicken Sie'],
    [/\bklicke\b/g, 'klicken Sie'],
    [/\bWähle\b/g, 'Wählen Sie'],
    [/\bwähle\b/g, 'wählen Sie'],
    [/\bGib\b(?! es)/g, 'Geben Sie'],
    [/\bgib\b(?! es)/g, 'geben Sie'],
    [/\bLade\b/g, 'Laden Sie'],
    [/\blade\b/g, 'laden Sie'],
    [/\bÖffne\b/g, 'Öffnen Sie'],
    [/\böffne\b/g, 'öffnen Sie'],
    [/\bSpeichere\b/g, 'Speichern Sie'],
    [/\bspeichere\b/g, 'speichern Sie'],
    [/\bLösche\b/g, 'Löschen Sie'],
    [/\blösche\b/g, 'löschen Sie'],
    [/\bKopiere\b/g, 'Kopieren Sie'],
    [/\bkopiere\b/g, 'kopieren Sie'],
    [/\bÄndere\b/g, 'Ändern Sie'],
    [/\bändere\b/g, 'ändern Sie'],
    [/\bPrüfe\b/g, 'Prüfen Sie'],
    [/\bprüfe\b/g, 'prüfen Sie'],
    [/\bStelle\b(?! )/g, 'Stellen Sie'],
    [/\bNutze\b/g, 'Nutzen Sie'],
    [/\bnutze\b/g, 'nutzen Sie'],
    [/\bVerwende\b/g, 'Verwenden Sie'],
    [/\bverwende\b/g, 'verwenden Sie'],
    [/\bAktiviere\b/g, 'Aktivieren Sie'],
    [/\baktiviere\b/g, 'aktivieren Sie'],
    [/\bDeaktiviere\b/g, 'Deaktivieren Sie'],
    [/\bdeaktiviere\b/g, 'deaktivieren Sie'],
    [/\bKonfiguriere\b/g, 'Konfigurieren Sie'],
    [/\bkonfiguriere\b/g, 'konfigurieren Sie'],
    [/\bErstelle\b/g, 'Erstellen Sie'],
    [/\berstelle\b/g, 'erstellen Sie'],
    [/\bBearbeite\b/g, 'Bearbeiten Sie'],
    [/\bbearbeite\b/g, 'bearbeiten Sie'],
    [/\bFüge\b/g, 'Fügen Sie'],
    [/\bfüge\b/g, 'fügen Sie'],
    [/\bEntferne\b/g, 'Entfernen Sie'],
    [/\bentferne\b/g, 'entfernen Sie'],
    [/\bBestätige\b/g, 'Bestätigen Sie'],
    [/\bbestätige\b/g, 'bestätigen Sie'],
    [/\bTrage\b/g, 'Tragen Sie'],
    [/\btrage\b/g, 'tragen Sie'],
    [/\bSchau\b/g, 'Schauen Sie'],
    [/\bschau\b/g, 'schauen Sie'],
  ];

  for (const [pattern, replacement] of imperatives) {
    s = s.replace(pattern, replacement);
  }

  return s;
}

let changed = 0;
for (const [key, value] of Object.entries(data)) {
  if (Array.isArray(value)) {
    const converted = value.map(v => toFormal(v));
    result[key] = converted;
    if (JSON.stringify(converted) !== JSON.stringify(value)) changed++;
  } else {
    const converted = toFormal(value);
    result[key] = converted;
    if (converted !== value) changed++;
  }
}

const outPath = path.join(__dirname, 'translations-de_DE_formal.json');
fs.writeFileSync(outPath, JSON.stringify(result, null, 2), 'utf-8');
console.log(`de_DE_formal: ${Object.keys(result).length} entries, ${changed} modified`);
