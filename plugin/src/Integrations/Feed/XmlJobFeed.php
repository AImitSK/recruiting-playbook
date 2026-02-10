<?php
/**
 * XML Job Feed für Jobbörsen
 *
 * Generiert einen standardisierten XML-Feed unter /feed/jobs/
 * kompatibel mit Jooble, Talent.com, Adzuna und weiteren Jobbörsen.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Feed;

defined( 'ABSPATH' ) || exit;

/**
 * XML Job Feed Generator
 */
class XmlJobFeed {

	/**
	 * Transient-Key für Cache
	 *
	 * @var string
	 */
	private const CACHE_KEY = 'rp_xml_job_feed';

	/**
	 * Cache-TTL in Sekunden (1 Stunde)
	 *
	 * @var int
	 */
	private const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Jobtype-Mapping (Taxonomy-Slug → XML-Wert)
	 *
	 * @var array<string, string>
	 */
	private const JOBTYPE_MAP = [
		'vollzeit'      => 'full-time',
		'teilzeit'      => 'part-time',
		'minijob'       => 'part-time',
		'ausbildung'    => 'internship',
		'praktikum'     => 'internship',
		'werkstudent'   => 'part-time',
		'freiberuflich' => 'freelance',
	];

	/**
	 * Feed registrieren
	 */
	public function register(): void {
		add_feed( 'jobs', [ $this, 'render' ] );

		// Cache invalidieren bei Änderungen an Stellenanzeigen.
		add_action( 'save_post_job_listing', [ $this, 'invalidateCache' ] );
		add_action( 'delete_post', [ $this, 'invalidateCacheOnDelete' ] );
		add_action( 'transition_post_status', [ $this, 'invalidateCacheOnStatusChange' ], 10, 3 );
	}

	/**
	 * Feed rendern
	 */
	public function render(): void {
		$settings = get_option( 'rp_integrations', [] );

		// Feed deaktiviert → 404.
		if ( isset( $settings['xml_feed_enabled'] ) && ! $settings['xml_feed_enabled'] ) {
			status_header( 404 );
			nocache_headers();
			echo '<?xml version="1.0" encoding="UTF-8"?><error>Feed is disabled</error>';
			exit;
		}

		// Aus Cache laden oder neu generieren.
		$xml = get_transient( self::CACHE_KEY );

		if ( false === $xml ) {
			$xml = $this->generateXml( $settings );
			set_transient( self::CACHE_KEY, $xml, self::CACHE_TTL );
		}

		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML output, escaped via DOMDocument.
		echo $xml;
		exit;
	}

