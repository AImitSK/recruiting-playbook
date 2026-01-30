<?php
/**
 * Conditional Logic Service
 *
 * Verarbeitet und evaluiert Conditional Logic für Formularfelder.
 *
 * @package RecruitingPlaybook\Services
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;

/**
 * Service für Conditional Logic
 */
class ConditionalLogicService {

	/**
	 * Verfügbare Operatoren
	 *
	 * @var array<string, array>
	 */
	private const OPERATORS = [
		'equals' => [
			'label'         => 'ist gleich',
			'requires_value' => true,
			'field_types'   => [ 'text', 'textarea', 'email', 'phone', 'url', 'select', 'radio', 'number', 'date' ],
		],
		'not_equals' => [
			'label'         => 'ist nicht gleich',
			'requires_value' => true,
			'field_types'   => [ 'text', 'textarea', 'email', 'phone', 'url', 'select', 'radio', 'number', 'date' ],
		],
		'contains' => [
			'label'         => 'enthält',
			'requires_value' => true,
			'field_types'   => [ 'text', 'textarea', 'email', 'phone', 'url' ],
		],
		'not_contains' => [
			'label'         => 'enthält nicht',
			'requires_value' => true,
			'field_types'   => [ 'text', 'textarea', 'email', 'phone', 'url' ],
		],
		'starts_with' => [
			'label'         => 'beginnt mit',
			'requires_value' => true,
			'field_types'   => [ 'text', 'textarea', 'email', 'phone', 'url' ],
		],
		'ends_with' => [
			'label'         => 'endet mit',
			'requires_value' => true,
			'field_types'   => [ 'text', 'textarea', 'email', 'phone', 'url' ],
		],
		'not_empty' => [
			'label'         => 'ist ausgefüllt',
			'requires_value' => false,
			'field_types'   => [ 'text', 'textarea', 'email', 'phone', 'url', 'select', 'radio', 'checkbox', 'number', 'date', 'file' ],
		],
		'empty' => [
			'label'         => 'ist leer',
			'requires_value' => false,
			'field_types'   => [ 'text', 'textarea', 'email', 'phone', 'url', 'select', 'radio', 'checkbox', 'number', 'date', 'file' ],
		],
		'checked' => [
			'label'         => 'ist ausgewählt',
			'requires_value' => false,
			'field_types'   => [ 'checkbox' ],
		],
		'not_checked' => [
			'label'         => 'ist nicht ausgewählt',
			'requires_value' => false,
			'field_types'   => [ 'checkbox' ],
		],
		'greater_than' => [
			'label'         => 'ist größer als',
			'requires_value' => true,
			'field_types'   => [ 'number' ],
		],
		'less_than' => [
			'label'         => 'ist kleiner als',
			'requires_value' => true,
			'field_types'   => [ 'number' ],
		],
		'greater_or_equal' => [
			'label'         => 'ist größer oder gleich',
			'requires_value' => true,
			'field_types'   => [ 'number' ],
		],
		'less_or_equal' => [
			'label'         => 'ist kleiner oder gleich',
			'requires_value' => true,
			'field_types'   => [ 'number' ],
		],
		'in' => [
			'label'         => 'ist einer von',
			'requires_value' => true,
			'field_types'   => [ 'text', 'select', 'radio' ],
		],
		'not_in' => [
			'label'         => 'ist keiner von',
			'requires_value' => true,
			'field_types'   => [ 'text', 'select', 'radio' ],
		],
		'before' => [
			'label'         => 'ist vor',
			'requires_value' => true,
			'field_types'   => [ 'date' ],
		],
		'after' => [
			'label'         => 'ist nach',
			'requires_value' => true,
			'field_types'   => [ 'date' ],
		],
	];

	/**
	 * Verfügbare Operatoren abrufen
	 *
	 * @param string|null $field_type Optional: Nur Operatoren für diesen Feldtyp.
	 * @return array<string, string> Operator => Label.
	 */
	public function getOperators( ?string $field_type = null ): array {
		$operators = [];

		foreach ( self::OPERATORS as $key => $config ) {
			if ( $field_type === null || in_array( $field_type, $config['field_types'], true ) ) {
				$operators[ $key ] = __( $config['label'], 'recruiting-playbook' );
			}
		}

		return $operators;
	}

	/**
	 * Operator-Konfiguration abrufen
	 *
	 * @return array Vollständige Operator-Konfiguration für Frontend.
	 */
	public function getOperatorConfig(): array {
		$config = [];

		foreach ( self::OPERATORS as $key => $data ) {
			$config[ $key ] = [
				'label'          => __( $data['label'], 'recruiting-playbook' ),
				'requires_value' => $data['requires_value'],
				'field_types'    => $data['field_types'],
			];
		}

		return $config;
	}

