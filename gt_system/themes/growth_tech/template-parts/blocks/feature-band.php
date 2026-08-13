<?php
/**
 * Block: Feature Band — Figma "Rectangle 1" (165:2298) + "Frame 18" (165:2300).
 *
 * Full-bleed image with a dark gradient over its left half, copy and a call to
 * action on top, and freely positioned tags pinned to the image.
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (unused).
 * @param bool   $is_preview True when rendering in the editor.
 */

$heading  = get_field( 'heading' );
$text     = get_field( 'text' );
$image    = (int) get_field( 'image' );
$tags     = get_field( 'tags' );
$cta_text = get_field( 'cta_text' );
$cta_link = get_field( 'cta_link' );

// Give editors something to look at before any fields are filled in.
if ( $is_preview && ! $heading && ! $text && ! $image ) {
	$heading = __( 'Formulated by experts. Grown by you.', 'gt' );
	$text    = __( 'Add a heading, some text and a background image in the sidebar to build this section.', 'gt' );
}

if ( ! $heading && ! $text && ! $image ) {
	return;
}

$block_id = ! empty( $block['anchor'] ) ? $block['anchor'] : 'feature-band-' . sanitize_title( $block['id'] );
$title_id = $block_id . '-title';

$classes = array( 'b-feature-band' );

if ( ! empty( $block['className'] ) ) {
	$classes[] = $block['className'];
}
?>

<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
	id="<?php echo esc_attr( $block_id ); ?>"
	<?php echo $heading ? 'aria-labelledby="' . esc_attr( $title_id ) . '"' : ''; ?>>

	<?php
	if ( $image ) {
		/*
		 * Full-bleed, so `sizes` is the viewport width. `loading` is left to
		 * core, which knows where the block sits on the page.
		 */
		echo wp_get_attachment_image(
			$image,
			'gt-band',
			false,
			array(
				'class' => 'b-feature-band__bg',
				'sizes' => '100vw',
			)
		);
	}
	?>

	<span class="b-feature-band__scrim" aria-hidden="true"></span>

	<div class="b-feature-band__inner">
		<div class="b-feature-band__content">
			<?php if ( $heading ) : ?>
				<h2 class="b-feature-band__title" id="<?php echo esc_attr( $title_id ); ?>">
					<?php echo esc_html( $heading ); ?>
				</h2>
			<?php endif; ?>

			<?php if ( $text ) : ?>
				<p class="b-feature-band__text"><?php echo wp_kses_post( $text ); ?></p>
			<?php endif; ?>

			<?php if ( $cta_text && $cta_link && ! empty( $cta_link['url'] ) ) : ?>
				<a class="btn-flat b-feature-band__cta"
					href="<?php echo esc_url( $cta_link['url'] ); ?>"
					<?php echo ! empty( $cta_link['target'] ) ? 'target="' . esc_attr( $cta_link['target'] ) . '" rel="noopener"' : ''; ?>>
					<span><?php echo esc_html( $cta_text ); ?></span>
					<?php gt_arrow_svg(); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( ! empty( $tags ) ) : ?>
		<?php
		/*
		 * The tags are a list of claims about the product, so they are content
		 * rather than decoration. Each one is pinned with custom properties so
		 * the position stays in CSS and can be dropped on smaller screens.
		 */
		?>
		<ul class="b-feature-band__tags">
			<?php
			foreach ( $tags as $tag ) :
				$label = ! empty( $tag['label'] ) ? $tag['label'] : '';

				if ( ! $label ) {
					continue;
				}

				$icon = ! empty( $tag['icon'] ) ? (int) $tag['icon'] : 0;
				$pos_x = isset( $tag['pos_x'] ) && '' !== $tag['pos_x'] ? (float) $tag['pos_x'] : 60;
				$pos_y = isset( $tag['pos_y'] ) && '' !== $tag['pos_y'] ? (float) $tag['pos_y'] : 40;
				?>
				<li class="b-feature-band__tag"
					style="--tag-x: <?php echo esc_attr( $pos_x ); ?>%; --tag-y: <?php echo esc_attr( $pos_y ); ?>%;">
					<?php
					if ( $icon ) {
						echo wp_get_attachment_image(
							$icon,
							'thumbnail',
							false,
							array(
								'class'   => 'b-feature-band__tag-icon',
								'alt'     => '',
								'loading' => 'lazy',
							)
						);
					}
					?>
					<span class="b-feature-band__tag-label"><?php echo esc_html( $label ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
