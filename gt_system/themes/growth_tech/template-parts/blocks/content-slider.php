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

// The dark instance — Figma 384:2551 on About Us — is the same component on
// black, with a wider copy column than the light one on the homepage.
if ( 'dark' === get_field( 'theme' ) ) {
	$classes[] = 'b-content-slider--dark';
}

// Product tiles put the image on a solid ground with the name beneath, rather
// than filling the card with a photo — Figma 384:2215 on Plant Academy.
$slide_style = 'tile' === get_field( 'slide_style' ) ? 'tile' : 'photo';
if ( 'tile' === $slide_style ) {
	$classes[] = 'b-content-slider--tiles';
}

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
	<?php
	$any_heading = '' !== trim( (string) $heading );
	if ( is_array( $slides ) ) {
		foreach ( $slides as $slide ) {
			$any_heading = $any_heading || '' !== trim( (string) ( $slide['heading'] ?? '' ) );
		}
	}
	echo $any_heading ? 'aria-labelledby="' . esc_attr( $title_id ) . '"' : '';
	?>>

	<div class="b-content-slider__inner" data-slider-scope>

		<?php
		/**
		 * Copy belongs to the slide when a slide sets any of its own, so the
		 * column changes as the cards advance. Every slide's copy is rendered
		 * and the slider shows one at a time — without JavaScript the first is
		 * the one on show. Blocks whose slides carry no copy render exactly
		 * one panel from the block's own heading and text, as they always did.
		 */
		$blank      = array( 'heading' => '', 'text' => '', 'btn1' => '', 'btn1_link' => null, 'btn2' => '', 'btn2_link' => null );
		$slide_copy = array();
		if ( is_array( $slides ) ) {
			foreach ( $slides as $slide ) {
				$slide_copy[] = array(
					'heading'   => trim( (string) ( $slide['heading'] ?? '' ) ),
					'text'      => trim( (string) ( $slide['text'] ?? '' ) ),
					'btn1'      => trim( (string) ( $slide['button_one_title'] ?? '' ) ),
					'btn1_link' => $slide['button_one_link'] ?? null,
					'btn2'      => trim( (string) ( $slide['button_two_title'] ?? '' ) ),
					'btn2_link' => $slide['button_two_link'] ?? null,
				);
			}
		}
		$per_slide_copy = (bool) array_filter( $slide_copy, function ( $c ) {
			return '' !== $c['heading'] || '' !== $c['text'] || '' !== $c['btn1'] || '' !== $c['btn2'];
		} );
		$panels = $per_slide_copy ? $slide_copy : array( $blank );
		?>
		<div class="b-content-slider__content">
			<div class="b-content-slider__copy-set"<?php echo $per_slide_copy ? ' data-slider-copy' : ''; ?>>
				<?php foreach ( $panels as $i => $panel ) : ?>
					<?php
					// Each part falls back on its own, so a slide can change
					// just its heading, or just a button, and inherit the rest.
					$panel_heading = '' !== $panel['heading'] ? $panel['heading'] : $heading;
					$panel_text    = '' !== $panel['text'] ? $panel['text'] : $text;
					$p_btn1        = '' !== $panel['btn1'] ? $panel['btn1'] : $btn1_text;
					$p_btn2        = '' !== $panel['btn2'] ? $panel['btn2'] : $btn2_text;
					$p_btn1_link   = is_array( $panel['btn1_link'] ) && ! empty( $panel['btn1_link']['url'] ) ? $panel['btn1_link'] : $btn1_link;
					$p_btn2_link   = is_array( $panel['btn2_link'] ) && ! empty( $panel['btn2_link']['url'] ) ? $panel['btn2_link'] : $btn2_link;
					$p_btn1_url    = is_array( $p_btn1_link ) ? ( $p_btn1_link['url'] ?? '' ) : '';
					$p_btn2_url    = is_array( $p_btn2_link ) ? ( $p_btn2_link['url'] ?? '' ) : '';
					if ( '' === $panel_heading && '' === $panel_text && '' === $p_btn1 && '' === $p_btn2 ) {
						continue;
					}
					?>
					<div class="b-content-slider__copy<?php echo 0 === $i ? ' is-active' : ''; ?>"<?php echo $per_slide_copy && 0 !== $i ? ' aria-hidden="true"' : ''; ?>>
						<?php if ( $panel_heading ) : ?>
							<h2 class="b-content-slider__title"<?php echo 0 === $i ? ' id="' . esc_attr( $title_id ) . '"' : ''; ?>>
								<?php echo esc_html( $panel_heading ); ?>
							</h2>
						<?php endif; ?>

						<?php if ( $panel_text ) : ?>
							<p class="b-content-slider__text"><?php echo wp_kses_post( $panel_text ); ?></p>
						<?php endif; ?>

						<?php if ( $p_btn1 || $p_btn2 ) : ?>
							<div class="b-content-slider__actions">
								<?php if ( $p_btn1 ) : ?>
									<?php if ( $p_btn1_url ) : ?>
										<a class="btn-flat btn-flat--dark" href="<?php echo esc_url( $p_btn1_url ); ?>"
											<?php echo ! empty( $p_btn1_link['target'] ) ? 'target="' . esc_attr( $p_btn1_link['target'] ) . '" rel="noopener"' : ''; ?>>
											<span><?php echo esc_html( $p_btn1 ); ?></span>
											<?php gt_arrow_svg(); ?>
										</a>
									<?php else : ?>
										<span class="btn-flat btn-flat--dark">
											<span><?php echo esc_html( $p_btn1 ); ?></span>
											<?php gt_arrow_svg(); ?>
										</span>
									<?php endif; ?>
								<?php endif; ?>

								<?php if ( $p_btn2 ) : ?>
									<?php if ( $p_btn2_url ) : ?>
										<a class="b-content-slider__link" href="<?php echo esc_url( $p_btn2_url ); ?>"
											<?php echo ! empty( $p_btn2_link['target'] ) ? 'target="' . esc_attr( $p_btn2_link['target'] ) . '" rel="noopener"' : ''; ?>>
											<?php echo esc_html( $p_btn2 ); ?>
										</a>
									<?php else : ?>
										<span class="b-content-slider__link"><?php echo esc_html( $p_btn2 ); ?></span>
									<?php endif; ?>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

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
						<?php if ( 'tile' === $slide_style ) : ?>
							<?php
							$slide_meta = isset( $slide['meta'] ) ? trim( (string) $slide['meta'] ) : '';
							$meta_parts = '' !== $slide_meta ? array_filter( array_map( 'trim', explode( '|', $slide_meta ) ) ) : array();
							$has_url    = $slide_link && ! empty( $slide_link['url'] );
							$tile_tag   = $has_url ? 'a' : 'span';
							?>
							<<?php echo esc_html( $tile_tag ); ?> class="b-content-slider__tile-link"<?php
								echo $has_url ? ' href="' . esc_url( $slide_link['url'] ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo $has_url && ! empty( $slide_link['target'] ) ? ' target="' . esc_attr( $slide_link['target'] ) . '" rel="noopener"' : '';
							?>>
								<span class="b-content-slider__tile">
									<?php
									if ( $logo ) {
										// Contained on the solid ground, never cropped.
										echo wp_get_attachment_image( $logo, 'gt-product-card', false, array(
											'class'   => 'b-content-slider__tile-img',
											'alt'     => '',
											'sizes'   => '(max-width: 767px) 90vw, (max-width: 1265px) 45vw, 320px',
											'loading' => $index > 0 ? 'lazy' : 'eager',
										) );
									}
									?>
								</span>

								<span class="b-content-slider__tile-body">
									<?php if ( $slide_title ) : ?>
										<span class="b-content-slider__tile-title"><?php echo esc_html( $slide_title ); ?></span>
									<?php endif; ?>

									<?php if ( $meta_parts ) : ?>
										<span class="b-content-slider__tile-meta">
											<?php foreach ( $meta_parts as $n => $part ) : ?>
												<?php if ( $n > 0 ) : ?>
													<span class="b-content-slider__tile-dot" aria-hidden="true"></span>
												<?php endif; ?>
												<span><?php echo esc_html( $part ); ?></span>
											<?php endforeach; ?>
										</span>
									<?php endif; ?>
								</span>
							</<?php echo esc_html( $tile_tag ); ?>>
						<?php else : ?>

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
						<?php endif; ?>
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
