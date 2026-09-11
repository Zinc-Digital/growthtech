<?php
require_once __DIR__ . '/lib/bootstrap.php';

function gt_ajax( array $params ) {
	$url  = admin_url( 'admin-ajax.php?' . http_build_query( array_merge( array( 'action' => 'gt_shop_filter' ), $params ) ) );
	$body = wp_remote_retrieve_body( wp_remote_get( $url, array( 'sslverify' => false, 'timeout' => 60 ) ) );
	return json_decode( $body, true );
}

$res = gt_ajax( array() );
gt_assert( ! empty( $res['success'] ), 'endpoint returns success' );
$d = $res['data'];
gt_assert_equal( 12, substr_count( $d['grid'], '<li class="product-card ' ), 'grid has 12 cards' );
gt_assert_equal( 2, substr_count( $d['grid'], 'class="brand-promo-cell"' ), 'grid has promos on page 1' );
gt_assert_contains( 'class="shop-filters"', $d['sidebar'], 'sidebar html returned' );
gt_assert_equal( 'Showing 12 of 13 products', $d['count'], 'count text' );
gt_assert_contains( 'data-shop-more', $d['more'], 'load more html returned' );
gt_assert_equal( wc_get_page_permalink( 'shop' ), $d['url'], 'canonical url' );
gt_assert_equal( 2, $d['total_pages'], 'total pages' );
gt_assert_not_contains( '>Nitrozyme<', $d['grid'], 'own brands first holds over AJAX (Nitrozyme pushed to page 2)' );

$res = gt_ajax( array( 'categories' => 'propagation', 'brands' => 'clonex' ) );
$d   = $res['data'];
gt_assert_equal( 'Showing 4 of 4 products', $d['count'], 'filtered count' );
gt_assert_equal( add_query_arg( array( 'brands' => 'clonex' ), get_term_link( 'propagation', 'product_cat' ) ), $d['url'], 'filtered canonical url' );
gt_assert_contains( 'name="brands[]" value="clonex" checked', $d['sidebar'], 'sidebar reflects selection' );
gt_assert_equal( '', $d['more'], 'no load more when one page' );
gt_assert_equal( array( 'propagation' ), $d['selection']['categories'], 'selection echoed back' );

$res = gt_ajax( array( 'paged' => 2, 'append' => 1 ) );
$d   = $res['data'];
gt_assert_equal( 1, substr_count( $d['grid'], '<li class="product-card ' ), 'page 2 append has the 13th card' );
gt_assert_equal( 0, substr_count( $d['grid'], 'brand-promo-cell' ), 'append never includes promos' );
gt_assert_equal( 'Showing 13 of 13 products', $d['count'], 'append count is cumulative' );
gt_assert_equal( '', $d['more'], 'no more after last page' );

$res = gt_ajax( array( 'brands[]' => 'clonex' ) );
gt_assert_equal( 'Showing 4 of 4 products', $res['data']['count'], 'array-style params accepted' );

gt_test_done();
