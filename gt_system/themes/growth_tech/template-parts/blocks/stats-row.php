<?php
/**
 * Block: Stats Row — Figma 384:2551 (Frame 188).
 *
 * A row of large figures with small capitalised labels, divided by hairlines.
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (unused).
 * @param bool   $is_preview True when rendering in the editor.
 */

$stats = get_field( 'stats' );
$stats = is_array( $stats ) ? array_values( array_filter( $stats, function ( $stat ) {
	return ! empty( $stat['value'] ) || ! empty( $stat['label'] );
} ) ) : array();

if ( ! $stats ) {
	if ( $is_preview ) {
		echo '<p class="block-empty">' . esc_html__( 'Stats Row — add a figure.', 'gt' ) . '</p>';
	}
	return;
}

$classes = 'b-stats-row';
if ( ! empty( $block['className'] ) ) {
	$classes .= ' ' . sanitize_html_class( $block['className'] );
}
$anchor = ! empty( $block['anchor'] ) ? ' id="' . esc_attr( $block['anchor'] ) . '"' : '';
?>
<section class="<?php echo esc_attr( $classes ); ?>"<?php echo $anchor; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	aria-label="<?php esc_attr_e( 'By the numbers', 'gt' ); ?>">
	<dl class="b-stats-row__inner">
		<?php foreach ( $stats as $stat ) : ?>
			<div class="b-stats-row__stat">
				<dt class="b-stats-row__value"><?php echo esc_html( $stat['value'] ); ?></dt>
				<dd class="b-stats-row__label"><?php echo esc_html( $stat['label'] ); ?></dd>
			</div>
		<?php endforeach; ?>
	</dl>
</section>
