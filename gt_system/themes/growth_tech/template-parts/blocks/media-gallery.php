<?php
/**
 * Media Gallery block — Figma 387:5948 (Frame 181). One or more items at the
 * full width of the article column; more than one turns it into a slider with
 * the shared arrows.
 *
 * An item is either an image, which opens full size in the shared lightbox, or
 * a video — uploaded, YouTube or Vimeo — which plays there instead. Either way
 * the slide shows the same still; only the control on it changes.
 */

$images = get_field( 'images' );
$images = is_array( $images ) ? array_values( array_filter( $images, function ( $row ) {
	return ! empty( $row['image'] );
} ) ) : array();

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
		<?php foreach ( $images as $i => $row ) : ?>
			<?php
			$image_id = (int) $row['image'];
			$full     = wp_get_attachment_image_url( $image_id, 'full' );
			$video    = gt_media_row_video( $row );
			// Nothing to open if it is an image with no full size behind it.
			$openable = $video || $full;
			?>
			<li class="b-media-gallery__slide">
				<?php
				echo wp_get_attachment_image( $image_id, 'gt-brand-hero', false, array(
					'class'   => 'b-media-gallery__img',
					'alt'     => '',
					'sizes'   => '(max-width: 1439px) calc(100vw - 100px), 1141px',
					'loading' => 0 === $i ? 'eager' : 'lazy',
				) );
				?>
				<?php if ( $openable ) : ?>
					<button type="button" class="b-media-gallery__zoom media-zoom<?php echo $video ? ' media-zoom--play' : ''; ?>"
						<?php echo gt_lightbox_attrs( $video, $full ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?>
						aria-label="<?php echo esc_attr( $video ? __( 'Play video', 'gt' ) : __( 'Enlarge image', 'gt' ) ); ?>">
						<?php gt_icon_svg( $video ? 'play' : 'zoom' ); ?>
					</button>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( $multiple ) : ?>
		<div class="b-media-gallery__arrows shop-slider__arrows" data-slider-arrows></div>
	<?php endif; ?>
</section>
