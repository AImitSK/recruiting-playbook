<?php
/**
 * FieldValue Model
 *
 * Repräsentiert einen gespeicherten Feldwert in einer Bewerbung.
 *
 * @package RecruitingPlaybook\Models
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Models;

defined( 'ABSPATH' ) || exit;

/**
 * FieldValue Model
 */
class FieldValue {

	/**
	 * Feld-Schlüssel
	 *
	 * @var string
	 */
	private string $field_key = '';

	/**
	 * Feldwert
	 *
	 * @var mixed
	 */
	private $value;

	/**
	 * Feldtyp (für Formatierung)
	 *
	 * @var string
	 */
	private string $field_type = 'text';

	/**
	 * Feldlabel (für Anzeige)
	 *
	 * @var string
	 */
	private string $label = '';

	/**
	 * Constructor
	 *
	 * @param string $field_key  Feld-Schlüssel.
	 * @param mixed  $value      Feldwert.
	 * @param string $field_type Feldtyp.
	 * @param string $label      Label.
	 */
	public function __construct( string $field_key = '', $value = null, string $field_type = 'text', string $label = '' ) {
		$this->field_key  = $field_key;
		$this->value      = $value;
		$this->field_type = $field_type;
		$this->label      = $label;
	}

	// -------------------------------------------------------------------------
	// Getters
	// -------------------------------------------------------------------------

	/**
	 * Get Field Key
	 *
	 * @return string
	 */
	public function getFieldKey(): string {
		return $this->field_key;
	}

	/**
	 * Get Raw Value
	 *
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Get Field Type
	 *
	 * @return string
	 */
	public function getFieldType(): string {
		return $this->field_type;
	}

	/**
	 * Get Label
	 *
	 * @return string
	 */
	public function getLabel(): string {
		return $this->label;
	}

	/**
	 * Get Typed Value
	 *
	 * Konvertiert den Wert basierend auf dem Feldtyp.
	 *
	 * @return mixed
	 */
	public function getTypedValue() {
		if ( $this->value === null ) {
			return null;
		}

		return match ( $this->field_type ) {
			'number'   => is_numeric( $this->value ) ? (float) $this->value : null,
			'checkbox' => $this->parseCheckboxValue(),
			'date'     => $this->value,
			'file'     => $this->parseFileValue(),
			default    => $this->value,
		};
	}

	/**
	 * Get Display Value
	 *
	 * Formatierter Wert für Admin-Anzeige.
	 *
	 * @return string
	 */
	public function getDisplayValue(): string {
		if ( $this->value === null || $this->value === '' ) {
			return '—';
		}

		return match ( $this->field_type ) {
			'checkbox' => $this->formatCheckboxDisplay(),
			'date'     => $this->formatDateDisplay(),
			'file'     => $this->formatFileDisplay(),
			'url'      => $this->formatUrlDisplay(),
			'email'    => $this->formatEmailDisplay(),
			'phone'    => $this->formatPhoneDisplay(),
			'number'   => $this->formatNumberDisplay(),
			'select', 'radio' => (string) $this->value,
			default    => esc_html( (string) $this->value ),
		};
	}

	/**
	 * Is Empty
	 *
	 * @return bool
	 */
	public function isEmpty(): bool {
		if ( $this->value === null ) {
			return true;
		}

		if ( is_string( $this->value ) && trim( $this->value ) === '' ) {
			return true;
		}

		if ( is_array( $this->value ) && empty( $this->value ) ) {
			return true;
		}

		return false;
	}

	// -------------------------------------------------------------------------
	// Setters
	// -------------------------------------------------------------------------

	/**
	 * Set Field Key
	 *
	 * @param string $field_key Field Key.
	 * @return self
	 */
	public function setFieldKey( string $field_key ): self {
		$this->field_key = $field_key;
		return $this;
	}

	/**
	 * Set Value
	 *
	 * @param mixed $value Value.
	 * @return self
	 */
	public function setValue( $value ): self {
		$this->value = $value;
		return $this;
	}

	/**
	 * Set Field Type
	 *
	 * @param string $field_type Field Type.
	 * @return self
	 */
	public function setFieldType( string $field_type ): self {
		$this->field_type = $field_type;
		return $this;
	}

	/**
	 * Set Label
	 *
	 * @param string $label Label.
	 * @return self
	 */
	public function setLabel( string $label ): self {
		$this->label = $label;
		return $this;
	}

	// -------------------------------------------------------------------------
	// Private Formatting Methods
	// -------------------------------------------------------------------------

