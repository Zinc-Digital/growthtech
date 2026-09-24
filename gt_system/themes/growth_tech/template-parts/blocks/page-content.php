<?php
/**
 * Block: Page Content — Figma 384:3067 (Frame 247).
 *
 * The plain-copy page template behind Terms and Conditions, Privacy Policy and
 * Cookies: a serif page title over any number of sub-heading + copy sections,
 * all on one 47px rhythm. The title falls back to the page's own title, so most
 * pages only need the sections filled in.
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (unused).
 * @param bool   $is_preview True when rendering in the editor.
 */

$title    = trim( (string) get_field( 'title' ) );
$sections = get_field( 'sections' );
$is_demo  = ! empty( $block['data']['is_example'] );

if ( $is_demo ) {
	$title    = __( 'Terms and Conditions', 'gt' );
	$sections = array(
		array(
			'heading' => __( 'Sub header titles', 'gt' ),
			'text'    => '<p>' . __( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut et massa mi. Aliquam in hendrerit urna. Pellentesque sit amet sapien fringilla, mattis ligula consectetur, ultrices mauris.', 'gt' ) . '</p>',
		),
	);
}

// An empty title still renders, because it falls back to the page title.
if ( '' === $title && ! $is_demo ) {
	$title = (string) get_the_title();
}

$sections = is_array( $sections ) ? array_values( array_filter( $sections, function ( $section ) {
	return '' !== trim( (string) ( $section['heading'] ?? '' ) )
		|| '' !== trim( wp_strip_all_tags( (string) ( $section['text'] ?? '' ) ) );
} ) ) : array();

if ( '' === trim( $title ) && ! $sections ) {
	if ( $is_preview ) {
		echo '<p class="block-empty">' . esc_html__( 'Page Content — add a title or a section.', 'gt' ) . '</p>';
	}
	return;
}

$classes = 'b-page-content';
if ( ! empty( $block['className'] ) ) {
	$classes .= ' ' . sanitize_html_class( $block['className'] );
}
$anchor = ! empty( $block['anchor'] ) ? ' id="' . esc_attr( $block['anchor'] ) . '"' : '';
?>
<section class="<?php echo esc_attr( $classes ); ?>"<?php echo $anchor; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="b-page-content__inner">
		<?php if ( '' !== trim( $title ) ) : ?>
			<h1 class="b-page-content__title"><?php echo esc_html( $title ); ?></h1>
		<?php endif; ?>

		<?php foreach ( $sections as $section ) : ?>
			<?php
			$heading = trim( (string) ( $section['heading'] ?? '' ) );
			$text    = (string) ( $section['text'] ?? '' );
			?>
			<?php if ( '' !== $heading ) : ?>
				<h2 class="b-page-content__heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php if ( '' !== trim( wp_strip_all_tags( $text ) ) ) : ?>
				<div class="b-page-content__body"><?php echo wp_kses_post( $text ); ?></div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</section>
