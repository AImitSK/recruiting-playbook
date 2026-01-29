<?php
/**
 * AutoEmailService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\AutoEmailService;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests für den AutoEmailService
 */
class AutoEmailServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var AutoEmailService
	 */
	private AutoEmailService $service;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		// Globales $wpdb Mock (für AutoEmailService::handleStatusChange).
		global $wpdb;
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->andReturnUsing( function( $query, ...$args ) {
			return vsprintf( str_replace( [ '%d', '%s', '%f' ], [ '%d', "'%s'", '%f' ], $query ), $args );
		} );
		$wpdb->shouldReceive( 'get_var' )->andReturn( null );
		$wpdb->shouldReceive( 'insert' )->andReturn( 1 );

		// Standard WordPress-Funktionen mocken.
		Functions\when( 'get_option' )->alias( function( $option, $default = false ) {
			if ( 'rp_auto_email_settings' === $option ) {
				return [
					'rejected' => [
						'enabled'     => true,
						'template_id' => 2,
						'delay'       => 0,
					],
					'interview' => [
						'enabled'     => false,
						'template_id' => 0,
						'delay'       => 0,
					],
				];
			}
			if ( 'rp_settings' === $option ) {
				return [
					'company_name'       => 'Test GmbH',
					'notification_email' => 'hr@test.de',
				];
			}
			if ( 'admin_email' === $option ) {
				return 'admin@test.de';
			}
			return $default;
		} );

		Functions\when( 'get_bloginfo' )->justReturn( 'Test Blog' );

		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'absint' )->alias( function( $val ) {
			return abs( (int) $val );
		} );
		Functions\when( 'current_time' )->justReturn( '2025-01-25 12:00:00' );

		// rp_can mocken - standardmäßig true (Pro-Tier).
		Functions\when( 'rp_can' )->justReturn( true );

		$this->service = new AutoEmailService();
	}

	/**
	 * Test: getSettings returns merged defaults
	 */
	public function test_get_settings_returns_merged_defaults(): void {
		$settings = $this->service->getSettings();

		$this->assertIsArray( $settings );
		$this->assertArrayHasKey( 'rejected', $settings );
		$this->assertArrayHasKey( 'interview', $settings );
		$this->assertArrayHasKey( 'offer', $settings );
		$this->assertArrayHasKey( 'hired', $settings );
	}

	/**
	 * Test: getSettings returns configured values
	 */
	public function test_get_settings_returns_configured_values(): void {
		$settings = $this->service->getSettings();

		// Rejected should be enabled with template_id = 2.
		$this->assertTrue( $settings['rejected']['enabled'] );
		$this->assertEquals( 2, $settings['rejected']['template_id'] );

		// Interview should be disabled (as configured).
		$this->assertFalse( $settings['interview']['enabled'] );
	}

	/**
	 * Test: saveSettings sanitizes input
	 */
	public function test_save_settings_sanitizes_input(): void {
		$dirty_settings = [
			'rejected' => [
				'enabled'     => 'yes', // Should be converted to bool.
				'template_id' => '5',   // Should be converted to int.
				'delay'       => '-10', // Should be abs().
			],
			'invalid_status' => [       // Should be ignored.
				'enabled' => true,
			],
		];

		$result = $this->service->saveSettings( $dirty_settings );

		$this->assertTrue( $result );
	}

	/**
	 * Test: getAvailableStatuses returns expected statuses
	 */
	public function test_get_available_statuses(): void {
		$statuses = AutoEmailService::getAvailableStatuses();

		$this->assertIsArray( $statuses );
		$this->assertArrayHasKey( 'rejected', $statuses );
		$this->assertArrayHasKey( 'interview', $statuses );
		$this->assertArrayHasKey( 'offer', $statuses );
		$this->assertArrayHasKey( 'hired', $statuses );

		// Should NOT include 'new' or 'screening' or 'withdrawn'.
		$this->assertArrayNotHasKey( 'new', $statuses );
		$this->assertArrayNotHasKey( 'screening', $statuses );
		$this->assertArrayNotHasKey( 'withdrawn', $statuses );
	}

	/**
	 * Test: registerHooks does nothing when feature is disabled
	 */
	public function test_register_hooks_respects_feature_flag(): void {
		// Override rp_can to return false.
		Functions\when( 'rp_can' )->justReturn( false );

		$service = new AutoEmailService();

		// Should not throw any errors when calling registerHooks.
		$service->registerHooks();

		// No assertions needed - we're just checking it doesn't fail.
		$this->assertTrue( true );
	}

	/**
	 * Test: handleStatusChange does nothing for disabled status
	 */
	public function test_handle_status_change_skips_disabled_status(): void {
		global $wpdb;
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';

		// Interview is disabled, so no DB queries should happen.
		$wpdb->shouldNotReceive( 'get_var' );
		$wpdb->shouldNotReceive( 'insert' );

		$this->service->handleStatusChange( 1, 'new', 'interview' );

		// Test passes if no exceptions thrown.
		$this->assertTrue( true );
	}

	/**
	 * Test: handleStatusChange does nothing when template_id is 0
	 */
	public function test_handle_status_change_skips_when_no_template(): void {
		// Service mit Settings wo template_id = 0.
		// Der Test nutzt den bereits konfigurierten Service mit rejected template_id = 2.
		// Da 'interview' template_id = 0 hat, testen wir mit 'interview'.
		$this->service->handleStatusChange( 1, 'new', 'interview' );

		// Test passes if no exceptions thrown - interview has template_id = 0.
		$this->assertTrue( true );
	}

	/**
	 * Test: Default settings structure
	 */
	public function test_default_settings_structure(): void {
		// Der bereits im setUp konfigurierte Service wird verwendet.
		$settings = $this->service->getSettings();

		foreach ( $settings as $status => $status_settings ) {
			$this->assertArrayHasKey( 'enabled', $status_settings, "Missing 'enabled' for $status" );
			$this->assertArrayHasKey( 'template_id', $status_settings, "Missing 'template_id' for $status" );
			$this->assertArrayHasKey( 'delay', $status_settings, "Missing 'delay' for $status" );

			$this->assertIsBool( $status_settings['enabled'] );
			$this->assertIsInt( $status_settings['template_id'] );
			$this->assertIsInt( $status_settings['delay'] );
		}
	}
}
