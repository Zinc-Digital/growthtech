<?php
/**
 * Block: Content Slider — Figma "Frame 205" (384:4540).
 *
 * Copy and two calls to action on the left, a slider of brand cards on the
 * right with a progress indicator and arrows beneath it.
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (unused).
 * @param bool   $is_preview True when rendering in the editor.
 */

$heading   = get_field( 'heading' );
$text      = get_field( 'text' );
$btn1_link = get_field( 'button_one_link' );
$btn2_link = get_field( 'button_two_link' );

/*
 * The label comes from the block's own title field, falling back to the title
 * typed into the Link modal — editors reasonably use either. A button with a
 * label but no URL still renders (as plain text), rather than disappearing with
 * no indication of why.
 */
$btn1_text = get_field( 'button_one_title' );
$btn2_text = get_field( 'button_two_title' );

if ( ! $btn1_text && is_array( $btn1_link ) && ! empty( $btn1_link['title'] ) ) {
	$btn1_text = $btn1_link['title'];
}

if ( ! $btn2_text && is_array( $btn2_link ) && ! empty( $btn2_link['title'] ) ) {
	$btn2_text = $btn2_link['title'];
}

$btn1_url = is_array( $btn1_link ) && ! empty( $btn1_link['url'] ) ? $btn1_link['url'] : '';
$btn2_url = is_array( $btn2_link ) && ! empty( $btn2_link['url'] ) ? $btn2_link['url'] : '';
$slides    = get_field( 'slides' );

// Slider settings, editable per instance.
$per_desktop = max( 1, (int) ( get_field( 'slides_desktop' ) ?: 2 ) );
$per_tablet  = max( 1, (int) ( get_field( 'slides_tablet' ) ?: 2 ) );
$per_mobile  = max( 1, (int) ( get_field( 'slides_mobile' ) ?: 1 ) );
$autoplay    = (bool) get_field( 'autoplay' );
$speed       = (float) ( get_field( 'autoplay_speed' ) ?: 6 );
$autoplay_ms = $autoplay ? (int) round( max( 1, $speed ) * 1000 ) : 0;

// Give editors something to look at before any fields are filled in.
if ( $is_preview && ! $heading && ! $text && empty( $slides ) ) {
	$heading = __( 'Formulated by Growth Technology', 'gt' );
	$text    = __( 'Add a heading, some text and a few slides in the sidebar to build this section.', 'gt' );
}

if ( ! $heading && ! $text && empty( $slides ) ) {
	return;
}

/*
 * ACF derives $block['id'] from the block's attributes, so two identical
 * instances on one page would share it — and with it the section id and the
 * aria-labelledby target. wp_unique_id() guarantees one per render.
 */
$block_id = ! empty( $block['anchor'] ) ? $block['anchor'] : wp_unique_id( 'content-slider-' );
$title_id = $block_id . '-title';

$classes = array( 'b-content-slider' );

if ( ! empty( $block['className'] ) ) {
	$classes[] = $block['className'];
}

// The slider only earns its script when there is more than a screenful.
$is_slider = is_array( $slides ) && count( $slides ) > $per_desktop;

if ( $is_slider && ! $is_preview ) {
	wp_enqueue_script( 'gt-block-slider' );
}
?>

