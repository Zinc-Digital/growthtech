<?php
require_once __DIR__ . '/lib/bootstrap.php';

gt_assert( post_type_exists( 'stockist' ), 'stockist post type registered' );
$pt = get_post_type_object( 'stockist' );
gt_assert_equal( false, $pt->public, 'stockist is not public' );
gt_assert_equal( true, $pt->show_ui, 'stockist has admin UI' );
gt_assert_equal( false, $pt->publicly_queryable, 'no front-end single' );
gt_assert_equal( false, $pt->show_in_rest, 'stockist not exposed over REST' );
gt_assert( taxonomy_exists( 'stockist_type' ), 'stockist_type taxonomy registered' );
gt_assert( in_array( 'stockist', (array) get_taxonomy( 'stockist_type' )->object_type, true ), 'stockist_type attaches to stockists' );
gt_assert_equal( false, get_taxonomy( 'stockist_type' )->show_in_rest, 'stockist_type not exposed over REST' );

gt_assert( ! empty( acf_get_field_group( 'group_gt_stockist' ) ), 'stockist field group registered' );
$names = wp_list_pluck( acf_get_fields( 'group_gt_stockist' ) ?: array(), 'name' );
foreach ( array( 'address_1', 'address_2', 'town', 'region', 'postcode', 'country', 'phone', 'website', 'email', 'products', 'lat', 'lng', 'geocode_status', 'geocoded_address' ) as $name ) {
	gt_assert( in_array( $name, $names, true ), "stockist group has {$name}" );
}
gt_assert( ! empty( acf_get_field_group( 'group_gt_stockists_page' ) ), 'stockists page group registered' );
$page_names = wp_list_pluck( acf_get_fields( 'group_gt_stockists_page' ) ?: array(), 'name' );
foreach ( array( 'intro', 'trade_heading', 'trade_text', 'trade_image', 'trade_link' ) as $name ) {
	gt_assert( in_array( $name, $page_names, true ), "stockists page group has {$name}" );
}

$countries = gt_stockist_countries();
gt_assert_equal( 'United Kingdom (UK)', $countries['GB'], 'country names come from WooCommerce' );
gt_assert( count( $countries ) > 100, 'full country list' );

$field = acf_get_field( 'field_gt_stockist_country' );
$field = apply_filters( 'acf/load_field', $field );
gt_assert( isset( $field['choices']['GB'] ), 'country select choices populated from WooCommerce' );

// Address string helper on a throwaway post.
$id = wp_insert_post( array( 'post_type' => 'stockist', 'post_status' => 'draft', 'post_title' => 'Test Stockist' ) );
try {
	update_field( 'field_gt_stockist_address_1', '1 High Street', $id );
	update_field( 'field_gt_stockist_town', 'Taunton', $id );
	update_field( 'field_gt_stockist_postcode', 'TA1 1AA', $id );
	update_field( 'field_gt_stockist_country', 'GB', $id );
	gt_assert_equal( '1 High Street, Taunton, TA1 1AA, United Kingdom (UK)', gt_stockist_address_string( $id ), 'address string skips empty parts and expands the country' );
} finally {
	wp_delete_post( $id, true );
}

$columns = apply_filters( 'manage_stockist_posts_columns', array( 'title' => 'Title', 'date' => 'Date' ) );
gt_assert( isset( $columns['geocode'] ), 'admin list has a Geocode column' );

gt_test_done();
