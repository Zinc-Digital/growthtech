<?php
/**
 * Guide sidebar — Figma 384:2407. Three stacked panels: jump links to the
 * guide's own sections, the products it uses, and what to read next.
 *
 * The contents list is built from the Section Text blocks in the body, so it
 * needs no second list to keep in step — but those blocks only carry an id
 * when the editor sets one, so gt_academy_sections() adds them.
 *
 * @param array $args ['post_id' => int]
 */

$post_id  = isset( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();
$sections = gt_academy_sections( $post_id );
$products = function_exists( 'get_field' ) ? get_field( 'products', $post_id ) : array();
$products = is_array( $products ) ? array_filter( array_map( 'wc_get_product', $products ) ) : array();
$related  = gt_academy_related( $post_id, 3 );

if ( ! $sections && ! $products && ! $related ) {
	return;
}
?>
<aside class="guide-aside" aria-label="<?php esc_attr_e( 'About this guide', 'gt' ); ?>">
	<div class="guide-aside__sticky">

		<?php if ( $sections ) : ?>
			<nav class="guide-aside__panel" aria-label="<?php esc_attr_e( 'In this guide', 'gt' ); ?>">
				<p class="guide-aside__label"><?php esc_html_e( 'In this guide', 'gt' ); ?></p>
				<ol class="guide-aside__toc">
					<?php foreach ( $sections as $section ) : ?>
						<li class="guide-aside__toc-item">
							<a class="guide-aside__toc-link" href="#<?php echo esc_attr( $section['id'] ); ?>"><?php echo esc_html( $section['title'] ); ?></a>
						</li>
					<?php endforeach; ?>
				</ol>
			</nav>
		<?php endif; ?>

		<?php if ( $products ) : ?>
			<div class="guide-aside__panel">
				<p class="guide-aside__label"><?php esc_html_e( 'In this guide we used', 'gt' ); ?></p>
				<ul class="guide-aside__products">
					<?php foreach ( $products as $product ) : ?>
						<?php
						$image_id = (int) $product->get_image_id();
						$category = function_exists( 'gt_product_primary_category' ) ? gt_product_primary_category( $product ) : null;
						?>
						<li class="guide-aside__product">
							<a class="guide-aside__product-link" href="<?php echo esc_url( $product->get_permalink() ); ?>">
								<span class="guide-aside__product-media">
									<?php
									if ( $image_id ) {
										echo wp_get_attachment_image( $image_id, 'gt-product-thumb', false, array(
											'class'   => 'guide-aside__product-img',
											'alt'     => '',
											'loading' => 'lazy',
										) );
									}
									?>
								</span>
								<span class="guide-aside__product-body">
									<span class="guide-aside__product-name"><?php echo esc_html( $product->get_name() ); ?></span>
									<?php if ( $category ) : ?>
										<span class="guide-aside__product-meta"><?php echo esc_html( $category->name ); ?></span>
									<?php endif; ?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( $related ) : ?>
			<div class="guide-aside__panel">
				<p class="guide-aside__label"><?php esc_html_e( 'Keep learning', 'gt' ); ?></p>
				<ul class="guide-aside__related">
					<?php foreach ( $related as $other ) : ?>
						<?php $other_term = gt_academy_primary_term( $other->ID ); ?>
						<li class="guide-aside__related-item">
							<?php if ( $other_term ) : ?>
								<span class="guide-aside__related-kicker"><?php echo esc_html( $other_term->name ); ?></span>
							<?php endif; ?>
							<a class="guide-aside__related-link" href="<?php echo esc_url( get_permalink( $other ) ); ?>"><?php echo esc_html( get_the_title( $other ) ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

	</div>
</aside>
