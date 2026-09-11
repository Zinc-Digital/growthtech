<?php
/**
 * Shop listing logic: the filter "selection", the queries it drives, sidebar
 * counts, brand promo placement and canonical URLs. Everything the archive
 * template and the AJAX endpoint share lives here so both render the same
 * thing for the same parameters.
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/** Sidebar groups in display order. Key = query param. */
function gt_shop_filter_groups() {
	return array(
		'categories'     => array( 'label' => __( 'Category', 'gt' ), 'taxonomy' => 'product_cat', 'collapsed' => false ),
		'brands'         => array( 'label' => __( 'Brands', 'gt' ), 'taxonomy' => 'product_brand', 'collapsed' => false ),
		'growing-medium' => array( 'label' => __( 'Growing Medium', 'gt' ), 'taxonomy' => 'pa_growing-medium', 'collapsed' => false ),
		'growing-stage'  => array( 'label' => __( 'Growing Stage', 'gt' ), 'taxonomy' => 'pa_growing-stage', 'collapsed' => true ),
	);
}

/** Comma-separated slugs → clean unique slug list. */
function gt_shop_parse_slugs( $value ) {
	if ( is_array( $value ) ) {
		$value = implode( ',', $value );
	}
	$slugs = array();
	foreach ( explode( ',', (string) $value ) as $slug ) {
		$slug = sanitize_title( trim( $slug ) );
		if ( '' !== $slug ) {
			$slugs[] = $slug;
		}
	}
	return array_values( array_unique( $slugs ) );
}

/**
 * The active filters. With no $source, reads $_GET and (on a category archive
 * rendered normally, not via AJAX) folds the current category in.
 */
function gt_shop_selection( $source = null ) {
	static $cached = null;
	$from_request = null === $source;

	if ( $from_request && null !== $cached ) {
		return $cached;
	}
	if ( $from_request ) {
		$source = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification -- read-only filters.
	}

	$selection = array();
	foreach ( array_keys( gt_shop_filter_groups() ) as $key ) {
		$selection[ $key ] = isset( $source[ $key ] ) ? gt_shop_parse_slugs( $source[ $key ] ) : array();
	}

	if ( $from_request && ! wp_doing_ajax() && is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term && ! in_array( $term->slug, $selection['categories'], true ) ) {
			array_unshift( $selection['categories'], $term->slug );
		}
	}

	$selection['q'] = isset( $source['q'] ) ? trim( sanitize_text_field( (string) $source['q'] ) ) : '';

	$orderby              = isset( $source['orderby'] ) ? sanitize_key( (string) $source['orderby'] ) : '';
	$selection['orderby'] = array_key_exists( $orderby, gt_shop_sort_options() ) ? $orderby : 'brands';

	$paged = isset( $source['paged'] ) ? (int) $source['paged'] : 0;
	if ( $from_request && $paged < 1 ) {
		$paged = (int) get_query_var( 'paged' );
	}
	$selection['paged'] = max( 1, $paged );

	if ( $from_request ) {
		$cached = $selection;
	}
	return $selection;
}

/** Taxonomy clauses for the active groups. */
function gt_shop_tax_query( array $selection ) {
	$tax_query = array();
	foreach ( gt_shop_filter_groups() as $key => $group ) {
		if ( ! empty( $selection[ $key ] ) && taxonomy_exists( $group['taxonomy'] ) ) {
			$tax_query[] = array(
				'taxonomy' => $group['taxonomy'],
				'field'    => 'slug',
				'terms'    => $selection[ $key ],
				'operator' => 'IN',
			);
		}
	}
	return $tax_query;
}

