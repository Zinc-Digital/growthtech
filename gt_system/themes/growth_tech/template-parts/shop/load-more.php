<?php
/**
 * "Load more" — a link to the next page that JS turns into an append.
 *
 * @param array $args ['selection' => array, 'total_pages' => int]
 */

$selection   = isset( $args['selection'] ) ? $args['selection'] : gt_shop_selection();
$total_pages = isset( $args['total_pages'] ) ? (int) $args['total_pages'] : 1;

if ( $selection['paged'] >= $total_pages ) {
	return;
}
$next = $selection;
$next['paged']++;
?>
<div class="shop-more" data-shop-more data-next-page="<?php echo esc_attr( $next['paged'] ); ?>" data-total-pages="<?php echo esc_attr( $total_pages ); ?>">
	<a class="btn-flat btn-flat--dark" href="<?php echo esc_url( gt_shop_build_url( $next ) ); ?>" data-shop-more-link>
		<span><?php esc_html_e( 'Load more products', 'gt' ); ?></span>
		<?php gt_arrow_svg(); ?>
	</a>
</div>
