# Shop Listing (Plan 1 of 3) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship the WooCommerce foundation plus the Shop and Category listing pages (Figma 384:1608 and 384:1267) — product card, brand promo tiles, sidebar filters with live counts, sort, AJAX filtering with URL sync, load more, and responsive layout.

**Architecture:** Standard WooCommerce template overrides under `woocommerce/` with `add_theme_support('woocommerce')`. A single "selection" array (parsed from `$_GET`, plus the current category on category archives) drives the main query, the AJAX endpoint, the sidebar counts and canonical URLs, so the no-JS GET path and the AJAX path render identical HTML from the same functions. Design-specific content (category hero, brand promo) lives in ACF field groups on WC terms. `GT_SHOP_ENQUIRY_MODE` gates every Phase-One-only hook removal.

**Tech Stack:** WordPress classic theme `growth_tech`, WooCommerce 11.1 (built-in `product_brand` taxonomy), ACF Pro 6.8 (JSON in `acf-json/`), SCSS compiled with `npx sass`, vanilla JS, WP-CLI via MAMP PHP for tests/seeding, headless Chrome for visual checks.

**Spec:** `docs/superpowers/specs/2026-09-11-woocommerce-shop-design.md` (sections 3–6, 10, 11). Plans 2 (single product + brand landing) and 3 (stockist finder) follow.

## Global Constraints

