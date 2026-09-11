<?php
require_once __DIR__ . '/lib/bootstrap.php';

$html = gt_fetch( '/shop/' );
gt_assert_contains( 'class="shop', $html, 'shop page uses the theme archive template' );
gt_assert_contains( 'shop-crumbs__current">Our Products<', $html, 'breadcrumb shows Our Products' );
gt_assert_not_contains( 'shop-hero', $html, 'no hero on the shop page' );
gt_assert_contains( 'Showing 12 of 13 products', $html, 'count text on page 1' );
gt_assert_equal( 12, substr_count( $html, '<li class="product-card ' ), '12 product cards on page 1' );
gt_assert_equal( 2, substr_count( $html, 'class="brand-promo-cell"' ), 'two promo tiles on page 1' );
gt_assert_contains( 'data-shop-more', $html, 'load more present when there is another page' );
gt_assert_contains( 'href="' . trailingslashit( wc_get_page_permalink( 'shop' ) ) . 'page/2/"', $html, 'load more links to page 2 without JS' );
gt_assert_contains( 'name="categories[]" value="propagation"', $html, 'category checkbox rendered' );
gt_assert_contains( 'name="brands[]" value="clonex"', $html, 'brand checkbox rendered' );
gt_assert_contains( 'shop-filters__item is-empty', $html, 'zero-count term (SMC) is greyed' );
gt_assert_contains( '<option value="brands" selected', $html, 'sort defaults to Our brands first' );
gt_assert_contains( 'shop-filters__group is-collapsed', $html, 'Growing Stage group starts collapsed' );
gt_assert_not_contains( 'add_to_cart_button', $html, 'no add-to-cart buttons in enquiry mode' );
gt_assert_not_contains( 'add-to-cart.min.js', $html, 'WC add-to-cart script dequeued in enquiry mode' );

$html = gt_fetch( '/shop/page/2/' );
gt_assert_contains( 'Showing 13 of 13 products', $html, 'count text on page 2' );
gt_assert_not_contains( 'data-shop-more', $html, 'no load more on the last page' );
gt_assert_equal( 0, substr_count( $html, 'class="brand-promo-cell"' ), 'no promo tiles on page 2' );

$html = gt_fetch( '/product-category/propagation/' );
gt_assert_contains( 'shop-hero', $html, 'category page has a hero' );
gt_assert_contains( 'Propagation - The science of the start.', $html, 'hero heading from ACF' );
gt_assert_contains( 'shop-crumbs__current">Propagation<', $html, 'breadcrumb current item is the category' );
gt_assert_contains( 'Showing 7 of 7 products', $html, 'category count' );
gt_assert_contains( 'name="categories[]" value="propagation" checked', $html, 'current category is ticked' );
gt_assert_contains( 'shop-filters__item is-checked', $html, 'ticked item gets the checked class' );

$html = gt_fetch( '/product-category/nutrients/' );
gt_assert_contains( 'shop-hero__title">Nutrients<', $html, 'hero falls back to the category name' );

$html = gt_fetch( '/shop/?brands=clonex&growing-medium=soil' );
gt_assert_contains( 'Showing 4 of 4 products', $html, 'GET filters work without JS' );
gt_assert_contains( 'name="brands[]" value="clonex" checked', $html, 'GET brand stays ticked' );

$html = gt_fetch( '/shop/?brands%5B%5D=clonex&growing-medium%5B%5D=soil' );
gt_assert_contains( 'Showing 4 of 4 products', $html, 'array-style checkbox params (no-JS submit) work' );
gt_assert_contains( 'name="brands[]" value="clonex" checked', $html, 'array-style params stay ticked' );

$html = gt_fetch( '/shop/?q=mist' );
gt_assert_contains( 'Showing 2 of 2 products', $html, 'GET search works' );
gt_assert_contains( 'value="mist"', $html, 'search box keeps its value' );

$html = gt_fetch( '/shop/?orderby=title-desc' );
$first = strpos( $html, 'product-card__title">' );
gt_assert_contains( 'Root Riot', substr( $html, $first, 80 ), 'GET sort applies' );

gt_test_done();