/** Complete WP_Query args for a selection — used by AJAX, counts and tests. */
function gt_shop_query_args( array $selection ) {
	// WC_Query::get_catalog_ordering_args() only splits an "orderby-order"
	// string (e.g. "title-desc") into its parts when it derives $orderby
	// itself from $_GET/query vars; passed a value directly, as here, it
	// skips that split. Split it ourselves so e.g. "title-desc" still sorts
	// Z-A instead of falling through to WP_Query's default ordering.
	$parts    = explode( '-', $selection['orderby'], 2 );
	$ordering = WC()->query->get_catalog_ordering_args( $parts[0], isset( $parts[1] ) ? $parts[1] : '' );
	$args     = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'paged'               => $selection['paged'],
		'posts_per_page'      => gt_shop_per_page(),
		'orderby'             => $ordering['orderby'],
		'order'               => $ordering['order'],
		'tax_query'           => array_merge( WC()->query->get_tax_query( array(), false ), gt_shop_tax_query( $selection ) ),
		'ignore_sticky_posts' => true,
		'gt_shop_query'       => true,
		'gt_shop_orderby'     => $selection['orderby'],
	);
	if ( ! empty( $ordering['meta_key'] ) ) {
		$args['meta_key'] = $ordering['meta_key']; // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	if ( '' !== $selection['q'] ) {
		$args['s'] = $selection['q'];
	}
	return $args;
}

/** Every product id matching the selection (ignores paging). Cached per request. */
function gt_shop_matching_ids( array $selection ) {
	static $cache = array();
	$key = md5( wp_json_encode( array_diff_key( $selection, array( 'paged' => 1, 'orderby' => 1 ) ) ) );
	if ( ! isset( $cache[ $key ] ) ) {
		$args = array_merge( gt_shop_query_args( $selection ), array(
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'paged'          => 1,
			'no_found_rows'  => true,
			'orderby'        => 'ID',
			'gt_shop_orderby' => 'title',
		) );
		$cache[ $key ] = array_map( 'intval', ( new WP_Query( $args ) )->posts );
	}
	return $cache[ $key ];
}

/**
 * Counts for one sidebar group: how many products each term would show,
 * with every *other* group's filters applied but not this group's own.
 */
function gt_shop_term_counts( $group_key, array $selection ) {
	$groups = gt_shop_filter_groups();
	if ( ! isset( $groups[ $group_key ] ) || ! taxonomy_exists( $groups[ $group_key ]['taxonomy'] ) ) {
		return array();
	}
	$others               = $selection;
	$others[ $group_key ] = array();
	$ids                  = gt_shop_matching_ids( $others );
	$taxonomy             = $groups[ $group_key ]['taxonomy'];
	$default_cat          = (int) get_option( 'default_product_cat' );

	$terms = get_terms( array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'parent'     => 'product_cat' === $taxonomy ? 0 : '',
		'orderby'    => 'name',
		'order'      => 'ASC',
	) );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	// Attribute terms keep the order set in Products > Attributes (term meta
	// "order", missing = 0). usort is stable on PHP 8 so ties stay A-Z.
	if ( 0 === strpos( $taxonomy, 'pa_' ) ) {
		usort( $terms, function ( $a, $b ) {
			return (int) get_term_meta( $a->term_id, 'order', true ) <=> (int) get_term_meta( $b->term_id, 'order', true );
		} );
	}

	$counts = array();
	foreach ( $terms as $term ) {
		if ( (int) $term->term_id === $default_cat ) {
			continue;
		}
		$in_term = $ids ? get_objects_in_term( $term->term_id, $taxonomy ) : array();
		$in_term = is_wp_error( $in_term ) ? array() : array_map( 'intval', $in_term );
		$counts[ $term->slug ] = count( array_intersect( $ids, $in_term ) );
	}
	return $counts;
}

/**
 * Brand promo tiles for this page: position => term. Only on page 1 and
 * only while no brand / attribute / search filter narrows the grid; on a
 * category page only brands that have products in that category.
 */
function gt_shop_promos( array $selection ) {
	if ( $selection['paged'] > 1 || $selection['brands'] || $selection['growing-medium'] || $selection['growing-stage'] || '' !== $selection['q'] ) {
		return array();
	}
	$brands = get_terms( array(
		'taxonomy'   => 'product_brand',
		'hide_empty' => false,
		'meta_key'   => 'promo_enabled', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value' => '1',              // phpcs:ignore WordPress.DB.SlowDBQuery
		// Explicit orderby so WooCommerce's menu_order default for brands in admin-ajax can't rewrite meta_key to "order".
		'orderby'    => 'name',
	) );
	if ( is_wp_error( $brands ) ) {
		return array();
	}

	$promos = array();
	foreach ( $brands as $brand ) {
		if ( $selection['categories'] ) {
			$has = get_posts( array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'tax_query'      => array(
					array( 'taxonomy' => 'product_brand', 'field' => 'term_id', 'terms' => $brand->term_id ),
					array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $selection['categories'] ),
				),
			) );
			if ( ! $has ) {
				continue;
			}
		}
		$position = max( 1, (int) gt_term_field( 'promo_position', $brand, 2 ) );
		while ( isset( $promos[ $position ] ) ) {
			$position++;
		}
		$promos[ $position ] = $brand;
	}
	ksort( $promos );
	return $promos;
}

