<?php
require_once __DIR__ . '/lib/bootstrap.php';

$mist   = wc_get_product( wc_get_product_id_by_sku( 'GT-001' ) );
$rr     = wc_get_product( wc_get_product_id_by_sku( 'GT-002' ) );
$clonex = get_term_by( 'slug', 'clonex', 'product_brand' );
$stockist_url = get_field( 'shop_stockist_page', 'option' );

gt_assert_equal( add_query_arg( array( 'product' => $mist->get_id(), 'region' => 'uk' ), $stockist_url ), gt_product_stockist_url( $mist ), 'stockist url carries product id and region' );
gt_assert_equal( add_query_arg( array( 'product' => $mist->get_id() ), get_field( 'shop_experts_link', 'option' )['url'] ), gt_product_experts_url( $mist ), 'experts url carries product id' );
gt_assert_equal( 'Ask our experts', gt_product_experts_label(), 'experts label from the link title' );
gt_assert_equal( add_query_arg( array( 'brand' => 'clonex' ), $stockist_url ), gt_brand_stockist_url( $clonex ), 'brand stockist url carries the brand slug' );

$related = gt_product_related( $mist );
gt_assert_equal( array( 'Clonex Rooting Hormone', 'Clonex Pro Start', 'Clonex Mist Concentrate' ), array_map( function ( $p ) { return $p->get_name(); }, $related ), 'related falls back to same-brand products excluding the current' );
$related = gt_product_related( $rr );
gt_assert_equal( array( 'Clonex Mist', 'Clonex Rooting Hormone' ), array_map( function ( $p ) { return $p->get_name(); }, $related ), 'related uses upsells in order when set' );
gt_assert_equal( 1, count( gt_product_related( $mist, 1 ) ), 'related respects the limit' );

gt_assert_equal( array( 'Independently tested', 'Made in Somerset', 'Registered product' ), wp_list_pluck( gt_product_badges( $mist ), 'name' ), 'badges in name order' );
gt_assert_equal( array(), gt_product_badges( $rr ), 'no badges → empty array' );

$band = gt_knowledge_band( $mist->get_id() );
gt_assert_equal( 'Better knowledge. Stronger roots.', $band['heading'], 'knowledge band falls back to Theme Settings' );
gt_assert( (int) $band['image'] > 0, 'knowledge band default image' );
update_field( 'field_gt_pd_knowledge', array( 'heading' => 'Override heading', 'text' => '', 'image' => '', 'link' => '' ), $mist->get_id() );
$band = gt_knowledge_band( $mist->get_id() );
gt_assert_equal( 'Override heading', $band['heading'], 'product override wins for the heading' );
gt_assert( (int) $band['image'] > 0, 'unset override fields fall back individually' );
update_field( 'field_gt_pd_knowledge', array( 'heading' => '', 'text' => '', 'image' => '', 'link' => '' ), $mist->get_id() );

// Brand archives are unpaginated (Plan 1's archive template still renders them until Task 8).
$html = gt_fetch( '/brand/clonex/' );
gt_assert_contains( 'Showing 4 of 4 products', $html, 'brand archive lists every brand product' );

foreach ( array( 'gt-product-main', 'gt-product-thumb', 'gt-brand-hero', 'gt-brand-hero-sm', 'gt-brand-step', 'gt-brand-step-sm', 'gt-brand-pair', 'gt-brand-pair-sm', 'gt-knowledge', 'gt-knowledge-sm' ) as $size ) {
	gt_assert( has_image_size( $size ), "image size {$size} registered" );
}

gt_test_done();
