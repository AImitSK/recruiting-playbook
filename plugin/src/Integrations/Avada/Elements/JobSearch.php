<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Stellensuche Element für Fusion Builder
 *
 * Zeigt ein Suchformular mit Filtern und Ergebnisliste.
 * Wrapper für den [rp_job_search] Shortcode.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class JobSearch extends AbstractElement {

	/**
	 * Element-Konfiguration für Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: Stellensuche', 'recruiting-playbook' ),
			'shortcode'       => 'rp_job_search',
			'icon'            => 'fusiona-search',
			'help_url'        => $this->getHelpUrl( 'rp_job_search' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => [
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Suchfeld anzeigen', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Textsuche für Stellentitel und Beschreibung.', 'recruiting-playbook' ),
					'param_name'  => 'show_search',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
						'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Kategorie-Filter', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Dropdown zur Auswahl der Kategorie.', 'recruiting-playbook' ),
					'param_name'  => 'show_category',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
						'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Standort-Filter', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Dropdown zur Auswahl des Standorts.', 'recruiting-playbook' ),
					'param_name'  => 'show_location',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
						'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Beschäftigungsart-Filter', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Dropdown zur Auswahl der Beschäftigungsart.', 'recruiting-playbook' ),
					'param_name'  => 'show_type',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
						'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'range',
					'heading'     => esc_attr__( 'Stellen pro Seite', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Anzahl der Stellen pro Seite.', 'recruiting-playbook' ),
					'param_name'  => 'limit',
					'value'       => '10',
					'min'         => '1',
					'max'         => '50',
					'step'        => '1',
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Spalten', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Anzahl der Spalten für die Ergebnisliste.', 'recruiting-playbook' ),
					'param_name'  => 'columns',
					'default'     => '1',
					'value'       => [
						'1' => '1',
						'2' => '2',
						'3' => '3',
					],
				],
			],
		];
	}
}
