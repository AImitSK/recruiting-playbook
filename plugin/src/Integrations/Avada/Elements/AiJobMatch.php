<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: KI-Job-Match Element für Fusion Builder
 *
 * "Passe ich zu diesem Job?" Button mit KI-Analyse.
 * Besucher können prüfen, ob ihr Profil zur Stelle passt.
 * Wrapper für den [rp_ai_job_match] Shortcode.
 *
 * Erfordert das AI-Addon.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class AiJobMatch extends AbstractElement {

	/**
	 * Element-Konfiguration für Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: KI-Job-Match', 'recruiting-playbook' ),
			'shortcode'       => 'rp_ai_job_match',
			'icon'            => 'fusiona-check-circle-o',
			'help_url'        => $this->getHelpUrl( 'rp_ai_job_match' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => [
				[
					'type'        => 'select',
					'heading'     => esc_attr__( 'Stelle', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Leer = automatisch erkennen (auf Stellenseiten).', 'recruiting-playbook' ),
					'param_name'  => 'job_id',
					'value'       => $this->getJobOptions(),
					'default'     => '',
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Button-Text', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Text auf dem Button.', 'recruiting-playbook' ),
					'param_name'  => 'title',
					'value'       => 'Passe ich zu diesem Job?',
				],
				[
					'type'        => 'select',
					'heading'     => esc_attr__( 'Button-Stil', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Visueller Stil des Buttons.', 'recruiting-playbook' ),
					'param_name'  => 'style',
					'default'     => '',
					'value'       => [
						''        => esc_attr__( 'Standard (gefüllt)', 'recruiting-playbook' ),
						'outline' => esc_attr__( 'Outline (nur Rahmen)', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'CSS-Klassen', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Zusätzliche CSS-Klassen für individuelle Gestaltung.', 'recruiting-playbook' ),
					'param_name'  => 'class',
					'value'       => '',
				],
			],
		];
	}
}
