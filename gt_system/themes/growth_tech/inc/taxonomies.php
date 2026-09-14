<?php
/**
 * Theme taxonomies.
 */

/**
 * Product badges — the small grey pills on the product page ("Registered
 * product", "Independently tested", "Made in Somerset"). Editors add new
 * ones in Products > Badges; there is no front-end archive.
 */
function gt_register_product_badge_taxonomy() {
	register_taxonomy(
		'product_badge',
		'product',
		array(
			'labels'             => array(
				'name'          => __( 'Badges', 'gt' ),
				'singular_name' => __( 'Badge', 'gt' ),
				'menu_name'     => __( 'Badges', 'gt' ),
				'add_new_item'  => __( 'Add New Badge', 'gt' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
			'hierarchical'       => false,
			'rewrite'            => false,
			'query_var'          => false,
		)
	);
}
add_action( 'init', 'gt_register_product_badge_taxonomy' );

/**
 * WooCommerce lays the product list out as a fixed table with widths on its
 * own columns, which leaves an added taxonomy column one character wide.
 * Give Badges the same footing as Categories / Brands.
 */
function gt_product_badge_admin_column_css() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'edit-product' !== $screen->id ) {
		return;
	}
	echo '<style>.post-type-product .wp-list-table .column-taxonomy-product_badge { width: 11ch; }</style>';
}
add_action( 'admin_head', 'gt_product_badge_admin_column_css' );
