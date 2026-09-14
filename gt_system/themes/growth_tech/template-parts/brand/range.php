<?php
/**
 * "The {Brand} Range" — Figma 384:1526. Every product in the brand (the
 * main archive query, which Task 3 made unpaginated for brands).
 *
 * @param array $args ['brand' => WP_Term]
 */

global $wp_query;
$brand = isset( $args['brand'] ) ? $args['brand'] : null;
if ( ! $brand instanceof WP_Term ) {
	return;
}
$total = (int) $wp_query->found_posts;
/* translators: %s: brand name */
$title = sprintf( __( 'The %s Range', 'gt' ), $brand->name );
?>
<section class="brand-range" id="range" aria-labelledby="brand-range-title">
	<div class="brand-range__inner">
		<div class="brand-range__head">
			<h2 id="brand-range-title" class="brand-range__title"><?php echo esc_html( $title ); ?></h2>
			<?php if ( $total ) : ?>
				<p class="brand-range__count"><?php echo esc_html( gt_shop_count_text( $total, $total ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $wp_query->have_posts() ) : ?>
			<ul class="brand-range__grid">
				<?php gt_shop_render_loop( $wp_query, array() ); ?>
			</ul>
		<?php else : ?>
			<p class="brand-range__empty"><?php esc_html_e( 'Products from this range are coming soon.', 'gt' ); ?></p>
		<?php endif; ?>
	</div>
</section>
