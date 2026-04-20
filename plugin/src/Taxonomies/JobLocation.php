<?php
/**
 * Taxonomy: Standort
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Taxonomies;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\PostTypes\JobListing;

/**
 * Taxonomy: Standort
 */
class JobLocation {

	public const TAXONOMY = 'job_location';

	public const META_STREET      = 'rp_street_address';
	public const META_POSTAL_CODE = 'rp_postal_code';
	public const META_REGION      = 'rp_address_region';

	/**
	 * Taxonomie registrieren
	 */
	public function register(): void {
		$labels = [
			'name'          => __( 'Locations', 'recruiting-playbook' ),
			'singular_name' => __( 'Location', 'recruiting-playbook' ),
			'search_items'  => __( 'Search Locations', 'recruiting-playbook' ),
			'all_items'     => __( 'All Locations', 'recruiting-playbook' ),
			'edit_item'     => __( 'Edit Location', 'recruiting-playbook' ),
			'update_item'   => __( 'Update Location', 'recruiting-playbook' ),
			'add_new_item'  => __( 'New Location', 'recruiting-playbook' ),
			'new_item_name' => __( 'New Location Name', 'recruiting-playbook' ),
			'menu_name'     => __( 'Locations', 'recruiting-playbook' ),
		];

		$args = [
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => 'standort' ],
			'show_in_rest'      => true,
			'rest_base'         => 'job-locations',
		];

		register_taxonomy( self::TAXONOMY, [ JobListing::POST_TYPE ], $args );

		$this->registerAddressFields();
	}

	/**
	 * Adress-Felder für Term-Meta registrieren (Street, PLZ, Region).
	 *
	 * Dient zur Anreicherung des Google for Jobs JSON-LD Schemas
	 * (streetAddress, postalCode, addressRegion).
	 */
	private function registerAddressFields(): void {
		// REST-API Exposure der Term-Meta.
		foreach ( [ self::META_STREET, self::META_POSTAL_CODE, self::META_REGION ] as $meta_key ) {
			register_term_meta(
				self::TAXONOMY,
				$meta_key,
				[
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => static fn() => current_user_can( 'manage_categories' ),
				]
			);
		}

		// UI: Felder beim Anlegen und Bearbeiten eines Location-Terms anzeigen.
		add_action( self::TAXONOMY . '_add_form_fields', [ $this, 'renderAddFields' ] );
		add_action( self::TAXONOMY . '_edit_form_fields', [ $this, 'renderEditFields' ], 10, 2 );
		add_action( 'created_' . self::TAXONOMY, [ $this, 'saveFields' ] );
		add_action( 'edited_' . self::TAXONOMY, [ $this, 'saveFields' ] );
	}

	/**
	 * Felder beim Anlegen eines neuen Location-Terms.
	 */
	public function renderAddFields(): void {
		wp_nonce_field( 'rp_location_address', 'rp_location_address_nonce' );
		?>
		<div class="form-field">
			<label for="<?php echo esc_attr( self::META_STREET ); ?>"><?php esc_html_e( 'Street address', 'recruiting-playbook' ); ?></label>
			<input type="text" name="<?php echo esc_attr( self::META_STREET ); ?>" id="<?php echo esc_attr( self::META_STREET ); ?>" value="" />
			<p><?php esc_html_e( 'Used for Google for Jobs structured data (streetAddress).', 'recruiting-playbook' ); ?></p>
		</div>
		<div class="form-field">
			<label for="<?php echo esc_attr( self::META_POSTAL_CODE ); ?>"><?php esc_html_e( 'Postal code', 'recruiting-playbook' ); ?></label>
			<input type="text" name="<?php echo esc_attr( self::META_POSTAL_CODE ); ?>" id="<?php echo esc_attr( self::META_POSTAL_CODE ); ?>" value="" />
		</div>
		<div class="form-field">
			<label for="<?php echo esc_attr( self::META_REGION ); ?>"><?php esc_html_e( 'Region / state', 'recruiting-playbook' ); ?></label>
			<input type="text" name="<?php echo esc_attr( self::META_REGION ); ?>" id="<?php echo esc_attr( self::META_REGION ); ?>" value="" />
			<p><?php esc_html_e( 'Federal state or region (e.g. "Bayern", "NRW").', 'recruiting-playbook' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Felder beim Bearbeiten eines Location-Terms.
	 *
	 * @param \WP_Term $term     Term-Objekt.
	 * @param string   $taxonomy Taxonomy-Name.
	 */
	public function renderEditFields( \WP_Term $term, string $taxonomy ): void {
		wp_nonce_field( 'rp_location_address', 'rp_location_address_nonce' );

		$street = get_term_meta( $term->term_id, self::META_STREET, true );
		$postal = get_term_meta( $term->term_id, self::META_POSTAL_CODE, true );
		$region = get_term_meta( $term->term_id, self::META_REGION, true );
		?>
		<tr class="form-field">
			<th scope="row"><label for="<?php echo esc_attr( self::META_STREET ); ?>"><?php esc_html_e( 'Street address', 'recruiting-playbook' ); ?></label></th>
			<td>
				<input type="text" name="<?php echo esc_attr( self::META_STREET ); ?>" id="<?php echo esc_attr( self::META_STREET ); ?>" value="<?php echo esc_attr( $street ); ?>" />
				<p class="description"><?php esc_html_e( 'Used for Google for Jobs structured data (streetAddress).', 'recruiting-playbook' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="<?php echo esc_attr( self::META_POSTAL_CODE ); ?>"><?php esc_html_e( 'Postal code', 'recruiting-playbook' ); ?></label></th>
			<td>
				<input type="text" name="<?php echo esc_attr( self::META_POSTAL_CODE ); ?>" id="<?php echo esc_attr( self::META_POSTAL_CODE ); ?>" value="<?php echo esc_attr( $postal ); ?>" />
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="<?php echo esc_attr( self::META_REGION ); ?>"><?php esc_html_e( 'Region / state', 'recruiting-playbook' ); ?></label></th>
			<td>
				<input type="text" name="<?php echo esc_attr( self::META_REGION ); ?>" id="<?php echo esc_attr( self::META_REGION ); ?>" value="<?php echo esc_attr( $region ); ?>" />
				<p class="description"><?php esc_html_e( 'Federal state or region (e.g. "Bayern", "NRW").', 'recruiting-playbook' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Adress-Felder speichern.
	 *
	 * @param int $term_id Term-ID.
	 */
	public function saveFields( int $term_id ): void {
		if ( ! isset( $_POST['rp_location_address_nonce'] )
			|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['rp_location_address_nonce'] ) ), 'rp_location_address' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		foreach ( [ self::META_STREET, self::META_POSTAL_CODE, self::META_REGION ] as $meta_key ) {
			if ( ! isset( $_POST[ $meta_key ] ) ) {
				continue;
			}
			$value = sanitize_text_field( wp_unslash( $_POST[ $meta_key ] ) );
			if ( '' === $value ) {
				delete_term_meta( $term_id, $meta_key );
			} else {
				update_term_meta( $term_id, $meta_key, $value );
			}
		}
	}
}
