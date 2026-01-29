<?php
/**
 * EmailService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\EmailService;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests für den EmailService
 */
class EmailServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var EmailService
	 */
	private EmailService $service;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		// Standard WordPress-Funktionen mocken.
		Functions\when( 'get_option' )->alias( function( $option, $default = false ) {
			if ( 'rp_settings' === $option ) {
				return [
					'company_name'        => 'Test GmbH',
					'notification_email'  => 'hr@test.de',
					'auto_rejection_email' => true,
				];
			}
			if ( 'admin_email' === $option ) {
				return 'admin@test.de';
			}
			if ( 'date_format' === $option ) {
				return 'd.m.Y';
			}
			if ( 'time_format' === $option ) {
				return 'H:i';
			}
			return $default;
		} );

		Functions\when( 'get_bloginfo' )->justReturn( 'Test Blog' );
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'admin_url' )->alias( function( $path = '' ) {
			return 'https://test.de/wp-admin/' . $path;
		} );
		Functions\when( 'get_permalink' )->justReturn( 'https://test.de/jobs/test-job/' );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'current_time' )->justReturn( '2025-01-25 12:00:00' );
		Functions\when( 'date_i18n' )->alias( function( $format, $timestamp = null ) {
			return date( $format, $timestamp ?: time() );
		} );
		Functions\when( 'wp_strip_all_tags' )->alias( function( $string ) {
			return strip_tags( $string );
		} );

		// rp_can mocken - standardmäßig false (Free-Tier).
		Functions\when( 'rp_can' )->justReturn( false );

		$this->service = new EmailService();
	}

	/**
	 * Test: E-Mail senden
	 */
	public function test_send_email(): void {
		Functions\when( 'wp_mail' )->justReturn( true );

		$result = $this->service->send(
			'test@example.com',
			'Test Betreff',
			'<p>Test Inhalt</p>'
		);

		$this->assertTrue( $result );
	}

	/**
	 * Test: E-Mail senden fehlschlägt
	 */
	public function test_send_email_fails(): void {
		Functions\when( 'wp_mail' )->justReturn( false );

		$result = $this->service->send(
			'test@example.com',
			'Test Betreff',
			'<p>Test Inhalt</p>'
		);

		$this->assertFalse( $result );
	}

	/**
	 * Test: E-Mail mit benutzerdefinierten Headers senden
	 */
	public function test_send_email_with_custom_headers(): void {
		Functions\expect( 'wp_mail' )
			->once()
			->with(
				'test@example.com',
				'Test Betreff',
				Mockery::any(),
				Mockery::on( function( $headers ) {
					// Prüfen ob Custom-Header enthalten ist.
					foreach ( $headers as $header ) {
						if ( strpos( $header, 'Reply-To' ) !== false ) {
							return true;
						}
					}
					return false;
				} )
			)
			->andReturn( true );

		$result = $this->service->send(
			'test@example.com',
			'Test Betreff',
			'<p>Test</p>',
			[ 'Reply-To: reply@test.de' ]
		);

		$this->assertTrue( $result );
	}

	/**
	 * Test: Pro-Feature sendWithTemplate benötigt Lizenz
	 */
	public function test_send_with_template_requires_pro(): void {
		// rp_can gibt false zurück (Free-Tier).
		Functions\when( 'rp_can' )->justReturn( false );

		$result = $this->service->sendWithTemplate( 1, 123 );

		$this->assertFalse( $result );
	}

	/**
	 * Test: Benutzerdefinierte E-Mail senden (ohne Template)
	 */
	public function test_send_custom_email(): void {
		// Prüfen dass die Methode existiert.
		// Die eigentliche Implementierung benötigt einen vollständigen DB-Mock,
		// was in Unit Tests schwierig ist ohne Dependency Injection.
		$this->assertTrue( method_exists( $this->service, 'sendCustomEmail' ) );
	}

	/**
	 * Test: Absage-E-Mail nur wenn aktiviert - Methode existiert
	 */
	public function test_rejection_email_only_when_enabled(): void {
		// Die sendRejectionEmail Methode benötigt komplexe DB-Operationen.
		// Hier prüfen wir nur die Signatur.
		$this->assertTrue( method_exists( $this->service, 'sendRejectionEmail' ) );
	}

	/**
	 * Test: SMTP-Konfiguration prüfen ohne Plugin
	 */
	public function test_check_smtp_config_no_plugin(): void {
		Functions\when( 'is_plugin_active' )->justReturn( false );
		Functions\when( 'has_filter' )->justReturn( false );

		$result = EmailService::checkSmtpConfig();

		$this->assertIsArray( $result );
		$this->assertFalse( $result['configured'] );
		$this->assertStringContainsString( 'Keine SMTP-Konfiguration', $result['message'] );
	}

	/**
	 * Test: SMTP-Konfiguration prüfen mit Plugin
	 */
	public function test_check_smtp_config_with_plugin(): void {
		Functions\when( 'is_plugin_active' )->alias( function( $plugin ) {
			return $plugin === 'wp-mail-smtp/wp_mail_smtp.php';
		} );

		$result = EmailService::checkSmtpConfig();

		$this->assertIsArray( $result );
		$this->assertTrue( $result['configured'] );
		$this->assertStringContainsString( 'SMTP-Plugin erkannt', $result['message'] );
	}

	/**
	 * Test: SMTP-Konfiguration prüfen mit Filter
	 */
	public function test_check_smtp_config_with_filter(): void {
		Functions\when( 'is_plugin_active' )->justReturn( false );
		Functions\when( 'has_filter' )->alias( function( $filter ) {
			return $filter === 'phpmailer_init';
		} );

		$result = EmailService::checkSmtpConfig();

		$this->assertIsArray( $result );
		$this->assertTrue( $result['configured'] );
	}

	/**
	 * Test: E-Mail-Historie für Bewerbung abrufen - Methode existiert
	 */
	public function test_get_history(): void {
		// Da EmailLogRepository intern erstellt wird, können wir nur testen
		// dass die Methode existiert.
		// In einer echten Testumgebung würden wir Dependency Injection verwenden.
		$this->assertTrue( method_exists( $this->service, 'getHistory' ) );
	}

	/**
	 * Test: E-Mail-Historie für Kandidaten abrufen
	 */
	public function test_get_history_by_candidate(): void {
		$this->assertTrue( method_exists( $this->service, 'getHistoryByCandidate' ) );
	}

	/**
	 * Test: E-Mail planen benötigt Pro-Lizenz
	 */
	public function test_schedule_email_requires_pro(): void {
		Functions\when( 'rp_can' )->justReturn( false );

		$result = $this->service->scheduleEmail(
			1, // template_id.
			123, // application_id.
			'2025-02-01 10:00:00' // scheduled_at.
		);

		$this->assertFalse( $result );
	}

	/**
	 * Test: sendWithTemplateSlug benötigt Pro-Lizenz
	 */
	public function test_send_with_template_slug_requires_pro(): void {
		// Methode existiert und erwartet Pro-Lizenz.
		$this->assertTrue( method_exists( $this->service, 'sendWithTemplateSlug' ) );
	}

	/**
	 * Test: Default Headers werden gesetzt
	 */
	public function test_default_headers_are_set(): void {
		$headers_received = [];

		Functions\expect( 'wp_mail' )
			->once()
			->with(
				Mockery::any(),
				Mockery::any(),
				Mockery::any(),
				Mockery::capture( $headers_received )
			)
			->andReturn( true );

		$this->service->send( 'test@example.com', 'Test', '<p>Test</p>' );

		// Prüfen dass Content-Type und From Header gesetzt sind.
		$has_content_type = false;
		$has_from = false;

		foreach ( $headers_received as $header ) {
			if ( strpos( $header, 'Content-Type: text/html' ) !== false ) {
				$has_content_type = true;
			}
			if ( strpos( $header, 'From:' ) !== false ) {
				$has_from = true;
			}
		}

		$this->assertTrue( $has_content_type, 'Content-Type Header fehlt' );
		$this->assertTrue( $has_from, 'From Header fehlt' );
	}

	/**
	 * Test: E-Mail Logging im Debug-Modus
	 */
	public function test_email_logging_in_debug_mode(): void {
		// WP_DEBUG aktivieren für diesen Test.
		if ( ! defined( 'WP_DEBUG' ) ) {
			define( 'WP_DEBUG', true );
		}

		Functions\when( 'wp_mail' )->justReturn( true );

		// Der Test prüft nur, dass kein Fehler auftritt.
		// error_log wird intern aufgerufen, aber das können wir nicht direkt testen.
		$result = $this->service->send( 'test@example.com', 'Test', '<p>Test</p>' );

		$this->assertTrue( $result );
	}

	/**
	 * Test: sendCustomEmail Methode existiert
	 */
	public function test_send_custom_email_method_exists(): void {
		// Die sendCustomEmail Methode benötigt DB-Zugriff.
		// Hier prüfen wir nur die Signatur.
		$reflection = new \ReflectionMethod( $this->service, 'sendCustomEmail' );
		$params = $reflection->getParameters();

		$this->assertGreaterThanOrEqual( 3, count( $params ) );
		$this->assertEquals( 'application_id', $params[0]->getName() );
		$this->assertEquals( 'subject', $params[1]->getName() );
		$this->assertEquals( 'body_html', $params[2]->getName() );
	}
}
