<?php
/**
 * Seed shop content for local development and tests. Idempotent: re-running
 * updates in place (products matched by SKU, terms by slug).
 *
 * Run: tests/bin/wpx eval-file tests/seed/seed-shop.php
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce is not active.' );
}

// -- Terms -------------------------------------------------------------------

function gt_seed_term( $taxonomy, $name, $slug, array $args = array() ) {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term ) {
		if ( ! empty( $args['description'] ) ) {
			wp_update_term( $term->term_id, $taxonomy, array( 'description' => $args['description'] ) );
		}
		return (int) $term->term_id;
	}
	$result = wp_insert_term( $name, $taxonomy, array_merge( array( 'slug' => $slug ), $args ) );
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( "{$taxonomy}/{$slug}: " . $result->get_error_message() );
	}
	return (int) $result['term_id'];
}

$categories = array(
	'propagation'   => 'Propagation',
	'nutrients'     => 'Nutrients',
	'growing-media' => 'Growing Media',
	'plant-care'    => 'Plant Care',
);
$cat_ids = array();
foreach ( $categories as $slug => $name ) {
	$cat_ids[ $slug ] = gt_seed_term( 'product_cat', $name, $slug, array(
		'description' => "Everything you need for {$name}.",
	) );
}
update_field( 'field_gt_cat_hero_heading', 'Propagation - The science of the start.', 'term_' . $cat_ids['propagation'] );
update_field( 'field_gt_cat_hero_text', 'Everything a cutting or seed needs to be come a plant - gels, mists, cubes and first feeds, formulated in-house since 1985.', 'term_' . $cat_ids['propagation'] );

// slug => [name, own, promo position (0 = none), tagline]
$brands = array(
	'clonex'            => array( 'Clonex', 1, 2, 'The original rooting gel. <em>A complete propagation system.</em>' ),
	'root-riot'         => array( 'Root Riot', 1, 7, 'Propagation cubes for <em>faster rooting.</em>' ),
	'ionic'             => array( 'Ionic', 1, 0, '' ),
	'formulex'          => array( 'Formulex', 1, 0, '' ),
	'nitrozyme'         => array( 'Nitrozyme', 0, 0, '' ),
	'growth-technology' => array( 'Growth Technology', 1, 0, '' ),
	'smc'               => array( 'SMC', 0, 0, '' ),
);
$brand_ids = array();
foreach ( $brands as $slug => $b ) {
	$id                 = gt_seed_term( 'product_brand', $b[0], $slug );
	$brand_ids[ $slug ] = $id;
	update_field( 'field_gt_brand_own', $b[1], 'term_' . $id );
	update_field( 'field_gt_brand_accent', 'root-riot' === $slug ? '#A9C23F' : '#FBC707', 'term_' . $id );
	update_field( 'field_gt_brand_promo_enabled', $b[2] ? 1 : 0, 'term_' . $id );
	update_field( 'field_gt_brand_promo_position', $b[2] ?: 2, 'term_' . $id );
	update_field( 'field_gt_brand_promo_tagline', $b[3], 'term_' . $id );
}

// -- Attributes --------------------------------------------------------------

function gt_seed_attribute( $name, $label, array $terms ) {
	$taxonomy = wc_attribute_taxonomy_name( $name );
	if ( ! wc_attribute_taxonomy_id_by_name( $name ) ) {
		$result = wc_create_attribute( array(
			'name'         => $label,
			'slug'         => $name,
			'type'         => 'select',
			'order_by'     => 'menu_order',
			'has_archives' => false,
		) );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( "attribute {$name}: " . $result->get_error_message() );
		}
		delete_transient( 'wc_attribute_taxonomies' );
	}
	if ( ! taxonomy_exists( $taxonomy ) ) {
		register_taxonomy( $taxonomy, 'product', array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ) );
	}
	$ids = array();
	foreach ( $terms as $index => $term_name ) {
		$slug        = sanitize_title( $term_name );
		$term        = get_term_by( 'slug', $slug, $taxonomy );
		$term_id     = $term ? (int) $term->term_id : (int) wp_insert_term( $term_name, $taxonomy, array( 'slug' => $slug ) )['term_id'];
		$ids[ $slug ] = $term_id;
		update_term_meta( $term_id, 'order', $index ); // WC sorts attribute terms by this when order_by = menu_order.
	}
	return array( $taxonomy, $ids );
}

list( $size_tax, $size_ids )     = gt_seed_attribute( 'size', 'Size', array( '5ml', '50ml', '100ml', '300ml', '500ml', '750ml', '1L', '5L', '50L', '24 Tray', '50 Refill', '100 Refill' ) );
list( $medium_tax, $medium_ids ) = gt_seed_attribute( 'growing-medium', 'Growing Medium', array( 'Soil', 'Coco', 'Hydro' ) );
list( $stage_tax, $stage_ids )   = gt_seed_attribute( 'growing-stage', 'Growing Stage', array( 'Cuttings', 'Seedlings', 'Vegetative', 'Flowering' ) );

// -- Products ----------------------------------------------------------------

function gt_seed_attribute_object( $name, $taxonomy, array $term_ids, $position ) {
	$attribute = new WC_Product_Attribute();
	$attribute->set_id( wc_attribute_taxonomy_id_by_name( $name ) );
	$attribute->set_name( $taxonomy );
	$attribute->set_options( $term_ids );
	$attribute->set_position( $position );
	$attribute->set_visible( true );
	$attribute->set_variation( false );
	return $attribute;
}

// sku => [title, category, brand, sizes, mediums, stages]
$products = array(
	'GT-001' => array( 'Clonex Mist', 'propagation', 'clonex', array( '100ml', '300ml', '750ml' ), array( 'soil', 'coco', 'hydro' ), array( 'cuttings' ) ),
	'GT-002' => array( 'Root Riot', 'propagation', 'root-riot', array( '24-tray', '50-refill', '100-refill' ), array( 'soil', 'coco', 'hydro' ), array( 'cuttings', 'seedlings' ) ),
	'GT-003' => array( 'Clonex Rooting Hormone', 'propagation', 'clonex', array( '50ml' ), array( 'soil', 'coco', 'hydro' ), array( 'cuttings' ) ),
	'GT-004' => array( 'Clonex Pro Start', 'propagation', 'clonex', array( '100ml', '500ml' ), array( 'soil', 'coco', 'hydro' ), array( 'cuttings', 'seedlings' ) ),
	'GT-005' => array( 'Clonex Mist Concentrate', 'propagation', 'clonex', array( '1l', '5l' ), array( 'soil', 'coco', 'hydro' ), array( 'cuttings' ) ),
	'GT-006' => array( 'Budget Propagator', 'propagation', 'growth-technology', array(), array(), array( 'cuttings', 'seedlings' ) ),
	'GT-007' => array( 'Pipettes', 'propagation', 'growth-technology', array( '5ml' ), array(), array() ),
	'GT-008' => array( 'Ionic Hydro Grow', 'nutrients', 'ionic', array( '1l', '5l' ), array( 'hydro' ), array( 'vegetative' ) ),
	'GT-009' => array( 'Ionic Coco Grow', 'nutrients', 'ionic', array( '1l', '5l' ), array( 'coco' ), array( 'vegetative' ) ),
	'GT-010' => array( 'Ionic Soil Grow', 'nutrients', 'ionic', array( '1l', '5l' ), array( 'soil' ), array( 'vegetative' ) ),
	'GT-011' => array( 'Formulex', 'nutrients', 'formulex', array( '1l' ), array( 'soil', 'coco', 'hydro' ), array( 'seedlings' ) ),
	'GT-012' => array( 'Coco Professional Plus', 'growing-media', 'growth-technology', array( '50l' ), array( 'coco' ), array() ),
	'GT-013' => array( 'Nitrozyme', 'plant-care', 'nitrozyme', array( '100ml', '300ml' ), array( 'soil', 'coco', 'hydro' ), array( 'vegetative', 'flowering' ) ),
);

$menu_order = 0;
foreach ( $products as $sku => $p ) {
	$existing = wc_get_product_id_by_sku( $sku );
	$product  = $existing ? wc_get_product( $existing ) : new WC_Product_Simple();

	$product->set_name( $p[0] );
	$product->set_sku( $sku );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_short_description( "{$p[0]} - a short description that appears under the product title." );
	$product->set_description( "{$p[0]} full description. Replace with real copy." );
	$product->set_category_ids( array( $cat_ids[ $p[1] ] ) );
	// Nitrozyme is the only third-party brand; give it the lowest menu_order so
	// that only the own-brands-first rank keeps it off page 1 — tests rely on this.
	$product->set_menu_order( 'GT-013' === $sku ? -1 : $menu_order++ );

	$attributes = array();
	if ( $p[3] ) {
		$attributes[] = gt_seed_attribute_object( 'size', $size_tax, array_map( function ( $s ) use ( $size_ids ) { return $size_ids[ $s ]; }, $p[3] ), 0 );
	}
	if ( $p[4] ) {
		$attributes[] = gt_seed_attribute_object( 'growing-medium', $medium_tax, array_map( function ( $s ) use ( $medium_ids ) { return $medium_ids[ $s ]; }, $p[4] ), 1 );
	}
	if ( $p[5] ) {
		$attributes[] = gt_seed_attribute_object( 'growing-stage', $stage_tax, array_map( function ( $s ) use ( $stage_ids ) { return $stage_ids[ $s ]; }, $p[5] ), 2 );
	}
	$product->set_attributes( $attributes );

	$id = $product->save();
	wp_set_object_terms( $id, array( $brand_ids[ $p[2] ] ), 'product_brand' );
	WP_CLI::log( ( $existing ? 'updated ' : 'created ' ) . $sku . ' ' . $p[0] );
}

// Term counts can lag after direct term assignment.
foreach ( array( 'product_cat', 'product_brand', $size_tax, $medium_tax, $stage_tax ) as $taxonomy ) {
	$tt_ids = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'tt_ids' ) );
	if ( ! is_wp_error( $tt_ids ) && $tt_ids ) {
		wp_update_term_count_now( $tt_ids, $taxonomy );
	}
}

// -- Pages -------------------------------------------------------------------

$shop_id = wc_get_page_id( 'shop' );
if ( $shop_id > 0 ) {
	wp_update_post( array( 'ID' => $shop_id, 'post_title' => 'Our Products' ) );
}

wc_delete_product_transients();
WP_CLI::success( 'Shop content seeded.' );
