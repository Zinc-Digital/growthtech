<?php
require_once __DIR__ . '/lib/bootstrap.php';

$html = gt_fetch( '/brand/clonex/' );
gt_assert_contains( 'class="page-wrapper brand-page"', $html, 'brand template renders' );
gt_assert_contains( '--brand-accent: #FBC707', $html, 'accent variable' );

// Hero
gt_assert_contains( 'class="brand-hero"', $html, 'hero' );
gt_assert_contains( 'The original rooting gel.', $html, 'hero heading' );
gt_assert_contains( '<em class="brand-hero__accent">A complete propagation system.</em>', $html, 'hero accent line' );
gt_assert_contains( 'still the market leader', $html, 'hero intro' );
gt_assert_contains( 'brand=clonex', $html, 'stockist CTA carries the brand' );
gt_assert_contains( 'href="#range"', $html, 'explore the range anchor' );
gt_assert_contains( '>Explore the Clonex Range<', $html, 'explore link label' );
gt_assert_contains( 'brand-hero__img', $html, 'hero image' );

// Science + steps + FAQ
gt_assert_contains( 'class="brand-science"', $html, 'science section' );
gt_assert_contains( 'Rooted in science.', $html, 'science heading' );
gt_assert_contains( '<em class="brand-science__accent">Built for success.</em>', $html, 'science accent line' );
gt_assert_equal( 3, substr_count( $html, 'class="brand-steps__step"' ), 'three steps' );
gt_assert_contains( 'data-block-slider', $html, 'steps are a slider' );
gt_assert_contains( 'data-progress="1"', $html, 'steps slider has the progress indicator' );
gt_assert_contains( 'data-slider-dots', $html, 'progress target' );
gt_assert_contains( 'data-slider-arrows', $html, 'arrows target' );
gt_assert_contains( 'Step 2 • Mist', $html, 'step label' );
gt_assert_contains( 'brand-steps__marker', $html, 'plus marker' );
gt_assert_contains( 'style="left: 73%; top: 47%;"', $html, 'marker positioned from the fields' );
gt_assert_contains( '>Clonex Mist</h3>', $html, 'step title from the product' );
gt_assert_contains( '>View Product<', $html, 'step link' );
gt_assert_contains( 'class="brand-faq"', $html, 'FAQ box' );
gt_assert_contains( 'dip straight into the bottle', $html, 'FAQ question' );
gt_assert_contains( 'More answers in the Plant Academy', $html, 'FAQ link' );
gt_assert_contains( 'assets/js/block-slider.js', $html, 'block slider script enqueued' );

// Range
gt_assert_contains( 'id="range"', $html, 'range anchor target' );
gt_assert_contains( 'brand-range__title">The Clonex Range<', $html, 'range title' );
gt_assert_contains( 'Showing 4 of 4 products', $html, 'range count' );
$start = strpos( $html, 'class="brand-range' );
$end   = strpos( $html, 'class="brand-pair', $start );
gt_assert_equal( 4, substr_count( substr( $html, $start, $end - $start ), '<li class="product-card ' ), 'four cards in the range' );

// Pair + guides
gt_assert_contains( 'class="brand-pair"', $html, 'pair band' );
gt_assert_contains( 'Pair with Root Riot', $html, 'pair heading' );
gt_assert_contains( 'brand-pair__logo', $html, 'paired brand logo/name' );
gt_assert_contains( 'class="brand-guides"', $html, 'guides band' );
gt_assert_contains( '<em class="brand-guides__accent">Stronger roots.</em>', $html, 'guides accent' );
gt_assert_equal( 2, substr_count( $html, 'class="brand-guides__card"' ), 'two guide cards' );
gt_assert_contains( '<strong>From Seed to Sprout:</strong>', $html, 'guide lead bold' );
gt_assert_contains( '>Propagation Guides<', $html, 'guides button' );
gt_assert_not_contains( 'shop-filters', $html, 'no shop sidebar on the brand page' );

// Brand without landing content: hero fallback + range only.
$html = gt_fetch( '/brand/ionic/' );
gt_assert_contains( '<h1 class="brand-hero__title">Ionic</h1>', $html, 'hero falls back to the brand name' );
gt_assert_not_contains( 'class="brand-science"', $html, 'no science section' );
gt_assert_not_contains( 'class="brand-pair"', $html, 'no pair band' );
gt_assert_not_contains( 'class="brand-guides"', $html, 'no guides band' );
gt_assert_contains( 'Showing 3 of 3 products', $html, 'Ionic range count' );

$html = gt_fetch( '/brand/smc/' );
gt_assert_contains( 'brand-range__empty', $html, 'empty range message for a brand with no products' );

gt_test_done();
