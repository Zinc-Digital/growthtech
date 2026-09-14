<?php
/**
 * WooCommerce foundation: theme support, Phase One "enquiry mode", sort
 * options, per-page count. Templates live in /woocommerce, listing logic in
 * inc/woocommerce-shop.php.
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/**
 * Phase One sells nothing online. While true, price and add-to-cart output is
 * removed and price sorting hidden; flip to false in Phase Two to restore
 * WooCommerce's defaults in the same template slots.
 */
if ( ! defined( 'GT_SHOP_ENQUIRY_MODE' ) ) {
	define( 'GT_SHOP_ENQUIRY_MODE', true );
}

function gt_wc_theme_support() {
	add_theme_support( 'woocommerce' );
	// Gallery zoom / lightbox / slider are replaced by the theme's own gallery
	// on the product page, so none of WC's are declared here.
}
add_action( 'after_setup_theme', 'gt_wc_theme_support' );

// The theme styles every WooCommerce element itself.
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Strip the WooCommerce output the templates replace, and — in enquiry mode —
 * everything to do with buying.
 */
function gt_wc_remove_default_hooks() {
	// Layout the theme owns.
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
	remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
	// content-product.php builds the card markup itself (link wrapper, thumbnail,
	// title, sale flash, rating) and exposes woocommerce_after_shop_loop_item_title
	// / woocommerce_after_shop_loop_item as Phase Two slots instead.
	remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
	remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );

	if ( GT_SHOP_ENQUIRY_MODE ) {
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	}
}
add_action( 'init', 'gt_wc_remove_default_hooks' );

/** No cart, no cart scripts. Phase Two: flip the constant and these return. */
function gt_wc_dequeue_cart_scripts() {
	if ( ! GT_SHOP_ENQUIRY_MODE ) {
		return;
	}
	foreach ( array( 'wc-add-to-cart', 'wc-cart-fragments', 'woocommerce', 'sourcebuster-js', 'wc-order-attribution' ) as $handle ) {
		wp_dequeue_script( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'gt_wc_dequeue_cart_scripts', 99 );

/** Products per page from Theme Settings, default 12. */
function gt_shop_per_page() {
	$value = function_exists( 'get_field' ) ? (int) get_field( 'shop_products_per_page', 'option' ) : 0;
	return $value > 0 ? $value : 12;
}
add_filter( 'loop_shop_per_page', 'gt_shop_per_page' );

/** Sort options offered in the toolbar. Price sorts return in Phase Two. */
function gt_shop_sort_options() {
	$options = array(
		'brands'     => __( 'Our brands first', 'gt' ),
		'title'      => __( 'A - Z', 'gt' ),
		'title-desc' => __( 'Z - A', 'gt' ),
		'date'       => __( 'Newest', 'gt' ),
	);
	if ( ! GT_SHOP_ENQUIRY_MODE ) {
		$options['price']      = __( 'Price: low to high', 'gt' );
		$options['price-desc'] = __( 'Price: high to low', 'gt' );
	}
	return $options;
}
add_filter( 'woocommerce_catalog_orderby', 'gt_shop_sort_options' );
add_filter( 'woocommerce_default_catalog_orderby', function () {
	return 'brands';
} );

/** Term ids of brands flagged as Growth Technology's own. */
function gt_shop_own_brand_term_ids() {
	static $ids = null;
	if ( null === $ids ) {
		$terms = get_terms( array(
			'taxonomy'   => 'product_brand',
			'hide_empty' => false,
			'fields'     => 'ids',
			'meta_key'   => 'own_brand',
			'meta_value' => '1',
			// Explicit orderby so WooCommerce's menu_order default for brands in admin-ajax can't rewrite meta_key to "order".
			'orderby'    => 'name',
		) );
		$ids = is_wp_error( $terms ) ? array() : array_map( 'intval', $terms );
	}
	return $ids;
}

/**
 * "Our brands first" is not a WooCommerce order. Map it to menu_order/title
 * here and let gt_shop_own_brands_first_clauses() prepend the brand rank.
 */
function gt_shop_catalog_ordering_args( $args, $orderby ) {
	if ( 'brands' === $orderby ) {
		$args['orderby']  = 'menu_order title';
		$args['order']    = 'ASC';
		$args['meta_key'] = '';
	}
	return $args;
}
add_filter( 'woocommerce_get_catalog_ordering_args', 'gt_shop_catalog_ordering_args', 10, 2 );

/**
 * Rank products in own brands ahead of the rest. Applies only to queries the
 * shop marks with gt_shop_query and only when the selection sorts by brands.
 */
function gt_shop_own_brands_first_clauses( $clauses, $query ) {
	if ( ! $query->get( 'gt_shop_query' ) || 'brands' !== $query->get( 'gt_shop_orderby' ) ) {
		return $clauses;
	}
	$ids = gt_shop_own_brand_term_ids();
	if ( ! $ids ) {
		return $clauses;
	}
	global $wpdb;
	$in   = implode( ',', $ids );
	$rank = "(CASE WHEN {$wpdb->posts}.ID IN (
		SELECT tr.object_id FROM {$wpdb->term_relationships} tr
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		WHERE tt.taxonomy = 'product_brand' AND tt.term_id IN ({$in})
	) THEN 0 ELSE 1 END)";

	$clauses['orderby'] = $rank . ' ASC' . ( $clauses['orderby'] ? ', ' . $clauses['orderby'] : '' );
	return $clauses;
}
add_filter( 'posts_clauses', 'gt_shop_own_brands_first_clauses', 20, 2 );

/** Filter/sort/load-more behaviour, only where the grid is. */
function gt_shop_enqueue_scripts() {
	if ( ! ( is_shop() || is_product_category() ) ) {
		return;
	}
	wp_enqueue_script(
		'gt-shop-filters',
		get_template_directory_uri() . '/assets/js/shop-filters.js',
		array(),
		gt_asset_version( '/assets/js/shop-filters.js' ),
		true
	);
	wp_localize_script( 'gt-shop-filters', 'gtShop', array(
		'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
		'categoryLocked' => is_product_category(),
		'strings'        => array(
			'empty' => __( 'No products match those filters. Try removing one.', 'gt' ),
		),
	) );
}
add_action( 'wp_enqueue_scripts', 'gt_shop_enqueue_scripts' );
