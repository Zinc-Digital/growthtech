<?php
/**
 * Seed stockists and the Find a Stockist page. Idempotent: stockists are
 * matched by title, the page by slug. Run after seed-shop.php and
 * seed-product-content.php.
 *
 * Run: tests/bin/wpx eval-file tests/seed/seed-stockists.php
 */

if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'update_field' ) ) {
	WP_CLI::error( 'WooCommerce and ACF must be active.' );
}
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

if ( ! function_exists( 'gt_seed_image' ) ) {
	function gt_seed_image( $slug, $label, $width, $height, array $rgb ) {
		$filename = 'gt-seed-' . sanitize_title( $slug ) . '.png';
		$existing = get_posts( array( 'post_type' => 'attachment', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_gt_seed_image', 'meta_value' => $filename ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $existing ) {
			return (int) $existing[0];
		}
		$img = imagecreatetruecolor( $width, $height );
		imagefill( $img, 0, 0, imagecolorallocate( $img, $rgb[0], $rgb[1], $rgb[2] ) );
		$fg = imagecolorallocate( $img, max( 0, $rgb[0] - 60 ), max( 0, $rgb[1] - 60 ), max( 0, $rgb[2] - 60 ) );
		imagefilledrectangle( $img, (int) ( $width * 0.3 ), (int) ( $height * 0.1 ), (int) ( $width * 0.7 ), (int) ( $height * 0.9 ), $fg );
		imagestring( $img, 5, 10, 10, $label, imagecolorallocate( $img, 255, 255, 255 ) );
		$tmp = wp_tempnam( $filename );
		imagepng( $img, $tmp );
		imagedestroy( $img );
		$id = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $tmp ), 0, $label );
		if ( is_wp_error( $id ) ) {
			WP_CLI::error( 'image ' . $filename . ': ' . $id->get_error_message() );
		}
		update_post_meta( $id, '_gt_seed_image', $filename );
		return (int) $id;
	}
}

if ( ! function_exists( 'gt_find_stockist_by_title' ) ) {
	function gt_find_stockist_by_title( $title ) {
		$posts = get_posts( array( 'post_type' => 'stockist', 'post_status' => 'any', 'title' => $title, 'posts_per_page' => 1 ) );
		return $posts ? $posts[0] : null;
	}
}

$sku = function ( $code ) {
	return (int) wc_get_product_id_by_sku( $code );
};

// name => [addr1, town, region, postcode, country, lat, lng, type, phone, website, skus]
$stockists = array(
	'Somerset Hydro Centre'  => array( '12 Station Road', 'Taunton', 'Somerset', 'TA1 1NL', 'GB', 51.0153, -3.1069, 'Hydroponics specialist', '01823 000 000', 'https://example.com/somerset-hydro', array( 'GT-001', 'GT-003', 'GT-002', 'GT-008', 'GT-009', 'GT-011', 'GT-013' ) ),
	'Bristol Grow Room'      => array( '5 Harbour Way', 'Bristol', '', 'BS1 5AA', 'GB', 51.4545, -2.5879, 'Hydroponics specialist', '0117 000 0000', 'https://example.com/bristol-grow', array( 'GT-001', 'GT-003', 'GT-008', 'GT-002', 'GT-004', 'GT-005' ) ),
	'Exe Valley Growshop'    => array( '8 Mill Lane', 'Exeter', 'Devon', 'EX1 1BB', 'GB', 50.7184, -3.5339, 'Grow shop', '01392 000 000', 'https://example.com/exe-valley', array( 'GT-003', 'GT-011', 'GT-013', 'GT-002', 'GT-006' ) ),
	'Urban Roots London'     => array( '221 Camden High Street', 'London', '', 'NW1 7BU', 'GB', 51.5074, -0.1278, 'Grow shop', '020 0000 0000', 'https://example.com/urban-roots', array( 'GT-001', 'GT-011', 'GT-013', 'GT-002', 'GT-007' ) ),
	'Manchester Hydro'       => array( '40 Deansgate', 'Manchester', '', 'M3 2EG', 'GB', 53.4808, -2.2426, 'Hydroponics specialist', '0161 000 0000', 'https://example.com/manchester-hydro', array( 'GT-008', 'GT-009', 'GT-010' ) ),
	'Edinburgh Grow'         => array( '3 Leith Walk', 'Edinburgh', '', 'EH6 8LN', 'GB', 55.9533, -3.1883, 'Grow shop', '0131 000 0000', '', array( 'GT-011', 'GT-013' ) ),
	'Dublin Hydro'           => array( '14 Dame Street', 'Dublin', '', 'D02 X285', 'IE', 53.3498, -6.2603, 'Hydroponics specialist', '+353 1 000 0000', 'https://example.com/dublin-hydro', array( 'GT-001', 'GT-003', 'GT-008', 'GT-012' ) ),
	'Amsterdam Grow Store'   => array( 'Prinsengracht 100', 'Amsterdam', '', '1015 EA', 'NL', 52.3676, 4.9041, 'Grow shop', '+31 20 000 0000', 'https://example.com/amsterdam-grow', array( 'GT-002', 'GT-003', 'GT-004' ) ),
);

