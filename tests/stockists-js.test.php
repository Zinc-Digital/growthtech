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
gt_assert_contains( '"hasKey":false', $html, 'hasKey false without a key' );
gt_assert_contains( '"geocodeUrl":"', $html, 'geocode url present' );
gt_assert_not_contains( 'assets/js/stockists.js', gt_fetch( '/shop/' ), 'not enqueued elsewhere' );

gt_test_done();
