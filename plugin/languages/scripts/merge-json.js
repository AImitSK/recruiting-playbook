#!/usr/bin/env node
/**
 * merge-json.js — Merges translated chunk files into a single translations JSON
 *
 * Usage: node merge-json.js <locale> <chunk1.json> <chunk2.json> ...
 *
 * Output: translations-<locale>.json
 */

const fs = require('fs');
const path = require('path');

const args = process.argv.slice(2);
if (args.length < 2) {
  console.log('Usage: node merge-json.js <locale> <chunk1.json> [chunk2.json] ...');
  process.exit(1);
}

const locale = args[0];
const chunkFiles = args.slice(1);
const merged = {};

for (const file of chunkFiles) {
  const filePath = path.isAbsolute(file) ? file : path.join(__dirname, file);
  if (!fs.existsSync(filePath)) {
    console.error(`File not found: ${filePath}`);
    process.exit(1);
  }

  const data = JSON.parse(fs.readFileSync(filePath, 'utf-8'));
  const count = Object.keys(data).length;
  Object.assign(merged, data);
  console.log(`  ${path.basename(file)}: ${count} entries`);
}

const outPath = path.join(__dirname, `translations-${locale}.json`);
fs.writeFileSync(outPath, JSON.stringify(merged, null, 2), 'utf-8');
console.log(`\nMerged ${Object.keys(merged).length} translations to ${path.basename(outPath)}`);
