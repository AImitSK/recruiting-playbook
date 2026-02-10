<?php
/**
 * PlaceholderService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

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
		Functions\when( 'get_option' )->alias( function( $option, $default = false ) {
			if ( 'rp_settings' === $option ) {
				return [
					'company_name'       => 'Test GmbH',
					'company_address'    => 'Teststraße 1, 12345 Berlin',
					'notification_email' => 'hr@test.de',
					'company_phone'      => '+49 30 12345',
					'contact_name'       => 'HR Team',
				];
			}
			if ( 'date_format' === $option ) {
				return 'd.m.Y';
			}
			if ( 'admin_email' === $option ) {
				return 'admin@test.de';
			}
			return $default;
		} );

		Functions\when( 'get_bloginfo' )->justReturn( 'Test Blog' );
		Functions\when( 'home_url' )->justReturn( 'https://test.de' );
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();

		// Mock wp_get_current_user.
		$mock_user = (object) [
			'ID'           => 1,
			'display_name' => 'Max Admin',
			'user_email'   => 'admin@test.de',
		];
		Functions\when( 'wp_get_current_user' )->justReturn( $mock_user );
		Functions\when( 'get_user_meta' )->justReturn( '' );

		// Mock date_i18n.
		Functions\when( 'date_i18n' )->alias( function( $format, $timestamp = null ) {
			return date( $format, $timestamp ?: time() );
		} );

		$this->service = new PlaceholderService();
	}

	/**
	 * Test: Platzhalter-Ersetzung im Text
	 */
	public function test_replace_placeholders_in_text(): void {
		$text = 'Hallo {vorname} {nachname}, willkommen bei {firma}!';
		$context = [
			'candidate' => [
				'first_name' => 'Max',
				'last_name'  => 'Mustermann',
			],
		];

		$result = $this->service->replace( $text, $context );

		$this->assertStringContainsString( 'Max', $result );
		$this->assertStringContainsString( 'Mustermann', $result );
		$this->assertStringContainsString( 'Test GmbH', $result );
		$this->assertStringNotContainsString( '{vorname}', $result );
		$this->assertStringNotContainsString( '{nachname}', $result );
		$this->assertStringNotContainsString( '{firma}', $result );
	}

	/**
	 * Test: Unbekannte Platzhalter werden entfernt
	 */
	public function test_unknown_placeholders_are_removed(): void {
		$text = 'Text mit {unbekannt} Platzhalter.';
		$context = [];

		$result = $this->service->replace( $text, $context );

		$this->assertEquals( 'Text mit  Platzhalter.', $result );
	}

	/**
	 * Test: Anrede männlich generieren
	 */
	public function test_formal_salutation_male(): void {
		$context = [
			'candidate' => [
				'salutation' => 'Herr',
				'first_name' => 'Max',
				'last_name'  => 'Mustermann',
			],
		];

		$values = $this->service->resolve( $context );

		$this->assertEquals( 'Herr', $values['anrede'] );
		$this->assertStringContainsString( 'Herr Mustermann', $values['anrede_formal'] );
	}

	/**
	 * Test: Anrede weiblich generieren
	 */
	public function test_formal_salutation_female(): void {
		$context = [
			'candidate' => [
				'salutation' => 'Frau',
				'first_name' => 'Maria',
				'last_name'  => 'Schmidt',
			],
		];

		$values = $this->service->resolve( $context );

		$this->assertEquals( 'Frau', $values['anrede'] );
		$this->assertStringContainsString( 'Frau Schmidt', $values['anrede_formal'] );
	}

	/**
	 * Test: Anrede ohne Geschlecht (neutral)
	 */
	public function test_formal_salutation_neutral(): void {
		$context = [
			'candidate' => [
				'salutation' => '',
				'first_name' => 'Kim',
				'last_name'  => 'Lee',
			],
		];

		$values = $this->service->resolve( $context );

		$this->assertEquals( '', $values['anrede'] );
		$this->assertStringContainsString( 'Lee', $values['anrede_formal'] );
	}

	/**
	 * Test: Bewerbungs-ID formatieren
	 */
	public function test_application_id_format(): void {
		$context = [
			'application' => [
				'id'         => 42,
				'created_at' => '2025-01-25 12:00:00',
				'status'     => 'new',
			],
		];

		$values = $this->service->resolve( $context );

		$this->assertMatchesRegularExpression( '/^#\d{4}-0042$/', $values['bewerbung_id'] );
	}

	/**
	 * Test: Status übersetzen
	 */
	public function test_status_translation(): void {
		$context = [
			'application' => [
				'id'     => 1,
				'status' => 'screening',
			],
		];

		$values = $this->service->resolve( $context );

		$this->assertEquals( 'In Prüfung', $values['bewerbung_status'] );
	}

	/**
	 * Test: Custom-Platzhalter übernehmen
	 */
	public function test_custom_placeholders(): void {
		$context = [
			'custom' => [
				'termin_datum'   => '15.02.2025',
				'termin_uhrzeit' => '14:00 Uhr',
				'termin_ort'     => 'Raum 302',
			],
		];

		$values = $this->service->resolve( $context );

		$this->assertEquals( '15.02.2025', $values['termin_datum'] );
		$this->assertEquals( '14:00 Uhr', $values['termin_uhrzeit'] );
		$this->assertEquals( 'Raum 302', $values['termin_ort'] );
	}

	/**
	 * Test: Platzhalter finden
	 */
	public function test_find_placeholders_in_text(): void {
		$text = 'Hallo {vorname}, Ihre Bewerbung für {stelle} wurde empfangen.';

		$placeholders = $this->service->findPlaceholders( $text );

		$this->assertContains( 'vorname', $placeholders );
		$this->assertContains( 'stelle', $placeholders );
		$this->assertCount( 2, $placeholders );
	}

	/**
	 * Test: Platzhalter-Validierung
	 */
	public function test_placeholder_validation(): void {
		$this->assertTrue( $this->service->isValidPlaceholder( 'vorname' ) );
		$this->assertTrue( $this->service->isValidPlaceholder( 'stelle' ) );
		$this->assertFalse( $this->service->isValidPlaceholder( 'nicht_existent' ) );
	}

	/**
	 * Test: Verfügbare Platzhalter abrufen
	 */
	public function test_get_available_placeholders(): void {
		$placeholders = $this->service->getAvailablePlaceholders();

		$this->assertIsArray( $placeholders );
		$this->assertArrayHasKey( 'vorname', $placeholders );
		$this->assertArrayHasKey( 'nachname', $placeholders );
		$this->assertArrayHasKey( 'stelle', $placeholders );
		$this->assertArrayHasKey( 'firma', $placeholders );
	}

	/**
	 * Test: Platzhalter nach Gruppen
	 */
	public function test_placeholders_by_group(): void {
		$grouped = $this->service->getPlaceholdersByGroup();

		$this->assertArrayHasKey( 'candidate', $grouped );
		$this->assertArrayHasKey( 'job', $grouped );
		$this->assertArrayHasKey( 'company', $grouped );

		$this->assertArrayHasKey( 'label', $grouped['candidate'] );
		$this->assertArrayHasKey( 'placeholders', $grouped['candidate'] );
	}

	/**
	 * Test: Vorschau-Werte generieren
	 */
	public function test_preview_values(): void {
		$preview = $this->service->getPreviewValues();

		$this->assertIsArray( $preview );
		$this->assertEquals( 'Max', $preview['vorname'] );
		$this->assertEquals( 'Mustermann', $preview['nachname'] );
		$this->assertEquals( 'Senior PHP Developer', $preview['stelle'] );
	}

	/**
	 * Test: Vorschau rendern
	 */
	public function test_render_preview(): void {
		$text = 'Hallo {vorname} {nachname}, Position: {stelle}';

		$rendered = $this->service->renderPreview( $text );

		$this->assertStringContainsString( 'Max', $rendered );
		$this->assertStringContainsString( 'Mustermann', $rendered );
		$this->assertStringContainsString( 'Senior PHP Developer', $rendered );
		$this->assertStringNotContainsString( '{vorname}', $rendered );
	}

	/**
	 * Test: Leerer Kandidatenname ergibt Fallback-Anrede
	 */
	public function test_empty_candidate_name_fallback(): void {
		$context = [
			'candidate' => [
				'salutation' => '',
				'first_name' => '',
				'last_name'  => '',
			],
		];

		$values = $this->service->resolve( $context );

		$this->assertStringContainsString( 'Guten Tag', $values['anrede_formal'] );
	}

	/**
	 * Test: Job-Platzhalter werden korrekt aufgelöst
	 */
	public function test_job_placeholders(): void {
		$context = [
			'job' => [
				'title'           => 'Frontend Developer',
				'location'        => 'München',
				'employment_type' => 'Vollzeit',
				'url'             => 'https://test.de/jobs/frontend',
			],
		];

		$values = $this->service->resolve( $context );

		$this->assertEquals( 'Frontend Developer', $values['stelle'] );
		$this->assertEquals( 'München', $values['stelle_ort'] );
		$this->assertEquals( 'Vollzeit', $values['stelle_typ'] );
		$this->assertEquals( 'https://test.de/jobs/frontend', $values['stelle_url'] );
	}
}
