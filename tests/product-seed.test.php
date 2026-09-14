<?php
require_once __DIR__ . '/lib/bootstrap.php';

$mist_id = wc_get_product_id_by_sku( 'GT-001' );
$mist    = wc_get_product( $mist_id );
gt_assert( (int) $mist->get_image_id() > 0, 'Clonex Mist has a featured image' );
gt_assert_equal( 2, count( $mist->get_gallery_image_ids() ), 'Clonex Mist has 2 gallery images' );
gt_assert_equal( 4, count( (array) get_field( 'features', $mist_id ) ), 'Clonex Mist has 4 features' );
gt_assert_equal( 'Direct foliar absorption', get_field( 'features', $mist_id )[0]['lead'], 'first feature lead' );
gt_assert_equal( 2, count( (array) get_field( 'downloads', $mist_id ) ), 'Clonex Mist has 2 downloads' );
gt_assert_equal( 2, count( (array) get_field( 'science', $mist_id ) ), 'Clonex Mist has 2 science columns' );
gt_assert_contains( 'Mist', (string) get_field( 'how_to_use', $mist_id ), 'how to use seeded' );
gt_assert_equal( 3, count( (array) get_field( 'specification', $mist_id ) ), '3 spec rows' );
gt_assert_equal( 1, count( (array) get_field( 'documents', $mist_id ) ), '1 useful document' );
$badges = wp_get_post_terms( $mist_id, 'product_badge', array( 'fields' => 'names' ) );
gt_assert_equal( array( 'Independently tested', 'Made in Somerset', 'Registered product' ), $badges, 'badges assigned (alphabetical)' );

$rr = wc_get_product( wc_get_product_id_by_sku( 'GT-002' ) );
gt_assert_equal( array( $mist_id, wc_get_product_id_by_sku( 'GT-003' ) ), array_map( 'intval', $rr->get_upsell_ids() ), 'Root Riot upsells' );

$hormone = wc_get_product( wc_get_product_id_by_sku( 'GT-003' ) );
gt_assert( (int) $hormone->get_image_id() > 0, 'every product has a featured image' );
gt_assert_equal( array(), (array) get_field( 'features', $hormone->get_id() ), 'other products have no extra content' );

$clonex = get_term_by( 'slug', 'clonex', 'product_brand' );
gt_assert_equal( 'The original rooting gel.', get_field( 'hero_heading', $clonex ), 'brand hero heading' );
gt_assert_equal( 'A complete propagation system.', get_field( 'hero_accent_line', $clonex ), 'brand hero accent line' );
gt_assert( (int) get_field( 'hero_image', $clonex ) > 0, 'brand hero image' );
gt_assert_equal( 3, count( (array) get_field( 'steps', $clonex ) ), '3 steps' );
gt_assert_equal( $mist_id, (int) get_field( 'steps', $clonex )[1]['product'], 'step 2 is Clonex Mist' );
gt_assert_contains( 'bottle', get_field( 'faq', $clonex )['question'], 'FAQ question' );
$rr_term = get_term_by( 'slug', 'root-riot', 'product_brand' );
gt_assert_equal( (int) $rr_term->term_id, (int) get_field( 'pair_brand', $clonex ), 'pair brand is Root Riot' );
gt_assert_equal( 2, count( (array) get_field( 'guides', $clonex ) ), '2 guides' );

$ionic = get_term_by( 'slug', 'ionic', 'product_brand' );
gt_assert_equal( '', (string) get_field( 'hero_heading', $ionic ), 'Ionic has no landing content' );

gt_assert_equal( 'Better knowledge. Stronger roots.', get_field( 'shop_knowledge_heading', 'option' ), 'knowledge default heading' );
gt_assert( (int) get_field( 'shop_knowledge_image', 'option' ) > 0, 'knowledge default image' );
$stockist = get_page_by_path( 'find-a-stockist' );
gt_assert( $stockist instanceof WP_Post, 'stockist page exists' );
gt_assert_equal( get_permalink( $stockist ), get_field( 'shop_stockist_page', 'option' ), 'stockist page set in options' );
gt_assert_contains( 'contact', (string) get_field( 'shop_experts_link', 'option' )['url'], 'experts link set' );

gt_test_done();
