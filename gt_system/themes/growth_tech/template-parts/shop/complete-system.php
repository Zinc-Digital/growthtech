<?php
/**
 * "Complete the system" — Figma 384:1915. Upsells (or the rest of the
 * brand) as product cards on a grey band; more than four becomes a slider
 * with the arrows in the header.
 *
 * @param array $args ['product' => WC_Product, 'items' => WC_Product[] (optional override)]
 */

$product = isset( $args['product'] ) ? $args['product'] : null;
if ( ! $product instanceof WC_Product ) {
	return;
}
$items = isset( $args['items'] ) && is_array( $args['items'] ) ? $args['items'] : gt_product_related( $product, 12 );
if ( ! $items ) {
	return;
}
$is_slider = count( $items ) > 4;
if ( $is_slider ) {
	wp_enqueue_script( 'gt-block-slider' );
}
$title_id = 'product-related-' . $product->get_id();
?>
<section class="product-related" aria-labelledby="<?php echo esc_attr( $title_id ); ?>" data-slider-scope>
	<div class="product-related__inner">
		<div class="product-related__head">
			<h2 id="<?php echo esc_attr( $title_id ); ?>" class="product-related__title"><?php esc_html_e( 'Complete the system', 'gt' ); ?></h2>
			<div class="product-related__tools">
				<a class="product-related__all" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'View all Products', 'gt' ); ?></a>
				<?php if ( $is_slider ) : ?>
					<div class="shop-slider__arrows" data-slider-arrows></div>
				<?php endif; ?>
			</div>
		</div>

		<ul class="product-related__grid<?php echo $is_slider ? ' product-related__grid--slider' : ''; ?>"
			<?php if ( $is_slider ) : ?>
				data-block-slider data-slides="4" data-slides-md="2" data-slides-sm="1" data-arrows="1" data-dots="0"
			<?php endif; ?>>
			<?php
			$current = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
			foreach ( $items as $item ) {
				$GLOBALS['product'] = $item; // content-product.php reads the global.
				wc_get_template_part( 'content', 'product' );
			}
			$GLOBALS['product'] = $current;
			?>
		</ul>
	</div>
</section>
