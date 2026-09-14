<?php
require_once __DIR__ . '/lib/bootstrap.php';

$find = function ( $title ) {
	$posts = get_posts( array( 'post_type' => 'stockist', 'post_status' => 'any', 'title' => $title, 'posts_per_page' => 1 ) );
	return $posts ? $posts[0] : null;
};

// -- Export ---------------------------------------------------------------------
$columns = gt_stockists_csv_columns();
gt_assert_equal( array( 'id', 'name', 'status', 'type', 'address_1', 'address_2', 'town', 'region', 'postcode', 'country', 'phone', 'website', 'email', 'products', 'latitude', 'longitude', 'geocode_status' ), $columns, 'column order' );

$rows = gt_stockists_export_rows();
gt_assert_equal( 8, count( $rows ), 'one row per seeded stockist' );
gt_assert_equal( 'Amsterdam Grow Store', $rows[0]['name'], 'rows ordered by name' );
$somerset = null;
foreach ( $rows as $row ) {
	if ( 'Somerset Hydro Centre' === $row['name'] ) {
		$somerset = $row;
	}
}
gt_assert( is_array( $somerset ), 'Somerset exported' );
gt_assert_equal( 'GB', $somerset['country'], 'country code exported' );
gt_assert_equal( 'Hydroponics specialist', $somerset['type'], 'type name exported' );
gt_assert_equal( '51.0153', $somerset['latitude'], 'latitude exported as typed' );
gt_assert_contains( 'Clonex Mist', $somerset['products'], 'products exported by name' );
gt_assert_not_contains( 'GT-001', $somerset['products'], 'not by SKU' );
gt_assert_equal( 7, count( explode( '|', $somerset['products'] ) ), 'seven names, pipe-separated' );
gt_assert_equal( 'manual', $somerset['geocode_status'], 'status exported' );
gt_assert_equal( (string) $find( 'Somerset Hydro Centre' )->ID, $somerset['id'], 'id exported' );

$csv = gt_stockists_csv_string( $rows );
gt_assert_equal( "\xEF\xBB\xBF", substr( $csv, 0, 3 ), 'UTF-8 BOM for Excel' );
gt_assert_contains( "id,name,status,type,", $csv, 'header row' );
gt_assert_contains( '"Somerset Hydro Centre"', $csv, 'name present (quoted where needed)' );

// -- Import: plan --------------------------------------------------------------
$tmp = wp_tempnam( 'stockists-import.csv' );
$somerset_id = $find( 'Somerset Hydro Centre' )->ID;
file_put_contents( $tmp, implode( "\n", array( // phpcs:ignore WordPress.WP.AlternativeFunctions
	'id,name,status,type,address_1,town,postcode,country,phone,website,products,latitude,longitude,ignored_column',
	$somerset_id . ',Somerset Hydro Centre,publish,Hydroponics specialist,"2 New Road",Taunton,TA1 2BB,GB,01823 111 222,https://somerset.example,Clonex Mist|root riot,,,x',
	',Import Test Shop,publish,Garden centre,"9 Test Lane",Bath,BA1 1AA,GB,01225 000 000,,GT-003|No Such Product,,,x',
	',Import Manual Shop,draft,Grow shop,"1 Pin Street",Cork,,IE,,,Clonex Pro Start,51.8985,-8.4756,x',
	',,publish,Grow shop,"No Name",Leeds,LS1 1AA,GB,,,,,,x',
	',Import Bad Country,publish,Grow shop,"1 Elsewhere",Nowhere,,ZZ,,,,,,x',
) ) );

