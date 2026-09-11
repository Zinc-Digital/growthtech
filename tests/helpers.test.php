<?php
require_once __DIR__ . '/lib/bootstrap.php';

$mist = wc_get_product( wc_get_product_id_by_sku( 'GT-001' ) );
$cat  = gt_product_primary_category( $mist );
gt_assert( $cat instanceof WP_Term && 'propagation' === $cat->slug, 'primary category of Clonex Mist is Propagation' );
gt_assert_equal( array( '100ml', '300ml', '750ml' ), gt_product_sizes( $mist ), 'sizes of Clonex Mist' );
$brand = gt_product_brand( $mist );
gt_assert( $brand instanceof WP_Term && 'clonex' === $brand->slug, 'brand of Clonex Mist is Clonex' );
gt_assert_equal( '#FBC707', gt_brand_accent( $brand ), 'Clonex accent colour' );

$propagator = wc_get_product( wc_get_product_id_by_sku( 'GT-006' ) );
gt_assert_equal( array(), gt_product_sizes( $propagator ), 'product without sizes returns empty array' );

gt_assert_equal( 'fallback', gt_term_field( 'nonexistent_field', $brand, 'fallback' ), 'gt_term_field falls back for missing fields' );
gt_assert_equal( true, (bool) gt_term_field( 'own_brand', $brand, false ), 'gt_term_field reads ACF term fields' );

gt_test_done();
