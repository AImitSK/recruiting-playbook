<?php
/**
 * Unit Tests für ApplicationStatus
 *
 * @package RecruitingPlaybook\Tests\Unit
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Constants\ApplicationStatus;
use RecruitingPlaybook\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * ApplicationStatus Test
 */
class ApplicationStatusTest extends TestCase {

	/**
	 * Test: Alle Status sind definiert
	 */
	public function test_all_statuses_are_defined(): void {
		$this->assertEquals( 'new', ApplicationStatus::NEW );
		$this->assertEquals( 'screening', ApplicationStatus::SCREENING );
		$this->assertEquals( 'interview', ApplicationStatus::INTERVIEW );
		$this->assertEquals( 'offer', ApplicationStatus::OFFER );
		$this->assertEquals( 'hired', ApplicationStatus::HIRED );
		$this->assertEquals( 'rejected', ApplicationStatus::REJECTED );
		$this->assertEquals( 'withdrawn', ApplicationStatus::WITHDRAWN );
	}

	/**
	 * Test: getAll liefert alle Status mit Labels
	 */
	public function test_get_all_returns_all_statuses_with_labels(): void {
		Functions\when( '__' )->returnArg( 1 );

		$all = ApplicationStatus::getAll();

		$this->assertIsArray( $all );
		$this->assertCount( 7, $all );
		$this->assertArrayHasKey( 'new', $all );
		$this->assertArrayHasKey( 'hired', $all );
		$this->assertArrayHasKey( 'rejected', $all );
	}

	/**
	 * Test: getColor liefert Farben für jeden Status
	 */
	public function test_get_color_returns_hex_color_for_each_status(): void {
		$this->assertMatchesRegularExpression( '/^#[0-9a-f]{6}$/i', ApplicationStatus::getColor( 'new' ) );
		$this->assertMatchesRegularExpression( '/^#[0-9a-f]{6}$/i', ApplicationStatus::getColor( 'hired' ) );
		$this->assertMatchesRegularExpression( '/^#[0-9a-f]{6}$/i', ApplicationStatus::getColor( 'rejected' ) );
		$this->assertMatchesRegularExpression( '/^#[0-9a-f]{6}$/i', ApplicationStatus::getColor( 'unknown' ) );
	}

	/**
	 * Test: getActiveStatuses liefert aktive Status
	 */
	public function test_get_active_statuses_returns_active_only(): void {
		$active = ApplicationStatus::getActiveStatuses();

		$this->assertContains( ApplicationStatus::NEW, $active );
		$this->assertContains( ApplicationStatus::SCREENING, $active );
		$this->assertContains( ApplicationStatus::INTERVIEW, $active );
		$this->assertContains( ApplicationStatus::OFFER, $active );
		$this->assertNotContains( ApplicationStatus::HIRED, $active );
		$this->assertNotContains( ApplicationStatus::REJECTED, $active );
	}

	/**
	 * Test: getClosedStatuses liefert abgeschlossene Status
	 */
	public function test_get_closed_statuses_returns_closed_only(): void {
		$closed = ApplicationStatus::getClosedStatuses();

		$this->assertContains( ApplicationStatus::HIRED, $closed );
		$this->assertContains( ApplicationStatus::REJECTED, $closed );
		$this->assertContains( ApplicationStatus::WITHDRAWN, $closed );
		$this->assertNotContains( ApplicationStatus::NEW, $closed );
	}

	/**
	 * Test: getAllowedTransitions definiert gültige Übergänge
	 */
	public function test_get_allowed_transitions_structure(): void {
		$transitions = ApplicationStatus::getAllowedTransitions();

		// Alle Status sollten Schlüssel sein.
		$this->assertArrayHasKey( ApplicationStatus::NEW, $transitions );
		$this->assertArrayHasKey( ApplicationStatus::HIRED, $transitions );

		// New kann zu Screening wechseln.
		$this->assertContains( ApplicationStatus::SCREENING, $transitions[ ApplicationStatus::NEW ] );

		// Hired hat keine weiteren Übergänge.
		$this->assertEmpty( $transitions[ ApplicationStatus::HIRED ] );
	}

	/**
	 * Test: isTransitionAllowed prüft Übergänge korrekt für Admins
	 */
	public function test_is_transition_allowed_for_admin(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		// Admins können alle Übergänge.
		$this->assertTrue( ApplicationStatus::isTransitionAllowed( 'new', 'hired' ) );
		$this->assertTrue( ApplicationStatus::isTransitionAllowed( 'rejected', 'new' ) );
	}

	/**
	 * Test: isTransitionAllowed prüft Übergänge für normale Benutzer
	 */
	public function test_is_transition_allowed_for_regular_user(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		// Erlaubte Übergänge.
		$this->assertTrue( ApplicationStatus::isTransitionAllowed( 'new', 'screening' ) );
		$this->assertTrue( ApplicationStatus::isTransitionAllowed( 'screening', 'interview' ) );

		// Nicht erlaubte Übergänge.
		$this->assertFalse( ApplicationStatus::isTransitionAllowed( 'new', 'hired' ) );
		$this->assertFalse( ApplicationStatus::isTransitionAllowed( 'hired', 'new' ) );
	}
}
