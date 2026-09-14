<?php
require_once __DIR__ . '/lib/bootstrap.php';

$html = gt_fetch( '/product/clonex-mist/' );
gt_assert_contains( 'class="product-related__title">Complete the system<', $html, 'related heading' );
$start = strpos( $html, 'class="product-related' );
$end   = strpos( $html, 'class="knowledge-band', $start );
$block = substr( $html, $start, $end - $start );
gt_assert_equal( 3, substr_count( $block, '<li class="product-card ' ), 'three same-brand products' );
gt_assert_not_contains( '>Clonex Mist</h2>', $block, 'current product excluded' );
gt_assert_contains( 'href="' . wc_get_page_permalink( 'shop' ) . '"', $block, 'View all Products links to the shop' );
gt_assert_not_contains( 'data-block-slider', $block, 'four or fewer → static grid, no slider' );
gt_assert_not_contains( 'data-slider-arrows', $block, 'no arrows without a slider' );

gt_assert_contains( 'class="knowledge-band"', $html, 'knowledge band rendered' );
gt_assert_contains( 'knowledge-band__title">Better knowledge. Stronger roots.<', $html, 'knowledge heading from Theme Settings' );
gt_assert_contains( 'Explore the Plant Academy', $html, 'knowledge button label' );

$html = gt_fetch( '/product/root-riot/' );
$start = strpos( $html, 'class="product-related' );
$block = substr( $html, $start, 6000 );
gt_assert_contains( '>Clonex Mist</h2>', $block, 'upsells drive related when set' );
gt_assert_contains( '>Clonex Rooting Hormone</h2>', $block, 'second upsell' );
gt_assert_equal( 2, substr_count( $block, '<li class="product-card ' ), 'only the upsells' );

// Slider mode with more than four items (rendered directly with an override).
$products = wc_get_products( array( 'limit' => 5, 'status' => 'publish', 'orderby' => 'ID', 'order' => 'ASC' ) );
$GLOBALS['product'] = $products[0];
ob_start();
get_template_part( 'template-parts/shop/complete-system', null, array( 'product' => $products[0], 'items' => $products ) );
$part = ob_get_clean();
gt_assert_contains( 'data-block-slider', $part, 'five items → slider' );
gt_assert_contains( 'data-slides="4"', $part, 'slider shows four at desktop' );
gt_assert_contains( 'data-slider-arrows', $part, 'arrows target in the header' );
gt_assert_equal( 5, substr_count( $part, '<li class="product-card ' ), 'all five rendered' );

// No related products → section absent.
$smc = wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'sku' => 'GT-013' ) )[0];
ob_start();
get_template_part( 'template-parts/shop/complete-system', null, array( 'product' => $smc ) );
gt_assert_equal( '', trim( ob_get_clean() ), 'Nitrozyme (alone in its brand, no upsells) renders no section' );

gt_test_done();
