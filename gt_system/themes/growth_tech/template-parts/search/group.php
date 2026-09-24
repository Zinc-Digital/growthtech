<?php
/**
 * One result group — Figma 384:2970 (products) and 384:3041 (Plant Academy).
 *
 * A serif heading with the group's count beside it, then either the four-up
 * product card grid or a stack of thumbnail rows.
 *
 * @param array $args ['group' => array] as built by gt_search_results().
 */

$group = isset( $args['group'] ) && is_array( $args['group'] ) ? $args['group'] : null;
if ( ! $group || ! $group['query'] instanceof WP_Query || ! $group['query']->have_posts() ) {
	return;
}

$query  = $group['query'];
$layout = 'grid' === $group['layout'] ? 'grid' : 'list';
$id     = 'search-group-' . sanitize_html_class( $group['slug'] );
?>
<section class="search-group search-group--<?php echo esc_attr( $layout ); ?>" aria-labelledby="<?php echo esc_attr( $id ); ?>">
	<header class="search-group__head">
		<h2 class="search-group__title" id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $group['label'] ); ?></h2>
		<p class="search-group__count"><?php echo esc_html( gt_search_group_count_label( $group, $group['count'] ) ); ?></p>
	</header>

	<?php if ( 'grid' === $layout ) : ?>
		<ul class="search-grid">
			<?php
			while ( $query->have_posts() ) {
				$query->the_post();
				wc_get_template_part( 'content', 'product' );
			}
			wp_reset_postdata();
			?>
		</ul>
	<?php else : ?>
		<ul class="search-list">
			<?php
			while ( $query->have_posts() ) {
				$query->the_post();
				get_template_part( 'template-parts/search/row', null, array( 'post' => get_post(), 'cta' => $group['cta'] ) );
			}
			wp_reset_postdata();
			?>
		</ul>
	<?php endif; ?>
</section>
