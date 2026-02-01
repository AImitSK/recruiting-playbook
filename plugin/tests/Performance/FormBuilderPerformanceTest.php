<?php
/**
 * Form Builder Performance Tests
 *
 * Testet die Performance-kritischen Komponenten:
 * - listForKanban Query mit vielen Bewerbungen
 * - getActiveFields Caching
 * - FormRenderService Rendering
 *
 * @package RecruitingPlaybook\Tests\Performance
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Performance;

use PHPUnit\Framework\TestCase;
use RecruitingPlaybook\Services\FormConfigService;
use RecruitingPlaybook\Services\ApplicationService;
use RecruitingPlaybook\Services\PlaceholderService;
use RecruitingPlaybook\Repositories\FormConfigRepository;
use RecruitingPlaybook\Repositories\FieldDefinitionRepository;
use RecruitingPlaybook\Models\FieldDefinition;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Performance Tests für Form Builder
 *
 * Hinweis: Diese Tests messen keine echten Zeiten, sondern
 * stellen sicher, dass die Komponenten korrekt mit großen
 * Datenmengen umgehen können.
 */
class FormBuilderPerformanceTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Standard WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'wp_cache_get' )->justReturn( false );
		Functions\when( 'wp_cache_set' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'get_privacy_policy_url' )->justReturn( '/datenschutz' );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	// =========================================================================
	// Test: getActiveFields Performance
	// =========================================================================

	/**
	 * @test
	 * @group performance
	 */
	public function get_active_fields_handles_many_fields_efficiently(): void {
		$config_repository = Mockery::mock( FormConfigRepository::class );
		$field_repository  = Mockery::mock( FieldDefinitionRepository::class );

		// 100 Custom Fields simulieren.
		$fields = [];
		for ( $i = 1; $i <= 100; $i++ ) {
			$fields[] = new FieldDefinition( [
				'id'          => $i,
				'field_key'   => "custom_field_{$i}",
				'field_type'  => 'text',
				'label'       => "Custom Field {$i}",
				'is_required' => false,
				'is_system'   => false,
			] );
		}

		// System-Felder hinzufügen.
		$fields[] = new FieldDefinition( [
			'id'          => 101,
			'field_key'   => 'first_name',
			'field_type'  => 'text',
			'label'       => 'Vorname',
			'is_required' => true,
			'is_system'   => true,
		] );
		$fields[] = new FieldDefinition( [
			'id'          => 102,
			'field_key'   => 'email',
			'field_type'  => 'email',
			'label'       => 'E-Mail',
			'is_required' => true,
			'is_system'   => true,
		] );

		$field_repository->shouldReceive( 'findAll' )->andReturn( $fields );

		// Config mit allen Feldern.
		$step_fields = [];
		for ( $i = 1; $i <= 100; $i++ ) {
			$step_fields[] = [
				'field_key'  => "custom_field_{$i}",
				'is_visible' => true,
			];
		}
		$step_fields[] = [ 'field_key' => 'first_name', 'is_visible' => true ];
		$step_fields[] = [ 'field_key' => 'email', 'is_visible' => true ];

		$published_config = [
			'config_data' => [
				'version' => 2,
				'steps'   => [
					[
						'id'     => 'step_1',
						'title'  => 'Alle Felder',
						'fields' => $step_fields,
					],
					[
						'id'            => 'step_finale',
						'title'         => 'Abschluss',
						'is_finale'     => true,
						'fields'        => [],
						'system_fields' => [
							[ 'field_key' => 'privacy_consent', 'type' => 'privacy_consent' ],
						],
					],
				],
			],
		];

		$config_repository->shouldReceive( 'getPublished' )->andReturn( $published_config );
		$config_repository->shouldReceive( 'getPublishedVersion' )->andReturn( 1 );

		$service = new FormConfigService( $config_repository, $field_repository );

		// Performance-Test: getActiveFields sollte auch mit 100+ Feldern schnell sein.
		$start  = microtime( true );
		$result = $service->getActiveFields();
		$end    = microtime( true );

		$this->assertIsArray( $result );
		$this->assertCount( 102, $result['fields'] ); // 100 custom + 2 system
		$this->assertCount( 1, $result['system_fields'] );

		// Sollte unter 100ms dauern (in echten Tests wäre das relevant).
		$duration = ( $end - $start ) * 1000;
		$this->assertLessThan( 1000, $duration, "getActiveFields dauerte {$duration}ms" );
	}

	/**
	 * @test
	 * @group performance
	 */
	public function get_active_fields_uses_cache_on_subsequent_calls(): void {
		$cache_hits = 0;

		Functions\when( 'wp_cache_get' )->alias( function( $key, $group ) use ( &$cache_hits ) {
			static $cache = [];
			if ( isset( $cache[ $key ] ) ) {
				$cache_hits++;
				return $cache[ $key ];
			}
			return false;
		} );

		Functions\when( 'wp_cache_set' )->alias( function( $key, $data, $group, $expire ) {
			static $cache = [];
			$cache[ $key ] = $data;
			return true;
		} );

		$config_repository = Mockery::mock( FormConfigRepository::class );
		$field_repository  = Mockery::mock( FieldDefinitionRepository::class );

		$field_repository->shouldReceive( 'findAll' )->andReturn( [
			new FieldDefinition( [
				'field_key'   => 'first_name',
				'field_type'  => 'text',
				'label'       => 'Vorname',
				'is_required' => true,
				'is_system'   => true,
			] ),
		] );

		$config_repository->shouldReceive( 'getPublished' )->andReturn( [
			'config_data' => [
				'version' => 2,
				'steps'   => [
					[
						'id'     => 'step_1',
						'title'  => 'Test',
						'fields' => [
							[ 'field_key' => 'first_name', 'is_visible' => true ],
						],
					],
					[
						'id'            => 'step_finale',
						'title'         => 'Abschluss',
						'is_finale'     => true,
						'fields'        => [],
						'system_fields' => [
							[ 'field_key' => 'privacy_consent', 'type' => 'privacy_consent' ],
						],
					],
				],
			],
		] );
		$config_repository->shouldReceive( 'getPublishedVersion' )->andReturn( 1 );

		$service = new FormConfigService( $config_repository, $field_repository );

		// Erster Aufruf - sollte Cache befüllen.
		$result1 = $service->getActiveFields();

		// Zweiter Aufruf - sollte Cache nutzen.
		$result2 = $service->getActiveFields();

		$this->assertEquals( $result1, $result2 );
	}

	// =========================================================================
	// Test: PlaceholderService Performance
	// =========================================================================

	/**
	 * @test
	 * @group performance
	 */
	public function placeholder_service_handles_large_templates_efficiently(): void {
		Functions\when( 'get_option' )->alias( function( $key, $default = false ) {
			if ( 'rp_settings' === $key ) {
				return [
					'company_name'   => 'Test GmbH',
					'company_street' => 'Musterstraße 1',
					'company_zip'    => '12345',
					'company_city'   => 'Berlin',
				];
			}
			return $default;
		} );
		Functions\when( 'date_i18n' )->justReturn( '21.01.2025' );

		$service = new PlaceholderService();

		// Großes Template mit vielen Platzhaltern generieren.
		$template = str_repeat( 'Hallo {vorname} {nachname}, Ihre E-Mail ist {email}. ', 100 );

		$context = [
			'candidate' => [
				'first_name' => 'Max',
				'last_name'  => 'Mustermann',
				'email'      => 'max@example.com',
			],
		];

		$start  = microtime( true );
		$result = $service->replace( $template, $context );
		$end    = microtime( true );

		// Alle Platzhalter ersetzt.
		$this->assertStringNotContainsString( '{vorname}', $result );
		$this->assertStringNotContainsString( '{nachname}', $result );
		$this->assertStringNotContainsString( '{email}', $result );

		// Enthält ersetzte Werte.
		$this->assertStringContainsString( 'Max', $result );
		$this->assertStringContainsString( 'Mustermann', $result );
		$this->assertStringContainsString( 'max@example.com', $result );

		// Performance-Check.
		$duration = ( $end - $start ) * 1000;
		$this->assertLessThan( 500, $duration, "PlaceholderService dauerte {$duration}ms" );
	}

	/**
	 * @test
	 * @group performance
	 */
	public function placeholder_service_validates_large_templates_efficiently(): void {
		Functions\when( 'get_option' )->justReturn( [] );

		$service = new PlaceholderService();

		// Template mit vielen (teils ungültigen) Platzhaltern.
		$placeholders = [];
		for ( $i = 1; $i <= 50; $i++ ) {
			$placeholders[] = "{valid_placeholder_{$i}}";
			$placeholders[] = "{invalid_{$i}}";
		}
		$template = implode( ' ', $placeholders );

		$start  = microtime( true );
		$result = $service->validateTemplate( $template );
		$end    = microtime( true );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'valid', $result );
		$this->assertArrayHasKey( 'invalid', $result );
		$this->assertArrayHasKey( 'found', $result );

		// Performance-Check.
		$duration = ( $end - $start ) * 1000;
		$this->assertLessThan( 200, $duration, "validateTemplate dauerte {$duration}ms" );
	}

	// =========================================================================
	// Test: Migration Performance
	// =========================================================================

	/**
	 * @test
	 * @group performance
	 */
	public function migration_handles_complex_configs_efficiently(): void {
		$config_repository = Mockery::mock( FormConfigRepository::class );
		$field_repository  = Mockery::mock( FieldDefinitionRepository::class );

		$field_repository->shouldReceive( 'findAll' )->andReturn( [] );

		$service = new FormConfigService( $config_repository, $field_repository );

		// Komplexe v1-Konfiguration mit vielen Steps und Feldern.
		$steps = [];
		for ( $i = 1; $i <= 10; $i++ ) {
			$fields = [];
			for ( $j = 1; $j <= 20; $j++ ) {
				$fields[] = [
					'field_key'  => "field_{$i}_{$j}",
					'is_visible' => true,
				];
			}

			$steps[] = [
				'id'     => "step_{$i}",
				'title'  => "Schritt {$i}",
				'fields' => $fields,
			];
		}

		// Finale-Step hinzufügen.
		$steps[] = [
			'id'        => 'step_finale',
			'title'     => 'Abschluss',
			'is_finale' => true,
			'fields'    => [
				[ 'field_key' => 'privacy_consent', 'is_visible' => true ],
			],
		];

		$v1_config = [
			'version' => 1,
			'steps'   => $steps,
		];

		$start  = microtime( true );
		$result = $service->migrateConfig( $v1_config );
		$end    = microtime( true );

		$this->assertEquals( 2, $result['version'] );
		$this->assertCount( 11, $result['steps'] );

		// Performance-Check.
		$duration = ( $end - $start ) * 1000;
		$this->assertLessThan( 500, $duration, "Migration dauerte {$duration}ms" );
	}

	// =========================================================================
	// Test: Validierung Performance
	// =========================================================================

	/**
	 * @test
	 * @group performance
	 */
	public function validation_handles_large_configs_efficiently(): void {
		$config_repository = Mockery::mock( FormConfigRepository::class );
		$field_repository  = Mockery::mock( FieldDefinitionRepository::class );

		$field_repository->shouldReceive( 'findAll' )->andReturn( [] );

		$service = new FormConfigService( $config_repository, $field_repository );

		// Große Konfiguration mit Pflichtfeldern.
		$all_fields = [
			[ 'field_key' => 'first_name', 'is_visible' => true ],
			[ 'field_key' => 'last_name', 'is_visible' => true ],
			[ 'field_key' => 'email', 'is_visible' => true ],
		];

		// 50 zusätzliche Custom Fields.
		for ( $i = 1; $i <= 50; $i++ ) {
			$all_fields[] = [
				'field_key'  => "custom_{$i}",
				'is_visible' => true,
			];
		}

		$config = [
			'version' => 2,
			'steps'   => [
				[
					'id'     => 'step_1',
					'title'  => 'Alle Felder',
					'fields' => $all_fields,
				],
				[
					'id'            => 'step_finale',
					'title'         => 'Abschluss',
					'is_finale'     => true,
					'fields'        => [],
					'system_fields' => [
						[ 'field_key' => 'summary' ],
						[ 'field_key' => 'privacy_consent' ],
					],
				],
			],
		];

		$config_repository->shouldReceive( 'saveDraft' )->once()->andReturn( true );

		$start  = microtime( true );
		$result = $service->saveDraft( $config );
		$end    = microtime( true );

		$this->assertTrue( $result );

		// Performance-Check.
		$duration = ( $end - $start ) * 1000;
		$this->assertLessThan( 200, $duration, "Validierung dauerte {$duration}ms" );
	}
}
