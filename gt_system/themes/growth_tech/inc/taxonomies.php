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
