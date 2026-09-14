<?php
/**
 * Seed product-page and brand-landing content on top of seed-shop.php.
 * Idempotent: images are matched by filename, content is overwritten.
 *
 * Run: tests/bin/wpx eval-file tests/seed/seed-product-content.php
 */

if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'update_field' ) ) {
	WP_CLI::error( 'WooCommerce and ACF must be active.' );
}
if ( ! function_exists( 'imagecreatetruecolor' ) ) {
	WP_CLI::error( 'GD is required to generate placeholder images.' );
}
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

/**
 * A flat-colour PNG with a label, uploaded once per filename. Returns the
 * attachment id.
 */
function gt_seed_image( $slug, $label, $width, $height, array $rgb ) {
	$filename = 'gt-seed-' . sanitize_title( $slug ) . '.png';
	$existing = get_posts( array(
		'post_type'      => 'attachment',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_gt_seed_image', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'     => $filename,         // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	if ( $existing ) {
		return (int) $existing[0];
	}

	$img = imagecreatetruecolor( $width, $height );
	$bg  = imagecolorallocate( $img, $rgb[0], $rgb[1], $rgb[2] );
	imagefill( $img, 0, 0, $bg );
	// A darker "product" block in the middle so contain/cover crops are visible.
	$fg = imagecolorallocate( $img, max( 0, $rgb[0] - 60 ), max( 0, $rgb[1] - 60 ), max( 0, $rgb[2] - 60 ) );
	imagefilledrectangle( $img, (int) ( $width * 0.3 ), (int) ( $height * 0.1 ), (int) ( $width * 0.7 ), (int) ( $height * 0.9 ), $fg );
	$white = imagecolorallocate( $img, 255, 255, 255 );
	imagestring( $img, 5, 10, 10, $label, $white );

	$tmp = wp_tempnam( $filename );
	imagepng( $img, $tmp );
	imagedestroy( $img );

	$id = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $tmp ), 0, $label );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( 'image ' . $filename . ': ' . $id->get_error_message() );
	}
	update_post_meta( $id, '_gt_seed_image', $filename );
	return (int) $id;
}

