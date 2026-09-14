<?php
/**
 * "Run a store? Stock the originals." — Figma 384:2866. Same component as
 * the product page's knowledge band, fed by the page's ACF fields.
 *
 * @param array $args ['page_id' => int]
 */

$page_id = isset( $args['page_id'] ) ? (int) $args['page_id'] : get_queried_object_id();
if ( ! function_exists( 'get_field' ) ) {
	return;
}
$heading  = (string) get_field( 'trade_heading', $page_id );
$text     = (string) get_field( 'trade_text', $page_id );
$image_id = (int) get_field( 'trade_image', $page_id );
$link     = get_field( 'trade_link', $page_id );
if ( '' === trim( $heading ) ) {
	return;
}
?>
<section class="knowledge-band knowledge-band--trade" aria-labelledby="trade-band-title">
	<?php
	if ( $image_id ) {
		echo wp_get_attachment_image( $image_id, 'gt-knowledge', false, array( 'class' => 'knowledge-band__img', 'alt' => '', 'sizes' => '(max-width: 1439px) calc(100vw - 100px), 1340px', 'loading' => 'lazy' ) );
	}
	?>
	<span class="knowledge-band__scrim" aria-hidden="true"></span>
	<div class="knowledge-band__content">
		<h2 id="trade-band-title" class="knowledge-band__title"><?php echo esc_html( $heading ); ?></h2>
		<?php if ( $text ) : ?>
			<p class="knowledge-band__text"><?php echo wp_kses_post( $text ); ?></p>
		<?php endif; ?>
		<?php if ( is_array( $link ) && ! empty( $link['url'] ) ) : ?>
			<a class="btn-flat" href="<?php echo esc_url( $link['url'] ); ?>"<?php echo ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; ?>>
				<span><?php echo esc_html( ! empty( $link['title'] ) ? $link['title'] : __( 'Contact our trade team today', 'gt' ) ); ?></span>
				<?php gt_arrow_svg(); ?>
			</a>
		<?php endif; ?>
	</div>
</section>