<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
	id="<?php echo esc_attr( $block_id ); ?>"
	<?php echo $heading ? 'aria-labelledby="' . esc_attr( $title_id ) . '"' : ''; ?>>

	<div class="b-content-slider__inner" data-slider-scope>

		<div class="b-content-slider__content">
			<?php if ( $heading ) : ?>
				<h2 class="b-content-slider__title" id="<?php echo esc_attr( $title_id ); ?>">
					<?php echo esc_html( $heading ); ?>
				</h2>
			<?php endif; ?>

			<?php if ( $text ) : ?>
				<p class="b-content-slider__text"><?php echo wp_kses_post( $text ); ?></p>
			<?php endif; ?>

			<?php if ( $btn1_text || $btn2_text ) : ?>
				<div class="b-content-slider__actions">
					<?php if ( $btn1_text ) : ?>
						<?php if ( $btn1_url ) : ?>
							<a class="btn-flat btn-flat--dark" href="<?php echo esc_url( $btn1_url ); ?>"
								<?php echo ! empty( $btn1_link['target'] ) ? 'target="' . esc_attr( $btn1_link['target'] ) . '" rel="noopener"' : ''; ?>>
								<span><?php echo esc_html( $btn1_text ); ?></span>
								<?php gt_arrow_svg(); ?>
							</a>
						<?php else : ?>
							<span class="btn-flat btn-flat--dark">
								<span><?php echo esc_html( $btn1_text ); ?></span>
								<?php gt_arrow_svg(); ?>
							</span>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( $btn2_text ) : ?>
						<?php if ( $btn2_url ) : ?>
							<a class="b-content-slider__link" href="<?php echo esc_url( $btn2_url ); ?>"
								<?php echo ! empty( $btn2_link['target'] ) ? 'target="' . esc_attr( $btn2_link['target'] ) . '" rel="noopener"' : ''; ?>>
								<?php echo esc_html( $btn2_text ); ?>
							</a>
						<?php else : ?>
							<span class="b-content-slider__link"><?php echo esc_html( $btn2_text ); ?></span>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $slides ) ) : ?>
			<div class="b-content-slider__media">
				<ul class="b-content-slider__slides"
					<?php if ( $is_slider && ! $is_preview ) : ?>
						data-block-slider
						data-slides="<?php echo esc_attr( $per_desktop ); ?>"
						data-slides-md="<?php echo esc_attr( $per_tablet ); ?>"
						data-slides-sm="<?php echo esc_attr( $per_mobile ); ?>"
						data-arrows="1"
						data-progress="1"
						data-autoplay="<?php echo esc_attr( $autoplay_ms ); ?>"
					<?php endif; ?>>

					<?php
					foreach ( $slides as $index => $slide ) :
						$bg          = ! empty( $slide['background'] ) ? (int) $slide['background'] : 0;
						$logo        = ! empty( $slide['image'] ) ? (int) $slide['image'] : 0;
						$slide_title = ! empty( $slide['title'] ) ? $slide['title'] : '';
						$slide_link  = ! empty( $slide['link'] ) && is_array( $slide['link'] ) ? $slide['link'] : null;

						if ( ! $bg && ! $logo && ! $slide_title ) {
							continue;
						}
						?>
						<li class="b-content-slider__slide">
							<?php
							if ( $bg ) {
								/*
								 * gt-slide (2x) and gt-slide-sm (1x) share the card's
								 * aspect ratio, so WordPress builds a real srcset. The
								 * first slide is left to core's loading optimisation;
								 * the rest are always lazy.
								 */
								$img_attr = array(
									'class' => 'b-content-slider__bg',
									'sizes' => '(max-width: 767px) 90vw, (max-width: 1265px) 45vw, 318px',
								);

								if ( $index > 0 ) {
									$img_attr['loading'] = 'lazy';
								}

								echo wp_get_attachment_image( $bg, 'gt-slide', false, $img_attr );
							}
							?>

							<span class="b-content-slider__scrim" aria-hidden="true"></span>

							<div class="b-content-slider__slide-body">
								<?php
								if ( $logo ) {
									/*
									 * The logo repeats the brand name in the title and
									 * link below it, so it is decorative here.
									 *
									 * SVGs are printed directly: WordPress cannot read
									 * their dimensions and emits width="1" height="1",
									 * which collapses the logo to a dot.
									 */
									if ( 'image/svg+xml' === get_post_mime_type( $logo ) ) {
										printf(
											'<img class="b-content-slider__logo" src="%s" alt="" loading="lazy" />',
											esc_url( wp_get_attachment_url( $logo ) )
										);
									} else {
										echo wp_get_attachment_image(
											$logo,
											'medium',
											false,
											array(
												'class'   => 'b-content-slider__logo',
												'alt'     => '',
												'loading' => 'lazy',
											)
										);
									}
								}
								?>

								<?php if ( $slide_title ) : ?>
									<p class="b-content-slider__slide-title"><?php echo esc_html( $slide_title ); ?></p>
								<?php endif; ?>

								<?php if ( $slide_link && ! empty( $slide_link['url'] ) ) : ?>
									<a class="b-content-slider__slide-link" href="<?php echo esc_url( $slide_link['url'] ); ?>"
										<?php echo ! empty( $slide_link['target'] ) ? 'target="' . esc_attr( $slide_link['target'] ) . '" rel="noopener"' : ''; ?>>
										<?php echo esc_html( ! empty( $slide_link['title'] ) ? $slide_link['title'] : __( 'Find out more', 'gt' ) ); ?>
									</a>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php if ( $is_slider && ! $is_preview ) : ?>
					<?php // Slick appends its dots and arrows into these, scoped to this block. ?>
					<div class="b-content-slider__controls">
						<div class="b-content-slider__progress" data-slider-dots></div>
						<div class="b-content-slider__arrows" data-slider-arrows></div>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	</div>
</section>
