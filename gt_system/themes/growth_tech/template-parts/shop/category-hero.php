<?php
/**
 * Category hero — Figma 384:1281. Only on product category archives.
 */

if ( ! is_product_category() ) {
	return;
}
$term = get_queried_object();
if ( ! $term instanceof WP_Term ) {
	return;
}

$heading  = (string) gt_term_field( 'hero_heading', $term, $term->name );
$text     = (string) gt_term_field( 'hero_text', $term, $term->description );
$image_id = (int) gt_term_field( 'hero_image', $term, 0 );
?>
<section class="shop-hero" aria-labelledby="shop-hero-title">
	<?php
	if ( $image_id ) {
		echo wp_get_attachment_image( $image_id, 'gt-category-hero', false, array(
			'class' => 'shop-hero__img',
			'alt'   => '',
			'sizes' => '(max-width: 1439px) calc(100vw - 100px), 1340px',
		) );
	}
	?>
	<span class="shop-hero__scrim" aria-hidden="true"></span>
	<h1 id="shop-hero-title" class="shop-hero__title"><?php echo esc_html( $heading ); ?></h1>
	<?php if ( $text ) : ?>
		<p class="shop-hero__text"><?php echo wp_kses_post( $text ); ?></p>
	<?php endif; ?>
</section>
