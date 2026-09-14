<?php
/**
 * Product gallery — Figma 384:1827. 600px tile with the cut-out contained,
 * zoom button bottom-left, prev/next bottom-right, three thumbnails under.
 *
 * Without JavaScript the first image shows and each thumbnail links to the
 * full-size file; product-gallery.js turns it into a slider + lightbox.
 *
 * @param array $args ['product' => WC_Product]
 */

$product = isset( $args['product'] ) ? $args['product'] : null;
if ( ! $product instanceof WC_Product ) {
	return;
}

$image_ids = array_filter( array_merge( array( (int) $product->get_image_id() ), array_map( 'intval', $product->get_gallery_image_ids() ) ) );
$image_ids = array_values( array_unique( $image_ids ) );
$multiple  = count( $image_ids ) > 1;
$name      = $product->get_name();
?>
<div class="product-gallery" data-product-gallery>
	<div class="product-gallery__main">
		<div class="product-gallery__slides" data-gallery-slides>
			<?php if ( $image_ids ) : ?>
				<?php foreach ( $image_ids as $index => $image_id ) : ?>
					<div class="product-gallery__slide">
						<?php
						$full = wp_get_attachment_image_url( $image_id, 'full' );
						$alt  = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
						echo wp_get_attachment_image( $image_id, 'gt-product-main', false, array(
							'class'     => 'product-gallery__img',
							'sizes'     => '(max-width: 767px) calc(100vw - 50px), (max-width: 1265px) 50vw, 600px',
							'data-full' => $full ? $full : '',
							'loading'   => $index > 0 ? 'lazy' : 'eager',
							'alt'       => $alt ? $alt : $name,
						) );
						?>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="product-gallery__slide">
					<?php echo wc_placeholder_img( 'gt-product-main', array( 'class' => 'product-gallery__img', 'data-full' => '' ) ); ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $image_ids ) : ?>
			<button type="button" class="product-gallery__zoom" data-gallery-zoom aria-label="<?php esc_attr_e( 'Enlarge image', 'gt' ); ?>">
				<?php gt_icon_svg( 'zoom' ); ?>
			</button>
		<?php endif; ?>

		<?php if ( $multiple ) : ?>
			<div class="product-gallery__arrows" data-gallery-arrows></div>
		<?php endif; ?>
	</div>

	<?php if ( $multiple ) : ?>
		<ul class="product-gallery__thumbs" data-gallery-thumbs>
			<?php foreach ( $image_ids as $index => $image_id ) : ?>
				<li class="product-gallery__thumb-item">
					<a class="product-gallery__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( wp_get_attachment_image_url( $image_id, 'full' ) ); ?>"
						data-gallery-thumb="<?php echo esc_attr( $index ); ?>"
						<?php echo 0 === $index ? 'aria-current="true"' : ''; ?>
						aria-label="<?php echo esc_attr( sprintf( /* translators: 1: image number, 2: product name */ __( 'Show image %1$d of %2$s', 'gt' ), $index + 1, $name ) ); ?>">
						<?php echo wp_get_attachment_image( $image_id, 'gt-product-thumb', false, array( 'class' => 'product-gallery__thumb-img', 'sizes' => '187px', 'loading' => 'lazy', 'alt' => '' ) ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $image_ids ) : ?>
		<div class="product-lightbox" data-gallery-lightbox hidden role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( '%s images', 'gt' ), $name ) ); ?>">
			<button type="button" class="product-lightbox__close" data-lightbox-close aria-label="<?php esc_attr_e( 'Close', 'gt' ); ?>"><?php gt_icon_svg( 'close' ); ?></button>
			<?php if ( $multiple ) : ?>
				<button type="button" class="product-lightbox__nav product-lightbox__nav--prev" data-lightbox-prev aria-label="<?php esc_attr_e( 'Previous image', 'gt' ); ?>"><?php gt_icon_svg( 'arrow-square' ); ?></button>
				<button type="button" class="product-lightbox__nav product-lightbox__nav--next" data-lightbox-next aria-label="<?php esc_attr_e( 'Next image', 'gt' ); ?>"><?php gt_icon_svg( 'arrow-square' ); ?></button>
			<?php endif; ?>
			<img class="product-lightbox__img" data-lightbox-img src="" alt="<?php echo esc_attr( $name ); ?>" />
		</div>
	<?php endif; ?>
</div>
