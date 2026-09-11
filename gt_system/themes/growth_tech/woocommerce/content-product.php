<?php
/**
 * Product card — Figma 384:1742 ("Frame 64" in All Products).
 *
 * Image on a light tile, then title and a "Category • sizes" line. The whole
 * card is one link. Used by the shop grid, the AJAX endpoint and every other
 * product row on the site.
 *
 * @var WC_Product $product
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
	return;
}

$category = gt_product_primary_category( $product );
$sizes    = gt_product_sizes( $product );
$image_id = (int) $product->get_image_id();
$img_attr = array(
	'class' => 'product-card__img',
	'sizes' => '(max-width: 767px) calc(100vw - 50px), (max-width: 1023px) calc(50vw - 38px), (max-width: 1265px) calc((100vw - 365px) / 2), 322px',
	'loading' => 'lazy',
);
?>
<li <?php wc_product_class( 'product-card', $product ); ?>>
	<a class="product-card__link" href="<?php echo esc_url( $product->get_permalink() ); ?>">
		<div class="product-card__media">
			<?php
			if ( $image_id ) {
				echo wp_get_attachment_image( $image_id, 'gt-product-card', false, $img_attr );
			} else {
				echo wc_placeholder_img( 'gt-product-card', $img_attr );
			}
			?>
		</div>
		<div class="product-card__body">
			<h2 class="product-card__title"><?php echo esc_html( $product->get_name() ); ?></h2>
			<?php if ( $category || $sizes ) : ?>
				<p class="product-card__meta">
					<?php if ( $category ) : ?>
						<span class="product-card__category"><?php echo esc_html( $category->name ); ?></span>
					<?php endif; ?>
					<?php if ( $category && $sizes ) : ?>
						<span class="product-card__dot" aria-hidden="true"></span>
					<?php endif; ?>
					<?php if ( $sizes ) : ?>
						<span class="product-card__sizes"><?php echo esc_html( implode( '/', $sizes ) ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>
	</a>
</li>
