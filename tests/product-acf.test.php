<?php
require_once __DIR__ . '/lib/bootstrap.php';

gt_assert( ! empty( acf_get_field_group( 'group_gt_product_details' ) ), 'product details group registered' );
$pd = wp_list_pluck( acf_get_fields( 'group_gt_product_details' ) ?: array(), 'name' );
foreach ( array( 'features', 'downloads', 'science', 'how_to_use', 'specification', 'documents', 'knowledge_override' ) as $name ) {
	gt_assert( in_array( $name, $pd, true ), "product details has {$name}" );
}

$brand = wp_list_pluck( acf_get_fields( 'group_gt_brand' ) ?: array(), 'name' );
foreach ( array( 'hero_heading', 'hero_accent_line', 'hero_intro', 'hero_image', 'science_heading', 'science_accent_line', 'science_text', 'steps', 'faq', 'pair_brand', 'pair_heading', 'pair_text', 'pair_image', 'pair_link', 'guides_heading', 'guides_accent_line', 'guides_text', 'guides_cta_1', 'guides_cta_2', 'guides' ) as $name ) {
	gt_assert( in_array( $name, $brand, true ), "brand group has {$name}" );
}

$shop = wp_list_pluck( acf_get_fields( 'group_gt_shop_settings' ) ?: array(), 'name' );
foreach ( array( 'shop_knowledge_heading', 'shop_knowledge_text', 'shop_knowledge_image', 'shop_knowledge_link' ) as $name ) {
	gt_assert( in_array( $name, $shop, true ), "shop settings has {$name}" );
}

gt_assert( taxonomy_exists( 'product_badge' ), 'product_badge taxonomy registered' );
gt_assert( in_array( 'product', (array) get_taxonomy( 'product_badge' )->object_type, true ), 'product_badge attaches to products' );
gt_assert_equal( false, get_taxonomy( 'product_badge' )->publicly_queryable, 'product_badge has no front-end archive' );

$locations = acf_get_field_group( 'group_gt_page_settings' )['location'];
$has_product = false;
foreach ( $locations as $group ) {
	foreach ( $group as $rule ) {
		if ( 'post_type' === $rule['param'] && 'product' === $rule['value'] ) {
			$has_product = true;
		}
	}
}
gt_assert( $has_product, 'hide_join_club is available on products' );

gt_test_done();