	/**
	 * Bedingung serverseitig evaluieren
	 *
	 * @param array $conditional Conditional-Konfiguration.
	 * @param array $form_data   Formulardaten.
	 * @return bool True wenn Bedingung erfüllt (Feld soll angezeigt werden).
	 */
	public function evaluate( array $conditional, array $form_data ): bool {
		if ( empty( $conditional ) || empty( $conditional['field'] ) ) {
			return true; // Keine Bedingung = immer anzeigen.
		}

		$field    = $conditional['field'];
		$operator = $conditional['operator'] ?? 'equals';
		$expected = $conditional['value'] ?? '';
		$actual   = $form_data[ $field ] ?? null;

		return $this->evaluateCondition( $actual, $operator, $expected );
	}

	/**
	 * Mehrere Bedingungen evaluieren (AND/OR)
	 *
	 * @param array  $conditions Array von Bedingungen.
	 * @param string $logic      'and' oder 'or'.
	 * @param array  $form_data  Formulardaten.
	 * @return bool True wenn Bedingungen erfüllt.
	 */
	public function evaluateMultiple( array $conditions, string $logic, array $form_data ): bool {
		if ( empty( $conditions ) ) {
			return true;
		}

		$results = [];
		foreach ( $conditions as $condition ) {
			$results[] = $this->evaluate( $condition, $form_data );
		}

		if ( $logic === 'or' ) {
			return in_array( true, $results, true );
		}

		// Default: AND.
		return ! in_array( false, $results, true );
	}

	/**
	 * Einzelne Bedingung evaluieren
	 *
	 * @param mixed  $actual   Tatsächlicher Wert.
	 * @param string $operator Operator.
	 * @param mixed  $expected Erwarteter Wert.
	 * @return bool True wenn Bedingung erfüllt.
	 */
	private function evaluateCondition( $actual, string $operator, $expected ): bool {
		// Null-Werte behandeln.
		$actual_str   = is_array( $actual ) ? '' : (string) ( $actual ?? '' );
		$expected_str = (string) $expected;

		switch ( $operator ) {
			case 'equals':
				return $actual_str === $expected_str;

			case 'not_equals':
				return $actual_str !== $expected_str;

			case 'contains':
				return str_contains( $actual_str, $expected_str );

			case 'not_contains':
				return ! str_contains( $actual_str, $expected_str );

			case 'starts_with':
				return str_starts_with( $actual_str, $expected_str );

			case 'ends_with':
				return str_ends_with( $actual_str, $expected_str );

			case 'not_empty':
				return $this->isNotEmpty( $actual );

			case 'empty':
				return $this->isEmpty( $actual );

			case 'checked':
				return ! empty( $actual ) && $actual !== '0' && $actual !== 'false';

			case 'not_checked':
				return empty( $actual ) || $actual === '0' || $actual === 'false';

			case 'greater_than':
				return is_numeric( $actual ) && floatval( $actual ) > floatval( $expected );

			case 'less_than':
				return is_numeric( $actual ) && floatval( $actual ) < floatval( $expected );

			case 'greater_or_equal':
				return is_numeric( $actual ) && floatval( $actual ) >= floatval( $expected );

			case 'less_or_equal':
				return is_numeric( $actual ) && floatval( $actual ) <= floatval( $expected );

			case 'in':
				$values = array_map( 'trim', explode( ',', $expected_str ) );
				return in_array( $actual_str, $values, true );

			case 'not_in':
				$values = array_map( 'trim', explode( ',', $expected_str ) );
				return ! in_array( $actual_str, $values, true );

			case 'before':
				return $this->compareDates( $actual_str, $expected_str, '<' );

			case 'after':
				return $this->compareDates( $actual_str, $expected_str, '>' );

			default:
				return true;
		}
	}

	/**
	 * Prüfen ob Wert leer ist
	 *
	 * @param mixed $value Wert.
	 * @return bool True wenn leer.
	 */
	private function isEmpty( $value ): bool {
		if ( $value === null || $value === '' || $value === [] ) {
			return true;
		}

		if ( is_array( $value ) ) {
			return empty( array_filter( $value, fn( $v ) => $v !== '' && $v !== null ) );
		}

		return false;
	}

	/**
	 * Prüfen ob Wert nicht leer ist
	 *
	 * @param mixed $value Wert.
	 * @return bool True wenn nicht leer.
	 */
	private function isNotEmpty( $value ): bool {
		return ! $this->isEmpty( $value );
	}

