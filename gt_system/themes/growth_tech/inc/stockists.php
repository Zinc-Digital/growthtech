<?php
/**
 * Stockists: post type, type taxonomy, country list, address helper and the
 * admin geocode column. Geocoding and the REST proxy live further down
 * (appended in Task 2); the front end is page-templates/page-stockists.php.
 */

function gt_register_stockist_post_type() {
	register_post_type(
		'stockist',
		array(
			'labels'              => array(
				'name'          => __( 'Stockists', 'gt' ),
				'singular_name' => __( 'Stockist', 'gt' ),
				'add_new_item'  => __( 'Add New Stockist', 'gt' ),
				'edit_item'     => __( 'Edit Stockist', 'gt' ),
				'menu_name'     => __( 'Stockists', 'gt' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			// No REST exposure — the finder page renders stockists itself; keeps published entries out of /wp/v2.
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-location',
			'menu_position'       => 27,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
		)
	);

	register_taxonomy(
		'stockist_type',
		'stockist',
		array(
			'labels'             => array(
				'name'          => __( 'Stockist Types', 'gt' ),
				'singular_name' => __( 'Stockist Type', 'gt' ),
				'menu_name'     => __( 'Types', 'gt' ),
				'add_new_item'  => __( 'Add New Type', 'gt' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_admin_column'  => true,
			// No REST exposure — the finder page renders stockists itself; keeps published entries out of /wp/v2.
			'show_in_rest'       => false,
			'hierarchical'       => false,
			'rewrite'            => false,
			'query_var'          => false,
		)
	);
}
add_action( 'init', 'gt_register_stockist_post_type' );

/** ISO-2 code => country name, from WooCommerce (falls back to GB only). */
function gt_stockist_countries() {
	static $countries = null;
	if ( null === $countries ) {
		$countries = ( function_exists( 'WC' ) && WC()->countries ) ? WC()->countries->get_countries() : array( 'GB' => 'United Kingdom (UK)' );
	}
	return $countries;
}

/** Feed the country select from WooCommerce's list. */
function gt_stockist_country_choices( $field ) {
	$field['choices'] = gt_stockist_countries();
	return $field;
}
add_filter( 'acf/load_field/name=country', 'gt_stockist_country_choices' );

/** "1 High Street, Taunton, TA1 1AA, United Kingdom (UK)" — empties skipped. */
function gt_stockist_address_string( $post_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}
	$parts = array();
	foreach ( array( 'address_1', 'address_2', 'town', 'region', 'postcode' ) as $name ) {
		$value = trim( (string) get_field( $name, $post_id ) );
		if ( '' !== $value ) {
			$parts[] = $value;
		}
	}
	$code      = strtoupper( trim( (string) get_field( 'country', $post_id ) ) );
	$countries = gt_stockist_countries();
	if ( $code ) {
		$parts[] = isset( $countries[ $code ] ) ? $countries[ $code ] : $code;
	}
	return implode( ', ', $parts );
}

/** Admin list: show whether each stockist has coordinates. */
function gt_stockist_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['geocode'] = __( 'Geocode', 'gt' );
		}
	}
	return $new;
}
add_filter( 'manage_stockist_posts_columns', 'gt_stockist_admin_columns' );

function gt_stockist_admin_column_value( $column, $post_id ) {
	if ( 'geocode' !== $column || ! function_exists( 'get_field' ) ) {
		return;
	}
	$status = (string) get_field( 'geocode_status', $post_id );
	$labels = array(
		'ok'     => __( 'Located', 'gt' ),
		'manual' => __( 'Manual coordinates', 'gt' ),
		'failed' => __( 'Not found — check the address', 'gt' ),
		'no_key' => __( 'No Google Maps key set', 'gt' ),
	);
	echo esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : __( 'Not geocoded yet', 'gt' ) );
}
add_action( 'manage_stockist_posts_custom_column', 'gt_stockist_admin_column_value', 10, 2 );

// -- Geocoding -------------------------------------------------------------------

/** Google Maps Platform key from Theme Settings, '' when unset. */
function gt_maps_key() {
	$key = function_exists( 'get_field' ) ? get_field( 'shop_google_maps_key', 'option' ) : '';
	return trim( (string) $key );
}

/**
 * Look an address up with the Google Geocoding API.
 *
 * @param string $address Free-text address or place.
 * @param string $region  Optional ISO-2 bias, e.g. "gb".
 * @return array|WP_Error ['lat' => float, 'lng' => float, 'label' => string]
 */
