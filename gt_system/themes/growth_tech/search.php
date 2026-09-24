<?php
/**
 * Search results — Figma 384:2947.
 *
 * A grey band holding the query and the result count, a tab row for filtering
 * by content type, then one section per group: products as a four-up card
 * grid, everything else as thumbnail rows.
 */

get_header();

$term    = get_search_query();
$results = gt_search_results( $term );
$groups  = $results['groups'];
$tab     = $results['tab'];
$total   = (int) $results['total'];
$shown   = 'all' === $tab ? $groups : array_intersect_key( $groups, array( $tab => true ) );
?>

<main class="page-wrapper search-results">

	<?php get_template_part( 'template-parts/search/form', null, array( 'term' => $term, 'total' => $total ) ); ?>

	<div class="search-results__inner">

		<?php get_template_part( 'template-parts/search/tabs', null, array( 'term' => $term, 'tab' => $tab, 'groups' => $groups, 'total' => $total ) ); ?>

		<?php
		$rendered = 0;
		foreach ( $shown as $group ) {
			if ( ! $group['count'] || ! $group['query'] instanceof WP_Query ) {
				continue;
			}
			get_template_part( 'template-parts/search/group', null, array( 'group' => $group ) );
			$rendered++;
		}
		?>

		<?php if ( ! $rendered ) : ?>
			<div class="search-results__empty">
				<?php if ( '' === trim( $term ) ) : ?>
					<p class="search-results__empty-title"><?php esc_html_e( 'What are you looking for?', 'gt' ); ?></p>
					<p class="search-results__empty-text"><?php esc_html_e( 'Search for a product, a brand or a growing question and we will find it.', 'gt' ); ?></p>
				<?php else : ?>
					<p class="search-results__empty-title">
						<?php
						/* translators: %s: the search term */
						printf( esc_html__( 'Nothing found for “%s”.', 'gt' ), esc_html( $term ) );
						?>
					</p>
					<p class="search-results__empty-text"><?php esc_html_e( 'Try a shorter term, check the spelling, or browse the full range.', 'gt' ); ?></p>
					<p class="search-results__empty-links">
						<a class="btn-flat btn-flat--dark" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">
							<span><?php esc_html_e( 'Browse all products', 'gt' ); ?></span>
							<?php gt_arrow_svg(); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	</div>

<?php
get_footer();
