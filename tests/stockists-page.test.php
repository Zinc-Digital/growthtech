<?php
require_once __DIR__ . '/lib/bootstrap.php';

/**
 * Isolate one card's HTML: from its own <li class="stockists-card" ...> tag
 * (not a "stockists-card__chip" child <li>, hence the closing quote) up to
 * the next one, so per-card assertions can't pick up a neighbouring card's
 * markup.
 */
function gt_stockist_extract_card( $html, $needle ) {
	$at         = strpos( $html, $needle );
	$card_start = strrpos( substr( $html, 0, $at ), '<li class="stockists-card"' );
	$card_end   = strpos( $html, '<li class="stockists-card"', $at );
	if ( false === $card_end ) {
		$card_end = strlen( $html );
	}
	return substr( $html, $card_start, $card_end - $card_start );
}

$mist_id = wc_get_product_id_by_sku( 'GT-001' );
$html    = gt_fetch( '/find-a-stockist/' );

gt_assert_contains( 'class="page-wrapper stockists"', $html, 'template renders' );
gt_assert_contains( '<h1 class="stockists__title">Find a Stockist</h1>', $html, 'title' );
gt_assert_contains( 'independent specialists', $html, 'intro' );
gt_assert_contains( 'data-has-key="0"', $html, 'no maps key flag' );
gt_assert_contains( 'stockists-map__placeholder', $html, 'map placeholder without a key' );
gt_assert_not_contains( 'maps.googleapis.com/maps/api/js', $html, 'Maps JS not loaded without a key' );
gt_assert_contains( 'option value="nearest" disabled', $html, 'nearest-first disabled without a key' );

gt_assert_equal( 8, substr_count( $html, '<li class="stockists-card"' ), 'all eight cards rendered' );
gt_assert_equal( 2, substr_count( $html, '<li class="stockists-card" hidden' ), 'the two international cards start hidden' );
gt_assert_contains( '>6 stockists<', $html, 'count reflects the UK default' );
gt_assert_contains( 'data-region="uk" aria-pressed="true"', $html, 'UK toggle active' );
gt_assert_contains( 'data-stockists-country', $html, 'country select present' );
gt_assert_contains( '<option value="IE">Ireland</option>', $html, 'country options from stockists present' );

// Somerset card.
$card = gt_stockist_extract_card( $html, 'data-name="Somerset Hydro Centre"' );
gt_assert_contains( 'data-country="GB"', $card, 'card country attr' );
gt_assert_contains( 'data-lat="51.0153"', $card, 'card lat attr' );
gt_assert_contains( 'data-products="', $card, 'card products attr' );
gt_assert_contains( 'data-brands="', $card, 'card brands attr' );
gt_assert_contains( 'Taunton, United Kingdom (UK)', $card, 'town + country' );
gt_assert_contains( 'Hydroponics specialist', $card, 'type' );
// Real product chips only — the "+N more" toggle is also a <li class="stockists-card__chip …">
// (it needs the base chip styling reset), so it's excluded by requiring an exact class match.
$chip_count = substr_count( $card, '<li class="stockists-card__chip">' ) + substr_count( $card, '<li class="stockists-card__chip is-extra">' );
gt_assert_equal( 7, $chip_count, 'seven product chips' );
gt_assert_equal( 4, substr_count( $card, 'is-extra' ), 'chips beyond three are marked extra' );
gt_assert_contains( '>+4 more<', $card, 'more toggle label' );
gt_assert_contains( 'google.com/maps/dir/?api=1&#038;destination=51.0153,-3.1069', $card, 'directions link uses the coordinates' );
gt_assert_contains( 'href="https://example.com/somerset-hydro"', $card, 'check stock links to the website' );
gt_assert_contains( 'data-card-map', $card, 'show on map button' );

$card = gt_stockist_extract_card( $html, 'data-name="Edinburgh Grow"' );
gt_assert_contains( 'href="tel:01310000000"', $card, 'no website → check stock links to the phone' );
gt_assert_not_contains( 'is-extra', $card, 'two chips → no extras' );

// Preselect from the product page link.
$html = gt_fetch( '/find-a-stockist/?product=' . $mist_id . '&region=uk' );
gt_assert_contains( '<option value="' . $mist_id . '" selected', $html, 'product preselected from the URL' );
gt_assert_contains( '>3 stockists<', $html, 'only UK stockists with Clonex Mist are visible' );
gt_assert_equal( 5, substr_count( $html, '<li class="stockists-card" hidden' ), 'the other five cards are hidden' );

$html = gt_fetch( '/find-a-stockist/?brand=ionic' );
gt_assert_contains( '>3 stockists<', $html, 'brand preselect: stockists with any Ionic product' );
gt_assert_contains( 'data-brand="ionic"', $html, 'brand preselect echoed for the JS' );

$html = gt_fetch( '/find-a-stockist/?region=international' );
gt_assert_contains( '>2 stockists<', $html, 'international region shows the two non-UK stockists' );
gt_assert_contains( 'data-region="international" aria-pressed="true"', $html, 'international toggle active' );

gt_assert_contains( 'knowledge-band knowledge-band--trade', $html, 'trade band reuses the knowledge band' );
gt_assert_contains( 'Run a store? Stock the originals.', $html, 'trade heading' );
gt_assert_contains( 'assets/js/stockists.js', $html, 'stockists script enqueued' );

gt_test_done();
