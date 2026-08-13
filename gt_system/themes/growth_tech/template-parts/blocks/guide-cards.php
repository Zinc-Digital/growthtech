<?php
/**
 * Block: Guide Cards — Figma "Frame 29" (165:2385) + "Frame 28" (165:2368).
 *
 * Centred intro, then a row of image cards each carrying a translucent panel
 * with a title, a run-on line of text and a link. More than three cards turns
 * the row into a Slick slider with arrows.
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
	$heading = __( 'Become the expert your plants think you are.', 'gt' );
	$text    = __( 'Add a heading, some intro text and a few cards in the sidebar to build this section.', 'gt' );
}

if ( ! $heading && ! $text && empty( $cards ) && ! $cta_text ) {
	return;
}

$block_id = ! empty( $block['anchor'] ) ? $block['anchor'] : 'guide-cards-' . sanitize_title( $block['id'] );
$title_id = $block_id . '-title';

$classes = array( 'b-guide-cards' );

if ( ! empty( $block['className'] ) ) {
	$classes[] = $block['className'];
}

// Three fit the designed row; beyond that it has to scroll.
$is_slider = is_array( $cards ) && count( $cards ) > 3;

if ( $is_slider ) {
	$classes[] = 'b-guide-cards--slider';

	// Only pages that need the carousel pay for the script.
	if ( ! $is_preview ) {
		wp_enqueue_script( 'gt-block-slider' );
	}
}
?>

<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
	id="<?php echo esc_attr( $block_id ); ?>"
	<?php echo $heading ? 'aria-labelledby="' . esc_attr( $title_id ) . '"' : ''; ?>>

	<div class="b-guide-cards__inner">

		<?php if ( $heading || $text ) : ?>
			<div class="b-guide-cards__head">
				<?php if ( $heading ) : ?>
					<h2 class="b-guide-cards__title" id="<?php echo esc_attr( $title_id ); ?>">
						<?php echo esc_html( $heading ); ?>
					</h2>
				<?php endif; ?>

				<?php if ( $text ) : ?>
					<p class="b-guide-cards__text"><?php echo wp_kses_post( $text ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $cards ) ) : ?>
			<ul class="b-guide-cards__cards"
				<?php if ( $is_slider && ! $is_preview ) : ?>
					data-block-slider data-slides="3" data-slides-md="2" data-slides-sm="1" data-arrows="1"
				<?php endif; ?>>

				<?php
				foreach ( $cards as $index => $card ) :
					$image      = ! empty( $card['image'] ) ? (int) $card['image'] : 0;
					$card_title = ! empty( $card['title'] ) ? $card['title'] : '';
					$card_text  = ! empty( $card['text'] ) ? $card['text'] : '';
					$card_link  = ! empty( $card['cta_link'] ) && is_array( $card['cta_link'] ) ? $card['cta_link'] : null;

					if ( ! $card_title && ! $card_text && ! $image ) {
						continue;
					}
					?>
					<li class="b-guide-cards__card">
						<?php
						if ( $image ) {
							/*
							 * gt-guide (2x) and gt-guide-sm (1x) share the card's
							 * aspect ratio, so WordPress builds a real srcset.
							 * The first card is left to core's loading
							 * optimisation; the rest are always lazy.
							 */
							$img_attr = array(
								'class' => 'b-guide-cards__card-img',
								'sizes' => '(max-width: 767px) 84vw, (max-width: 1265px) 44vw, 347px',
							);

							if ( $index > 0 ) {
								$img_attr['loading'] = 'lazy';
							}

							echo wp_get_attachment_image( $image, 'gt-guide', false, $img_attr );
						}
						?>

						<div class="b-guide-cards__panel">
							<?php if ( $card_title || $card_text ) : ?>
								<p class="b-guide-cards__card-title">
									<?php if ( $card_title ) : ?>
										<strong><?php echo esc_html( $card_title ); ?></strong>
									<?php endif; ?>
									<?php echo $card_text ? ' ' . esc_html( $card_text ) : ''; ?>
								</p>
							<?php endif; ?>

							<?php if ( $card_link && ! empty( $card_link['url'] ) ) : ?>
								<?php
								$card_label = ! empty( $card_link['title'] ) ? $card_link['title'] : __( 'Read the Guide', 'gt' );
								?>
								<a class="b-guide-cards__card-link"
									href="<?php echo esc_url( $card_link['url'] ); ?>"
									<?php echo ! empty( $card_link['target'] ) ? 'target="' . esc_attr( $card_link['target'] ) . '" rel="noopener"' : ''; ?>>
									<?php echo esc_html( $card_label ); ?>
									<?php if ( $card_title ) : ?>
										<span class="screen-reader-text"><?php echo esc_html( ': ' . $card_title . ' ' . $card_text ); ?></span>
									<?php endif; ?>
								</a>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $cta_text && $cta_link && ! empty( $cta_link['url'] ) ) : ?>
			<div class="b-guide-cards__cta">
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
