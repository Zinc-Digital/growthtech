<?php
/**
 * Brand landing page — Figma 384:1489 (Brand Range Template).
 *
 * Hero, "Rooted in science" (steps slider + FAQ), the brand's range, a
 * cross-sell band and a guides band. Optional sections render nothing when
 * their term fields are empty; the range always shows.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$brand  = get_queried_object();
$brand  = $brand instanceof WP_Term ? $brand : null;
$accent = $brand ? gt_brand_accent( $brand ) : '#FBC707';
?>

<main class="page-wrapper brand-page" style="--brand-accent: <?php echo esc_attr( $accent ); ?>">
	<?php if ( $brand ) : ?>
		<?php get_template_part( 'template-parts/brand/hero', null, array( 'brand' => $brand ) ); ?>
		<?php get_template_part( 'template-parts/brand/science', null, array( 'brand' => $brand ) ); ?>
		<?php get_template_part( 'template-parts/brand/range', null, array( 'brand' => $brand ) ); ?>
		<?php get_template_part( 'template-parts/brand/pair', null, array( 'brand' => $brand ) ); ?>
		<?php get_template_part( 'template-parts/brand/guides', null, array( 'brand' => $brand ) ); ?>
	<?php endif; ?>

<?php
get_footer();
