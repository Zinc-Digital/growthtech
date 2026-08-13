<?php
/**
 * Search form — the rounded pill from the Figma header (11:97 / 40:433).
 *
 * Used by get_search_form() in the header and the mobile panel.
 */

$gt_search_id = 'gt-search-' . wp_unique_id();
?>
<form role="search" method="get" class="search-pill" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<button type="submit" class="search-pill__submit">
		<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/icons/search.svg' ); ?>"
			alt="" width="20" height="20" />
		<span class="screen-reader-text"><?php esc_html_e( 'Search', 'gt' ); ?></span>
	</button>

	<label class="screen-reader-text" for="<?php echo esc_attr( $gt_search_id ); ?>">
		<?php esc_html_e( 'Search products and plant care', 'gt' ); ?>
	</label>

	<input type="search"
		id="<?php echo esc_attr( $gt_search_id ); ?>"
		class="search-pill__input"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'Search products and plant care...', 'gt' ); ?>" />
</form>
