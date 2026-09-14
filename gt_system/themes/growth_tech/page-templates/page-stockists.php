<?php
/* Template Name: Find a Stockist */
/**
 * Stockist finder — Figma 384:2719. Title + intro, controls, the stockist
 * list beside the map, and the "Run a store?" band. Every card is rendered
 * server-side with data attributes; stockists.js filters/sorts them and
 * drives the map. Without a Google Maps key the map is a placeholder and
 * the list still works.
 */

get_header();

$page_id   = get_queried_object_id();
$intro     = function_exists( 'get_field' ) ? (string) get_field( 'intro', $page_id ) : '';
$stockists = gt_stockists_all();
$pre       = gt_stockist_preselect();
$visible   = array_filter( $stockists, function ( $s ) use ( $pre ) { return gt_stockist_is_visible( $s, $pre ); } );
$has_key   = '' !== gt_maps_key();
?>

<main class="page-wrapper stockists" data-stockists data-has-key="<?php echo $has_key ? '1' : '0'; ?>"
	data-region="<?php echo esc_attr( $pre['region'] ); ?>" data-product="<?php echo esc_attr( $pre['product'] ); ?>" data-brand="<?php echo esc_attr( $pre['brand'] ); ?>">
	<div class="stockists__inner">

		<header class="stockists__head">
			<h1 class="stockists__title"><?php echo esc_html( get_the_title( $page_id ) ); ?></h1>
			<?php if ( $intro ) : ?>
				<p class="stockists__intro"><?php echo wp_kses_post( $intro ); ?></p>
			<?php endif; ?>
		</header>

		<?php get_template_part( 'template-parts/stockists/controls', null, array( 'stockists' => $stockists, 'pre' => $pre, 'has_key' => $has_key ) ); ?>

		<div class="stockists__results">
			<div class="stockists__list-col">
				<div class="stockists__toolbar">
					<p class="stockists__count" data-stockists-count aria-live="polite"><?php
						$n = count( $visible );
						/* translators: %d: number of stockists */
						echo esc_html( 1 === $n ? __( '1 stockist', 'gt' ) : sprintf( __( '%d stockists', 'gt' ), $n ) );
					?></p>
					<label class="stockists__sort">
						<span class="stockists__sort-label"><?php esc_html_e( 'Sort by', 'gt' ); ?></span>
						<span class="stockists__select-wrap">
							<select class="stockists__sort-select" data-stockists-sort>
								<option value="nearest"<?php echo $has_key ? '' : ' disabled'; ?><?php echo $has_key ? '' : ' title="' . esc_attr__( 'Search for a place to sort by distance', 'gt' ) . '"'; ?>><?php esc_html_e( 'Nearest first', 'gt' ); ?></option>
								<option value="az" selected><?php esc_html_e( 'A – Z', 'gt' ); ?></option>
							</select>
						</span>
					</label>
				</div>
				<p class="stockists__note" data-stockists-note hidden></p>
				<div class="stockists__list">
					<ul class="stockists__cards" data-stockists-cards>
						<?php foreach ( $stockists as $s ) : ?>
							<?php get_template_part( 'template-parts/stockists/card', null, array( 'stockist' => $s, 'visible' => gt_stockist_is_visible( $s, $pre ) ) ); ?>
						<?php endforeach; ?>
					</ul>
					<p class="stockists__empty" data-stockists-empty<?php echo $visible ? ' hidden' : ''; ?>><?php esc_html_e( 'No stockists match your search yet. Try another place or product.', 'gt' ); ?></p>
				</div>
			</div>

			<div class="stockists__map-col">
				<?php get_template_part( 'template-parts/stockists/map', null, array( 'has_key' => $has_key ) ); ?>
			</div>
		</div>

		<?php get_template_part( 'template-parts/stockists/trade-band', null, array( 'page_id' => $page_id ) ); ?>
	</div>

<?php
get_footer();
