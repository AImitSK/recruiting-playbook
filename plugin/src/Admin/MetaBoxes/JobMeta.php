<?php
/**
 * Meta-Felder für Stellenanzeigen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Admin\MetaBoxes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\PostTypes\JobListing;
use WP_Post;

/**
 * Meta-Felder für Stellenanzeigen
 */
class JobMeta {

	/**
	 * Meta-Felder Definitionen
	 */
	private const FIELDS = [
		'_rp_featured'            => [
			'type'     => 'checkbox',
			'label'    => 'Hervorgehobene Stelle (Top-Job)',
			'sanitize' => 'boolval',
		],
		'_rp_salary_min'          => [
			'type'     => 'number',
			'label'    => 'Gehalt (Min)',
			'sanitize' => 'absint',
		],
		'_rp_salary_max'          => [
			'type'     => 'number',
			'label'    => 'Gehalt (Max)',
			'sanitize' => 'absint',
		],
		'_rp_salary_currency'     => [
			'type'    => 'select',
			'label'   => 'Währung',
			'options' => [ 'EUR', 'CHF', 'USD' ],
			'default' => 'EUR',
		],
		'_rp_salary_period'       => [
			'type'    => 'select',
			'label'   => 'Gehaltszeitraum',
			'options' => [
				'hour'  => 'Pro Stunde',
				'month' => 'Pro Monat',
				'year'  => 'Pro Jahr',
			],
			'default' => 'month',
		],
		'_rp_hide_salary'         => [
			'type'     => 'checkbox',
			'label'    => 'Gehalt nicht anzeigen',
			'sanitize' => 'boolval',
		],
		'_rp_application_deadline' => [
			'type'     => 'date',
			'label'    => 'Bewerbungsfrist',
			'sanitize' => 'sanitize_text_field',
		],
		'_rp_contact_person'      => [
			'type'     => 'text',
			'label'    => 'Ansprechpartner',
			'sanitize' => 'sanitize_text_field',
		],
		'_rp_contact_email'       => [
			'type'     => 'email',
			'label'    => 'Kontakt E-Mail',
			'sanitize' => 'sanitize_email',
		],
		'_rp_contact_phone'       => [
			'type'     => 'tel',
			'label'    => 'Kontakt Telefon',
			'sanitize' => 'sanitize_text_field',
		],
		'_rp_remote_option'       => [
			'type'    => 'select',
			'label'   => 'Remote-Arbeit',
			'options' => [
				''       => 'Keine Angabe',
				'no'     => 'Keine Remote-Arbeit',
				'hybrid' => 'Hybrid (teilweise Remote)',
				'full'   => '100% Remote möglich',
			],
		],
		'_rp_start_date'          => [
			'type'        => 'text',
			'label'       => 'Startdatum',
			'placeholder' => 'z.B. "Ab sofort" oder "01.04.2025"',
			'sanitize'    => 'sanitize_text_field',
		],
	];

	/**
	 * Meta Box registrieren
	 */
	public function register(): void {
		add_meta_box(
			'rp_job_details',
			__( 'Job Details', 'recruiting-playbook' ),
			[ $this, 'render' ],
			JobListing::POST_TYPE,
			'normal',
			'high'
		);

		// REST API Meta registrieren.
		foreach ( self::FIELDS as $key => $field ) {
			register_post_meta(
				JobListing::POST_TYPE,
				$key,
				[
					'show_in_rest' => true,
					'single'       => true,
					'type'         => $this->getRestType( $field['type'] ),
				]
			);
		}
	}

