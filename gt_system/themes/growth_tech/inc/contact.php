<?php
/**
 * Contact page — Figma 384:2873.
 *
 * Field plumbing for page-templates/page-contact.php: the Gravity Forms
 * picker, the Google Map (same key as the stockist finder) and the helpers
 * the template parts read.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Published Gravity Forms as the choices for the page's "Form" select, so the
 * editor picks a form by name instead of typing an id.
 */
function gt_contact_form_choices( $field ) {
	$field['choices'] = array();

	if ( class_exists( 'GFAPI' ) ) {
		foreach ( GFAPI::get_forms( true ) as $form ) {
			$field['choices'][ (string) $form['id'] ] = $form['title'];
		}
	}
	if ( ! $field['choices'] ) {
		$field['instructions'] = __( 'No Gravity Forms have been published yet. Create one under Forms, then choose it here.', 'gt' );
	}

	return $field;
}
add_filter( 'acf/load_field/key=field_gt_contact_form', 'gt_contact_form_choices' );

/**
 * Let ACF's map field use the key already stored in Theme Settings, so there
 * is one key to manage for both the stockist finder and this page.
 */
function gt_contact_acf_maps_key( $api ) {
	if ( empty( $api['key'] ) && function_exists( 'gt_maps_key' ) ) {
		$key = gt_maps_key();
		if ( '' !== $key ) {
			$api['key'] = $key;
		}
	}

	return $api;
}
add_filter( 'acf/fields/google_map/api', 'gt_contact_acf_maps_key' );

/**
 * The map card's "Get directions" target. Coordinates win over the address
 * because they land on the building rather than the street.
 *
 * @param string $address Free-text address.
 * @param float  $lat     Latitude, or 0.
 * @param float  $lng     Longitude, or 0.
 * @return string
 */
function gt_contact_directions_url( $address, $lat = 0, $lng = 0 ) {
	$destination = ( $lat && $lng ) ? $lat . ',' . $lng : trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $address ) ) );

	return '' === $destination ? '' : add_query_arg(
		array( 'api' => 1, 'destination' => $destination ),
		'https://www.google.com/maps/dir/'
	);
}

/** The page's map location, or an empty array when none is set. */
function gt_contact_location( $page_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}
	$location = get_field( 'map_location', $page_id );

	return ( is_array( $location ) && ! empty( $location['lat'] ) && ! empty( $location['lng'] ) ) ? $location : array();
}

/** Google Maps + the map script, on the contact template only and only when both a key and a pin exist. */
function gt_contact_enqueue() {
	if ( ! is_page_template( 'page-templates/page-contact.php' ) ) {
		return;
	}
	$key      = function_exists( 'gt_maps_key' ) ? gt_maps_key() : '';
	$location = gt_contact_location( get_queried_object_id() );
	if ( '' === $key || ! $location ) {
		return;
	}

	wp_enqueue_script( 'gt-contact-map', get_template_directory_uri() . '/assets/js/contact-map.js', array(), gt_asset_version( '/assets/js/contact-map.js' ), true );

	$zoom = (int) get_field( 'map_zoom', get_queried_object_id() );
	$data = array(
		'lat'   => (float) $location['lat'],
		'lng'   => (float) $location['lng'],
		'zoom'  => $zoom ? $zoom : 13,
		'title' => (string) get_field( 'map_card_title', get_queried_object_id() ),
		// The stockist pin at the 35 x 48 the design draws it.
		'pin'   => 'data:image/svg+xml;utf8,' . rawurlencode( '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="24.75" viewBox="0 0 18 24.75"><path fill="#000" d="M0 8.84063C0 3.95625 4.03125 0 9 0C13.9688 0 18 3.95625 18 8.84063C18 15.9094 9 24.75 9 24.75C9 24.75 0 15.9094 0 8.84063ZM9 12C9.79565 12 10.5587 11.6839 11.1213 11.1213C11.6839 10.5587 12 9.79565 12 9C12 8.20435 11.6839 7.44129 11.1213 6.87868C10.5587 6.31607 9.79565 6 9 6C8.20435 6 7.44129 6.31607 6.87868 6.87868C6.31607 7.44129 6 8.20435 6 9C6 9.79565 6.31607 10.5587 6.87868 11.1213C7.44129 11.6839 8.20435 12 9 12Z"/></svg>' ),
	);
	wp_add_inline_script( 'gt-contact-map', 'var gtContactMap = ' . wp_json_encode( $data ) . ';', 'before' );

	wp_enqueue_script(
		'gt-google-maps-contact',
		add_query_arg( array( 'key' => rawurlencode( $key ), 'callback' => 'gtContactMapReady', 'loading' => 'async' ), 'https://maps.googleapis.com/maps/api/js' ),
		array( 'gt-contact-map' ),
		null,
		array( 'in_footer' => true, 'strategy' => 'async' )
	);
}
add_action( 'wp_enqueue_scripts', 'gt_contact_enqueue' );
