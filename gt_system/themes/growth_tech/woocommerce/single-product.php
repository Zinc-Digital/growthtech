<?php
/**
 * Single product — Figma 384:1816 (Product Detail).
 *
 * Breadcrumb, gallery + summary, tabs, "Complete the system", the knowledge
 * band. Each part hides itself when it has nothing to show. The Phase Two
 * price/add-to-cart slot is the woocommerce_single_product_summary action
 * fired inside product-summary.php.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	global $product;

	if ( ! $product instanceof WC_Product ) {
		continue;
	}

	$brand  = gt_product_brand( $product );
	$accent = $brand ? gt_brand_accent( $brand ) : '#FBC707';

	do_action( 'woocommerce_before_single_product' );
	?>

<main class="page-wrapper product-page" id="product-<?php the_ID(); ?>" style="--brand-accent: <?php echo esc_attr( $accent ); ?>">
	<div class="product-page__inner">

		<div class="product-page__crumb">
			<?php get_template_part( 'template-parts/shop/breadcrumb' ); ?>
		</div>

		<div class="product-page__top">
			<div class="product-page__gallery">
				<?php get_template_part( 'template-parts/shop/product-gallery', null, array( 'product' => $product ) ); ?>
			</div>
			<div class="product-page__summary">
				<?php get_template_part( 'template-parts/shop/product-summary', null, array( 'product' => $product, 'brand' => $brand ) ); ?>
			</div>
		</div>

		<div class="product-page__tabs">
			<?php get_template_part( 'template-parts/shop/product-tabs', null, array( 'product' => $product ) ); ?>
		</div>
	</div>

	<?php /* TASK 7: complete the system goes here */ ?>

	<div class="product-page__inner">
		<?php /* TASK 7: knowledge band goes here */ ?>
	</div>

	<?php do_action( 'woocommerce_after_single_product' ); ?>

<?php
endwhile;

get_footer();