	/**
	 * Parse Checkbox Value
	 *
	 * @return bool|array
	 */
	private function parseCheckboxValue() {
		// Single checkbox (boolean).
		if ( is_bool( $this->value ) ) {
			return $this->value;
		}

		if ( $this->value === '1' || $this->value === 1 || $this->value === 'true' ) {
			return true;
		}

		if ( $this->value === '0' || $this->value === 0 || $this->value === 'false' || $this->value === '' ) {
			return false;
		}

		// Multi-checkbox (array).
		if ( is_array( $this->value ) ) {
			return $this->value;
		}

		// JSON array string.
		if ( is_string( $this->value ) ) {
			$decoded = json_decode( $this->value, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return (bool) $this->value;
	}

	/**
	 * Parse File Value
	 *
	 * @return array|null
	 */
	private function parseFileValue(): ?array {
		if ( is_array( $this->value ) ) {
			return $this->value;
		}

		if ( is_string( $this->value ) ) {
			$decoded = json_decode( $this->value, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return null;
	}

	/**
	 * Format Checkbox for Display
	 *
	 * @return string
	 */
	private function formatCheckboxDisplay(): string {
		$parsed = $this->parseCheckboxValue();

		// Single checkbox.
		if ( is_bool( $parsed ) ) {
			return $parsed
				? __( 'Ja', 'recruiting-playbook' )
				: __( 'Nein', 'recruiting-playbook' );
		}

		// Multi-checkbox.
		if ( is_array( $parsed ) && ! empty( $parsed ) ) {
			return implode( ', ', array_map( 'esc_html', $parsed ) );
		}

		return '—';
	}

	/**
	 * Format Date for Display
	 *
	 * @return string
	 */
	private function formatDateDisplay(): string {
		if ( empty( $this->value ) ) {
			return '—';
		}

		$timestamp = strtotime( $this->value );
		if ( $timestamp === false ) {
			return esc_html( $this->value );
		}

		return wp_date( get_option( 'date_format', 'd.m.Y' ), $timestamp );
	}

	/**
	 * Format File for Display
	 *
	 * @return string
	 */
	private function formatFileDisplay(): string {
		$files = $this->parseFileValue();

		if ( empty( $files ) ) {
			return '—';
		}

		// Single file.
		if ( isset( $files['original_name'] ) ) {
			return esc_html( $files['original_name'] );
		}

		// Multiple files.
		$names = [];
		foreach ( $files as $file ) {
			if ( isset( $file['original_name'] ) ) {
				$names[] = esc_html( $file['original_name'] );
			}
		}

		return ! empty( $names ) ? implode( ', ', $names ) : '—';
	}

	/**
	 * Format URL for Display
	 *
	 * @return string
	 */
	private function formatUrlDisplay(): string {
		$url = esc_url( $this->value );
		if ( empty( $url ) ) {
			return '—';
		}

		// Show shortened URL.
		$display = preg_replace( '#^https?://#', '', $url );
		$display = rtrim( $display, '/' );
		if ( strlen( $display ) > 50 ) {
			$display = substr( $display, 0, 47 ) . '...';
		}

		return sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			$url,
			esc_html( $display )
		);
	}

	/**
	 * Format Email for Display
	 *
	 * @return string
	 */
	private function formatEmailDisplay(): string {
		$email = sanitize_email( $this->value );
		if ( empty( $email ) ) {
			return '—';
		}

		return sprintf(
			'<a href="mailto:%s">%s</a>',
			esc_attr( $email ),
			esc_html( $email )
		);
	}

	/**
	 * Format Phone for Display
	 *
	 * @return string
	 */
	private function formatPhoneDisplay(): string {
		$phone = esc_html( $this->value );
		if ( empty( $phone ) ) {
			return '—';
		}

		// Create tel: link.
		$tel = preg_replace( '/[^0-9+]/', '', $this->value );

		return sprintf(
			'<a href="tel:%s">%s</a>',
			esc_attr( $tel ),
			$phone
		);
	}

	/**
	 * Format Number for Display
	 *
	 * @return string
	 */
	private function formatNumberDisplay(): string {
		if ( ! is_numeric( $this->value ) ) {
			return esc_html( (string) $this->value );
		}

		// Format with locale-specific number formatting.
		return number_format_i18n( (float) $this->value );
	}

	// -------------------------------------------------------------------------
	// Conversion
	// -------------------------------------------------------------------------

	/**
	 * To Array
	 *
	 * @return array
	 */
	public function toArray(): array {
		return [
			'field_key'     => $this->field_key,
			'field_type'    => $this->field_type,
			'label'         => $this->label,
			'value'         => $this->value,
			'display_value' => $this->getDisplayValue(),
		];
	}

	/**
	 * Create from array
	 *
	 * @param string $field_key  Field key.
	 * @param mixed  $value      Value.
	 * @param string $field_type Field type.
	 * @param string $label      Label.
	 * @return self
	 */
	public static function create( string $field_key, $value, string $field_type = 'text', string $label = '' ): self {
		return new self( $field_key, $value, $field_type, $label );
	}
}
