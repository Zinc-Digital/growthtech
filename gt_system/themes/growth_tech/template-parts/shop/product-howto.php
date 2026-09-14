<?php
/**
 * "How to use" steps — Figma 384:1965 (the How to use tab state).
 *
 * Two steps per row: a 250 x 150 image with a badge in its bottom-left
 * corner, then STEP n / title / text. The badge opens the step's media in
 * the shared lightbox shell at the end: the full-size image, an uploaded
 * video, or a YouTube/Vimeo embed. Embeds are only created when opened.
 *
 * @param array $args ['steps' => array (from gt_product_howto_steps), 'intro' => string]
 */

$steps = isset( $args['steps'] ) && is_array( $args['steps'] ) ? $args['steps'] : array();
$intro = isset( $args['intro'] ) ? (string) $args['intro'] : '';
if ( ! $steps ) {
	return;
}
$allowed = array(
	'a'      => array( 'href' => true, 'target' => true, 'rel' => true ),
	'strong' => array(),
	'em'     => array(),
	'br'     => array(),
);
?>
<?php if ( trim( wp_strip_all_tags( $intro ) ) ) : ?>
	<div class="product-tabs__text product-tabs__text--single product-howto__intro"><?php echo wp_kses_post( $intro ); ?></div>
<?php endif; ?>
<ol class="product-howto">
	<?php foreach ( $steps as $i => $step ) : ?>
		<?php
		$n        = $i + 1;
		$is_video = 'image' !== $step['media'];
		$full     = wp_get_attachment_image_url( $step['image'], 'full' );
		$label    = $is_video
			/* translators: %s: step title */
			? sprintf( __( 'Play video: %s', 'gt' ), $step['title'] )
			/* translators: %s: step title */
			: sprintf( __( 'Enlarge: %s', 'gt' ), $step['title'] );
		?>
		<li class="product-howto__step">
			<div class="product-howto__media">
				<?php
				echo wp_get_attachment_image( $step['image'], 'medium_large', false, array(
					'class'   => 'product-howto__img',
					'alt'     => $step['title'],
					'loading' => 'lazy',
				) );
				?>
				<button type="button" class="product-howto__badge product-howto__badge--<?php echo $is_video ? 'play' : 'zoom'; ?>"
					data-howto-open data-media="<?php echo esc_attr( $step['media'] ); ?>" data-src="<?php echo esc_attr( $step['src'] ); ?>"
					data-full="<?php echo esc_attr( $full ); ?>"
					data-title="<?php echo esc_attr( $step['title'] ); ?>"
					aria-label="<?php echo esc_attr( $label ); ?>"><?php gt_icon_svg( $is_video ? 'play' : 'zoom' ); ?></button>
			</div>
			<div class="product-howto__copy">
				<span class="product-howto__eyebrow"><?php echo esc_html( sprintf( /* translators: %d: step number */ __( 'Step %d', 'gt' ), $n ) ); ?></span>
				<h3 class="product-howto__title"><?php echo esc_html( $step['title'] ); ?></h3>
				<?php if ( '' !== trim( $step['text'] ) ) : ?>
					<p class="product-howto__text"><?php echo wp_kses( $step['text'], $allowed ); ?></p>
				<?php endif; ?>
			</div>
		</li>
	<?php endforeach; ?>
</ol>
