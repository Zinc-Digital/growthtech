<?php
require_once __DIR__ . '/lib/bootstrap.php';

// Parsing.
$sel = gt_shop_selection( array( 'categories' => 'propagation, Nutrients', 'brands' => 'clonex', 'q' => ' mist ', 'orderby' => 'title-desc', 'paged' => '2', 'growing-medium' => '', 'junk' => 'x' ) );
gt_assert_equal( array( 'propagation', 'nutrients' ), $sel['categories'], 'categories parsed, trimmed, slugified' );
gt_assert_equal( array( 'clonex' ), $sel['brands'], 'brands parsed' );
gt_assert_equal( array(), $sel['growing-medium'], 'empty group is an empty array' );
gt_assert_equal( 'mist', $sel['q'], 'q trimmed' );
gt_assert_equal( 'title-desc', $sel['orderby'], 'orderby kept when valid' );
gt_assert_equal( 2, $sel['paged'], 'paged is an int' );
$sel = gt_shop_selection( array( 'orderby' => 'price', 'paged' => '-3' ) );
gt_assert_equal( 'brands', $sel['orderby'], 'invalid orderby falls back to brands' );
gt_assert_equal( 1, $sel['paged'], 'paged never below 1' );
gt_assert_equal( 'mist', gt_shop_selection( array( 'q' => array( 'mist', 'x' ) ) )['q'], 'array q uses its first value' );
gt_assert_equal( 'brands', gt_shop_selection( array( 'orderby' => array( 'price' ) ) )['orderby'], 'array orderby falls back safely' );

// Tax query.
$sel = gt_shop_selection( array( 'categories' => 'propagation', 'growing-medium' => 'soil,coco' ) );
$tax = gt_shop_tax_query( $sel );
gt_assert_equal( 2, count( $tax ), 'one clause per active group' );
gt_assert_equal( 'pa_growing-medium', $tax[1]['taxonomy'], 'attribute group maps to its pa_ taxonomy' );
gt_assert_equal( array( 'soil', 'coco' ), $tax[1]['terms'], 'terms passed as slugs' );

// Matching ids & counts (seeded data).
gt_assert_equal( 13, count( gt_shop_matching_ids( gt_shop_selection( array() ) ) ), 'no filters matches all 13' );
gt_assert_equal( 7, count( gt_shop_matching_ids( gt_shop_selection( array( 'categories' => 'propagation' ) ) ) ), 'propagation matches 7' );
gt_assert_equal( 4, count( gt_shop_matching_ids( gt_shop_selection( array( 'categories' => 'propagation', 'brands' => 'clonex' ) ) ) ), 'propagation + clonex matches 4' );
gt_assert_equal( 2, count( gt_shop_matching_ids( gt_shop_selection( array( 'q' => 'mist' ) ) ) ), 'search "mist" matches 2' );

$counts = gt_shop_term_counts( 'categories', gt_shop_selection( array( 'brands' => 'clonex' ) ) );
gt_assert_equal( 4, $counts['propagation'], 'category counts respect the brand filter' );
gt_assert_equal( 0, $counts['nutrients'], 'zero count reported for empty combination' );
$counts = gt_shop_term_counts( 'brands', gt_shop_selection( array( 'brands' => 'clonex' ) ) );
gt_assert_equal( 3, $counts['ionic'], 'a group does not constrain its own counts' );
gt_assert( ! isset( $counts['uncategorized'] ), 'default category never appears' );

// Promos.
$promos = gt_shop_promos( gt_shop_selection( array() ) );
gt_assert_equal( array( 2, 7 ), array_keys( $promos ), 'shop page promos at positions 2 and 7' );
gt_assert_equal( 'clonex', $promos[2]->slug, 'position 2 is Clonex' );
gt_assert_equal( array( 2, 7 ), array_keys( gt_shop_promos( gt_shop_selection( array( 'categories' => 'propagation' ) ) ) ), 'both brands have propagation products' );
gt_assert_equal( array(), gt_shop_promos( gt_shop_selection( array( 'categories' => 'nutrients' ) ) ), 'no promos where the brand has no products' );
gt_assert_equal( array(), gt_shop_promos( gt_shop_selection( array( 'brands' => 'ionic' ) ) ), 'no promos when a brand filter is active' );
gt_assert_equal( array(), gt_shop_promos( gt_shop_selection( array( 'paged' => 2 ) ) ), 'no promos after page 1' );

// URLs.
$shop = wc_get_page_permalink( 'shop' );
gt_assert_equal( $shop, gt_shop_build_url( gt_shop_selection( array() ) ), 'empty selection is the shop url' );
gt_assert_equal( get_term_link( 'propagation', 'product_cat' ), gt_shop_build_url( gt_shop_selection( array( 'categories' => 'propagation' ) ) ), 'single category is the category permalink' );
gt_assert_equal( add_query_arg( array( 'brands' => 'clonex' ), get_term_link( 'propagation', 'product_cat' ) ), gt_shop_build_url( gt_shop_selection( array( 'categories' => 'propagation', 'brands' => 'clonex' ) ) ), 'category permalink keeps other params' );
gt_assert_equal( add_query_arg( array( 'categories' => 'propagation,nutrients', 'orderby' => 'title' ), $shop ), gt_shop_build_url( gt_shop_selection( array( 'categories' => 'propagation,nutrients', 'orderby' => 'title' ) ) ), 'multi-category goes to shop with params' );
gt_assert_equal( add_query_arg( array( 'q' => 'mist' ), trailingslashit( $shop ) . 'page/2/' ), gt_shop_build_url( gt_shop_selection( array( 'q' => 'mist', 'paged' => 2 ) ) ), 'paged urls use /page/N/' );
gt_assert_equal( add_query_arg( array( 'q' => rawurlencode( 'root & riot=x' ) ), $shop ), gt_shop_build_url( gt_shop_selection( array( 'q' => 'root & riot=x' ) ) ), 'query values are url-encoded' );

// Ordering.
$all    = new WP_Query( array_merge( gt_shop_query_args( gt_shop_selection( array() ) ), array( 'posts_per_page' => -1 ) ) );
$titles = wp_list_pluck( $all->posts, 'post_title' );
gt_assert_equal( 'Nitrozyme', end( $titles ), 'own brands first: third-party Nitrozyme is last of all 13' );
$q = new WP_Query( gt_shop_query_args( gt_shop_selection( array() ) ) );
gt_assert_equal( 12, count( $q->posts ), 'query respects per page' );
gt_assert_equal( 13, (int) $q->found_posts, 'found_posts is the full total' );
$q = new WP_Query( gt_shop_query_args( gt_shop_selection( array( 'orderby' => 'title-desc' ) ) ) );
gt_assert_equal( 'Root Riot', $q->posts[0]->post_title, 'Z-A sort' );

gt_assert_equal( 'Showing 7 of 26 products', gt_shop_count_text( 7, 26 ), 'count text' );

gt_test_done();
