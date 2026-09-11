<?php
/**
 * Filter sidebar — Figma 384:1612 ("Frame 30").
 *
 * A GET form: search, then one collapsible group of checkboxes per filter
 * taxonomy with live counts. Submits to the shop page; JS upgrades it to
 * AJAX. Re-rendered by the AJAX endpoint after every change.
 *
 * @param array $args ['selection' => array]
 */

$selection = isset( $args['selection'] ) ? $args['selection'] : gt_shop_selection();
$groups    = gt_shop_filter_groups();
$form_id   = 'shop-filters-' . wp_unique_id();
?>
<form class="shop-filters" method="get" action="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" data-shop-form id="<?php echo esc_attr( $form_id ); ?>">
	<button type="button" class="shop-filters__close" data-shop-filters-close aria-label="<?php esc_attr_e( 'Close filters', 'gt' ); ?>">
		<?php gt_icon_svg( 'close' ); ?>
	</button>

	<div class="shop-filters__search">
		<label class="screen-reader-text" for="<?php echo esc_attr( $form_id ); ?>-q"><?php esc_html_e( 'Search by product name', 'gt' ); ?></label>
		<input class="shop-filters__search-input" type="search" name="q" id="<?php echo esc_attr( $form_id ); ?>-q"
			value="<?php echo esc_attr( $selection['q'] ); ?>"
			placeholder="<?php esc_attr_e( 'Search by product name', 'gt' ); ?>" autocomplete="off" />
		<button type="submit" class="shop-filters__search-btn" aria-label="<?php esc_attr_e( 'Search', 'gt' ); ?>">
			<svg viewBox="0 0 18 18" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12.9362 7.31375C12.9362 5.82165 12.3437 4.39067 11.2889 3.33559C10.2341 2.28052 8.80348 1.68779 7.31179 1.68779C5.82009 1.68779 4.38949 2.28052 3.3347 3.33559C2.27991 4.39067 1.68734 5.82165 1.68734 7.31375C1.68734 8.80585 2.27991 10.2368 3.3347 11.2919C4.38949 12.347 5.82009 12.9397 7.31179 12.9397C8.80348 12.9397 10.2341 12.347 11.2889 11.2919C12.3437 10.2368 12.9362 8.80585 12.9362 7.31375ZM11.85 13.0487C10.6056 14.0368 9.02724 14.6275 7.31179 14.6275C3.27273 14.6275 0 11.3539 0 7.31375C0 3.27361 3.27273 0 7.31179 0C11.3508 0 14.6236 3.27361 14.6236 7.31375C14.6236 9.02967 14.033 10.6085 13.0452 11.8532L17.7522 16.5614C18.0826 16.8919 18.0826 17.4264 17.7522 17.7534C17.4217 18.0804 16.8874 18.0839 16.5605 17.7534L11.85 13.0487Z"/></svg>
		</button>
	</div>

	<p class="shop-filters__title"><?php esc_html_e( 'Filters', 'gt' ); ?></p>

	<?php foreach ( $groups as $key => $group ) : ?>
		<?php
		if ( ! taxonomy_exists( $group['taxonomy'] ) ) {
			continue;
		}
		$counts = gt_shop_term_counts( $key, $selection );
		if ( ! $counts ) {
			continue;
		}
		$list_id  = $form_id . '-' . $key;
		$selected = $selection[ $key ];
		$collapsed = ! empty( $group['collapsed'] ) && ! $selected;
		?>
		<fieldset class="shop-filters__group<?php echo $collapsed ? ' is-collapsed' : ''; ?>" data-shop-group="<?php echo esc_attr( $key ); ?>">
			<legend class="screen-reader-text"><?php echo esc_html( $group['label'] ); ?></legend>
			<button type="button" class="shop-filters__group-toggle" data-shop-group-toggle
				aria-expanded="<?php echo $collapsed ? 'false' : 'true'; ?>" aria-controls="<?php echo esc_attr( $list_id ); ?>">
				<span><?php echo esc_html( $group['label'] ); ?></span>
				<svg class="shop-filters__chevron" viewBox="0 0 10 6" width="10" height="6" aria-hidden="true" focusable="false"><path fill="currentColor" d="M5.00211 0L5.4783 0.48L10 5.03788L9.04551 6L8.56932 5.52L5 1.92212L1.43068 5.52L0.954488 6L0 5.03788L0.47619 4.55788L4.5217 0.48L4.99789 0H5.00211Z"/></svg>
			</button>
			<ul class="shop-filters__list" id="<?php echo esc_attr( $list_id ); ?>">
				<?php foreach ( $counts as $slug => $count ) : ?>
					<?php
					$term = get_term_by( 'slug', $slug, $group['taxonomy'] );
					if ( ! $term ) {
						continue;
					}
					$checked  = in_array( $slug, $selected, true );
					$empty    = 0 === $count && ! $checked;
					$input_id = $list_id . '-' . $slug;
					?>
					<li class="shop-filters__item<?php echo $checked ? ' is-checked' : ''; ?><?php echo $empty ? ' is-empty' : ''; ?>">
						<label class="shop-filters__option" for="<?php echo esc_attr( $input_id ); ?>">
							<input class="shop-filters__checkbox" type="checkbox" id="<?php echo esc_attr( $input_id ); ?>"
								name="<?php echo esc_attr( $key ); ?>[]" value="<?php echo esc_attr( $slug ); ?>"<?php checked( $checked ); ?><?php disabled( $empty ); ?> />
							<span><?php echo esc_html( $term->name ); ?></span>
						</label>
						<span class="shop-filters__count"><?php echo esc_html( $count ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</fieldset>
	<?php endforeach; ?>

	<?php if ( 'brands' !== $selection['orderby'] ) : ?>
		<input type="hidden" name="orderby" value="<?php echo esc_attr( $selection['orderby'] ); ?>" />
	<?php endif; ?>
	<noscript><button type="submit" class="btn-flat btn-flat--dark shop-filters__apply"><span><?php esc_html_e( 'Apply filters', 'gt' ); ?></span></button></noscript>
</form>
