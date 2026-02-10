<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Neueste Stellen Element für Fusion Builder
 *
 * Zeigt die neuesten Stellenanzeigen an. Ideal für Sidebars.
 * Wrapper für den [rp_latest_jobs] Shortcode.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class LatestJobs extends AbstractElement {

	/**
	 * Element-Konfiguration für Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: Neueste Stellen', 'recruiting-playbook' ),
			'shortcode'       => 'rp_latest_jobs',
			'icon'            => 'fusiona-clock',
			'help_url'        => $this->getHelpUrl( 'rp_latest_jobs' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => [
				[
					'type'        => 'range',
					'heading'     => esc_attr__( 'Anzahl', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Anzahl der neuesten Stellen.', 'recruiting-playbook' ),
					'param_name'  => 'limit',
					'value'       => '5',
					'min'         => '1',
					'max'         => '20',
					'step'        => '1',
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Spalten', 'recruiting-playbook' ),
					'description' => esc_attr__( '0 = Listendarstellung ohne Grid.', 'recruiting-playbook' ),
					'param_name'  => 'columns',
					'default'     => '0',
					'value'       => [
						'0' => esc_attr__( 'Liste', 'recruiting-playbook' ),
						'1' => '1',
						'2' => '2',
						'3' => '3',
					],
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Überschrift', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Optional: Überschrift über den neuesten Stellen.', 'recruiting-playbook' ),
					'param_name'  => 'title',
					'value'       => '',
				],
				[
					'type'        => 'select',
					'heading'     => esc_attr__( 'Kategorie', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Optional: Nur Stellen dieser Kategorie anzeigen.', 'recruiting-playbook' ),
					'param_name'  => 'category',
					'value'       => $this->getTaxonomyOptions( 'job_category' ),
					'default'     => '',
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Datum anzeigen', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Veröffentlichungsdatum anzeigen.', 'recruiting-playbook' ),
					'param_name'  => 'show_date',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
						'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Auszug anzeigen', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Stellenbeschreibung als Kurztext anzeigen.', 'recruiting-playbook' ),
					'param_name'  => 'show_excerpt',
					'default'     => 'false',
					'value'       => [
						'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
						'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
					],
				],
			],
		];
	}
}
