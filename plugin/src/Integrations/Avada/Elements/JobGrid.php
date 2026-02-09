<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Stellenliste Element für Fusion Builder
 *
 * Zeigt eine Liste von Stellenanzeigen in einem Grid-Layout.
 * Wrapper für den [rp_jobs] Shortcode.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class JobGrid extends AbstractElement {

	/**
	 * Element-Konfiguration für Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: Stellenliste', 'recruiting-playbook' ),
			'shortcode'       => 'rp_jobs',
			'icon'            => 'fusiona-sorting-boxes',
			'help_url'        => $this->getHelpUrl( 'rp_jobs' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => array_merge(
				$this->getGeneralParams(),
				$this->getFilterParams(),
				$this->getSortingParams()
			),
		];
	}

	/**
	 * Allgemeine Parameter
	 *
	 * @return array<array<string, mixed>>
	 */
	private function getGeneralParams(): array {
		return [
			[
				'type'        => 'range',
				'heading'     => esc_attr__( 'Anzahl Stellen', 'recruiting-playbook' ),
				'description' => esc_attr__( 'Wie viele Stellen sollen angezeigt werden?', 'recruiting-playbook' ),
				'param_name'  => 'limit',
				'value'       => '10',
				'min'         => '1',
				'max'         => '50',
				'step'        => '1',
				'group'       => esc_attr__( 'Allgemein', 'recruiting-playbook' ),
			],
			[
				'type'        => 'radio_button_set',
				'heading'     => esc_attr__( 'Spalten', 'recruiting-playbook' ),
				'description' => esc_attr__( 'Anzahl der Spalten im Grid.', 'recruiting-playbook' ),
				'param_name'  => 'columns',
				'default'     => '2',
				'value'       => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				],
				'group'       => esc_attr__( 'Allgemein', 'recruiting-playbook' ),
			],
		];
	}

	/**
	 * Filter-Parameter
	 *
	 * @return array<array<string, mixed>>
	 */
	private function getFilterParams(): array {
		return [
			[
				'type'        => 'select',
				'heading'     => esc_attr__( 'Kategorie', 'recruiting-playbook' ),
				'description' => esc_attr__( 'Nach Kategorie filtern.', 'recruiting-playbook' ),
				'param_name'  => 'category',
				'value'       => $this->getTaxonomyOptions( 'job_category' ),
				'default'     => '',
				'group'       => esc_attr__( 'Filter', 'recruiting-playbook' ),
			],
			[
				'type'        => 'select',
				'heading'     => esc_attr__( 'Standort', 'recruiting-playbook' ),
				'description' => esc_attr__( 'Nach Standort filtern.', 'recruiting-playbook' ),
				'param_name'  => 'location',
				'value'       => $this->getTaxonomyOptions( 'job_location' ),
				'default'     => '',
				'group'       => esc_attr__( 'Filter', 'recruiting-playbook' ),
			],
			[
				'type'        => 'select',
				'heading'     => esc_attr__( 'Beschäftigungsart', 'recruiting-playbook' ),
				'description' => esc_attr__( 'Nach Beschäftigungsart filtern.', 'recruiting-playbook' ),
				'param_name'  => 'type',
				'value'       => $this->getTaxonomyOptions( 'employment_type' ),
				'default'     => '',
				'group'       => esc_attr__( 'Filter', 'recruiting-playbook' ),
			],
			[
				'type'        => 'radio_button_set',
				'heading'     => esc_attr__( 'Nur Featured', 'recruiting-playbook' ),
				'description' => esc_attr__( 'Nur hervorgehobene Stellen anzeigen.', 'recruiting-playbook' ),
				'param_name'  => 'featured',
				'default'     => 'false',
				'value'       => [
					'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
					'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
				],
				'group'       => esc_attr__( 'Filter', 'recruiting-playbook' ),
			],
		];
	}

	/**
	 * Sortierungs-Parameter
	 *
	 * @return array<array<string, mixed>>
	 */
	private function getSortingParams(): array {
		return [
			[
				'type'        => 'select',
				'heading'     => esc_attr__( 'Sortieren nach', 'recruiting-playbook' ),
				'param_name'  => 'orderby',
				'default'     => 'date',
				'value'       => [
					'date'  => esc_attr__( 'Datum', 'recruiting-playbook' ),
					'title' => esc_attr__( 'Titel', 'recruiting-playbook' ),
					'rand'  => esc_attr__( 'Zufällig', 'recruiting-playbook' ),
				],
				'group'       => esc_attr__( 'Sortierung', 'recruiting-playbook' ),
			],
			[
				'type'        => 'radio_button_set',
				'heading'     => esc_attr__( 'Reihenfolge', 'recruiting-playbook' ),
				'param_name'  => 'order',
				'default'     => 'DESC',
				'value'       => [
					'DESC' => esc_attr__( 'Absteigend', 'recruiting-playbook' ),
					'ASC'  => esc_attr__( 'Aufsteigend', 'recruiting-playbook' ),
				],
				'group'       => esc_attr__( 'Sortierung', 'recruiting-playbook' ),
			],
		];
	}
}
