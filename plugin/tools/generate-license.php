<?php
/**
 * Lizenzschlüssel-Generator (CLI Tool)
 *
 * Verwendung:
 *   php generate-license.php PRO
 *   php generate-license.php AI
 *   php generate-license.php BUNDLE
 *
 * @package RecruitingPlaybook
 */

// CLI-only check.
if ( php_sapi_name() !== 'cli' ) {
	die( 'Dieses Tool kann nur über die Kommandozeile ausgeführt werden.' . PHP_EOL );
}

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
 * @param string $tier Tier-Name (PRO, AI, BUNDLE).
 * @return string Vollständiger Lizenzschlüssel.
 */
function generate_license_key( string $tier ): string {
	$prefix = "RP-{$tier}";

	// 4 zufällige Blöcke.
	$blocks = array();
	for ( $i = 0; $i < 4; $i++ ) {
		$blocks[] = generate_random_block();
	}

	$payload = $prefix . '-' . implode( '-', $blocks );

	// Checksum berechnen (CRC32, erste 4 Hex-Zeichen).
	$checksum = strtoupper( substr( dechex( crc32( $payload ) ), 0, 4 ) );

	return $payload . '-' . $checksum;
}

/**
 * Validiert einen Lizenzschlüssel
 *
 * @param string $key Lizenzschlüssel.
 * @return bool True wenn gültig.
 */
function validate_license_key( string $key ): bool {
	// Format prüfen.
	$pattern = '/^RP-(PRO|AI|BUNDLE)-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/';

	if ( ! preg_match( $pattern, $key ) ) {
		return false;
	}

	// Checksum prüfen.
	$checksum = substr( $key, -4 );
	$payload  = substr( $key, 0, -5 );

	$calculated = strtoupper( substr( dechex( crc32( $payload ) ), 0, 4 ) );

	return $checksum === $calculated;
}

// Lizenzschlüssel generieren.
$license_key = generate_license_key( $tier );

// Validierung testen.
$is_valid = validate_license_key( $license_key );

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
if ( isset( $argv[2] ) && is_numeric( $argv[2] ) ) {
	$count = min( (int) $argv[2], 100 );

	echo 'Weitere Schlüssel (' . $count . '):' . PHP_EOL;
	echo '----------------------------------------' . PHP_EOL;

	for ( $i = 0; $i < $count; $i++ ) {
		echo generate_license_key( $tier ) . PHP_EOL;
	}

	echo PHP_EOL;
}

exit( 0 );
