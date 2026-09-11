<?php
/**
 * Result count + sort — Figma 384:1733 ("Frame 86").
 *
 * @param array $args ['selection' => array, 'shown' => int, 'total' => int]
 */

$selection = isset( $args['selection'] ) ? $args['selection'] : gt_shop_selection();
$shown     = isset( $args['shown'] ) ? (int) $args['shown'] : 0;
$total     = isset( $args['total'] ) ? (int) $args['total'] : 0;
$select_id = 'shop-sort-' . wp_unique_id();
?>
<div class="shop-toolbar">
	<p class="shop-toolbar__count" data-shop-count aria-live="polite"><?php echo esc_html( gt_shop_count_text( $shown, $total ) ); ?></p>
	<form class="shop-toolbar__sort" method="get" action="" data-shop-sort-form>
		<label class="shop-toolbar__sort-label" for="<?php echo esc_attr( $select_id ); ?>"><?php esc_html_e( 'Sort by', 'gt' ); ?></label>
		<span class="shop-toolbar__select-wrap">
			<select class="shop-toolbar__select" name="orderby" id="<?php echo esc_attr( $select_id ); ?>" data-shop-sort>
				<?php foreach ( gt_shop_sort_options() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $selection['orderby'], $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</span>
		<?php foreach ( array( 'brands', 'growing-medium', 'growing-stage', 'categories' ) as $key ) : ?>
			<?php if ( $selection[ $key ] && ! ( 'categories' === $key && is_product_category() ) ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( implode( ',', $selection[ $key ] ) ); ?>" />
			<?php endif; ?>
		<?php endforeach; ?>
		<?php if ( '' !== $selection['q'] ) : ?>
			<input type="hidden" name="q" value="<?php echo esc_attr( $selection['q'] ); ?>" />
		<?php endif; ?>
		<noscript><button type="submit" class="shop-toolbar__go"><?php esc_html_e( 'Go', 'gt' ); ?></button></noscript>
	</form>
</div>
