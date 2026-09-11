<?php
require_once __DIR__ . '/lib/bootstrap.php';

$html = gt_fetch( '/shop/' );
gt_assert_contains( 'assets/js/shop-filters.js', $html, 'shop-filters.js enqueued on the shop' );
gt_assert_contains( 'var gtShop = ', $html, 'gtShop settings localised' );
gt_assert_contains( '"ajaxUrl":"', $html, 'ajaxUrl present' );

$html = gt_fetch( '/product-category/propagation/' );
gt_assert_contains( 'assets/js/shop-filters.js', $html, 'shop-filters.js enqueued on a category' );

$html = gt_fetch( '/' );
gt_assert_not_contains( 'assets/js/shop-filters.js', $html, 'not enqueued elsewhere' );

gt_assert( file_exists( get_template_directory() . '/assets/js/shop-filters.js' ), 'script file exists' );
$js = file_get_contents( get_template_directory() . '/assets/js/shop-filters.js' );
foreach ( array( 'data-shop-form', 'data-shop-grid', 'data-shop-sidebar', 'data-shop-count', 'data-shop-more', 'data-shop-sort', 'data-shop-filters-toggle', 'data-shop-group-toggle', 'pushState', 'popstate', 'aria-busy' ) as $needle ) {
	gt_assert_contains( $needle, $js, "script handles {$needle}" );
}

gt_test_done();
