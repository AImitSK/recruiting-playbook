#!/usr/bin/env python3
"""
Merge per-source-file JSON translation files into per-bundle JSON files.

WordPress wp i18n make-json creates one JSON per source file reference,
but wp_set_script_translations() looks for ONE JSON per built script bundle.
This script merges the individual JSONs into the correct bundle JSONs.

Usage (from plugin/ directory):
    python3 tools/merge-json-translations.py [languages_dir]

Default languages_dir: ./languages
"""
import json
import os
import sys
import hashlib
import glob

# Webpack entry points: name -> (output_path, include_prefixes, exclude_prefixes, extra_sources)
WEBPACK_ENTRIES = {
    "admin": {
        "output": "assets/dist/js/admin.js",
        "include": ["assets/src/js/admin/"],
        "exclude": ["assets/src/js/admin/email/", "assets/src/js/admin/form-builder/"],
        "extra": ["assets/dist/js/a.js"],  # webpack shared chunk
    },
    # These already get correct JSONs from make-json (source = built file),
    # but we merge them too for consistency and to include any split-out source refs.
    "admin-email": {
        "output": "assets/dist/js/admin-email.js",
        "include": ["assets/src/js/admin/email/", "assets/dist/js/admin-email.js"],
        "exclude": [],
        "extra": [],
    },
    "admin-form-builder": {
        "output": "assets/dist/js/admin-form-builder.js",
        "include": ["assets/src/js/admin/form-builder/", "assets/dist/js/admin-form-builder.js"],
        "exclude": [],
        "extra": [],
    },
    "blocks": {
        "output": "assets/dist/js/blocks.js",
        "include": ["assets/src/js/blocks/", "assets/dist/js/blocks.js"],
        "exclude": [],
        "extra": [],
    },
}

DOMAIN = "recruiting-playbook"


def find_locales(lang_dir):
    """Find all locales from existing JSON files."""
    locales = set()
    for f in glob.glob(os.path.join(lang_dir, f"{DOMAIN}-*-*.json")):
        base = os.path.basename(f)
        parts = base.replace(f"{DOMAIN}-", "").replace(".json", "")
        segments = parts.rsplit("-", 1)
        if len(segments) == 2 and len(segments[1]) == 32:
            locales.add(segments[0])
    return sorted(locales)


def source_matches_entry(source, entry):
    """Check if a JSON source path belongs to a webpack entry."""
    for prefix in entry["include"]:
        if source.startswith(prefix):
            for ex in entry["exclude"]:
                if source.startswith(ex):
                    return False
            return True
    if source in entry.get("extra", []):
        return True
    return False


def merge_for_entry(lang_dir, entry_name, entry_config, locales):
    """Merge all matching JSONs into one per locale for a webpack entry."""
    target_hash = hashlib.md5(entry_config["output"].encode()).hexdigest()
    results = {}

    for locale in locales:
        pattern = os.path.join(lang_dir, f"{DOMAIN}-{locale}-*.json")
        json_files = glob.glob(pattern)

        merged_messages = {"": {"domain": "messages", "lang": locale}}
        source_count = 0
        string_count = 0

        for jf in json_files:
            try:
                with open(jf, "r", encoding="utf-8") as f:
                    data = json.load(f)
                source = data.get("source", "")

                if source_matches_entry(source, entry_config):
                    msgs = data.get("locale_data", {}).get("messages", {})
                    for k, v in msgs.items():
                        if k:  # skip header entry
                            merged_messages[k] = v
                            string_count += 1
                    source_count += 1
            except (json.JSONDecodeError, OSError):
                pass

        if string_count > 0:
            merged = {
                "translation-revision-date": "2026-02-23 15:00+0000",
                "generator": "merge-json-translations.py",
                "source": entry_config["output"],
                "domain": "messages",
                "locale_data": {
                    "messages": merged_messages
                }
            }

            out_file = os.path.join(lang_dir, f"{DOMAIN}-{locale}-{target_hash}.json")
            with open(out_file, "w", encoding="utf-8") as f:
                json.dump(merged, f, ensure_ascii=False)

            results[locale] = (source_count, string_count)

    return target_hash, results


def main():
    lang_dir = sys.argv[1] if len(sys.argv) > 1 else "languages"

    if not os.path.isdir(lang_dir):
        print(f"Error: Directory '{lang_dir}' not found.", file=sys.stderr)
        sys.exit(1)

    locales = find_locales(lang_dir)
    if not locales:
        print(f"No JSON translation files found in '{lang_dir}'.")
        sys.exit(0)

    print(f"Found {len(locales)} locales: {', '.join(locales)}")
    print()

    total_files = 0
    for entry_name, entry_config in WEBPACK_ENTRIES.items():
        target_hash, results = merge_for_entry(lang_dir, entry_name, entry_config, locales)
        if results:
            first_locale = next(iter(results.values()))
            print(f"  {entry_name} -> {entry_config['output']}")
            print(f"    Hash: {target_hash}")
            print(f"    Strings: {first_locale[1]} (from {first_locale[0]} sources)")
            print(f"    Locales: {len(results)}")
            total_files += len(results)
        else:
            print(f"  {entry_name}: No matching translations found")

    print(f"\nCreated {total_files} merged JSON files.")


if __name__ == "__main__":
    main()
