<?php
/**
 * AJAX endpoint for the shop filters. Same selection → same functions →
 * same HTML as a normal page load; the JS just swaps the regions.
 *
 * admin-ajax.php requests run with is_admin() true (a WP quirk: the request
 * is served from /wp-admin/, even for wp_ajax_nopriv_ actions), which makes
 * WooCommerce default orderby to menu_order for the product_brand taxonomy.
 * The get_terms( product_brand ) calls this endpoint depends on (in
 * gt_shop_own_brand_term_ids() and gt_shop_promos()) pass an explicit
 * orderby for that reason, so that default can't rewrite their meta_key.
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

function gt_shop_ajax_filter() {
	$selection = gt_shop_selection( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification -- public, read-only.
	$append    = ! empty( $_GET['append'] ); // phpcs:ignore WordPress.Security.NonceVerification

	$query       = new WP_Query( gt_shop_query_args( $selection ) );
	$total       = (int) $query->found_posts;
	$total_pages = max( 1, (int) $query->max_num_pages );
	$shown       = min( $total, $selection['paged'] * gt_shop_per_page() );
	$promos      = $append ? array() : gt_shop_promos( $selection );

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
