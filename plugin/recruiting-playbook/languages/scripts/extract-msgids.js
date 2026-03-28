#!/usr/bin/env node
/**
 * extract-msgids.js — Extracts all msgids from a .pot file as JSON template
 * Output: { "english string": "", ... } for singular
 *         { "english string": ["", ""] } for plural (msgid_plural present)
 */

const fs = require('fs');
const path = require('path');

const potPath = path.join(__dirname, '..', 'recruiting-playbook.pot');
const potContent = fs.readFileSync(potPath, 'utf-8');
const lines = potContent.split('\n');

const result = {};
let i = 0;

// Skip header
while (i < lines.length && !(lines[i].startsWith('#:') || lines[i].startsWith('#.'))) {
  i++;
}

function extractString(startIdx) {
  const firstMatch = lines[startIdx].match(/"(.*)"/);
  let result = firstMatch ? firstMatch[1] : '';
  let j = startIdx + 1;
  while (j < lines.length && lines[j].startsWith('"')) {
    const match = lines[j].match(/"(.*)"/);
    if (match) result += match[1];
    j++;
  }
  return { value: result.replace(/\\n/g, '\n').replace(/\\t/g, '\t').replace(/\\"/g, '"').replace(/\\\\/g, '\\'), nextIdx: j };
}

function skipMultiline(startIdx) {
  let j = startIdx + 1;
  while (j < lines.length && lines[j].startsWith('"')) j++;
  return j;
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

    // Check for msgid_plural
    let hasPlural = false;
    if (i < lines.length && lines[i].startsWith('msgid_plural ')) {
      hasPlural = true;
      i = skipMultiline(i);
    }

    // Skip msgstr
    while (i < lines.length && lines[i].startsWith('msgstr')) {
      i = skipMultiline(i);
    }

    if (msgid) {
      const key = msgctxt ? `${msgctxt}\u0004${msgid}` : msgid;
      result[key] = hasPlural ? ['', ''] : '';
    }
  } else {
    i++;
  }
}

const outPath = path.join(__dirname, 'msgids-template.json');
fs.writeFileSync(outPath, JSON.stringify(result, null, 2), 'utf-8');
console.log(`Extracted ${Object.keys(result).length} msgids to ${outPath}`);

// Also output just the keys for reference
const keysPath = path.join(__dirname, 'msgids-list.txt');
const keysList = Object.keys(result).map(k => {
  const isPlural = Array.isArray(result[k]);
  return isPlural ? `[PLURAL] ${k}` : k;
}).join('\n');
fs.writeFileSync(keysPath, keysList, 'utf-8');
console.log(`Key list written to ${keysPath}`);
