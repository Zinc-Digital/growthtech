<?php
/**
 * "Rooted in science" — Figma 384:1494 / 384:1564 / 384:1508. Steps slider
 * left, heading + copy + FAQ callout right. Skipped entirely when the brand
 * has no copy, steps or FAQ.
 *
 * @param array $args ['brand' => WP_Term]
 */

$brand = isset( $args['brand'] ) ? $args['brand'] : null;
if ( ! $brand instanceof WP_Term ) {
	return;
}
$heading = (string) gt_term_field( 'science_heading', $brand, '' );
$accent  = (string) gt_term_field( 'science_accent_line', $brand, '' );
$text    = (string) gt_term_field( 'science_text', $brand, '' );
$steps   = gt_term_field( 'steps', $brand, array() );
$faq     = gt_term_field( 'faq', $brand, array() );
$steps   = is_array( $steps ) ? array_values( array_filter( $steps, function ( $s ) {
	return ! empty( $s['image'] ) || ! empty( $s['product'] ) || ! empty( $s['text'] );
} ) ) : array();
$has_faq = is_array( $faq ) && ! empty( $faq['question'] );

if ( ! $heading && ! trim( wp_strip_all_tags( $text ) ) && ! $steps && ! $has_faq ) {
	return;
}
$is_slider = count( $steps ) > 1;
if ( $is_slider ) {
	wp_enqueue_script( 'gt-block-slider' );
}
?>
<section class="brand-science" data-slider-scope>
	<div class="brand-science__inner">

		<?php if ( $steps ) : ?>
			<div class="brand-science__slider">
				<ul class="brand-steps"
					<?php if ( $is_slider ) : ?>
						data-block-slider data-slides="1" data-slides-md="1" data-slides-sm="1" data-arrows="1" data-progress="1"
					<?php endif; ?>>
					<?php foreach ( $steps as $index => $step ) : ?>
						<?php
						$product  = ! empty( $step['product'] ) ? wc_get_product( (int) $step['product'] ) : null;
						$product  = $product instanceof WC_Product ? $product : null;
						$label    = ! empty( $step['label'] ) ? $step['label'] : '';
						$image_id = ! empty( $step['image'] ) ? (int) $step['image'] : ( $product ? (int) $product->get_image_id() : 0 );
						$title    = $product ? $product->get_name() : $label;
						$url      = $product ? $product->get_permalink() : '';
						$x        = isset( $step['hotspot_x'] ) && '' !== $step['hotspot_x'] ? max( 0, min( 100, (float) $step['hotspot_x'] ) ) : 73;
						$y        = isset( $step['hotspot_y'] ) && '' !== $step['hotspot_y'] ? max( 0, min( 100, (float) $step['hotspot_y'] ) ) : 47;
						?>
						<li class="brand-steps__step">
							<div class="brand-steps__media">
								<?php
								if ( $image_id ) {
									echo wp_get_attachment_image( $image_id, 'gt-brand-step', false, array(
										'class'   => 'brand-steps__img',
										'alt'     => '',
										'sizes'   => '(max-width: 767px) calc(100vw - 50px), 429px',
										'loading' => $index > 0 ? 'lazy' : 'eager',
									) );
								}
								?>
								<?php if ( $label ) : ?>
									<span class="brand-steps__badge"><?php echo esc_html( mb_strtoupper( $label ) ); ?></span>
								<?php endif; ?>
								<?php if ( $url ) : ?>
									<a class="brand-steps__marker" href="<?php echo esc_url( $url ); ?>"
										style="left: <?php echo esc_attr( $x ); ?>%; top: <?php echo esc_attr( $y ); ?>%;"
										aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'View %s', 'gt' ), $title ) ); ?>">
										<?php gt_icon_svg( 'plus' ); ?>
									</a>
								<?php endif; ?>
							</div>
							<div class="brand-steps__body">
								<?php if ( $title ) : ?>
									<h3 class="brand-steps__title"><?php echo esc_html( $title ); ?></h3>
								<?php endif; ?>
								<?php if ( ! empty( $step['text'] ) ) : ?>
									<p class="brand-steps__text"><?php echo wp_kses_post( $step['text'] ); ?></p>
								<?php endif; ?>
								<?php if ( $url ) : ?>
									<a class="brand-steps__link" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'View Product', 'gt' ); ?></a>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php if ( $is_slider ) : ?>
					<div class="brand-steps__controls">
						<div class="shop-slider__progress" data-slider-dots></div>
						<div class="shop-slider__arrows" data-slider-arrows></div>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="brand-science__copy">
			<?php if ( $heading || $accent ) : ?>
				<h2 class="brand-science__title"><?php
					echo esc_html( $heading );
					if ( $accent ) {
						echo ' <em class="brand-science__accent">' . esc_html( $accent ) . '</em>';
					}
				?></h2>
			<?php endif; ?>

			<?php if ( trim( wp_strip_all_tags( $text ) ) ) : ?>
				<div class="brand-science__text"><?php echo wp_kses_post( $text ); ?></div>
			<?php endif; ?>

			<?php if ( $has_faq ) : ?>
				<aside class="brand-faq">
					<?php if ( ! empty( $faq['eyebrow'] ) ) : ?>
						<p class="brand-faq__eyebrow"><?php echo esc_html( mb_strtoupper( $faq['eyebrow'] ) ); ?></p>
					<?php endif; ?>
					<p class="brand-faq__question"><?php echo esc_html( $faq['question'] ); ?></p>
					<?php if ( ! empty( $faq['answer'] ) ) : ?>
						<p class="brand-faq__answer"><?php echo wp_kses_post( $faq['answer'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $faq['link']['url'] ) ) : ?>
						<a class="brand-faq__link" href="<?php echo esc_url( $faq['link']['url'] ); ?>"
							<?php echo ! empty( $faq['link']['target'] ) ? 'target="' . esc_attr( $faq['link']['target'] ) . '" rel="noopener"' : ''; ?>>
							<?php echo esc_html( ! empty( $faq['link']['title'] ) ? $faq['link']['title'] : __( 'More answers in the Plant Academy', 'gt' ) ); ?>
						</a>
					<?php endif; ?>
				</aside>
			<?php endif; ?>
		</div>

	</div>
</section>
