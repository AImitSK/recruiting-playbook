<?php
/**
 * ID-Mapping für Import (alte IDs → neue IDs)
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Import;

defined( 'ABSPATH' ) || exit;

/**
 * ID-Mapper Klasse
 */
class IdMapper {

	/**
	 * Mapping-Speicher: type => [ old_id => new_id ]
	 *
	 * @var array<string, array<int, int>>
	 */
	private array $map = [];

	/**
	 * Mapping hinzufügen
	 *
	 * @param string $type    Typ (z.B. 'job', 'candidate', 'application').
	 * @param int    $old_id  Alte ID.
	 * @param int    $new_id  Neue ID.
	 */
	public function add( string $type, int $old_id, int $new_id ): void {
		$this->map[ $type ][ $old_id ] = $new_id;
	}

	/**
	 * Neue ID abrufen
	 *
	 * @param string $type   Typ.
	 * @param int    $old_id Alte ID.
	 * @return int|null Neue ID oder null.
	 */
	public function get( string $type, int $old_id ): ?int {
		return $this->map[ $type ][ $old_id ] ?? null;
	}

	/**
	 * Alle Mappings eines Typs abrufen
	 *
	 * @param string $type Typ.
	 * @return array<int, int>
	 */
	public function getAll( string $type ): array {
		return $this->map[ $type ] ?? [];
	}

	/**
	 * Prüfen ob ein Mapping existiert
	 *
	 * @param string $type   Typ.
	 * @param int    $old_id Alte ID.
	 * @return bool
	 */
	public function has( string $type, int $old_id ): bool {
		return isset( $this->map[ $type ][ $old_id ] );
	}
}
