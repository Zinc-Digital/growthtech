<?php
require_once __DIR__ . '/lib/bootstrap.php';

$google_ok = function ( $pre, $args, $url ) {
	if ( false === strpos( $url, 'maps.googleapis.com/maps/api/geocode' ) ) {
		return $pre;
	}
	$GLOBALS['gt_geocode_calls'][] = $url;
	$body = ( false !== strpos( $url, 'Nowhere' ) )
		? array( 'status' => 'ZERO_RESULTS', 'results' => array() )
		: array( 'status' => 'OK', 'results' => array( array( 'formatted_address' => 'Taunton, UK', 'geometry' => array( 'location' => array( 'lat' => 51.0153, 'lng' => -3.1069 ) ) ) ) );
	return array( 'response' => array( 'code' => 200, 'message' => 'OK' ), 'body' => wp_json_encode( $body ), 'headers' => array(), 'cookies' => array(), 'filename' => null );
};

$saved_key = get_field( 'shop_google_maps_key', 'option' );
$GLOBALS['gt_geocode_calls'] = array();
add_filter( 'pre_http_request', $google_ok, 10, 3 );
delete_transient( 'gt_geocode_' . md5( 'gb|Taunton, UK' ) );
delete_transient( 'gt_geocode_' . md5( '|Nowhere' ) );

