<?php
/**
 * Block: Guide Hotspots — Figma 384:2174 (Frame 156).
 *
 * A heading and a line of copy over a wide photo with guide links pinned onto
 * it. Positions are set by dragging the chip in the editor, the same map the
 * Feature Band uses. Each pin carries its own small icon, as the design draws
 * a different glyph on every chip. More than one photo turns the band into a
 * slider with the shared progress bar and arrows.
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (unused).
 * @param bool   $is_preview True when rendering in the editor.
 */

$heading = (string) get_field( 'heading' );   // the fallback for slides with no copy
$text    = (string) get_field( 'text' );
$photos  = get_field( 'photos' );
$is_demo = ! empty( $block['data']['is_example'] );

if ( $is_demo ) {
	$heading = __( 'Explore propagation guides.', 'gt' );
	$text    = __( 'Click any point in the photo below to read the full step-by-step guide.', 'gt' );
}

$photos = is_array( $photos ) ? array_values( array_filter( $photos, function ( $photo ) {
	return ! empty( $photo['image'] );
} ) ) : array();

if ( ! $photos && ! $is_demo ) {
	if ( $is_preview ) {
		echo '<p class="block-empty">' . esc_html__( 'Guide Hotspots — add a photo and pin some guides onto it.', 'gt' ) . '</p>';
	}
	return;
}

// One photo needs no slider, so the page ships no slider script for it.
$is_slider = count( $photos ) > 1;
if ( $is_slider && ! $is_preview ) {
	wp_enqueue_script( 'gt-block-slider' );
}

// One copy panel per slide, stacked above the track and swapped with it.
$panels = array();
foreach ( $photos as $photo ) {
	$panels[] = array(
		'heading' => '' !== trim( (string) ( $photo['heading'] ?? '' ) ) ? trim( (string) $photo['heading'] ) : $heading,
		'text'    => '' !== trim( (string) ( $photo['text'] ?? '' ) ) ? trim( (string) $photo['text'] ) : $text,
	);
}
if ( ! $panels ) {
	$panels[] = array( 'heading' => $heading, 'text' => $text );
}

$classes = 'b-hotspots';
if ( ! empty( $block['className'] ) ) {
	$classes .= ' ' . sanitize_html_class( $block['className'] );
}
$anchor   = ! empty( $block['anchor'] ) ? ' id="' . esc_attr( $block['anchor'] ) . '"' : '';
$title_id = 'hotspots-' . ( ! empty( $block['id'] ) ? sanitize_title( $block['id'] ) : 'title' );
?>
<section class="<?php echo esc_attr( $classes ); ?>"<?php echo $anchor; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php
	$has_heading = '' !== trim( $heading );
	foreach ( $photos as $photo ) {
		$has_heading = $has_heading || '' !== trim( (string) ( $photo['heading'] ?? '' ) );
	}
	echo $has_heading ? ' aria-labelledby="' . esc_attr( $title_id ) . '"' : ' aria-label="' . esc_attr__( 'Guides in pictures', 'gt' ) . '"';
	?>>
	<div class="b-hotspots__inner" data-slider-scope>

		<?php if ( $is_slider && ! $is_preview ) : ?>
			<?php // Slick appends its dots and arrows into these, scoped to this block. ?>
			<div class="b-hotspots__controls">
				<div class="b-hotspots__progress" data-slider-dots></div>
				<div class="b-hotspots__arrows" data-slider-arrows></div>
			</div>
		<?php endif; ?>

		<div class="b-hotspots__copy-set"<?php echo $is_slider && ! $is_preview ? ' data-slider-copy' : ''; ?>>
			<?php foreach ( $panels as $i => $panel ) : ?>
				<?php if ( '' === $panel['heading'] && '' === $panel['text'] ) { continue; } ?>
				<div class="b-hotspots__copy<?php echo 0 === $i ? ' is-active' : ''; ?>"<?php echo $is_slider && ! $is_preview && 0 !== $i ? ' aria-hidden="true"' : ''; ?>>
					<?php if ( '' !== $panel['heading'] ) : ?>
						<h2 class="b-hotspots__title"<?php echo 0 === $i ? ' id="' . esc_attr( $title_id ) . '"' : ''; ?>><?php echo esc_html( $panel['heading'] ); ?></h2>
					<?php endif; ?>
					<?php if ( '' !== $panel['text'] ) : ?>
						<p class="b-hotspots__text"><?php echo wp_kses_post( $panel['text'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<ul class="b-hotspots__photos"
			<?php if ( $is_slider && ! $is_preview ) : ?>
				data-block-slider
				data-slides="1"
				data-slides-md="1"
				data-slides-sm="1"
				data-arrows="1"
				data-progress="1"
			<?php endif; ?>>
			<?php foreach ( $photos as $photo ) : ?>
				<?php
				$image = $photo['image'];
				$pins  = isset( $photo['pins'] ) && is_array( $photo['pins'] ) ? $photo['pins'] : array();
				?>
				<li class="b-hotspots__photo">
					<div class="b-hotspots__stage">
						<?php
						echo wp_get_attachment_image( is_array( $image ) ? $image['ID'] : $image, 'gt-academy-photo', false, array(
							'class'   => 'b-hotspots__img',
							'alt'     => '',
							'sizes'   => '(max-width: 1439px) calc(100vw - 100px), 1340px',
							'loading' => 'lazy',
						) );
						?>

						<?php foreach ( $pins as $pin ) : ?>
							<?php
							$guide = isset( $pin['guide'] ) ? get_post( $pin['guide'] ) : null;
							$label = isset( $pin['label'] ) ? trim( (string) $pin['label'] ) : '';
							if ( ! $guide && '' === $label ) {
								continue;
							}
							$label = '' !== $label ? $label : get_the_title( $guide );
							$x     = max( 0, min( 100, (float) ( $pin['x'] ?? 50 ) ) );
							$y     = max( 0, min( 100, (float) ( $pin['y'] ?? 50 ) ) );
							$style = sprintf( 'left:%s%%;top:%s%%;', esc_attr( (string) $x ), esc_attr( (string) $y ) );
							?>
							<?php
							$icon_id = isset( $pin['icon'] ) ? $pin['icon'] : 0;
							$icon_id = is_array( $icon_id ) ? (int) ( $icon_id['ID'] ?? 0 ) : (int) $icon_id;
							$tag     = $guide ? 'a' : 'span';
							$href    = $guide ? ' href="' . esc_url( get_permalink( $guide ) ) . '"' : '';
							?>
							<<?php echo esc_html( $tag ); ?> class="b-hotspots__pin<?php echo $guide ? '' : ' is-static'; ?>"<?php
								echo $href; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?> style="<?php echo esc_attr( $style ); ?>">
								<?php if ( $icon_id ) : ?>
									<span class="b-hotspots__pin-icon">
										<?php echo wp_get_attachment_image( $icon_id, 'full', false, array( 'alt' => '', 'loading' => 'lazy' ) ); ?>
									</span>
								<?php endif; ?>
								<span class="b-hotspots__pin-label"><?php echo esc_html( $label ); ?></span>
							</<?php echo esc_html( $tag ); ?>>
						<?php endforeach; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>