function gt_seed_pdf( $slug, $label ) {
	$filename = 'gt-seed-' . sanitize_title( $slug ) . '.pdf';
	$existing = get_posts( array( 'post_type' => 'attachment', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_gt_seed_image', 'meta_value' => $filename ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( $existing ) {
		return (int) $existing[0];
	}
	$tmp = wp_tempnam( $filename );
	// Minimal single-page PDF.
	file_put_contents( $tmp, "%PDF-1.1\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$id = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $tmp ), 0, $label );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( 'pdf ' . $filename . ': ' . $id->get_error_message() );
	}
	update_post_meta( $id, '_gt_seed_image', $filename );
	return (int) $id;
}

// -- Product images ----------------------------------------------------------

$hues = array( array( 210, 220, 200 ), array( 200, 215, 225 ), array( 225, 210, 200 ), array( 215, 205, 225 ) );
$products = wc_get_products( array( 'limit' => -1, 'status' => 'publish', 'orderby' => 'ID', 'order' => 'ASC' ) );
foreach ( $products as $i => $product ) {
	$sku = $product->get_sku() ?: 'p' . $product->get_id();
	$product->set_image_id( gt_seed_image( $sku . '-main', $product->get_name(), 800, 1000, $hues[ $i % 4 ] ) );
	if ( 'GT-001' === $sku ) {
		$product->set_gallery_image_ids( array(
			gt_seed_image( $sku . '-alt-1', $product->get_name() . ' angle', 800, 1000, array( 190, 200, 210 ) ),
			gt_seed_image( $sku . '-alt-2', $product->get_name() . ' detail', 800, 1000, array( 200, 190, 210 ) ),
		) );
	}
	$product->save();
}
WP_CLI::log( 'product images set' );

// -- Badges --------------------------------------------------------------------

$badge_ids = array();
foreach ( array( 'Registered product', 'Independently tested', 'Made in Somerset' ) as $badge ) {
	$term = term_exists( $badge, 'product_badge' );
	if ( ! $term ) {
		$term = wp_insert_term( $badge, 'product_badge' );
	}
	$badge_ids[] = (int) $term['term_id'];
}

// -- Clonex Mist content --------------------------------------------------------

$mist_id = wc_get_product_id_by_sku( 'GT-001' );
wp_set_object_terms( $mist_id, $badge_ids, 'product_badge' );
update_field( 'field_gt_pd_features', array(
	array( 'lead' => 'Direct foliar absorption', 'text' => 'delivers immediate amino acids and vital minerals directly to foliage' ),
	array( 'lead' => 'Accelerates rooting', 'text' => 'promotes faster, thicker, and more uniform root development' ),
	array( 'lead' => 'Flexible integration', 'text' => 'works effectively standalone or paired with Clonex Rooting Gel' ),
	array( 'lead' => 'Ready-to-use formula', 'text' => 'pre-mixed spray, ideal for pre-treating mother plants and fresh cuttings' ),
), $mist_id );
update_field( 'field_gt_pd_downloads', array(
	array( 'label' => 'Safety Data Sheet (PDF)', 'file' => gt_seed_pdf( 'sds-mist', 'Clonex Mist SDS' ) ),
	array( 'label' => 'Growing Schedule (PDF)', 'file' => gt_seed_pdf( 'schedule-mist', 'Clonex Mist growing schedule' ) ),
), $mist_id );
update_field( 'field_gt_pd_science', array(
	array( 'heading' => 'Cellular Nutrition & Stress Mitigation', 'text' => '<p>Before fresh cuttings develop roots, they depend entirely on foliage to survive. Clonex® Mist delivers a bio-available blend of complex amino acids and mineral nutrients directly through leaf tissue via foliar absorption. This immediate metabolic support bypasses the root system, drastically reducing moisture loss, transplant shock, and foliage yellowing during the vulnerable transition phase.</p>' ),
	array( 'heading' => 'Pre-Conditioning & Root Initiation', 'text' => '<p>Pre-treating mother plants pre-loads plant tissue with mobile nitrogen, calcium, and key amino acids right at the nodes. When cuttings are taken, this localized nutrient reservoir accelerates initial cell division, prompting faster, thicker, and more uniform root emergence without depleting the cutting\'s internal energy reserves.</p>' ),
), $mist_id );
update_field( 'field_gt_pd_how_to_use', '<p>Shake well. Mist mother plants 2–3 days before taking cuttings, then mist cuttings lightly every other day until rooted. Clonex Mist is ready to use — do not dilute.</p>', $mist_id );
update_field( 'field_gt_pd_specification', array(
	array( 'label' => 'Form', 'value' => 'Ready-to-use foliar spray' ),
	array( 'label' => 'Sizes', 'value' => '100ml, 300ml, 750ml' ),
	array( 'label' => 'Shelf life', 'value' => '2 years unopened' ),
), $mist_id );
update_field( 'field_gt_pd_documents', array(
	array( 'label' => 'Clonex Mist product sheet (PDF)', 'file' => gt_seed_pdf( 'sheet-mist', 'Clonex Mist product sheet' ) ),
), $mist_id );
update_field( 'field_gt_pd_knowledge', array( 'heading' => '', 'text' => '', 'image' => '', 'link' => '' ), $mist_id );

// Clear extra content on every other product so the seed is idempotent.
foreach ( $products as $product ) {
	if ( $product->get_id() === $mist_id ) {
		continue;
	}
	// ACF's update_field( $key, array(), $id ) on a repeater stores the row
	// count as an empty string rather than "0", which makes get_field()
	// return false (not array()) afterwards. delete_field() leaves the field
	// truly unset, so get_field() returns null and (array) casts to array().
	foreach ( array( 'field_gt_pd_features', 'field_gt_pd_downloads', 'field_gt_pd_science', 'field_gt_pd_specification', 'field_gt_pd_documents' ) as $key ) {
		delete_field( $key, $product->get_id() );
	}
	update_field( 'field_gt_pd_how_to_use', '', $product->get_id() );
	wp_set_object_terms( $product->get_id(), array(), 'product_badge' );
}

// Root Riot upsells → Clonex Mist + Rooting Hormone (tests "Complete the system" via upsells).
$rr = wc_get_product( wc_get_product_id_by_sku( 'GT-002' ) );
$rr->set_upsell_ids( array( $mist_id, wc_get_product_id_by_sku( 'GT-003' ) ) );
$rr->save();

// -- Pages & options --------------------------------------------------------------

$stockist = get_page_by_path( 'find-a-stockist' );
if ( ! $stockist ) {
	$stockist_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Find a Stockist', 'post_name' => 'find-a-stockist', 'post_content' => '' ) );
} else {
	$stockist_id = $stockist->ID;
}
$contact = get_page_by_path( 'contact-us' );
if ( ! $contact ) {
	$contact_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Contact Us', 'post_name' => 'contact-us', 'post_content' => '<p>Contact form placeholder.</p>' ) );
} else {
	$contact_id = $contact->ID;
}
update_field( 'field_gt_shop_stockist_page', get_permalink( $stockist_id ), 'option' );
update_field( 'field_gt_shop_experts_link', array( 'title' => 'Ask our experts', 'url' => get_permalink( $contact_id ), 'target' => '' ), 'option' );
update_field( 'field_gt_shop_knowledge_heading', 'Better knowledge. Stronger roots.', 'option' );
update_field( 'field_gt_shop_knowledge_text', 'Discover how to get maximum performance out of your products with step-by-step tutorials, expert protocols and care routines from the Plant Academy.', 'option' );
update_field( 'field_gt_shop_knowledge_image', gt_seed_image( 'knowledge-band', 'Knowledge', 1340, 300, array( 60, 80, 60 ) ), 'option' );
update_field( 'field_gt_shop_knowledge_link', array( 'title' => 'Explore the Plant Academy', 'url' => home_url( '/plant-academy/' ), 'target' => '' ), 'option' );

// -- Clonex brand landing -----------------------------------------------------------

$clonex = get_term_by( 'slug', 'clonex', 'product_brand' );
$rr_term = get_term_by( 'slug', 'root-riot', 'product_brand' );
$t = 'term_' . $clonex->term_id;
update_field( 'field_gt_brand_lp_hero_heading', 'The original rooting gel.', $t );
update_field( 'field_gt_brand_lp_hero_accent', 'A complete propagation system.', $t );
update_field( 'field_gt_brand_lp_hero_intro', 'Invented by Growth Technology in the 1980s — the first rooting gel on the market, and still the market leader on three continents. Three products, one complete propagation system.', $t );
update_field( 'field_gt_brand_lp_hero_image', gt_seed_image( 'clonex-hero', 'Clonex range', 1440, 500, array( 20, 20, 20 ) ), $t );
update_field( 'field_gt_brand_lp_science_heading', 'Rooted in science.', $t );
update_field( 'field_gt_brand_lp_science_accent', 'Built for success.', $t );
update_field( 'field_gt_brand_lp_science_text', '<p>Successful propagation isn\'t a guessing game—it\'s a precise science. When growers understand how young plants heal, feed, and root, predictable results naturally follow. Decades ago, Clonex® set the industry standard by bridging plant physiology with high-performance formulas, giving every cutting the strongest possible start in life.</p><p>The mission extends far beyond product performance: it’s about educating, empowering, and helping growers master the art and science of plant propagation.</p>', $t );
update_field( 'field_gt_brand_lp_steps', array(
	array( 'label' => 'Step 1 • Gel', 'product' => wc_get_product_id_by_sku( 'GT-003' ), 'image' => gt_seed_image( 'clonex-step-1', 'Step 1', 858, 840, array( 120, 140, 110 ) ), 'hotspot_x' => 60, 'hotspot_y' => 55, 'text' => 'Dip the cutting into Clonex Rooting Hormone gel to seal the cut and supply the hormones roots need to start.' ),
	array( 'label' => 'Step 2 • Mist', 'product' => $mist_id, 'image' => gt_seed_image( 'clonex-step-2', 'Step 2', 858, 840, array( 110, 130, 120 ) ), 'hotspot_x' => 73, 'hotspot_y' => 47, 'text' => 'Rootless cuttings rely on their leaves for nourishment, so Clonex® Mist delivers targeted amino acids and minerals directly to foliage—minimizing plant stress and accelerating root development.' ),
	array( 'label' => 'Step 3 • Feed', 'product' => wc_get_product_id_by_sku( 'GT-004' ), 'image' => gt_seed_image( 'clonex-step-3', 'Step 3', 858, 840, array( 130, 120, 110 ) ), 'hotspot_x' => 40, 'hotspot_y' => 60, 'text' => 'Clonex Pro Start feeds new roots with a gentle, balanced nutrient mix as the cutting establishes.' ),
), $t );
update_field( 'field_gt_brand_lp_faq', array( 'eyebrow' => 'What growers ask us most', 'question' => '“Can I dip straight into the bottle?”', 'answer' => 'No — decant a little into a separate dish and discard what’s left. One contaminated cutting can spoil a whole bottle, and the gel’s anti-fungal protects the plant, not the container.', 'link' => array( 'title' => 'More answers in the Plant Academy', 'url' => home_url( '/plant-academy/' ), 'target' => '' ) ), $t );
update_field( 'field_gt_brand_lp_pair_brand', $rr_term->term_id, $t );
update_field( 'field_gt_brand_lp_pair_image', gt_seed_image( 'clonex-pair', 'Root Riot cubes', 1340, 289, array( 25, 25, 25 ) ), $t );
update_field( 'field_gt_brand_lp_pair_heading', 'Pair with Root Riot® for maximum propagation success', $t );
update_field( 'field_gt_brand_lp_pair_text', 'Achieve higher strike rates and early vigour. Root Riot’s organic cubes hold the optimal air-to-water ratio, keeping nutrients where cuts need them.', $t );
update_field( 'field_gt_brand_lp_pair_link', array( 'title' => 'View Product', 'url' => get_permalink( wc_get_product_id_by_sku( 'GT-002' ) ), 'target' => '' ), $t );
update_field( 'field_gt_brand_lp_guides_heading', 'Better knowledge.', $t );
update_field( 'field_gt_brand_lp_guides_accent', 'Stronger roots.', $t );
update_field( 'field_gt_brand_lp_guides_text', 'Discover how to get maximum performance out of your Clonex® products with step-by-step tutorials, expert cloning protocols, and care routines. Learn the science behind every mist and gel application to eliminate guesswork and build explosive root systems.', $t );
update_field( 'field_gt_brand_lp_guides_cta1', array( 'title' => 'Propagation Guides', 'url' => home_url( '/plant-academy/propagation/' ), 'target' => '' ), $t );
update_field( 'field_gt_brand_lp_guides_cta2', array( 'title' => 'Explore the Plant Academy', 'url' => home_url( '/plant-academy/' ), 'target' => '' ), $t );
update_field( 'field_gt_brand_lp_guides', array(
	array( 'image' => gt_seed_image( 'clonex-guide-1', 'Guide 1', 694, 804, array( 150, 130, 110 ) ), 'lead' => 'From Seed to Sprout:', 'title' => 'The Beginner’s Guide to Perfect Germination', 'link' => array( 'title' => 'Read the Guide', 'url' => home_url( '/plant-academy/seed-to-sprout/' ), 'target' => '' ) ),
	array( 'image' => gt_seed_image( 'clonex-guide-2', 'Guide 2', 694, 804, array( 110, 150, 120 ) ), 'lead' => 'Rooting for Success:', 'title' => 'A Step-by-Step Guide to Propagating Stem Cuttings', 'link' => array( 'title' => 'Read the Guide', 'url' => home_url( '/plant-academy/stem-cuttings/' ), 'target' => '' ) ),
), $t );

// Every other brand: no landing content (tests the fallback page).
foreach ( get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => false ) ) as $brand ) {
	if ( $brand->term_id === $clonex->term_id ) {
		continue;
	}
	update_field( 'field_gt_brand_lp_hero_heading', '', 'term_' . $brand->term_id );
}

// Category hero image for Propagation, so the category page has a real hero too.
$prop = get_term_by( 'slug', 'propagation', 'product_cat' );
update_field( 'field_gt_cat_hero_image', gt_seed_image( 'propagation-hero', 'Propagation', 2680, 650, array( 40, 60, 40 ) ), 'term_' . $prop->term_id );

wc_delete_product_transients();
WP_CLI::success( 'Product and brand content seeded.' );
