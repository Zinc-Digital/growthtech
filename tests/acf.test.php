<?php
require_once __DIR__ . '/lib/bootstrap.php';

foreach ( array( 'group_gt_brand', 'group_gt_product_category', 'group_gt_shop_settings' ) as $key ) {
	$group = acf_get_field_group( $key );
	gt_assert( ! empty( $group ), "field group {$key} is registered from acf-json" );
}

$brand_fields = wp_list_pluck( acf_get_fields( 'group_gt_brand' ) ?: array(), 'name' );
foreach ( array( 'logo', 'accent_colour', 'own_brand', 'promo_enabled', 'promo_image', 'promo_tagline', 'promo_position' ) as $name ) {
	gt_assert( in_array( $name, $brand_fields, true ), "brand group has field {$name}" );
}

$cat_fields = wp_list_pluck( acf_get_fields( 'group_gt_product_category' ) ?: array(), 'name' );
foreach ( array( 'hero_image', 'hero_heading', 'hero_text' ) as $name ) {
	gt_assert( in_array( $name, $cat_fields, true ), "category group has field {$name}" );
}

$shop_fields = wp_list_pluck( acf_get_fields( 'group_gt_shop_settings' ) ?: array(), 'name' );
foreach ( array( 'shop_products_per_page', 'shop_stockist_page', 'shop_experts_link', 'shop_google_maps_key' ) as $name ) {
	gt_assert( in_array( $name, $shop_fields, true ), "shop settings group has field {$name}" );
}

gt_test_done();
