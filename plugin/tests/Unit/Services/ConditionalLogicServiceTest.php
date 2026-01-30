<?php
/**
 * ConditionalLogicService Unit Tests
 *
 * @package RecruitingPlaybook\Tests\Unit\Services
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use RecruitingPlaybook\Services\ConditionalLogicService;
use RecruitingPlaybook\Models\FieldDefinition;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Tests für ConditionalLogicService
 */
class ConditionalLogicServiceTest extends TestCase {

	private ConditionalLogicService $service;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$this->service = new ConditionalLogicService();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_returns_all_operators(): void {
		$operators = $this->service->getOperators();

		$this->assertArrayHasKey( 'equals', $operators );
		$this->assertArrayHasKey( 'not_equals', $operators );
		$this->assertArrayHasKey( 'contains', $operators );
		$this->assertArrayHasKey( 'not_empty', $operators );
		$this->assertArrayHasKey( 'empty', $operators );
		$this->assertArrayHasKey( 'greater_than', $operators );
		$this->assertArrayHasKey( 'less_than', $operators );
	}

	/**
	 * @test
	 */
	public function it_filters_operators_by_field_type(): void {
		$number_operators = $this->service->getOperators( 'number' );
		$text_operators   = $this->service->getOperators( 'text' );

		$this->assertArrayHasKey( 'greater_than', $number_operators );
		$this->assertArrayHasKey( 'less_than', $number_operators );
		$this->assertArrayNotHasKey( 'greater_than', $text_operators );
	}

	/**
	 * @test
	 */
	public function it_returns_true_for_empty_condition(): void {
		$result = $this->service->evaluate( [], [ 'field' => 'value' ] );

		$this->assertTrue( $result );
	}

	/**
	 * @test
	 */
	public function it_evaluates_equals_operator(): void {
		$condition = [
			'field'    => 'category',
			'operator' => 'equals',
			'value'    => 'tech',
		];

		$this->assertTrue( $this->service->evaluate( $condition, [ 'category' => 'tech' ] ) );
		$this->assertFalse( $this->service->evaluate( $condition, [ 'category' => 'other' ] ) );
	}

	/**
	 * @test
	 */
	public function it_evaluates_not_equals_operator(): void {
		$condition = [
			'field'    => 'status',
			'operator' => 'not_equals',
			'value'    => 'closed',
		];

		$this->assertTrue( $this->service->evaluate( $condition, [ 'status' => 'open' ] ) );
		$this->assertFalse( $this->service->evaluate( $condition, [ 'status' => 'closed' ] ) );
	}

	/**
	 * @test
	 */
	public function it_evaluates_contains_operator(): void {
		$condition = [
			'field'    => 'description',
			'operator' => 'contains',
			'value'    => 'PHP',
		];

		$this->assertTrue( $this->service->evaluate( $condition, [ 'description' => 'We need PHP developers' ] ) );
		$this->assertFalse( $this->service->evaluate( $condition, [ 'description' => 'We need Java developers' ] ) );
	}

	/**
	 * @test
	 */
	public function it_evaluates_not_empty_operator(): void {
		$condition = [
			'field'    => 'name',
			'operator' => 'not_empty',
		];

		$this->assertTrue( $this->service->evaluate( $condition, [ 'name' => 'John' ] ) );
		$this->assertFalse( $this->service->evaluate( $condition, [ 'name' => '' ] ) );
		$this->assertFalse( $this->service->evaluate( $condition, [ 'name' => null ] ) );
	}

	/**
	 * @test
	 */
	public function it_evaluates_empty_operator(): void {
		$condition = [
			'field'    => 'optional',
			'operator' => 'empty',
		];

		$this->assertTrue( $this->service->evaluate( $condition, [ 'optional' => '' ] ) );
		$this->assertTrue( $this->service->evaluate( $condition, [ 'optional' => null ] ) );
		$this->assertFalse( $this->service->evaluate( $condition, [ 'optional' => 'value' ] ) );
	}

	/**
	 * @test
	 */
	public function it_evaluates_checked_operator(): void {
		$condition = [
			'field'    => 'agree',
			'operator' => 'checked',
		];

		$this->assertTrue( $this->service->evaluate( $condition, [ 'agree' => true ] ) );
		$this->assertTrue( $this->service->evaluate( $condition, [ 'agree' => '1' ] ) );
		$this->assertFalse( $this->service->evaluate( $condition, [ 'agree' => false ] ) );
		$this->assertFalse( $this->service->evaluate( $condition, [ 'agree' => '0' ] ) );
	}

