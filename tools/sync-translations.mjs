#!/usr/bin/env node
/**
 * Synchronisiert PO-Dateien mit der POT-Vorlage
 *
 * Fügt neue Strings aus der POT hinzu, behält bestehende Übersetzungen bei.
 *
 * Usage: node tools/sync-translations.mjs
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import gettextParser from 'gettext-parser';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const LANGUAGES_DIR = path.join(__dirname, '../plugin/languages');
const POT_FILE = path.join(LANGUAGES_DIR, 'recruiting-playbook.pot');

// Alle PO-Dateien finden
function findPoFiles() {
    const files = fs.readdirSync(LANGUAGES_DIR);
    return files
        .filter(f => f.endsWith('.po') && !f.includes('.pot'))
        .map(f => path.join(LANGUAGES_DIR, f));
}

// POT-Datei parsen
function parsePot() {
    const content = fs.readFileSync(POT_FILE, 'utf8');
    return gettextParser.po.parse(content);
}

// PO-Datei parsen
function parsePo(filePath) {
    const content = fs.readFileSync(filePath, 'utf8');
    return gettextParser.po.parse(content);
}

// PO mit POT synchronisieren
function syncPoWithPot(po, pot) {
    const potTranslations = pot.translations[''] || {};
    const poTranslations = po.translations[''] || {};

    let added = 0;
    let kept = 0;
    let removed = 0;

    // Neue Translations-Objekt erstellen
    const newTranslations = { '': {} };

    // Header beibehalten
    if (poTranslations['']) {
        newTranslations[''][''] = poTranslations[''];
    } else if (potTranslations['']) {
        newTranslations[''][''] = { ...potTranslations[''] };
    }

    // Alle POT-Strings durchgehen
    for (const [msgid, potEntry] of Object.entries(potTranslations)) {
        if (msgid === '') continue; // Header überspringen

        if (poTranslations[msgid]) {
            // Bestehende Übersetzung beibehalten
            newTranslations[''][msgid] = {
                ...potEntry,
                msgstr: poTranslations[msgid].msgstr
            };
            kept++;
        } else {
            // Neuer String aus POT
            newTranslations[''][msgid] = {
                ...potEntry,
                msgstr: potEntry.msgid_plural
                    ? ['', ''] // Plural: leere Übersetzungen
                    : ['']     // Singular: leere Übersetzung
            };
            added++;
        }
    }

    // Zählen wie viele Strings entfernt wurden (in PO aber nicht in POT)
    for (const msgid of Object.keys(poTranslations)) {
        if (msgid !== '' && !potTranslations[msgid]) {
            removed++;
        }
    }

    po.translations = newTranslations;

    return { added, kept, removed };
}

// PO-Datei speichern
function savePo(filePath, po) {
    const output = gettextParser.po.compile(po);
    fs.writeFileSync(filePath, output);
}

// MO-Datei generieren
function generateMo(poFilePath) {
    const moFilePath = poFilePath.replace('.po', '.mo');
    const po = parsePo(poFilePath);
    const mo = gettextParser.mo.compile(po);
    fs.writeFileSync(moFilePath, mo);
    return moFilePath;
}

// Hauptfunktion
function main() {
    console.log('🔄 Synchronisiere Übersetzungen mit POT...\n');

    // POT laden
    const pot = parsePot();
    const potCount = Object.keys(pot.translations[''] || {}).length - 1; // -1 für Header
    console.log(`📋 POT enthält ${potCount} Strings\n`);

    // Alle PO-Dateien verarbeiten
    const poFiles = findPoFiles();

    for (const poFile of poFiles) {
        const filename = path.basename(poFile);
        const po = parsePo(poFile);

        // Synchronisieren
        const stats = syncPoWithPot(po, pot);

        // Speichern
        savePo(poFile, po);

        // MO generieren
        const moFile = generateMo(poFile);

        console.log(`✅ ${filename}`);
        console.log(`   ➕ ${stats.added} neue Strings`);
        console.log(`   ✓  ${stats.kept} beibehalten`);
        if (stats.removed > 0) {
            console.log(`   ➖ ${stats.removed} entfernt (nicht mehr in POT)`);
        }
        console.log('');
    }

    console.log('✨ Fertig! Alle PO/MO-Dateien wurden aktualisiert.');
}

main();
