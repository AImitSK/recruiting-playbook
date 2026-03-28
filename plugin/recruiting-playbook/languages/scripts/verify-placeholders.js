#!/usr/bin/env node
/**
 * verify-placeholders.js — Checks that all %s, %d, %1$s placeholders are preserved
 */
const fs = require('fs');
const path = require('path');

const langDir = path.join(__dirname, '..');
const files = fs.readdirSync(langDir).filter(f => f.endsWith('.po'));

for (const file of files) {
  const content = fs.readFileSync(path.join(langDir, file), 'utf-8');
  const lines = content.split('\n');
  let issues = 0;
  let i = 0;

  while (i < lines.length) {
    if (lines[i].startsWith('msgid "') && lines[i] !== 'msgid ""') {
      const msgidLine = lines[i];
      let msgstr = '';
      let msgid = '';

      // Extract msgid
      const idM = msgidLine.match(/^msgid "(.*)"$/);
      if (idM) msgid = idM[1];
      i++;

      // Skip continuation lines of msgid
      while (i < lines.length && lines[i].startsWith('"')) {
        const m = lines[i].match(/^"(.*)"$/);
        if (m) msgid += m[1];
        i++;
      }

      // Skip msgid_plural
      if (i < lines.length && lines[i].startsWith('msgid_plural')) {
        i++;
        while (i < lines.length && lines[i].startsWith('"')) i++;
      }

      // Extract msgstr
      if (i < lines.length && lines[i].startsWith('msgstr')) {
        const strM = lines[i].match(/^msgstr(?:\[\d\])? "(.*)"$/);
        if (strM) msgstr = strM[1];
        i++;
        while (i < lines.length && lines[i].startsWith('"')) {
          const m = lines[i].match(/^"(.*)"$/);
          if (m) msgstr += m[1];
          i++;
        }
      }

      // Compare placeholders
      if (msgstr && msgid) {
        const phPattern = /%(?:\d+\$)?[sdf]/g;
        const idPhs = (msgid.match(phPattern) || []).sort().join(',');
        const strPhs = (msgstr.match(phPattern) || []).sort().join(',');

        if (idPhs && idPhs !== strPhs) {
          issues++;
        }
      }
    } else {
      i++;
    }
  }

  console.log(`${file}: ${issues} placeholder issues`);
}
