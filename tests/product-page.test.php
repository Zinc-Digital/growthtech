<?php
require_once __DIR__ . '/lib/bootstrap.php';

$mist_id = wc_get_product_id_by_sku( 'GT-001' );
$html    = gt_fetch( '/product/clonex-mist/' );

gt_assert_contains( 'class="page-wrapper product-page"', $html, 'theme single template renders' );
gt_assert_contains( 'shop-crumbs__current">Clonex Mist<', $html, 'breadcrumb ends with the product' );
gt_assert_contains( '>Propagation</a>', $html, 'breadcrumb has the category' );
gt_assert_contains( '>Clonex</a>', $html, 'breadcrumb has the brand' );

// Gallery
gt_assert_contains( 'data-product-gallery', $html, 'gallery root' );
gt_assert_equal( 3, substr_count( $html, 'class="product-gallery__slide"' ), 'three slides (featured + 2 gallery)' );
gt_assert_equal( 3, substr_count( $html, 'data-gallery-thumb="' ), 'three thumbnails' );
gt_assert_contains( 'data-gallery-zoom', $html, 'zoom button' );
gt_assert_contains( 'data-gallery-lightbox', $html, 'lightbox container' );
gt_assert_contains( 'data-full="', $html, 'slides carry the full-size url' );

// Summary
gt_assert_contains( '<h1 class="product-summary__title">Clonex Mist</h1>', $html, 'title' );
gt_assert_contains( 'product-summary__brand', $html, 'brand mark' );
gt_assert_contains( '--brand-accent: #FBC707', $html, 'brand accent variable on the page' );
gt_assert_contains( 'a short description that appears under the product title', $html, 'short description rendered' );
gt_assert_equal( 4, substr_count( $html, 'class="product-summary__feature"' ), 'four feature bullets' );
gt_assert_contains( '<strong>Direct foliar absorption</strong>', $html, 'feature lead is bold' );
gt_assert_equal( 3, substr_count( $html, 'class="product-sizes__chip' ), 'three size chips' );
gt_assert_not_contains( 'product-sizes__chip is-selected', $html, 'no chip is selected in enquiry mode' );
gt_assert_contains( '<li class="product-sizes__chip">100ml</li>', $html, 'chips render plain' );
gt_assert_contains( 'product-summary__stockist', $html, 'stockist CTA present' );
gt_assert_contains( 'product=clonex-mist&#038;region=uk', $html, 'stockist CTA carries the product slug + region' );
gt_assert_contains( 'product-summary__experts', $html, 'experts link present' );
gt_assert_contains( '>Ask our experts<', $html, 'experts link label' );
gt_assert_equal( 2, substr_count( $html, 'class="product-downloads__link"' ), 'two downloads' );
gt_assert_contains( 'Safety Data Sheet (PDF)', $html, 'download label' );
gt_assert_equal( 3, substr_count( $html, 'class="product-badges__item"' ), 'three badges' );
gt_assert_contains( 'Made in Somerset', $html, 'badge label' );
gt_assert_not_contains( 'add_to_cart_button', $html, 'no add to cart in enquiry mode' );
gt_assert_not_contains( 'single_add_to_cart_button', $html, 'no single add to cart in enquiry mode' );
gt_assert_not_contains( 'woocommerce-Price-amount', $html, 'no price in enquiry mode' );
gt_assert_not_contains( 'woocommerce-product-details__short-description', $html, 'WC default excerpt markup not duplicated' );
gt_assert_contains( 'site-footer__club', $html, 'join the growth club band shows' );

// The per-page "hide join club" toggle removes the footer band.
try {
	update_field( 'field_gt_hide_club', 1, $mist_id );
	$html = gt_fetch( '/product/clonex-mist/' );
	gt_assert_not_contains( 'site-footer__club', $html, 'join the growth club band hides when the page toggle is on' );
} finally {
	update_field( 'field_gt_hide_club', 0, $mist_id );
}

// A product with no extras renders the essentials only.
$html = gt_fetch( '/product/budget-propagator/' );
gt_assert_contains( '<h1 class="product-summary__title">Budget Propagator</h1>', $html, 'plain product title' );
gt_assert_not_contains( 'product-summary__features', $html, 'no feature list when empty' );
gt_assert_not_contains( 'product-sizes', $html, 'no sizes block when the product has none' );
gt_assert_not_contains( 'product-downloads', $html, 'no downloads block when empty' );
gt_assert_not_contains( 'product-badges', $html, 'no badges block when empty' );
gt_assert_equal( 1, substr_count( $html, 'class="product-gallery__slide"' ), 'single image → one slide' );
gt_assert_not_contains( 'data-gallery-thumb=', $html, 'single image → no thumbnails' );
gt_assert_not_contains( 'data-gallery-arrows', $html, 'single image → no arrows target' );

gt_test_done();
