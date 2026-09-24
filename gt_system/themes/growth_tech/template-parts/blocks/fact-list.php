<?php
/**
 * Block: Fact List — Figma 384:2551 (Frame 90).
 *
 * An image beside a heading and a list of claims, each a bold line over a
 * sentence of detail, separated by hairlines.
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (unused).
 * @param bool   $is_preview True when rendering in the editor.
 */

$heading  = (string) get_field( 'heading' );
$image_id = (int) get_field( 'image' );
$side     = get_field( 'image_side' );
$bg       = (string) get_field( 'background' );
$facts    = get_field( 'facts' );
$facts    = is_array( $facts ) ? array_values( array_filter( $facts, function ( $fact ) {
	return ! empty( $fact['title'] ) || ! empty( $fact['text'] );
} ) ) : array();

if ( ! $facts ) {
	if ( $is_preview ) {
		echo '<p class="block-empty">' . esc_html__( 'Fact List — add a fact.', 'gt' ) . '</p>';
	}
	return;
}

$classes = 'b-fact-list';
$classes .= 'right' === $side ? ' b-fact-list--image-right' : '';
if ( ! empty( $block['className'] ) ) {
	$classes .= ' ' . sanitize_html_class( $block['className'] );
}
$anchor = ! empty( $block['anchor'] ) ? ' id="' . esc_attr( $block['anchor'] ) . '"' : '';
// Validated before it reaches the style attribute; the design's band is #F4F4F4.
$bg    = preg_match( '/^#[0-9a-fA-F]{6}$/', $bg ) ? strtoupper( $bg ) : '#F4F4F4';
$id    = wp_unique_id( 'fact-list-' );
?>
<section class="<?php echo esc_attr( $classes ); ?>"<?php echo $anchor; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	style="--fl-bg: <?php echo esc_attr( $bg ); ?>"
	<?php echo trim( $heading ) ? ' aria-labelledby="' . esc_attr( $id ) . '-title"' : ''; ?>>
	<div class="b-fact-list__inner">
		<?php if ( $image_id ) : ?>
			<div class="b-fact-list__media">
				<?php
				echo wp_get_attachment_image( $image_id, 'gt-split', false, array(
					'class'   => 'b-fact-list__img',
					'alt'     => '',
					'sizes'   => '(max-width: 1023px) calc(100vw - 100px), 542px',
					'loading' => 'lazy',
				) );
				?>
			</div>
		<?php endif; ?>

		<div class="b-fact-list__content">
			<?php if ( trim( $heading ) ) : ?>
				<h2 class="b-fact-list__title" id="<?php echo esc_attr( $id ); ?>-title"><?php echo nl2br( esc_html( $heading ) ); ?></h2>
			<?php endif; ?>

			<ul class="b-fact-list__facts">
				<?php foreach ( $facts as $fact ) : ?>
					<li class="b-fact-list__fact">
						<?php if ( ! empty( $fact['title'] ) ) : ?>
							<p class="b-fact-list__fact-title"><?php echo esc_html( $fact['title'] ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $fact['text'] ) ) : ?>
							<p class="b-fact-list__fact-text"><?php echo esc_html( $fact['text'] ); ?></p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>
