<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Bewerbungsformular Element für Fusion Builder
 *
 * Zeigt das mehrstufige Bewerbungsformular an.
 * Wrapper für den [rp_application_form] Shortcode.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class ApplicationForm extends AbstractElement {

	/**
	 * Element-Konfiguration für Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: Bewerbungs-Formular', 'recruiting-playbook' ),
			'shortcode'       => 'rp_application_form',
			'icon'            => 'fusiona-list-alt',
			'component'       => true,
			'help_url'        => $this->getHelpUrl( 'rp_application_form' ),
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
					'heading'     => esc_attr__( 'Überschrift', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Überschrift des Formulars.', 'recruiting-playbook' ),
					'param_name'  => 'title',
					'value'       => 'Jetzt bewerben',
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Stellentitel anzeigen', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Stellentitel über dem Formular anzeigen.', 'recruiting-playbook' ),
					'param_name'  => 'show_job_title',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
						'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Fortschrittsanzeige', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Fortschrittsbalken für mehrstufiges Formular anzeigen.', 'recruiting-playbook' ),
					'param_name'  => 'show_progress',
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
