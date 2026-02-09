<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: KI-Job-Finder Element für Fusion Builder
 *
 * KI-gestützter Job-Finder: Besucher laden ihren Lebenslauf hoch
 * und erhalten passende Stellenvorschläge.
 * Wrapper für den [rp_ai_job_finder] Shortcode.
 *
 * Erfordert das AI-Addon.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class AiJobFinder extends AbstractElement {

	/**
	 * Element-Konfiguration für Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: KI-Job-Finder', 'recruiting-playbook' ),
			'shortcode'       => 'rp_ai_job_finder',
			'icon'            => 'fusiona-avada-ai',
			'help_url'        => $this->getHelpUrl( 'rp_ai_job_finder' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => [
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Überschrift', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Überschrift des Job-Finders.', 'recruiting-playbook' ),
					'param_name'  => 'title',
					'value'       => 'Finde deinen Traumjob',
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Untertitel', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Erklärungstext unter der Überschrift.', 'recruiting-playbook' ),
					'param_name'  => 'subtitle',
					'value'       => 'Lade deinen Lebenslauf hoch und entdecke passende Stellen.',
				],
				[
					'type'        => 'range',
					'heading'     => esc_attr__( 'Max. Vorschläge', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Maximale Anzahl der KI-Vorschläge.', 'recruiting-playbook' ),
					'param_name'  => 'limit',
					'value'       => '5',
					'min'         => '1',
					'max'         => '10',
					'step'        => '1',
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