- Theme root: `gt_system/themes/growth_tech` — **all theme paths below are relative to it**; repo-root paths (`tests/`, `docs/`) are stated as such.
- Local site: `https://growth-tech.local` (MAMP, self-signed cert → `curl -k`). DB is only reachable through MAMP's PHP with the socket; use `tests/bin/wpx` for every WP-CLI call.
- Compile CSS after every SCSS change: `npx sass assets/sass/main.scss assets/css/main.css --style=compressed --source-map` (run from the theme root; `/` division deprecation warnings from existing files are expected and harmless).
- Conventions: BEM classes; SCSS partials in `assets/sass/components/shop/_s.*.scss` imported from `assets/sass/main.scss`; PHP escapes all output; sections with empty data render nothing; `gt_asset_version()` for enqueues; DM Sans (`"DM Sans", $primary_font`) for UI text, Cormorant Garamond 700 for display; theme breakpoints `$sm: 768px`, `$md: 1024px`, `$xl: 1266px`.
- Figma tokens (desktop 1440): page gutter 50px, content width 1340px, sidebar 300px (25px inner padding, bg `rgba(0,0,0,.05)`), column gap 25px, 3 columns of 321.67px, row gap 25px, card image tile 370px tall bg `rgba(35,31,32,.05)`, card title DM Sans 500 18px, card meta 12px `rgba(0,0,0,.5)`, toolbar 12px, breadcrumb 12px, black is `#000`.
- WooCommerce query vars used: `orderby`, `paged`. Theme query params: `categories`, `brands`, `growing-medium`, `growing-stage` (comma-separated slugs), `q` (text search).
- Field names on the options page are prefixed `shop_` (deviation from the spec's bare names — the options page is one shared namespace).
- Commit after each task with the trailer `Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>`.
- Do not touch the pre-existing uncommitted homepage-block changes in the working tree; stage only files this plan names.

---

### Task 1: Test harness and local environment

**Files:**
- Create: `tests/bin/wpx` (repo root)
- Create: `tests/lib/bootstrap.php`
- Create: `tests/run.sh`
- Create: `tests/smoke.test.php`
- Modify: `.gitignore` (repo root)

**Interfaces:**
- Produces: `tests/bin/wpx <wp args>` — WP-CLI against the local DB. `gt_assert($cond, $msg)`, `gt_assert_equal($expected, $actual, $msg)`, `gt_assert_contains($needle, $haystack, $msg)`, `gt_fetch($path)` (returns HTML body of `https://growth-tech.local{$path}`), `gt_test_done()`. Tests are `tests/*.test.php`, run via `tests/run.sh` (all) or `tests/bin/wpx eval-file tests/x.test.php` (one).

- [ ] **Step 1: Un-ignore `tests/` in `.gitignore`**

Edit `.gitignore` so the allow-list block reads:

```
!/.gitignore
!/README.md
!/gt_system
!/docs
!/tests
```

- [ ] **Step 2: Create the WP-CLI wrapper**

`tests/bin/wpx`:

```sh
#!/bin/sh
# WP-CLI against the MAMP site: MAMP's PHP + MySQL socket + site URL.
# Usage: tests/bin/wpx <wp args>
exec /Applications/MAMP/bin/php/php8.3.30/bin/php \
  -d mysqli.default_socket=/Applications/MAMP/tmp/mysql/mysql.sock \
  -d error_reporting="E_ALL&~E_DEPRECATED" \
  /opt/homebrew/bin/wp \
  --path="$(cd "$(dirname "$0")/../.." && pwd)" \
  --url=https://growth-tech.local "$@"
```

Run: `chmod +x tests/bin/wpx && tests/bin/wpx option get siteurl`
Expected: `http://growth-tech.local`

- [ ] **Step 3: Switch the site to pretty permalinks (WooCommerce archive URLs need them)**

Run:
```bash
tests/bin/wpx rewrite structure '/%postname%/' --hard
tests/bin/wpx rewrite flush --hard
curl -sk -o /dev/null -w "%{http_code}\n" https://growth-tech.local/shop/
```
Expected: last line `200`.

- [ ] **Step 4: Write the assertion library**

`tests/lib/bootstrap.php`:

```php
<?php
/**
 * Tiny assertion helpers for WP-CLI `eval-file` tests. WordPress is already
 * loaded when these run. Each test file: require this, assert, call
 * gt_test_done().
 */

$GLOBALS['gt_test_failures'] = 0;
$GLOBALS['gt_test_count']    = 0;

function gt_assert( $condition, $message ) {
	$GLOBALS['gt_test_count']++;
	if ( $condition ) {
		WP_CLI::log( '  ok   ' . $message );
	} else {
		$GLOBALS['gt_test_failures']++;
		WP_CLI::warning( 'FAIL ' . $message );
	}
}

function gt_assert_equal( $expected, $actual, $message ) {
	gt_assert(
		$expected === $actual,
		$message . ' (expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . ')'
	);
}

function gt_assert_contains( $needle, $haystack, $message ) {
	gt_assert( false !== strpos( (string) $haystack, $needle ), $message . ' (looking for "' . $needle . '")' );
}

function gt_assert_not_contains( $needle, $haystack, $message ) {
	gt_assert( false === strpos( (string) $haystack, $needle ), $message . ' (must not contain "' . $needle . '")' );
}

/** GET a front-end path over HTTPS (self-signed cert) and return the body. */
function gt_fetch( $path ) {
	$response = wp_remote_get( home_url( $path ), array( 'sslverify' => false, 'timeout' => 60 ) );
	if ( is_wp_error( $response ) ) {
		WP_CLI::warning( 'fetch failed: ' . $response->get_error_message() );
		return '';
	}
	return (string) wp_remote_retrieve_body( $response );
}

function gt_test_done() {
	if ( $GLOBALS['gt_test_failures'] ) {
		WP_CLI::error( $GLOBALS['gt_test_failures'] . ' assertion(s) failed' );
	}
	WP_CLI::success( $GLOBALS['gt_test_count'] . ' assertions passed' );
}
```

- [ ] **Step 5: Write the runner and a smoke test**

`tests/run.sh`:

```sh
#!/bin/sh
# Run every tests/*.test.php through WP-CLI. Exit 1 if any file fails.
cd "$(dirname "$0")/.." || exit 1
status=0
for f in tests/*.test.php; do
  echo "== $f"
  tests/bin/wpx eval-file "$f" || status=1
done
exit $status
```

`tests/smoke.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

gt_assert( class_exists( 'WooCommerce' ), 'WooCommerce is active' );
gt_assert( function_exists( 'get_field' ), 'ACF is active' );
gt_assert_equal( 'growth_tech', wp_get_theme()->get_stylesheet(), 'growth_tech theme is active' );
gt_assert_equal( '/%postname%/', get_option( 'permalink_structure' ), 'pretty permalinks are on' );
gt_assert_contains( '<html', gt_fetch( '/shop/' ), 'shop page responds with HTML' );

gt_test_done();
```

Run: `chmod +x tests/run.sh && tests/run.sh`
Expected: `Success: 5 assertions passed`

- [ ] **Step 6: Commit**

```bash
git add .gitignore tests/bin/wpx tests/lib/bootstrap.php tests/run.sh tests/smoke.test.php
git commit -m "Add WP-CLI test harness for the shop build

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 2: ACF field groups — brand, category, shop settings

**Files:**
- Create: `acf-json/brand.json`
- Create: `acf-json/product-category.json`
- Create: `acf-json/theme-settings-shop.json`
- Create: `tests/acf.test.php`

**Interfaces:**
- Produces field keys/names used by the seed and templates:
  - Brand (`product_brand` term): `field_gt_brand_logo`/`logo` (image id), `field_gt_brand_accent`/`accent_colour` (hex string), `field_gt_brand_own`/`own_brand` (bool), `field_gt_brand_promo_enabled`/`promo_enabled` (bool), `field_gt_brand_promo_image`/`promo_image` (image id), `field_gt_brand_promo_tagline`/`promo_tagline` (text, `<em>` allowed), `field_gt_brand_promo_position`/`promo_position` (int).
  - Category (`product_cat` term): `field_gt_cat_hero_image`/`hero_image` (image id), `field_gt_cat_hero_heading`/`hero_heading`, `field_gt_cat_hero_text`/`hero_text`.
  - Options: `field_gt_shop_per_page`/`shop_products_per_page` (int, default 12), `field_gt_shop_stockist_page`/`shop_stockist_page` (page link), `field_gt_shop_experts_link`/`shop_experts_link` (link array), `field_gt_shop_maps_key`/`shop_google_maps_key` (text).

- [ ] **Step 1: Write the failing test**

`tests/acf.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

foreach ( array( 'group_gt_brand', 'group_gt_product_category', 'group_gt_shop_settings' ) as $key ) {
	$group = acf_get_field_group( $key );
	gt_assert( ! empty( $group ), "field group {$key} is registered from acf-json" );
}

$brand_fields = wp_list_pluck( acf_get_fields( 'group_gt_brand' ) ?: array(), 'name' );
foreach ( array( 'logo', 'accent_colour', 'own_brand', 'promo_enabled', 'promo_image', 'promo_tagline', 'promo_position' ) as $name ) {
	gt_assert( in_array( $name, $brand_fields, true ), "brand group has field {$name}" );
}

$cat_fields = wp_list_pluck( acf_get_fields( 'group_gt_product_category' ) ?: array(), 'name' );
foreach ( array( 'hero_image', 'hero_heading', 'hero_text' ) as $name ) {
	gt_assert( in_array( $name, $cat_fields, true ), "category group has field {$name}" );
}

$shop_fields = wp_list_pluck( acf_get_fields( 'group_gt_shop_settings' ) ?: array(), 'name' );
foreach ( array( 'shop_products_per_page', 'shop_stockist_page', 'shop_experts_link', 'shop_google_maps_key' ) as $name ) {
	gt_assert( in_array( $name, $shop_fields, true ), "shop settings group has field {$name}" );
}

gt_test_done();
```

Run: `tests/bin/wpx eval-file tests/acf.test.php`
Expected: FAIL — "field group group_gt_brand is registered" and the others fail.

- [ ] **Step 2: Create the brand field group**

`acf-json/brand.json`:

```json
{
    "key": "group_gt_brand",
    "title": "Brand",
    "fields": [
        {
            "key": "field_gt_brand_tab_identity",
            "label": "Identity",
            "name": "",
            "type": "tab",
            "placement": "top"
        },
        {
            "key": "field_gt_brand_logo",
            "label": "Logo",
            "name": "logo",
            "type": "image",
            "instructions": "SVG or PNG on a transparent background. Shown on product cards, the brand page hero and the product page.",
            "return_format": "id",
            "library": "all",
            "preview_size": "medium",
            "wrapper": { "width": "50", "class": "", "id": "" }
        },
        {
            "key": "field_gt_brand_accent",
            "label": "Accent colour",
            "name": "accent_colour",
            "type": "color_picker",
            "instructions": "Highlight colour for this brand (e.g. Clonex yellow #FBC707).",
            "default_value": "#FBC707",
            "enable_opacity": 0,
            "return_format": "string",
            "wrapper": { "width": "50", "class": "", "id": "" }
        },
        {
            "key": "field_gt_brand_own",
            "label": "Growth Technology brand",
            "name": "own_brand",
            "type": "true_false",
            "instructions": "Tick for brands formulated by Growth Technology. The shop's default sort puts these first.",
            "default_value": 1,
            "ui": 1,
            "ui_on_text": "Ours",
            "ui_off_text": "Third party"
        },
        {
            "key": "field_gt_brand_tab_promo",
            "label": "Shop promo tile",
            "name": "",
            "type": "tab",
            "placement": "top"
        },
        {
            "key": "field_gt_brand_promo_enabled",
            "label": "Feature in the shop grid",
            "name": "promo_enabled",
            "type": "true_false",
            "instructions": "Adds an \"Explore the range\" tile among the products on the shop page and on category pages where this brand has products.",
            "default_value": 0,
            "ui": 1,
            "ui_on_text": "Shown",
            "ui_off_text": "Hidden"
        },
        {
            "key": "field_gt_brand_promo_image",
            "label": "Tile image",
            "name": "promo_image",
            "type": "image",
            "instructions": "Portrait lifestyle shot; cropped to 322 x 439 at desktop.",
            "return_format": "id",
            "library": "all",
            "preview_size": "medium",
            "conditional_logic": [ [ { "field": "field_gt_brand_promo_enabled", "operator": "==", "value": "1" } ] ],
            "wrapper": { "width": "50", "class": "", "id": "" }
        },
        {
            "key": "field_gt_brand_promo_position",
            "label": "Grid position",
            "name": "promo_position",
            "type": "number",
            "instructions": "Which cell of the grid the tile occupies, counting from 1. The Figma uses 2 for Clonex and 7 for Root Riot.",
            "default_value": 2,
            "min": 1,
            "step": 1,
            "conditional_logic": [ [ { "field": "field_gt_brand_promo_enabled", "operator": "==", "value": "1" } ] ],
            "wrapper": { "width": "50", "class": "", "id": "" }
        },
        {
            "key": "field_gt_brand_promo_tagline",
            "label": "Tagline",
            "name": "promo_tagline",
            "type": "text",
            "instructions": "Wrap the highlighted words in <em> … </em> to colour them with the accent, e.g. The original rooting gel. <em>A complete propagation system.</em>",
            "placeholder": "The original rooting gel. <em>A complete propagation system.</em>",
            "conditional_logic": [ [ { "field": "field_gt_brand_promo_enabled", "operator": "==", "value": "1" } ] ]
        }
    ],
    "location": [ [ { "param": "taxonomy", "operator": "==", "value": "product_brand" } ] ],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "instruction_placement": "label",
    "hide_on_screen": "",
    "active": true,
    "description": "Brand identity and shop promo tile. Brand landing page content is added in a later phase.",
    "show_in_rest": 0
}
```

- [ ] **Step 3: Create the category field group**

`acf-json/product-category.json`:

```json
{
    "key": "group_gt_product_category",
    "title": "Category page hero",
    "fields": [
        {
            "key": "field_gt_cat_hero_image",
            "label": "Hero image",
            "name": "hero_image",
            "type": "image",
            "instructions": "Landscape, cropped to 1340 x 325 at desktop. A dark gradient is laid over the left side for the text.",
            "return_format": "id",
            "library": "all",
            "preview_size": "medium"
        },
        {
            "key": "field_gt_cat_hero_heading",
            "label": "Hero heading",
            "name": "hero_heading",
            "type": "text",
            "instructions": "Falls back to the category name.",
            "placeholder": "Propagation - The science of the start."
        },
        {
            "key": "field_gt_cat_hero_text",
            "label": "Hero text",
            "name": "hero_text",
            "type": "textarea",
            "instructions": "Falls back to the category description.",
            "rows": 3,
            "new_lines": ""
        }
    ],
    "location": [ [ { "param": "taxonomy", "operator": "==", "value": "product_cat" } ] ],
    "menu_order": 0,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "instruction_placement": "label",
    "hide_on_screen": "",
    "active": true,
    "description": "",
    "show_in_rest": 0
}
```

- [ ] **Step 4: Create the shop settings group on the existing Theme Settings page**

`acf-json/theme-settings-shop.json`:

```json
{
    "key": "group_gt_shop_settings",
    "title": "Shop Settings",
    "fields": [
        {
            "key": "field_gt_shop_per_page",
            "label": "Products per page",
            "name": "shop_products_per_page",
            "type": "number",
            "instructions": "How many products load before the \"Load more\" button.",
            "default_value": 12,
            "min": 3,
            "step": 1,
            "wrapper": { "width": "50", "class": "", "id": "" }
        },
        {
            "key": "field_gt_shop_stockist_page",
            "label": "Stockist page",
            "name": "shop_stockist_page",
            "type": "page_link",
            "instructions": "\"Find a local stockist\" buttons link here with the product pre-selected.",
            "post_type": [ "page" ],
            "allow_null": 1,
            "allow_archives": 0,
            "multiple": 0,
            "wrapper": { "width": "50", "class": "", "id": "" }
        },
        {
            "key": "field_gt_shop_experts_link",
            "label": "\"Ask our experts\" link",
            "name": "shop_experts_link",
            "type": "link",
            "instructions": "Where the product page's \"Ask our experts\" link goes. The product ID is appended as ?product=.",
            "return_format": "array",
            "wrapper": { "width": "50", "class": "", "id": "" }
        },
        {
            "key": "field_gt_shop_maps_key",
            "label": "Google Maps API key",
            "name": "shop_google_maps_key",
            "type": "text",
            "instructions": "Used by the stockist finder map and geocoding.",
            "wrapper": { "width": "50", "class": "", "id": "" }
        }
    ],
    "location": [ [ { "param": "options_page", "operator": "==", "value": "theme-general-settings" } ] ],
    "menu_order": 20,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "instruction_placement": "label",
    "hide_on_screen": "",
    "active": true,
    "description": "",
    "show_in_rest": 0
}
```

- [ ] **Step 5: Run the test**

Run: `tests/bin/wpx eval-file tests/acf.test.php`
Expected: `Success: 17 assertions passed`

- [ ] **Step 6: Commit**

```bash
git add gt_system/themes/growth_tech/acf-json/brand.json gt_system/themes/growth_tech/acf-json/product-category.json gt_system/themes/growth_tech/acf-json/theme-settings-shop.json tests/acf.test.php
git commit -m "Add ACF field groups for brands, category heroes and shop settings

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 3: Seed script — categories, brands, attributes, products

**Files:**
- Create: `tests/seed/seed-shop.php`
- Create: `tests/seed.test.php`

**Interfaces:**
- Produces (in the DB, idempotent): categories Propagation / Nutrients / Growing Media / Plant Care; brands Clonex, Root Riot, Ionic, Formulex, Nitrozyme, Growth Technology, SMC; attributes `pa_size`, `pa_growing-medium` (Soil, Coco, Hydro), `pa_growing-stage` (Cuttings, Seedlings, Vegetative, Flowering); 13 products with SKUs `GT-001` … `GT-013`; shop page titled "Our Products". Own brands: all except Nitrozyme and SMC. Promo tiles: Clonex (pos 2), Root Riot (pos 7). Propagation has hero fields set.

- [ ] **Step 1: Write the failing test**

`tests/seed.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

$cat = get_term_by( 'slug', 'propagation', 'product_cat' );
gt_assert( $cat instanceof WP_Term, 'Propagation category exists' );
gt_assert_equal( 7, (int) $cat->count, 'Propagation has 7 products' );
gt_assert_equal( 'Propagation - The science of the start.', get_field( 'hero_heading', $cat ), 'Propagation hero heading seeded' );

$clonex = get_term_by( 'slug', 'clonex', 'product_brand' );
gt_assert( $clonex instanceof WP_Term, 'Clonex brand exists' );
gt_assert_equal( 4, (int) $clonex->count, 'Clonex has 4 products' );
gt_assert_equal( true, (bool) get_field( 'own_brand', $clonex ), 'Clonex is an own brand' );
gt_assert_equal( true, (bool) get_field( 'promo_enabled', $clonex ), 'Clonex promo tile enabled' );
gt_assert_equal( 2, (int) get_field( 'promo_position', $clonex ), 'Clonex promo at position 2' );

$smc = get_term_by( 'slug', 'smc', 'product_brand' );
gt_assert( $smc instanceof WP_Term && 0 === (int) $smc->count, 'SMC brand exists with no products' );

gt_assert( taxonomy_exists( 'pa_size' ), 'pa_size attribute taxonomy exists' );
gt_assert( taxonomy_exists( 'pa_growing-medium' ), 'pa_growing-medium attribute taxonomy exists' );
gt_assert( taxonomy_exists( 'pa_growing-stage' ), 'pa_growing-stage attribute taxonomy exists' );

$mist = wc_get_product( wc_get_product_id_by_sku( 'GT-001' ) );
gt_assert( $mist instanceof WC_Product, 'Clonex Mist product exists' );
gt_assert_equal( array( '100ml', '300ml', '750ml' ), wc_get_product_terms( $mist->get_id(), 'pa_size', array( 'fields' => 'names' ) ), 'Clonex Mist sizes in order' );
gt_assert_equal( '', $mist->get_price(), 'Clonex Mist has no price (enquiry mode)' );

$count = new WP_Query( array( 'post_type' => 'product', 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => -1 ) );
gt_assert_equal( 13, (int) $count->found_posts, '13 products seeded' );

gt_assert_equal( 'Our Products', get_the_title( wc_get_page_id( 'shop' ) ), 'shop page is titled Our Products' );

gt_test_done();
```

Run: `tests/bin/wpx eval-file tests/seed.test.php`
Expected: FAIL — "Propagation category exists" fails.

- [ ] **Step 2: Write the seed script**

`tests/seed/seed-shop.php`:

```php
<?php
/**
 * Seed shop content for local development and tests. Idempotent: re-running
 * updates in place (products matched by SKU, terms by slug).
 *
 * Run: tests/bin/wpx eval-file tests/seed/seed-shop.php
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce is not active.' );
}

// -- Terms -------------------------------------------------------------------

function gt_seed_term( $taxonomy, $name, $slug, array $args = array() ) {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term ) {
		if ( ! empty( $args['description'] ) ) {
			wp_update_term( $term->term_id, $taxonomy, array( 'description' => $args['description'] ) );
		}
		return (int) $term->term_id;
	}
	$result = wp_insert_term( $name, $taxonomy, array_merge( array( 'slug' => $slug ), $args ) );
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( "{$taxonomy}/{$slug}: " . $result->get_error_message() );
	}
	return (int) $result['term_id'];
}

$categories = array(
	'propagation'   => 'Propagation',
	'nutrients'     => 'Nutrients',
	'growing-media' => 'Growing Media',
	'plant-care'    => 'Plant Care',
);
$cat_ids = array();
foreach ( $categories as $slug => $name ) {
	$cat_ids[ $slug ] = gt_seed_term( 'product_cat', $name, $slug, array(
		'description' => "Everything you need for {$name}.",
	) );
}
update_field( 'field_gt_cat_hero_heading', 'Propagation - The science of the start.', 'term_' . $cat_ids['propagation'] );
update_field( 'field_gt_cat_hero_text', 'Everything a cutting or seed needs to be come a plant - gels, mists, cubes and first feeds, formulated in-house since 1985.', 'term_' . $cat_ids['propagation'] );

// slug => [name, own, promo position (0 = none), tagline]
$brands = array(
	'clonex'            => array( 'Clonex', 1, 2, 'The original rooting gel. <em>A complete propagation system.</em>' ),
	'root-riot'         => array( 'Root Riot', 1, 7, 'Propagation cubes for <em>faster rooting.</em>' ),
	'ionic'             => array( 'Ionic', 1, 0, '' ),
	'formulex'          => array( 'Formulex', 1, 0, '' ),
	'nitrozyme'         => array( 'Nitrozyme', 0, 0, '' ),
	'growth-technology' => array( 'Growth Technology', 1, 0, '' ),
	'smc'               => array( 'SMC', 0, 0, '' ),
);
$brand_ids = array();
foreach ( $brands as $slug => $b ) {
	$id                 = gt_seed_term( 'product_brand', $b[0], $slug );
	$brand_ids[ $slug ] = $id;
	update_field( 'field_gt_brand_own', $b[1], 'term_' . $id );
	update_field( 'field_gt_brand_accent', 'root-riot' === $slug ? '#A9C23F' : '#FBC707', 'term_' . $id );
	update_field( 'field_gt_brand_promo_enabled', $b[2] ? 1 : 0, 'term_' . $id );
	update_field( 'field_gt_brand_promo_position', $b[2] ?: 2, 'term_' . $id );
	update_field( 'field_gt_brand_promo_tagline', $b[3], 'term_' . $id );
}

// -- Attributes --------------------------------------------------------------

function gt_seed_attribute( $name, $label, array $terms ) {
	$taxonomy = wc_attribute_taxonomy_name( $name );
	if ( ! wc_attribute_taxonomy_id_by_name( $name ) ) {
		$result = wc_create_attribute( array(
			'name'         => $label,
			'slug'         => $name,
			'type'         => 'select',
			'order_by'     => 'menu_order',
			'has_archives' => false,
		) );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( "attribute {$name}: " . $result->get_error_message() );
		}
		delete_transient( 'wc_attribute_taxonomies' );
	}
	if ( ! taxonomy_exists( $taxonomy ) ) {
		register_taxonomy( $taxonomy, 'product', array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ) );
	}
	$ids = array();
	foreach ( $terms as $index => $term_name ) {
		$slug        = sanitize_title( $term_name );
		$term        = get_term_by( 'slug', $slug, $taxonomy );
		$term_id     = $term ? (int) $term->term_id : (int) wp_insert_term( $term_name, $taxonomy, array( 'slug' => $slug ) )['term_id'];
		$ids[ $slug ] = $term_id;
		update_term_meta( $term_id, 'order', $index ); // WC sorts attribute terms by this when order_by = menu_order.
	}
	return array( $taxonomy, $ids );
}

list( $size_tax, $size_ids )     = gt_seed_attribute( 'size', 'Size', array( '50ml', '100ml', '300ml', '500ml', '750ml', '1L', '5L', '50L', '24 Tray', '50 Refill', '100 Refill' ) );
list( $medium_tax, $medium_ids ) = gt_seed_attribute( 'growing-medium', 'Growing Medium', array( 'Soil', 'Coco', 'Hydro' ) );
list( $stage_tax, $stage_ids )   = gt_seed_attribute( 'growing-stage', 'Growing Stage', array( 'Cuttings', 'Seedlings', 'Vegetative', 'Flowering' ) );

// -- Products ----------------------------------------------------------------

function gt_seed_attribute_object( $name, $taxonomy, array $term_ids, $position ) {
	$attribute = new WC_Product_Attribute();
	$attribute->set_id( wc_attribute_taxonomy_id_by_name( $name ) );
	$attribute->set_name( $taxonomy );
	$attribute->set_options( $term_ids );
	$attribute->set_position( $position );
	$attribute->set_visible( true );
	$attribute->set_variation( false );
	return $attribute;
}

// sku => [title, category, brand, sizes, mediums, stages]
$products = array(
	'GT-001' => array( 'Clonex Mist', 'propagation', 'clonex', array( '100ml', '300ml', '750ml' ), array( 'soil', 'coco', 'hydro' ), array( 'cuttings' ) ),
	'GT-002' => array( 'Root Riot', 'propagation', 'root-riot', array( '24-tray', '50-refill', '100-refill' ), array( 'soil', 'coco', 'hydro' ), array( 'cuttings', 'seedlings' ) ),
	'GT-003' => array( 'Clonex Rooting Hormone', 'propagation', 'clonex', array( '50ml' ), array( 'soil', 'coco', 'hydro' ), array( 'cuttings' ) ),
	'GT-004' => array( 'Clonex Pro Start', 'propagation', 'clonex', array( '100ml', '500ml' ), array( 'soil', 'coco', 'hydro' ), array( 'cuttings', 'seedlings' ) ),
	'GT-005' => array( 'Clonex Mist Concentrate', 'propagation', 'clonex', array( '1l', '5l' ), array( 'soil', 'coco', 'hydro' ), array( 'cuttings' ) ),
	'GT-006' => array( 'Budget Propagator', 'propagation', 'growth-technology', array(), array(), array( 'cuttings', 'seedlings' ) ),
	'GT-007' => array( 'Pipettes', 'propagation', 'growth-technology', array( '5ml' ), array(), array() ),
	'GT-008' => array( 'Ionic Hydro Grow', 'nutrients', 'ionic', array( '1l', '5l' ), array( 'hydro' ), array( 'vegetative' ) ),
	'GT-009' => array( 'Ionic Coco Grow', 'nutrients', 'ionic', array( '1l', '5l' ), array( 'coco' ), array( 'vegetative' ) ),
	'GT-010' => array( 'Ionic Soil Grow', 'nutrients', 'ionic', array( '1l', '5l' ), array( 'soil' ), array( 'vegetative' ) ),
	'GT-011' => array( 'Formulex', 'nutrients', 'formulex', array( '1l' ), array( 'soil', 'coco', 'hydro' ), array( 'seedlings' ) ),
	'GT-012' => array( 'Coco Professional Plus', 'growing-media', 'growth-technology', array( '50l' ), array( 'coco' ), array() ),
	'GT-013' => array( 'Nitrozyme', 'plant-care', 'nitrozyme', array( '100ml', '300ml' ), array( 'soil', 'coco', 'hydro' ), array( 'vegetative', 'flowering' ) ),
);

$menu_order = 0;
foreach ( $products as $sku => $p ) {
	$existing = wc_get_product_id_by_sku( $sku );
	$product  = $existing ? wc_get_product( $existing ) : new WC_Product_Simple();

	$product->set_name( $p[0] );
	$product->set_sku( $sku );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_short_description( "{$p[0]} - a short description that appears under the product title." );
	$product->set_description( "{$p[0]} full description. Replace with real copy." );
	$product->set_category_ids( array( $cat_ids[ $p[1] ] ) );
	$product->set_menu_order( $menu_order++ );

	$attributes = array();
	if ( $p[3] ) {
		$attributes[] = gt_seed_attribute_object( 'size', $size_tax, array_map( function ( $s ) use ( $size_ids ) { return $size_ids[ $s ]; }, $p[3] ), 0 );
	}
	if ( $p[4] ) {
		$attributes[] = gt_seed_attribute_object( 'growing-medium', $medium_tax, array_map( function ( $s ) use ( $medium_ids ) { return $medium_ids[ $s ]; }, $p[4] ), 1 );
	}
	if ( $p[5] ) {
		$attributes[] = gt_seed_attribute_object( 'growing-stage', $stage_tax, array_map( function ( $s ) use ( $stage_ids ) { return $stage_ids[ $s ]; }, $p[5] ), 2 );
	}
	$product->set_attributes( $attributes );

	$id = $product->save();
	wp_set_object_terms( $id, array( $brand_ids[ $p[2] ] ), 'product_brand' );
	WP_CLI::log( ( $existing ? 'updated ' : 'created ' ) . $sku . ' ' . $p[0] );
}

// Term counts can lag after direct term assignment.
foreach ( array( 'product_cat', 'product_brand', $size_tax, $medium_tax, $stage_tax ) as $taxonomy ) {
	$tt_ids = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'tt_ids' ) );
	if ( ! is_wp_error( $tt_ids ) && $tt_ids ) {
		wp_update_term_count_now( $tt_ids, $taxonomy );
	}
}

// -- Pages -------------------------------------------------------------------

$shop_id = wc_get_page_id( 'shop' );
if ( $shop_id > 0 ) {
	wp_update_post( array( 'ID' => $shop_id, 'post_title' => 'Our Products' ) );
}

wc_delete_product_transients();
WP_CLI::success( 'Shop content seeded.' );
```

- [ ] **Step 3: Run the seed, then the test**

Run:
```bash
tests/bin/wpx eval-file tests/seed/seed-shop.php
tests/bin/wpx eval-file tests/seed.test.php
```
Expected: `Success: Shop content seeded.` then `Success: 17 assertions passed`.

- [ ] **Step 4: Run the seed a second time to prove idempotence**

Run: `tests/bin/wpx eval-file tests/seed/seed-shop.php && tests/bin/wpx eval-file tests/seed.test.php`
Expected: every product line says `updated`, test still passes (still 13 products).

- [ ] **Step 5: Commit**

```bash
git add tests/seed/seed-shop.php tests/seed.test.php
git commit -m "Add idempotent shop seed script for local development

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 4: WooCommerce foundation — theme support, enquiry mode, sorting, image sizes

**Files:**
- Create: `inc/woocommerce.php`
- Modify: `functions.php` (bottom include block, and `gt_theme_support()` image sizes)
- Create: `tests/foundation.test.php`

**Interfaces:**
- Produces: constant `GT_SHOP_ENQUIRY_MODE` (bool, true); `gt_shop_per_page(): int`; `gt_shop_sort_options(): array` (`brands|title|title-desc|date` => label); `gt_shop_own_brand_term_ids(): int[]`; image sizes `gt-product-card` (640×740 soft), `gt-product-card-sm` (320×370 soft), `gt-promo` (644×878 crop), `gt-promo-sm` (322×439 crop), `gt-category-hero` (2680×650 crop), `gt-category-hero-sm` (1340×325 crop).
- WooCommerce sees: theme support, no WC front-end CSS, no price / add-to-cart / result-count / ordering / breadcrumb / sidebar / wrapper hooks, sort options replaced, default sort `brands`, `loop_shop_per_page` = `gt_shop_per_page()`.

- [ ] **Step 1: Write the failing test**

`tests/foundation.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

gt_assert( current_theme_supports( 'woocommerce' ), 'theme declares woocommerce support' );
gt_assert( defined( 'GT_SHOP_ENQUIRY_MODE' ) && GT_SHOP_ENQUIRY_MODE === true, 'GT_SHOP_ENQUIRY_MODE is true' );

gt_assert_equal( array(), apply_filters( 'woocommerce_enqueue_styles', array( 'x' => 1 ) ), 'WooCommerce front-end styles are dequeued' );

gt_assert_equal( false, has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price' ), 'single price hook removed in enquiry mode' );
gt_assert_equal( false, has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart' ), 'single add-to-cart hook removed in enquiry mode' );
gt_assert_equal( false, has_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' ), 'loop add-to-cart hook removed' );
gt_assert_equal( false, has_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price' ), 'loop price hook removed' );
gt_assert_equal( false, has_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb' ), 'WC breadcrumb hook removed' );
gt_assert_equal( false, has_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar' ), 'WC sidebar hook removed' );
gt_assert_equal( false, has_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering' ), 'WC ordering dropdown hook removed' );
gt_assert_equal( false, has_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count' ), 'WC result count hook removed' );

gt_assert_equal(
	array( 'brands' => 'Our brands first', 'title' => 'A - Z', 'title-desc' => 'Z - A', 'date' => 'Newest' ),
	apply_filters( 'woocommerce_catalog_orderby', array( 'price' => 'x' ) ),
	'sort options replaced (no price sorts in enquiry mode)'
);
gt_assert_equal( 'brands', apply_filters( 'woocommerce_default_catalog_orderby', 'menu_order' ), 'default sort is Our brands first' );
gt_assert_equal( 12, gt_shop_per_page(), 'products per page defaults to 12' );
gt_assert_equal( 12, apply_filters( 'loop_shop_per_page', 16 ), 'loop_shop_per_page uses the setting' );

$own = gt_shop_own_brand_term_ids();
$clonex = get_term_by( 'slug', 'clonex', 'product_brand' );
$nitro  = get_term_by( 'slug', 'nitrozyme', 'product_brand' );
gt_assert( in_array( (int) $clonex->term_id, $own, true ), 'Clonex is in the own-brand id list' );
gt_assert( ! in_array( (int) $nitro->term_id, $own, true ), 'Nitrozyme is not in the own-brand id list' );

foreach ( array( 'gt-product-card', 'gt-product-card-sm', 'gt-promo', 'gt-promo-sm', 'gt-category-hero', 'gt-category-hero-sm' ) as $size ) {
	gt_assert( has_image_size( $size ), "image size {$size} registered" );
}

gt_test_done();
```

Run: `tests/bin/wpx eval-file tests/foundation.test.php`
Expected: FAIL — theme support and everything after it fails.

- [ ] **Step 2: Add image sizes to `gt_theme_support()` in `functions.php`**

Inside `gt_theme_support()` after the feature-band sizes add:

```php
	// Shop product card: 322 x 370 tile in the design, product cut-outs sit
	// inside it uncropped, so these are soft (max-bounds) sizes.
	add_image_size( 'gt-product-card', 640, 740, false );
	add_image_size( 'gt-product-card-sm', 320, 370, false );

	// Brand promo tile in the shop grid: 322 x 439, cropped, plus 2x.
	add_image_size( 'gt-promo', 644, 878, true );
	add_image_size( 'gt-promo-sm', 322, 439, true );

	// Category hero: 1340 x 325, cropped, plus 2x.
	add_image_size( 'gt-category-hero', 2680, 650, true );
	add_image_size( 'gt-category-hero-sm', 1340, 325, true );
```

- [ ] **Step 3: Create `inc/woocommerce.php`**

```php
<?php
/**
 * WooCommerce foundation: theme support, Phase One "enquiry mode", sort
 * options, per-page count. Templates live in /woocommerce, listing logic in
 * inc/woocommerce-shop.php.
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/**
 * Phase One sells nothing online. While true, price and add-to-cart output is
 * removed and price sorting hidden; flip to false in Phase Two to restore
 * WooCommerce's defaults in the same template slots.
 */
if ( ! defined( 'GT_SHOP_ENQUIRY_MODE' ) ) {
	define( 'GT_SHOP_ENQUIRY_MODE', true );
}

function gt_wc_theme_support() {
	add_theme_support( 'woocommerce' );
	// Gallery zoom / lightbox / slider are replaced by the theme's own gallery
	// on the product page, so none of WC's are declared here.
}
add_action( 'after_setup_theme', 'gt_wc_theme_support' );

// The theme styles every WooCommerce element itself.
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Strip the WooCommerce output the templates replace, and — in enquiry mode —
 * everything to do with buying.
 */
function gt_wc_remove_default_hooks() {
	// Layout the theme owns.
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
	remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );

	if ( GT_SHOP_ENQUIRY_MODE ) {
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	}
}
add_action( 'init', 'gt_wc_remove_default_hooks' );

/** No cart, no cart scripts. Phase Two: flip the constant and these return. */
function gt_wc_dequeue_cart_scripts() {
	if ( ! GT_SHOP_ENQUIRY_MODE ) {
		return;
	}
	foreach ( array( 'wc-add-to-cart', 'wc-cart-fragments', 'woocommerce', 'sourcebuster-js', 'wc-order-attribution' ) as $handle ) {
		wp_dequeue_script( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'gt_wc_dequeue_cart_scripts', 99 );

/** Products per page from Theme Settings, default 12. */
function gt_shop_per_page() {
	$value = function_exists( 'get_field' ) ? (int) get_field( 'shop_products_per_page', 'option' ) : 0;
	return $value > 0 ? $value : 12;
}
add_filter( 'loop_shop_per_page', 'gt_shop_per_page' );

/** Sort options offered in the toolbar. Price sorts return in Phase Two. */
function gt_shop_sort_options() {
	$options = array(
		'brands'     => __( 'Our brands first', 'gt' ),
		'title'      => __( 'A - Z', 'gt' ),
		'title-desc' => __( 'Z - A', 'gt' ),
		'date'       => __( 'Newest', 'gt' ),
	);
	if ( ! GT_SHOP_ENQUIRY_MODE ) {
		$options['price']      = __( 'Price: low to high', 'gt' );
		$options['price-desc'] = __( 'Price: high to low', 'gt' );
	}
	return $options;
}
add_filter( 'woocommerce_catalog_orderby', 'gt_shop_sort_options' );
add_filter( 'woocommerce_default_catalog_orderby', function () {
	return 'brands';
} );

/** Term ids of brands flagged as Growth Technology's own. */
function gt_shop_own_brand_term_ids() {
	static $ids = null;
	if ( null === $ids ) {
		$terms = get_terms( array(
			'taxonomy'   => 'product_brand',
			'hide_empty' => false,
			'fields'     => 'ids',
			'meta_key'   => 'own_brand',
			'meta_value' => '1',
		) );
		$ids = is_wp_error( $terms ) ? array() : array_map( 'intval', $terms );
	}
	return $ids;
}

/**
 * "Our brands first" is not a WooCommerce order. Map it to menu_order/title
 * here and let gt_shop_own_brands_first_clauses() prepend the brand rank.
 */
function gt_shop_catalog_ordering_args( $args, $orderby ) {
	if ( 'brands' === $orderby ) {
		$args['orderby']  = 'menu_order title';
		$args['order']    = 'ASC';
		$args['meta_key'] = '';
	}
	return $args;
}
add_filter( 'woocommerce_get_catalog_ordering_args', 'gt_shop_catalog_ordering_args', 10, 2 );

/**
 * Rank products in own brands ahead of the rest. Applies only to queries the
 * shop marks with gt_shop_query and only when the selection sorts by brands.
 */
function gt_shop_own_brands_first_clauses( $clauses, $query ) {
	if ( ! $query->get( 'gt_shop_query' ) || 'brands' !== $query->get( 'gt_shop_orderby' ) ) {
		return $clauses;
	}
	$ids = gt_shop_own_brand_term_ids();
	if ( ! $ids ) {
		return $clauses;
	}
	global $wpdb;
	$in   = implode( ',', $ids );
	$rank = "(CASE WHEN {$wpdb->posts}.ID IN (
		SELECT tr.object_id FROM {$wpdb->term_relationships} tr
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		WHERE tt.taxonomy = 'product_brand' AND tt.term_id IN ({$in})
	) THEN 0 ELSE 1 END)";

	$clauses['orderby'] = $rank . ' ASC' . ( $clauses['orderby'] ? ', ' . $clauses['orderby'] : '' );
	return $clauses;
}
add_filter( 'posts_clauses', 'gt_shop_own_brands_first_clauses', 20, 2 );
```

- [ ] **Step 4: Include the file from `functions.php`**

Change the include block at the bottom of `functions.php` to:

```php
include_once __DIR__ . '/inc/header.php';
include_once __DIR__ . '/inc/post-types.php';
include_once __DIR__ . '/inc/register-blocks.php';
include_once __DIR__ . '/inc/taxonomies.php';
include_once __DIR__ . '/inc/woocommerce.php';
```

- [ ] **Step 5: Run the test**

Run: `tests/bin/wpx eval-file tests/foundation.test.php`
Expected: `Success: 23 assertions passed`

- [ ] **Step 6: Commit**

```bash
git add gt_system/themes/growth_tech/inc/woocommerce.php gt_system/themes/growth_tech/functions.php tests/foundation.test.php
git commit -m "Add WooCommerce foundation with enquiry mode and brand-first sorting

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 5: Product helpers — primary category, sizes, brand

**Files:**
- Create: `inc/woocommerce-helpers.php`
- Modify: `functions.php` (include)
- Create: `tests/helpers.test.php`

**Interfaces:**
- Produces: `gt_product_primary_category( WC_Product $product ): ?WP_Term` (Yoast primary if set, else first top-level category by name, never "Uncategorized"); `gt_product_sizes( WC_Product $product ): string[]` (pa_size names in attribute order); `gt_product_brand( WC_Product $product ): ?WP_Term`; `gt_brand_accent( WP_Term $brand ): string` (hex, default `#FBC707`); `gt_term_field( string $name, WP_Term $term, $default = '' )` (ACF-safe getter).

- [ ] **Step 1: Write the failing test**

`tests/helpers.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

$mist = wc_get_product( wc_get_product_id_by_sku( 'GT-001' ) );
$cat  = gt_product_primary_category( $mist );
gt_assert( $cat instanceof WP_Term && 'propagation' === $cat->slug, 'primary category of Clonex Mist is Propagation' );
gt_assert_equal( array( '100ml', '300ml', '750ml' ), gt_product_sizes( $mist ), 'sizes of Clonex Mist' );
$brand = gt_product_brand( $mist );
gt_assert( $brand instanceof WP_Term && 'clonex' === $brand->slug, 'brand of Clonex Mist is Clonex' );
gt_assert_equal( '#FBC707', gt_brand_accent( $brand ), 'Clonex accent colour' );

$propagator = wc_get_product( wc_get_product_id_by_sku( 'GT-006' ) );
gt_assert_equal( array(), gt_product_sizes( $propagator ), 'product without sizes returns empty array' );

gt_assert_equal( 'fallback', gt_term_field( 'nonexistent_field', $brand, 'fallback' ), 'gt_term_field falls back for missing fields' );
gt_assert_equal( true, (bool) gt_term_field( 'own_brand', $brand, false ), 'gt_term_field reads ACF term fields' );

gt_test_done();
```

Run: `tests/bin/wpx eval-file tests/helpers.test.php`
Expected: FAIL — `gt_product_primary_category` undefined.

- [ ] **Step 2: Create `inc/woocommerce-helpers.php`**

```php
<?php
/**
 * Small product/term readers shared by the shop templates.
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/** ACF term field with a fallback, safe when ACF is off. */
function gt_term_field( $name, WP_Term $term, $default = '' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}
	$value = get_field( $name, $term );
	return ( null === $value || '' === $value || false === $value ) ? $default : $value;
}

/**
 * The category shown on the card. Yoast's primary term wins when set;
 * otherwise the first top-level category alphabetically. Never the
 * WooCommerce default "Uncategorized".
 */
function gt_product_primary_category( WC_Product $product ) {
	$default_id = (int) get_option( 'default_product_cat' );
	$primary_id = (int) get_post_meta( $product->get_id(), '_yoast_wpseo_primary_product_cat', true );

	if ( $primary_id && $primary_id !== $default_id ) {
		$term = get_term( $primary_id, 'product_cat' );
		if ( $term instanceof WP_Term ) {
			return $term;
		}
	}

	$terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'orderby' => 'name', 'order' => 'ASC' ) );
	if ( is_wp_error( $terms ) || ! $terms ) {
		return null;
	}
	$terms = array_filter( $terms, function ( $t ) use ( $default_id ) {
		return (int) $t->term_id !== $default_id;
	} );
	if ( ! $terms ) {
		return null;
	}
	foreach ( $terms as $term ) {
		if ( 0 === (int) $term->parent ) {
			return $term;
		}
	}
	return reset( $terms );
}

/** Size names in the attribute's own order, e.g. ['100ml', '300ml', '750ml']. */
function gt_product_sizes( WC_Product $product ) {
	if ( ! taxonomy_exists( 'pa_size' ) ) {
		return array();
	}
	$names = wc_get_product_terms( $product->get_id(), 'pa_size', array( 'fields' => 'names' ) );
	return is_wp_error( $names ) ? array() : array_values( $names );
}

/** First brand term on the product, or null. */
function gt_product_brand( WC_Product $product ) {
	if ( ! taxonomy_exists( 'product_brand' ) ) {
		return null;
	}
	$terms = wp_get_post_terms( $product->get_id(), 'product_brand' );
	return ( is_wp_error( $terms ) || ! $terms ) ? null : $terms[0];
}

/** Brand accent hex, defaulting to the Clonex yellow used across the designs. */
function gt_brand_accent( WP_Term $brand ) {
	$colour = (string) gt_term_field( 'accent_colour', $brand, '' );
	return preg_match( '/^#[0-9a-fA-F]{6}$/', $colour ) ? strtoupper( $colour ) : '#FBC707';
}
```

- [ ] **Step 3: Include it from `functions.php`** (after `inc/woocommerce.php`):

```php
include_once __DIR__ . '/inc/woocommerce-helpers.php';
```

- [ ] **Step 4: Run the test**

Run: `tests/bin/wpx eval-file tests/helpers.test.php`
Expected: `Success: 7 assertions passed`

- [ ] **Step 5: Commit**

```bash
git add gt_system/themes/growth_tech/inc/woocommerce-helpers.php gt_system/themes/growth_tech/functions.php tests/helpers.test.php
git commit -m "Add product helper readers for category, sizes and brand

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 6: Product card and brand promo tile templates + styles

**Files:**
- Create: `woocommerce/content-product.php`
- Create: `woocommerce/content-brand-promo.php`
- Create: `assets/sass/components/shop/_s.card.scss`
- Create: `assets/sass/components/shop/_s.promo.scss`
- Modify: `assets/sass/main.scss`
- Create: `tests/card.test.php`

**Interfaces:**
- Consumes: `gt_product_primary_category`, `gt_product_sizes`, `gt_brand_accent`, `gt_term_field` (Task 5).
- Produces: `content-product.php` renders one `<li class="product-card">` for `global $product`; `content-brand-promo.php` renders one `<li class="brand-promo-cell">` for `$brand` (WP_Term) passed via `wc_get_template( 'content-brand-promo.php', array( 'brand' => $term ) )`.

- [ ] **Step 1: Write the failing test**

`tests/card.test.php`:

```php
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
```

Run: `tests/bin/wpx eval-file tests/card.test.php`
Expected: FAIL — WooCommerce's default `content-product.php` renders, so `class="product-card"` is missing.

- [ ] **Step 2: Create `woocommerce/content-product.php`**

```php
<?php
/**
 * Product card — Figma 384:1742 ("Frame 64" in All Products).
 *
 * Image on a light tile, then title and a "Category • sizes" line. The whole
 * card is one link. Used by the shop grid, the AJAX endpoint and every other
 * product row on the site.
 *
 * @var WC_Product $product
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
	return;
}

$category = gt_product_primary_category( $product );
$sizes    = gt_product_sizes( $product );
$image_id = (int) $product->get_image_id();
$img_attr = array(
	'class' => 'product-card__img',
	'sizes' => '(max-width: 767px) calc(100vw - 50px), (max-width: 1023px) calc(50vw - 38px), (max-width: 1265px) calc((100vw - 365px) / 2), 322px',
	'loading' => 'lazy',
);
?>
<li <?php wc_product_class( 'product-card', $product ); ?>>
	<a class="product-card__link" href="<?php echo esc_url( $product->get_permalink() ); ?>">
		<div class="product-card__media">
			<?php
			if ( $image_id ) {
				echo wp_get_attachment_image( $image_id, 'gt-product-card', false, $img_attr );
			} else {
				echo wc_placeholder_img( 'gt-product-card', $img_attr );
			}
			?>
		</div>
		<div class="product-card__body">
			<h2 class="product-card__title"><?php echo esc_html( $product->get_name() ); ?></h2>
			<?php if ( $category || $sizes ) : ?>
				<p class="product-card__meta">
					<?php if ( $category ) : ?>
						<span class="product-card__category"><?php echo esc_html( $category->name ); ?></span>
					<?php endif; ?>
					<?php if ( $category && $sizes ) : ?>
						<span class="product-card__dot" aria-hidden="true"></span>
					<?php endif; ?>
					<?php if ( $sizes ) : ?>
						<span class="product-card__sizes"><?php echo esc_html( implode( '/', $sizes ) ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>
	</a>
</li>
```

- [ ] **Step 3: Create `woocommerce/content-brand-promo.php`**

```php
<?php
/**
 * Brand promo tile — Figma 384:1750 ("Frame 66": Explore the Clonex Range).
 *
 * A grid cell the same size as a product card: lifestyle image, dark
 * gradient, brand logo, tagline with accent-coloured <em>, underlined link.
 *
 * @var WP_Term $brand Passed by wc_get_template().
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $brand ) || ! $brand instanceof WP_Term || ! gt_term_field( 'promo_enabled', $brand, false ) ) {
	return;
}

$image_id = (int) gt_term_field( 'promo_image', $brand, 0 );
$logo_id  = (int) gt_term_field( 'logo', $brand, 0 );
$tagline  = (string) gt_term_field( 'promo_tagline', $brand, '' );
$accent   = gt_brand_accent( $brand );
$link     = get_term_link( $brand );

if ( is_wp_error( $link ) ) {
	return;
}
/* translators: %s: brand name */
$label = sprintf( __( 'Explore the %s Range', 'gt' ), $brand->name );
?>
<li class="brand-promo-cell">
	<a class="brand-promo" href="<?php echo esc_url( $link ); ?>" style="--brand-accent: <?php echo esc_attr( $accent ); ?>">
		<?php
		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, 'gt-promo', false, array(
				'class'   => 'brand-promo__img',
				'alt'     => '',
				'sizes'   => '(max-width: 767px) calc(100vw - 50px), (max-width: 1023px) calc(50vw - 38px), (max-width: 1265px) calc((100vw - 365px) / 2), 322px',
				'loading' => 'lazy',
			) );
		}
		?>
		<span class="brand-promo__scrim" aria-hidden="true"></span>
		<?php if ( $logo_id ) : ?>
			<?php echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'brand-promo__logo', 'alt' => $brand->name ) ); ?>
		<?php else : ?>
			<span class="brand-promo__logo-text"><?php echo esc_html( $brand->name ); ?></span>
		<?php endif; ?>
		<?php if ( $tagline ) : ?>
			<span class="brand-promo__tagline"><?php echo wp_kses( $tagline, array( 'em' => array(), 'br' => array() ) ); ?></span>
		<?php endif; ?>
		<span class="brand-promo__link"><?php echo esc_html( $label ); ?></span>
	</a>
</li>
```

- [ ] **Step 4: Card styles `assets/sass/components/shop/_s.card.scss`**

```scss
// ---------------------------------------------------------------------------
// Product card — Figma 384:1742
//
// 322 x 370 image tile, 15px gap, then a 5px-padded body with the title and
// the "Category • sizes" line. Widths come from the grid; only heights and
// ratios live here.
// ---------------------------------------------------------------------------

.product-card {
  list-style: none;
  margin: 0;

  &__link {
    display: flex;
    flex-direction: column;
    gap: 15px;
    height: 100%;
    color: #000;
    text-decoration: none;
  }

  &__media {
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 322 / 370;
    padding: 32px;
    background-color: rgba(35, 31, 32, 0.05);
    overflow: hidden;
  }

  &__img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    transition: transform 0.4s ease;
  }

  &__link:hover &__img,
  &__link:focus-visible &__img {
    transform: scale(1.04);
  }

  &__body {
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 5px;
  }

  &__title {
    margin: 0;
    font-family: "DM Sans", #{$primary_font};
    font-size: 18px;
    font-weight: $font-500;
    line-height: normal;
    color: #000;
  }

  &__meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin: 0;
    font-family: "DM Sans", #{$primary_font};
    font-size: 12px;
    font-weight: $font-400;
    line-height: normal;
    color: rgba(0, 0, 0, 0.5);
  }

  &__dot {
    display: block;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background-color: #d9d9d9;
  }
}

@media (prefers-reduced-motion: reduce) {
  .product-card__img {
    transition: none;
  }
}
```

- [ ] **Step 5: Promo styles `assets/sass/components/shop/_s.promo.scss`**

```scss
// ---------------------------------------------------------------------------
// Brand promo tile — Figma 384:1750
//
// Fills a grid cell (stretches to the product cards' 439px). Image cover,
// bottom gradient, then logo / tagline / link stacked at the bottom with a
// 30px inset and 20px gaps.
// ---------------------------------------------------------------------------

.brand-promo-cell {
  list-style: none;
  margin: 0;
  display: flex;
}

.brand-promo {
  position: relative;
  display: flex;
  flex: 1 1 auto;
  flex-direction: column;
  justify-content: flex-end;
  gap: 20px;
  min-height: 100%;
  padding: 30px;
  overflow: hidden;
  background-color: #000;
  color: color(white);
  text-decoration: none;

  &__img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
  }

  &:hover &__img,
  &:focus-visible &__img {
    transform: scale(1.03);
  }

  &__scrim {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.85) 50.114%);
  }

  &__logo,
  &__logo-text,
  &__tagline,
  &__link {
    position: relative;
    z-index: 1;
  }

  &__logo {
    display: block;
    width: auto;
    max-width: 150px;
    height: 27px;
    object-fit: contain;
    object-position: left center;
  }

  &__logo-text {
    font-family: "DM Sans", #{$primary_font};
    font-size: 18px;
    font-weight: $font-900;
    line-height: 27px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--brand-accent, #fbc707);
  }

  &__tagline {
    display: block;
    margin: 0;
    font-family: "Cormorant Garamond", serif;
    font-size: 30px;
    font-weight: $font-700;
    line-height: 35px;
    letter-spacing: -0.6px;

    em {
      font-style: normal;
      color: var(--brand-accent, #fbc707);
    }
  }

  &__link {
    font-family: "DM Sans", #{$primary_font};
    font-size: 14px;
    font-weight: $font-700;
    line-height: normal;
    text-decoration: underline;
    text-underline-position: from-font;
  }
}

