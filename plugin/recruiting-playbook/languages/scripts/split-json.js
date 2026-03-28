#!/usr/bin/env node
/**
 * split-json.js — Splits msgids-template.json into N chunks
 *
 * Usage: node split-json.js [chunks] [input-file]
 *   chunks: number of chunks (default: 4)
 *   input-file: JSON file to split (default: msgids-template.json)
 *
 * Output: chunk-1.json, chunk-2.json, ..., chunk-N.json
 */

const fs = require('fs');
const path = require('path');

const numChunks = parseInt(process.argv[2]) || 4;
const inputFile = process.argv[3] || path.join(__dirname, 'msgids-template.json');

const data = JSON.parse(fs.readFileSync(inputFile, 'utf-8'));
const keys = Object.keys(data);
const chunkSize = Math.ceil(keys.length / numChunks);

console.log(`Splitting ${keys.length} entries into ${numChunks} chunks (~${chunkSize} each)`);

for (let i = 0; i < numChunks; i++) {
  const start = i * chunkSize;
  const end = Math.min(start + chunkSize, keys.length);
  const chunk = {};

  for (let j = start; j < end; j++) {
    chunk[keys[j]] = data[keys[j]];
  }

  const outPath = path.join(__dirname, `chunk-${i + 1}.json`);
  fs.writeFileSync(outPath, JSON.stringify(chunk, null, 2), 'utf-8');
  console.log(`  chunk-${i + 1}.json: ${Object.keys(chunk).length} entries`);
}

console.log('Done.');
