<?php
/**
 * Search band — Figma 384:2950 (Frame 227). Grey ground, the query set large
 * in serif over a hairline, and the result count beneath. The query is a live
 * input, so a new search can be run from the results themselves.
 *
 * @param array $args ['term' => string, 'total' => int]
 */

$term  = isset( $args['term'] ) ? (string) $args['term'] : '';
$total = isset( $args['total'] ) ? (int) $args['total'] : 0;
$id    = 'gt-search-results-' . wp_unique_id();
?>
<div class="search-band">
	<div class="search-band__inner">
		<form class="search-band__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>">
				<?php esc_html_e( 'Search products and plant care', 'gt' ); ?>
			</label>

			<button type="submit" class="search-band__submit">
				<?php gt_icon_svg( 'search-bold' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Search', 'gt' ); ?></span>
			</button>

			<input type="search"
				id="<?php echo esc_attr( $id ); ?>"
				class="search-band__input"
				name="s"
				value="<?php echo esc_attr( $term ); ?>"
				placeholder="<?php esc_attr_e( 'Search products and plant care...', 'gt' ); ?>" />
		</form>

		<?php if ( '' !== trim( $term ) ) : ?>
			<p class="search-band__count" aria-live="polite">
				<?php
				printf(
					/* translators: 1: number of results, 2: the search term */
					esc_html( _n( '%1$s result for “%2$s”', '%1$s results for “%2$s”', $total, 'gt' ) ),
					esc_html( number_format_i18n( $total ) ),
					esc_html( $term )
				);
				?>
			</p>
		<?php endif; ?>
	</div>
</div>
