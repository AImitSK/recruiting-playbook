<?php
/**
 * Custom Fields Meta Box für Stellenanzeigen
 *
 * Ermöglicht die Konfiguration, welche Custom Fields für eine Stelle angezeigt werden.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\MetaBoxes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\PostTypes\JobListing;
use RecruitingPlaybook\Services\FieldDefinitionService;
use RecruitingPlaybook\Models\FieldDefinition;
use WP_Post;

/**
 * Custom Fields Meta Box für Jobs
 */
class JobCustomFieldsMeta {

	/**
	 * Meta Key für Custom Fields Konfiguration
	 */
	public const META_KEY = '_rp_custom_fields_config';

	/**
	 * Meta Key für Custom Fields Override
	 */
	public const OVERRIDE_META_KEY = '_rp_custom_fields_override';

	/**
	 * Field Definition Service
	 *
	 * @var FieldDefinitionService|null
	 */
	private ?FieldDefinitionService $field_service = null;

	/**
	 * Meta Box registrieren
	 */
	public function register(): void {
		// Pro-Feature Check.
		if ( ! function_exists( 'rp_can' ) || ! rp_can( 'custom_fields' ) ) {
			return;
		}

		add_meta_box(
			'rp_job_custom_fields',
			__( 'Bewerbungsformular', 'recruiting-playbook' ),
			[ $this, 'render' ],
			JobListing::POST_TYPE,
			'side',
			'default'
		);

		// REST API Meta registrieren.
		register_post_meta(
			JobListing::POST_TYPE,
			self::META_KEY,
			[
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			]
		);

		register_post_meta(
			JobListing::POST_TYPE,
			self::OVERRIDE_META_KEY,
			[
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'boolean',
			]
		);
	}

	/**
	 * Field Definition Service lazy-loading
	 *
	 * @return FieldDefinitionService
	 */
	private function getFieldService(): FieldDefinitionService {
		if ( null === $this->field_service ) {
			$this->field_service = new FieldDefinitionService();
		}
		return $this->field_service;
	}

	/**
	 * Meta Box rendern
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( 'rp_job_custom_fields', 'rp_job_custom_fields_nonce' );

		$override = get_post_meta( $post->ID, self::OVERRIDE_META_KEY, true );
		$config   = get_post_meta( $post->ID, self::META_KEY, true );
		$config   = $config ? json_decode( $config, true ) : [];

		$fields = $this->getFieldService()->getActiveFields();

		// Nur nicht-System-Felder anzeigen.
		$custom_fields = array_filter(
			$fields,
			fn( FieldDefinition $f ) => ! $f->isSystem() && 'heading' !== $f->getType()
		);

		if ( empty( $custom_fields ) ) {
			echo '<p class="description">';
			echo esc_html__( 'Keine Custom Fields konfiguriert.', 'recruiting-playbook' );
			echo ' <a href="' . esc_url( admin_url( 'admin.php?page=rp-form-builder' ) ) . '">';
			echo esc_html__( 'Zum Form Builder', 'recruiting-playbook' );
			echo '</a></p>';
			return;
		}

		?>
		<div class="rp-job-custom-fields">
			<p>
				<label>
					<input type="checkbox"
						name="<?php echo esc_attr( self::OVERRIDE_META_KEY ); ?>"
						value="1"
						<?php checked( $override, '1' ); ?>
						class="rp-override-toggle"
					>
					<?php esc_html_e( 'Felder für diese Stelle anpassen', 'recruiting-playbook' ); ?>
				</label>
			</p>

			<div class="rp-fields-config" style="<?php echo $override ? '' : 'display:none;'; ?>">
				<p class="description" style="margin-bottom: 10px;">
					<?php esc_html_e( 'Aktivierte Felder werden im Bewerbungsformular dieser Stelle angezeigt.', 'recruiting-playbook' ); ?>
				</p>

				<?php foreach ( $custom_fields as $field ) : ?>
					<?php
					$field_key = $field->getFieldKey();
					$is_enabled = isset( $config[ $field_key ] ) ? (bool) $config[ $field_key ] : $field->isEnabled();
					?>
					<p>
						<label style="display: flex; align-items: center; gap: 8px;">
							<input type="checkbox"
								name="rp_field_config[<?php echo esc_attr( $field_key ); ?>]"
								value="1"
								<?php checked( $is_enabled ); ?>
							>
							<span><?php echo esc_html( $field->getLabel() ); ?></span>
							<?php if ( $field->isRequired() ) : ?>
								<span style="color: #d63638;">*</span>
							<?php endif; ?>
						</label>
					</p>
				<?php endforeach; ?>

				<p style="margin-top: 15px;">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-form-builder' ) ); ?>" class="button button-small">
						<?php esc_html_e( 'Felder bearbeiten', 'recruiting-playbook' ); ?>
					</a>
				</p>
			</div>

			<div class="rp-fields-default" style="<?php echo $override ? 'display:none;' : ''; ?>">
				<p class="description">
					<?php
					$enabled_count = count(
						array_filter( $custom_fields, fn( FieldDefinition $f ) => $f->isEnabled() )
					);
					printf(
						/* translators: %d: number of enabled fields */
						esc_html__( 'Standard-Konfiguration: %d Felder aktiv', 'recruiting-playbook' ),
						$enabled_count
					);
					?>
				</p>
			</div>
		</div>

		<script>
		jQuery(function($) {
			$('.rp-override-toggle').on('change', function() {
				if (this.checked) {
					$('.rp-fields-config').slideDown();
					$('.rp-fields-default').slideUp();
				} else {
					$('.rp-fields-config').slideUp();
					$('.rp-fields-default').slideDown();
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Meta-Werte speichern
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save( int $post_id, WP_Post $post ): void {
		// Pro-Feature Check.
		if ( ! function_exists( 'rp_can' ) || ! rp_can( 'custom_fields' ) ) {
			return;
		}

		// Nonce prüfen.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_POST['rp_job_custom_fields_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['rp_job_custom_fields_nonce'] ), 'rp_job_custom_fields' ) ) {
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

		// Override speichern.
		$override = isset( $_POST[ self::OVERRIDE_META_KEY ] ) ? '1' : '';
		update_post_meta( $post_id, self::OVERRIDE_META_KEY, $override );

		// Feld-Konfiguration speichern.
		if ( $override && isset( $_POST['rp_field_config'] ) && is_array( $_POST['rp_field_config'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_config = wp_unslash( $_POST['rp_field_config'] );
			$config = [];

			foreach ( $raw_config as $key => $value ) {
				$config[ sanitize_key( $key ) ] = (bool) $value;
			}

			update_post_meta( $post_id, self::META_KEY, wp_json_encode( $config ) );
		} elseif ( ! $override ) {
			// Override deaktiviert: Konfiguration löschen.
			delete_post_meta( $post_id, self::META_KEY );
		}
	}

	/**
	 * Custom Fields Konfiguration für einen Job abrufen
	 *
	 * @param int $job_id Job-ID.
	 * @return array|null Konfiguration oder null für Standard.
	 */
	public static function getJobConfig( int $job_id ): ?array {
		$override = get_post_meta( $job_id, self::OVERRIDE_META_KEY, true );

		if ( ! $override ) {
			return null; // Standard-Konfiguration verwenden.
		}

		$config = get_post_meta( $job_id, self::META_KEY, true );

		if ( ! $config ) {
			return null;
		}

		$decoded = json_decode( $config, true );

		return is_array( $decoded ) ? $decoded : null;
	}
}
