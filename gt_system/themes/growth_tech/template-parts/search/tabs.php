<?php
/**
 * Result tabs — Figma 384:2957 (Frame 134). "All" plus one tab per group,
 * each carrying its count. The hairline runs the full width and turns black
 * under the chosen tab. Counts of zero are left off, as the design draws the
 * empty News tab.
 *
 * @param array $args ['term' => string, 'tab' => string, 'groups' => array, 'total' => int]
 */

$term   = isset( $args['term'] ) ? (string) $args['term'] : '';
$tab    = isset( $args['tab'] ) ? (string) $args['tab'] : 'all';
$groups = isset( $args['groups'] ) && is_array( $args['groups'] ) ? $args['groups'] : array();
$total  = isset( $args['total'] ) ? (int) $args['total'] : 0;

if ( '' === trim( $term ) ) {
	return;
}

$tabs = array( 'all' => array( 'label' => __( 'All', 'gt' ), 'count' => $total ) );
foreach ( $groups as $slug => $group ) {
	$tabs[ $slug ] = array( 'label' => $group['label'], 'count' => (int) $group['count'] );
}
?>
<nav class="search-tabs" aria-label="<?php esc_attr_e( 'Filter results by type', 'gt' ); ?>">
	<ul class="search-tabs__list">
		<?php foreach ( $tabs as $slug => $item ) : ?>
			<?php $is_current = ( $slug === $tab ); ?>
			<li class="search-tabs__item">
				<a class="search-tabs__link<?php echo $is_current ? ' is-current' : ''; ?>"
					href="<?php echo esc_url( gt_search_tab_url( $slug, $term ) ); ?>"
					<?php echo $is_current ? ' aria-current="page"' : ''; ?>>
					<?php
					echo esc_html( $item['label'] );
					if ( $item['count'] > 0 ) {
						echo ' (' . esc_html( number_format_i18n( $item['count'] ) ) . ')';
					}
					?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
