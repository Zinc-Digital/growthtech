<?php
/**
 * Shop breadcrumb — Figma 384:1277. "Our Products / Propagation".
 *
 * @param array $args ['items' => [ ['label' => string, 'url' => string|null], ... ]]
 *                    Optional; defaults to the shop page + current category chain.
 */

$items = isset( $args['items'] ) ? $args['items'] : null;

if ( null === $items ) {
	$shop_id = wc_get_page_id( 'shop' );
	$items   = array( array( 'label' => $shop_id > 0 ? get_the_title( $shop_id ) : __( 'Our Products', 'gt' ), 'url' => wc_get_page_permalink( 'shop' ) ) );

	if ( is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$chain = array_reverse( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );
			foreach ( $chain as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'product_cat' );
				if ( $ancestor instanceof WP_Term ) {
					$items[] = array( 'label' => $ancestor->name, 'url' => get_term_link( $ancestor ) );
				}
			}
			$items[] = array( 'label' => $term->name, 'url' => null );
		}
	}
}

if ( ! $items ) {
	return;
}
$last = count( $items ) - 1;
?>
<nav class="shop-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'gt' ); ?>">
	<ol class="shop-crumbs__list">
		<?php foreach ( $items as $i => $item ) : ?>
			<li class="shop-crumbs__item">
				<?php if ( $i < $last && ! empty( $item['url'] ) ) : ?>
					<a class="shop-crumbs__link" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
				<?php else : ?>
					<span aria-current="page" class="shop-crumbs__current"><?php echo esc_html( $item['label'] ); ?></span>
				<?php endif; ?>
				<?php if ( $i < $last ) : ?>
					<span class="shop-crumbs__sep" aria-hidden="true">/</span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