$created = array( 'Import Test Shop', 'Import Manual Shop' );
try {
	$plan = gt_stockists_import_parse( $tmp );
	gt_assert( ! is_wp_error( $plan ), 'file parses' );
	gt_assert_equal( 5, count( $plan['rows'] ), 'five data rows' );
	gt_assert_equal( 'update', $plan['rows'][0]['action'], 'row with a known id updates' );
	gt_assert_equal( $somerset_id, $plan['rows'][0]['post_id'], 'matched by id' );
	gt_assert_equal( 'create', $plan['rows'][1]['action'], 'new name creates' );
	gt_assert_contains( 'No Such Product', implode( ' ', $plan['rows'][1]['warnings'] ), 'unknown product is warned about, not fatal' );
	gt_assert_equal( 'create', $plan['rows'][2]['action'], 'draft row with coordinates creates' );
	gt_assert_equal( 'skip', $plan['rows'][3]['action'], 'missing name skips' );
	gt_assert_contains( 'name', implode( ' ', $plan['rows'][3]['errors'] ), 'skip reason names the field' );
	gt_assert_equal( 'skip', $plan['rows'][4]['action'], 'unknown country skips' );
	gt_assert_contains( 'ZZ', implode( ' ', $plan['rows'][4]['errors'] ), 'skip reason quotes the code' );
	gt_assert_equal( array( 'create' => 2, 'update' => 1, 'skip' => 2 ), $plan['totals'], 'totals' );
	gt_assert( ! in_array( 'ignored_column', $plan['columns'], true ), 'unknown columns dropped' );

	// -- Import: apply ---------------------------------------------------------
	$result = gt_stockists_import_apply( $plan );
	gt_assert_equal( 2, $result['created'], 'two created' );
	gt_assert_equal( 1, $result['updated'], 'one updated' );
	gt_assert_equal( 2, $result['skipped'], 'two skipped' );
	gt_assert_equal( 8 + 2, count( get_posts( array( 'post_type' => 'stockist', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) ) ), 'nothing deleted' );

	gt_assert_equal( '2 New Road', get_field( 'address_1', $somerset_id ), 'update wrote the address' );
	gt_assert_equal( 'TA1 2BB', get_field( 'postcode', $somerset_id ), 'update wrote the postcode' );
	gt_assert_equal( array( wc_get_product_id_by_sku( 'GT-001' ), wc_get_product_id_by_sku( 'GT-002' ) ), array_map( 'intval', (array) get_field( 'products', $somerset_id ) ), 'update replaced the products, matched by name case-insensitively' );
	gt_assert_equal( 'https://somerset.example', get_field( 'website', $somerset_id ), 'update wrote the website' );
	gt_assert_equal( '', (string) get_field( 'lat', $somerset_id ), 'blank coordinates clear the pin so it is re-geocoded' );
	gt_assert( in_array( $somerset_id, gt_stockists_geocode_queue(), true ), 'updated row without coordinates is queued' );

	$shop = $find( 'Import Test Shop' );
	gt_assert( $shop instanceof WP_Post, 'created' );
	gt_assert_equal( 'publish', $shop->post_status, 'published' );
	gt_assert_equal( array( 'Garden centre' ), wp_get_post_terms( $shop->ID, 'stockist_type', array( 'fields' => 'names' ) ), 'new type term created and assigned' );
	gt_assert_equal( array( wc_get_product_id_by_sku( 'GT-003' ) ), array_map( 'intval', (array) get_field( 'products', $shop->ID ) ), 'a SKU still resolves; the unknown name is left out' );
	gt_assert( in_array( $shop->ID, gt_stockists_geocode_queue(), true ), 'created row without coordinates is queued' );

	$manual = $find( 'Import Manual Shop' );
	gt_assert_equal( 'draft', $manual->post_status, 'draft status honoured' );
	gt_assert_equal( 51.8985, (float) get_field( 'lat', $manual->ID ), 'coordinates written' );
	gt_assert_equal( 'manual', get_field( 'geocode_status', $manual->ID ), 'typed coordinates are manual' );
	gt_assert( ! in_array( $manual->ID, gt_stockists_geocode_queue(), true ), 'row with coordinates is not queued' );

	// Re-running the same file is idempotent: the created rows now match by name and update.
	$plan2 = gt_stockists_import_parse( $tmp );
	gt_assert_equal( array( 'create' => 0, 'update' => 3, 'skip' => 2 ), $plan2['totals'], 'second run updates instead of duplicating' );
	gt_stockists_import_apply( $plan2 );
	gt_assert_equal( 10, count( get_posts( array( 'post_type' => 'stockist', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) ) ), 'still ten' );

	// -- Geocode queue + batch (no key: everything lands on no_key) ------------
	$saved_key = get_field( 'shop_google_maps_key', 'option' );
	$saved_gk  = get_field( 'shop_google_geocoding_key', 'option' );
	try {
		update_field( 'field_gt_shop_maps_key', '', 'option' );
		update_field( 'field_gt_shop_geocoding_key', '', 'option' );
		gt_assert( wp_next_scheduled( 'gt_stockist_geocode_batch' ) > 0, 'batch cron scheduled while the queue has items' );
		gt_stockists_geocode_batch();
		gt_assert_equal( 'no_key', get_field( 'geocode_status', $shop->ID ), 'batch ran the save-hook geocoder' );
		gt_assert( ! in_array( $shop->ID, gt_stockists_geocode_queue(), true ), 'processed id leaves the queue' );
		gt_assert_equal( array(), gt_stockists_geocode_queue(), 'queue drained in one batch (fewer than the batch size)' );
		gt_assert_equal( false, wp_next_scheduled( 'gt_stockist_geocode_batch' ), 'no cron left once the queue is empty' );
	} finally {
		update_field( 'field_gt_shop_maps_key', $saved_key, 'option' );
		update_field( 'field_gt_shop_geocoding_key', $saved_gk, 'option' );
	}

	// -- Bad file ----------------------------------------------------------------
	file_put_contents( $tmp, "foo,bar\n1,2\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$bad = gt_stockists_import_parse( $tmp );
	gt_assert( is_wp_error( $bad ) && 'missing_columns' === $bad->get_error_code(), 'a file without a name column is rejected' );
} finally {
	foreach ( $created as $title ) {
		$p = $find( $title );
		if ( $p ) {
			wp_delete_post( $p->ID, true );
		}
	}
	$term = get_term_by( 'name', 'Garden centre', 'stockist_type' );
	if ( $term ) {
		wp_delete_term( $term->term_id, 'stockist_type' );
	}
	delete_option( 'gt_stockists_geocode_queue' );
	wp_clear_scheduled_hook( 'gt_stockist_geocode_batch' );
	unlink( $tmp );
	// Put Somerset back the way the seed left it.
	include __DIR__ . '/seed/seed-stockists.php';
}

gt_test_done();