	/**
	 * @test
	 */
	public function it_evaluates_greater_than_operator(): void {
		$condition = [
			'field'    => 'age',
			'operator' => 'greater_than',
			'value'    => '18',
		];

		$this->assertTrue( $this->service->evaluate( $condition, [ 'age' => 25 ] ) );
		$this->assertTrue( $this->service->evaluate( $condition, [ 'age' => '21' ] ) );
		$this->assertFalse( $this->service->evaluate( $condition, [ 'age' => 18 ] ) );
		$this->assertFalse( $this->service->evaluate( $condition, [ 'age' => 16 ] ) );
	}

	/**
	 * @test
	 */
	public function it_evaluates_less_than_operator(): void {
		$condition = [
			'field'    => 'price',
			'operator' => 'less_than',
			'value'    => '100',
		];

		$this->assertTrue( $this->service->evaluate( $condition, [ 'price' => 50 ] ) );
		$this->assertFalse( $this->service->evaluate( $condition, [ 'price' => 100 ] ) );
		$this->assertFalse( $this->service->evaluate( $condition, [ 'price' => 150 ] ) );
	}

	/**
	 * @test
	 */
	public function it_evaluates_in_operator(): void {
		$condition = [
			'field'    => 'country',
			'operator' => 'in',
			'value'    => 'DE, AT, CH',
		];

		$this->assertTrue( $this->service->evaluate( $condition, [ 'country' => 'DE' ] ) );
		$this->assertTrue( $this->service->evaluate( $condition, [ 'country' => 'AT' ] ) );
		$this->assertFalse( $this->service->evaluate( $condition, [ 'country' => 'FR' ] ) );
	}

	/**
	 * @test
	 */
	public function it_evaluates_starts_with_operator(): void {
		$condition = [
			'field'    => 'code',
			'operator' => 'starts_with',
			'value'    => 'PRO-',
		];

		$this->assertTrue( $this->service->evaluate( $condition, [ 'code' => 'PRO-123' ] ) );
		$this->assertFalse( $this->service->evaluate( $condition, [ 'code' => 'DEV-456' ] ) );
	}

	/**
	 * @test
	 */
	public function it_evaluates_multiple_conditions_with_and(): void {
		$conditions = [
			[ 'field' => 'type', 'operator' => 'equals', 'value' => 'premium' ],
			[ 'field' => 'active', 'operator' => 'checked' ],
		];

		$form_data_both = [ 'type' => 'premium', 'active' => true ];
		$form_data_one  = [ 'type' => 'premium', 'active' => false ];

		$this->assertTrue( $this->service->evaluateMultiple( $conditions, 'and', $form_data_both ) );
		$this->assertFalse( $this->service->evaluateMultiple( $conditions, 'and', $form_data_one ) );
	}

	/**
	 * @test
	 */
	public function it_evaluates_multiple_conditions_with_or(): void {
		$conditions = [
			[ 'field' => 'role', 'operator' => 'equals', 'value' => 'admin' ],
			[ 'field' => 'role', 'operator' => 'equals', 'value' => 'editor' ],
		];

		$form_data_admin  = [ 'role' => 'admin' ];
		$form_data_editor = [ 'role' => 'editor' ];
		$form_data_user   = [ 'role' => 'user' ];

		$this->assertTrue( $this->service->evaluateMultiple( $conditions, 'or', $form_data_admin ) );
		$this->assertTrue( $this->service->evaluateMultiple( $conditions, 'or', $form_data_editor ) );
		$this->assertFalse( $this->service->evaluateMultiple( $conditions, 'or', $form_data_user ) );
	}

	/**
	 * @test
	 */
	public function it_generates_alpine_expression_for_equals(): void {
		$condition = [
			'field'    => 'type',
			'operator' => 'equals',
			'value'    => 'other',
		];

		$expression = $this->service->toAlpineExpression( $condition );

		$this->assertStringContainsString( 'formData.type', $expression );
		$this->assertStringContainsString( "=== 'other'", $expression );
	}

	/**
	 * @test
	 */
	public function it_generates_alpine_expression_for_not_empty(): void {
		$condition = [
			'field'    => 'name',
			'operator' => 'not_empty',
		];

		$expression = $this->service->toAlpineExpression( $condition );

		$this->assertStringContainsString( 'formData.name', $expression );
		$this->assertStringContainsString( '!!', $expression );
	}