function gt_stockist_geocode( $address, $region = '' ) {
	$address = trim( (string) $address );
	$region  = strtolower( trim( (string) $region ) );
	if ( '' === $address ) {
		return new WP_Error( 'empty', __( 'No address to look up.', 'gt' ) );
	}
	$key = gt_maps_key();
	if ( '' === $key ) {
		return new WP_Error( 'no_key', __( 'No Google Maps API key is set in Theme Settings.', 'gt' ) );
	}

	$cache_key = 'gt_geocode_' . md5( $region . '|' . $address );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$url = add_query_arg( array_filter( array(
		'address' => rawurlencode( $address ),
		'region'  => $region ? rawurlencode( $region ) : '',
		'key'     => rawurlencode( $key ),
	) ), 'https://maps.googleapis.com/maps/api/geocode/json' );

	$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'http', __( 'The geocoding request failed.', 'gt' ) );
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) || empty( $data['status'] ) ) {
		return new WP_Error( 'bad_response', __( 'Unexpected geocoding response.', 'gt' ) );
	}
	if ( 'ZERO_RESULTS' === $data['status'] || empty( $data['results'][0]['geometry']['location'] ) ) {
		return new WP_Error( 'zero_results', __( 'No location found for that address.', 'gt' ) );
	}
	if ( 'OK' !== $data['status'] ) {
		return new WP_Error( 'bad_response', sprintf( 'Geocoding status: %s', sanitize_text_field( $data['status'] ) ) );
	}

	$location = $data['results'][0]['geometry']['location'];
	$result   = array(
		'lat'   => (float) $location['lat'],
		'lng'   => (float) $location['lng'],
		'label' => isset( $data['results'][0]['formatted_address'] ) ? sanitize_text_field( $data['results'][0]['formatted_address'] ) : $address,
	);
	set_transient( $cache_key, $result, 30 * DAY_IN_SECONDS );
	return $result;
}

/**
 * Keep coordinates in step with the address whenever a stockist is saved.
 * Typed-in coordinates (no geocoded_address on record) are left alone.
 */
function gt_stockist_maybe_geocode( $post_id ) {
	if ( ! is_numeric( $post_id ) || 'stockist' !== get_post_type( $post_id ) || ! function_exists( 'get_field' ) ) {
		return;
	}
	$post_id = (int) $post_id;
	$lat     = get_field( 'lat', $post_id );
	$lng     = get_field( 'lng', $post_id );
	$has_xy  = '' !== (string) $lat && null !== $lat && '' !== (string) $lng && null !== $lng;
	$address = gt_stockist_address_string( $post_id );
	$last    = (string) get_field( 'geocoded_address', $post_id );

	if ( $has_xy && '' === $last ) {
		update_field( 'field_gt_stockist_geocode_status', 'manual', $post_id );
		return;
	}
	if ( $has_xy && $last === $address ) {
		return; // Nothing changed.
	}
	if ( '' === $address ) {
		return;
	}

	$region = strtolower( (string) get_field( 'country', $post_id ) );
	$result = gt_stockist_geocode( $address, $region );
	if ( is_wp_error( $result ) ) {
		update_field( 'field_gt_stockist_geocode_status', 'no_key' === $result->get_error_code() ? 'no_key' : 'failed', $post_id );
		return;
	}
	update_field( 'field_gt_stockist_lat', $result['lat'], $post_id );
	update_field( 'field_gt_stockist_lng', $result['lng'], $post_id );
	update_field( 'field_gt_stockist_geocoded_address', $address, $post_id );
	update_field( 'field_gt_stockist_geocode_status', 'ok', $post_id );
}
add_action( 'acf/save_post', 'gt_stockist_maybe_geocode', 20 );

// -- REST proxy (keeps the key server-side) -----------------------------------------

function gt_stockist_register_rest() {
	register_rest_route( 'gt/v1', '/geocode', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'args'                => array(
			'q'      => array( 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
			'region' => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_key' ),
		),
		'callback'            => function ( WP_REST_Request $request ) {
			$q = trim( (string) $request->get_param( 'q' ) );
			if ( mb_strlen( $q ) < 2 ) {
				return new WP_REST_Response( array( 'code' => 'too_short' ), 400 );
			}
			$result = gt_stockist_geocode( $q, (string) $request->get_param( 'region' ) );
			if ( is_wp_error( $result ) ) {
				$codes = array( 'no_key' => 503, 'zero_results' => 404, 'http' => 502, 'bad_response' => 502, 'empty' => 400 );
				$code  = $result->get_error_code();
				return new WP_REST_Response( array( 'code' => $code ), isset( $codes[ $code ] ) ? $codes[ $code ] : 500 );
			}
			return new WP_REST_Response( $result, 200 );
		},
	) );
}
add_action( 'rest_api_init', 'gt_stockist_register_rest' );