@media (prefers-reduced-motion: reduce) {
  .brand-promo__img {
    transition: none;
  }
}
```

- [ ] **Step 6: Import from `assets/sass/main.scss`**

After the `//Blocks` imports add:

```scss
//Shop
@import "components/shop/s.card";
@import "components/shop/s.promo";
```

Then compile: `npx sass assets/sass/main.scss assets/css/main.css --style=compressed --source-map`
Expected: `main.css` now contains `.product-card` (`grep -c product-card assets/css/main.css` ≥ 1).

- [ ] **Step 7: Run the test**

Run: `tests/bin/wpx eval-file tests/card.test.php`
Expected: `Success: 14 assertions passed`

- [ ] **Step 8: Commit**

```bash
git add gt_system/themes/growth_tech/woocommerce/content-product.php gt_system/themes/growth_tech/woocommerce/content-brand-promo.php gt_system/themes/growth_tech/assets/sass/components/shop/_s.card.scss gt_system/themes/growth_tech/assets/sass/components/shop/_s.promo.scss gt_system/themes/growth_tech/assets/sass/main.scss gt_system/themes/growth_tech/assets/css/main.css gt_system/themes/growth_tech/assets/css/main.css.map tests/card.test.php
git commit -m "Add product card and brand promo tile templates

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 7: Selection, query building, term counts, promos and canonical URLs

**Files:**
- Create: `inc/woocommerce-shop.php`
- Modify: `functions.php` (include)
- Create: `tests/selection.test.php`

**Interfaces:**
- Consumes: `gt_shop_per_page`, `gt_shop_own_brand_term_ids`, `gt_term_field` (Tasks 4–5).
- Produces:
  - `gt_shop_filter_groups(): array` — `key => ['label', 'taxonomy', 'collapsed']` for `categories`, `brands`, `growing-medium`, `growing-stage`.
  - `gt_shop_selection( ?array $source = null ): array` — `['categories'=>[], 'brands'=>[], 'growing-medium'=>[], 'growing-stage'=>[], 'q'=>'', 'orderby'=>'brands', 'paged'=>1]`. `null` reads `$_GET` (+ current category on `is_product_category()` outside AJAX) and caches; an array bypasses the cache (tests, AJAX).
  - `gt_shop_tax_query( array $selection ): array`
  - `gt_shop_query_args( array $selection ): array` — full `WP_Query` args incl. `gt_shop_query => true`, `gt_shop_orderby`.
  - `gt_shop_matching_ids( array $selection ): int[]`
  - `gt_shop_term_counts( string $group_key, array $selection ): array` — `slug => count`, other groups' filters applied.
  - `gt_shop_promos( array $selection ): array` — `position => WP_Term`.
  - `gt_shop_build_url( array $selection ): string` — canonical URL.
  - `gt_shop_render_loop( WP_Query $query, array $promos ): void` — echoes `<li>`s.
  - `gt_shop_count_text( int $shown, int $total ): string` — "Showing 7 of 26 products".
  - Main query hook on `woocommerce_product_query`; canonical redirect on `template_redirect`.

- [ ] **Step 1: Write the failing test**

`tests/selection.test.php`:

```php
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
```

Run: `tests/bin/wpx eval-file tests/selection.test.php`
Expected: FAIL — `gt_shop_selection` undefined.

- [ ] **Step 2: Create `inc/woocommerce-shop.php`**

```php
<?php
/**
 * Shop listing logic: the filter "selection", the queries it drives, sidebar
 * counts, brand promo placement and canonical URLs. Everything the archive
 * template and the AJAX endpoint share lives here so both render the same
 * thing for the same parameters.
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/** Sidebar groups in display order. Key = query param. */
function gt_shop_filter_groups() {
	return array(
		'categories'     => array( 'label' => __( 'Category', 'gt' ), 'taxonomy' => 'product_cat', 'collapsed' => false ),
		'brands'         => array( 'label' => __( 'Brands', 'gt' ), 'taxonomy' => 'product_brand', 'collapsed' => false ),
		'growing-medium' => array( 'label' => __( 'Growing Medium', 'gt' ), 'taxonomy' => 'pa_growing-medium', 'collapsed' => false ),
		'growing-stage'  => array( 'label' => __( 'Growing Stage', 'gt' ), 'taxonomy' => 'pa_growing-stage', 'collapsed' => true ),
	);
}

