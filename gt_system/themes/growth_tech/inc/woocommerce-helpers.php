<?php
/**
 * Small product/term readers shared by the shop templates.
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/** ACF term field with a fallback, safe when ACF is off. */
function gt_term_field( $name, WP_Term $term, $default = '' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}
	$value = get_field( $name, $term );
	return ( null === $value || '' === $value || false === $value ) ? $default : $value;
}

/**
 * The category shown on the card. Yoast's primary term wins when set;
 * otherwise the first top-level category alphabetically. Never the
 * WooCommerce default "Uncategorized".
 */
function gt_product_primary_category( WC_Product $product ) {
	$default_id = (int) get_option( 'default_product_cat' );
	$primary_id = (int) get_post_meta( $product->get_id(), '_yoast_wpseo_primary_product_cat', true );

	if ( $primary_id && $primary_id !== $default_id ) {
		$term = get_term( $primary_id, 'product_cat' );
		if ( $term instanceof WP_Term ) {
			return $term;
		}
	}

	$terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'orderby' => 'name', 'order' => 'ASC' ) );
	if ( is_wp_error( $terms ) || ! $terms ) {
		return null;
	}
	$terms = array_filter( $terms, function ( $t ) use ( $default_id ) {
		return (int) $t->term_id !== $default_id;
	} );
	if ( ! $terms ) {
		return null;
	}
	foreach ( $terms as $term ) {
		if ( 0 === (int) $term->parent ) {
			return $term;
		}
	}
	return reset( $terms );
}

/** Size names in the attribute's own order, e.g. ['100ml', '300ml', '750ml']. */
function gt_product_sizes( WC_Product $product ) {
	if ( ! taxonomy_exists( 'pa_size' ) ) {
		return array();
	}
	$names = wc_get_product_terms( $product->get_id(), 'pa_size', array( 'fields' => 'names' ) );
	return is_wp_error( $names ) ? array() : array_values( $names );
}

/** First brand term on the product, or null. */
function gt_product_brand( WC_Product $product ) {
	if ( ! taxonomy_exists( 'product_brand' ) ) {
		return null;
	}
	$terms = wp_get_post_terms( $product->get_id(), 'product_brand' );
	return ( is_wp_error( $terms ) || ! $terms ) ? null : $terms[0];
}

/** Brand accent hex, defaulting to the Clonex yellow used across the designs. */
function gt_brand_accent( WP_Term $brand ) {
	$colour = (string) gt_term_field( 'accent_colour', $brand, '' );
	return preg_match( '/^#[0-9a-fA-F]{6}$/', $colour ) ? strtoupper( $colour ) : '#FBC707';
}
