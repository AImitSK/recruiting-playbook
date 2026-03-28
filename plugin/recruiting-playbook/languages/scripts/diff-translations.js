#!/usr/bin/env node
/**
 * diff-translations.js — Finds missing translations (template keys not in existing)
 *
 * Usage: node diff-translations.js <locale>
 *
 * Reads: msgids-template.json + existing-<locale>.json
 * Output: missing-<locale>.json — { "english": "", ... } (only missing keys)
 */

const fs = require('fs');
const path = require('path');

const locale = process.argv[2];
if (!locale) {
  console.log('Usage: node diff-translations.js <locale>');
  process.exit(1);
}

const templatePath = path.join(__dirname, 'msgids-template.json');
const existingPath = path.join(__dirname, `existing-${locale}.json`);

if (!fs.existsSync(templatePath)) {
  console.error('msgids-template.json not found. Run extract-msgids.js first.');
  process.exit(1);
}

if (!fs.existsSync(existingPath)) {
  console.error(`existing-${locale}.json not found. Run extract-existing.js first.`);
  process.exit(1);
}

const template = JSON.parse(fs.readFileSync(templatePath, 'utf-8'));
const existing = JSON.parse(fs.readFileSync(existingPath, 'utf-8'));

const missing = {};
let count = 0;

for (const key of Object.keys(template)) {
  if (!existing[key]) {
    missing[key] = template[key]; // Keep empty string or array format
    count++;
  }
}

const outPath = path.join(__dirname, `missing-${locale}.json`);
fs.writeFileSync(outPath, JSON.stringify(missing, null, 2), 'utf-8');
console.log(`${locale}: ${count} missing strings (of ${Object.keys(template).length} total) → ${path.basename(outPath)}`);
