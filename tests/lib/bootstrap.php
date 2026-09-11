<?php
/**
 * Tiny assertion helpers for WP-CLI `eval-file` tests. WordPress is already
 * loaded when these run. Each test file: require this, assert, call
 * gt_test_done().
 */

$GLOBALS['gt_test_failures'] = 0;
$GLOBALS['gt_test_count']    = 0;

function gt_assert( $condition, $message ) {
	$GLOBALS['gt_test_count']++;
	if ( $condition ) {
		WP_CLI::log( '  ok   ' . $message );
	} else {
		$GLOBALS['gt_test_failures']++;
		WP_CLI::warning( 'FAIL ' . $message );
	}
}

function gt_assert_equal( $expected, $actual, $message ) {
	gt_assert(
		$expected === $actual,
		$message . ' (expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . ')'
	);
}

function gt_assert_contains( $needle, $haystack, $message ) {
	gt_assert( false !== strpos( (string) $haystack, $needle ), $message . ' (looking for "' . $needle . '")' );
}

function gt_assert_not_contains( $needle, $haystack, $message ) {
	gt_assert( false === strpos( (string) $haystack, $needle ), $message . ' (must not contain "' . $needle . '")' );
}

/** GET a front-end path over HTTPS (self-signed cert) and return the body. */
function gt_fetch( $path ) {
	$response = wp_remote_get( home_url( $path ), array( 'sslverify' => false, 'timeout' => 60 ) );
	if ( is_wp_error( $response ) ) {
		WP_CLI::warning( 'fetch failed: ' . $response->get_error_message() );
		return '';
	}
	return (string) wp_remote_retrieve_body( $response );
}

function gt_test_done() {
	if ( $GLOBALS['gt_test_failures'] ) {
		WP_CLI::error( $GLOBALS['gt_test_failures'] . ' assertion(s) failed' );
	}
	WP_CLI::success( $GLOBALS['gt_test_count'] . ' assertions passed' );
}
