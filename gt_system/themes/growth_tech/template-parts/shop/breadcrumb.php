<?php
/**
 * Shop breadcrumb — Figma 384:1277. "Our Products / Propagation".
 *
 * @param array $args ['items' => [ ['label' => string, 'url' => string|null], ... ]]
 *                    Optional; defaults to gt_shop_breadcrumb_items().
 */

$items = isset( $args['items'] ) ? $args['items'] : gt_shop_breadcrumb_items();

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
