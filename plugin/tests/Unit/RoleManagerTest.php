<?php
/**
 * RoleManager Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Core\RoleManager;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests für den RoleManager
 */
class RoleManagerTest extends TestCase {

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
	}

	/**
	 * Test: getAllCapabilities gibt alle Capabilities zurück
	 */
	public function test_get_all_capabilities_returns_expected_count(): void {
		$caps = RoleManager::getAllCapabilities();

		$this->assertCount( 25, $caps );
		$this->assertIsArray( $caps );
	}

	/**
	 * Test: getAllCapabilities enthält alle Basis-Capabilities
	 */
	public function test_get_all_capabilities_contains_base_caps(): void {
		$caps = RoleManager::getAllCapabilities();

		$this->assertContains( 'rp_manage_recruiting', $caps );
		$this->assertContains( 'rp_view_applications', $caps );
		$this->assertContains( 'rp_edit_applications', $caps );
		$this->assertContains( 'rp_delete_applications', $caps );
	}

	/**
	 * Test: getAllCapabilities enthält Notizen-Capabilities
	 */
	public function test_get_all_capabilities_contains_notes_caps(): void {
		$caps = RoleManager::getAllCapabilities();

		$this->assertContains( 'rp_view_notes', $caps );
		$this->assertContains( 'rp_create_notes', $caps );
		$this->assertContains( 'rp_edit_own_notes', $caps );
		$this->assertContains( 'rp_edit_others_notes', $caps );
		$this->assertContains( 'rp_delete_notes', $caps );
	}

	/**
	 * Test: getAllCapabilities enthält Admin-only Capabilities
	 */
	public function test_get_all_capabilities_contains_admin_caps(): void {
		$caps = RoleManager::getAllCapabilities();

		$this->assertContains( 'rp_manage_roles', $caps );
		$this->assertContains( 'rp_assign_jobs', $caps );
	}

	/**
	 * Test: getAllCapabilities enthält E-Mail-Capabilities
	 */
	public function test_get_all_capabilities_contains_email_caps(): void {
		$caps = RoleManager::getAllCapabilities();

		$this->assertContains( 'rp_read_email_templates', $caps );
		$this->assertContains( 'rp_create_email_templates', $caps );
		$this->assertContains( 'rp_edit_email_templates', $caps );
		$this->assertContains( 'rp_delete_email_templates', $caps );
		$this->assertContains( 'rp_send_emails', $caps );
		$this->assertContains( 'rp_view_email_log', $caps );
	}

	/**
	 * Test: Alle Capabilities beginnen mit rp_ Prefix
	 */
	public function test_all_capabilities_have_rp_prefix(): void {
		$caps = RoleManager::getAllCapabilities();

		foreach ( $caps as $cap ) {
			$this->assertStringStartsWith( 'rp_', $cap, "Capability '$cap' hat kein rp_ Prefix." );
		}
	}

	/**
	 * Test: getDefaults gibt Konfiguration für beide Custom Rollen zurück
	 */
	public function test_get_defaults_contains_both_roles(): void {
		$defaults = RoleManager::getDefaults();

		$this->assertArrayHasKey( 'rp_recruiter', $defaults );
		$this->assertArrayHasKey( 'rp_hiring_manager', $defaults );
		$this->assertCount( 2, $defaults );
	}

	/**
	 * Test: Recruiter-Defaults haben korrekte Capabilities
	 */
	public function test_recruiter_defaults_are_correct(): void {
		$defaults = RoleManager::getDefaults();
		$recruiter = $defaults['rp_recruiter'];

		// Recruiter kann Bewerbungen ansehen und bearbeiten, aber nicht löschen.
		$this->assertTrue( $recruiter['rp_view_applications'] );
		$this->assertTrue( $recruiter['rp_edit_applications'] );
		$this->assertFalse( $recruiter['rp_delete_applications'] );

		// Admin-only Capabilities sind immer false.
		$this->assertFalse( $recruiter['rp_manage_roles'] );
		$this->assertFalse( $recruiter['rp_assign_jobs'] );

		// E-Mail-Zugriff.
		$this->assertTrue( $recruiter['rp_send_emails'] );
		$this->assertTrue( $recruiter['rp_view_email_log'] );
	}

	/**
	 * Test: Hiring Manager-Defaults haben eingeschränkte Capabilities
	 */
	public function test_hiring_manager_defaults_are_correct(): void {
		$defaults = RoleManager::getDefaults();
		$manager = $defaults['rp_hiring_manager'];

		// Hiring Manager kann ansehen, aber nicht bearbeiten.
		$this->assertTrue( $manager['rp_view_applications'] );
		$this->assertFalse( $manager['rp_edit_applications'] );
		$this->assertFalse( $manager['rp_delete_applications'] );

		// Kein E-Mail-Versand.
		$this->assertFalse( $manager['rp_send_emails'] );
		$this->assertFalse( $manager['rp_view_email_log'] );

		// Kein Talent-Pool Zugriff.
		$this->assertFalse( $manager['rp_manage_talent_pool'] );

		// Aber Bewertung und Notizen.
		$this->assertTrue( $manager['rp_rate_applications'] );
		$this->assertTrue( $manager['rp_view_notes'] );
		$this->assertTrue( $manager['rp_create_notes'] );
	}

	/**
	 * Test: Default-Capabilities decken alle bekannten Capabilities ab (außer rp_manage_recruiting)
	 */
	public function test_defaults_cover_all_capabilities(): void {
		$defaults = RoleManager::getDefaults();
		$all_caps = RoleManager::getAllCapabilities();

		// rp_manage_recruiting wird automatisch vergeben, nicht in Defaults.
		$configurable_caps = array_filter( $all_caps, fn( $cap ) => 'rp_manage_recruiting' !== $cap );

		foreach ( $defaults as $role_slug => $role_caps ) {
			foreach ( $configurable_caps as $cap ) {
				$this->assertArrayHasKey(
					$cap,
					$role_caps,
					"Capability '$cap' fehlt in Defaults für '$role_slug'."
				);
			}
		}
	}

	/**
	 * Test: getCapabilityGroups gibt 6 Gruppen zurück
	 */
	public function test_get_capability_groups_returns_six_groups(): void {
		$groups = RoleManager::getCapabilityGroups();

		$this->assertCount( 6, $groups );
	}

	/**
	 * Test: getCapabilityGroups hat erwartete Gruppen-Keys
	 */
	public function test_get_capability_groups_has_expected_keys(): void {
		$groups = RoleManager::getCapabilityGroups();

		$this->assertArrayHasKey( 'applications', $groups );
		$this->assertArrayHasKey( 'notes', $groups );
		$this->assertArrayHasKey( 'evaluation', $groups );
		$this->assertArrayHasKey( 'email', $groups );
		$this->assertArrayHasKey( 'admin', $groups );
	}

	/**
	 * Test: Jede Capability-Gruppe hat label und capabilities
	 */
	public function test_capability_groups_have_required_structure(): void {
		$groups = RoleManager::getCapabilityGroups();

		foreach ( $groups as $key => $group ) {
			$this->assertArrayHasKey( 'label', $group, "Gruppe '$key' hat kein label." );
			$this->assertArrayHasKey( 'capabilities', $group, "Gruppe '$key' hat keine capabilities." );
			$this->assertNotEmpty( $group['capabilities'], "Gruppe '$key' hat leere capabilities." );
		}
	}

	/**
	 * Test: Capabilities in Gruppen decken alle bekannten Capabilities ab
	 */
	public function test_capability_groups_cover_all_caps(): void {
		$groups = RoleManager::getCapabilityGroups();
		$all_caps = RoleManager::getAllCapabilities();

		// rp_manage_recruiting ist die Basis-Cap und nicht in Gruppen.
		$expected_in_groups = array_filter( $all_caps, fn( $cap ) => 'rp_manage_recruiting' !== $cap );

		$grouped_caps = [];
		foreach ( $groups as $group ) {
			$grouped_caps = array_merge( $grouped_caps, array_keys( $group['capabilities'] ) );
		}

		foreach ( $expected_in_groups as $cap ) {
			$this->assertContains(
				$cap,
				$grouped_caps,
				"Capability '$cap' ist keiner Gruppe zugeordnet."
			);
		}
	}

	/**
	 * Test: getJobListingCapabilities gibt CPT-Capabilities zurück
	 */
	public function test_get_job_listing_capabilities(): void {
		$caps = RoleManager::getJobListingCapabilities();

		$this->assertIsArray( $caps );
		$this->assertNotEmpty( $caps );
		$this->assertContains( 'edit_job_listings', $caps );
		$this->assertContains( 'publish_job_listings', $caps );
		$this->assertContains( 'delete_job_listings', $caps );
		$this->assertContains( 'read_job_listing', $caps );
	}

	/**
	 * Test: register erstellt beide Custom Rollen
	 */
	public function test_register_creates_roles(): void {
		$role_recruiter = Mockery::mock( '\WP_Role' );
		$role_manager = Mockery::mock( '\WP_Role' );
		$admin_role = Mockery::mock( '\WP_Role' );
		$editor_role = Mockery::mock( '\WP_Role' );

		Functions\expect( 'add_role' )
			->once()
			->with( 'rp_recruiter', 'Recruiter', Mockery::type( 'array' ) )
			->andReturn( $role_recruiter );

		Functions\expect( 'add_role' )
			->once()
			->with( 'rp_hiring_manager', 'Hiring Manager', Mockery::type( 'array' ) )
			->andReturn( $role_manager );

		Functions\expect( 'get_option' )
			->once()
			->with( 'rp_role_capabilities', Mockery::type( 'array' ) )
			->andReturn( RoleManager::getDefaults() );

		Functions\when( 'get_role' )->alias( function ( $slug ) use ( $role_recruiter, $role_manager, $admin_role, $editor_role ) {
			return match ( $slug ) {
				'rp_recruiter'      => $role_recruiter,
				'rp_hiring_manager' => $role_manager,
				'administrator'     => $admin_role,
				'editor'            => $editor_role,
				default             => null,
			};
		} );

		// Capabilities werden auf allen Rollen gesetzt.
		$role_recruiter->shouldReceive( 'add_cap' )->andReturn( null );
		$role_recruiter->shouldReceive( 'remove_cap' )->andReturn( null );
		$role_manager->shouldReceive( 'add_cap' )->andReturn( null );
		$role_manager->shouldReceive( 'remove_cap' )->andReturn( null );
		$admin_role->shouldReceive( 'add_cap' )->andReturn( null );
		$editor_role->shouldReceive( 'add_cap' )->andReturn( null );

		RoleManager::register();

		// Assertions sind implizit: Mockery prüft die Erwartungen.
		$this->assertTrue( true );
	}

	/**
	 * Test: unregister entfernt Rollen und Capabilities
	 */
	public function test_unregister_removes_roles(): void {
		$admin_role = Mockery::mock( '\WP_Role' );
		$editor_role = Mockery::mock( '\WP_Role' );

		Functions\when( 'get_role' )->alias( function ( $slug ) use ( $admin_role, $editor_role ) {
			return match ( $slug ) {
				'administrator' => $admin_role,
				'editor'        => $editor_role,
				default         => null,
			};
		} );

		// Alle rp_* Capabilities werden entfernt.
		$admin_role->shouldReceive( 'remove_cap' )
			->times( count( RoleManager::getAllCapabilities() ) );
		$editor_role->shouldReceive( 'remove_cap' )
			->times( count( RoleManager::getAllCapabilities() ) );

		Functions\expect( 'remove_role' )
			->once()
			->with( 'rp_recruiter' );

		Functions\expect( 'remove_role' )
			->once()
			->with( 'rp_hiring_manager' );

		RoleManager::unregister();

		$this->assertTrue( true );
	}

	/**
	 * Test: assignCapabilities vergibt Recruiter-Capabilities korrekt
	 */
	public function test_assign_capabilities_for_recruiter(): void {
		$recruiter_role = Mockery::mock( '\WP_Role' );
		$manager_role = Mockery::mock( '\WP_Role' );
		$admin_role = Mockery::mock( '\WP_Role' );
		$editor_role = Mockery::mock( '\WP_Role' );

		Functions\expect( 'get_option' )
			->once()
			->with( 'rp_role_capabilities', Mockery::type( 'array' ) )
			->andReturn( RoleManager::getDefaults() );

		// get_role muss als Alias definiert werden, da es mit verschiedenen Args aufgerufen wird.
		Functions\when( 'get_role' )->alias( function ( $slug ) use ( $recruiter_role, $manager_role, $admin_role, $editor_role ) {
			return match ( $slug ) {
				'rp_recruiter'      => $recruiter_role,
				'rp_hiring_manager' => $manager_role,
				'administrator'     => $admin_role,
				'editor'            => $editor_role,
				default             => null,
			};
		} );

		// Recruiter: Capabilities mit true werden per add_cap gesetzt.
		$recruiter_role->shouldReceive( 'add_cap' )->andReturn( null );
		$recruiter_role->shouldReceive( 'remove_cap' )->andReturn( null );

		// Hiring Manager: Weniger Capabilities.
		$manager_role->shouldReceive( 'add_cap' )->andReturn( null );
		$manager_role->shouldReceive( 'remove_cap' )->andReturn( null );

		// Admin bekommt alle Capabilities.
		$admin_role->shouldReceive( 'add_cap' )->andReturn( null );

		// Editor bekommt Recruiter-ähnliche Capabilities.
		$editor_role->shouldReceive( 'add_cap' )->andReturn( null );

		RoleManager::assignCapabilities();

		// Verifizierung: rp_manage_recruiting wird Recruiter vergeben (weil rp_view_applications = true).
		$recruiter_role->shouldHaveReceived( 'add_cap' )
			->with( 'rp_manage_recruiting' )
			->once();

		// Admin bekommt alle 20 rp_* + Job Listing Capabilities.
		$expected_admin_calls = count( RoleManager::getAllCapabilities() )
			+ count( RoleManager::getJobListingCapabilities() );
		$admin_role->shouldHaveReceived( 'add_cap' )
			->times( $expected_admin_calls );
	}

	/**
	 * Test: hasCustomRole erkennt Recruiter
	 */
	public function test_has_custom_role_for_recruiter(): void {
		$user = Mockery::mock( '\WP_User' );
		$user->roles = [ 'rp_recruiter' ];
		$user->shouldReceive( 'exists' )->andReturn( true );

		Functions\expect( 'get_user_by' )
			->once()
			->with( 'id', 5 )
			->andReturn( $user );

		$this->assertTrue( RoleManager::hasCustomRole( 5 ) );
	}

	/**
	 * Test: hasCustomRole erkennt Hiring Manager
	 */
	public function test_has_custom_role_for_hiring_manager(): void {
		$user = Mockery::mock( '\WP_User' );
		$user->roles = [ 'rp_hiring_manager' ];
		$user->shouldReceive( 'exists' )->andReturn( true );

		Functions\expect( 'get_user_by' )
			->once()
			->with( 'id', 10 )
			->andReturn( $user );

		$this->assertTrue( RoleManager::hasCustomRole( 10 ) );
	}

	/**
	 * Test: hasCustomRole gibt false für Standard-Rolle zurück
	 */
	public function test_has_custom_role_for_standard_role(): void {
		$user = Mockery::mock( '\WP_User' );
		$user->roles = [ 'administrator' ];
		$user->shouldReceive( 'exists' )->andReturn( true );

		Functions\expect( 'get_user_by' )
			->once()
			->with( 'id', 1 )
			->andReturn( $user );

		$this->assertFalse( RoleManager::hasCustomRole( 1 ) );
	}

	/**
	 * Test: hasCustomRole gibt false für ungültigen User zurück
	 */
	public function test_has_custom_role_for_invalid_user(): void {
		Functions\expect( 'get_user_by' )
			->once()
			->with( 'id', 999 )
			->andReturn( false );

		$this->assertFalse( RoleManager::hasCustomRole( 999 ) );
	}

	/**
	 * Test: hasCustomRole ohne Parameter nutzt aktuellen User
	 */
	public function test_has_custom_role_for_current_user(): void {
		$user = Mockery::mock( '\WP_User' );
		$user->roles = [ 'rp_recruiter' ];
		$user->shouldReceive( 'exists' )->andReturn( true );

		Functions\expect( 'wp_get_current_user' )
			->once()
			->andReturn( $user );

		$this->assertTrue( RoleManager::hasCustomRole() );
	}

	/**
	 * Test: assignCapabilities vergibt rp_manage_recruiting wenn rp_view_applications aktiv
	 */
	public function test_assign_capabilities_adds_manage_recruiting_when_view_is_set(): void {
		$custom_config = [
			'rp_recruiter' => [
				'rp_view_applications' => true,
				'rp_edit_applications' => false,
			],
		];

		$recruiter_role = Mockery::mock( '\WP_Role' );
		$admin_role = Mockery::mock( '\WP_Role' );
		$editor_role = Mockery::mock( '\WP_Role' );

		Functions\expect( 'get_option' )
			->once()
			->with( 'rp_role_capabilities', Mockery::type( 'array' ) )
			->andReturn( $custom_config );

		Functions\when( 'get_role' )->alias( function ( $slug ) use ( $recruiter_role, $admin_role, $editor_role ) {
			return match ( $slug ) {
				'rp_recruiter'  => $recruiter_role,
				'administrator' => $admin_role,
				'editor'        => $editor_role,
				default         => null,
			};
		} );

		$recruiter_role->shouldReceive( 'add_cap' )->andReturn( null );
		$recruiter_role->shouldReceive( 'remove_cap' )->andReturn( null );
		$admin_role->shouldReceive( 'add_cap' )->andReturn( null );
		$editor_role->shouldReceive( 'add_cap' )->andReturn( null );

		RoleManager::assignCapabilities();

		// rp_manage_recruiting wird vergeben weil rp_view_applications = true.
		$recruiter_role->shouldHaveReceived( 'add_cap' )
			->with( 'rp_manage_recruiting' )
			->once();
	}
}
