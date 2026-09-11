<?php
require_once __DIR__ . '/lib/bootstrap.php';

gt_assert( class_exists( 'WooCommerce' ), 'WooCommerce is active' );
gt_assert( function_exists( 'get_field' ), 'ACF is active' );
gt_assert_equal( 'growth_tech', wp_get_theme()->get_stylesheet(), 'growth_tech theme is active' );
gt_assert_equal( '/%postname%/', get_option( 'permalink_structure' ), 'pretty permalinks are on' );
gt_assert_contains( '<html', gt_fetch( '/shop/' ), 'shop page responds with HTML' );

gt_test_done();
