<?php
/**
 * Block: Page Hero — Figma 384:2551 (the About Us banner).
 *
 * A full-bleed image behind a darkening scrim, with the title, a paragraph and
 * up to two calls to action held in the left-hand column.
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (unused).
 * @param bool   $is_preview True when rendering in the editor.
 */

$heading  = (string) get_field( 'heading' );
$text     = (string) get_field( 'text' );
$image_id = (int) get_field( 'image' );
$cta      = get_field( 'cta_link' );
$link     = get_field( 'text_link' );
$height   = (int) get_field( 'height' );
$height   = $height > 0 ? $height : 690;

if ( $is_preview && '' === trim( $heading ) && ! $image_id ) {
	$heading = __( 'Rooted in 40 years of science', 'gt' );
	$text    = __( 'Add a heading, some copy and a background image in the sidebar to build this banner.', 'gt' );
}
if ( '' === trim( $heading ) && '' === trim( $text ) && ! $image_id ) {
	return;
}

$classes = 'b-page-hero';
if ( ! empty( $block['className'] ) ) {
	$classes .= ' ' . sanitize_html_class( $block['className'] );
}
$anchor = ! empty( $block['anchor'] ) ? ' id="' . esc_attr( $block['anchor'] ) . '"' : '';
?>
<section class="<?php echo esc_attr( $classes ); ?>"<?php echo $anchor; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	style="--hero-height: <?php echo (int) $height; ?>px">
	<?php
	if ( $image_id ) {
		echo wp_get_attachment_image( $image_id, 'gt-brand-hero', false, array(
			'class' => 'b-page-hero__img',
			'alt'   => '',
			'sizes' => '100vw',
		) );
	}
	?>
	<span class="b-page-hero__scrim" aria-hidden="true"></span>

	<div class="b-page-hero__inner">
		<div class="b-page-hero__content">
			<?php if ( trim( $heading ) ) : ?>
				<h1 class="b-page-hero__title"><?php echo nl2br( esc_html( $heading ) ); ?></h1>
			<?php endif; ?>
			<?php if ( trim( $text ) ) : ?>
				<p class="b-page-hero__text"><?php echo nl2br( esc_html( $text ) ); ?></p>
			<?php endif; ?>

			<?php if ( ( is_array( $cta ) && ! empty( $cta['url'] ) ) || ( is_array( $link ) && ! empty( $link['url'] ) ) ) : ?>
				<div class="b-page-hero__actions">
					<?php if ( is_array( $cta ) && ! empty( $cta['url'] ) ) : ?>
						<a class="btn-flat" href="<?php echo esc_url( $cta['url'] ); ?>"
							<?php echo ! empty( $cta['target'] ) ? 'target="' . esc_attr( $cta['target'] ) . '" rel="noopener"' : ''; ?>>
							<span><?php echo esc_html( ! empty( $cta['title'] ) ? $cta['title'] : __( 'Read more', 'gt' ) ); ?></span>
							<?php gt_arrow_svg(); ?>
						</a>
					<?php endif; ?>
					<?php if ( is_array( $link ) && ! empty( $link['url'] ) ) : ?>
						<a class="b-page-hero__link" href="<?php echo esc_url( $link['url'] ); ?>"
							<?php echo ! empty( $link['target'] ) ? 'target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; ?>>
							<?php echo esc_html( ! empty( $link['title'] ) ? $link['title'] : __( 'Find out more', 'gt' ) ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