/** Comma-separated slugs → clean unique slug list. */
function gt_shop_parse_slugs( $value ) {
	if ( is_array( $value ) ) {
		$value = implode( ',', $value );
	}
	$slugs = array();
	foreach ( explode( ',', (string) $value ) as $slug ) {
		$slug = sanitize_title( trim( $slug ) );
		if ( '' !== $slug ) {
			$slugs[] = $slug;
		}
	}
	return array_values( array_unique( $slugs ) );
}

/**
 * The active filters. With no $source, reads $_GET and (on a category archive
 * rendered normally, not via AJAX) folds the current category in.
 */
function gt_shop_selection( $source = null ) {
	static $cached = null;
	$from_request = null === $source;

	if ( $from_request && null !== $cached ) {
		return $cached;
	}
	if ( $from_request ) {
		$source = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification -- read-only filters.
	}

	$selection = array();
	foreach ( array_keys( gt_shop_filter_groups() ) as $key ) {
		$selection[ $key ] = isset( $source[ $key ] ) ? gt_shop_parse_slugs( $source[ $key ] ) : array();
	}

	if ( $from_request && ! wp_doing_ajax() && is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term && ! in_array( $term->slug, $selection['categories'], true ) ) {
			array_unshift( $selection['categories'], $term->slug );
		}
	}

	$selection['q'] = isset( $source['q'] ) ? trim( sanitize_text_field( (string) $source['q'] ) ) : '';

	$orderby              = isset( $source['orderby'] ) ? sanitize_key( (string) $source['orderby'] ) : '';
	$selection['orderby'] = array_key_exists( $orderby, gt_shop_sort_options() ) ? $orderby : 'brands';

	$paged = isset( $source['paged'] ) ? (int) $source['paged'] : 0;
	if ( $from_request && $paged < 1 ) {
		$paged = (int) get_query_var( 'paged' );
	}
	$selection['paged'] = max( 1, $paged );

	if ( $from_request ) {
		$cached = $selection;
	}
	return $selection;
}

