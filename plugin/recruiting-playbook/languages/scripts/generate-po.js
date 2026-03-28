#!/usr/bin/env node
/**
 * generate-po.js — Generates a .po file from a .pot template + JSON translations
 *
 * Usage:
 *   node generate-po.js <locale> <translations.json>
 *
 * - Reads recruiting-playbook.pot from the parent directory
 * - Reads the JSON translations file (key = English msgid, value = translated string)
 * - For plurals: value is an array [singular, plural]
 * - Outputs a complete .po file to ../recruiting-playbook-<locale>.po
 */

const fs = require('fs');
const path = require('path');

const HEADERS = {
  'de_DE': {
    comment: '# German translations for Recruiting Playbook',
    language: 'de_DE',
    team: 'German',
    pluralForms: 'nplurals=2; plural=(n != 1);',
  },
  'de_DE_formal': {
    comment: '# German (formal) translations for Recruiting Playbook',
    language: 'de_DE_formal',
    team: 'German',
    pluralForms: 'nplurals=2; plural=(n != 1);',
  },
  'de_AT': {
    comment: '# Austrian German translations for Recruiting Playbook',
    language: 'de_AT',
    team: 'German (Austria)',
    pluralForms: 'nplurals=2; plural=(n != 1);',
  },
  'de_CH': {
    comment: '# Swiss German translations for Recruiting Playbook',
    language: 'de_CH',
    team: 'German (Switzerland)',
    pluralForms: 'nplurals=2; plural=(n != 1);',
  },
  'fr_FR': {
    comment: '# French translations for Recruiting Playbook',
    language: 'fr_FR',
    team: 'French',
    pluralForms: 'nplurals=2; plural=(n > 1);',
  },
  'es_ES': {
    comment: '# Spanish translations for Recruiting Playbook',
    language: 'es_ES',
    team: 'Spanish',
    pluralForms: 'nplurals=2; plural=(n != 1);',
  },
  'it_IT': {
    comment: '# Italian translations for Recruiting Playbook',
    language: 'it_IT',
    team: 'Italian',
    pluralForms: 'nplurals=2; plural=(n != 1);',
  },
  'nl_NL': {
    comment: '# Dutch translations for Recruiting Playbook',
    language: 'nl_NL',
    team: 'Dutch',
    pluralForms: 'nplurals=2; plural=(n != 1);',
  },
};

function parsePot(potContent) {
  const entries = [];
  const lines = potContent.split('\n');
  let i = 0;

  // Skip header
  while (i < lines.length && !(lines[i].startsWith('#:') || lines[i].startsWith('#.'))) {
    i++;
  }

  while (i < lines.length) {
    const entry = { comments: [], msgctxt: null, msgid: '', msgid_plural: null };

    // Collect comments
    while (i < lines.length && (lines[i].startsWith('#') || lines[i].trim() === '')) {
      if (lines[i].trim() !== '') {
        entry.comments.push(lines[i]);
      }
      i++;
    }

    if (i >= lines.length) break;

    // msgctxt
    if (lines[i].startsWith('msgctxt ')) {
      entry.msgctxt = extractString(lines, i);
      i = skipMultiline(lines, i);
    }

    // msgid
    if (i < lines.length && lines[i].startsWith('msgid ')) {
      entry.msgid = extractString(lines, i);
      i = skipMultiline(lines, i);
    }

    // msgid_plural
    if (i < lines.length && lines[i].startsWith('msgid_plural ')) {
      entry.msgid_plural = extractString(lines, i);
      i = skipMultiline(lines, i);
    }

    // Skip msgstr in .pot
    while (i < lines.length && lines[i].startsWith('msgstr')) {
      i = skipMultiline(lines, i);
    }

    if (entry.msgid) {
      entries.push(entry);
    }
  }

  return entries;
}

function extractString(lines, startIdx) {
  // First line: msgid "string" or msgid ""
  const firstMatch = lines[startIdx].match(/"(.*)"/);
  let result = firstMatch ? firstMatch[1] : '';

  let j = startIdx + 1;
  // Continuation lines start with "
  while (j < lines.length && lines[j].startsWith('"')) {
    const match = lines[j].match(/"(.*)"/);
    if (match) result += match[1];
    j++;
  }

  return unescapePo(result);
}

function skipMultiline(lines, startIdx) {
  let j = startIdx + 1;
  while (j < lines.length && lines[j].startsWith('"')) {
    j++;
  }
  return j;
}

