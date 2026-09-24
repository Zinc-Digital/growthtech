<?php
/**
 * Pagination — the news archive's pattern (Figma 384:4631, Frame 236): page
 * numbers on the left, the arrow pair on the right, both plain links so a
 * listing pages without JavaScript. Hidden on a single page.
 *
 * Shared by the search results and the Plant Academy category archive. The
 * caller supplies `link`, so each listing keeps its own query string.
 *
 * @param array $args ['paged' => int, 'pages' => int, 'link' => callable, 'label' => string]
 */

$paged = isset( $args['paged'] ) ? max( 1, (int) $args['paged'] ) : 1;
$pages = isset( $args['pages'] ) ? (int) $args['pages'] : 0;
$link  = isset( $args['link'] ) && is_callable( $args['link'] ) ? $args['link'] : null;
$label = isset( $args['label'] ) ? (string) $args['label'] : __( 'Pages', 'gt' );

if ( $pages < 2 || ! $link ) {
	return;
}
?>
<nav class="gt-pagination" aria-label="<?php echo esc_attr( $label ); ?>">
	<ol class="gt-pagination__pages">
		<?php for ( $page = 1; $page <= $pages; $page++ ) : ?>
			<li class="gt-pagination__page">
				<?php if ( $page === $paged ) : ?>
					<span class="gt-pagination__num is-current" aria-current="page"><?php echo esc_html( number_format_i18n( $page ) ); ?></span>
				<?php else : ?>
					<a class="gt-pagination__num" href="<?php echo esc_url( $link( $page ) ); ?>">
						<span class="screen-reader-text"><?php esc_html_e( 'Page', 'gt' ); ?> </span><?php echo esc_html( number_format_i18n( $page ) ); ?>
					</a>
				<?php endif; ?>
			</li>
		<?php endfor; ?>
	</ol>

	<div class="gt-pagination__arrows shop-slider__arrows">
		<?php if ( $paged > 1 ) : ?>
			<a class="slick-prev" href="<?php echo esc_url( $link( $paged - 1 ) ); ?>" aria-label="<?php esc_attr_e( 'Previous page', 'gt' ); ?>"><?php gt_arrow_svg(); ?></a>
		<?php else : ?>
			<span class="slick-prev slick-disabled" aria-hidden="true"><?php gt_arrow_svg(); ?></span>
		<?php endif; ?>

		<?php if ( $paged < $pages ) : ?>
			<a class="slick-next" href="<?php echo esc_url( $link( $paged + 1 ) ); ?>" aria-label="<?php esc_attr_e( 'Next page', 'gt' ); ?>"><?php gt_arrow_svg(); ?></a>
		<?php else : ?>
			<span class="slick-next slick-disabled" aria-hidden="true"><?php gt_arrow_svg(); ?></span>
		<?php endif; ?>
	</div>
</nav>
