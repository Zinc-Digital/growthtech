<?php
require_once __DIR__ . '/lib/bootstrap.php';

$html = gt_fetch( '/product/clonex-mist/' );
gt_assert_contains( 'data-product-tabs', $html, 'tabs rendered' );
gt_assert_contains( 'role="tablist"', $html, 'tablist role' );
gt_assert_equal( 4, substr_count( $html, 'role="tab"' ), 'four tabs' );
gt_assert_equal( 4, substr_count( $html, 'role="tabpanel"' ), 'four panels' );
gt_assert_contains( 'data-tab="science" aria-selected="true"', $html, 'first tab selected' );
gt_assert_contains( 'data-tab="how-to-use" aria-selected="false"', $html, 'second tab not selected' );
gt_assert_contains( '>The science<', $html, 'tab label' );
gt_assert_contains( '>Useful Documents<', $html, 'documents tab label' );
gt_assert_equal( 2, substr_count( $html, 'class="product-tabs__column"' ), 'science renders two columns' );
gt_assert_contains( 'class="product-tabs__heading">Cellular Nutrition &amp; Stress Mitigation<', $html, 'science heading' );
gt_assert_contains( 'do not dilute', $html, 'how to use content' );
gt_assert_equal( 3, substr_count( $html, 'class="product-spec__row"' ), 'three spec rows' );
gt_assert_contains( '<dt class="product-spec__label">Shelf life</dt>', $html, 'spec label' );
gt_assert_equal( 1, substr_count( $html, 'class="product-docs__link"' ), 'one useful document' );
gt_assert_contains( '<span class="product-docs__icon" aria-hidden="true"><svg', $html, 'document tile icon' );
gt_assert_contains( '<rect width="35" height="35" fill="currentColor" />', $html, 'the tile is the icon\'s own square' );
gt_assert_not_contains( 'class="product-docs__icon" aria-hidden="true"><svg width="14"', $html, 'old download glyph gone' );
gt_assert_equal( 4, substr_count( $html, 'data-tab-acc' ), 'accordion heading per panel' );
gt_assert_contains( 'assets/js/product-tabs.js', $html, 'tabs script enqueued' );
// Non-first panels are hidden server-side so no-JS shows the first tab's content.
gt_assert_contains( 'data-tab-panel="how-to-use" hidden', $html, 'later panels hidden by default' );

$html = gt_fetch( '/product/budget-propagator/' );
gt_assert_not_contains( 'data-product-tabs', $html, 'no tabs when every tab is empty' );

gt_test_done();
