<?php
/**
 * Info Band block — Figma 387:5948 (Frames 99 and 182). A black band with a
 * serif label on the left and the copy beside it: "Top Tip", "Quote" or
 * whatever else the editor needs.
 */

$label = (string) get_field( 'label' );
$text  = (string) get_field( 'text' );
$cite  = (string) get_field( 'attribution' );
$is_demo = ! empty( $block['data']['is_example'] );

if ( $is_demo ) {
	$label = __( 'Top Tip', 'gt' );
	$text  = __( 'Using coco loose from the bag? Break up any large clumps before potting.', 'gt' );
}
if ( '' === trim( $text ) ) {
	if ( ! empty( $is_preview ) ) {
		echo '<p class="block-empty">' . esc_html__( 'Info Band — add the text.', 'gt' ) . '</p>';
	}
	return;
}

$classes = 'b-info-band';
if ( ! empty( $block['className'] ) ) {
	$classes .= ' ' . sanitize_html_class( $block['className'] );
}
$anchor = ! empty( $block['anchor'] ) ? ' id="' . esc_attr( $block['anchor'] ) . '"' : '';
// A band with an attribution is a pull quote, so mark it up as one.
$is_quote = '' !== trim( $cite );
?>
<aside class="<?php echo esc_attr( $classes ); ?>"<?php echo $anchor; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( trim( $label ) ) : ?>
		<p class="b-info-band__label"><?php echo esc_html( $label ); ?></p>
	<?php endif; ?>
	<?php if ( $is_quote ) : ?>
		<blockquote class="b-info-band__text">
			<p><?php echo wp_kses_post( $text ); ?> <cite class="b-info-band__cite">&ndash; <?php echo esc_html( $cite ); ?></cite></p>
		</blockquote>
	<?php else : ?>
		<div class="b-info-band__text"><p><?php echo wp_kses_post( $text ); ?></p></div>
	<?php endif; ?>
</aside>