	/**
	 * XML generieren
	 *
	 * @param array $settings Integrations-Settings.
	 * @return string XML-String.
	 */
	private function generateXml( array $settings ): string {
		$max_items        = (int) ( $settings['xml_feed_max_items'] ?? 50 );
		$show_salary      = (bool) ( $settings['xml_feed_show_salary'] ?? true );
		$html_description = (bool) ( $settings['xml_feed_html_description'] ?? true );

		$plugin_settings = get_option( 'rp_settings', [] );
		$company_name    = $plugin_settings['company_name'] ?? get_bloginfo( 'name' );

		$jobs = get_posts(
			[
				'post_type'      => 'job_listing',
				'post_status'    => 'publish',
				'posts_per_page' => min( $max_items, 500 ),
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		$dom->formatOutput = true;

		$root = $dom->createElement( 'jobs' );
		$dom->appendChild( $root );

		foreach ( $jobs as $post ) {
			$job_element = $this->buildJobElement( $dom, $post, $company_name, $show_salary, $html_description );
			$root->appendChild( $job_element );
		}

		return $dom->saveXML();
	}

	/**
	 * Einzelnes <job>-Element erstellen
	 *
	 * @param \DOMDocument $dom             DOM Document.
	 * @param \WP_Post     $post            Job Post.
	 * @param string       $company_name    Firmenname.
	 * @param bool         $show_salary     Gehalt anzeigen.
	 * @param bool         $html_description HTML oder Plain Text.
	 * @return \DOMElement Job-Element.
	 */
	private function buildJobElement(
		\DOMDocument $dom,
		\WP_Post $post,
		string $company_name,
		bool $show_salary,
		bool $html_description
	): \DOMElement {
		$job = $dom->createElement( 'job' );

		// Pflichtfelder.
		$this->addElement( $dom, $job, 'id', (string) $post->ID );
		$this->addElement( $dom, $job, 'link', get_permalink( $post ) );
		$this->addElement( $dom, $job, 'name', get_the_title( $post ) );

		// Standort.
		$region = $this->getRegion( $post->ID );
		if ( $region ) {
			$this->addElement( $dom, $job, 'region', $region );
		}

		// Beschreibung.
		if ( $html_description ) {
			$content = apply_filters( 'the_content', $post->post_content );
			$content = wp_kses_post( $content );
			$this->addCdataElement( $dom, $job, 'description', $content );
		} else {
			$content = wp_strip_all_tags( $post->post_content );
			$this->addElement( $dom, $job, 'description', $content );
		}

		// Daten.
		$this->addElement( $dom, $job, 'pubdate', get_the_date( 'Y-m-d', $post ) );
		$this->addElement( $dom, $job, 'updated', get_the_modified_date( 'Y-m-d', $post ) );

		// Bewerbungsfrist.
		$deadline = get_post_meta( $post->ID, '_rp_application_deadline', true );
		if ( $deadline ) {
			$this->addElement( $dom, $job, 'expire', $deadline );
		}

		// Beschäftigungsart.
		$jobtype = $this->getJobType( $post->ID );
		if ( $jobtype ) {
			$this->addElement( $dom, $job, 'jobtype', $jobtype );
		}

		// Gehalt.
		if ( $show_salary ) {
			$hide_salary = get_post_meta( $post->ID, '_rp_hide_salary', true );
			if ( ! $hide_salary ) {
				$this->addSalaryFields( $dom, $job, $post->ID );
			}
		}

		// Firma.
		$this->addElement( $dom, $job, 'company', $company_name );

		// Kategorie.
		$category = $this->getCategory( $post->ID );
		if ( $category ) {
			$this->addElement( $dom, $job, 'category', $category );
		}

		// Remote.
		$remote = get_post_meta( $post->ID, '_rp_remote_option', true );
		if ( $remote ) {
			$this->addElement( $dom, $job, 'remote', $remote );
		}

		// Kontakt-E-Mail.
		$contact_email = get_post_meta( $post->ID, '_rp_contact_email', true );
		if ( $contact_email ) {
			$this->addElement( $dom, $job, 'contact_email', $contact_email );
		}

		return $job;
	}

	/**
	 * Standort aus Taxonomy
	 *
	 * @param int $post_id Post ID.
	 * @return string|null Standort.
	 */
	private function getRegion( int $post_id ): ?string {
		$terms = get_the_terms( $post_id, 'job_location' );

		if ( ! $terms || is_wp_error( $terms ) ) {
			$remote = get_post_meta( $post_id, '_rp_remote_option', true );
			if ( 'full' === $remote ) {
				return 'Remote';
			}
			return null;
		}

		// Alle Standorte komma-separiert.
		return implode( ', ', wp_list_pluck( $terms, 'name' ) );
	}

	/**
	 * Beschäftigungsart aus Taxonomy
	 *
	 * @param int $post_id Post ID.
	 * @return string|null Jobtype.
	 */
	private function getJobType( int $post_id ): ?string {
		$terms = get_the_terms( $post_id, 'employment_type' );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return null;
		}

		// Erste passende Beschäftigungsart zurückgeben.
		foreach ( $terms as $term ) {
			if ( isset( self::JOBTYPE_MAP[ $term->slug ] ) ) {
				return self::JOBTYPE_MAP[ $term->slug ];
			}
		}

		return null;
	}

	/**
	 * Kategorie aus Taxonomy
	 *
	 * @param int $post_id Post ID.
	 * @return string|null Kategorie.
	 */
	private function getCategory( int $post_id ): ?string {
		$terms = get_the_terms( $post_id, 'job_category' );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return null;
		}

		return $terms[0]->name;
	}

