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
add_filter( 'acf/load_field/key=field_gt_stockist_country', 'gt_stockist_country_choices' );

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
		'error'  => __( 'Lookup error — check the key and try again', 'gt' ),
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
 * Transient key for a geocode lookup. The address is case-folded so "Taunton"
 * and "taunton" share one cache entry; used both by gt_stockist_geocode()
 * and by the save hook, which needs the same key to force a fresh lookup.
 *
 * @param string $address Free-text address or place.
 * @param string $region  Optional ISO-2 bias, e.g. "gb".
 */
function gt_stockist_geocode_cache_key( $address, $region = '' ) {
	$address = mb_strtolower( trim( (string) $address ) );
	$region  = strtolower( trim( (string) $region ) );
	return 'gt_geocode_' . md5( $region . '|' . $address );
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
	$key = gt_geocoding_key();
	if ( '' === $key ) {
		return new WP_Error( 'no_key', __( 'No Google Maps API key is set in Theme Settings.', 'gt' ) );
	}

	$cache_key = gt_stockist_geocode_cache_key( $address, $region );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		if ( isset( $cached['error'] ) ) {
			return new WP_Error( $cached['error'], isset( $cached['message'] ) ? $cached['message'] : $cached['error'] );
		}
		return $cached;
	}

	$url = add_query_arg( array_filter( array(
		'address' => rawurlencode( $address ),
		'region'  => $region ? rawurlencode( $region ) : '',
		'key'     => rawurlencode( $key ),
	) ), 'https://maps.googleapis.com/maps/api/geocode/json' );

	$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		$message = __( 'The geocoding request failed.', 'gt' );
		set_transient( $cache_key, array( 'error' => 'http', 'message' => $message ), 5 * MINUTE_IN_SECONDS );
		return new WP_Error( 'http', $message );
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) || empty( $data['status'] ) ) {
		$message = __( 'Unexpected geocoding response.', 'gt' );
		set_transient( $cache_key, array( 'error' => 'bad_response', 'message' => $message ), 5 * MINUTE_IN_SECONDS );
		return new WP_Error( 'bad_response', $message );
	}
	if ( 'ZERO_RESULTS' === $data['status'] || ( 'OK' === $data['status'] && empty( $data['results'][0]['geometry']['location'] ) ) ) {
		$message = __( 'No location found for that address.', 'gt' );
		set_transient( $cache_key, array( 'error' => 'zero_results', 'message' => $message ), HOUR_IN_SECONDS );
		return new WP_Error( 'zero_results', $message );
	}
	// Any other non-OK status (REQUEST_DENIED, OVER_QUERY_LIMIT, ...) also has
	// empty results, but says nothing about the address — keep it distinct.
	if ( 'OK' !== $data['status'] ) {
		// Google's error_message can include account/billing detail — log it, never return it over REST.
		if ( in_array( $data['status'], array( 'REQUEST_DENIED', 'OVER_QUERY_LIMIT', 'INVALID_REQUEST' ), true ) && ! empty( $data['error_message'] ) ) {
			error_log( sprintf( 'Google Geocoding %s: %s', $data['status'], $data['error_message'] ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
		$message = sprintf(
			/* translators: %s: Google's geocoding status code. */
			__( 'Geocoding status: %s', 'gt' ),
			sanitize_text_field( $data['status'] )
		);
		set_transient( $cache_key, array( 'error' => 'bad_response', 'message' => $message ), 5 * MINUTE_IN_SECONDS );
		return new WP_Error( 'bad_response', $message );
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

	// The address is unchanged but the coordinates are empty: an admin cleared
	// Latitude/Longitude to force a fresh lookup. Drop the cached result for
	// this address so the request actually reaches Google again rather than
	// quietly handing back the same 30-day-cached answer.
	if ( ! $has_xy && '' !== $last && $last === $address ) {
		delete_transient( gt_stockist_geocode_cache_key( $address, $region ) );
	}

	$result = gt_stockist_geocode( $address, $region );
	if ( is_wp_error( $result ) ) {
		// Only a definite "no such place" clears the coordinates: a stale pin
		// must not stay on the public map once Google says the address does not
		// exist. A key/transport problem (REQUEST_DENIED, quota, timeout) says
		// nothing about the address, so the existing pin is kept and the admin
		// column points at the key instead. On no_key nothing was attempted.
		$code = $result->get_error_code();
		if ( 'zero_results' === $code ) {
			update_field( 'field_gt_stockist_lat', '', $post_id );
			update_field( 'field_gt_stockist_lng', '', $post_id );
			update_field( 'field_gt_stockist_geocoded_address', '', $post_id );
			$status = 'failed';
		} elseif ( 'no_key' === $code ) {
			$status = 'no_key';
		} else {
			$status = 'error';
		}
		update_field( 'field_gt_stockist_geocode_status', $status, $post_id );
		return;
	}
	update_field( 'field_gt_stockist_lat', $result['lat'], $post_id );
	update_field( 'field_gt_stockist_lng', $result['lng'], $post_id );
	update_field( 'field_gt_stockist_geocoded_address', $address, $post_id );
	update_field( 'field_gt_stockist_geocode_status', 'ok', $post_id );
}
add_action( 'acf/save_post', 'gt_stockist_maybe_geocode', 20 );

// -- REST proxy (keeps the key server-side) -----------------------------------------

/**
 * Per-IP request counter for the geocode proxy: allows 30 requests per
 * fixed 60s window. The window's expiry is set once, on the first request
 * that opens it, and later requests never push it back — an accumulator
 * that keeps refreshing its own TTL on every call would let a client that
 * stays active (retrying after a 429, or just browsing steadily) end up
 * blocked indefinitely, since the window would never get 60s of total
 * silence to expire in. Returns true once an IP is over the limit.
 */
function gt_stockist_rate_limited( $ip ) {
	$key  = 'gt_geocode_rl_' . md5( (string) $ip );
	$data = get_transient( $key );
	if ( ! is_array( $data ) || ! isset( $data['count'], $data['reset'] ) || time() >= $data['reset'] ) {
		// No window yet, or the old one has lapsed: open a fresh one.
		set_transient( $key, array( 'count' => 1, 'reset' => time() + 60 ), 60 );
		return false;
	}
	if ( $data['count'] > 30 ) {
		// Already over the limit for this window: don't write, don't extend it.
		return true;
	}
	$data['count']++;
	// Re-save with whatever time is left in the window, never more.
	set_transient( $key, $data, max( 1, $data['reset'] - time() ) );
	return $data['count'] > 30;
}

function gt_stockist_register_rest() {
	register_rest_route( 'gt/v1', '/geocode', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'args'                => array(
			'q'      => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $value ) {
					return mb_strlen( (string) $value ) <= 200;
				},
			),
			'region' => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => function ( $value ) {
					return (bool) preg_match( '/^[a-z]{0,2}$/i', (string) $value );
				},
			),
		),
		'callback'            => function ( WP_REST_Request $request ) {
			$q = trim( (string) $request->get_param( 'q' ) );
			if ( mb_strlen( $q ) < 2 ) {
				return new WP_REST_Response( array( 'code' => 'too_short' ), 400 );
			}
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			if ( gt_stockist_rate_limited( $ip ) ) {
				$response = new WP_REST_Response( array( 'code' => 'rate_limited' ), 429 );
				$response->header( 'Retry-After', '60' );
				return $response;
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

// -- Data for the finder page ------------------------------------------------------

/** Server-side Geocoding key: the dedicated field, else the Maps key. */
function gt_geocoding_key() {
	$key = function_exists( 'get_field' ) ? trim( (string) get_field( 'shop_google_geocoding_key', 'option' ) ) : '';
	return '' !== $key ? $key : gt_maps_key();
}

/** Every published stockist as a flat array the template and JS both read. */
function gt_stockists_all() {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}
	$countries     = gt_stockist_countries();
	$posts         = get_posts( array( 'post_type' => 'stockist', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	$out           = array();
	// Several stockists commonly link the same product; resolve each product
	// id at most once per request instead of once per stockist→product link.
	$product_cache = array();

	foreach ( $posts as $post ) {
		$id       = $post->ID;
		$code     = strtoupper( trim( (string) get_field( 'country', $id ) ) );
		$lat      = get_field( 'lat', $id );
		$lng      = get_field( 'lng', $id );
		$has_xy   = '' !== (string) $lat && null !== $lat && '' !== (string) $lng && null !== $lng;
		$products = array();
		$brands   = array();
		foreach ( array_map( 'intval', (array) get_field( 'products', $id ) ) as $pid ) {
			if ( ! $pid ) {
				continue;
			}
			if ( ! array_key_exists( $pid, $product_cache ) ) {
				$product = wc_get_product( $pid );
				$product_cache[ $pid ] = ( $product instanceof WC_Product && 'publish' === $product->get_status() )
					? array( 'name' => $product->get_name(), 'slug' => $product->get_slug(), 'brand' => gt_product_brand( $product ) )
					: null;
			}
			$cached = $product_cache[ $pid ];
			if ( null === $cached ) {
				continue;
			}
			$products[] = array( 'id' => $pid, 'slug' => $cached['slug'], 'name' => $cached['name'] );
			if ( $cached['brand'] ) {
				$brands[ $cached['brand']->slug ] = $cached['brand']->slug;
			}
		}
		$types   = wp_get_post_terms( $id, 'stockist_type', array( 'fields' => 'names' ) );
		$address = gt_stockist_address_string( $id );
		$town    = trim( (string) get_field( 'town', $id ) );

		$out[] = array(
			'id'           => $id,
			'name'         => get_the_title( $post ),
			'town'         => $town,
			'region'       => trim( (string) get_field( 'region', $id ) ),
			'postcode'     => trim( (string) get_field( 'postcode', $id ) ),
			'country'      => $code,
			'country_name' => isset( $countries[ $code ] ) ? $countries[ $code ] : $code,
			'type'         => ( ! is_wp_error( $types ) && $types ) ? $types[0] : '',
			'lat'          => $has_xy ? (float) $lat : null,
			'lng'          => $has_xy ? (float) $lng : null,
			'phone'        => trim( (string) get_field( 'phone', $id ) ),
			'website'      => trim( (string) get_field( 'website', $id ) ),
			'email'        => trim( (string) get_field( 'email', $id ) ),
			'address'      => $address,
			// Coordinates need no encoding (esc_url() only touches the "&" on output); a free-text
			// address does, so only that branch is rawurlencode()'d.
			'directions'   => ( $has_xy || '' !== $address )
				? 'https://www.google.com/maps/dir/?api=1&destination=' . ( $has_xy ? $lat . ',' . $lng : rawurlencode( $address ) )
				: '',
			'products'     => $products,
			'brands'       => array_values( $brands ),
			'search'       => mb_strtolower( trim( get_the_title( $post ) . ' ' . $town . ' ' . get_field( 'postcode', $id ) . ' ' . get_field( 'region', $id ) ) ),
		);
	}
	return $out;
}

/**
 * id => name for the "Stocking any product" select. Every published product,
 * including catalog-hidden ones — gt_stockist_preselect() accepts any
 * published product for a ?product= link, so excluding hidden ones here
 * would silently break the preselect for those.
 */
function gt_stockist_product_options() {
	$options = array();
	foreach ( wc_get_products( array( 'limit' => -1, 'status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) ) as $product ) {
		$options[ $product->get_slug() ] = $product->get_name();
	}
	return $options;
}

/** Country options for the International tab: codes present on non-GB stockists. */
function gt_stockist_countries_present( array $stockists ) {
	$present = array();
	foreach ( $stockists as $s ) {
		if ( 'GB' !== $s['country'] && $s['country'] ) {
			$present[ $s['country'] ] = $s['country_name'];
		}
	}
	asort( $present );
	return $present;
}

/**
 * WooCommerce registers "product" as the public query var for its product
 * post type, so a plain page request carrying ?product=clonex-mist (our preselect
 * param) gets merged into the main query as pagename=find-a-stockist AND
 * post_type=product/name=clonex-mist (WP maps the "product" var onto "name" for
 * its rewrite tag) — an impossible combination WP_Query can't match, which
 * 404s the page before page-stockists.php ever runs. gt_stockist_preselect()
 * reads the value straight from $_GET, so it's safe to drop these here;
 * only the main query resolution needs protecting, and only when it
 * already resolves to a page (a real product permalink never carries a
 * pagename/page_id too).
 */
function gt_stockist_strip_product_query_var( $vars ) {
	$is_page_request = isset( $vars['pagename'] ) || isset( $vars['page_id'] );
	if ( $is_page_request && isset( $vars['product'] ) && 'product' === ( isset( $vars['post_type'] ) ? $vars['post_type'] : '' ) ) {
		unset( $vars['product'], $vars['post_type'], $vars['name'] );
	}
	return $vars;
}
add_filter( 'request', 'gt_stockist_strip_product_query_var' );

/**
 * What the URL asked for: ?product=, ?brand=, ?region=, ?q=.
 *
 * Products are identified by slug (?product=clonex-mist) so the link reads
 * as the product it points at; a numeric id from an older link is mapped to
 * its slug. Anything that is not a published product resolves to ''.
 */
function gt_stockist_preselect() {
	$product = '';
	if ( isset( $_GET['product'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$raw  = sanitize_title( wp_unslash( $_GET['product'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$post = ctype_digit( $raw ) ? get_post( (int) $raw ) : get_page_by_path( $raw, OBJECT, 'product' );
		if ( $post instanceof WP_Post && 'product' === $post->post_type && 'publish' === $post->post_status ) {
			$product = $post->post_name;
		}
	}
	$brand = isset( $_GET['brand'] ) ? sanitize_title( wp_unslash( $_GET['brand'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $brand && ! term_exists( $brand, 'product_brand' ) ) {
		$brand = '';
	}
	$region = isset( $_GET['region'] ) && 'international' === sanitize_key( $_GET['region'] ) ? 'international' : 'uk'; // phpcs:ignore WordPress.Security.NonceVerification
	$q      = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	return array( 'product' => $product, 'brand' => $brand, 'region' => $region, 'q' => $q );
}

/** Initial visibility of a card for the requested region/product/brand. */
function gt_stockist_is_visible( array $s, array $pre ) {
	$is_uk = 'GB' === $s['country'];
	if ( ( 'uk' === $pre['region'] ) !== $is_uk ) {
		return false;
	}
	if ( $pre['product'] && ! in_array( $pre['product'], wp_list_pluck( $s['products'], 'slug' ), true ) ) {
		return false;
	}
	if ( $pre['brand'] && ! in_array( $pre['brand'], $s['brands'], true ) ) {
		return false;
	}
	if ( '' !== $pre['q'] ) {
		$q = mb_strtolower( $pre['q'] );
		// Also compare with the spaces taken out, so "TA11NL" finds a stockist
		// whose postcode is stored as "TA1 1NL".
		$compact = function ( $value ) {
			return preg_replace( '/\s+/u', '', $value );
		};
		if ( false === strpos( $s['search'], $q )
			&& false === strpos( $compact( $s['search'] ), $compact( $q ) ) ) {
			return false;
		}
	}
	return true;
}

/** Front-end script (+ Google Maps when a key is set) on the finder template only. */
function gt_stockists_enqueue() {
	if ( ! is_page_template( 'page-templates/page-stockists.php' ) ) {
		return;
	}
	$key = gt_maps_key();
	wp_enqueue_script( 'gt-stockists', get_template_directory_uri() . '/assets/js/stockists.js', array(), gt_asset_version( '/assets/js/stockists.js' ), true );
	// wp_localize_script() would stringify hasKey ("" / "1"); Task 5 wants a real boolean, so build the
	// object ourselves and hand it to the script as an inline var instead.
	$data = array(
		'geocodeUrl' => rest_url( 'gt/v1/geocode' ),
		'hasKey'     => '' !== $key,
		'pin'        => 'data:image/svg+xml;utf8,' . rawurlencode( '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="24.75" viewBox="0 0 18 24.75"><path fill="#000" d="M0 8.84063C0 3.95625 4.03125 0 9 0C13.9688 0 18 3.95625 18 8.84063C18 15.9094 9 24.75 9 24.75C9 24.75 0 15.9094 0 8.84063ZM9 12C9.79565 12 10.5587 11.6839 11.1213 11.1213C11.6839 10.5587 12 9.79565 12 9C12 8.20435 11.6839 7.44129 11.1213 6.87868C10.5587 6.31607 9.79565 6 9 6C8.20435 6 7.44129 6.31607 6.87868 6.87868C6.31607 7.44129 6 8.20435 6 9C6 9.79565 6.31607 10.5587 6.87868 11.1213C7.44129 11.6839 8.20435 12 9 12Z"/></svg>' ),
		'strings'    => array(
			/* translators: %d: number of stockists */
			'count'    => __( '%d stockists', 'gt' ),
			'one'      => __( '1 stockist', 'gt' ),
			'nearest'  => __( 'Showing stockists nearest to %s', 'gt' ),
			'showLess' => __( 'Show less', 'gt' ),
			'more'     => __( '+%d more', 'gt' ),
		),
	);
	wp_add_inline_script( 'gt-stockists', 'var gtStockists = ' . wp_json_encode( $data ) . ';', 'before' );
	if ( '' !== $key ) {
		wp_enqueue_script(
			'google-maps',
			add_query_arg( array( 'key' => rawurlencode( $key ), 'callback' => 'gtStockistsMapReady', 'loading' => 'async' ), 'https://maps.googleapis.com/maps/api/js' ),
			array( 'gt-stockists' ),
			null,
			array( 'in_footer' => true, 'strategy' => 'async' )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'gt_stockists_enqueue' );
