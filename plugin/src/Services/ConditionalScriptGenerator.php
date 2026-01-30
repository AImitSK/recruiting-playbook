<?php
/**
 * Conditional Script Generator
 *
 * Generiert JavaScript-Konfiguration für Client-Side Conditional Logic.
 *
 * @package RecruitingPlaybook\Services
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;

/**
 * Generator für Conditional Logic Scripts
 */
class ConditionalScriptGenerator {

	/**
	 * ConditionalLogicService
	 *
	 * @var ConditionalLogicService
	 */
	private ConditionalLogicService $logic_service;

	/**
	 * Konstruktor
	 *
	 * @param ConditionalLogicService|null $logic_service Optional: Service-Instanz.
	 */
	public function __construct( ?ConditionalLogicService $logic_service = null ) {
		$this->logic_service = $logic_service ?? new ConditionalLogicService();
	}

	/**
	 * Conditional Logic Konfiguration für Felder generieren
	 *
	 * @param FieldDefinition[] $fields Felddefinitionen.
	 * @return array Konfiguration für Frontend.
	 */
	public function generateConfig( array $fields ): array {
		$config = [
			'fields'     => [],
			'operators'  => $this->logic_service->getOperatorConfig(),
			'dependency' => $this->logic_service->buildDependencyGraph( $fields ),
		];

		foreach ( $fields as $field ) {
			$conditional = $field->getConditional();

			if ( ! empty( $conditional ) && ! empty( $conditional['field'] ) ) {
				$config['fields'][ $field->getFieldKey() ] = [
					'condition'  => $conditional,
					'expression' => $this->logic_service->toAlpineExpression( $conditional ),
				];
			}
		}

		return $config;
	}

	/**
	 * Inline JavaScript für Conditional Logic generieren
	 *
	 * @param FieldDefinition[] $fields Felddefinitionen.
	 * @return string JavaScript-Code.
	 */
	public function generateInlineScript( array $fields ): string {
		$config = $this->generateConfig( $fields );

		if ( empty( $config['fields'] ) ) {
			return '';
		}

		$json = wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );

