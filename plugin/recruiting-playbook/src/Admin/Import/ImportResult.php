<?php
/**
 * Import-Ergebnis Statistiken
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Import;

defined( 'ABSPATH' ) || exit;

/**
 * Import-Ergebnis Klasse
 */
class ImportResult {

	/**
	 * Zähler: type => [ created, skipped, updated ]
	 *
	 * @var array<string, array<string, int>>
	 */
	private array $counts = [];

	/**
	 * Warnungen
	 *
	 * @var string[]
	 */
	private array $warnings = [];

	/**
	 * Fehler
	 *
	 * @var string[]
	 */
	private array $errors = [];

	/**
	 * Erstellt-Zähler erhöhen
	 *
	 * @param string $type Typ (z.B. 'jobs', 'candidates').
	 * @param int    $count Anzahl.
	 */
	public function addCreated( string $type, int $count = 1 ): void {
		$this->ensureType( $type );
		$this->counts[ $type ]['created'] += $count;
	}

	/**
	 * Übersprungen-Zähler erhöhen
	 *
	 * @param string $type Typ.
	 * @param int    $count Anzahl.
	 */
	public function addSkipped( string $type, int $count = 1 ): void {
		$this->ensureType( $type );
		$this->counts[ $type ]['skipped'] += $count;
	}

	/**
	 * Aktualisiert-Zähler erhöhen
	 *
	 * @param string $type Typ.
	 * @param int    $count Anzahl.
	 */
	public function addUpdated( string $type, int $count = 1 ): void {
		$this->ensureType( $type );
		$this->counts[ $type ]['updated'] += $count;
	}

	/**
	 * Warnung hinzufügen
	 *
	 * @param string $message Warnung.
	 */
	public function addWarning( string $message ): void {
		$this->warnings[] = $message;
	}

	/**
	 * Fehler hinzufügen
	 *
	 * @param string $message Fehler.
	 */
	public function addError( string $message ): void {
		$this->errors[] = $message;
	}

	/**
	 * Ergebnis als Array
	 *
	 * @return array
	 */
	public function toArray(): array {
		return [
			'counts'   => $this->counts,
			'warnings' => $this->warnings,
			'errors'   => $this->errors,
		];
	}

	/**
	 * Typ-Zähler initialisieren
	 *
	 * @param string $type Typ.
	 */
	private function ensureType( string $type ): void {
		if ( ! isset( $this->counts[ $type ] ) ) {
			$this->counts[ $type ] = [
				'created' => 0,
				'skipped' => 0,
				'updated' => 0,
			];
		}
	}
}
