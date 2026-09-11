<?php
/**
 * Shop and category archive — Figma 384:1608 (All Products) and 384:1267
 * (Product Range Template).
 *
 * Breadcrumb, optional category hero, filter sidebar, toolbar, grid with
 * brand promo tiles, load more. The grid, sidebar and toolbar are also
 * produced by the AJAX endpoint from the same parts.
 */

defined( 'ABSPATH' ) || exit;

get_header();

global $wp_query;

$selection   = gt_shop_selection();
$total       = (int) $wp_query->found_posts;
$per_page    = gt_shop_per_page();
$total_pages = max( 1, (int) $wp_query->max_num_pages );
$shown       = min( $total, $selection['paged'] * $per_page );
$promos      = gt_shop_promos( $selection );
$active      = count( $selection['brands'] ) + count( $selection['growing-medium'] ) + count( $selection['growing-stage'] ) + ( is_product_category() ? 0 : count( $selection['categories'] ) );
?>

<main class="page-wrapper shop<?php echo is_product_category() ? ' shop--category' : ''; ?>">
	<div class="shop__inner">

		<div class="shop__breadcrumb">
			<?php get_template_part( 'template-parts/shop/breadcrumb' ); ?>
		</div>

		<?php if ( is_product_category() ) : ?>
			<div class="shop__hero">
				<?php get_template_part( 'template-parts/shop/category-hero' ); ?>
			</div>
		<?php else : ?>
			<h1 class="screen-reader-text"><?php woocommerce_page_title(); ?></h1>
		<?php endif; ?>

		<div class="shop__body">

			<button type="button" class="shop__filters-toggle btn-flat btn-flat--dark" data-shop-filters-toggle aria-expanded="false" aria-controls="shop-sidebar">
				<span><?php esc_html_e( 'Filters', 'gt' ); ?><?php echo $active ? ' <span class="shop__filters-badge">' . esc_html( $active ) . '</span>' : ''; ?></span>
			</button>

			<aside class="shop__sidebar" id="shop-sidebar" data-shop-sidebar aria-label="<?php esc_attr_e( 'Filter products', 'gt' ); ?>">
				<?php get_template_part( 'template-parts/shop/filters', null, array( 'selection' => $selection ) ); ?>
			</aside>

			<div class="shop__main">
				<?php get_template_part( 'template-parts/shop/toolbar', null, array( 'selection' => $selection, 'shown' => $shown, 'total' => $total ) ); ?>

				<?php if ( $wp_query->have_posts() ) : ?>
					<ul class="shop-grid" data-shop-grid aria-busy="false">
						<?php gt_shop_render_loop( $wp_query, $promos ); ?>
					</ul>
					<?php get_template_part( 'template-parts/shop/load-more', null, array( 'selection' => $selection, 'total_pages' => $total_pages ) ); ?>
				<?php else : ?>
					<ul class="shop-grid" data-shop-grid aria-busy="false"></ul>
					<p class="shop__empty"><?php esc_html_e( 'No products match those filters. Try removing one.', 'gt' ); ?></p>
				<?php endif; ?>
			</div>

		</div>
	</div>

<?php
get_footer();
