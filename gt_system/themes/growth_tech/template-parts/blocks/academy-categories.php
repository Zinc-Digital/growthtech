<?php
/**
 * Block: Academy Categories — Figma 384:2120 (Frame 90).
 *
 * A grey band with the copy on the left and the Plant Academy categories
 * listed on the right: name, tagline, a line of description and the guide
 * count, divided by hairlines. On a category archive the category being read
 * drops out of the list, which is what the design draws there.
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (unused).
 * @param bool   $is_preview True when rendering in the editor.
 */

$heading = (string) get_field( 'heading' );
$text    = (string) get_field( 'text' );
$chosen  = get_field( 'categories' );
$skip    = (bool) get_field( 'exclude_current' );
$is_demo = ! empty( $block['data']['is_example'] );

if ( $is_demo ) {
	$heading = __( 'Learn the science. Master the grow.', 'gt' );
	$text    = __( 'Five comprehensive modules break down the complete plant lifecycle.', 'gt' );
}

// An explicit pick wins; otherwise every category, in name order.
$terms = array();
if ( is_array( $chosen ) && $chosen ) {
	foreach ( $chosen as $one ) {
		$term = $one instanceof WP_Term ? $one : get_term( (int) $one, 'academy_cat' );
		if ( $term instanceof WP_Term ) {
			$terms[] = $term;
		}
	}
} elseif ( ! $is_demo ) {
	$terms = gt_academy_categories();
}

// The category being read is already the page; listing it again is noise.
if ( $skip && is_tax( 'academy_cat' ) ) {
	$current = get_queried_object();
	$terms   = array_values( array_filter( $terms, function ( $term ) use ( $current ) {
		return ! ( $current instanceof WP_Term ) || $term->term_id !== $current->term_id;
	} ) );
}

if ( ! $terms && ! $is_demo ) {
	if ( $is_preview ) {
		echo '<p class="block-empty">' . esc_html__( 'Academy Categories — add a category under Plant Academy first.', 'gt' ) . '</p>';
	}
	return;
}

$classes = 'b-academy-cats';
if ( ! empty( $block['className'] ) ) {
	$classes .= ' ' . sanitize_html_class( $block['className'] );
}
$anchor   = ! empty( $block['anchor'] ) ? ' id="' . esc_attr( $block['anchor'] ) . '"' : '';
$title_id = 'academy-cats-' . ( ! empty( $block['id'] ) ? sanitize_title( $block['id'] ) : 'title' );
?>
<section class="<?php echo esc_attr( $classes ); ?>"<?php echo $anchor; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo $heading ? ' aria-labelledby="' . esc_attr( $title_id ) . '"' : ' aria-label="' . esc_attr__( 'Plant Academy categories', 'gt' ) . '"'; ?>>
	<div class="b-academy-cats__inner">

		<div class="b-academy-cats__copy">
			<?php if ( $heading ) : ?>
				<h2 class="b-academy-cats__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php if ( $text ) : ?>
				<p class="b-academy-cats__text"><?php echo wp_kses_post( $text ); ?></p>
			<?php endif; ?>
		</div>

		<ul class="b-academy-cats__list">
			<?php foreach ( $terms as $term ) : ?>
				<?php
				$tagline = gt_term_field( 'tagline', $term, '' );
				$count   = (int) $term->count;
				?>
				<li class="b-academy-cats__item">
					<a class="b-academy-cats__link" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
						<span class="b-academy-cats__body">
							<span class="b-academy-cats__head">
								<span class="b-academy-cats__name"><?php echo esc_html( $term->name ); ?></span>
								<?php if ( $tagline ) : ?>
									<span class="b-academy-cats__tagline"><?php echo esc_html( $tagline ); ?></span>
								<?php endif; ?>
							</span>
							<?php if ( $term->description ) : ?>
								<span class="b-academy-cats__desc"><?php echo esc_html( $term->description ); ?></span>
							<?php endif; ?>
						</span>

						<span class="b-academy-cats__cta">
							<span class="b-academy-cats__count"><?php
								printf(
									/* translators: %s: number of guides */
									esc_html( _n( '%s guide', '%s guides', $count, 'gt' ) ),
									esc_html( number_format_i18n( $count ) )
								);
							?></span>
							<?php gt_icon_svg( 'arrow-sm' ); ?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>
