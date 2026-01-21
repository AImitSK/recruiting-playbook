<?php
/**
 * Base Test Case
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base Test Case mit Brain Monkey Integration
 */
abstract class TestCase extends PHPUnitTestCase {

	use MockeryPHPUnitIntegration;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Globale WordPress-Variablen mocken.
		$this->mockGlobalWpdb();
	}

	/**
	 * Teardown nach jedem Test
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Mock $wpdb global variable
	 */
	protected function mockGlobalWpdb(): void {
		global $wpdb;

		$wpdb = \Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->insert_id = 1;

		// Standard-Verhalten für prepare.
		$wpdb->shouldReceive( 'prepare' )
			->andReturnUsing( function ( $query, ...$args ) {
				return vsprintf( str_replace( [ '%s', '%d' ], [ "'%s'", '%d' ], $query ), $args );
			} );

		// Standard-Verhalten für esc_like.
		$wpdb->shouldReceive( 'esc_like' )
			->andReturnUsing( function ( $text ) {
				return addcslashes( $text, '_%\\' );
			} );
	}

	/**
	 * Hilfsmethode: WordPress-Funktionen mocken
	 *
	 * @param string $function Funktionsname.
	 * @param mixed  $return   Rückgabewert.
	 */
	protected function mockWpFunction( string $function, $return = null ): void {
		Monkey\Functions\when( $function )->justReturn( $return );
	}

	/**
	 * Hilfsmethode: Erwartete WordPress-Funktion.
	 *
	 * @param string $function Funktionsname.
	 * @return \Brain\Monkey\Expectation\Expectation
	 */
	protected function expectWpFunction( string $function ) {
		return Monkey\Functions\expect( $function );
	}
}