	/**
	 * Gehaltsfelder hinzufügen
	 *
	 * @param \DOMDocument $dom     DOM Document.
	 * @param \DOMElement  $parent  Parent-Element.
	 * @param int          $post_id Post ID.
	 */
	private function addSalaryFields( \DOMDocument $dom, \DOMElement $parent, int $post_id ): void {
		$min      = get_post_meta( $post_id, '_rp_salary_min', true );
		$max      = get_post_meta( $post_id, '_rp_salary_max', true );
		$currency = get_post_meta( $post_id, '_rp_salary_currency', true ) ?: 'EUR';
		$period   = get_post_meta( $post_id, '_rp_salary_period', true ) ?: 'month';

		if ( ! $min && ! $max ) {
			return;
		}

		// Formatiertes Gehalt.
		$period_labels = [
			'hour'  => 'Std.',
			'month' => 'Monat',
			'year'  => 'Jahr',
		];
		$period_label = $period_labels[ $period ] ?? 'Monat';

		if ( $min && $max ) {
			$salary_text = sprintf(
				'%s–%s %s/%s',
				number_format( (float) $min, 0, ',', '.' ),
				number_format( (float) $max, 0, ',', '.' ),
				$currency,
				$period_label
			);
		} elseif ( $min ) {
			$salary_text = sprintf(
				'ab %s %s/%s',
				number_format( (float) $min, 0, ',', '.' ),
				$currency,
				$period_label
			);
		} else {
			$salary_text = sprintf(
				'bis %s %s/%s',
				number_format( (float) $max, 0, ',', '.' ),
				$currency,
				$period_label
			);
		}

		$this->addElement( $dom, $parent, 'salary', $salary_text );

		if ( $min ) {
			$this->addElement( $dom, $parent, 'salary_min', $min );
		}
		if ( $max ) {
			$this->addElement( $dom, $parent, 'salary_max', $max );
		}
		$this->addElement( $dom, $parent, 'salary_currency', $currency );
	}

	/**
	 * Text-Element zum DOM hinzufügen
	 *
	 * @param \DOMDocument $dom    DOM Document.
	 * @param \DOMElement  $parent Parent-Element.
	 * @param string       $name   Element-Name.
	 * @param string       $value  Text-Inhalt.
	 */
	private function addElement( \DOMDocument $dom, \DOMElement $parent, string $name, string $value ): void {
		$element = $dom->createElement( $name );
		$element->appendChild( $dom->createTextNode( $value ) );
		$parent->appendChild( $element );
	}

	/**
	 * CDATA-Element zum DOM hinzufügen
	 *
	 * @param \DOMDocument $dom    DOM Document.
	 * @param \DOMElement  $parent Parent-Element.
	 * @param string       $name   Element-Name.
	 * @param string       $value  CDATA-Inhalt.
	 */
	private function addCdataElement( \DOMDocument $dom, \DOMElement $parent, string $name, string $value ): void {
		$element = $dom->createElement( $name );
		$element->appendChild( $dom->createCDATASection( $value ) );
		$parent->appendChild( $element );
	}

	/**
	 * Cache invalidieren
	 */
	public function invalidateCache(): void {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Cache invalidieren bei Post-Löschung
	 *
	 * @param int $post_id Post ID.
	 */
	public function invalidateCacheOnDelete( int $post_id ): void {
		if ( 'job_listing' === get_post_type( $post_id ) ) {
			$this->invalidateCache();
		}
	}

	/**
	 * Cache invalidieren bei Status-Änderung
	 *
	 * @param string   $new_status Neuer Status.
	 * @param string   $old_status Alter Status.
	 * @param \WP_Post $post       Post-Objekt.
	 */
	public function invalidateCacheOnStatusChange( string $new_status, string $old_status, \WP_Post $post ): void {
		if ( 'job_listing' === $post->post_type && $new_status !== $old_status ) {
			$this->invalidateCache();
		}
	}
}
