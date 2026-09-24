<?php
/**
 * Section Text block — Figma 387:5948 (Frame 137). A serif sub-heading over a
 * paragraph or two. The heading is optional, so the block also serves as a
 * plain body-copy section.
 */

$heading = (string) get_field( 'heading' );
$text    = (string) get_field( 'text' );
$is_demo = ! empty( $block['data']['is_example'] );

if ( $is_demo ) {
	$heading = __( 'Sub Title', 'gt' );
	$text    = '<p>' . __( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut et massa mi. Aliquam in hendrerit urna.', 'gt' ) . '</p>';
}
if ( '' === trim( $heading ) && '' === trim( wp_strip_all_tags( $text ) ) ) {
	if ( ! empty( $is_preview ) ) {
		echo '<p class="block-empty">' . esc_html__( 'Section Text — add a heading or some copy.', 'gt' ) . '</p>';
	}
	return;
}

$classes = 'b-section-text';
if ( ! empty( $block['className'] ) ) {
	$classes .= ' ' . sanitize_html_class( $block['className'] );
}
$anchor = ! empty( $block['anchor'] ) ? ' id="' . esc_attr( $block['anchor'] ) . '"' : '';
?>
<section class="<?php echo esc_attr( $classes ); ?>"<?php echo $anchor; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( trim( $heading ) ) : ?>
		<h2 class="b-section-text__title"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>
	<?php if ( trim( wp_strip_all_tags( $text ) ) ) : ?>
		<div class="b-section-text__body"><?php echo wp_kses_post( $text ); ?></div>
	<?php endif; ?>
</section>