try {
	// No key.
	update_field( 'field_gt_shop_maps_key', '', 'option' );
	gt_assert_equal( '', gt_maps_key(), 'no key by default' );
	$result = gt_stockist_geocode( 'Taunton, UK', 'gb' );
	gt_assert( is_wp_error( $result ) && 'no_key' === $result->get_error_code(), 'geocode returns no_key without a key' );
	$request  = new WP_REST_Request( 'GET', '/gt/v1/geocode' );
	$request->set_query_params( array( 'q' => 'Taunton', 'region' => 'gb' ) );
	$response = rest_do_request( $request );
	gt_assert_equal( 503, $response->get_status(), 'proxy is 503 without a key' );
	gt_assert_equal( 'no_key', $response->get_data()['code'], 'proxy names the reason' );

	// With a (fake) key and stubbed Google.
	update_field( 'field_gt_shop_maps_key', 'TEST-KEY', 'option' );
	gt_assert_equal( 'TEST-KEY', gt_maps_key(), 'key read from Theme Settings' );
	$result = gt_stockist_geocode( 'Taunton, UK', 'gb' );
	gt_assert( is_array( $result ) && 51.0153 === $result['lat'] && -3.1069 === $result['lng'], 'geocode parses Google\'s response' );
	gt_assert_equal( 'Taunton, UK', $result['label'], 'geocode label' );
	gt_assert_contains( 'key=TEST-KEY', end( $GLOBALS['gt_geocode_calls'] ), 'request carries the key' );
	gt_assert_contains( 'region=gb', end( $GLOBALS['gt_geocode_calls'] ), 'request carries the region bias' );
	$calls = count( $GLOBALS['gt_geocode_calls'] );
	gt_stockist_geocode( 'Taunton, UK', 'gb' );
	gt_assert_equal( $calls, count( $GLOBALS['gt_geocode_calls'] ), 'second lookup is served from the transient cache' );
	$miss = gt_stockist_geocode( 'Nowhere' );
	gt_assert( is_wp_error( $miss ) && 'zero_results' === $miss->get_error_code(), 'ZERO_RESULTS becomes a zero_results error' );
	$calls = count( $GLOBALS['gt_geocode_calls'] );
	gt_stockist_geocode( 'Nowhere' );
	gt_assert_equal( $calls, count( $GLOBALS['gt_geocode_calls'] ), 'zero_results is negatively cached and not looked up again' );

	$request = new WP_REST_Request( 'GET', '/gt/v1/geocode' );
	$request->set_query_params( array( 'q' => 'Taunton, UK', 'region' => 'gb' ) );
	$response = rest_do_request( $request );
	gt_assert_equal( 200, $response->get_status(), 'proxy 200 with a key' );
	gt_assert_equal( 51.0153, $response->get_data()['lat'], 'proxy returns lat' );
	$request = new WP_REST_Request( 'GET', '/gt/v1/geocode' );
	$request->set_query_params( array( 'q' => 'T' ) );
	gt_assert_equal( 400, rest_do_request( $request )->get_status(), 'proxy rejects short queries' );
	$request = new WP_REST_Request( 'GET', '/gt/v1/geocode' );
	$request->set_query_params( array( 'q' => 'Nowhere' ) );
	gt_assert_equal( 404, rest_do_request( $request )->get_status(), 'proxy 404 on zero results' );

	// Rate limiting.
	$rl_ip = '203.0.113.9';
	$rl_ok = true;
	for ( $i = 0; $i < 30; $i++ ) {
		if ( gt_stockist_rate_limited( $rl_ip ) ) {
			$rl_ok = false;
		}
	}
	gt_assert( $rl_ok, 'rate limit allows 30 requests inside the window' );
	gt_assert( gt_stockist_rate_limited( $rl_ip ), 'the 31st request in the window is rate limited' );

	// The window is fixed, not a refreshing accumulator: an expired window
	// resets rather than staying tripped, and a live one doesn't get pushed
	// back by every call.
	$rl_key2 = 'gt_geocode_rl_' . md5( $rl_ip );
	set_transient( $rl_key2, array( 'count' => 31, 'reset' => time() - 1 ), 60 );
	try {
		gt_assert( ! gt_stockist_rate_limited( $rl_ip ), 'an expired rate-limit window is recreated, not treated as still over limit' );
		$tripped = false;
		for ( $i = 0; $i < 30; $i++ ) {
			$tripped = gt_stockist_rate_limited( $rl_ip );
		}
		gt_assert( $tripped, 'the fixed window still trips after 31 requests without the window itself being extended' );
	} finally {
		delete_transient( $rl_key2 );
	}

	// Save hook.
	$id = wp_insert_post( array( 'post_type' => 'stockist', 'post_status' => 'publish', 'post_title' => 'Geocode Test' ) );
	try {
		update_field( 'field_gt_stockist_town', 'Taunton', $id );
		update_field( 'field_gt_stockist_country', 'GB', $id );
		gt_stockist_maybe_geocode( $id );
		gt_assert_equal( 'ok', get_field( 'geocode_status', $id ), 'save hook geocodes a new stockist' );
		gt_assert_equal( 51.0153, (float) get_field( 'lat', $id ), 'lat stored' );
		gt_assert_equal( gt_stockist_address_string( $id ), get_field( 'geocoded_address', $id ), 'geocoded address recorded' );

		$calls = count( $GLOBALS['gt_geocode_calls'] );
		gt_stockist_maybe_geocode( $id );
		gt_assert_equal( $calls, count( $GLOBALS['gt_geocode_calls'] ), 'unchanged address is not looked up again' );

		update_field( 'field_gt_stockist_lat', 50.0, $id );
		update_field( 'field_gt_stockist_lng', -1.0, $id );
		update_field( 'field_gt_stockist_geocoded_address', '', $id );
		gt_stockist_maybe_geocode( $id );
		gt_assert_equal( 'manual', get_field( 'geocode_status', $id ), 'typed coordinates are kept as manual' );
		gt_assert_equal( 50.0, (float) get_field( 'lat', $id ), 'manual lat untouched' );

		update_field( 'field_gt_shop_maps_key', '', 'option' );
		update_field( 'field_gt_stockist_lat', '', $id );
		update_field( 'field_gt_stockist_lng', '', $id );
		gt_stockist_maybe_geocode( $id );
		gt_assert_equal( 'no_key', get_field( 'geocode_status', $id ), 'without a key the status says so' );
	} finally {
		delete_transient( 'gt_geocode_' . md5( 'gb|' . gt_stockist_address_string( $id ) ) );
		wp_delete_post( $id, true );
	}
} finally {
	remove_filter( 'pre_http_request', $google_ok, 10 );
	update_field( 'field_gt_shop_maps_key', $saved_key ? $saved_key : '', 'option' );
	delete_transient( 'gt_geocode_' . md5( 'gb|Taunton, UK' ) );
	delete_transient( 'gt_geocode_' . md5( '|Nowhere' ) );
	delete_transient( 'gt_geocode_rl_' . md5( '203.0.113.9' ) );
}

gt_test_done();
