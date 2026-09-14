<?php
require_once __DIR__ . '/lib/bootstrap.php';

$js_path = get_template_directory() . '/assets/js/stockists.js';
gt_assert( file_exists( $js_path ), 'stockists.js exists' );
$js = file_get_contents( $js_path );
foreach ( array( 'data-stockists', 'data-stockists-region', 'data-stockists-country', 'data-stockists-search', 'data-stockists-product', 'data-stockists-sort', 'data-stockists-count', 'data-stockists-cards', 'data-stockists-empty', 'data-stockists-note', 'data-card-map', 'data-card-more', 'data-stockists-canvas', 'data-map-zoom', 'gtStockistsMapReady', 'replaceState', 'haversine', 'fitBounds', 'geocodeUrl' ) as $needle ) {
	gt_assert_contains( $needle, $js, "script handles {$needle}" );
}
$html = gt_fetch( '/find-a-stockist/' );
gt_assert_contains( 'var gtStockists = ', $html, 'settings localised' );
// Derived from the configured key so this stays green once the user sets one, rather than hard-coding "no key".
$expect_has_key = gt_maps_key() ? '"hasKey":true' : '"hasKey":false';
gt_assert_contains( $expect_has_key, $html, 'hasKey matches the configured key' );
gt_assert_contains( '"geocodeUrl":"', $html, 'geocode url present' );
gt_assert_not_contains( 'assets/js/stockists.js', gt_fetch( '/shop/' ), 'not enqueued elsewhere' );

gt_test_done();
