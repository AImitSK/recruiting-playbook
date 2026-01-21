<?php
/**
 * Unit Tests für SpamProtection
 *
 * @package RecruitingPlaybook\Tests\Unit
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Services\SpamProtection;
use RecruitingPlaybook\Tests\TestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * SpamProtection Test
 */
class SpamProtectionTest extends TestCase {

	private SpamProtection $service;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'do_action' )->justReturn( null );

		$this->service = new SpamProtection();
	}

	/**
	 * Helper: Mock WP_REST_Request erstellen
	 */
	private function createMockRequest( array $params = [] ): \WP_REST_Request {
		$request = Mockery::mock( 'WP_REST_Request' );

		$request->shouldReceive( 'get_param' )
			->andReturnUsing( function ( $key ) use ( $params ) {
				return $params[ $key ] ?? null;
			} );

		$request->shouldReceive( 'get_header' )
			->with( 'user-agent' )
			->andReturn( 'TestBrowser/1.0' );

		return $request;
	}

	/**
	 * Test: check() erkennt Honeypot-Spam
	 */
	public function test_check_detects_honeypot_spam(): void {
		$request = $this->createMockRequest( [
			'_hp_field' => 'spam content', // Bot hat Honeypot ausgefüllt.
		] );

		$result = $this->service->check( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'spam_detected', $result->get_error_code() );
	}

	/**
	 * Test: check() akzeptiert leeres Honeypot-Feld
	 */
	public function test_check_accepts_empty_honeypot(): void {
		$request = $this->createMockRequest( [
			'_hp_field'        => '', // Honeypot leer (gut).
			'_form_timestamp'  => time() - 10, // 10 Sekunden (OK).
		] );

		Functions\when( 'get_transient' )->justReturn( 0 );
		Functions\when( 'set_transient' )->justReturn( true );

		$result = $this->service->check( $request );

		$this->assertTrue( $result );
	}

	/**
	 * Test: check() erkennt zu schnelle Formular-Übermittlung
	 */
	public function test_check_detects_fast_submission(): void {
		$request = $this->createMockRequest( [
			'_hp_field'       => '',
			'_form_timestamp' => time() - 2, // Nur 2 Sekunden (zu schnell).
		] );

		$result = $this->service->check( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'submission_too_fast', $result->get_error_code() );
	}

	/**
	 * Test: check() akzeptiert normale Formular-Zeit
	 */
	public function test_check_accepts_normal_submission_time(): void {
		$request = $this->createMockRequest( [
			'_hp_field'       => '',
			'_form_timestamp' => time() - 30, // 30 Sekunden (OK).
		] );

		Functions\when( 'get_transient' )->justReturn( 0 );
		Functions\when( 'set_transient' )->justReturn( true );

		$result = $this->service->check( $request );

		$this->assertTrue( $result );
	}

	/**
	 * Test: check() überspringt Time-Check ohne Timestamp
	 */
	public function test_check_skips_time_check_without_timestamp(): void {
		$request = $this->createMockRequest( [
			'_hp_field'       => '',
			'_form_timestamp' => null, // Kein Timestamp.
		] );

		Functions\when( 'get_transient' )->justReturn( 0 );
		Functions\when( 'set_transient' )->justReturn( true );

		$result = $this->service->check( $request );

		$this->assertTrue( $result );
	}

	/**
	 * Test: check() erkennt Rate-Limit-Überschreitung
	 */
	public function test_check_detects_rate_limit_exceeded(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';

		$request = $this->createMockRequest( [
			'_hp_field'       => '',
			'_form_timestamp' => time() - 10,
		] );

		Functions\when( 'get_transient' )->justReturn( 10 ); // Bereits 10 Versuche.

		$result = $this->service->check( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rate_limit_exceeded', $result->get_error_code() );

		unset( $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Test: check() erlaubt Anfragen unter Rate-Limit
	 */
	public function test_check_allows_requests_under_rate_limit(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';

		$request = $this->createMockRequest( [
			'_hp_field'       => '',
			'_form_timestamp' => time() - 10,
		] );

		Functions\when( 'get_transient' )->justReturn( 2 ); // Erst 2 Versuche.
		Functions\when( 'set_transient' )->justReturn( true );

		$result = $this->service->check( $request );

		$this->assertTrue( $result );

		unset( $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Test: getHoneypotField() generiert HTML
	 */
	public function test_get_honeypot_field_generates_html(): void {
		$html = SpamProtection::getHoneypotField();

		$this->assertStringContainsString( '_hp_field', $html );
		$this->assertStringContainsString( 'aria-hidden="true"', $html );
		$this->assertStringContainsString( 'tabindex="-1"', $html );
		$this->assertStringContainsString( 'left:-9999px', $html );
	}

	/**
	 * Test: getTimestampField() generiert HTML mit aktuellem Timestamp
	 */
	public function test_get_timestamp_field_generates_html_with_timestamp(): void {
		$before = time();
		$html = SpamProtection::getTimestampField();
		$after = time();

		$this->assertStringContainsString( '_form_timestamp', $html );
		$this->assertStringContainsString( 'type="hidden"', $html );

		// Timestamp sollte zwischen before und after liegen.
		preg_match( '/value="(\d+)"/', $html, $matches );
		$timestamp = (int) $matches[1];

		$this->assertGreaterThanOrEqual( $before, $timestamp );
		$this->assertLessThanOrEqual( $after, $timestamp );
	}
}
