<?php
/**
 * FormRenderService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Services;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\FormRenderService;
use RecruitingPlaybook\Services\FormConfigService;
use RecruitingPlaybook\Repositories\FieldDefinitionRepository;
use RecruitingPlaybook\Models\FieldDefinition;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests für den FormRenderService
 */
class FormRenderServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var FormRenderService
	 */
	private FormRenderService $service;

	/**
	 * Mock Config Service
	 *
	 * @var FormConfigService|Mockery\MockInterface
	 */
	private $config_service;

	/**
	 * Mock Field Repository
	 *
	 * @var FieldDefinitionRepository|Mockery\MockInterface
	 */
	private $field_repository;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->config_service   = Mockery::mock( FormConfigService::class );
		$this->field_repository = Mockery::mock( FieldDefinitionRepository::class );
		$this->service          = new FormRenderService(
			$this->config_service,
			$this->field_repository
		);

		// WordPress-Funktionen mocken.
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'esc_js' )->returnArg( 1 );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'wp_kses_post' )->returnArg( 1 );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_attr__' )->returnArg( 1 );
		Functions\when( 'get_privacy_policy_url' )->justReturn( 'https://example.com/privacy' );

		// Konstanten definieren.
		if ( ! defined( 'RP_PLUGIN_DIR' ) ) {
			define( 'RP_PLUGIN_DIR', dirname( __DIR__, 3 ) . '/' );
		}
	}

	/**
	 * Teardown nach jedem Test
	 */
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Helper: Mock-Konfiguration mit System-Feldern erstellen
	 */
	private function createMockConfigWithSystemFields(): array {
		return [
			'version'  => 2,
			'settings' => [],
			'steps'    => [
				[
					'id'            => 'step_personal',
					'title'         => 'Persönliche Daten',
					'position'      => 1,
					'deletable'     => false,
					'is_finale'     => false,
					'fields'        => [
						[
							'field_key'   => 'first_name',
							'is_visible'  => true,
							'is_required' => true,
						],
						[
							'field_key'   => 'email',
							'is_visible'  => true,
							'is_required' => true,
						],
					],
					'system_fields' => [],
				],
				[
					'id'            => 'step_documents',
					'title'         => 'Dokumente',
					'position'      => 2,
					'deletable'     => true,
					'is_finale'     => false,
					'fields'        => [],
					'system_fields' => [
						[
							'field_key' => 'file_upload',
							'type'      => 'file_upload',
							'settings'  => [
								'label'         => 'Dokumente hochladen',
								'allowed_types' => [ 'pdf', 'doc', 'docx' ],
								'max_file_size' => 10,
								'max_files'     => 5,
								'help_text'     => 'Bitte laden Sie Ihren Lebenslauf hoch.',
							],
						],
					],
				],
				[
					'id'            => 'step_finale',
					'title'         => 'Abschluss',
					'position'      => 3,
					'deletable'     => false,
					'is_finale'     => true,
					'fields'        => [],
					'system_fields' => [
						[
							'field_key' => 'summary',
							'type'      => 'summary',
							'settings'  => [
								'label'             => 'Zusammenfassung',
								'show_header'       => true,
								'show_step_titles'  => true,
								'show_edit_buttons' => true,
							],
						],
						[
							'field_key'    => 'privacy_consent',
							'type'         => 'privacy_consent',
							'is_removable' => false,
							'settings'     => [
								'consent_text'      => 'Ich habe die {privacy_link} gelesen.',
								'privacy_link_text' => 'Datenschutzerklärung',
								'privacy_url'       => 'https://example.com/datenschutz',
								'error_message'     => 'Bitte stimmen Sie zu.',
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Helper: Mock-Feld-Definitionen erstellen
	 */
	private function createMockFieldDefinitions(): array {
		$fields = [];

		$firstName = Mockery::mock( FieldDefinition::class );
		$firstName->shouldReceive( 'getFieldKey' )->andReturn( 'first_name' );
		$firstName->shouldReceive( 'getFieldType' )->andReturn( 'text' );
		$firstName->shouldReceive( 'getLabel' )->andReturn( 'Vorname' );
		$firstName->shouldReceive( 'getPlaceholder' )->andReturn( '' );
		$firstName->shouldReceive( 'getDescription' )->andReturn( '' );
		$firstName->shouldReceive( 'isRequired' )->andReturn( true );
		$firstName->shouldReceive( 'isSystem' )->andReturn( true );
		$firstName->shouldReceive( 'getSettings' )->andReturn( [] );
		$firstName->shouldReceive( 'getValidation' )->andReturn( [] );
		$firstName->shouldReceive( 'getOptions' )->andReturn( [] );
		$fields[] = $firstName;

		$email = Mockery::mock( FieldDefinition::class );
		$email->shouldReceive( 'getFieldKey' )->andReturn( 'email' );
		$email->shouldReceive( 'getFieldType' )->andReturn( 'email' );
		$email->shouldReceive( 'getLabel' )->andReturn( 'E-Mail' );
		$email->shouldReceive( 'getPlaceholder' )->andReturn( '' );
		$email->shouldReceive( 'getDescription' )->andReturn( '' );
		$email->shouldReceive( 'isRequired' )->andReturn( true );
		$email->shouldReceive( 'isSystem' )->andReturn( true );
		$email->shouldReceive( 'getSettings' )->andReturn( [] );
		$email->shouldReceive( 'getValidation' )->andReturn( [] );
		$email->shouldReceive( 'getOptions' )->andReturn( [] );
		$fields[] = $email;

		return $fields;
	}

	/**
	 * Test: render() gibt Fallback aus wenn keine Config vorhanden
	 */
	public function test_render_shows_fallback_when_no_config(): void {
		$this->config_service->shouldReceive( 'getPublished' )
			->once()
			->andReturn( [] );

		$html = $this->service->render( 123, false );

		$this->assertStringContainsString( 'rp-form-error', $html );
	}

	/**
	 * Test: render() rendert Steps korrekt
	 */
	public function test_render_generates_steps_correctly(): void {
		$config = $this->createMockConfigWithSystemFields();
		$fields = $this->createMockFieldDefinitions();

		$this->config_service->shouldReceive( 'getPublished' )
			->once()
			->andReturn( $config );

		$this->field_repository->shouldReceive( 'findAll' )
			->once()
			->andReturn( $fields );

		$html = $this->service->render( 123, false );

		// Prüfe dass Steps vorhanden sind.
		$this->assertStringContainsString( 'x-show="step === 1"', $html );
		$this->assertStringContainsString( 'x-show="step === 2"', $html );
		$this->assertStringContainsString( 'x-show="step === 3"', $html );

		// Prüfe Step-Titel.
		$this->assertStringContainsString( 'Persönliche Daten', $html );
		$this->assertStringContainsString( 'Dokumente', $html );
		$this->assertStringContainsString( 'Abschluss', $html );
	}

	/**
	 * Test: render() rendert File-Upload System-Feld
	 */
	public function test_render_includes_file_upload_system_field(): void {
		$config = $this->createMockConfigWithSystemFields();
		$fields = $this->createMockFieldDefinitions();

		$this->config_service->shouldReceive( 'getPublished' )
			->once()
			->andReturn( $config );

		$this->field_repository->shouldReceive( 'findAll' )
			->once()
			->andReturn( $fields );

		$html = $this->service->render( 123, false );

		// Prüfe File-Upload Komponente.
		$this->assertStringContainsString( 'rp-system-field--file-upload', $html );
		$this->assertStringContainsString( 'Dokumente hochladen', $html );
		$this->assertStringContainsString( 'maxFiles: 5', $html );
		$this->assertStringContainsString( 'Bitte laden Sie Ihren Lebenslauf hoch.', $html );
	}

	/**
	 * Test: render() rendert Privacy Consent System-Feld
	 */
	public function test_render_includes_privacy_consent_system_field(): void {
		$config = $this->createMockConfigWithSystemFields();
		$fields = $this->createMockFieldDefinitions();

		$this->config_service->shouldReceive( 'getPublished' )
			->once()
			->andReturn( $config );

		$this->field_repository->shouldReceive( 'findAll' )
			->once()
			->andReturn( $fields );

		$html = $this->service->render( 123, false );

		// Prüfe Privacy Consent Komponente.
		$this->assertStringContainsString( 'rp-system-field--privacy-consent', $html );
		$this->assertStringContainsString( 'x-model="formData.privacy_consent"', $html );
		$this->assertStringContainsString( 'Datenschutzerklärung', $html );
		$this->assertStringContainsString( 'https://example.com/datenschutz', $html );
	}

	/**
	 * Test: render() rendert Summary System-Feld
	 */
	public function test_render_includes_summary_system_field(): void {
		$config = $this->createMockConfigWithSystemFields();
		$fields = $this->createMockFieldDefinitions();

		$this->config_service->shouldReceive( 'getPublished' )
			->once()
			->andReturn( $config );

		$this->field_repository->shouldReceive( 'findAll' )
			->once()
			->andReturn( $fields );

		$html = $this->service->render( 123, false );

		// Prüfe Summary Komponente.
		$this->assertStringContainsString( 'rp-summary', $html );
		$this->assertStringContainsString( 'Zusammenfassung', $html );
	}

	/**
	 * Test: File-Upload validiert Dateitypen
	 */
	public function test_file_upload_includes_type_validation(): void {
		$config = $this->createMockConfigWithSystemFields();
		$fields = $this->createMockFieldDefinitions();

		$this->config_service->shouldReceive( 'getPublished' )
			->once()
			->andReturn( $config );

		$this->field_repository->shouldReceive( 'findAll' )
			->once()
			->andReturn( $fields );

		$html = $this->service->render( 123, false );

		// Prüfe Dateityp-Validierung.
		$this->assertStringContainsString( 'allowedTypes:', $html );
		$this->assertStringContainsString( 'isValidType(file)', $html );
		$this->assertStringContainsString( 'pdf', $html );
	}

	/**
	 * Test: getCustomFieldsConfig() enthält System-Feld-Daten
	 */
	public function test_getCustomFieldsConfig_includes_system_fields(): void {
		$config = $this->createMockConfigWithSystemFields();
		$fields = $this->createMockFieldDefinitions();

		$this->config_service->shouldReceive( 'getPublished' )
			->once()
			->andReturn( $config );

		$this->field_repository->shouldReceive( 'findAll' )
			->once()
			->andReturn( $fields );

		$result = $this->service->getCustomFieldsConfig( 123 );

		$this->assertArrayHasKey( 'fields', $result );
		$this->assertArrayHasKey( 'steps', $result );
		$this->assertArrayHasKey( 'initialData', $result );

		// Prüfe dass job_id in initialData ist.
		$this->assertEquals( 123, $result['initialData']['job_id'] );
	}

	/**
	 * Test: Config mit Wrapper enthält Alpine.js Daten
	 */
	public function test_render_with_wrapper_includes_alpine_config(): void {
		$config = $this->createMockConfigWithSystemFields();
		$fields = $this->createMockFieldDefinitions();

		$this->config_service->shouldReceive( 'getPublished' )
			->once()
			->andReturn( $config );

		$this->field_repository->shouldReceive( 'findAll' )
			->once()
			->andReturn( $fields );

		$html = $this->service->render( 123, true );

		// Prüfe Alpine.js Config.
		$this->assertStringContainsString( 'window.rpFormConfig', $html );
		$this->assertStringContainsString( 'x-data="applicationForm"', $html );

		// Prüfe dass files-Objekt vorhanden ist (wegen file_upload).
		$this->assertStringContainsString( '"files"', $html );
		$this->assertStringContainsString( '"documents"', $html );

		// Prüfe dass privacy_consent Validierung vorhanden ist.
		$this->assertStringContainsString( '"privacy_consent"', $html );
	}

	/**
	 * Test: Privacy Consent URL Fallback auf WP Datenschutz-Seite
	 */
	public function test_privacy_consent_uses_wp_privacy_url_as_fallback(): void {
		$config = $this->createMockConfigWithSystemFields();
		// Entferne die custom privacy_url.
		$config['steps'][2]['system_fields'][1]['settings']['privacy_url'] = '';
		$fields = $this->createMockFieldDefinitions();

		$this->config_service->shouldReceive( 'getPublished' )
			->once()
			->andReturn( $config );

		$this->field_repository->shouldReceive( 'findAll' )
			->once()
			->andReturn( $fields );

		$html = $this->service->render( 123, false );

		// Prüfe dass WP Privacy URL verwendet wird.
		$this->assertStringContainsString( 'https://example.com/privacy', $html );
	}
}