	/**
	 * Datumswerte vergleichen
	 *
	 * @param string $date1    Erstes Datum.
	 * @param string $date2    Zweites Datum.
	 * @param string $operator Vergleichsoperator.
	 * @return bool Vergleichsergebnis.
	 */
	private function compareDates( string $date1, string $date2, string $operator ): bool {
		$ts1 = strtotime( $date1 );
		$ts2 = strtotime( $date2 );

		if ( $ts1 === false || $ts2 === false ) {
			return false;
		}

		return match ( $operator ) {
			'<'  => $ts1 < $ts2,
			'>'  => $ts1 > $ts2,
			'<=' => $ts1 <= $ts2,
			'>=' => $ts1 >= $ts2,
			'==' => $ts1 === $ts2,
			default => false,
		};
	}

	/**
	 * Alpine.js Expression für eine Bedingung generieren
	 *
	 * @param array $conditional Conditional-Konfiguration.
	 * @return string Alpine.js Expression.
	 */
	public function toAlpineExpression( array $conditional ): string {
		if ( empty( $conditional ) || empty( $conditional['field'] ) ) {
			return 'true';
		}

		$field    = $conditional['field'];
		$operator = $conditional['operator'] ?? 'equals';
		$value    = $conditional['value'] ?? '';

		$field_ref = "formData.{$field}";
		$escaped   = addslashes( $value );

		return match ( $operator ) {
			'equals'           => sprintf( "%s === '%s'", $field_ref, $escaped ),
			'not_equals'       => sprintf( "%s !== '%s'", $field_ref, $escaped ),
			'contains'         => sprintf( "(%s || '').includes('%s')", $field_ref, $escaped ),
			'not_contains'     => sprintf( "!(%s || '').includes('%s')", $field_ref, $escaped ),
			'starts_with'      => sprintf( "(%s || '').startsWith('%s')", $field_ref, $escaped ),
			'ends_with'        => sprintf( "(%s || '').endsWith('%s')", $field_ref, $escaped ),
			'not_empty'        => sprintf( "!!%s && %s !== ''", $field_ref, $field_ref ),
			'empty'            => sprintf( "!%s || %s === ''", $field_ref, $field_ref ),
			'checked'          => sprintf( "!!%s && %s !== '0' && %s !== false", $field_ref, $field_ref, $field_ref ),
			'not_checked'      => sprintf( "!%s || %s === '0' || %s === false", $field_ref, $field_ref, $field_ref ),
			'greater_than'     => sprintf( "parseFloat(%s || 0) > %s", $field_ref, floatval( $value ) ),
			'less_than'        => sprintf( "parseFloat(%s || 0) < %s", $field_ref, floatval( $value ) ),
			'greater_or_equal' => sprintf( "parseFloat(%s || 0) >= %s", $field_ref, floatval( $value ) ),
			'less_or_equal'    => sprintf( "parseFloat(%s || 0) <= %s", $field_ref, floatval( $value ) ),
			'in'               => sprintf( "%s.includes(%s)", wp_json_encode( array_map( 'trim', explode( ',', $value ) ) ), $field_ref ),
			'not_in'           => sprintf( "!%s.includes(%s)", wp_json_encode( array_map( 'trim', explode( ',', $value ) ) ), $field_ref ),
			'before'           => sprintf( "new Date(%s) < new Date('%s')", $field_ref, $escaped ),
			'after'            => sprintf( "new Date(%s) > new Date('%s')", $field_ref, $escaped ),
			default            => 'true',
		};
	}

	/**
	 * Alpine.js Expression für mehrere Bedingungen generieren
	 *
	 * @param array  $conditions Array von Bedingungen.
	 * @param string $logic      'and' oder 'or'.
	 * @return string Alpine.js Expression.
	 */
	public function toAlpineExpressionMultiple( array $conditions, string $logic = 'and' ): string {
		if ( empty( $conditions ) ) {
			return 'true';
		}

		$expressions = [];
		foreach ( $conditions as $condition ) {
			$expressions[] = '(' . $this->toAlpineExpression( $condition ) . ')';
		}

		$connector = $logic === 'or' ? ' || ' : ' && ';

		return implode( $connector, $expressions );
	}

	/**
	 * Felder filtern basierend auf Conditional Logic
	 *
	 * Gibt nur die Felder zurück, deren Bedingungen erfüllt sind.
	 *
	 * @param FieldDefinition[] $fields    Felddefinitionen.
	 * @param array             $form_data Formulardaten.
	 * @return FieldDefinition[] Gefilterte Felder.
	 */
	public function filterVisibleFields( array $fields, array $form_data ): array {
		return array_filter(
			$fields,
			fn( $field ) => $this->evaluate( $field->getConditional() ?? [], $form_data )
		);
	}

