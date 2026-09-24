<?php
/**
 * Finder controls — Figma 384:2726. Region toggle, place/store search,
 * product filter; the country select appears for International.
 *
 * @param array $args ['stockists' => array, 'pre' => array, 'has_key' => bool]
 */

$stockists = isset( $args['stockists'] ) ? $args['stockists'] : array();
$pre       = isset( $args['pre'] ) ? $args['pre'] : gt_stockist_preselect();
$countries = gt_stockist_countries_present( $stockists );
$products  = gt_stockist_product_options();
$intl      = 'international' === $pre['region'];
?>
<div class="stockists-controls" data-stockists-controls>
	<div class="stockists-controls__region" role="group" aria-label="<?php esc_attr_e( 'Region', 'gt' ); ?>" data-stockists-region>
		<button type="button" class="stockists-controls__toggle<?php echo $intl ? '' : ' is-active'; ?>" data-region="uk" aria-pressed="<?php echo $intl ? 'false' : 'true'; ?>"><?php esc_html_e( 'United Kingdom', 'gt' ); ?></button>
		<button type="button" class="stockists-controls__toggle<?php echo $intl ? ' is-active' : ''; ?>" data-region="international" aria-pressed="<?php echo $intl ? 'true' : 'false'; ?>"><?php esc_html_e( 'International', 'gt' ); ?></button>
	</div>

	<label class="stockists-controls__country<?php echo $intl ? '' : ' is-hidden'; ?>" data-stockists-country-wrap>
		<span class="screen-reader-text"><?php esc_html_e( 'Country', 'gt' ); ?></span>
		<span class="stockists-controls__select-wrap">
			<select class="stockists-controls__select" data-stockists-country>
				<option value=""><?php esc_html_e( 'All countries', 'gt' ); ?></option>
				<?php foreach ( $countries as $code => $name ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
		</span>
	</label>

	<label class="stockists-controls__search">
		<span class="screen-reader-text"><?php esc_html_e( 'Search by town, city, postcode or store name', 'gt' ); ?></span>
		<span class="stockists-controls__search-icon" aria-hidden="true"><?php gt_icon_svg( 'search' ); ?></span>
		<input type="search" class="stockists-controls__input" data-stockists-search value="<?php echo esc_attr( $pre['q'] ); ?>"
			placeholder="<?php esc_attr_e( 'Search by town, city, postcode or store name...', 'gt' ); ?>" autocomplete="off" />
	</label>

	<label class="stockists-controls__product">
		<span class="screen-reader-text"><?php esc_html_e( 'Product', 'gt' ); ?></span>
		<span class="stockists-controls__select-wrap">
			<select class="stockists-controls__select" data-stockists-product>
				<option value=""><?php esc_html_e( 'Stocking any product', 'gt' ); ?></option>
				<?php foreach ( $products as $slug => $name ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>"<?php selected( $pre['product'], $slug ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
		</span>
	</label>
</div>
