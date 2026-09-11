<?php
require_once __DIR__ . '/lib/bootstrap.php';

gt_assert( current_theme_supports( 'woocommerce' ), 'theme declares woocommerce support' );
gt_assert( defined( 'GT_SHOP_ENQUIRY_MODE' ) && GT_SHOP_ENQUIRY_MODE === true, 'GT_SHOP_ENQUIRY_MODE is true' );

gt_assert_equal( array(), apply_filters( 'woocommerce_enqueue_styles', array( 'x' => 1 ) ), 'WooCommerce front-end styles are dequeued' );

gt_assert_equal( false, has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price' ), 'single price hook removed in enquiry mode' );
gt_assert_equal( false, has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart' ), 'single add-to-cart hook removed in enquiry mode' );
gt_assert_equal( false, has_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' ), 'loop add-to-cart hook removed' );
gt_assert_equal( false, has_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price' ), 'loop price hook removed' );
gt_assert_equal( false, has_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb' ), 'WC breadcrumb hook removed' );
gt_assert_equal( false, has_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar' ), 'WC sidebar hook removed' );
gt_assert_equal( false, has_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering' ), 'WC ordering dropdown hook removed' );
gt_assert_equal( false, has_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count' ), 'WC result count hook removed' );

gt_assert_equal(
	array( 'brands' => 'Our brands first', 'title' => 'A - Z', 'title-desc' => 'Z - A', 'date' => 'Newest' ),
	apply_filters( 'woocommerce_catalog_orderby', array( 'price' => 'x' ) ),
	'sort options replaced (no price sorts in enquiry mode)'
);
gt_assert_equal( 'brands', apply_filters( 'woocommerce_default_catalog_orderby', 'menu_order' ), 'default sort is Our brands first' );
gt_assert_equal( 12, gt_shop_per_page(), 'products per page defaults to 12' );
gt_assert_equal( 12, apply_filters( 'loop_shop_per_page', 16 ), 'loop_shop_per_page uses the setting' );

$own = gt_shop_own_brand_term_ids();
$clonex = get_term_by( 'slug', 'clonex', 'product_brand' );
$nitro  = get_term_by( 'slug', 'nitrozyme', 'product_brand' );
gt_assert( in_array( (int) $clonex->term_id, $own, true ), 'Clonex is in the own-brand id list' );
gt_assert( ! in_array( (int) $nitro->term_id, $own, true ), 'Nitrozyme is not in the own-brand id list' );

foreach ( array( 'gt-product-card', 'gt-product-card-sm', 'gt-promo', 'gt-promo-sm', 'gt-category-hero', 'gt-category-hero-sm' ) as $size ) {
	gt_assert( has_image_size( $size ), "image size {$size} registered" );
}

gt_test_done();