function unescapePo(str) {
  return str
    .replace(/\\n/g, '\n')
    .replace(/\\t/g, '\t')
    .replace(/\\"/g, '"')
    .replace(/\\\\/g, '\\');
}

function escapePo(str) {
  return str
    .replace(/\\/g, '\\\\')
    .replace(/"/g, '\\"')
    .replace(/\t/g, '\\t');
}

function formatPoString(prefix, str) {
  // Handle newlines — split into multiline format
  if (str.includes('\n') && str !== '\n') {
    const parts = str.split('\n');
    const lines = [`${prefix} ""`];
    for (let k = 0; k < parts.length; k++) {
      const suffix = k < parts.length - 1 ? '\\n' : '';
      if (parts[k] || suffix) {
        lines.push(`"${escapePo(parts[k])}${suffix}"`);
      }
    }
    return lines.join('\n');
  }

  return `${prefix} "${escapePo(str)}"`;
}

function generatePo(locale, entries, translations) {
  const h = HEADERS[locale];
  if (!h) {
    console.error(`Unknown locale: ${locale}`);
    process.exit(1);
  }

  const output = [];

  // Header
  output.push(h.comment);
  output.push('# Copyright (C) 2026 Recruiting Playbook');
  output.push('# This file is distributed under the GPL-2.0-or-later.');
  output.push('msgid ""');
  output.push('msgstr ""');
  output.push(`"Project-Id-Version: Recruiting Playbook 1.0.0\\n"`);
  output.push(`"Report-Msgid-Bugs-To: https://github.com/AImitSK/recruiting-playbook/issues\\n"`);
  output.push(`"Last-Translator: Recruiting Playbook Team <info@recruiting-playbook.de>\\n"`);
  output.push(`"Language-Team: ${h.team}\\n"`);
  output.push(`"Language: ${h.language}\\n"`);
  output.push(`"MIME-Version: 1.0\\n"`);
  output.push(`"Content-Type: text/plain; charset=UTF-8\\n"`);
  output.push(`"Content-Transfer-Encoding: 8bit\\n"`);
  output.push(`"POT-Creation-Date: 2026-02-11T08:22:00+00:00\\n"`);
  output.push(`"PO-Revision-Date: 2026-02-11T12:00:00+00:00\\n"`);
  output.push(`"X-Generator: Claude Code\\n"`);
  output.push(`"X-Domain: recruiting-playbook\\n"`);
  output.push(`"Plural-Forms: ${h.pluralForms}\\n"`);
  output.push('');

  let translated = 0;
  let total = 0;

  for (const entry of entries) {
    // Comments
    for (const c of entry.comments) {
      output.push(c);
    }

    // msgctxt
    if (entry.msgctxt) {
      output.push(formatPoString('msgctxt', entry.msgctxt));
    }

    // Lookup key: use msgctxt|msgid if context exists
    const lookupKey = entry.msgctxt ? `${entry.msgctxt}\u0004${entry.msgid}` : entry.msgid;

    total++;

    if (entry.msgid_plural) {
      // Plural entry
      output.push(formatPoString('msgid', entry.msgid));
      output.push(formatPoString('msgid_plural', entry.msgid_plural));

      const trans = translations[lookupKey];
      if (trans && Array.isArray(trans) && trans.length >= 2) {
        output.push(formatPoString('msgstr[0]', trans[0]));
        output.push(formatPoString('msgstr[1]', trans[1]));
        translated++;
      } else if (trans && typeof trans === 'string') {
        output.push(formatPoString('msgstr[0]', trans));
        output.push(formatPoString('msgstr[1]', trans));
        translated++;
      } else {
        output.push('msgstr[0] ""');
        output.push('msgstr[1] ""');
      }
    } else {
      // Singular entry
      output.push(formatPoString('msgid', entry.msgid));

      const trans = translations[lookupKey];
      if (trans !== undefined && trans !== null && trans !== '') {
        const transStr = Array.isArray(trans) ? trans[0] : trans;
        output.push(formatPoString('msgstr', transStr));
        translated++;
      } else {
        output.push('msgstr ""');
      }
    }

    output.push('');
  }

  console.log(`${locale}: ${translated}/${total} strings translated (${Math.round(translated/total*100)}%)`);
  return output.join('\n');
}

// Main
const args = process.argv.slice(2);
if (args.length < 2) {
  console.log('Usage: node generate-po.js <locale> <translations.json>');
  console.log('Locales: ' + Object.keys(HEADERS).join(', '));
  process.exit(1);
}

const locale = args[0];
const transFile = args[1];

const potPath = path.join(__dirname, '..', 'recruiting-playbook.pot');
if (!fs.existsSync(potPath)) {
  // Try parent dir
  const altPotPath = path.join(__dirname, 'recruiting-playbook.pot');
  if (!fs.existsSync(altPotPath)) {
    console.error('Cannot find recruiting-playbook.pot');
    process.exit(1);
  }
}

const potContent = fs.readFileSync(potPath, 'utf-8');
const entries = parsePot(potContent);
console.log(`Parsed ${entries.length} entries from .pot`);

const transContent = fs.readFileSync(transFile, 'utf-8');
const translations = JSON.parse(transContent);
console.log(`Loaded ${Object.keys(translations).length} translations from JSON`);

const poContent = generatePo(locale, entries, translations);

const outPath = path.join(__dirname, '..', `recruiting-playbook-${locale}.po`);
fs.writeFileSync(outPath, poContent, 'utf-8');
console.log(`Written to: ${outPath}`);