		return sprintf(
			'<script>window.rpConditionalConfig = %s;</script>',
			$json
		);
	}

	/**
	 * Alpine.js x-data Objekt für Formular mit Conditional Logic generieren
	 *
	 * @param FieldDefinition[] $fields      Felddefinitionen.
	 * @param array             $initial_data Initiale Formulardaten.
	 * @return string Alpine.js x-data Attributwert.
	 */
	public function generateAlpineData( array $fields, array $initial_data = [] ): string {
		$field_keys = [];
		$conditions = [];

		foreach ( $fields as $field ) {
			$key                = $field->getFieldKey();
			$field_keys[ $key ] = $initial_data[ $key ] ?? $this->getDefaultValue( $field );

			$conditional = $field->getConditional();
			if ( ! empty( $conditional ) && ! empty( $conditional['field'] ) ) {
				$conditions[ $key ] = $conditional;
			}
		}

		$data = [
			'formData'   => (object) $field_keys,
			'conditions' => (object) $conditions,
			'isVisible'  => $this->generateVisibilityFunction(),
		];

		return wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_APOS );
	}

	/**
	 * Default-Wert für Feldtyp ermitteln
	 *
	 * @param FieldDefinition $field Felddefinition.
	 * @return mixed Default-Wert.
	 */
	private function getDefaultValue( FieldDefinition $field ): mixed {
		$type     = $field->getType();
		$settings = $field->getSettings() ?? [];

		return match ( $type ) {
			'checkbox' => ( $settings['mode'] ?? 'single' ) === 'multi' ? [] : false,
			'file'     => [],
			'number'   => null,
			default    => '',
		};
	}

	/**
	 * Visibility-Funktion als String generieren
	 *
	 * @return string JavaScript-Funktion.
	 */
	private function generateVisibilityFunction(): string {
		return 'function(fieldKey) {
			const condition = this.conditions[fieldKey];
			if (!condition || !condition.field) return true;
			return window.RPConditionalLogic ?
				window.RPConditionalLogic.evaluate(condition, this.formData) : true;
		}';
	}

	/**
	 * x-show Attribut für ein Feld generieren
	 *
	 * @param FieldDefinition $field Felddefinition.
	 * @return string x-show Wert oder leerer String.
	 */
	public function generateXShow( FieldDefinition $field ): string {
		$conditional = $field->getConditional();

		if ( empty( $conditional ) || empty( $conditional['field'] ) ) {
			return '';
		}

		return $this->logic_service->toAlpineExpression( $conditional );
	}

	/**
	 * Alle x-show Attribute für Felder generieren
	 *
	 * @param FieldDefinition[] $fields Felddefinitionen.
	 * @return array<string, string> field_key => x-show Expression.
	 */
	public function generateAllXShow( array $fields ): array {
		$expressions = [];

		foreach ( $fields as $field ) {
			$expr = $this->generateXShow( $field );
			if ( $expr ) {
				$expressions[ $field->getFieldKey() ] = $expr;
			}
		}

		return $expressions;
	}

	/**
	 * CSS für Hidden Fields generieren
	 *
	 * @return string CSS-Code.
	 */
	public function generateHiddenStyles(): string {
		return '
			<style>
				[x-cloak] { display: none !important; }
				.rp-form__field[hidden] { display: none !important; }
				.rp-form__field--hidden {
					display: none !important;
					visibility: hidden;
					height: 0;
					overflow: hidden;
				}
			</style>
		';
	}

	/**
	 * Watcher-Code für reaktive Updates generieren
	 *
	 * @param FieldDefinition[] $fields Felddefinitionen.
	 * @return string JavaScript-Code.
	 */
	public function generateWatchers( array $fields ): string {
		$dependency_graph = $this->logic_service->buildDependencyGraph( $fields );

		if ( empty( $dependency_graph ) ) {
			return '';
		}

		$watchers = [];

		foreach ( $dependency_graph as $source_field => $dependent_fields ) {
			$watchers[] = sprintf(
				"this.\$watch('formData.%s', () => { this.updateVisibility(); });",
				$source_field
			);
		}

		return implode( "\n", array_unique( $watchers ) );
	}

	/**
	 * Kompletten Alpine.js Component-Code generieren
	 *
	 * @param FieldDefinition[] $fields      Felddefinitionen.
	 * @param array             $initial_data Initiale Daten.
	 * @return string JavaScript-Code.
	 */
	public function generateAlpineComponent( array $fields, array $initial_data = [] ): string {
		$config       = $this->generateConfig( $fields );
		$watchers     = $this->generateWatchers( $fields );
		$initial_json = wp_json_encode( $initial_data, JSON_HEX_TAG | JSON_HEX_APOS );
		$config_json  = wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_APOS );

		return <<<JS
function rpFormWithConditional() {
	return {
		formData: {$initial_json},
		conditions: {$config_json}.fields,
		errors: {},
		submitting: false,

		init() {
			this.updateVisibility();
			{$watchers}
		},

		updateVisibility() {
			// Visibility wird über x-show directives gesteuert
		},

		isVisible(fieldKey) {
			const fieldConfig = this.conditions[fieldKey];
			if (!fieldConfig || !fieldConfig.condition) return true;

			return window.RPConditionalLogic
				? window.RPConditionalLogic.evaluate(fieldConfig.condition, this.formData)
				: true;
		},

		shouldValidate(fieldKey) {
			return this.isVisible(fieldKey);
		}
	};
}
JS;
	}

	/**
	 * Script-Tag für Conditional Logic Assets generieren
	 *
	 * @return string HTML Script-Tags.
	 */
	public function getScriptTags(): string {
		$script_url = RP_PLUGIN_URL . 'assets/src/js/conditional-logic.js';

		return sprintf(
			'<script src="%s" defer></script>',
			esc_url( $script_url )
		);
	}
}
