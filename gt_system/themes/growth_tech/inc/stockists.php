<?php
/**
 * Stockists: post type, type taxonomy, country list, address helper and the
 * admin geocode column. Geocoding and the REST proxy live further down
 * (appended in Task 2); the front end is page-templates/page-stockists.php.
 */

function gt_register_stockist_post_type() {
	register_post_type(
		'stockist',
		array(
			'labels'              => array(
				'name'          => __( 'Stockists', 'gt' ),
				'singular_name' => __( 'Stockist', 'gt' ),
				'add_new_item'  => __( 'Add New Stockist', 'gt' ),
				'edit_item'     => __( 'Edit Stockist', 'gt' ),
				'menu_name'     => __( 'Stockists', 'gt' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			// No REST exposure — the finder page renders stockists itself; keeps published entries out of /wp/v2.
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-location',
			'menu_position'       => 27,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
		)
	);

	register_taxonomy(
		'stockist_type',
		'stockist',
		array(
			'labels'             => array(
				'name'          => __( 'Stockist Types', 'gt' ),
				'singular_name' => __( 'Stockist Type', 'gt' ),
				'menu_name'     => __( 'Types', 'gt' ),
				'add_new_item'  => __( 'Add New Type', 'gt' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_admin_column'  => true,
			// No REST exposure — the finder page renders stockists itself; keeps published entries out of /wp/v2.
			'show_in_rest'       => false,
			'hierarchical'       => false,
			'rewrite'            => false,
			'query_var'          => false,
		)
	);
}
add_action( 'init', 'gt_register_stockist_post_type' );

/** ISO-2 code => country name, from WooCommerce (falls back to GB only). */
function gt_stockist_countries() {
	static $countries = null;
	if ( null === $countries ) {
		$countries = ( function_exists( 'WC' ) && WC()->countries ) ? WC()->countries->get_countries() : array( 'GB' => 'United Kingdom (UK)' );
	}
	return $countries;
}

/** Feed the country select from WooCommerce's list. */
function gt_stockist_country_choices( $field ) {
	$field['choices'] = gt_stockist_countries();
	return $field;
}
add_filter( 'acf/load_field/name=country', 'gt_stockist_country_choices' );

/** "1 High Street, Taunton, TA1 1AA, United Kingdom (UK)" — empties skipped. */
function gt_stockist_address_string( $post_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}
	$parts = array();
	foreach ( array( 'address_1', 'address_2', 'town', 'region', 'postcode' ) as $name ) {
		$value = trim( (string) get_field( $name, $post_id ) );
		if ( '' !== $value ) {
			$parts[] = $value;
		}
	}
	$code      = strtoupper( trim( (string) get_field( 'country', $post_id ) ) );
	$countries = gt_stockist_countries();
	if ( $code ) {
		$parts[] = isset( $countries[ $code ] ) ? $countries[ $code ] : $code;
	}
	return implode( ', ', $parts );
}

/** Admin list: show whether each stockist has coordinates. */
function gt_stockist_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['geocode'] = __( 'Geocode', 'gt' );
		}
	}
	return $new;
}
add_filter( 'manage_stockist_posts_columns', 'gt_stockist_admin_columns' );

function gt_stockist_admin_column_value( $column, $post_id ) {
	if ( 'geocode' !== $column || ! function_exists( 'get_field' ) ) {
		return;
	}
	$status = (string) get_field( 'geocode_status', $post_id );
	$labels = array(
		'ok'     => __( 'Located', 'gt' ),
		'manual' => __( 'Manual coordinates', 'gt' ),
		'failed' => __( 'Not found — check the address', 'gt' ),
		'no_key' => __( 'No Google Maps key set', 'gt' ),
	);
	echo esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : __( 'Not geocoded yet', 'gt' ) );
}
add_action( 'manage_stockist_posts_custom_column', 'gt_stockist_admin_column_value', 10, 2 );
