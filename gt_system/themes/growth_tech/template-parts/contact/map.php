<?php
/**
 * Map band — Figma 384:2873 (Frame 69). A live Google map when a key and a
 * location are set, otherwise the fallback image; the black address card sits
 * 50 in from the top left either way.
 *
 * @param array $args ['page_id' => int]
 */

$page_id = isset( $args['page_id'] ) ? (int) $args['page_id'] : get_queried_object_id();
if ( ! function_exists( 'get_field' ) ) {
	return;
}

$location = gt_contact_location( $page_id );
$has_key  = function_exists( 'gt_maps_key' ) && '' !== gt_maps_key();
$image_id = (int) get_field( 'map_image', $page_id );
$live     = $location && $has_key;

$title   = trim( (string) get_field( 'map_card_title', $page_id ) );
$address = trim( (string) get_field( 'map_card_address', $page_id ) );
if ( '' === $address ) {
	$address = trim( (string) get_field( 'address', $page_id ) );
}
$link = get_field( 'map_card_link', $page_id );
$url  = is_array( $link ) && ! empty( $link['url'] ) ? $link['url'] : gt_contact_directions_url(
	$location ? $location['address'] : $address,
	$location ? $location['lat'] : 0,
	$location ? $location['lng'] : 0
);
$label = is_array( $link ) && ! empty( $link['title'] ) ? $link['title'] : __( 'Get directions', 'gt' );

$has_card = '' !== $title || '' !== $address;
if ( ! $live && ! $image_id && ! $has_card ) {
	return;
}
?>
<section class="contact-map"<?php echo $has_card && '' !== $title ? ' aria-labelledby="contact-map-title"' : ' aria-label="' . esc_attr__( 'Our location', 'gt' ) . '"'; ?>>
	<?php if ( $live ) : ?>
		<div class="contact-map__canvas" data-contact-map role="region" aria-label="<?php esc_attr_e( 'Map of our head office', 'gt' ); ?>"></div>
	<?php elseif ( $image_id ) : ?>
		<?php
		echo wp_get_attachment_image( $image_id, 'gt-category-hero', false, array(
			'class'   => 'contact-map__img',
			'alt'     => '',
			'sizes'   => '(max-width: 1439px) calc(100vw - 150px), 1290px',
			'loading' => 'lazy',
		) );
		?>
	<?php elseif ( current_user_can( 'manage_options' ) ) : ?>
		<p class="contact-map__hint"><?php esc_html_e( 'Set a location under Contact page → Map, and a Google Maps key under Theme Settings → Shop Settings, to show the map here.', 'gt' ); ?></p>
	<?php endif; ?>

	<?php if ( $has_card ) : ?>
		<div class="contact-map__card">
			<?php if ( '' !== $title ) : ?>
				<h2 class="contact-map__title" id="contact-map-title"><?php echo nl2br( esc_html( $title ) ); ?></h2>
			<?php endif; ?>
			<?php if ( '' !== $address ) : ?>
				<p class="contact-map__address"><?php echo nl2br( esc_html( $address ) ); ?></p>
			<?php endif; ?>
			<?php if ( $url ) : ?>
				<a class="contact-map__link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
