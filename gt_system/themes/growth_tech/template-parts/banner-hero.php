<?php
/**
 * Hero banner — Figma "Frame 62" (165:2267) over "Rectangle 9" (165:2263).
 *
 * Slides come from the ACF "Hero Banner" repeater on the page. One row renders
 * a static banner; two or more turn it into a Slick slider.
 *
 * The shape is wider than the viewport (1724 on a 1440 frame) and clipped to an
 * ellipse at the bottom, which is what produces the curved edge. The overhang is
 * symmetrical, so a centred container lands on the design's 50px gutter.
 */

$slides = function_exists( 'get_field' ) ? get_field( 'hero_slides' ) : array();

if ( empty( $slides ) ) {
	return;
}

$multiple = count( $slides ) > 1;

// Banner-wide slide duration in seconds, editable on the page. Falls back to 6
// so an unset field still behaves sensibly.
$duration = function_exists( 'get_field' ) ? (float) get_field( 'slide_duration' ) : 0;

if ( $duration <= 0 ) {
	$duration = 6;
}

$duration_ms = (int) round( $duration * 1000 );
?>

<section class="banner-hero" data-header-hero>
	<div class="banner-hero__shape">
		<div class="banner-hero__slider<?php echo $multiple ? ' banner-hero__slider--multi' : ''; ?>"
			<?php echo $multiple ? 'data-hero-slider' : ''; ?>
			data-autoplay="<?php echo esc_attr( $duration_ms ); ?>">

			<?php foreach ( $slides as $i => $slide ) : ?>
				<?php
				$image   = ! empty( $slide['image'] ) ? (int) $slide['image'] : 0;
				$heading = ! empty( $slide['heading'] ) ? $slide['heading'] : '';
				$text    = ! empty( $slide['text'] ) ? $slide['text'] : '';
				$link    = ! empty( $slide['link'] ) && is_array( $slide['link'] ) ? $slide['link'] : null;

				// Optional per-slide override of the banner-wide duration.
				$slide_seconds = ! empty( $slide['duration'] ) ? (float) $slide['duration'] : 0;
				$slide_ms      = $slide_seconds > 0 ? (int) round( $slide_seconds * 1000 ) : 0;

				// The first slide carries the page's h1; the rest are not headings
				// in the document outline, so they use a plain element.
				$title_tag = ( 0 === $i ) ? 'h1' : 'p';
				?>
				<div class="banner-hero__slide"
					<?php echo $slide_ms ? 'data-duration="' . esc_attr( $slide_ms ) . '"' : ''; ?>>
					<?php
					if ( $image ) {
						echo wp_get_attachment_image(
							$image,
							'full',
							false,
							array(
								'class'         => 'banner-hero__img',
								'loading'       => ( 0 === $i ) ? 'eager' : 'lazy',
								'fetchpriority' => ( 0 === $i ) ? 'high' : 'auto',
								'alt'           => '',
							)
						);
					}
					?>

					<span class="banner-hero__scrim" aria-hidden="true"></span>

					<div class="banner-hero__container">
						<div class="banner-hero__content">
							<?php if ( $heading ) : ?>
								<<?php echo $title_tag; ?> class="banner-hero__title">
									<?php echo esc_html( $heading ); ?>
								</<?php echo $title_tag; ?>>
							<?php endif; ?>

							<?php if ( $text ) : ?>
								<p class="banner-hero__text"><?php echo wp_kses_post( $text ); ?></p>
							<?php endif; ?>

							<?php if ( $link && ! empty( $link['url'] ) ) : ?>
								<a class="banner-hero__cta" href="<?php echo esc_url( $link['url'] ); ?>"
									<?php echo ! empty( $link['target'] ) ? 'target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; ?>>
									<span class="banner-hero__cta-label">
										<?php echo esc_html( ! empty( $link['title'] ) ? $link['title'] : __( 'Find out more', 'gt' ) ); ?>
									</span>
									<svg class="banner-hero__cta-arrow" viewBox="0 0 21 18" width="21" height="18"
										focusable="false" aria-hidden="true">
										<path fill="currentColor"
											d="M20.6531 9.81831C20.8734 9.60737 21 9.31205 21 9.00268C21 8.6933 20.8734 8.40268 20.6531 8.18706L12.4031 0.312055C11.9531 -0.119195 11.2406 -0.100445 10.8141 0.349555C10.3875 0.799555 10.4016 1.51206 10.8516 1.93862L17.0672 7.87768H1.125C0.501562 7.87768 0 8.37924 0 9.00268C0 9.62612 0.501562 10.1277 1.125 10.1277H17.0672L10.8469 16.0621C10.3969 16.4933 10.3828 17.2012 10.8094 17.6512C11.2359 18.1012 11.9484 18.1152 12.3984 17.6887L20.6484 9.81362L20.6531 9.81831Z" />
									</svg>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<?php if ( $multiple ) : ?>
		<?php // Slick appends its dots here so they line up with the content column. ?>
		<div class="banner-hero__dots" data-hero-dots></div>
	<?php endif; ?>
</section>
