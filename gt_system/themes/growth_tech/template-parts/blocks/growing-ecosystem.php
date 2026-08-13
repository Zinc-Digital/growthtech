<?php
/**
 * Block: Growing Ecosystem — Figma "Frame 13" (165:2277) + "Frame 17" (165:2290).
 *
 * Centred heading and intro, a row of image cards each carrying its own call to
 * action, then a closing dark button.
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (unused).
 * @param bool   $is_preview True when rendering in the editor.
 */

$heading  = get_field( 'heading' );
$text     = get_field( 'text' );
$cards    = get_field( 'cards' );
$cta_text = get_field( 'cta_text' );
$cta_link = get_field( 'cta_link' );

// Give editors something to look at before any fields are filled in.
if ( $is_preview && ! $heading && ! $text && empty( $cards ) ) {
	$heading = __( "What's your growing ecosystem?", 'gt' );
	$text    = __( 'Add a heading, some intro text and a few cards in the sidebar to build this section.', 'gt' );
}

if ( ! $heading && ! $text && empty( $cards ) && ! $cta_text ) {
	return;
}

$block_id = ! empty( $block['anchor'] ) ? $block['anchor'] : 'ecosystem-' . sanitize_title( $block['id'] );
$title_id = $block_id . '-title';

$classes = array( 'b-ecosystem' );

if ( ! empty( $block['className'] ) ) {
	$classes[] = $block['className'];
}

// More than three cards will not fit the designed row, so it becomes a slider.
$is_slider = is_array( $cards ) && count( $cards ) > 3;

if ( $is_slider ) {
	$classes[] = 'b-ecosystem--slider';

	// Only pages that actually need the carousel pay for the script.
	if ( ! $is_preview ) {
		wp_enqueue_script( 'gt-block-slider' );
	}
}
?>

<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
	id="<?php echo esc_attr( $block_id ); ?>"
	<?php echo $heading ? 'aria-labelledby="' . esc_attr( $title_id ) . '"' : ''; ?>>

	<div class="b-ecosystem__inner">

		<?php if ( $heading || $text ) : ?>
			<div class="b-ecosystem__head">
				<?php if ( $heading ) : ?>
					<h2 class="b-ecosystem__title" id="<?php echo esc_attr( $title_id ); ?>">
						<?php echo esc_html( $heading ); ?>
					</h2>
				<?php endif; ?>

				<?php if ( $text ) : ?>
					<p class="b-ecosystem__text"><?php echo wp_kses_post( $text ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $cards ) ) : ?>
			<ul class="b-ecosystem__cards"
				<?php if ( $is_slider && ! $is_preview ) : ?>
					data-block-slider data-slides="3" data-slides-md="2" data-slides-sm="1" data-arrows="1" data-dots="1"
				<?php endif; ?>>

				<?php
				foreach ( $cards as $index => $card ) :
					$image     = ! empty( $card['image'] ) ? (int) $card['image'] : 0;
					$card_text = ! empty( $card['cta_text'] ) ? $card['cta_text'] : '';
					$card_link = ! empty( $card['cta_link'] ) && is_array( $card['cta_link'] ) ? $card['cta_link'] : null;
					?>
					<li class="b-ecosystem__card">
						<?php
						if ( $image ) {
							/*
							 * gt-card (2x) and gt-card-sm (1x) share the card's
							 * aspect ratio, so WordPress builds a real srcset from
							 * them; `sizes` gives it the true display width.
							 *
							 * The first card is left to core's loading optimisation,
							 * which knows where the block sits on the page and may
							 * mark it as the LCP image. Everything after it is lazy
							 * regardless — those cards are never the LCP.
							 */
							$img_attr = array(
								'class' => 'b-ecosystem__card-img',
								'sizes' => '(max-width: 767px) 84vw, (max-width: 1265px) 44vw, 413px',
							);

							if ( $index > 0 ) {
								$img_attr['loading'] = 'lazy';
							}

							echo wp_get_attachment_image( $image, 'gt-card', false, $img_attr );
						}
						?>

						<?php if ( $card_text && $card_link && ! empty( $card_link['url'] ) ) : ?>
							<a class="btn-flat b-ecosystem__card-cta"
								href="<?php echo esc_url( $card_link['url'] ); ?>"
								<?php echo ! empty( $card_link['target'] ) ? 'target="' . esc_attr( $card_link['target'] ) . '" rel="noopener"' : ''; ?>>
								<span><?php echo esc_html( $card_text ); ?></span>
								<?php gt_arrow_svg(); ?>
							</a>
						<?php elseif ( $card_text ) : ?>
							<span class="btn-flat b-ecosystem__card-cta">
								<span><?php echo esc_html( $card_text ); ?></span>
								<?php gt_arrow_svg(); ?>
							</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $cta_text && $cta_link && ! empty( $cta_link['url'] ) ) : ?>
			<div class="b-ecosystem__cta">
				<a class="btn-flat btn-flat--dark"
					href="<?php echo esc_url( $cta_link['url'] ); ?>"
					<?php echo ! empty( $cta_link['target'] ) ? 'target="' . esc_attr( $cta_link['target'] ) . '" rel="noopener"' : ''; ?>>
					<span><?php echo esc_html( $cta_text ); ?></span>
					<?php gt_arrow_svg(); ?>
				</a>
			</div>
		<?php endif; ?>

	</div>
</section>
