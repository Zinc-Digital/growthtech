<?php
require_once __DIR__ . '/lib/bootstrap.php';

$cat = get_term_by( 'slug', 'propagation', 'product_cat' );
gt_assert( $cat instanceof WP_Term, 'Propagation category exists' );
gt_assert_equal( 7, (int) $cat->count, 'Propagation has 7 products' );
gt_assert_equal( 'Propagation - The science of the start.', get_field( 'hero_heading', $cat ), 'Propagation hero heading seeded' );

$clonex = get_term_by( 'slug', 'clonex', 'product_brand' );
gt_assert( $clonex instanceof WP_Term, 'Clonex brand exists' );
gt_assert_equal( 4, (int) $clonex->count, 'Clonex has 4 products' );
gt_assert_equal( true, (bool) get_field( 'own_brand', $clonex ), 'Clonex is an own brand' );
gt_assert_equal( true, (bool) get_field( 'promo_enabled', $clonex ), 'Clonex promo tile enabled' );
gt_assert_equal( 2, (int) get_field( 'promo_position', $clonex ), 'Clonex promo at position 2' );

$smc = get_term_by( 'slug', 'smc', 'product_brand' );
gt_assert( $smc instanceof WP_Term && 0 === (int) $smc->count, 'SMC brand exists with no products' );

gt_assert( taxonomy_exists( 'pa_size' ), 'pa_size attribute taxonomy exists' );
gt_assert( taxonomy_exists( 'pa_growing-medium' ), 'pa_growing-medium attribute taxonomy exists' );
gt_assert( taxonomy_exists( 'pa_growing-stage' ), 'pa_growing-stage attribute taxonomy exists' );

$mist = wc_get_product( wc_get_product_id_by_sku( 'GT-001' ) );
gt_assert( $mist instanceof WC_Product, 'Clonex Mist product exists' );
gt_assert_equal( array( '100ml', '300ml', '750ml' ), wc_get_product_terms( $mist->get_id(), 'pa_size', array( 'fields' => 'names' ) ), 'Clonex Mist sizes in order' );
gt_assert_equal( '', $mist->get_price(), 'Clonex Mist has no price (enquiry mode)' );

$count = new WP_Query( array( 'post_type' => 'product', 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => -1 ) );
gt_assert_equal( 13, (int) $count->found_posts, '13 products seeded' );

gt_assert_equal( 'Our Products', get_the_title( wc_get_page_id( 'shop' ) ), 'shop page is titled Our Products' );

gt_test_done();
