<?php
/**
 * Block: Split Band — the "Bring the science home." section.
 *
 * Black band with copy and a call to action on one side and an image on the
 * other. The image side is switchable so the block can alternate down a page.
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (unused).
 * @param bool   $is_preview True when rendering in the editor.
 */

$heading  = get_field( 'heading' );
$text     = get_field( 'text' );
$image    = (int) get_field( 'image' );
$side     = get_field( 'image_side' );
$cta_text = get_field( 'cta_text' );
$cta_link = get_field( 'cta_link' );

// Give editors something to look at before any fields are filled in.
if ( $is_preview && ! $heading && ! $text && ! $image ) {
	$heading = __( 'Bring the science home.', 'gt' );
	$text    = __( 'Add a heading, some text and an image in the sidebar to build this section.', 'gt' );
}

if ( ! $heading && ! $text && ! $image ) {
	return;
}

$block_id = ! empty( $block['anchor'] ) ? $block['anchor'] : 'split-band-' . sanitize_title( $block['id'] );
$title_id = $block_id . '-title';

$classes = array( 'b-split-band' );
$classes[] = 'left' === $side ? 'b-split-band--image-left' : 'b-split-band--image-right';

if ( ! empty( $block['className'] ) ) {
	$classes[] = $block['className'];
}
?>

<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
	id="<?php echo esc_attr( $block_id ); ?>"
	<?php echo $heading ? 'aria-labelledby="' . esc_attr( $title_id ) . '"' : ''; ?>>

	<div class="b-split-band__inner">

		<div class="b-split-band__content">
			<?php if ( $heading ) : ?>
				<h2 class="b-split-band__title" id="<?php echo esc_attr( $title_id ); ?>">
					<?php echo esc_html( $heading ); ?>
				</h2>
			<?php endif; ?>

			<?php if ( $text ) : ?>
				<p class="b-split-band__text"><?php echo wp_kses_post( $text ); ?></p>
			<?php endif; ?>

			<?php if ( $cta_text && $cta_link && ! empty( $cta_link['url'] ) ) : ?>
				<a class="btn-flat b-split-band__cta"
					href="<?php echo esc_url( $cta_link['url'] ); ?>"
					<?php echo ! empty( $cta_link['target'] ) ? 'target="' . esc_attr( $cta_link['target'] ) . '" rel="noopener"' : ''; ?>>
					<span><?php echo esc_html( $cta_text ); ?></span>
					<?php gt_arrow_svg(); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( $image ) : ?>
			<div class="b-split-band__media">
				<?php
				/*
				 * gt-split (2x) and gt-split-sm (1x) share the panel's aspect
				 * ratio, so WordPress builds a real srcset. `loading` is left to
				 * core, which knows where the block sits on the page and may mark
				 * this as the LCP image.
				 */
				echo wp_get_attachment_image(
					$image,
					'gt-split',
					false,
					array(
						'class' => 'b-split-band__img',
						'sizes' => '(max-width: 767px) 100vw, 50vw',
					)
				);
				?>
			</div>
		<?php endif; ?>

	</div>
</section>
