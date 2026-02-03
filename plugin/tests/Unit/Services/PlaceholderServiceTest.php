<?php
/**
 * PlaceholderService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Services;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\PlaceholderService;
use Brain\Monkey\Functions;

/**
 * Tests für den PlaceholderService
 */
class PlaceholderServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var PlaceholderService
	 */
	private PlaceholderService $service;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		// WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'get_bloginfo' )->justReturn( 'Test Blog' );
		Functions\when( 'home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'get_option' )->alias( function ( $option, $default = false ) {
			if ( 'rp_settings' === $option ) {
				return [
					'company_name'    => 'Test GmbH',
					'company_street'  => 'Teststraße 1',
					'company_zip'     => '12345',
					'company_city'    => 'Berlin',
					'company_website' => 'https://test.de',
				];
			}
			if ( 'date_format' === $option ) {
				return 'd.m.Y';
			}
			return $default;
		} );
		Functions\when( 'date_i18n' )->alias( function ( $format, $timestamp = false ) {
			return date( $format, $timestamp ?: time() );
		} );

		$this->service = new PlaceholderService();
	}

	/**
	 * Test: replace() ersetzt alle Standard-Platzhalter
	 */
	public function test_replace_substitutes_all_standard_placeholders(): void {
		$template = 'Hallo {vorname} {nachname}, Ihre Bewerbung für {stelle} ist eingegangen.';
		$context  = [
			'candidate'   => [
				'first_name' => 'Max',
				'last_name'  => 'Mustermann',
				'email'      => 'max@example.com',
			],
			'application' => [
				'id'         => 42,
				'created_at' => '2025-01-15 10:30:00',
				'status'     => 'new',
			],
			'job'         => [
				'title' => 'PHP Developer',
			],
		];

		$result = $this->service->replace( $template, $context );

		$this->assertStringContainsString( 'Max', $result );
		$this->assertStringContainsString( 'Mustermann', $result );
		$this->assertStringContainsString( 'PHP Developer', $result );
		$this->assertStringNotContainsString( '{vorname}', $result );
		$this->assertStringNotContainsString( '{nachname}', $result );
		$this->assertStringNotContainsString( '{stelle}', $result );
	}

	/**
	 * Test: replace() ersetzt {name} mit vollem Namen
	 */
	public function test_replace_creates_full_name(): void {
		$template = 'Liebe/r {name},';
		$context  = [
			'candidate' => [
				'first_name' => 'Max',
				'last_name'  => 'Mustermann',
			],
		];

		$result = $this->service->replace( $template, $context );

		$this->assertStringContainsString( 'Max Mustermann', $result );
	}

	/**
	 * Test: {anrede_formal} mit Herr
	 */
	public function test_anrede_formal_with_herr(): void {
		$template = '{anrede_formal},';
		$context  = [
			'candidate' => [
				'salutation' => 'Herr',
				'last_name'  => 'Mustermann',
			],
		];

		$result = $this->service->replace( $template, $context );

		$this->assertStringContainsString( 'Sehr geehrter Herr Mustermann', $result );
	}

	/**
	 * Test: {anrede_formal} mit Frau
	 */
	public function test_anrede_formal_with_frau(): void {
		$template = '{anrede_formal},';
		$context  = [
			'candidate' => [
				'salutation' => 'Frau',
				'last_name'  => 'Müller',
			],
		];

		$result = $this->service->replace( $template, $context );

		$this->assertStringContainsString( 'Sehr geehrte Frau Müller', $result );
	}

	/**
	 * Test: {anrede_formal} ohne Anrede fällt auf Nachname zurück
	 */
	public function test_anrede_formal_without_salutation_uses_lastname(): void {
		$template = '{anrede_formal},';
		$context  = [
			'candidate' => [
				'salutation' => '',
				'last_name'  => 'Müller',
			],
		];

		$result = $this->service->replace( $template, $context );

		$this->assertStringContainsString( 'Guten Tag Müller', $result );
	}

	/**
	 * Test: {anrede_formal} ohne Anrede und Namen
	 */
	public function test_anrede_formal_without_any_data(): void {
		$template = '{anrede_formal},';
		$context  = [
			'candidate' => [
				'salutation' => '',
				'last_name'  => '',
			],
		];

		$result = $this->service->replace( $template, $context );

		$this->assertStringContainsString( 'Guten Tag', $result );
	}

	/**
	 * Test: Fehlende Werte werden als leerer String ersetzt
	 */
	public function test_missing_values_replaced_with_empty_string(): void {
		$template = 'Tel: {telefon}';
		$context  = [
			'candidate' => [
				'phone' => null,
			],
		];

		$result = $this->service->replace( $template, $context );

		$this->assertEquals( 'Tel: ', $result );
	}

	/**
	 * Test: Firmendaten aus Settings
	 */
	public function test_company_placeholders_from_settings(): void {
		$template = '{firma} - {firma_adresse}';
		$context  = [];

		$result = $this->service->replace( $template, $context );

		$this->assertStringContainsString( 'Test GmbH', $result );
		$this->assertStringContainsString( 'Teststraße 1', $result );
		$this->assertStringContainsString( '12345 Berlin', $result );
	}

	/**
	 * Test: getAvailablePlaceholders() gibt alle Definitionen zurück
	 */
	public function test_getAvailablePlaceholders_returns_all_definitions(): void {
		$placeholders = $this->service->getAvailablePlaceholders();

		$this->assertIsArray( $placeholders );
		$this->assertArrayHasKey( 'vorname', $placeholders );
		$this->assertArrayHasKey( 'nachname', $placeholders );
		$this->assertArrayHasKey( 'name', $placeholders );
		$this->assertArrayHasKey( 'email', $placeholders );
		$this->assertArrayHasKey( 'anrede_formal', $placeholders );
		$this->assertArrayHasKey( 'bewerbung_id', $placeholders );
		$this->assertArrayHasKey( 'stelle', $placeholders );
		$this->assertArrayHasKey( 'firma', $placeholders );
	}

	/**
	 * Test: Jeder Platzhalter hat Label und Gruppe
	 */
	public function test_all_placeholders_have_required_properties(): void {
		$placeholders = $this->service->getAvailablePlaceholders();

		foreach ( $placeholders as $key => $definition ) {
			$this->assertArrayHasKey( 'label', $definition, "Placeholder {$key} fehlt 'label'" );
			$this->assertArrayHasKey( 'group', $definition, "Placeholder {$key} fehlt 'group'" );
		}
	}

	/**
	 * Test: validateTemplate() erkennt valide Templates
	 */
	public function test_validateTemplate_recognizes_valid_template(): void {
		$template = 'Hallo {vorname}, Ihre Bewerbung für {stelle} ist eingegangen.';

		$result = $this->service->validateTemplate( $template );

		$this->assertTrue( $result['valid'] );
		$this->assertEmpty( $result['invalid'] );
		$this->assertContains( 'vorname', $result['found'] );
		$this->assertContains( 'stelle', $result['found'] );
	}

	/**
	 * Test: validateTemplate() findet ungültige Platzhalter
	 */
	public function test_validateTemplate_finds_invalid_placeholders(): void {
		$template = 'Hallo {vorname}, {unbekannt_feld} und {noch_ein_fehler}.';

		$result = $this->service->validateTemplate( $template );

		$this->assertFalse( $result['valid'] );
		$this->assertContains( 'unbekannt_feld', $result['invalid'] );
		$this->assertContains( 'noch_ein_fehler', $result['invalid'] );
	}

	/**
	 * Test: findPlaceholders() extrahiert alle Platzhalter
	 */
	public function test_findPlaceholders_extracts_all(): void {
		$text = 'Text mit {vorname} und {nachname} und {email}';

		$found = $this->service->findPlaceholders( $text );

		$this->assertCount( 3, $found );
		$this->assertContains( 'vorname', $found );
		$this->assertContains( 'nachname', $found );
		$this->assertContains( 'email', $found );
	}

	/**
	 * Test: isValidPlaceholder() prüft korrekt
	 */
	public function test_isValidPlaceholder_validates_correctly(): void {
		$this->assertTrue( $this->service->isValidPlaceholder( 'vorname' ) );
		$this->assertTrue( $this->service->isValidPlaceholder( 'stelle' ) );
		$this->assertFalse( $this->service->isValidPlaceholder( 'unbekannt' ) );
		$this->assertFalse( $this->service->isValidPlaceholder( 'xyz_feld' ) );
	}

	/**
	 * Test: Bewerbungs-ID wird korrekt formatiert
	 */
	public function test_application_id_is_formatted(): void {
		$template = 'Referenz: {bewerbung_id}';
		$context  = [
			'application' => [
				'id' => 42,
			],
		];

		$result = $this->service->replace( $template, $context );

		// Format: #YYYY-0042
		$this->assertMatchesRegularExpression( '/#\d{4}-0042/', $result );
	}

	/**
	 * Test: getPlaceholdersByGroup() gruppiert korrekt
	 */
	public function test_getPlaceholdersByGroup_groups_correctly(): void {
		$grouped = $this->service->getPlaceholdersByGroup();

		$this->assertArrayHasKey( 'candidate', $grouped );
		$this->assertArrayHasKey( 'application', $grouped );
		$this->assertArrayHasKey( 'job', $grouped );
		$this->assertArrayHasKey( 'company', $grouped );

		// Kandidaten-Gruppe enthält erwartete Felder.
		$this->assertArrayHasKey( 'vorname', $grouped['candidate']['placeholders'] );
		$this->assertArrayHasKey( 'nachname', $grouped['candidate']['placeholders'] );
	}

	/**
	 * Test: Unbekannte Platzhalter werden entfernt
	 */
	public function test_unknown_placeholders_are_removed(): void {
		$template = 'Text mit {unbekannter_platzhalter} drin.';
		$context  = [];

		$result = $this->service->replace( $template, $context );

		$this->assertEquals( 'Text mit  drin.', $result );
	}

	/**
	 * Test: Custom-Platzhalter werden hinzugefügt
	 */
	public function test_custom_placeholders_are_added(): void {
		$template = 'Custom: {custom_field}';
		$context  = [
			'custom' => [
				'custom_field' => 'Mein Wert',
			],
		];

		$result = $this->service->replace( $template, $context );

		$this->assertStringContainsString( 'Mein Wert', $result );
	}
}