	/**
	 * @test
	 */
	public function it_generates_alpine_expression_for_greater_than(): void {
		$condition = [
			'field'    => 'age',
			'operator' => 'greater_than',
			'value'    => '18',
		];

		$expression = $this->service->toAlpineExpression( $condition );

		$this->assertStringContainsString( 'parseFloat', $expression );
		$this->assertStringContainsString( '> 18', $expression );
	}

	/**
	 * @test
	 */
	public function it_returns_true_expression_for_empty_condition(): void {
		$expression = $this->service->toAlpineExpression( [] );

		$this->assertEquals( 'true', $expression );
	}

	/**
	 * @test
	 */
	public function it_validates_conditional_config(): void {
		$valid_fields = [ 'name', 'email', 'type' ];

		// Gültige Konfiguration.
		$valid = [
			'field'    => 'type',
			'operator' => 'equals',
			'value'    => 'other',
		];
		$this->assertTrue( $this->service->validateConditional( $valid, $valid_fields ) );

		// Leere Konfiguration ist gültig.
		$this->assertTrue( $this->service->validateConditional( [], $valid_fields ) );
	}

	/**
	 * @test
	 */
	public function it_rejects_invalid_field_reference(): void {
		$valid_fields = [ 'name', 'email' ];

		$invalid = [
			'field'    => 'nonexistent',
			'operator' => 'equals',
			'value'    => 'test',
		];

		$result = $this->service->validateConditional( $invalid, $valid_fields );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_rejects_invalid_operator(): void {
		$valid_fields = [ 'name' ];

		$invalid = [
			'field'    => 'name',
			'operator' => 'invalid_operator',
			'value'    => 'test',
		];

		$result = $this->service->validateConditional( $invalid, $valid_fields );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	/**
	 * @test
	 */
	public function it_builds_dependency_graph(): void {
		$fields = [
			$this->createFieldDefinition( 'type', [] ),
			$this->createFieldDefinition( 'other_field', [ 'field' => 'type', 'operator' => 'equals', 'value' => 'other' ] ),
			$this->createFieldDefinition( 'details', [ 'field' => 'type', 'operator' => 'equals', 'value' => 'special' ] ),
		];

		$graph = $this->service->buildDependencyGraph( $fields );

		$this->assertArrayHasKey( 'type', $graph );
		$this->assertContains( 'other_field', $graph['type'] );
		$this->assertContains( 'details', $graph['type'] );
	}

	/**
	 * @test
	 */
	public function it_detects_no_circular_dependency(): void {
		$fields = [
			$this->createFieldDefinition( 'type', [] ),
			$this->createFieldDefinition( 'other', [ 'field' => 'type', 'operator' => 'equals', 'value' => 'x' ] ),
		];

		$this->assertFalse( $this->service->hasCircularDependency( $fields ) );
	}

	/**
	 * @test
	 */
	public function it_filters_visible_fields(): void {
		$fields = [
			$this->createFieldDefinition( 'type', [] ),
			$this->createFieldDefinition( 'visible', [ 'field' => 'type', 'operator' => 'equals', 'value' => 'show' ] ),
			$this->createFieldDefinition( 'hidden', [ 'field' => 'type', 'operator' => 'equals', 'value' => 'other' ] ),
		];

		$form_data = [ 'type' => 'show' ];

		$visible = $this->service->filterVisibleFields( $fields, $form_data );

		$this->assertCount( 2, $visible );

		$keys = array_map( fn( $f ) => $f->getFieldKey(), $visible );
		$this->assertContains( 'type', $keys );
		$this->assertContains( 'visible', $keys );
		$this->assertNotContains( 'hidden', $keys );
	}

	/**
	 * Hilfsmethode zum Erstellen einer FieldDefinition
	 *
	 * @param string $key         Feldschlüssel.
	 * @param array  $conditional Conditional-Konfiguration.
	 * @return FieldDefinition
	 */
	private function createFieldDefinition( string $key, array $conditional ): FieldDefinition {
		$data = [
			'id'          => rand( 1, 1000 ),
			'field_key'   => $key,
			'type'        => 'text',
			'label'       => ucfirst( $key ),
			'is_required' => false,
			'conditional' => ! empty( $conditional ) ? wp_json_encode( $conditional ) : null,
		];

		return FieldDefinition::hydrate( $data );
	}
}
