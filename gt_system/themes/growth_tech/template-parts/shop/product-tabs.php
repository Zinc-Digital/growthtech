<?php
/**
 * Product tabs — Figma 384:1894. The science / How to use / Specification /
 * Useful Documents. Tabs without content are left out; nothing renders when
 * all are empty. Under 768px the same markup works as an accordion
 * (product-tabs.js).
 *
 * @param array $args ['product' => WC_Product]
 */

$product = isset( $args['product'] ) ? $args['product'] : null;
if ( ! $product instanceof WC_Product || ! function_exists( 'get_field' ) ) {
	return;
}
$id   = $product->get_id();
$tabs = array();

$science = get_field( 'science', $id );
if ( is_array( $science ) ) {
	$columns = '';
	foreach ( $science as $column ) {
		$heading = ! empty( $column['heading'] ) ? $column['heading'] : '';
		$text    = ! empty( $column['text'] ) ? $column['text'] : '';
		if ( ! $heading && ! trim( wp_strip_all_tags( $text ) ) ) {
			continue;
		}
		$columns .= '<div class="product-tabs__column">';
		if ( $heading ) {
			$columns .= '<h3 class="product-tabs__heading">' . esc_html( $heading ) . '</h3>';
		}
		if ( $text ) {
			$columns .= '<div class="product-tabs__text">' . wp_kses_post( $text ) . '</div>';
		}
		$columns .= '</div>';
	}
	if ( $columns ) {
		$tabs['science'] = array( 'label' => __( 'The science', 'gt' ), 'html' => '<div class="product-tabs__columns">' . $columns . '</div>' );
	}
}

$how   = (string) get_field( 'how_to_use', $id );
$steps = gt_product_howto_steps( $id );
if ( $steps ) {
	ob_start();
	get_template_part( 'template-parts/shop/product-howto', null, array( 'steps' => $steps, 'intro' => $how ) );
	$tabs['how-to-use'] = array( 'label' => __( 'How to use', 'gt' ), 'html' => ob_get_clean() );
	// The lightbox shell prints once, after the tabs; the script only where there are steps.
	wp_enqueue_script( 'gt-product-howto' );
	add_action( 'wp_footer', 'gt_product_howto_lightbox_shell' );
} elseif ( trim( wp_strip_all_tags( $how ) ) ) {
	$tabs['how-to-use'] = array( 'label' => __( 'How to use', 'gt' ), 'html' => '<div class="product-tabs__text product-tabs__text--single">' . wp_kses_post( $how ) . '</div>' );
}

$spec = get_field( 'specification', $id );
if ( is_array( $spec ) ) {
	$rows = '';
	foreach ( $spec as $row ) {
		$label = ! empty( $row['label'] ) ? $row['label'] : '';
		$value = ! empty( $row['value'] ) ? $row['value'] : '';
		if ( ! $label && ! $value ) {
			continue;
		}
		$rows .= '<div class="product-spec__row"><dt class="product-spec__label">' . esc_html( $label ) . '</dt><dd class="product-spec__value">' . esc_html( $value ) . '</dd></div>';
	}
	if ( $rows ) {
		$tabs['specification'] = array( 'label' => __( 'Specification', 'gt' ), 'html' => '<dl class="product-spec">' . $rows . '</dl>' );
	}
}

$docs = get_field( 'documents', $id );
if ( is_array( $docs ) ) {
	$links = '';
	foreach ( $docs as $doc ) {
		$file_id = ! empty( $doc['file'] ) ? (int) $doc['file'] : 0;
		$url     = $file_id ? wp_get_attachment_url( $file_id ) : '';
		if ( ! $url ) {
			continue;
		}
		$label = ! empty( $doc['label'] ) ? $doc['label'] : get_the_title( $file_id );
		ob_start();
		gt_icon_svg( 'document' );
		$icon   = ob_get_clean();
		$links .= '<li class="product-docs__item"><a class="product-docs__link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener"><span class="product-docs__icon" aria-hidden="true">' . $icon . '</span><span>' . esc_html( $label ) . '</span></a></li>';
	}
	if ( $links ) {
		$tabs['documents'] = array( 'label' => __( 'Useful Documents', 'gt' ), 'html' => '<ul class="product-docs">' . $links . '</ul>' );
	}
}

$tabs = apply_filters( 'gt_product_tabs', $tabs, $product );
if ( ! $tabs ) {
	return;
}
$base = 'product-tabs-' . $id;
$keys = array_keys( $tabs );
?>
<div class="product-tabs" data-product-tabs>
	<div class="product-tabs__list" role="tablist" aria-label="<?php esc_attr_e( 'Product information', 'gt' ); ?>">
		<?php foreach ( $keys as $i => $key ) : ?>
			<button type="button" class="product-tabs__tab<?php echo 0 === $i ? ' is-active' : ''; ?>" role="tab"
				id="<?php echo esc_attr( "{$base}-tab-{$key}" ); ?>"
				aria-controls="<?php echo esc_attr( "{$base}-panel-{$key}" ); ?>"
				data-tab="<?php echo esc_attr( $key ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"
				tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>"><?php echo esc_html( $tabs[ $key ]['label'] ); ?></button>
		<?php endforeach; ?>
	</div>

	<?php foreach ( $keys as $i => $key ) : ?>
		<section class="product-tabs__panel<?php echo 0 === $i ? ' is-open' : ''; ?>" role="tabpanel"
			id="<?php echo esc_attr( "{$base}-panel-{$key}" ); ?>"
			aria-labelledby="<?php echo esc_attr( "{$base}-tab-{$key}" ); ?>"
			data-tab-panel="<?php echo esc_attr( $key ); ?>"<?php echo 0 === $i ? '' : ' hidden'; ?>>
			<button type="button" class="product-tabs__acc" data-tab-acc aria-expanded="<?php echo 0 === $i ? 'true' : 'false'; ?>"
				aria-controls="<?php echo esc_attr( "{$base}-body-{$key}" ); ?>">
				<span><?php echo esc_html( $tabs[ $key ]['label'] ); ?></span>
				<svg class="product-tabs__chevron" viewBox="0 0 10 6" width="10" height="6" aria-hidden="true" focusable="false"><path fill="currentColor" d="M5.00211 0L5.4783 0.48L10 5.03788L9.04551 6L8.56932 5.52L5 1.92212L1.43068 5.52L0.954488 6L0 5.03788L0.47619 4.55788L4.5217 0.48L4.99789 0H5.00211Z"/></svg>
			</button>
			<div class="product-tabs__body" id="<?php echo esc_attr( "{$base}-body-{$key}" ); ?>">
				<?php echo $tabs[ $key ]['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- built above from escaped pieces. ?>
			</div>
		</section>
	<?php endforeach; ?>
</div>
