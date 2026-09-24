<?php
/**
 * Block: Image & Copy — Figma 384:2551 (Frames 183 and 207).
 *
 * An image beside a column of copy: heading, body, an optional dark quote card
 * and up to two calls to action. The image side, the vertical alignment and
 * the band's background are all switchable so one block covers both the
 * "Formulated by experts" and "Forty years of answers" sections.
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (unused).
 * @param bool   $is_preview True when rendering in the editor.
 */

$heading   = (string) get_field( 'heading' );
$text      = (string) get_field( 'text' );
$image_id  = (int) get_field( 'image' );
$side      = get_field( 'image_side' );
$align     = get_field( 'align_items' );
$quote     = (string) get_field( 'quote' );
$quote_tag = (string) get_field( 'quote_eyebrow' );
$cta       = get_field( 'cta_link' );
$link      = get_field( 'text_link' );
$bg        = (string) get_field( 'background' );
$space_top = get_field( 'space_top' );
$space_bot = get_field( 'space_bottom' );

if ( $is_preview && '' === trim( $heading ) && ! $image_id ) {
	$heading = __( 'Formulated by experts. Grown by you.', 'gt' );
	$text    = __( 'Add a heading, some copy and an image in the sidebar to build this section.', 'gt' );
}
if ( '' === trim( $heading ) && '' === trim( $text ) && ! $image_id ) {
	return;
}

$classes = 'b-image-copy';
$classes .= 'right' === $side ? ' b-image-copy--image-right' : '';
$classes .= 'center' === $align ? ' b-image-copy--center' : '';
// Any colour the picker returns is validated before it reaches the style attribute.
$bg = preg_match( '/^#[0-9a-fA-F]{6}$/', $bg ) ? strtoupper( $bg ) : '';
$classes .= $bg ? ' b-image-copy--banded' : '';
if ( ! empty( $block['className'] ) ) {
	$classes .= ' ' . sanitize_html_class( $block['className'] );
}
$anchor = ! empty( $block['anchor'] ) ? ' id="' . esc_attr( $block['anchor'] ) . '"' : '';

// The design spaces sections from above, so each instance carries its own.
$vars = array();
if ( $bg ) {
	$vars[] = '--ic-bg: ' . $bg;
}
if ( '' !== $space_top && null !== $space_top ) {
	$vars[] = '--ic-space-top: ' . (int) $space_top . 'px';
}
if ( '' !== $space_bot && null !== $space_bot ) {
	$vars[] = '--ic-space-bottom: ' . (int) $space_bot . 'px';
}
$style = $vars ? ' style="' . esc_attr( implode( '; ', $vars ) ) . '"' : '';
?>
<section class="<?php echo esc_attr( $classes ); ?>"<?php echo $anchor . $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="b-image-copy__inner">
		<?php if ( $image_id ) : ?>
			<div class="b-image-copy__media">
				<?php
				echo wp_get_attachment_image( $image_id, 'gt-split', false, array(
					'class'   => 'b-image-copy__img',
					'alt'     => '',
					'sizes'   => '(max-width: 1023px) calc(100vw - 100px), 544px',
					'loading' => 'lazy',
				) );
				?>
			</div>
		<?php endif; ?>

		<div class="b-image-copy__content">
			<?php if ( trim( $heading ) ) : ?>
				<h2 class="b-image-copy__title"><?php echo nl2br( esc_html( $heading ) ); ?></h2>
			<?php endif; ?>

			<?php if ( trim( wp_strip_all_tags( $text ) ) ) : ?>
				<div class="b-image-copy__text"><?php echo wp_kses_post( $text ); ?></div>
			<?php endif; ?>

			<?php if ( trim( $quote ) ) : ?>
				<figure class="b-image-copy__quote">
					<blockquote class="b-image-copy__quote-text"><p><?php echo nl2br( esc_html( $quote ) ); ?></p></blockquote>
					<?php if ( trim( $quote_tag ) ) : ?>
						<figcaption class="b-image-copy__quote-tag"><?php echo esc_html( $quote_tag ); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endif; ?>

			<?php if ( ( is_array( $cta ) && ! empty( $cta['url'] ) ) || ( is_array( $link ) && ! empty( $link['url'] ) ) ) : ?>
				<div class="b-image-copy__actions">
					<?php if ( is_array( $cta ) && ! empty( $cta['url'] ) ) : ?>
						<a class="btn-flat btn-flat--dark" href="<?php echo esc_url( $cta['url'] ); ?>"
							<?php echo ! empty( $cta['target'] ) ? 'target="' . esc_attr( $cta['target'] ) . '" rel="noopener"' : ''; ?>>
							<span><?php echo esc_html( ! empty( $cta['title'] ) ? $cta['title'] : __( 'Read more', 'gt' ) ); ?></span>
							<?php gt_arrow_svg(); ?>
						</a>
					<?php endif; ?>
					<?php if ( is_array( $link ) && ! empty( $link['url'] ) ) : ?>
						<a class="b-image-copy__link" href="<?php echo esc_url( $link['url'] ); ?>"
							<?php echo ! empty( $link['target'] ) ? 'target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; ?>>
							<?php echo esc_html( ! empty( $link['title'] ) ? $link['title'] : __( 'Find out more', 'gt' ) ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
