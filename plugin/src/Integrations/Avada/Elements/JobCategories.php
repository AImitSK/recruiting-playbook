<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Job-Kategorien Element für Fusion Builder
 *
 * Zeigt alle Job-Kategorien als klickbare Karten an.
 * Wrapper für den [rp_job_categories] Shortcode.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class JobCategories extends AbstractElement {

	/**
	 * Element-Konfiguration für Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: Job-Kategorien', 'recruiting-playbook' ),
			'shortcode'       => 'rp_job_categories',
			'icon'            => 'fusiona-folder',
			'help_url'        => $this->getHelpUrl( 'rp_job_categories' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => [
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Spalten', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Anzahl der Spalten im Grid.', 'recruiting-playbook' ),
					'param_name'  => 'columns',
					'default'     => '4',
					'value'       => [
						'2' => '2',
						'3' => '3',
						'4' => '4',
						'5' => '5',
						'6' => '6',
					],
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Anzahl anzeigen', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Zeigt die Anzahl der Jobs pro Kategorie.', 'recruiting-playbook' ),
					'param_name'  => 'show_count',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
						'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Leere verstecken', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Kategorien ohne Jobs ausblenden.', 'recruiting-playbook' ),
					'param_name'  => 'hide_empty',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
						'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'select',
					'heading'     => esc_attr__( 'Sortierung', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Sortierung der Kategorien.', 'recruiting-playbook' ),
					'param_name'  => 'orderby',
					'default'     => 'name',
					'value'       => [
						'name'  => esc_attr__( 'Name', 'recruiting-playbook' ),
						'count' => esc_attr__( 'Anzahl', 'recruiting-playbook' ),
					],
				],
			],
		];
	}
}
