<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Abstrakte Basisklasse für Elementor Widgets
 *
 * Alle RP-Widgets wrappen bestehende Shortcodes.
 *
 * @package RecruitingPlaybook
 * @since 1.3.0
 */
abstract class AbstractWidget extends Widget_Base {

	/**
	 * Widget-Kategorie
	 */
	public function get_categories(): array {
		return [ 'recruiting-playbook' ];
	}

	/**
	 * CSS-Abhängigkeiten für dieses Widget
	 *
	 * Elementor lädt diese Styles automatisch wenn das Widget verwendet wird,
	 * auch im Editor-Preview.
	 */
	public function get_style_depends(): array {
		return [ 'rp-frontend' ];
	}

	/**
	 * Shortcode-Name (wird von Subklassen definiert)
	 */
	abstract protected function get_shortcode_name(): string;

	/**
	 * Shortcode-Attribute aus Widget-Settings ableiten
	 */
	protected function get_shortcode_atts(): array {
		$settings = $this->get_settings_for_display();
		$atts     = [];

		foreach ( $this->get_shortcode_mapping() as $setting_key => $shortcode_attr ) {
			if ( isset( $settings[ $setting_key ] ) && '' !== $settings[ $setting_key ] ) {
				$atts[ $shortcode_attr ] = $settings[ $setting_key ];
			}
		}

		return $atts;
	}

	/**
	 * Mapping: Elementor Setting Key → Shortcode Attribute
	 *
	 * Subklassen überschreiben dies.
	 * Default: leer (keine Attribute)
	 */
	protected function get_shortcode_mapping(): array {
		return [];
	}

	/**
	 * Shortcode-String zusammenbauen
	 */
	protected function build_shortcode(): string {
		$name = $this->get_shortcode_name();
		$atts = $this->get_shortcode_atts();

		if ( empty( $atts ) ) {
			return "[{$name}]";
		}

		$pairs = [];
		foreach ( $atts as $key => $value ) {
			$pairs[] = sprintf( '%s="%s"', $key, esc_attr( (string) $value ) );
		}

		return "[{$name} " . implode( ' ', $pairs ) . ']';
	}

	/**
	 * Frontend-Render: Shortcode ausführen
	 */
	protected function render(): void {
		echo do_shortcode( $this->build_shortcode() );
	}

	/**
	 * Editor-Vorschau (JS-Template)
	 *
	 * Leer lassen → Elementor nutzt serverseitiges Rendering via AJAX.
	 * So wird der echte Shortcode-Output im Editor angezeigt.
	 */
	protected function content_template(): void {}

	/**
	 * Taxonomie-Optionen laden (für Controls)
	 *
	 * @param string $taxonomy Taxonomy-Name.
	 * @return array<string, string> Optionen als slug => name.
	 */
	protected function getTaxonomyOptions( string $taxonomy ): array {
		$terms   = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false ] );
		$options = [ '' => esc_html__( '— Alle —', 'recruiting-playbook' ) ];

		if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->slug ] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * Job-Optionen laden (für Controls)
	 *
	 * @return array<string, string> Optionen als ID => Titel.
	 */
	protected function getJobOptions(): array {
		$jobs = get_posts( [
			'post_type'      => 'job_listing',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		$options = [ '' => esc_html__( '— Automatisch —', 'recruiting-playbook' ) ];

		foreach ( $jobs as $job ) {
			$options[ (string) $job->ID ] = $job->post_title;
		}

		return $options;
	}
}
