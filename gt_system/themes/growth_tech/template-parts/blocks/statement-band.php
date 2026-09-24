<?php
/**
 * Block: Statement Band — Figma 384:2551 (Frame 99, "Our goal").
 *
 * A black band carrying a small eyebrow, a large centred statement and a line
 * of supporting copy, over an optional background image.
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (unused).
 * @param bool   $is_preview True when rendering in the editor.
 */

$eyebrow  = (string) get_field( 'eyebrow' );
$heading  = (string) get_field( 'heading' );
$text     = (string) get_field( 'text' );
$image_id = (int) get_field( 'image' );

if ( $is_preview && '' === trim( $heading ) ) {
	$eyebrow = __( 'Our goal', 'gt' );
	$heading = __( 'An accessible, discoverable, human and expert voice in UK hydroponics.', 'gt' );
}
if ( '' === trim( $heading ) ) {
	return;
}

$classes = 'b-statement-band';
if ( ! empty( $block['className'] ) ) {
	$classes .= ' ' . sanitize_html_class( $block['className'] );
}
$anchor = ! empty( $block['anchor'] ) ? ' id="' . esc_attr( $block['anchor'] ) . '"' : '';
?>
<section class="<?php echo esc_attr( $classes ); ?>"<?php echo $anchor; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php
	if ( $image_id ) {
		echo wp_get_attachment_image( $image_id, 'gt-band', false, array(
			'class'   => 'b-statement-band__img',
			'alt'     => '',
			'sizes'   => '100vw',
			'loading' => 'lazy',
		) );
	}
	?>
	<div class="b-statement-band__inner">
		<?php if ( trim( $eyebrow ) ) : ?>
			<p class="b-statement-band__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>
		<h2 class="b-statement-band__title"><?php echo nl2br( esc_html( $heading ) ); ?></h2>
		<?php if ( trim( $text ) ) : ?>
			<p class="b-statement-band__text"><?php echo nl2br( esc_html( $text ) ); ?></p>
		<?php endif; ?>
	</div>
</section>
