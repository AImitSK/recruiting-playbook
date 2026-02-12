#!/usr/bin/env node
/**
 * extract-existing.js — Extracts existing translations from a .po file as JSON
 *
 * Usage: node extract-existing.js <locale>
 *
 * Reads: ../recruiting-playbook-<locale>.po
 * Output: existing-<locale>.json — { "english": "translated", ... }
 */

const fs = require('fs');
const path = require('path');

const locale = process.argv[2];
if (!locale) {
  console.log('Usage: node extract-existing.js <locale>');
  process.exit(1);
}

const poPath = path.join(__dirname, '..', `recruiting-playbook-${locale}.po`);
if (!fs.existsSync(poPath)) {
  console.error(`File not found: ${poPath}`);
  process.exit(1);
}

const content = fs.readFileSync(poPath, 'utf-8');
const lines = content.split('\n');

function extractString(startIdx) {
  const firstMatch = lines[startIdx].match(/"(.*)"/);
  let result = firstMatch ? firstMatch[1] : '';
  let j = startIdx + 1;
  while (j < lines.length && lines[j].startsWith('"')) {
    const match = lines[j].match(/"(.*)"/);
    if (match) result += match[1];
    j++;
  }
  return {
    value: result.replace(/\\n/g, '\n').replace(/\\t/g, '\t').replace(/\\"/g, '"').replace(/\\\\/g, '\\'),
    nextIdx: j,
  };
}

const result = {};
let i = 0;
let translated = 0;
let empty = 0;

// Skip header block
while (i < lines.length && !(lines[i].startsWith('#:') || lines[i].startsWith('#.'))) {
  i++;
}

while (i < lines.length) {
  // Skip comments and blank lines
  while (i < lines.length && (lines[i].startsWith('#') || lines[i].trim() === '')) i++;
  if (i >= lines.length) break;

  let msgctxt = null;

  // msgctxt
  if (lines[i].startsWith('msgctxt ')) {
    const r = extractString(i);
    msgctxt = r.value;
    i = r.nextIdx;
  }

  // msgid
  if (i < lines.length && lines[i].startsWith('msgid ')) {
    const r = extractString(i);
    const msgid = r.value;
    i = r.nextIdx;

    // msgid_plural
    let hasPlural = false;
    if (i < lines.length && lines[i].startsWith('msgid_plural ')) {
      hasPlural = true;
      i = extractString(i).nextIdx;
    }

    // msgstr / msgstr[0], msgstr[1]
    const translations = [];
    while (i < lines.length && lines[i].startsWith('msgstr')) {
      const r2 = extractString(i);
      translations.push(r2.value);
      i = r2.nextIdx;
    }

    if (msgid) {
      const key = msgctxt ? `${msgctxt}\u0004${msgid}` : msgid;

      if (hasPlural) {
        if (translations.length >= 2 && (translations[0] || translations[1])) {
          result[key] = translations.slice(0, 2);
          translated++;
        } else {
          empty++;
        }
      } else {
        if (translations[0]) {
          result[key] = translations[0];
          translated++;
        } else {
          empty++;
        }
      }
    }
  } else {
    i++;
  }
}

const outPath = path.join(__dirname, `existing-${locale}.json`);
fs.writeFileSync(outPath, JSON.stringify(result, null, 2), 'utf-8');
console.log(`${locale}: ${translated} translated, ${empty} empty → ${path.basename(outPath)}`);