/** Canonical URL for a selection: category permalink when exactly one category, else the shop page. */
function gt_shop_build_url( array $selection ) {
	$params = array();
	$base   = wc_get_page_permalink( 'shop' );

	if ( 1 === count( $selection['categories'] ) ) {
		$link = get_term_link( $selection['categories'][0], 'product_cat' );
		if ( ! is_wp_error( $link ) ) {
			$base = $link;
		} else {
			$params['categories'] = $selection['categories'][0];
		}
	} elseif ( $selection['categories'] ) {
		$params['categories'] = implode( ',', $selection['categories'] );
	}

	foreach ( array( 'brands', 'growing-medium', 'growing-stage' ) as $key ) {
		if ( $selection[ $key ] ) {
			$params[ $key ] = implode( ',', $selection[ $key ] );
		}
	}
	if ( '' !== $selection['q'] ) {
		$params['q'] = $selection['q'];
	}
	if ( 'brands' !== $selection['orderby'] ) {
		$params['orderby'] = $selection['orderby'];
	}
	if ( $selection['paged'] > 1 ) {
		$base = trailingslashit( $base ) . 'page/' . $selection['paged'] . '/';
	}
	return $params ? add_query_arg( $params, $base ) : $base;
}

/** "Showing 7 of 26 products". */
function gt_shop_count_text( $shown, $total ) {
	/* translators: 1: products shown so far, 2: total matching products */
	return sprintf( __( 'Showing %1$d of %2$d products', 'gt' ), (int) $shown, (int) $total );
}

/** Echo the grid items for a query, splicing promo tiles in at their positions. */
function gt_shop_render_loop( WP_Query $query, array $promos = array() ) {
	$cell = 1;
	while ( $query->have_posts() ) {
		$query->the_post();
		while ( isset( $promos[ $cell ] ) ) {
			wc_get_template( 'content-brand-promo.php', array( 'brand' => $promos[ $cell ] ) );
			unset( $promos[ $cell ] );
			$cell++;
		}
		wc_get_template_part( 'content', 'product' );
		$cell++;
	}
	foreach ( $promos as $brand ) {
		wc_get_template( 'content-brand-promo.php', array( 'brand' => $brand ) );
	}
	wp_reset_postdata();
}

/** Apply the selection to WooCommerce's main product query. */
function gt_shop_main_query( $query ) {
	$selection = gt_shop_selection();
	$query->set( 'gt_shop_query', true );
	$query->set( 'gt_shop_orderby', $selection['orderby'] );
	$query->set( 'tax_query', array_merge( (array) $query->get( 'tax_query' ), gt_shop_tax_query( $selection ) ) );
	if ( '' !== $selection['q'] ) {
		$query->set( 's', $selection['q'] );
	}
}
add_action( 'woocommerce_product_query', 'gt_shop_main_query' );

/**
 * One URL per selection: /shop/?categories=x becomes the category permalink,
 * and a category page carrying other categories goes back to the shop.
 */
function gt_shop_canonical_redirect() {
	if ( wp_doing_ajax() || is_admin() || ! ( is_shop() || is_product_category() ) ) {
		return;
	}
	if ( ! isset( $_GET['categories'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$selection = gt_shop_selection();
	$target    = gt_shop_build_url( $selection );
	$current   = home_url( add_query_arg( array() ) );
	if ( untrailingslashit( $target ) !== untrailingslashit( $current ) ) {
		wp_safe_redirect( $target, 302 );
		exit;
	}
}
add_action( 'template_redirect', 'gt_shop_canonical_redirect' );
