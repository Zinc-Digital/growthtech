<?php
/**
 * AJAX endpoint for the shop filters. Same selection → same functions →
 * same HTML as a normal page load; the JS just swaps the regions.
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/**
 * admin-ajax.php requests run with is_admin() true (a WP quirk: the request
 * is served from /wp-admin/, even for wp_ajax_nopriv_ actions). That wakes
 * WC_Brands_Admin, which adds "product_brand" to the sortable taxonomies
 * list; get_terms( 'product_brand' ) then defaults to orderby=menu_order,
 * and WooCommerce's pre_get_terms handler for that rewrites any meta_key we
 * pass into its own "order" meta_key while leaving our meta_value in place —
 * so gt_shop_promos()'s get_terms( meta_key: promo_enabled, meta_value: 1 )
 * silently becomes meta_key: order, meta_value: 1, which matches nothing.
 * Only happens over AJAX; a normal page load never hits this. Drop
 * "product_brand" back out of that list for the one call that needs it.
 */
function gt_shop_ajax_brand_not_sortable( $taxonomies ) {
	return array_diff( $taxonomies, array( 'product_brand' ) );
}

function gt_shop_ajax_filter() {
	$selection = gt_shop_selection( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification -- public, read-only.
	$append    = ! empty( $_GET['append'] ); // phpcs:ignore WordPress.Security.NonceVerification

	$query       = new WP_Query( gt_shop_query_args( $selection ) );
	$total       = (int) $query->found_posts;
	$total_pages = max( 1, (int) $query->max_num_pages );
	$shown       = min( $total, $selection['paged'] * gt_shop_per_page() );

	add_filter( 'woocommerce_sortable_taxonomies', 'gt_shop_ajax_brand_not_sortable' );
	$promos = $append ? array() : gt_shop_promos( $selection );
	remove_filter( 'woocommerce_sortable_taxonomies', 'gt_shop_ajax_brand_not_sortable' );

	ob_start();
	gt_shop_render_loop( $query, $promos );
	$grid = ob_get_clean();

	ob_start();
	get_template_part( 'template-parts/shop/filters', null, array( 'selection' => $selection ) );
	$sidebar = ob_get_clean();

	ob_start();
	get_template_part( 'template-parts/shop/load-more', null, array( 'selection' => $selection, 'total_pages' => $total_pages ) );
	$more = ob_get_clean();

	wp_send_json_success( array(
		'grid'        => $grid,
		'sidebar'     => $sidebar,
		'count'       => gt_shop_count_text( $shown, $total ),
		'more'        => trim( $more ),
		'url'         => gt_shop_build_url( $selection ),
		'page'        => $selection['paged'],
		'total_pages' => $total_pages,
		'total'       => $total,
		'selection'   => $selection,
	) );
}
add_action( 'wp_ajax_gt_shop_filter', 'gt_shop_ajax_filter' );
add_action( 'wp_ajax_nopriv_gt_shop_filter', 'gt_shop_ajax_filter' );