	/**
	 * Meta Box rendern
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( 'rp_job_meta', 'rp_job_meta_nonce' );

		echo '<div class="rp-meta-fields">';

		// Featured / Hervorgehobene Stelle.
		echo '<div class="rp-featured-toggle">';
		$this->renderField( '_rp_featured', $post );
		echo '</div>';

		// Gehalt.
		echo '<fieldset class="rp-fieldset">';
		echo '<legend>' . esc_html__( 'Salary', 'recruiting-playbook' ) . '</legend>';
		echo '<div class="rp-field-group">';
		$this->renderField( '_rp_salary_min', $post );
		$this->renderField( '_rp_salary_max', $post );
		$this->renderField( '_rp_salary_currency', $post );
		$this->renderField( '_rp_salary_period', $post );
		$this->renderField( '_rp_hide_salary', $post );
		echo '</div>';
		echo '</fieldset>';

		// Kontakt.
		echo '<fieldset class="rp-fieldset">';
		echo '<legend>' . esc_html__( 'Contact Person', 'recruiting-playbook' ) . '</legend>';
		echo '<div class="rp-field-group">';
		$this->renderField( '_rp_contact_person', $post );
		$this->renderField( '_rp_contact_email', $post );
		$this->renderField( '_rp_contact_phone', $post );
		echo '</div>';
		echo '</fieldset>';

		// Details.
		echo '<fieldset class="rp-fieldset">';
		echo '<legend>' . esc_html__( 'Additional Details', 'recruiting-playbook' ) . '</legend>';
		echo '<div class="rp-field-group">';
		$this->renderField( '_rp_application_deadline', $post );
		$this->renderField( '_rp_start_date', $post );
		$this->renderField( '_rp_remote_option', $post );
		echo '</div>';
		echo '</fieldset>';

		echo '</div>';

		// Inline Styles.
		?>
		<style>
			.rp-meta-fields { display: grid; gap: 20px; }
			.rp-fieldset { border: 1px solid #ccd0d4; padding: 15px; }
			.rp-fieldset legend { font-weight: 600; padding: 0 10px; }
			.rp-field-group { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
			.rp-field { display: flex; flex-direction: column; gap: 5px; }
			.rp-field label { font-weight: 500; }
			.rp-field input, .rp-field select { width: 100%; }
			.rp-field-checkbox { flex-direction: row; align-items: center; }
			.rp-field-checkbox input { width: auto; margin-right: 8px; }
			.rp-featured-toggle { background: #f0f6fc; border: 1px solid #c3c4c7; border-left: 4px solid #2271b1; padding: 12px 15px; }
			.rp-featured-toggle .rp-field-checkbox label { font-weight: 600; font-size: 13px; }
		</style>
		<?php
	}

	/**
	 * Einzelnes Feld rendern
	 *
	 * @param string  $key  Field key.
	 * @param WP_Post $post Post object.
	 */
	private function renderField( string $key, WP_Post $post ): void {
		$field = self::FIELDS[ $key ];
		$value = get_post_meta( $post->ID, $key, true );
		$id    = 'rp_' . ltrim( $key, '_rp_' );

		echo '<div class="rp-field' . ( 'checkbox' === $field['type'] ? ' rp-field-checkbox' : '' ) . '">';

		switch ( $field['type'] ) {
			case 'select':
				echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';
				echo '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $id ) . '">';
				foreach ( $field['options'] as $opt_value => $opt_label ) {
					$is_default = isset( $field['default'] ) && $field['default'] === $opt_value;
					$selected   = ( $value === (string) $opt_value || ( '' === $value && $is_default ) );
					echo '<option value="' . esc_attr( $opt_value ) . '"' . selected( $selected, true, false ) . '>';
					echo esc_html( is_string( $opt_label ) ? $opt_label : $opt_value );
					echo '</option>';
				}
				echo '</select>';
				break;

			case 'checkbox':
				echo '<input type="checkbox" name="' . esc_attr( $key ) . '" id="' . esc_attr( $id ) . '" value="1"' . checked( $value, '1', false ) . '>';
				echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';
				break;

			default:
				echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';
				echo '<input type="' . esc_attr( $field['type'] ) . '" ';
				echo 'name="' . esc_attr( $key ) . '" ';
				echo 'id="' . esc_attr( $id ) . '" ';
				echo 'value="' . esc_attr( $value ) . '"';
				if ( isset( $field['placeholder'] ) ) {
					echo ' placeholder="' . esc_attr( $field['placeholder'] ) . '"';
				}
				echo '>';
		}

		echo '</div>';
	}

	/**
	 * Meta-Werte speichern
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save( int $post_id, WP_Post $post ): void {
		// Nonce prüfen.
		if ( ! isset( $_POST['rp_job_meta_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['rp_job_meta_nonce'] ), 'rp_job_meta' ) ) {
			return;
		}

		// Autosave überspringen.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Berechtigung prüfen.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Felder speichern.
		foreach ( self::FIELDS as $key => $field ) {
			if ( isset( $_POST[ $key ] ) ) {
				$value = wp_unslash( $_POST[ $key ] );

				// Sanitization.
				if ( isset( $field['sanitize'] ) ) {
					$value = call_user_func( $field['sanitize'], $value );
				} else {
					$value = sanitize_text_field( $value );
				}

				update_post_meta( $post_id, $key, $value );
			} else {
				// Checkbox: nicht gesetzt = löschen.
				if ( 'checkbox' === $field['type'] ) {
					delete_post_meta( $post_id, $key );
				}
			}
		}
	}

	/**
	 * REST API Typ ermitteln
	 *
	 * @param string $field_type Field type.
	 * @return string
	 */
	private function getRestType( string $field_type ): string {
		return match ( $field_type ) {
			'number' => 'integer',
			'checkbox' => 'boolean',
			default => 'string',
		};
	}
}
