<?php
require_once __DIR__ . '/lib/bootstrap.php';

$html = gt_fetch( '/product/clonex-mist/' );
gt_assert_contains( 'assets/js/product-gallery.js', $html, 'gallery script enqueued on products' );
gt_assert_not_contains( 'photoswipe', $html, 'WooCommerce PhotoSwipe not loaded' );
gt_assert_not_contains( 'flexslider', $html, 'WooCommerce FlexSlider not loaded' );
gt_assert_not_contains( 'zoom.min.js', $html, 'WooCommerce zoom not loaded' );
gt_assert_contains( 'assets/js/slick.min.js', $html, 'slick available for the gallery' );

gt_assert_not_contains( 'assets/js/product-gallery.js', gt_fetch( '/shop/' ), 'gallery script not enqueued elsewhere' );

$js = file_get_contents( get_template_directory() . '/assets/js/product-gallery.js' );
foreach ( array( 'data-product-gallery', 'data-gallery-slides', 'data-gallery-thumb', 'data-gallery-zoom', 'data-gallery-arrows', 'data-gallery-lightbox', 'data-lightbox-img', 'data-lightbox-close', 'Escape', 'slickGoTo', 'product-lightbox-open' ) as $needle ) {
	gt_assert_contains( $needle, $js, "script handles {$needle}" );
}

gt_test_done();