/** Taxonomy clauses for the active groups. */
function gt_shop_tax_query( array $selection ) {
	$tax_query = array();
	foreach ( gt_shop_filter_groups() as $key => $group ) {
		if ( ! empty( $selection[ $key ] ) && taxonomy_exists( $group['taxonomy'] ) ) {
			$tax_query[] = array(
				'taxonomy' => $group['taxonomy'],
				'field'    => 'slug',
				'terms'    => $selection[ $key ],
				'operator' => 'IN',
			);
		}
	}
	return $tax_query;
}

/** Complete WP_Query args for a selection — used by AJAX, counts and tests. */
function gt_shop_query_args( array $selection ) {
	$ordering = WC()->query->get_catalog_ordering_args( $selection['orderby'] );
	$args     = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'paged'               => $selection['paged'],
		'posts_per_page'      => gt_shop_per_page(),
		'orderby'             => $ordering['orderby'],
		'order'               => $ordering['order'],
		'tax_query'           => array_merge( WC()->query->get_tax_query( array(), false ), gt_shop_tax_query( $selection ) ),
		'ignore_sticky_posts' => true,
		'gt_shop_query'       => true,
		'gt_shop_orderby'     => $selection['orderby'],
	);
	if ( ! empty( $ordering['meta_key'] ) ) {
		$args['meta_key'] = $ordering['meta_key']; // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	if ( '' !== $selection['q'] ) {
		$args['s'] = $selection['q'];
	}
	return $args;
}

/** Every product id matching the selection (ignores paging). Cached per request. */
function gt_shop_matching_ids( array $selection ) {
	static $cache = array();
	$key = md5( wp_json_encode( array_diff_key( $selection, array( 'paged' => 1, 'orderby' => 1 ) ) ) );
	if ( ! isset( $cache[ $key ] ) ) {
		$args = array_merge( gt_shop_query_args( $selection ), array(
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'paged'          => 1,
			'no_found_rows'  => true,
			'orderby'        => 'ID',
			'gt_shop_orderby' => 'title',
		) );
		$cache[ $key ] = array_map( 'intval', ( new WP_Query( $args ) )->posts );
	}
	return $cache[ $key ];
}

/**
 * Counts for one sidebar group: how many products each term would show,
 * with every *other* group's filters applied but not this group's own.
 */
function gt_shop_term_counts( $group_key, array $selection ) {
	$groups = gt_shop_filter_groups();
	if ( ! isset( $groups[ $group_key ] ) || ! taxonomy_exists( $groups[ $group_key ]['taxonomy'] ) ) {
		return array();
	}
	$others               = $selection;
	$others[ $group_key ] = array();
	$ids                  = gt_shop_matching_ids( $others );
	$taxonomy             = $groups[ $group_key ]['taxonomy'];
	$default_cat          = (int) get_option( 'default_product_cat' );

	$terms = get_terms( array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'parent'     => 'product_cat' === $taxonomy ? 0 : '',
		'orderby'    => 'name',
		'order'      => 'ASC',
	) );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	// Attribute terms keep the order set in Products > Attributes (term meta
	// "order", missing = 0). usort is stable on PHP 8 so ties stay A-Z.
	if ( 0 === strpos( $taxonomy, 'pa_' ) ) {
		usort( $terms, function ( $a, $b ) {
			return (int) get_term_meta( $a->term_id, 'order', true ) <=> (int) get_term_meta( $b->term_id, 'order', true );
		} );
	}

	$counts = array();
	foreach ( $terms as $term ) {
		if ( (int) $term->term_id === $default_cat ) {
			continue;
		}
		$in_term = $ids ? get_objects_in_term( $term->term_id, $taxonomy ) : array();
		$in_term = is_wp_error( $in_term ) ? array() : array_map( 'intval', $in_term );
		$counts[ $term->slug ] = count( array_intersect( $ids, $in_term ) );
	}
	return $counts;
}

/**
 * Brand promo tiles for this page: position => term. Only on page 1 and
 * only while no brand / attribute / search filter narrows the grid; on a
 * category page only brands that have products in that category.
 */
function gt_shop_promos( array $selection ) {
	if ( $selection['paged'] > 1 || $selection['brands'] || $selection['growing-medium'] || $selection['growing-stage'] || '' !== $selection['q'] ) {
		return array();
	}
	$brands = get_terms( array(
		'taxonomy'   => 'product_brand',
		'hide_empty' => false,
		'meta_key'   => 'promo_enabled', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value' => '1',              // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	if ( is_wp_error( $brands ) ) {
		return array();
	}

	$promos = array();
	foreach ( $brands as $brand ) {
		if ( $selection['categories'] ) {
			$has = get_posts( array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'tax_query'      => array(
					array( 'taxonomy' => 'product_brand', 'field' => 'term_id', 'terms' => $brand->term_id ),
					array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $selection['categories'] ),
				),
			) );
			if ( ! $has ) {
				continue;
			}
		}
		$position = max( 1, (int) gt_term_field( 'promo_position', $brand, 2 ) );
		while ( isset( $promos[ $position ] ) ) {
			$position++;
		}
		$promos[ $position ] = $brand;
	}
	ksort( $promos );
	return $promos;
}

/** Canonical URL for a selection: category permalink when exactly one category, else the shop page. */
function gt_shop_build_url( array $selection ) {
	$params = array();
	$base   = wc_get_page_permalink( 'shop' );

	if ( 1 === count( $selection['categories'] ) ) {
		$link = get_term_link( $selection['categories'][0], 'product_cat' );
		if ( ! is_wp_error( $link ) ) {
			$base = $link;
		} else {
			$params['categories'] = $selection['categories'][0];
		}
	} elseif ( $selection['categories'] ) {
		$params['categories'] = implode( ',', $selection['categories'] );
	}

	foreach ( array( 'brands', 'growing-medium', 'growing-stage' ) as $key ) {
		if ( $selection[ $key ] ) {
			$params[ $key ] = implode( ',', $selection[ $key ] );
		}
	}
	if ( '' !== $selection['q'] ) {
		$params['q'] = $selection['q'];
	}
	if ( 'brands' !== $selection['orderby'] ) {
		$params['orderby'] = $selection['orderby'];
	}
	if ( $selection['paged'] > 1 ) {
		$base = trailingslashit( $base ) . 'page/' . $selection['paged'] . '/';
	}
	return $params ? add_query_arg( $params, $base ) : $base;
}

/** "Showing 7 of 26 products". */
function gt_shop_count_text( $shown, $total ) {
	/* translators: 1: products shown so far, 2: total matching products */
	return sprintf( __( 'Showing %1$d of %2$d products', 'gt' ), (int) $shown, (int) $total );
}

/** Echo the grid items for a query, splicing promo tiles in at their positions. */
function gt_shop_render_loop( WP_Query $query, array $promos = array() ) {
	$cell = 1;
	while ( $query->have_posts() ) {
		$query->the_post();
		while ( isset( $promos[ $cell ] ) ) {
			wc_get_template( 'content-brand-promo.php', array( 'brand' => $promos[ $cell ] ) );
			unset( $promos[ $cell ] );
			$cell++;
		}
		wc_get_template_part( 'content', 'product' );
		$cell++;
	}
	foreach ( $promos as $brand ) {
		wc_get_template( 'content-brand-promo.php', array( 'brand' => $brand ) );
	}
	wp_reset_postdata();
}

/** Apply the selection to WooCommerce's main product query. */
function gt_shop_main_query( $query ) {
	$selection = gt_shop_selection();
	$query->set( 'gt_shop_query', true );
	$query->set( 'gt_shop_orderby', $selection['orderby'] );
	$query->set( 'tax_query', array_merge( (array) $query->get( 'tax_query' ), gt_shop_tax_query( $selection ) ) );
	if ( '' !== $selection['q'] ) {
		$query->set( 's', $selection['q'] );
	}
}
add_action( 'woocommerce_product_query', 'gt_shop_main_query' );

/**
 * One URL per selection: /shop/?categories=x becomes the category permalink,
 * and a category page carrying other categories goes back to the shop.
 */
