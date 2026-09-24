<?php
/**
 * News pagination — Figma 384:4631 (Frame 236). Page numbers on the left,
 * the 35px arrow pair on the right. Both are plain links, so the listing
 * pages without JavaScript.
 *
 * @param array $args ['paged' => int, 'total' => int]
 */

$paged = isset( $args['paged'] ) ? max( 1, (int) $args['paged'] ) : 1;
$total = isset( $args['total'] ) ? (int) $args['total'] : 0;
if ( $total < 2 ) {
	return;
}

// Keep the chosen sort while paging.
$sort = gt_news_sort();
$link = function ( $page ) use ( $sort ) {
	$url = get_pagenum_link( max( 1, (int) $page ) );

	return 'recent' === $sort ? $url : add_query_arg( 'sort', $sort, $url );
};
?>
<nav class="news-pagination" aria-label="<?php esc_attr_e( 'News pages', 'gt' ); ?>">
	<ol class="news-pagination__pages">
		<?php for ( $page = 1; $page <= $total; $page++ ) : ?>
			<li class="news-pagination__page">
				<?php if ( $page === $paged ) : ?>
					<span class="news-pagination__num is-current" aria-current="page"><?php echo esc_html( number_format_i18n( $page ) ); ?></span>
				<?php else : ?>
					<a class="news-pagination__num" href="<?php echo esc_url( $link( $page ) ); ?>">
						<span class="screen-reader-text"><?php esc_html_e( 'Page', 'gt' ); ?> </span><?php echo esc_html( number_format_i18n( $page ) ); ?>
					</a>
				<?php endif; ?>
			</li>
		<?php endfor; ?>
	</ol>

	<div class="news-pagination__arrows shop-slider__arrows">
		<?php if ( $paged > 1 ) : ?>
			<a class="slick-prev" href="<?php echo esc_url( $link( $paged - 1 ) ); ?>" aria-label="<?php esc_attr_e( 'Previous page', 'gt' ); ?>"><?php gt_arrow_svg(); ?></a>
		<?php else : ?>
			<span class="slick-prev slick-disabled" aria-hidden="true"><?php gt_arrow_svg(); ?></span>
		<?php endif; ?>

		<?php if ( $paged < $total ) : ?>
			<a class="slick-next" href="<?php echo esc_url( $link( $paged + 1 ) ); ?>" aria-label="<?php esc_attr_e( 'Next page', 'gt' ); ?>"><?php gt_arrow_svg(); ?></a>
		<?php else : ?>
			<span class="slick-next slick-disabled" aria-hidden="true"><?php gt_arrow_svg(); ?></span>
		<?php endif; ?>
	</div>
</nav>