foreach ( $stockists as $name => $s ) {
	$existing = gt_find_stockist_by_title( $name );
	$id       = $existing ? $existing->ID : wp_insert_post( array( 'post_type' => 'stockist', 'post_status' => 'publish', 'post_title' => $name ) );
	if ( ! $id || is_wp_error( $id ) ) {
		WP_CLI::error( "could not create {$name}" );
	}
	update_field( 'field_gt_stockist_address_1', $s[0], $id );
	update_field( 'field_gt_stockist_address_2', '', $id );
	update_field( 'field_gt_stockist_town', $s[1], $id );
	update_field( 'field_gt_stockist_region', $s[2], $id );
	update_field( 'field_gt_stockist_postcode', $s[3], $id );
	update_field( 'field_gt_stockist_country', $s[4], $id );
	update_field( 'field_gt_stockist_lat', $s[5], $id );
	update_field( 'field_gt_stockist_lng', $s[6], $id );
	update_field( 'field_gt_stockist_geocoded_address', '', $id );
	update_field( 'field_gt_stockist_geocode_status', 'manual', $id );
	update_field( 'field_gt_stockist_phone', $s[8], $id );
	update_field( 'field_gt_stockist_website', $s[9], $id );
	update_field( 'field_gt_stockist_email', '', $id );
	update_field( 'field_gt_stockist_products', array_values( array_filter( array_map( $sku, $s[10] ) ) ), $id );
	$type = term_exists( $s[7], 'stockist_type' );
	if ( ! $type ) {
		$type = wp_insert_term( $s[7], 'stockist_type' );
	}
	wp_set_object_terms( $id, array( (int) $type['term_id'] ), 'stockist_type' );
	WP_CLI::log( ( $existing ? 'updated ' : 'created ' ) . $name );
}

$page = get_page_by_path( 'find-a-stockist' );
if ( ! $page ) {
	$page_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Find a Stockist', 'post_name' => 'find-a-stockist' ) );
} else {
	$page_id = $page->ID;
}
update_post_meta( $page_id, '_wp_page_template', 'page-templates/page-stockists.php' );
update_field( 'field_gt_stockists_intro', 'We don’t sell direct — our products are stocked by independent specialists who know them inside out. Search by place, or by the product you’re after.', $page_id );
update_field( 'field_gt_stockists_trade_heading', 'Run a store? Stock the originals.', $page_id );
update_field( 'field_gt_stockists_trade_text', 'Offer your customers professional-grade formulas with proven repeat demand. From eye-catching POS displays to dedicated launch support, we make stocking Growth Technology simple and profitable.', $page_id );
update_field( 'field_gt_stockists_trade_image', gt_seed_image( 'trade-band', 'Trade', 2680, 600, array( 40, 40, 60 ) ), $page_id );
update_field( 'field_gt_stockists_trade_link', array( 'title' => 'Contact our trade team today', 'url' => home_url( '/contact-us/' ), 'target' => '' ), $page_id );

WP_CLI::success( 'Stockists seeded.' );
