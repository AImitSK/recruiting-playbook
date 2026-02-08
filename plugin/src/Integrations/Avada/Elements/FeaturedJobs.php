<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Featured Jobs Element für Fusion Builder
 *
 * Zeigt hervorgehobene (Featured) Stellenanzeigen an.
 * Wrapper für den [rp_featured_jobs] Shortcode.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class FeaturedJobs extends AbstractElement {

	/**
	 * Element-Konfiguration für Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: Featured Jobs', 'recruiting-playbook' ),
			'shortcode'       => 'rp_featured_jobs',
			'icon'            => 'fusiona-star',
			'help_url'        => $this->getHelpUrl( 'rp_featured_jobs' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => [
				[
					'type'        => 'range',
					'heading'     => esc_attr__( 'Anzahl', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Anzahl der Featured Jobs.', 'recruiting-playbook' ),
					'param_name'  => 'limit',
					'value'       => '3',
					'min'         => '1',
					'max'         => '12',
					'step'        => '1',
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Spalten', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Anzahl der Spalten im Grid.', 'recruiting-playbook' ),
					'param_name'  => 'columns',
					'default'     => '3',
					'value'       => [
						'1' => '1',
						'2' => '2',
						'3' => '3',
						'4' => '4',
					],
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Überschrift', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Optional: Überschrift über den Featured Jobs.', 'recruiting-playbook' ),
					'param_name'  => 'title',
					'value'       => '',
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Auszug anzeigen', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Stellenbeschreibung als Kurztext anzeigen.', 'recruiting-playbook' ),
					'param_name'  => 'show_excerpt',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
						'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
					],
				],
			],
		];
	}
}
