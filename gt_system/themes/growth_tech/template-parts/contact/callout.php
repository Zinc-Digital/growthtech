<?php
/**
 * "Looking for a product near you?" card — Figma 384:2873 (Frame 99).
 *
 * @param array $args ['page_id' => int]
 */

$page_id = isset( $args['page_id'] ) ? (int) $args['page_id'] : get_queried_object_id();
if ( ! function_exists( 'get_field' ) ) {
	return;
}

$title = trim( (string) get_field( 'callout_title', $page_id ) );
$text  = trim( (string) get_field( 'callout_text', $page_id ) );
$link  = get_field( 'callout_link', $page_id );
if ( '' === $title ) {
	return;
}
$has_link = is_array( $link ) && ! empty( $link['url'] );
?>
<aside class="contact-callout" aria-labelledby="contact-callout-title">
	<div class="contact-callout__body">
		<h2 class="contact-callout__title" id="contact-callout-title"><?php echo esc_html( $title ); ?></h2>
		<?php if ( '' !== $text ) : ?>
			<p class="contact-callout__text"><?php echo nl2br( esc_html( $text ) ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( $has_link ) : ?>
		<a class="contact-callout__link" href="<?php echo esc_url( $link['url'] ); ?>"
			<?php echo ! empty( $link['target'] ) ? 'target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; ?>>
			<?php echo esc_html( ! empty( $link['title'] ) ? $link['title'] : __( 'Find a stockist', 'gt' ) ); ?>
		</a>
	<?php endif; ?>
</aside>
