<?php
require_once __DIR__ . '/lib/bootstrap.php';

function gt_render_card( $sku ) {
	global $product, $post;
	$product = wc_get_product( wc_get_product_id_by_sku( $sku ) );
	$post    = get_post( $product->get_id() );
	setup_postdata( $post );
	ob_start();
	wc_get_template_part( 'content', 'product' );
	$html = ob_get_clean();
	wp_reset_postdata();
	return $html;
}

$html = gt_render_card( 'GT-001' );
gt_assert_contains( '<li class="product-card ', $html, 'card root class (wc_product_class puts ours first)' );
gt_assert_contains( 'href="' . get_permalink( wc_get_product_id_by_sku( 'GT-001' ) ) . '"', $html, 'card links to the product' );
gt_assert_contains( '<h2 class="product-card__title">Clonex Mist</h2>', $html, 'card title' );
gt_assert_contains( 'Propagation', $html, 'card shows primary category' );
gt_assert_contains( '100ml/300ml/750ml', $html, 'card joins sizes with slashes' );
gt_assert_contains( 'product-card__dot', $html, 'dot separator between category and sizes' );
gt_assert_contains( 'product-card__img', $html, 'card has an image (placeholder when none set)' );

$html = gt_render_card( 'GT-006' );
gt_assert_not_contains( 'product-card__dot', $html, 'no dot when the product has no sizes' );

$clonex = get_term_by( 'slug', 'clonex', 'product_brand' );
ob_start();
wc_get_template( 'content-brand-promo.php', array( 'brand' => $clonex ) );
$promo = ob_get_clean();
gt_assert_contains( 'class="brand-promo-cell"', $promo, 'promo tile root' );
gt_assert_contains( 'href="' . get_term_link( $clonex ) . '"', $promo, 'promo tile links to the brand archive' );
gt_assert_contains( '--brand-accent: #FBC707', $promo, 'promo tile carries the accent colour' );
gt_assert_contains( '<em>A complete propagation system.</em>', $promo, 'promo tagline keeps its <em>' );
gt_assert_contains( 'Explore the Clonex Range', $promo, 'promo link label' );

$plain = get_term_by( 'slug', 'ionic', 'product_brand' );
ob_start();
wc_get_template( 'content-brand-promo.php', array( 'brand' => $plain ) );
gt_assert_equal( '', trim( ob_get_clean() ), 'promo tile renders nothing for a brand without promo enabled' );

gt_test_done();
