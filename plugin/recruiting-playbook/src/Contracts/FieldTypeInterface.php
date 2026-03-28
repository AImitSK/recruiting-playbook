<?php
/**
 * FieldType Interface
 *
 * Definiert die Schnittstelle für alle Feldtypen im Custom Fields Builder.
 *
 * @package RecruitingPlaybook\Contracts
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Contracts;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * Interface für Feldtypen
 */
interface FieldTypeInterface {

	/**
	 * Feldtyp-Identifier zurückgeben
	 *
	 * @return string z.B. 'text', 'email', 'select'
	 */
	public function getType(): string;

	/**
	 * Anzeige-Label zurückgeben
	 *
	 * @return string z.B. 'Textfeld', 'E-Mail', 'Dropdown'
	 */
	public function getLabel(): string;

	/**
	 * Icon-Name zurückgeben (Lucide Icon)
	 *
	 * @return string z.B. 'type', 'mail', 'chevron-down'
	 */
	public function getIcon(): string;

	/**
	 * Gruppe zurückgeben für UI-Kategorisierung
	 *
	 * @return string 'text', 'choice', 'special', 'layout'
	 */
	public function getGroup(): string;

	/**
	 * Standard-Einstellungen zurückgeben
	 *
	 * @return array
	 */
	public function getDefaultSettings(): array;

	/**
	 * Standard-Validierungsregeln zurückgeben
	 *
	 * @return array
	 */
	public function getDefaultValidation(): array;

	/**
	 * Verfügbare Validierungsregeln für diesen Typ
	 *
	 * @return array Array mit Regel-Definitionen für den Editor
	 */
	public function getAvailableValidationRules(): array;

	/**
	 * Wert validieren
	 *
	 * @param mixed           $value      Der zu validierende Wert.
	 * @param FieldDefinition $field      Die Feld-Definition.
	 * @param array           $form_data  Alle Formulardaten (für Conditional Logic).
	 * @return true|WP_Error True wenn gültig, WP_Error mit Fehlermeldung wenn ungültig.
	 */
	public function validate( $value, FieldDefinition $field, array $form_data = [] ): bool|WP_Error;

	/**
	 * Wert bereinigen/sanitisieren
	 *
	 * @param mixed           $value Der zu bereinigende Wert.
	 * @param FieldDefinition $field Die Feld-Definition.
	 * @return mixed Der bereinigte Wert.
	 */
	public function sanitize( $value, FieldDefinition $field ): mixed;

	/**
	 * Prüfen ob Wert leer ist
	 *
	 * @param mixed $value Der zu prüfende Wert.
	 * @return bool
	 */
	public function isEmpty( $value ): bool;

	/**
	 * Feld für Frontend rendern
	 *
	 * @param FieldDefinition $field Die Feld-Definition.
	 * @param mixed           $value Aktueller Wert (optional).
	 * @return string HTML-Output.
	 */
	public function render( FieldDefinition $field, $value = null ): string;

	/**
	 * Wert für Anzeige formatieren
	 *
	 * @param mixed           $value Der anzuzeigende Wert.
	 * @param FieldDefinition $field Die Feld-Definition.
	 * @return string Formatierter Wert für Admin-Anzeige.
	 */
	public function formatDisplayValue( $value, FieldDefinition $field ): string;

	/**
	 * Wert für CSV-Export formatieren
	 *
	 * @param mixed           $value Der zu exportierende Wert.
	 * @param FieldDefinition $field Die Feld-Definition.
	 * @return string Formatierter Wert für CSV.
	 */
	public function formatExportValue( $value, FieldDefinition $field ): string;

	/**
	 * Prüfen ob dieser Feldtyp Optionen unterstützt (Select, Radio, Checkbox)
	 *
	 * @return bool
	 */
	public function supportsOptions(): bool;

	/**
	 * Prüfen ob dieser Feldtyp Datei-Uploads ist
	 *
	 * @return bool
	 */
	public function isFileUpload(): bool;
}
