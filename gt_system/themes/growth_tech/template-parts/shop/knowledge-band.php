<?php
/**
 * "Better knowledge. Stronger roots." band — Figma 384:1958. Theme Settings
 * defaults with a per-product override (gt_knowledge_band()).
 *
 * @param array $args ['product_id' => int]
 */

$band = gt_knowledge_band( isset( $args['product_id'] ) ? (int) $args['product_id'] : 0 );
if ( ! $band ) {
	return;
}
?>
<section class="knowledge-band" aria-labelledby="knowledge-band-title">
	<?php
	if ( $band['image'] ) {
		echo wp_get_attachment_image( $band['image'], 'gt-knowledge', false, array(
			'class'   => 'knowledge-band__img',
			'alt'     => '',
			'sizes'   => '(max-width: 1439px) calc(100vw - 100px), 1340px',
			'loading' => 'lazy',
		) );
	}
	?>
	<span class="knowledge-band__scrim" aria-hidden="true"></span>
	<div class="knowledge-band__content">
		<h2 id="knowledge-band-title" class="knowledge-band__title"><?php echo esc_html( $band['heading'] ); ?></h2>
		<?php if ( $band['text'] ) : ?>
			<p class="knowledge-band__text"><?php echo wp_kses_post( $band['text'] ); ?></p>
		<?php endif; ?>
		<?php if ( $band['link'] ) : ?>
			<a class="btn-flat" href="<?php echo esc_url( $band['link']['url'] ); ?>"
				<?php echo ! empty( $band['link']['target'] ) ? 'target="' . esc_attr( $band['link']['target'] ) . '" rel="noopener"' : ''; ?>>
				<span><?php echo esc_html( ! empty( $band['link']['title'] ) ? $band['link']['title'] : __( 'Explore the Plant Academy', 'gt' ) ); ?></span>
				<?php gt_arrow_svg(); ?>
			</a>
		<?php endif; ?>
	</div>
</section>
