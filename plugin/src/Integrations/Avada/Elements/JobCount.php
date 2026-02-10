<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Stellen-Zähler Element für Fusion Builder
 *
 * Zeigt die Anzahl der verfügbaren Stellen an.
 * Wrapper für den [rp_job_count] Shortcode.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class JobCount extends AbstractElement {

	/**
	 * Element-Konfiguration für Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: Stellen-Zähler', 'recruiting-playbook' ),
			'shortcode'       => 'rp_job_count',
			'icon'            => 'fusiona-dashboard',
			'help_url'        => $this->getHelpUrl( 'rp_job_count' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => [
				[
					'type'        => 'select',
					'heading'     => esc_attr__( 'Kategorie', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Optional: Nur Stellen dieser Kategorie zählen.', 'recruiting-playbook' ),
					'param_name'  => 'category',
					'value'       => $this->getTaxonomyOptions( 'job_category' ),
					'default'     => '',
				],
				[
					'type'        => 'select',
					'heading'     => esc_attr__( 'Standort', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Optional: Nur Stellen an diesem Standort zählen.', 'recruiting-playbook' ),
					'param_name'  => 'location',
					'value'       => $this->getTaxonomyOptions( 'job_location' ),
					'default'     => '',
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Format (Mehrzahl)', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Leer = Standard. Platzhalter: {count}. Bsp: {count} offene Stellen', 'recruiting-playbook' ),
					'param_name'  => 'format',
					'value'       => '',
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Format (Einzahl)', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Leer = Standard. Platzhalter: {count}. Bsp: {count} offene Stelle', 'recruiting-playbook' ),
					'param_name'  => 'singular',
					'value'       => '',
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Format (Null)', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Leer = "Keine offenen Stellen".', 'recruiting-playbook' ),
					'param_name'  => 'zero',
					'value'       => '',
				],
			],
		];
	}
}
