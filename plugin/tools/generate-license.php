<?php
/**
 * Lizenzschlüssel-Generator (CLI Tool)
 *
 * Generiert gültige Lizenzschlüssel für das Recruiting Playbook Plugin.
 * Die Schlüssel verwenden HMAC-SHA256 für die Checksum-Validierung.
 *
 * Verwendung:
 *   php generate-license.php TIER [ANZAHL]
 *
 * Argumente:
 *   TIER    - Lizenz-Tier: PRO, AI oder BUNDLE (erforderlich)
 *   ANZAHL  - Anzahl zusätzlicher Schlüssel (1-100, optional)
 *
 * Beispiele:
 *   php generate-license.php PRO          - Einen Pro-Schlüssel erstellen
 *   php generate-license.php AI           - Einen AI-Addon-Schlüssel erstellen
 *   php generate-license.php BUNDLE       - Einen Bundle-Schlüssel erstellen
 *   php generate-license.php PRO 10       - 11 Pro-Schlüssel erstellen (1 + 10)
 *
 * Umgebungsvariablen:
 *   RP_LICENSE_SECRET - Secret für HMAC (muss mit wp-config.php übereinstimmen)
 *
 * @package RecruitingPlaybook
 */

// CLI-only check.
if ( php_sapi_name() !== 'cli' ) {
	die( 'Dieses Tool kann nur über die Kommandozeile ausgeführt werden.' . PHP_EOL );
}

// License Secret (muss mit RP_LICENSE_SECRET in wp-config.php übereinstimmen).
$license_secret = getenv( 'RP_LICENSE_SECRET' ) ?: 'rp-default-license-secret-change-in-production';

// Tier aus Argument lesen.
$tier = strtoupper( $argv[1] ?? '' );

$valid_tiers = array( 'PRO', 'AI', 'BUNDLE' );

if ( empty( $tier ) || ! in_array( $tier, $valid_tiers, true ) ) {
	echo 'Verwendung: php generate-license.php [TIER]' . PHP_EOL;
	echo 'Verfügbare Tiers: ' . implode( ', ', $valid_tiers ) . PHP_EOL;
	echo PHP_EOL;
	echo 'Beispiele:' . PHP_EOL;
	echo '  php generate-license.php PRO     - Erstellt einen Pro-Lizenzschlüssel' . PHP_EOL;
	echo '  php generate-license.php AI      - Erstellt einen AI-Addon-Lizenzschlüssel' . PHP_EOL;
	echo '  php generate-license.php BUNDLE  - Erstellt einen Bundle-Lizenzschlüssel' . PHP_EOL;
	exit( 1 );
}

/**
 * Generiert einen zufälligen 4-Zeichen-Block
 *
 * @return string 4 alphanumerische Zeichen.
 */
function generate_random_block(): string {
	// Keine verwechselbaren Zeichen (I, O, 0, 1).
	$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	$block = '';

	for ( $i = 0; $i < 4; $i++ ) {
		$block .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
	}

	return $block;
}

/**
 * Generiert einen vollständigen Lizenzschlüssel
 *
 * @param string $tier   Tier-Name (PRO, AI, BUNDLE).
 * @param string $secret License Secret für HMAC.
 * @return string Vollständiger Lizenzschlüssel.
 */
function generate_license_key( string $tier, string $secret ): string {
	$prefix = "RP-{$tier}";

	// 4 zufällige Blöcke.
	$blocks = array();
	for ( $i = 0; $i < 4; $i++ ) {
		$blocks[] = generate_random_block();
	}

	$payload = $prefix . '-' . implode( '-', $blocks );

	// HMAC-SHA256 Checksum (erste 4 Hex-Zeichen).
	$checksum = strtoupper( substr( hash_hmac( 'sha256', $payload, $secret ), 0, 4 ) );

	return $payload . '-' . $checksum;
}

/**
 * Validiert einen Lizenzschlüssel
 *
 * @param string $key    Lizenzschlüssel.
 * @param string $secret License Secret für HMAC.
 * @return bool True wenn gültig.
 */
function validate_license_key( string $key, string $secret ): bool {
	// Format prüfen.
	$pattern = '/^RP-(PRO|AI|BUNDLE)-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/';

	if ( ! preg_match( $pattern, $key ) ) {
		return false;
	}

	// HMAC-SHA256 Checksum prüfen.
	$checksum = substr( $key, -4 );
	$payload  = substr( $key, 0, -5 );

	$calculated = strtoupper( substr( hash_hmac( 'sha256', $payload, $secret ), 0, 4 ) );

	return hash_equals( $checksum, $calculated );
}

// Lizenzschlüssel generieren.
$license_key = generate_license_key( $tier, $license_secret );

// Validierung testen.
$is_valid = validate_license_key( $license_key, $license_secret );

// Ausgabe.
echo PHP_EOL;
echo '========================================' . PHP_EOL;
echo '  Recruiting Playbook Lizenzschlüssel' . PHP_EOL;
echo '========================================' . PHP_EOL;
echo PHP_EOL;
echo 'Tier: ' . $tier . PHP_EOL;
echo PHP_EOL;
echo 'Lizenzschlüssel:' . PHP_EOL;
echo $license_key . PHP_EOL;
echo PHP_EOL;
echo 'Validierung: ' . ( $is_valid ? 'OK' : 'FEHLER' ) . PHP_EOL;
echo PHP_EOL;

// Optional: Mehrere Schlüssel generieren.
if ( isset( $argv[2] ) ) {
	if ( ! is_numeric( $argv[2] ) ) {
		echo 'WARNUNG: Ungültige Anzahl "' . $argv[2] . '" - muss eine Zahl sein.' . PHP_EOL;
		echo PHP_EOL;
	} else {
		$requested = (int) $argv[2];
		$count     = max( 1, min( $requested, 100 ) ); // Min 1, Max 100.

		if ( $requested > 100 ) {
			echo 'HINWEIS: Maximal 100 Schlüssel erlaubt, generiere ' . $count . '.' . PHP_EOL;
		}

		echo 'Weitere Schlüssel (' . $count . '):' . PHP_EOL;
		echo '----------------------------------------' . PHP_EOL;

		for ( $i = 0; $i < $count; $i++ ) {
			echo generate_license_key( $tier, $license_secret ) . PHP_EOL;
		}

		echo PHP_EOL;
	}
}

exit( 0 );