function gt_shop_canonical_redirect() {
	if ( wp_doing_ajax() || is_admin() || ! ( is_shop() || is_product_category() ) ) {
		return;
	}
	if ( ! isset( $_GET['categories'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$selection = gt_shop_selection();
	$target    = gt_shop_build_url( $selection );
	$current   = home_url( add_query_arg( array() ) );
	if ( untrailingslashit( $target ) !== untrailingslashit( $current ) ) {
		wp_safe_redirect( $target, 302 );
		exit;
	}
}
add_action( 'template_redirect', 'gt_shop_canonical_redirect' );
```

- [ ] **Step 3: Include it from `functions.php`** (after the helpers include):

```php
include_once __DIR__ . '/inc/woocommerce-shop.php';
```

- [ ] **Step 4: Run the test**

Run: `tests/bin/wpx eval-file tests/selection.test.php`
Expected: `Success: 35 assertions passed`. If the "own brands first" assertion fails, check `gt_shop_own_brands_first_clauses` receives `gt_shop_orderby` = `brands` (it is set in `gt_shop_query_args`).

- [ ] **Step 5: Run the whole suite to make sure nothing regressed**

Run: `tests/run.sh`
Expected: every file ends with `Success:`.

- [ ] **Step 6: Commit**

```bash
git add gt_system/themes/growth_tech/inc/woocommerce-shop.php gt_system/themes/growth_tech/functions.php tests/selection.test.php
git commit -m "Add shop selection, query, counts, promo placement and canonical URLs

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 8: Archive template — breadcrumb, category hero, sidebar, toolbar, grid, load more (server-rendered)

**Files:**
- Create: `woocommerce/archive-product.php`
- Create: `template-parts/shop/breadcrumb.php`
- Create: `template-parts/shop/category-hero.php`
- Create: `template-parts/shop/filters.php`
- Create: `template-parts/shop/toolbar.php`
- Create: `template-parts/shop/load-more.php`
- Create: `assets/images/icons/tick.svg`
- Create: `assets/sass/components/shop/_s.layout.scss`
- Create: `assets/sass/components/shop/_s.breadcrumb.scss`
- Create: `assets/sass/components/shop/_s.category-hero.scss`
- Create: `assets/sass/components/shop/_s.filters.scss`
- Create: `assets/sass/components/shop/_s.toolbar.scss`
- Modify: `assets/sass/main.scss`
- Create: `tests/archive.test.php`

**Interfaces:**
- Consumes everything from Task 7, `gt_arrow_svg()`, `gt_icon_svg()`.
- Produces: `template-parts/shop/filters.php` and `toolbar.php` accept `$args` = `['selection' => array, 'total' => int, 'shown' => int]` via `get_template_part( 'template-parts/shop/filters', null, $args )`; both are re-rendered by the AJAX endpoint in Task 9. Data hooks for JS: `form[data-shop-form]`, `[data-shop-sidebar]`, `[data-shop-count]`, `ul[data-shop-grid]`, `[data-shop-more]` (with `data-next-page`, `data-total-pages`), `button[data-shop-filters-toggle]`, `button[data-shop-filters-close]`, `button[data-shop-group-toggle]`.

- [ ] **Step 1: Write the failing test**

`tests/archive.test.php`:

```php
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

$html = gt_fetch( '/shop/?q=mist' );
gt_assert_contains( 'Showing 2 of 2 products', $html, 'GET search works' );
gt_assert_contains( 'value="mist"', $html, 'search box keeps its value' );

$html = gt_fetch( '/shop/?orderby=title-desc' );
$first = strpos( $html, 'product-card__title">' );
gt_assert_contains( 'Root Riot', substr( $html, $first, 80 ), 'GET sort applies' );

gt_test_done();
```

Run: `tests/bin/wpx eval-file tests/archive.test.php`
Expected: FAIL — the WooCommerce default archive renders.

- [ ] **Step 2: Add the tick icon `assets/images/icons/tick.svg`** (from Figma 384:1297)

```svg
<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.36407 4.06272L9.99688 4.5344L10.1326 4.63694L10.035 4.77561L9.80547 5.10178L6.68145 9.51779L6.4129 9.89768L6.2918 10.0696L6.14532 9.91916L4.25957 7.98069L3.98321 7.69748L3.86407 7.57541L3.98321 7.45334L4.53594 6.88498L4.66094 6.75608L4.78594 6.88498L6.15313 8.29026L8.8875 4.42697L9.117 4.10276L9.22051 3.95529L9.36407 4.06272Z" fill="white" stroke="white" stroke-width="0.35"/></svg>
```

- [ ] **Step 3: Breadcrumb part `template-parts/shop/breadcrumb.php`**

```php
<?php
/**
 * Shop breadcrumb — Figma 384:1277. "Our Products / Propagation".
 *
 * @param array $args ['items' => [ ['label' => string, 'url' => string|null], ... ]]
 *                    Optional; defaults to the shop page + current category chain.
 */

$items = isset( $args['items'] ) ? $args['items'] : null;

if ( null === $items ) {
	$shop_id = wc_get_page_id( 'shop' );
	$items   = array( array( 'label' => $shop_id > 0 ? get_the_title( $shop_id ) : __( 'Our Products', 'gt' ), 'url' => wc_get_page_permalink( 'shop' ) ) );

	if ( is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$chain = array_reverse( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );
			foreach ( $chain as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'product_cat' );
				if ( $ancestor instanceof WP_Term ) {
					$items[] = array( 'label' => $ancestor->name, 'url' => get_term_link( $ancestor ) );
				}
			}
			$items[] = array( 'label' => $term->name, 'url' => null );
		}
	}
}

if ( ! $items ) {
	return;
}
$last = count( $items ) - 1;
?>
<nav class="shop-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'gt' ); ?>">
	<ol class="shop-crumbs__list">
		<?php foreach ( $items as $i => $item ) : ?>
			<li class="shop-crumbs__item">
				<?php if ( $i < $last && ! empty( $item['url'] ) ) : ?>
					<a class="shop-crumbs__link" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
				<?php else : ?>
					<span aria-current="page" class="shop-crumbs__current"><?php echo esc_html( $item['label'] ); ?></span>
				<?php endif; ?>
				<?php if ( $i < $last ) : ?>
					<span class="shop-crumbs__sep" aria-hidden="true">/</span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
```

- [ ] **Step 4: Category hero part `template-parts/shop/category-hero.php`**

```php
<?php
/**
 * Category hero — Figma 384:1281. Only on product category archives.
 */

if ( ! is_product_category() ) {
	return;
}
$term = get_queried_object();
if ( ! $term instanceof WP_Term ) {
	return;
}

$heading  = (string) gt_term_field( 'hero_heading', $term, $term->name );
$text     = (string) gt_term_field( 'hero_text', $term, $term->description );
$image_id = (int) gt_term_field( 'hero_image', $term, 0 );
?>
<section class="shop-hero" aria-labelledby="shop-hero-title">
	<?php
	if ( $image_id ) {
		echo wp_get_attachment_image( $image_id, 'gt-category-hero', false, array(
			'class' => 'shop-hero__img',
			'alt'   => '',
			'sizes' => '(max-width: 1439px) calc(100vw - 100px), 1340px',
		) );
	}
	?>
	<span class="shop-hero__scrim" aria-hidden="true"></span>
	<h1 id="shop-hero-title" class="shop-hero__title"><?php echo esc_html( $heading ); ?></h1>
	<?php if ( $text ) : ?>
		<p class="shop-hero__text"><?php echo wp_kses_post( $text ); ?></p>
	<?php endif; ?>
</section>
```

- [ ] **Step 5: Filters sidebar part `template-parts/shop/filters.php`**

```php
<?php
/**
 * Filter sidebar — Figma 384:1612 ("Frame 30").
 *
 * A GET form: search, then one collapsible group of checkboxes per filter
 * taxonomy with live counts. Submits to the shop page; JS upgrades it to
 * AJAX. Re-rendered by the AJAX endpoint after every change.
 *
 * @param array $args ['selection' => array]
 */

$selection = isset( $args['selection'] ) ? $args['selection'] : gt_shop_selection();
$groups    = gt_shop_filter_groups();
$form_id   = 'shop-filters-' . wp_unique_id();
?>
<form class="shop-filters" method="get" action="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" data-shop-form id="<?php echo esc_attr( $form_id ); ?>">
	<button type="button" class="shop-filters__close" data-shop-filters-close aria-label="<?php esc_attr_e( 'Close filters', 'gt' ); ?>">
		<?php gt_icon_svg( 'close' ); ?>
	</button>

	<div class="shop-filters__search">
		<label class="screen-reader-text" for="<?php echo esc_attr( $form_id ); ?>-q"><?php esc_html_e( 'Search by product name', 'gt' ); ?></label>
		<input class="shop-filters__search-input" type="search" name="q" id="<?php echo esc_attr( $form_id ); ?>-q"
			value="<?php echo esc_attr( $selection['q'] ); ?>"
			placeholder="<?php esc_attr_e( 'Search by product name', 'gt' ); ?>" autocomplete="off" />
		<button type="submit" class="shop-filters__search-btn" aria-label="<?php esc_attr_e( 'Search', 'gt' ); ?>">
			<svg viewBox="0 0 18 18" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12.9362 7.31375C12.9362 5.82165 12.3437 4.39067 11.2889 3.33559C10.2341 2.28052 8.80348 1.68779 7.31179 1.68779C5.82009 1.68779 4.38949 2.28052 3.3347 3.33559C2.27991 4.39067 1.68734 5.82165 1.68734 7.31375C1.68734 8.80585 2.27991 10.2368 3.3347 11.2919C4.38949 12.347 5.82009 12.9397 7.31179 12.9397C8.80348 12.9397 10.2341 12.347 11.2889 11.2919C12.3437 10.2368 12.9362 8.80585 12.9362 7.31375ZM11.85 13.0487C10.6056 14.0368 9.02724 14.6275 7.31179 14.6275C3.27273 14.6275 0 11.3539 0 7.31375C0 3.27361 3.27273 0 7.31179 0C11.3508 0 14.6236 3.27361 14.6236 7.31375C14.6236 9.02967 14.033 10.6085 13.0452 11.8532L17.7522 16.5614C18.0826 16.8919 18.0826 17.4264 17.7522 17.7534C17.4217 18.0804 16.8874 18.0839 16.5605 17.7534L11.85 13.0487Z"/></svg>
		</button>
	</div>

	<p class="shop-filters__title"><?php esc_html_e( 'Filters', 'gt' ); ?></p>

	<?php foreach ( $groups as $key => $group ) : ?>
		<?php
		if ( ! taxonomy_exists( $group['taxonomy'] ) ) {
			continue;
		}
		$counts = gt_shop_term_counts( $key, $selection );
		if ( ! $counts ) {
			continue;
		}
		$list_id  = $form_id . '-' . $key;
		$selected = $selection[ $key ];
		$collapsed = ! empty( $group['collapsed'] ) && ! $selected;
		?>
		<fieldset class="shop-filters__group<?php echo $collapsed ? ' is-collapsed' : ''; ?>" data-shop-group="<?php echo esc_attr( $key ); ?>">
			<legend class="screen-reader-text"><?php echo esc_html( $group['label'] ); ?></legend>
			<button type="button" class="shop-filters__group-toggle" data-shop-group-toggle
				aria-expanded="<?php echo $collapsed ? 'false' : 'true'; ?>" aria-controls="<?php echo esc_attr( $list_id ); ?>">
				<span><?php echo esc_html( $group['label'] ); ?></span>
				<svg class="shop-filters__chevron" viewBox="0 0 10 6" width="10" height="6" aria-hidden="true" focusable="false"><path fill="currentColor" d="M5.00211 0L5.4783 0.48L10 5.03788L9.04551 6L8.56932 5.52L5 1.92212L1.43068 5.52L0.954488 6L0 5.03788L0.47619 4.55788L4.5217 0.48L4.99789 0H5.00211Z"/></svg>
			</button>
			<ul class="shop-filters__list" id="<?php echo esc_attr( $list_id ); ?>">
				<?php foreach ( $counts as $slug => $count ) : ?>
					<?php
					$term = get_term_by( 'slug', $slug, $group['taxonomy'] );
					if ( ! $term ) {
						continue;
					}
					$checked  = in_array( $slug, $selected, true );
					$empty    = 0 === $count && ! $checked;
					$input_id = $list_id . '-' . $slug;
					?>
					<li class="shop-filters__item<?php echo $checked ? ' is-checked' : ''; ?><?php echo $empty ? ' is-empty' : ''; ?>">
						<label class="shop-filters__option" for="<?php echo esc_attr( $input_id ); ?>">
							<input class="shop-filters__checkbox" type="checkbox" id="<?php echo esc_attr( $input_id ); ?>"
								name="<?php echo esc_attr( $key ); ?>[]" value="<?php echo esc_attr( $slug ); ?>"<?php checked( $checked ); ?><?php disabled( $empty ); ?> />
							<span><?php echo esc_html( $term->name ); ?></span>
						</label>
						<span class="shop-filters__count"><?php echo esc_html( $count ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</fieldset>
	<?php endforeach; ?>

	<?php if ( 'brands' !== $selection['orderby'] ) : ?>
		<input type="hidden" name="orderby" value="<?php echo esc_attr( $selection['orderby'] ); ?>" />
	<?php endif; ?>
	<noscript><button type="submit" class="btn-flat btn-flat--dark shop-filters__apply"><span><?php esc_html_e( 'Apply filters', 'gt' ); ?></span></button></noscript>
</form>
```

Note: checkbox groups post as `brands[]=clonex&brands[]=ionic`; `gt_shop_parse_slugs()` accepts arrays, and the JS in Task 10 normalises to comma lists for the canonical URL.

- [ ] **Step 6: Toolbar part `template-parts/shop/toolbar.php`**

```php
<?php
/**
 * Result count + sort — Figma 384:1733 ("Frame 86").
 *
 * @param array $args ['selection' => array, 'shown' => int, 'total' => int]
 */

$selection = isset( $args['selection'] ) ? $args['selection'] : gt_shop_selection();
$shown     = isset( $args['shown'] ) ? (int) $args['shown'] : 0;
$total     = isset( $args['total'] ) ? (int) $args['total'] : 0;
$select_id = 'shop-sort-' . wp_unique_id();
?>
<div class="shop-toolbar">
	<p class="shop-toolbar__count" data-shop-count aria-live="polite"><?php echo esc_html( gt_shop_count_text( $shown, $total ) ); ?></p>
	<form class="shop-toolbar__sort" method="get" action="" data-shop-sort-form>
		<label class="shop-toolbar__sort-label" for="<?php echo esc_attr( $select_id ); ?>"><?php esc_html_e( 'Sort by', 'gt' ); ?></label>
		<span class="shop-toolbar__select-wrap">
			<select class="shop-toolbar__select" name="orderby" id="<?php echo esc_attr( $select_id ); ?>" data-shop-sort>
				<?php foreach ( gt_shop_sort_options() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $selection['orderby'], $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</span>
		<?php foreach ( array( 'brands', 'growing-medium', 'growing-stage', 'categories' ) as $key ) : ?>
			<?php if ( $selection[ $key ] && ! ( 'categories' === $key && is_product_category() ) ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( implode( ',', $selection[ $key ] ) ); ?>" />
			<?php endif; ?>
		<?php endforeach; ?>
		<?php if ( '' !== $selection['q'] ) : ?>
			<input type="hidden" name="q" value="<?php echo esc_attr( $selection['q'] ); ?>" />
		<?php endif; ?>
		<noscript><button type="submit" class="shop-toolbar__go"><?php esc_html_e( 'Go', 'gt' ); ?></button></noscript>
	</form>
</div>
```

- [ ] **Step 7: Load more part `template-parts/shop/load-more.php`**

```php
<?php
/**
 * "Load more" — a link to the next page that JS turns into an append.
 *
 * @param array $args ['selection' => array, 'total_pages' => int]
 */

$selection   = isset( $args['selection'] ) ? $args['selection'] : gt_shop_selection();
$total_pages = isset( $args['total_pages'] ) ? (int) $args['total_pages'] : 1;

if ( $selection['paged'] >= $total_pages ) {
	return;
}
$next = $selection;
$next['paged']++;
?>
<div class="shop-more" data-shop-more data-next-page="<?php echo esc_attr( $next['paged'] ); ?>" data-total-pages="<?php echo esc_attr( $total_pages ); ?>">
	<a class="btn-flat btn-flat--dark" href="<?php echo esc_url( gt_shop_build_url( $next ) ); ?>" data-shop-more-link>
		<span><?php esc_html_e( 'Load more products', 'gt' ); ?></span>
		<?php gt_arrow_svg(); ?>
	</a>
</div>
```

- [ ] **Step 8: The archive template `woocommerce/archive-product.php`**

```php
<?php
/**
 * Shop and category archive — Figma 384:1608 (All Products) and 384:1267
 * (Product Range Template).
 *
 * Breadcrumb, optional category hero, filter sidebar, toolbar, grid with
 * brand promo tiles, load more. The grid, sidebar and toolbar are also
 * produced by the AJAX endpoint from the same parts.
 */

defined( 'ABSPATH' ) || exit;

get_header();

global $wp_query;

$selection   = gt_shop_selection();
$total       = (int) $wp_query->found_posts;
$per_page    = gt_shop_per_page();
$total_pages = max( 1, (int) $wp_query->max_num_pages );
$shown       = min( $total, $selection['paged'] * $per_page );
$promos      = gt_shop_promos( $selection );
$active      = count( $selection['brands'] ) + count( $selection['growing-medium'] ) + count( $selection['growing-stage'] ) + ( is_product_category() ? 0 : count( $selection['categories'] ) );
?>

<main class="page-wrapper shop<?php echo is_product_category() ? ' shop--category' : ''; ?>">
	<div class="shop__inner">

		<div class="shop__breadcrumb">
			<?php get_template_part( 'template-parts/shop/breadcrumb' ); ?>
		</div>

		<?php if ( is_product_category() ) : ?>
			<div class="shop__hero">
				<?php get_template_part( 'template-parts/shop/category-hero' ); ?>
			</div>
		<?php else : ?>
			<h1 class="screen-reader-text"><?php woocommerce_page_title(); ?></h1>
		<?php endif; ?>

		<div class="shop__body">

			<button type="button" class="shop__filters-toggle btn-flat btn-flat--dark" data-shop-filters-toggle aria-expanded="false" aria-controls="shop-sidebar">
				<span><?php esc_html_e( 'Filters', 'gt' ); ?><?php echo $active ? ' <span class="shop__filters-badge">' . esc_html( $active ) . '</span>' : ''; ?></span>
			</button>

			<aside class="shop__sidebar" id="shop-sidebar" data-shop-sidebar aria-label="<?php esc_attr_e( 'Filter products', 'gt' ); ?>">
				<?php get_template_part( 'template-parts/shop/filters', null, array( 'selection' => $selection ) ); ?>
			</aside>

			<div class="shop__main">
				<?php get_template_part( 'template-parts/shop/toolbar', null, array( 'selection' => $selection, 'shown' => $shown, 'total' => $total ) ); ?>

				<?php if ( $wp_query->have_posts() ) : ?>
					<ul class="shop-grid" data-shop-grid aria-busy="false">
						<?php gt_shop_render_loop( $wp_query, $promos ); ?>
					</ul>
					<?php get_template_part( 'template-parts/shop/load-more', null, array( 'selection' => $selection, 'total_pages' => $total_pages ) ); ?>
				<?php else : ?>
					<ul class="shop-grid" data-shop-grid aria-busy="false"></ul>
					<p class="shop__empty"><?php esc_html_e( 'No products match those filters. Try removing one.', 'gt' ); ?></p>
				<?php endif; ?>
			</div>

		</div>
	</div>

<?php
get_footer();
```

(`footer.php` closes `</main>`.)

- [ ] **Step 9: Layout styles `assets/sass/components/shop/_s.layout.scss`**

```scss
// ---------------------------------------------------------------------------
// Shop page frame — Figma 384:1608 / 384:1267
//
// 50px gutters, 1340px content, breadcrumb 30px under the header, sidebar
// 300px + 25px gap + 3 columns of 321.67px. Rows and columns both gap 25px.
// ---------------------------------------------------------------------------

.shop {
  padding: 30px 50px 115px;

  &__inner {
    max-width: 1340px;
    margin: 0 auto;
  }

  &__breadcrumb {
    margin-bottom: 14px;
  }

  &__hero {
    margin: 20px 0 50px;
  }

  &__body {
    display: flex;
    align-items: flex-start;
    gap: 25px;
  }

  &__sidebar {
    flex: 0 0 300px;
    max-width: 300px;
  }

  &__main {
    flex: 1 1 auto;
    min-width: 0;
  }

  &__filters-toggle {
    display: none;
  }

  &__filters-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    margin-left: 8px;
    padding: 0 5px;
    border-radius: 9px;
    background-color: color(white);
    color: #000;
    font-size: 11px;
  }

  &__empty {
    margin: 40px 0 0;
    font-family: "DM Sans", #{$primary_font};
    font-size: 15px;
    color: rgba(0, 0, 0, 0.5);
  }
}

.shop-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 25px;
  margin: 0;
  padding: 0;
  list-style: none;
  transition: opacity 0.2s ease;

  &[aria-busy="true"] {
    opacity: 0.45;
    pointer-events: none;
  }
}

.shop-more {
  display: flex;
  justify-content: center;
  margin-top: 50px;
}

// -- tablet: narrower sidebar, two columns --------------------------------
@media (max-width: $xl - 1px) {
  .shop {
    padding-left: 25px;
    padding-right: 25px;

    &__sidebar {
      flex-basis: 240px;
      max-width: 240px;
    }
  }

  .shop-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
  }
}

// -- small tablet: sidebar becomes an off-canvas panel ----------------------
@media (max-width: $md - 1px) {
  .shop {
    padding-bottom: 80px;

    &__body {
      flex-direction: column;
      align-items: stretch;
    }

    &__filters-toggle {
      display: inline-flex;
      align-self: flex-start;
      margin-bottom: 20px;
    }

    &__sidebar {
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      z-index: 1000;
      width: min(360px, 100%);
      max-width: none;
      overflow-y: auto;
      background-color: #f2f2f2;
      transform: translateX(-100%);
      transition: transform 0.3s ease;
      visibility: hidden;

      &.is-open {
        transform: none;
        visibility: visible;
        box-shadow: 0 0 40px rgba(0, 0, 0, 0.25);
      }
    }
  }

  body.shop-filters-open {
    overflow: hidden;
  }
}

// -- mobile: one column -----------------------------------------------------
@media (max-width: $sm - 1px) {
  .shop {
    padding-top: 20px;
    padding-bottom: 60px;

    &__hero {
      margin: 15px 0 30px;
    }
  }

  .shop-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .shop-more {
    margin-top: 30px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .shop__sidebar,
  .shop-grid {
    transition: none;
  }
}
```

- [ ] **Step 10: Breadcrumb styles `assets/sass/components/shop/_s.breadcrumb.scss`**

```scss
// Breadcrumb — Figma 384:1277. 12px, 50% black, 10px gaps, current item medium black.
.shop-crumbs {
  font-family: "DM Sans", #{$primary_font};
  font-size: 12px;
  line-height: normal;
  color: rgba(0, 0, 0, 0.5);

  &__list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  &__item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: inherit;
    line-height: inherit;
  }

  &__link {
    color: inherit;
    text-decoration: none;

    &:hover,
    &:focus-visible {
      color: #000;
      text-decoration: underline;
    }
  }

  &__current {
    font-weight: $font-500;
    color: #000;
  }
}
```

- [ ] **Step 11: Hero styles `assets/sass/components/shop/_s.category-hero.scss`**

```scss
// ---------------------------------------------------------------------------
// Category hero — Figma 384:1281
//
// 1340 x 325 image with a left-to-right black gradient; copy bottom-left with
// a 40px inset, 150px of headroom and a 600px measure.
// ---------------------------------------------------------------------------

.shop-hero {
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  gap: 20px;
  min-height: 325px;
  padding: 150px 40px 40px;
  overflow: hidden;
  background-color: #000;
  color: color(white);

  &__img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  &__scrim {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, #000 0%, rgba(0, 0, 0, 0) 87.127%);
  }

  &__title,
  &__text {
    position: relative;
    max-width: 600px;
    margin: 0;
  }

  &__title {
    font-family: "Cormorant Garamond", serif;
    font-size: 60px;
    font-weight: $font-700;
    line-height: 65px;
    letter-spacing: -1.2px;
  }

  &__text {
    font-family: "DM Sans", #{$primary_font};
    font-size: 15px;
    font-weight: $font-400;
    line-height: 25px;
  }
}

@media (max-width: $md - 1px) {
  .shop-hero {
    min-height: 280px;
    padding: 110px 30px 30px;

    &__title {
      font-size: 44px;
      line-height: 48px;
      letter-spacing: -0.9px;
    }
  }
}

@media (max-width: $sm - 1px) {
  .shop-hero {
    min-height: 240px;
    padding: 90px 25px 25px;
    gap: 12px;

    &__scrim {
      background: linear-gradient(180deg, rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0.85) 100%);
    }

    &__title {
      font-size: 34px;
      line-height: 38px;
      letter-spacing: -0.7px;
    }

    &__text {
      font-size: 14px;
      line-height: 22px;
    }
  }
}
```

- [ ] **Step 12: Filter styles `assets/sass/components/shop/_s.filters.scss`**

```scss
// ---------------------------------------------------------------------------
// Filter sidebar — Figma 384:1612
//
// 25px padding on a 5% black wash; blocks stack with 25px gaps. Search and
// "Filters" rows carry bottom rules; each group is an 18px black heading, a
// 10x6 chevron, then 14px options with 14px square checkboxes and 10px counts.
// ---------------------------------------------------------------------------

.shop-filters {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 25px;
  padding: 25px;
  background-color: rgba(0, 0, 0, 0.05);
  font-family: "DM Sans", #{$primary_font};

  &__close {
    display: none;
    position: absolute;
    top: 14px;
    right: 14px;
    width: 32px;
    height: 32px;
    padding: 0;
    cursor: pointer;

    svg {
      display: block;
      width: 14px;
      height: 14px;
      margin: 0 auto;
    }
  }

  &__search {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding-bottom: 15px;
    border-bottom: 1px solid #000;
  }

  &__search-input {
    flex: 1 1 auto;
    min-width: 0;
    padding: 0;
    font-family: inherit;
    font-size: 14px;
    line-height: normal;
    color: #000;

    &::placeholder {
      color: rgba(0, 0, 0, 0.25);
      opacity: 1;
    }

    &::-webkit-search-cancel-button {
      -webkit-appearance: none;
    }
  }

  &__search-btn {
    flex: 0 0 auto;
    width: 18px;
    height: 18px;
    padding: 0;
    cursor: pointer;
    color: #000;

    svg {
      display: block;
      width: 18px;
      height: 18px;
    }
  }

  &__title {
    margin: 0;
    padding-bottom: 15px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.25);
    font-size: 18px;
    font-weight: $font-900;
    line-height: normal;
    color: #000;
  }

  &__group {
    display: flex;
    flex-direction: column;
    gap: 15px;
    min-width: 0;
    margin: 0;
    padding: 0;
    border: 0;

    &.is-collapsed {
      gap: 0;
    }
  }

  // Written at this level so `&` is .shop-filters, not .shop-filters__group.
  &__group.is-collapsed &__list {
    display: none;
  }

  &__group.is-collapsed &__chevron {
    transform: rotate(180deg);
  }

  &__group-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0;
    cursor: pointer;
    text-align: left;
    font-family: inherit;
    font-size: 18px;
    font-weight: $font-900;
    line-height: normal;
    color: #000;

    &:focus-visible {
      outline: 2px solid #000;
      outline-offset: 3px;
    }
  }

  &__chevron {
    flex: 0 0 auto;
    width: 10px;
    height: 6px;
    color: rgba(0, 0, 0, 0.5);
    transition: transform 0.25s ease;
  }

  &__list {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  &__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin: 0;
    font-size: 14px;
    line-height: normal;
  }

  &__option {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
    cursor: pointer;
    font-family: inherit;
    font-size: 14px;
    font-weight: $font-400;
    line-height: normal;
    color: rgba(0, 0, 0, 0.5);
    transition: color 0.2s ease;

    &:hover {
      color: #000;
    }
  }

  &__checkbox {
    flex: 0 0 auto;
    width: 14px;
    height: 14px;
    margin: 0;
    border: 1px solid rgba(0, 0, 0, 0.25);
    background-color: color(white);
    cursor: pointer;
    transition: background-color 0.2s ease, border-color 0.2s ease;

    &:checked {
      border-color: #000;
      background: #000 url("../images/icons/tick.svg") center / 14px 14px no-repeat;
    }

    &:focus-visible {
      outline: 2px solid #000;
      outline-offset: 2px;
    }

    &:disabled {
      cursor: default;
    }
  }

  &__count {
    flex: 0 0 auto;
    font-size: 10px;
    font-weight: $font-400;
    line-height: normal;
    color: rgba(0, 0, 0, 0.5);
  }

  &__item.is-checked &__option,
  &__item.is-checked &__count {
    font-weight: $font-700;
    color: #000;
  }

  &__item.is-empty &__option,
  &__item.is-empty &__count {
    color: rgba(0, 0, 0, 0.15);
    cursor: default;
  }

  &__item.is-empty &__checkbox {
    border-color: rgba(0, 0, 0, 0.15);
  }

  &__apply {
    align-self: flex-start;
  }
}

@media (max-width: $md - 1px) {
  .shop-filters {
    min-height: 100%;
    padding-top: 60px;

    &__close {
      display: block;
    }
  }
}

@media (prefers-reduced-motion: reduce) {
  .shop-filters__chevron,
  .shop-filters__checkbox,
  .shop-filters__option {
    transition: none;
  }
}
```

- [ ] **Step 13: Toolbar styles `assets/sass/components/shop/_s.toolbar.scss`**

```scss
// ---------------------------------------------------------------------------
// Toolbar — Figma 384:1733. 12px count left; "Sort by" + underlined select
// with a 7x4 chevron on the right. 25px above the grid.
// ---------------------------------------------------------------------------

.shop-toolbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 25px;
  font-family: "DM Sans", #{$primary_font};
  font-size: 12px;
  line-height: normal;
  color: rgba(0, 0, 0, 0.5);

  &__count {
    margin: 0;
    font-family: inherit;
    font-size: inherit;
    line-height: inherit;
    color: inherit;
  }

  &__sort {
    display: flex;
    align-items: flex-start;
    gap: 10px;
  }

  &__sort-label {
    font-family: inherit;
    font-size: inherit;
    line-height: inherit;
    color: inherit;
  }

  &__select-wrap {
    position: relative;
    display: inline-block;
    border-bottom: 1px solid #000;
  }

  &__select {
    padding: 0 12px 5px 0;
    font-family: inherit;
    font-size: 12px;
    line-height: normal;
    color: #000;
    cursor: pointer;
    background: transparent url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='7' height='4' viewBox='0 0 7 4'%3E%3Cpath fill='%23000' d='M3.5 4 0 .64.67 0 3.5 2.72 6.33 0 7 .64Z'/%3E%3C/svg%3E") no-repeat right 6px / 7px 4px;

    &:focus-visible {
      outline: 2px solid #000;
      outline-offset: 3px;
    }
  }

  &__go {
    margin-left: 6px;
    text-decoration: underline;
    cursor: pointer;
  }
}

@media (max-width: $sm - 1px) {
  .shop-toolbar {
    flex-wrap: wrap;
    margin-bottom: 20px;
  }
}
```

- [ ] **Step 14: Import the new partials in `assets/sass/main.scss`** — the `//Shop` block becomes:

```scss
//Shop
@import "components/shop/s.layout";
@import "components/shop/s.breadcrumb";
@import "components/shop/s.category-hero";
@import "components/shop/s.filters";
@import "components/shop/s.toolbar";
@import "components/shop/s.card";
@import "components/shop/s.promo";
```

Compile: `npx sass assets/sass/main.scss assets/css/main.css --style=compressed --source-map`

- [ ] **Step 15: Run the test**

Run: `tests/bin/wpx eval-file tests/archive.test.php`
Expected: `Success: 30 assertions passed`.

Common failures: "count text on page 2" — confirm `/shop/page/2/` isn't 404 (permalinks flushed in Task 1); "GET filters work" — confirm `gt_shop_main_query` is hooked and `gt_shop_selection()` sees `brands[]`/comma forms.

- [ ] **Step 16: Look at it**

```bash
"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless=new --disable-gpu --ignore-certificate-errors --hide-scrollbars --window-size=1440,2000 --screenshot=/tmp/shop-1440.png https://growth-tech.local/shop/ 2>/dev/null
"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless=new --disable-gpu --ignore-certificate-errors --hide-scrollbars --window-size=1440,2400 --screenshot=/tmp/cat-1440.png https://growth-tech.local/product-category/propagation/ 2>/dev/null
```
Open both PNGs (Read tool) and compare against the Figma screenshots in the spec: sidebar 300px wide with 25px inner padding, three equal columns, promo tiles at cells 2 and 7 stretched to the card height, toolbar 12px, hero copy bottom-left with 40px inset. Fix any SCSS drift before committing.

- [ ] **Step 17: Commit**

```bash
git add gt_system/themes/growth_tech/woocommerce/archive-product.php gt_system/themes/growth_tech/template-parts/shop gt_system/themes/growth_tech/assets/images/icons/tick.svg gt_system/themes/growth_tech/assets/sass/components/shop gt_system/themes/growth_tech/assets/sass/main.scss gt_system/themes/growth_tech/assets/css/main.css gt_system/themes/growth_tech/assets/css/main.css.map tests/archive.test.php
git commit -m "Add shop and category archive template with filters, toolbar and grid

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 9: AJAX endpoint

**Files:**
- Create: `inc/woocommerce-ajax.php`
- Modify: `functions.php` (include)
- Create: `tests/ajax.test.php`

**Interfaces:**
- Consumes Task 7 functions and Task 8 parts.
- Produces: `GET /wp-admin/admin-ajax.php?action=gt_shop_filter&<selection params>` → `wp_send_json_success()` with `{grid: string (<li>s), sidebar: string, count: string, more: string, url: string, page: int, total_pages: int, total: int, selection: object}`. `append=1` returns only the next page's grid items (no promos) for load-more.

- [ ] **Step 1: Write the failing test**

`tests/ajax.test.php`:

```php
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
```

Run: `tests/bin/wpx eval-file tests/ajax.test.php`
Expected: FAIL — response is `0` (unknown action), `success` empty.

- [ ] **Step 2: Create `inc/woocommerce-ajax.php`**

```php
<?php
/**
 * AJAX endpoint for the shop filters. Same selection → same functions →
 * same HTML as a normal page load; the JS just swaps the regions.
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

function gt_shop_ajax_filter() {
	$selection = gt_shop_selection( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification -- public, read-only.
	$append    = ! empty( $_GET['append'] ); // phpcs:ignore WordPress.Security.NonceVerification

	$query       = new WP_Query( gt_shop_query_args( $selection ) );
	$total       = (int) $query->found_posts;
	$total_pages = max( 1, (int) $query->max_num_pages );
	$shown       = min( $total, $selection['paged'] * gt_shop_per_page() );
	$promos      = $append ? array() : gt_shop_promos( $selection );

	ob_start();
	gt_shop_render_loop( $query, $promos );
	$grid = ob_get_clean();

	ob_start();
	get_template_part( 'template-parts/shop/filters', null, array( 'selection' => $selection ) );
	$sidebar = ob_get_clean();

	ob_start();
	get_template_part( 'template-parts/shop/load-more', null, array( 'selection' => $selection, 'total_pages' => $total_pages ) );
	$more = ob_get_clean();

	wp_send_json_success( array(
		'grid'        => $grid,
		'sidebar'     => $sidebar,
		'count'       => gt_shop_count_text( $shown, $total ),
		'more'        => trim( $more ),
		'url'         => gt_shop_build_url( $selection ),
		'page'        => $selection['paged'],
		'total_pages' => $total_pages,
		'total'       => $total,
		'selection'   => $selection,
	) );
}
add_action( 'wp_ajax_gt_shop_filter', 'gt_shop_ajax_filter' );
add_action( 'wp_ajax_nopriv_gt_shop_filter', 'gt_shop_ajax_filter' );
```

- [ ] **Step 3: Include from `functions.php`** (after `inc/woocommerce-shop.php`):

```php
include_once __DIR__ . '/inc/woocommerce-ajax.php';
```

- [ ] **Step 4: Run the test**

Run: `tests/bin/wpx eval-file tests/ajax.test.php`
Expected: `Success: 18 assertions passed`

- [ ] **Step 5: Commit**

```bash
git add gt_system/themes/growth_tech/inc/woocommerce-ajax.php gt_system/themes/growth_tech/functions.php tests/ajax.test.php
git commit -m "Add AJAX endpoint for shop filtering and load more

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 10: Front-end JS — live filtering, URL sync, load more, group toggles, off-canvas

**Files:**
- Create: `assets/js/shop-filters.js`
- Modify: `inc/woocommerce.php` (enqueue)
- Create: `tests/js-enqueue.test.php`

**Interfaces:**
- Consumes the data hooks from Task 8 and the endpoint from Task 9.
- Produces: script handle `gt-shop-filters`, localised as `gtShop = { ajaxUrl, categoryLocked: bool }`, enqueued only on `is_shop() || is_product_category()`.

- [ ] **Step 1: Write the failing test**

`tests/js-enqueue.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

$html = gt_fetch( '/shop/' );
gt_assert_contains( 'assets/js/shop-filters.js', $html, 'shop-filters.js enqueued on the shop' );
gt_assert_contains( 'var gtShop = ', $html, 'gtShop settings localised' );
gt_assert_contains( '"ajaxUrl":"', $html, 'ajaxUrl present' );

$html = gt_fetch( '/product-category/propagation/' );
gt_assert_contains( 'assets/js/shop-filters.js', $html, 'shop-filters.js enqueued on a category' );

$html = gt_fetch( '/' );
gt_assert_not_contains( 'assets/js/shop-filters.js', $html, 'not enqueued elsewhere' );

gt_assert( file_exists( get_template_directory() . '/assets/js/shop-filters.js' ), 'script file exists' );
$js = file_get_contents( get_template_directory() . '/assets/js/shop-filters.js' );
foreach ( array( 'data-shop-form', 'data-shop-grid', 'data-shop-sidebar', 'data-shop-count', 'data-shop-more', 'data-shop-sort', 'data-shop-filters-toggle', 'data-shop-group-toggle', 'pushState', 'popstate', 'aria-busy' ) as $needle ) {
	gt_assert_contains( $needle, $js, "script handles {$needle}" );
}

gt_test_done();
```

Run: `tests/bin/wpx eval-file tests/js-enqueue.test.php`
Expected: FAIL — script not enqueued.

- [ ] **Step 2: Enqueue in `inc/woocommerce.php`** (append):

```php
/** Filter/sort/load-more behaviour, only where the grid is. */
function gt_shop_enqueue_scripts() {
	if ( ! ( is_shop() || is_product_category() ) ) {
		return;
	}
	wp_enqueue_script(
		'gt-shop-filters',
		get_template_directory_uri() . '/assets/js/shop-filters.js',
		array(),
		gt_asset_version( '/assets/js/shop-filters.js' ),
		true
	);
	wp_localize_script( 'gt-shop-filters', 'gtShop', array(
		'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
		'categoryLocked' => is_product_category(),
	) );
}
add_action( 'wp_enqueue_scripts', 'gt_shop_enqueue_scripts' );
```

- [ ] **Step 3: Write `assets/js/shop-filters.js`**

```js
/**
 * Shop filters — progressive enhancement over the GET form.
 *
 * Any change to a checkbox, the search box or the sort select fetches the
 * grid, sidebar, count and load-more from admin-ajax and swaps them in,
 * then pushes the canonical URL. Load more appends the next page. Back and
 * forward re-fetch from history state. Without JS everything still works
 * as plain GET requests.
 */
(function () {
	'use strict';

	var settings = window.gtShop || {};
	var page = document.querySelector('.shop');
	if (!page || !settings.ajaxUrl || !window.fetch) {
		return;
	}

	var sidebar = page.querySelector('[data-shop-sidebar]');
	var main = page.querySelector('.shop__main');
	var toggle = page.querySelector('[data-shop-filters-toggle]');
	var collapsed = {};
	var controller = null;
	var searchTimer = null;

	// -- helpers ---------------------------------------------------------------

	function form() { return page.querySelector('[data-shop-form]'); }
	function grid() { return page.querySelector('[data-shop-grid]'); }
	function sortSelect() { return page.querySelector('[data-shop-sort]'); }
	function moreWrap() { return page.querySelector('[data-shop-more]'); }

	function html(string) {
		var tpl = document.createElement('template');
		tpl.innerHTML = string.trim();
		return tpl.content;
	}

	/** Read the current selection from the form + sort select as flat params. */
	function readParams() {
		var params = {};
		var f = form();
		if (f) {
			var groups = {};
			f.querySelectorAll('input[type="checkbox"]:checked').forEach(function (box) {
				var key = box.name.replace('[]', '');
				groups[key] = groups[key] || [];
				groups[key].push(box.value);
			});
			Object.keys(groups).forEach(function (key) { params[key] = groups[key].join(','); });
			var q = f.querySelector('input[name="q"]');
			if (q && q.value.trim()) { params.q = q.value.trim(); }
		}
		var sort = sortSelect();
		if (sort && sort.value && sort.value !== 'brands') { params.orderby = sort.value; }
		return params;
	}

	function query(params) {
		var parts = ['action=gt_shop_filter'];
		Object.keys(params).forEach(function (key) {
			parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(params[key]));
		});
		return settings.ajaxUrl + (settings.ajaxUrl.indexOf('?') === -1 ? '?' : '&') + parts.join('&');
	}

	function setBusy(busy) {
		var g = grid();
		if (g) { g.setAttribute('aria-busy', busy ? 'true' : 'false'); }
		var m = moreWrap();
		if (m) { m.classList.toggle('is-loading', busy); }
	}

	function rememberCollapsed() {
		collapsed = {};
		page.querySelectorAll('[data-shop-group]').forEach(function (group) {
			collapsed[group.getAttribute('data-shop-group')] = group.classList.contains('is-collapsed');
		});
	}

	function restoreCollapsed() {
		page.querySelectorAll('[data-shop-group]').forEach(function (group) {
			var key = group.getAttribute('data-shop-group');
			if (key in collapsed) { setGroup(group, collapsed[key]); }
		});
	}

	function setGroup(group, isCollapsed) {
		group.classList.toggle('is-collapsed', isCollapsed);
		var button = group.querySelector('[data-shop-group-toggle]');
		if (button) { button.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true'); }
	}

	// -- fetch + swap ----------------------------------------------------------

	function load(params, options) {
		options = options || {};
		if (controller) { controller.abort(); }
		controller = new AbortController();
		setBusy(true);

		var focusValue = options.focusValue || null;

		return fetch(query(params), { signal: controller.signal, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res || !res.success) { throw new Error('bad response'); }
				var d = res.data;
				var g = grid();

				if (options.append && g) {
					g.appendChild(html(d.grid));
				} else if (g) {
					g.innerHTML = d.grid;
				}

				if (!options.append && sidebar) {
					rememberCollapsed();
					sidebar.innerHTML = d.sidebar;
					restoreCollapsed();
					if (focusValue) {
						var box = sidebar.querySelector('input[value="' + focusValue + '"]');
						if (box) { box.focus({ preventScroll: true }); }
					}
				}

				var count = page.querySelector('[data-shop-count]');
				if (count) { count.textContent = d.count; }

				var oldMore = moreWrap();
				if (oldMore) { oldMore.remove(); }
				if (d.more && main) { main.appendChild(html(d.more)); }

				var empty = main ? main.querySelector('.shop__empty') : null;
				if (empty) { empty.remove(); }
				if (d.total === 0 && main) {
					var p = document.createElement('p');
					p.className = 'shop__empty';
					p.textContent = 'No products match those filters. Try removing one.';
					main.appendChild(p);
				}

				if (options.push !== false) {
					history.pushState({ gtShop: params }, '', d.url);
				}
				if (toggle) { updateToggleBadge(d.selection); }
			})
			.catch(function (err) {
				if (err.name !== 'AbortError') {
					// Fall back to a full page load with the same parameters.
					var f = form();
					if (f) { f.submit(); }
				}
			})
			.then(function () { setBusy(false); });
	}

	function updateToggleBadge(selection) {
		var active = 0;
		['brands', 'growing-medium', 'growing-stage'].forEach(function (key) {
			active += (selection[key] || []).length;
		});
		if (!settings.categoryLocked) { active += (selection.categories || []).length; }
		var badge = toggle.querySelector('.shop__filters-badge');
		if (active && !badge) {
			badge = document.createElement('span');
			badge.className = 'shop__filters-badge';
			toggle.firstElementChild.appendChild(badge);
		}
		if (badge) {
			if (active) { badge.textContent = String(active); } else { badge.remove(); }
		}
	}

	// -- events ----------------------------------------------------------------

	page.addEventListener('change', function (event) {
		var target = event.target;
		if (target.matches('[data-shop-form] input[type="checkbox"]')) {
			load(readParams(), { focusValue: target.value });
		} else if (target.matches('[data-shop-sort]')) {
			load(readParams());
		}
	});

	page.addEventListener('input', function (event) {
		if (!event.target.matches('[data-shop-form] input[name="q"]')) { return; }
		clearTimeout(searchTimer);
		searchTimer = setTimeout(function () { load(readParams()); }, 350);
	});

	page.addEventListener('submit', function (event) {
		if (event.target.matches('[data-shop-form], [data-shop-sort-form]')) {
			event.preventDefault();
			clearTimeout(searchTimer);
			load(readParams());
		}
	});

	page.addEventListener('click', function (event) {
		var groupToggle = event.target.closest('[data-shop-group-toggle]');
		if (groupToggle) {
			var group = groupToggle.closest('[data-shop-group]');
			setGroup(group, !group.classList.contains('is-collapsed'));
			return;
		}

		var more = event.target.closest('[data-shop-more-link]');
		if (more) {
			event.preventDefault();
			var wrap = more.closest('[data-shop-more]');
			var params = readParams();
			params.paged = wrap.getAttribute('data-next-page');
			params.append = '1';
			var g = grid();
			var before = g ? g.children.length : 0;
			load(params, { append: true }).then(function () {
				var g2 = grid();
				if (g2 && g2.children[before]) {
					var link = g2.children[before].querySelector('a');
					if (link) { link.focus({ preventScroll: true }); }
				}
			});
			return;
		}

		if (event.target.closest('[data-shop-filters-toggle]')) {
			openSidebar(true);
			return;
		}
		if (event.target.closest('[data-shop-filters-close]')) {
			openSidebar(false);
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && sidebar && sidebar.classList.contains('is-open')) {
			openSidebar(false);
		}
	});

	function openSidebar(open) {
		if (!sidebar) { return; }
		sidebar.classList.toggle('is-open', open);
		document.body.classList.toggle('shop-filters-open', open);
		if (toggle) { toggle.setAttribute('aria-expanded', open ? 'true' : 'false'); }
		if (open) {
			var first = sidebar.querySelector('input, button');
			if (first) { first.focus(); }
		} else if (toggle) {
			toggle.focus();
		}
	}

	window.addEventListener('popstate', function (event) {
		if (event.state && event.state.gtShop) {
			load(event.state.gtShop, { push: false });
		} else {
			window.location.reload();
		}
	});

	// Seed history so the first back-navigation restores the initial grid.
	history.replaceState({ gtShop: readParams() }, '', window.location.href);
})();
```

Note on category pages: the category checkbox is part of the form, so `readParams()` sends `categories=propagation` and the server returns the category permalink as the canonical `url`. Unticking it sends no `categories` and the URL becomes the shop page — matching the spec.

- [ ] **Step 4: Run the test**

Run: `tests/bin/wpx eval-file tests/js-enqueue.test.php`
Expected: `Success: 17 assertions passed`

- [ ] **Step 5: Manual browser check (required — headless can't click)**

Open `https://growth-tech.local/shop/` in Chrome (accept the self-signed cert). Verify:
1. Tick **Clonex** → grid shrinks to 4 without reload; URL becomes `/shop/?brands=clonex`; category counts change (Propagation 4, others 0 greyed); focus stays on the Clonex checkbox.
2. Tick **Propagation** → URL becomes `/product-category/propagation/?brands=clonex`.
3. Untick both → back to `/shop/`. Browser Back/Forward replays each state.
4. Type "mist" in the search → after a pause, 2 results, URL `?q=mist`.
5. Sort **Z – A** → Root Riot first; URL `?orderby=title-desc`.
6. Clear filters, click **Load more products** → the 13th card appends, count reads "Showing 13 of 13 products", button disappears.
7. Click the **Growing Stage** heading → expands; tick a filter → group stays expanded after the swap.
8. Resize to 900px wide → sidebar hidden, **Filters** button shows; click → off-canvas panel slides in; Esc closes it and returns focus to the button.
9. Chrome DevTools → disable JavaScript → tick a box and press **Apply filters** → same result via GET.

Record any failures and fix before committing.

- [ ] **Step 6: Commit**

```bash
git add gt_system/themes/growth_tech/assets/js/shop-filters.js gt_system/themes/growth_tech/inc/woocommerce.php tests/js-enqueue.test.php
git commit -m "Add live shop filtering with URL sync, load more and off-canvas filters

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 11: Visual verification against Figma at all breakpoints

**Files:**
- Create: `tests/shots.sh`

**Interfaces:** none new; this task confirms the previous ones against the designs.

- [ ] **Step 1: Screenshot script `tests/shots.sh`**

```sh
#!/bin/sh
# Headless screenshots of the shop pages at the design's breakpoints.
# Usage: tests/shots.sh [outdir]   (default /tmp/gt-shots)
OUT="${1:-/tmp/gt-shots}"
mkdir -p "$OUT"
CHROME="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
shot() { # name url width height
  "$CHROME" --headless=new --disable-gpu --ignore-certificate-errors --hide-scrollbars \
    --window-size="$3,$4" --screenshot="$OUT/$1-$3.png" "$2" >/dev/null 2>&1
  echo "$OUT/$1-$3.png"
}
for w in 1440 1024 768 390; do
  shot shop "https://growth-tech.local/shop/" "$w" 2600
  shot category "https://growth-tech.local/product-category/propagation/" "$w" 3000
done
```

Run: `chmod +x tests/shots.sh && tests/shots.sh`

- [ ] **Step 2: Compare desktop shots with the Figma frames**

Open `/tmp/gt-shots/shop-1440.png` and `/tmp/gt-shots/category-1440.png` and check against Figma 384:1608 / 384:1267 (screenshots referenced in the spec):
- Breadcrumb 30px below the header line, 12px grey; current item medium black.
- Sidebar 300px, 25px padding, `#F2F2F2`; search row rule black; "Filters" rule 25% black; group headings 18px black (900); options 14px 50% black; counts 10px right-aligned; SMC / Growth Technology greyed at 15%.
- Grid 3 × 321.67px, 25px gaps; card tiles 370px tall; titles 18px medium; meta 12px with a 5px `#D9D9D9` dot.
- Promo tiles at cells 2 and 7, stretched to the card height, gradient from 50%, logo 27px tall, tagline 30/35 Cormorant with the accent `<em>`, underlined bold 14px link.
- Toolbar: "Showing 12 of 13 products" left, "Sort by  Our brands first ⌄" right with a black underline.
- Category hero: 325px tall, gradient left→right, title 60/65 Cormorant, text 15/25, 40px inset, 50px gap to the sidebar.
- Grid ends 115px above the "Join the growth club" band.

Adjust SCSS for any drift, recompile, re-shoot.

- [ ] **Step 3: Check the tablet and mobile shots**

- 1024: sidebar 240px, 2 columns, gutters 25px, hero title 44px.
- 768 and 390: no sidebar; **Filters** button above the grid; 768 → 2 columns, 390 → 1 column; hero 240px tall with the vertical gradient; toolbar wraps cleanly; promo tile still full-height; nothing overflows horizontally (`document.documentElement.scrollWidth === window.innerWidth`).

- [ ] **Step 4: Run the full suite one last time**

Run: `tests/run.sh`
Expected: every test file reports `Success`.

- [ ] **Step 5: Commit**

```bash
git add tests/shots.sh
git commit -m "Add breakpoint screenshot script for the shop pages

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## Self-review notes

- Spec §6 (shop & category page) is covered by Tasks 4–11; §4.1/4.3/4.4 data model by Tasks 2–3; §10 accessibility (real checkboxes, live region, keyboard group toggles, focus management, Esc) by Tasks 8 and 10; §11 verification by Tasks 8, 10, 11. The Phase-Two constant (§8) is in Task 4. Spec §7–9 (brand page, product page, stockists) are Plans 2 and 3.
- Deviations from the spec, all deliberate: options field names prefixed `shop_`; breadcrumb is a theme part rather than a filtered WC breadcrumb (Plan 2 extends the same part with brand + product crumbs); the AJAX response carries `more`/`selection` in addition to the spec's `grid/sidebar/count/url`.
- Names used across tasks: `gt_shop_selection`, `gt_shop_query_args`, `gt_shop_matching_ids`, `gt_shop_term_counts`, `gt_shop_promos`, `gt_shop_build_url`, `gt_shop_render_loop`, `gt_shop_count_text`, `gt_shop_per_page`, `gt_shop_sort_options`, `gt_shop_own_brand_term_ids`, `gt_shop_filter_groups`, `gt_product_primary_category`, `gt_product_sizes`, `gt_product_brand`, `gt_brand_accent`, `gt_term_field` — consistent between definitions (Tasks 4, 5, 7) and uses (Tasks 6, 8, 9).
