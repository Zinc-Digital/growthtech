<?php
require_once __DIR__ . '/lib/bootstrap.php';

if ( ! function_exists( 'gt_find_stockist_by_title' ) ) {
	function gt_find_stockist_by_title( $title ) {
		$posts = get_posts( array( 'post_type' => 'stockist', 'post_status' => 'any', 'title' => $title, 'posts_per_page' => 1 ) );
		return $posts ? $posts[0] : null;
	}
}

$all = get_posts( array( 'post_type' => 'stockist', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
gt_assert_equal( 8, count( $all ), 'eight stockists seeded' );

$somerset = gt_find_stockist_by_title( 'Somerset Hydro Centre' );
gt_assert( $somerset instanceof WP_Post, 'Somerset Hydro Centre exists' );
gt_assert_equal( 'GB', get_field( 'country', $somerset->ID ), 'country code' );
gt_assert_equal( 51.0153, (float) get_field( 'lat', $somerset->ID ), 'coordinates seeded' );
gt_assert_equal( 'manual', get_field( 'geocode_status', $somerset->ID ), 'seeded coordinates are marked manual' );
gt_assert_equal( 7, count( (array) get_field( 'products', $somerset->ID ) ), 'Somerset stocks 7 products' );
gt_assert( in_array( wc_get_product_id_by_sku( 'GT-001' ), array_map( 'intval', (array) get_field( 'products', $somerset->ID ) ), true ), 'Somerset stocks Clonex Mist' );
gt_assert_equal( array( 'Hydroponics specialist' ), wp_get_post_terms( $somerset->ID, 'stockist_type', array( 'fields' => 'names' ) ), 'type term' );
gt_assert_contains( '01823', get_field( 'phone', $somerset->ID ), 'phone' );
gt_assert_contains( 'https://', get_field( 'website', $somerset->ID ), 'website' );

$edinburgh = gt_find_stockist_by_title( 'Edinburgh Grow' );
gt_assert_equal( '', (string) get_field( 'website', $edinburgh->ID ), 'Edinburgh has no website (tel fallback case)' );

$intl = array_filter( $all, function ( $p ) { return 'GB' !== get_field( 'country', $p->ID ); } );
gt_assert_equal( 2, count( $intl ), 'two international stockists' );

$page = get_page_by_path( 'find-a-stockist' );
gt_assert_equal( 'page-templates/page-stockists.php', get_page_template_slug( $page->ID ), 'stockist page uses the template' );
gt_assert_contains( 'independent specialists', (string) get_field( 'intro', $page->ID ), 'intro seeded' );
gt_assert_equal( 'Run a store? Stock the originals.', get_field( 'trade_heading', $page->ID ), 'trade heading' );
gt_assert( (int) get_field( 'trade_image', $page->ID ) > 0, 'trade image' );
gt_assert_contains( 'Contact our trade team', get_field( 'trade_link', $page->ID )['title'], 'trade link' );

gt_test_done();