	/**
	 * Abhängigkeitsgraph für Felder erstellen
	 *
	 * Identifiziert welche Felder von welchen anderen abhängen.
	 *
	 * @param FieldDefinition[] $fields Felddefinitionen.
	 * @return array<string, string[]> field_key => [abhängige_field_keys].
	 */
	public function buildDependencyGraph( array $fields ): array {
		$graph = [];

		foreach ( $fields as $field ) {
			$conditional = $field->getConditional();

			if ( ! empty( $conditional['field'] ) ) {
				$depends_on = $conditional['field'];

				if ( ! isset( $graph[ $depends_on ] ) ) {
					$graph[ $depends_on ] = [];
				}

				$graph[ $depends_on ][] = $field->getFieldKey();
			}
		}

		return $graph;
	}

	/**
	 * Prüfen ob Conditional Logic zirkuläre Abhängigkeiten hat
	 *
	 * @param FieldDefinition[] $fields Felddefinitionen.
	 * @return bool True wenn zirkuläre Abhängigkeit gefunden.
	 */
	public function hasCircularDependency( array $fields ): bool {
		$graph = [];

		// Abhängigkeiten als Graph aufbauen.
		foreach ( $fields as $field ) {
			$key         = $field->getFieldKey();
			$conditional = $field->getConditional();

			if ( ! empty( $conditional['field'] ) ) {
				$graph[ $key ] = [ $conditional['field'] ];
			} else {
				$graph[ $key ] = [];
			}
		}

		// DFS für Zykluserkennung.
		$visited = [];
		$stack   = [];

		foreach ( array_keys( $graph ) as $node ) {
			if ( $this->hasCycle( $node, $graph, $visited, $stack ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * DFS-Hilfsfunktion für Zykluserkennung
	 *
	 * @param string $node    Aktueller Knoten.
	 * @param array  $graph   Abhängigkeitsgraph.
	 * @param array  $visited Bereits besuchte Knoten.
	 * @param array  $stack   Aktueller Rekursionsstack.
	 * @return bool True wenn Zyklus gefunden.
	 */
	private function hasCycle( string $node, array $graph, array &$visited, array &$stack ): bool {
		if ( isset( $stack[ $node ] ) ) {
			return true; // Zyklus gefunden.
		}

		if ( isset( $visited[ $node ] ) ) {
			return false; // Bereits verarbeitet.
		}

		$visited[ $node ] = true;
		$stack[ $node ]   = true;

		foreach ( $graph[ $node ] ?? [] as $neighbor ) {
			if ( isset( $graph[ $neighbor ] ) && $this->hasCycle( $neighbor, $graph, $visited, $stack ) ) {
				return true;
			}
		}

		unset( $stack[ $node ] );

		return false;
	}

	/**
	 * Conditional-Konfiguration validieren
	 *
	 * @param array    $conditional Conditional-Konfiguration.
	 * @param string[] $valid_fields Gültige Feld-Keys.
	 * @return true|\WP_Error True wenn gültig.
	 */
	public function validateConditional( array $conditional, array $valid_fields ): bool|\WP_Error {
		if ( empty( $conditional ) ) {
			return true;
		}

		// Feld muss angegeben sein.
		if ( empty( $conditional['field'] ) ) {
			return new \WP_Error(
				'invalid_conditional',
				__( 'Conditional Logic: Kein Feld angegeben.', 'recruiting-playbook' )
			);
		}

		// Feld muss existieren.
		if ( ! in_array( $conditional['field'], $valid_fields, true ) ) {
			return new \WP_Error(
				'invalid_conditional',
				sprintf(
					/* translators: %s: field key */
					__( 'Conditional Logic: Feld "%s" existiert nicht.', 'recruiting-playbook' ),
					$conditional['field']
				)
			);
		}

		// Operator muss gültig sein.
		$operator = $conditional['operator'] ?? 'equals';
		if ( ! isset( self::OPERATORS[ $operator ] ) ) {
			return new \WP_Error(
				'invalid_conditional',
				sprintf(
					/* translators: %s: operator */
					__( 'Conditional Logic: Ungültiger Operator "%s".', 'recruiting-playbook' ),
					$operator
				)
			);
		}

		// Wert muss angegeben sein, wenn Operator es erfordert.
		if ( self::OPERATORS[ $operator ]['requires_value'] && ! isset( $conditional['value'] ) ) {
			return new \WP_Error(
				'invalid_conditional',
				__( 'Conditional Logic: Wert ist erforderlich für diesen Operator.', 'recruiting-playbook' )
			);
		}

		return true;
	}
}
