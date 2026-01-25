<?php
/**
 * EmailTemplateService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\EmailTemplateService;
use RecruitingPlaybook\Services\PlaceholderService;
use RecruitingPlaybook\Repositories\EmailTemplateRepository;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests für den EmailTemplateService
 */
class EmailTemplateServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var EmailTemplateService
	 */
	private EmailTemplateService $service;

	/**
	 * Mock Repository
	 *
	 * @var EmailTemplateRepository|Mockery\MockInterface
	 */
	private $repository;

	/**
	 * Mock PlaceholderService
	 *
	 * @var PlaceholderService|Mockery\MockInterface
	 */
	private $placeholderService;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->repository = Mockery::mock( EmailTemplateRepository::class );
		$this->placeholderService = Mockery::mock( PlaceholderService::class );

		$this->service = new EmailTemplateService( $this->repository, $this->placeholderService );

		// Standard WordPress-Funktionen mocken.
		Functions\when( 'get_option' )->alias( function( $option, $default = false ) {
			if ( 'rp_settings' === $option ) {
				return [ 'company_name' => 'Test GmbH' ];
			}
			return $default;
		} );
		Functions\when( 'get_bloginfo' )->justReturn( 'Test Blog' );
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		// is_wp_error is already defined in stubs - don't mock it.
		Functions\when( 'wp_strip_all_tags' )->alias( function( $string ) {
			return strip_tags( $string );
		} );
	}

	/**
	 * Test: Template erstellen
	 */
	public function test_create_template(): void {
		$data = [
			'name'      => 'Test Template',
			'slug'      => 'test-template',
			'subject'   => 'Betreff: {stelle}',
			'body_html' => '<p>Hallo {vorname},</p>',
			'category'  => 'application',
		];

		$expected_template = array_merge( $data, [
			'id'        => 1,
			'variables' => [ 'stelle', 'vorname' ],
		] );

		$this->placeholderService
			->shouldReceive( 'findPlaceholders' )
			->with( 'Betreff: {stelle}' )
			->andReturn( [ 'stelle' ] );

		$this->placeholderService
			->shouldReceive( 'findPlaceholders' )
			->with( '<p>Hallo {vorname},</p>' )
			->andReturn( [ 'vorname' ] );

		$this->repository
			->shouldReceive( 'slugExists' )
			->andReturn( false );

		$this->repository
			->shouldReceive( 'create' )
			->once()
			->andReturn( 1 );

		$this->repository
			->shouldReceive( 'find' )
			->with( 1 )
			->andReturn( $expected_template );

		$result = $this->service->create( $data );

		$this->assertIsArray( $result );
		$this->assertEquals( 1, $result['id'] );
		$this->assertEquals( 'Test Template', $result['name'] );
	}

	/**
	 * Test: Template erstellen ohne Name schlägt fehl
	 */
	public function test_create_template_without_name_fails(): void {
		$data = [
			'subject'   => 'Betreff',
			'body_html' => 'Inhalt',
		];

		$result = $this->service->create( $data );

		$this->assertFalse( $result );
	}

	/**
	 * Test: Template erstellen ohne Subject schlägt fehl
	 */
	public function test_create_template_without_subject_fails(): void {
		$data = [
			'name'      => 'Test',
			'body_html' => 'Inhalt',
		];

		$result = $this->service->create( $data );

		$this->assertFalse( $result );
	}

	/**
	 * Test: Template aktualisieren
	 */
	public function test_update_template(): void {
		$existing_template = [
			'id'        => 1,
			'name'      => 'Original',
			'slug'      => 'original',
			'subject'   => 'Alter Betreff',
			'body_html' => 'Alter Inhalt',
			'is_system' => false,
			'category'  => 'custom',
		];

		$updated_template = array_merge( $existing_template, [
			'subject' => 'Neuer Betreff: {stelle}',
		] );

		$this->repository
			->shouldReceive( 'find' )
			->with( 1 )
			->andReturn( $existing_template, $updated_template );

		$this->repository
			->shouldReceive( 'slugExists' )
			->andReturn( false );

		$this->placeholderService
			->shouldReceive( 'findPlaceholders' )
			->andReturn( [ 'stelle' ] );

		$this->repository
			->shouldReceive( 'update' )
			->once()
			->with( 1, Mockery::any() )
			->andReturn( true );

		$result = $this->service->update( 1, [ 'subject' => 'Neuer Betreff: {stelle}' ] );

		$this->assertIsArray( $result );
		$this->assertEquals( 'Neuer Betreff: {stelle}', $result['subject'] );
	}

	/**
	 * Test: System-Template kann nur eingeschränkt bearbeitet werden
	 */
	public function test_system_template_limited_update(): void {
		$system_template = [
			'id'        => 1,
			'name'      => 'Eingangsbestätigung',
			'slug'      => 'application-confirmation',
			'subject'   => 'Betreff',
			'body_html' => 'Inhalt',
			'is_system' => true,
			'category'  => 'application',
		];

		$this->repository
			->shouldReceive( 'find' )
			->with( 1 )
			->andReturn( $system_template, $system_template );

		$this->repository
			->shouldReceive( 'slugExists' )
			->andReturn( false );

		$this->placeholderService
			->shouldReceive( 'findPlaceholders' )
			->andReturn( [] );

		// Update sollte nur bestimmte Felder erlauben (nicht name, slug, category).
		$this->repository
			->shouldReceive( 'update' )
			->once()
			->with( 1, Mockery::on( function( $data ) {
				// Name sollte NICHT im Update sein.
				return ! isset( $data['name'] ) && ! isset( $data['slug'] );
			} ) )
			->andReturn( true );

		$result = $this->service->update( 1, [
			'name'      => 'Neuer Name', // Sollte ignoriert werden.
			'slug'      => 'neuer-slug', // Sollte ignoriert werden.
			'subject'   => 'Neuer Betreff', // Erlaubt.
			'body_html' => 'Neuer Inhalt', // Erlaubt.
		] );

		$this->assertIsArray( $result );
	}

	/**
	 * Test: Template löschen
	 */
	public function test_delete_template(): void {
		$template = [
			'id'        => 1,
			'name'      => 'Test',
			'is_system' => false,
		];

		$this->repository
			->shouldReceive( 'find' )
			->with( 1 )
			->andReturn( $template );

		$this->repository
			->shouldReceive( 'softDelete' )
			->with( 1 )
			->andReturn( true );

		$result = $this->service->delete( 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test: System-Template kann nicht gelöscht werden
	 */
	public function test_system_template_cannot_be_deleted(): void {
		$system_template = [
			'id'        => 1,
			'name'      => 'Eingangsbestätigung',
			'is_system' => true,
		];

		$this->repository
			->shouldReceive( 'find' )
			->with( 1 )
			->andReturn( $system_template );

		$result = $this->service->delete( 1 );

		$this->assertFalse( $result );
	}

	/**
	 * Test: Template duplizieren
	 */
	public function test_duplicate_template(): void {
		$duplicated = [
			'id'        => 2,
			'name'      => 'Test (Kopie)',
			'slug'      => 'test-kopie',
			'is_system' => false,
		];

		$this->repository
			->shouldReceive( 'duplicate' )
			->with( 1, '' )
			->andReturn( 2 );

		$this->repository
			->shouldReceive( 'find' )
			->with( 2 )
			->andReturn( $duplicated );

		$result = $this->service->duplicate( 1 );

		$this->assertIsArray( $result );
		$this->assertEquals( 2, $result['id'] );
		$this->assertStringContainsString( 'Kopie', $result['name'] );
	}

	/**
	 * Test: Template per Slug finden
	 */
	public function test_find_by_slug(): void {
		$template = [
			'id'   => 1,
			'slug' => 'application-confirmation',
		];

		$this->repository
			->shouldReceive( 'findBySlug' )
			->with( 'application-confirmation' )
			->andReturn( $template );

		$result = $this->service->findBySlug( 'application-confirmation' );

		$this->assertIsArray( $result );
		$this->assertEquals( 'application-confirmation', $result['slug'] );
	}

	/**
	 * Test: Standard-Template für Kategorie laden
	 */
	public function test_get_default_for_category(): void {
		$default_template = [
			'id'         => 1,
			'name'       => 'Standard Eingangsbestätigung',
			'category'   => 'application',
			'is_default' => true,
		];

		$this->repository
			->shouldReceive( 'findDefault' )
			->with( 'application' )
			->andReturn( $default_template );

		$result = $this->service->getDefault( 'application' );

		$this->assertIsArray( $result );
		$this->assertEquals( 'application', $result['category'] );
	}

	/**
	 * Test: Template rendern mit Kontext
	 */
	public function test_render_template(): void {
		$template = [
			'id'        => 1,
			'subject'   => 'Bewerbung: {stelle}',
			'body_html' => '<p>Hallo {vorname},</p>',
			'body_text' => '',
		];

		$this->repository
			->shouldReceive( 'find' )
			->with( 1 )
			->andReturn( $template );

		$this->placeholderService
			->shouldReceive( 'replace' )
			->with( 'Bewerbung: {stelle}', Mockery::any() )
			->andReturn( 'Bewerbung: PHP Developer' );

		$this->placeholderService
			->shouldReceive( 'replace' )
			->with( '<p>Hallo {vorname},</p>', Mockery::any() )
			->andReturn( '<p>Hallo Max,</p>' );

		$context = [
			'candidate' => [ 'first_name' => 'Max' ],
			'job'       => [ 'title' => 'PHP Developer' ],
		];

		$result = $this->service->render( 1, $context );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'subject', $result );
		$this->assertArrayHasKey( 'body_html', $result );
		$this->assertArrayHasKey( 'body_text', $result );
		$this->assertStringContainsString( 'PHP Developer', $result['subject'] );
	}

	/**
	 * Test: Template-Vorschau generieren
	 */
	public function test_preview_template(): void {
		$template = [
			'id'        => 1,
			'subject'   => 'Bewerbung: {stelle}',
			'body_html' => '<p>Hallo {vorname},</p>',
		];

		$this->repository
			->shouldReceive( 'find' )
			->with( 1 )
			->andReturn( $template );

		$this->placeholderService
			->shouldReceive( 'renderPreview' )
			->with( 'Bewerbung: {stelle}' )
			->andReturn( 'Bewerbung: Senior PHP Developer' );

		$this->placeholderService
			->shouldReceive( 'renderPreview' )
			->with( '<p>Hallo {vorname},</p>' )
			->andReturn( '<p>Hallo Max,</p>' );

		$result = $this->service->preview( 1 );

		$this->assertIsArray( $result );
		$this->assertStringContainsString( 'Senior PHP Developer', $result['subject'] );
		$this->assertStringContainsString( 'Max', $result['body_html'] );
	}

	/**
	 * Test: Verfügbare Kategorien abrufen
	 */
	public function test_get_categories(): void {
		$categories = $this->service->getCategories();

		$this->assertIsArray( $categories );
		$this->assertArrayHasKey( 'application', $categories );
		$this->assertArrayHasKey( 'interview', $categories );
		$this->assertArrayHasKey( 'offer', $categories );
		$this->assertArrayHasKey( 'custom', $categories );
	}

	/**
	 * Test: Nicht existierendes Template gibt null zurück
	 */
	public function test_find_non_existent_returns_null(): void {
		$this->repository
			->shouldReceive( 'find' )
			->with( 999 )
			->andReturn( null );

		$result = $this->service->find( 999 );

		$this->assertNull( $result );
	}

	/**
	 * Test: Template-Liste abrufen
	 */
	public function test_get_list(): void {
		$templates = [
			[ 'id' => 1, 'name' => 'Template 1' ],
			[ 'id' => 2, 'name' => 'Template 2' ],
		];

		$this->repository
			->shouldReceive( 'getList' )
			->with( [] )
			->andReturn( $templates );

		$result = $this->service->getList();

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
	}
}
