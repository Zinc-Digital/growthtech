<?php
/**
 * Media Gallery block — Figma 387:5948 (Frame 181). One or more images at the
 * full width of the article column, each with an enlarge button; more than
 * one turns it into a slider with the shared arrows.
 */

$images = get_field( 'images' );
$images = is_array( $images ) ? array_values( array_filter( array_map( 'intval', wp_list_pluck( $images, 'image' ) ) ) ) : array();

if ( ! $images ) {
	if ( ! empty( $is_preview ) ) {
		echo '<p class="block-empty">' . esc_html__( 'Media Gallery — add an image.', 'gt' ) . '</p>';
	}
	return;
}

$classes = 'b-media-gallery';
if ( ! empty( $block['className'] ) ) {
	$classes .= ' ' . sanitize_html_class( $block['className'] );
}
$anchor   = ! empty( $block['anchor'] ) ? ' id="' . esc_attr( $block['anchor'] ) . '"' : '';
$multiple = count( $images ) > 1;

wp_enqueue_script( 'gt-media-lightbox' );
if ( $multiple ) {
	wp_enqueue_script( 'gt-block-slider' );
}
?>
<section class="<?php echo esc_attr( $classes ); ?>"<?php echo $anchor; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	aria-label="<?php esc_attr_e( 'Gallery', 'gt' ); ?>"<?php echo $multiple ? ' data-slider-scope' : ''; ?>>
	<ul class="b-media-gallery__slides"<?php echo $multiple ? ' data-block-slider data-slides="1" data-slides-md="1" data-slides-sm="1" data-arrows="1" data-dots="0"' : ''; ?>>
		<?php foreach ( $images as $i => $image_id ) : ?>
			<?php $full = wp_get_attachment_image_url( $image_id, 'full' ); ?>
			<li class="b-media-gallery__slide">
				<?php
				echo wp_get_attachment_image( $image_id, 'gt-brand-hero', false, array(
					'class'   => 'b-media-gallery__img',
					'alt'     => '',
					'sizes'   => '(max-width: 1439px) calc(100vw - 100px), 1141px',
					'loading' => 0 === $i ? 'eager' : 'lazy',
				) );
				?>
				<?php if ( $full ) : ?>
					<button type="button" class="b-media-gallery__zoom media-zoom" data-media-zoom
						data-src="<?php echo esc_url( $full ); ?>" aria-label="<?php esc_attr_e( 'Enlarge image', 'gt' ); ?>">
						<?php gt_icon_svg( 'zoom' ); ?>
					</button>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( $multiple ) : ?>
		<div class="b-media-gallery__arrows shop-slider__arrows" data-slider-arrows></div>
	<?php endif; ?>
</section>
