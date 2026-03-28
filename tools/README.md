# WordPress.org Compliance Test Tool

Automatisierter Test für WordPress.org Plugin Review Compliance basierend auf der Review-Mail vom 26. März 2026.

## Features

Testet automatisch:

1. **Trialware / Locked Features** (Guideline 5)
   - Premium-Dateien im Free-ZIP
   - `rp_can()` Feature-Gates
   - `is__premium_only()` Runtime-Checks
   - Premium-Feature Keywords

2. **REST API Permission Callbacks**
   - `get_company` Permissions
   - Alle Controller-Endpoints

3. **Freemius SDK Version**
   - Version >= 2.13.1

4. **External Services Documentation**
   - readme.txt External Services Sektion
   - Dokumentierte URLs
   - Terms/Privacy Links

5. **Prefixing (4+ Zeichen)**
   - Funktionen
   - Konstanten

6. **Additional Checks**
   - WP_DEBUG Kompatibilität
   - Debug-Statements
   - Unsafe DB Queries
   - Nonce Verification

## Usage

### Linux / macOS / Git Bash

```bash
cd tools
chmod +x wordpress-org-compliance-test.sh
./wordpress-org-compliance-test.sh path/to/recruiting-playbook-free.1.3.0.zip
```

### Windows (WSL / Git Bash)

```bash
cd tools
bash wordpress-org-compliance-test.sh "c:\Users\...\recruiting-playbook-free.1.3.0.zip"
```

### In CI/CD Pipeline

```yaml
# GitHub Actions
- name: WordPress.org Compliance Test
  run: |
    chmod +x tools/wordpress-org-compliance-test.sh
    tools/wordpress-org-compliance-test.sh recruiting-playbook-free.zip
```

## Output

### Passed Test
```
========================================
TEST SUMMARY
========================================
✓ Passed:   45
✗ Failed:   0
⚠ Warnings: 2
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Total:     47

✅ COMPLIANCE TEST PASSED
The plugin meets WordPress.org guidelines!
```

**Exit Code:** 0

### Failed Test
```
========================================
TEST SUMMARY
========================================
✓ Passed:   32
✗ Failed:   8
⚠ Warnings: 5
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Total:     45

❌ COMPLIANCE TEST FAILED
The plugin does NOT meet WordPress.org guidelines.
```

**Exit Code:** 1

## Exit Codes

- `0` - Alle Tests bestanden (oder nur minor Warnings)
- `1` - Mindestens ein kritischer Test fehlgeschlagen

## Workflow Integration

### Nach jedem Freemius Deploy

```bash
# 1. Freemius Deploy triggern
git tag v1.3.0 && git push origin v1.3.0

# 2. Warten bis GitHub Action durch ist
# 3. Free-Version von Freemius Dashboard downloaden

# 4. Compliance Test ausführen
cd tools
./wordpress-org-compliance-test.sh ~/Downloads/recruiting-playbook-free.1.3.0.zip

# 5. Wenn PASSED → Zu WordPress.org hochladen
# 6. Wenn FAILED → Fehler fixen und neu deployen
```

### Docker Integration

```bash
# Test im Docker Container ausführen
docker cp recruiting-playbook-free.zip devcontainer-wordpress-1:/tmp/
docker exec devcontainer-wordpress-1 bash /workspace/tools/wordpress-org-compliance-test.sh /tmp/recruiting-playbook-free.zip
```

## Test-Kategorien

### 🔴 CRITICAL (Must Fix)

Diese Tests müssen bestehen, sonst lehnt WordPress.org ab:

- Premium-Dateien im Free-ZIP
- REST API Permissions
- Freemius SDK Version veraltet
- Fehlende External Services Doku

### ⚠️ WARNING (Should Fix)

Diese Warnings sollten geprüft werden:

- `rp_can()` Checks (OK wenn Features entfernt)
- Debug-Statements
- Unsafe DB Queries
- Prefixing Issues

## Troubleshooting

### "Plugin directory not found"

ZIP-Struktur prüfen:
```bash
unzip -l recruiting-playbook-free.zip | head -20
```

Sollte sein:
```
recruiting-playbook/
  recruiting-playbook.php
  src/
  assets/
  ...
```

### "Permission denied"

Script ausführbar machen:
```bash
chmod +x wordpress-org-compliance-test.sh
```

### Test schlägt fehl aber Issue ist gefixt

Cache leeren und neu downloaden:
```bash
# Freemius Dashboard → Download Free Version (neu)
# Nicht gecachte Version verwenden
```

## Custom Checks hinzufügen

Script erweitern:

```bash
# tools/wordpress-org-compliance-test.sh

################################################################################
# TEST 7: My Custom Check
################################################################################
print_header "TEST 7: My Custom Check"

print_test "Checking something specific"

if [ some_condition ]; then
    print_pass "Custom check passed"
else
    print_fail "Custom check failed"
fi
```

## Reports speichern

```bash
# Report in Datei schreiben
./wordpress-org-compliance-test.sh plugin.zip > report-$(date +%Y%m%d).txt 2>&1

# Nur Fehler anzeigen
./wordpress-org-compliance-test.sh plugin.zip 2>&1 | grep "FAIL"

# Nur Summary anzeigen
./wordpress-org-compliance-test.sh plugin.zip 2>&1 | tail -20
```

## Automatisierung

### Pre-Deploy Hook

```bash
# .git/hooks/pre-push
#!/bin/bash
if [[ $(git describe --tags) =~ ^v[0-9] ]]; then
    echo "Tag detected - running compliance test..."
    # Test gegen letzte Free-Version
    ./tools/wordpress-org-compliance-test.sh latest-free.zip
    if [ $? -ne 0 ]; then
        echo "❌ Compliance test failed - push aborted"
        exit 1
    fi
fi
```

### GitHub Action

```yaml
name: WordPress.org Compliance

on:
  push:
    tags:
      - 'v*'

jobs:
  compliance-test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Download Free Version from Freemius
        run: |
          # Freemius API call to download Free version
          # ...

      - name: Run Compliance Test
        run: |
          chmod +x tools/wordpress-org-compliance-test.sh
          ./tools/wordpress-org-compliance-test.sh recruiting-playbook-free.zip

      - name: Upload Report
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: compliance-report
          path: tools/test-report.txt
```

## Known Issues

### False Positives

- **rp_can() Warnings:** OK wenn Premium-Code entfernt ist
- **Keyword Warnings:** OK in Kommentaren oder Variablennamen
- **Prefixing:** OK wenn in Namespace (PSR-4)

### Limitations

- Kann nicht testen ob `is__premium_only()` Code wirklich entfernt wurde (nur heuristisch)
- Kann keine komplexe logische Feature-Gates erkennen
- Freemius SDK Version Check basiert auf composer.json (nicht Runtime)

## Support

Bei Fragen oder Problemen:

1. Check Script-Ausgabe für Details
2. Prüfe WordPress.org Guidelines: https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
3. Vergleiche mit Freemius Dokumentation: https://freemius.com/help/documentation/

---

**Version:** 1.0
**Erstellt:** 28. März 2026
**Basis:** WordPress.org Review Mail vom 26. März 2026
