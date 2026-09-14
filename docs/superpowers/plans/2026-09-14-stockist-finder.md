# Stockist Finder (Plan 3 of 3) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship the "Find a Stockist" page (Figma 384:2719): a `stockist` post type managed in admin with geocode-on-save, a page template with UK/International toggle, town/store search, product filter (pre-selected from `?product=` / `?brand=`), nearest-first sort, a Google Map with custom pins, and the "Run a store?" trade band — all working without the Maps key (list, filters, preselect) and lighting up the map + geocoding once the key is added in Theme Settings.

**Architecture:** `inc/stockists.php` registers the CPT + `stockist_type` taxonomy, a geocoding helper with a transient cache and an `acf/save_post` hook, a `gt/v1/geocode` REST proxy (server-side key), and card-data helpers. `page-templates/page-stockists.php` renders the whole list server-side — every card carries `data-*` attributes (country, coords, product ids, brand slugs, searchable text) — and `assets/js/stockists.js` filters/sorts/reorders those DOM nodes and drives the map from the same attributes. No duplicated card template, and no JS is required for a usable list. The trade band reuses the product page's `.knowledge-band` component.

**Tech Stack:** WordPress classic theme, ACF Pro (JSON), WooCommerce (products + `WC()->countries` for country names), Google Maps JavaScript API + Geocoding API (key from Theme Settings → Shop Settings `shop_google_maps_key`), vanilla JS, SCSS via `npx sass`, WP-CLI tests via `tests/bin/wpx`, puppeteer-core for browser checks.

**Spec:** `docs/superpowers/specs/2026-09-11-woocommerce-shop-design.md` §4.5 (stockists data), §9 (stockist finder), §10, §11. Plans 1 and 2 are merged on `main`.

## Global Constraints

- Theme root `gt_system/themes/growth_tech` — **theme paths below are relative to it**; `tests/` and `docs/` are repo-root paths. Work from the repo root; branch from `main`.
- Local site `https://growth-tech.local` (MAMP, self-signed → `curl -k`; servers running). WP-CLI only via `tests/bin/wpx`; `tests/run.sh` runs all `tests/*.test.php` (18 suites green at start). Helpers: `gt_assert`, `gt_assert_equal`, `gt_assert_contains`, `gt_assert_not_contains`, `gt_fetch($path)`, `gt_test_done`. See `tests/README.md`.
- Compile CSS after every SCSS change (theme root): `npx sass assets/sass/main.scss assets/css/main.css --style=compressed --source-map`. **Cascade rule:** the theme's base `p, li, span, button, label, h1–h6` rules set museo-sans/1rem and `_c.forms.scss` styles `form input`/`form select` (borders, radius, backgrounds, `!important` select backgrounds) — every new text element and form control sets its own `font-family: "DM Sans", #{$primary_font}`, size, border, radius and background explicitly; `.btn-flat > span { font: inherit }` already exists globally.
- Conventions: BEM; SCSS partials `assets/sass/components/shop/_s.*.scss` imported from `assets/sass/main.scss` under the `//Shop` block; all output escaped; empty data renders nothing; `gt_asset_version()`; `btn-flat` + `gt_arrow_svg()`; `gt_icon_svg('name')` prints `assets/images/icons/name.svg` with `fill="black"` → `currentColor` (svg/path/g/rect only).
- Reused from Plans 1–2 (do not re-implement): `gt_term_field()`, `gt_shop_stockist_base_url()` (Theme Settings `shop_stockist_page`), `gt_product_stockist_url()` (`?product={id}&region=uk`), `gt_brand_stockist_url()` (`?brand={slug}`), `gt_product_brand()`, `.knowledge-band` markup/classes (`template-parts/shop/knowledge-band.php` + `_s.knowledge.scss`), `.shop-toolbar__select` styling pattern, seed images via GD (`tests/seed/seed-product-content.php` has `gt_seed_image()` — copy the helper, don't include the file).
- **No key by default:** `gt_maps_key()` returns '' until the user fills Theme Settings → Shop Settings → Google Maps API key. Without it: the map column renders a grey placeholder (with an admin-only hint), "Show on map" buttons are hidden by CSS, "Nearest first" is present but disabled with a title, the geocode proxy returns HTTP 503 `{"code":"no_key"}`, and saving a stockist sets `geocode_status` to `no_key` (or `manual` when lat/lng were typed in). With the key: map loads via the Maps JS API (`loading=async`, callback `gtStockistsMapReady`), greyscale style, custom pin, +/− controls, fit-to-bounds; search text geocodes via the proxy for nearest-first.
- Figma tokens (desktop 1440): gutters 50 / content 1340; head row 60 below the header: title Cormorant 700 60/65 −1.2 #000 max-width 743, intro DM Sans 15/25 `rgba(0,0,0,.75)` width 356 right-aligned to the bottom; padding-bottom 50 + 1px `rgba(0,0,0,.1)` rule; controls 45 below: segmented toggle (49 tall; active black/white text, inactive 1px `rgba(0,0,0,.1)` border; 13px; padding 10/15; widths 133/112 ≈ content), gap 15, search 350×49 (1px `rgba(35,31,32,.1)`, padding-left 16, 20px icon, gap 7, placeholder 13px light `rgba(35,31,32,.35)`), gap 15, product select 49 tall (1px border, padding 0 15, 13px, 10×6 chevron 25 right of the text); results row 29 below: list column 562 (cards 550 wide, a 12px scroll gutter), toolbar (count 12px `rgba(0,0,0,.5)` left; "Sort by" + 12px underlined select right), list 15 below, scroll box 773 tall with a white fade at the bottom; cards 550×~182: 1px `rgba(0,0,0,.1)`, padding 20, gap 15 — name 18 medium, gap 5, "Town, Country • Type" 12 `rgba(0,0,0,.5)` with a 5px `#D9D9D9` dot; "Show on map" black 112×44 13px white top-right; chips `#F4F4F4` 12px padding 10/15 gap 5 with "+N more"; 1px `rgba(0,0,0,.1)` rule; 13px row: phone (400) + "Get Directions" + "Check stock first" (700 underlined) gap 15. Map 758×724 at gap 20, `#F4F4F4` when empty; zoom +/− 35px white squares stacked top-left inset 20 (a 1px `rgba(0,0,0,.1)` divider between); pins 18×24.75 black. Trade band 60 below the results row: same as the product knowledge band (1340×300, 30/50 padding, gradient, Cormorant 60 white, 15/25 white/75 width 622, white btn-flat).
- Responsive (no Figma frames): <1266 gutters 25; <1024 map above the list (map 420 tall), list not scroll-boxed, controls wrap; <768 controls stack full-width, toggle full-width halves, card actions wrap, "Show on map" under the name.
- Commit after each task; keep whatever Co-Authored-By trailer the environment adds. Stage only the files each task names (the working tree holds unrelated uncommitted homepage-block files — never stage them; main.scss/main.css/.map hunks from them may ride along, by standing ruling).

---

### Task 1: Stockist post type, taxonomy, ACF fields, admin column

**Files:**
- Create: `inc/stockists.php`
- Modify: `functions.php` (include after `inc/woocommerce-ajax.php`)
- Create: `acf-json/stockist.json`
- Create: `acf-json/stockists-page.json`
- Create: `tests/stockists-cpt.test.php`

**Interfaces:**
- Produces: CPT `stockist` (public false, `show_ui`, `show_in_rest` true for admin, supports title only, menu icon `dashicons-location`, no front-end single/archive); taxonomy `stockist_type` (non-hierarchical, on `stockist`, admin column, `show_in_rest`); ACF group `group_gt_stockist` (location post_type == stockist) with fields `address_1`, `address_2`, `town`, `region`, `postcode`, `country` (select, ISO-2 choices from `WC()->countries->get_countries()` via `acf/load_field`, default `GB`), `phone`, `website` (url), `email`, `products` (relationship → product, return id), `lat`, `lng` (number), `geocode_status` (text, readonly), `geocoded_address` (text, readonly); ACF group `group_gt_stockists_page` (location page_template == `page-templates/page-stockists.php`) with `intro` (textarea), `trade_heading`, `trade_text`, `trade_image` (id), `trade_link` (array). Helpers: `gt_stockist_countries(): array` (code => name, from WooCommerce, cached), `gt_stockist_address_string(int $post_id): string` ("addr1, addr2, town, region, postcode, Country name" skipping empties). Admin list gets a "Geocode" column showing `geocode_status`.

- [ ] **Step 1: Write the failing test**

`tests/stockists-cpt.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

gt_assert( post_type_exists( 'stockist' ), 'stockist post type registered' );
$pt = get_post_type_object( 'stockist' );
gt_assert_equal( false, $pt->public, 'stockist is not public' );
gt_assert_equal( true, $pt->show_ui, 'stockist has admin UI' );
gt_assert_equal( false, $pt->publicly_queryable, 'no front-end single' );
gt_assert( taxonomy_exists( 'stockist_type' ), 'stockist_type taxonomy registered' );
gt_assert( in_array( 'stockist', (array) get_taxonomy( 'stockist_type' )->object_type, true ), 'stockist_type attaches to stockists' );

gt_assert( ! empty( acf_get_field_group( 'group_gt_stockist' ) ), 'stockist field group registered' );
$names = wp_list_pluck( acf_get_fields( 'group_gt_stockist' ) ?: array(), 'name' );
foreach ( array( 'address_1', 'address_2', 'town', 'region', 'postcode', 'country', 'phone', 'website', 'email', 'products', 'lat', 'lng', 'geocode_status', 'geocoded_address' ) as $name ) {
	gt_assert( in_array( $name, $names, true ), "stockist group has {$name}" );
}
gt_assert( ! empty( acf_get_field_group( 'group_gt_stockists_page' ) ), 'stockists page group registered' );
$page_names = wp_list_pluck( acf_get_fields( 'group_gt_stockists_page' ) ?: array(), 'name' );
foreach ( array( 'intro', 'trade_heading', 'trade_text', 'trade_image', 'trade_link' ) as $name ) {
	gt_assert( in_array( $name, $page_names, true ), "stockists page group has {$name}" );
}

$countries = gt_stockist_countries();
gt_assert_equal( 'United Kingdom (UK)', $countries['GB'], 'country names come from WooCommerce' );
gt_assert( count( $countries ) > 100, 'full country list' );

$field = acf_get_field( 'field_gt_stockist_country' );
$field = apply_filters( 'acf/load_field', $field );
gt_assert( isset( $field['choices']['GB'] ), 'country select choices populated from WooCommerce' );

// Address string helper on a throwaway post.
$id = wp_insert_post( array( 'post_type' => 'stockist', 'post_status' => 'draft', 'post_title' => 'Test Stockist' ) );
try {
	update_field( 'field_gt_stockist_address_1', '1 High Street', $id );
	update_field( 'field_gt_stockist_town', 'Taunton', $id );
	update_field( 'field_gt_stockist_postcode', 'TA1 1AA', $id );
	update_field( 'field_gt_stockist_country', 'GB', $id );
	gt_assert_equal( '1 High Street, Taunton, TA1 1AA, United Kingdom (UK)', gt_stockist_address_string( $id ), 'address string skips empty parts and expands the country' );
} finally {
	wp_delete_post( $id, true );
}

$columns = apply_filters( 'manage_stockist_posts_columns', array( 'title' => 'Title', 'date' => 'Date' ) );
gt_assert( isset( $columns['geocode'] ), 'admin list has a Geocode column' );

gt_test_done();
```

Run: `tests/bin/wpx eval-file tests/stockists-cpt.test.php` → FAIL (post type missing).

- [ ] **Step 2: `inc/stockists.php`** (Task 2 appends the geocoding + REST parts to this same file)

```php
<?php
/**
 * Stockists: post type, type taxonomy, country list, address helper and the
 * admin geocode column. Geocoding and the REST proxy live further down
 * (appended in Task 2); the front end is page-templates/page-stockists.php.
 */

function gt_register_stockist_post_type() {
	register_post_type(
		'stockist',
		array(
			'labels'              => array(
				'name'          => __( 'Stockists', 'gt' ),
				'singular_name' => __( 'Stockist', 'gt' ),
				'add_new_item'  => __( 'Add New Stockist', 'gt' ),
				'edit_item'     => __( 'Edit Stockist', 'gt' ),
				'menu_name'     => __( 'Stockists', 'gt' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-location',
			'menu_position'       => 27,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
		)
	);

	register_taxonomy(
		'stockist_type',
		'stockist',
		array(
			'labels'             => array(
				'name'          => __( 'Stockist Types', 'gt' ),
				'singular_name' => __( 'Stockist Type', 'gt' ),
				'menu_name'     => __( 'Types', 'gt' ),
				'add_new_item'  => __( 'Add New Type', 'gt' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
			'hierarchical'       => false,
			'rewrite'            => false,
			'query_var'          => false,
		)
	);
}
add_action( 'init', 'gt_register_stockist_post_type' );

/** ISO-2 code => country name, from WooCommerce (falls back to GB only). */
function gt_stockist_countries() {
	static $countries = null;
	if ( null === $countries ) {
		$countries = ( function_exists( 'WC' ) && WC()->countries ) ? WC()->countries->get_countries() : array( 'GB' => 'United Kingdom (UK)' );
	}
	return $countries;
}

/** Feed the country select from WooCommerce's list. */
function gt_stockist_country_choices( $field ) {
	$field['choices'] = gt_stockist_countries();
	return $field;
}
add_filter( 'acf/load_field/name=country', 'gt_stockist_country_choices' );

/** "1 High Street, Taunton, TA1 1AA, United Kingdom (UK)" — empties skipped. */
function gt_stockist_address_string( $post_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}
	$parts = array();
	foreach ( array( 'address_1', 'address_2', 'town', 'region', 'postcode' ) as $name ) {
		$value = trim( (string) get_field( $name, $post_id ) );
		if ( '' !== $value ) {
			$parts[] = $value;
		}
	}
	$code      = strtoupper( trim( (string) get_field( 'country', $post_id ) ) );
	$countries = gt_stockist_countries();
	if ( $code ) {
		$parts[] = isset( $countries[ $code ] ) ? $countries[ $code ] : $code;
	}
	return implode( ', ', $parts );
}

/** Admin list: show whether each stockist has coordinates. */
function gt_stockist_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['geocode'] = __( 'Geocode', 'gt' );
		}
	}
	return $new;
}
add_filter( 'manage_stockist_posts_columns', 'gt_stockist_admin_columns' );

function gt_stockist_admin_column_value( $column, $post_id ) {
	if ( 'geocode' !== $column || ! function_exists( 'get_field' ) ) {
		return;
	}
	$status = (string) get_field( 'geocode_status', $post_id );
	$labels = array(
		'ok'     => __( 'Located', 'gt' ),
		'manual' => __( 'Manual coordinates', 'gt' ),
		'failed' => __( 'Not found — check the address', 'gt' ),
		'no_key' => __( 'No Google Maps key set', 'gt' ),
	);
	echo esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : __( 'Not geocoded yet', 'gt' ) );
}
add_action( 'manage_stockist_posts_custom_column', 'gt_stockist_admin_column_value', 10, 2 );
```

Include from `functions.php` after the `inc/woocommerce-ajax.php` line:

```php
include_once __DIR__ . '/inc/stockists.php';
```

- [ ] **Step 3: `acf-json/stockist.json`**

```json
{
    "key": "group_gt_stockist",
    "title": "Stockist",
    "fields": [
        { "key": "field_gt_stockist_tab_address", "label": "Address", "name": "", "type": "tab", "placement": "top" },
        { "key": "field_gt_stockist_address_1", "label": "Address line 1", "name": "address_1", "type": "text", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_stockist_address_2", "label": "Address line 2", "name": "address_2", "type": "text", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_stockist_town", "label": "Town / city", "name": "town", "type": "text", "required": 1, "wrapper": { "width": "34", "class": "", "id": "" } },
        { "key": "field_gt_stockist_region", "label": "County / state", "name": "region", "type": "text", "wrapper": { "width": "33", "class": "", "id": "" } },
        { "key": "field_gt_stockist_postcode", "label": "Postcode", "name": "postcode", "type": "text", "wrapper": { "width": "33", "class": "", "id": "" } },
        { "key": "field_gt_stockist_country", "label": "Country", "name": "country", "type": "select", "instructions": "United Kingdom stockists appear under the UK tab; everything else under International.", "required": 1, "choices": { "GB": "United Kingdom (UK)" }, "default_value": "GB", "return_format": "value", "ui": 1, "allow_null": 0, "multiple": 0, "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_stockist_tab_contact", "label": "Contact", "name": "", "type": "tab", "placement": "top" },
        { "key": "field_gt_stockist_phone", "label": "Phone", "name": "phone", "type": "text", "wrapper": { "width": "33", "class": "", "id": "" } },
        { "key": "field_gt_stockist_website", "label": "Website", "name": "website", "type": "url", "instructions": "\"Check stock first\" links here (falls back to the phone number).", "wrapper": { "width": "34", "class": "", "id": "" } },
        { "key": "field_gt_stockist_email", "label": "Email", "name": "email", "type": "email", "wrapper": { "width": "33", "class": "", "id": "" } },
        { "key": "field_gt_stockist_tab_products", "label": "Products", "name": "", "type": "tab", "placement": "top" },
        { "key": "field_gt_stockist_products", "label": "Products stocked", "name": "products", "type": "relationship", "instructions": "Shown as chips on the card and used by the product filter.", "post_type": [ "product" ], "filters": [ "search", "taxonomy" ], "return_format": "id", "min": 0, "max": 0 },
        { "key": "field_gt_stockist_tab_location", "label": "Location", "name": "", "type": "tab", "placement": "top" },
        { "key": "field_gt_stockist_lat", "label": "Latitude", "name": "lat", "type": "number", "instructions": "Filled in automatically from the address when a Google Maps key is set. Type coordinates here to override.", "step": "any", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_stockist_lng", "label": "Longitude", "name": "lng", "type": "number", "step": "any", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_stockist_geocode_status", "label": "Geocode status", "name": "geocode_status", "type": "text", "readonly": 1, "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_stockist_geocoded_address", "label": "Geocoded address", "name": "geocoded_address", "type": "text", "instructions": "The address the coordinates were looked up for; changes trigger a new lookup on save.", "readonly": 1, "wrapper": { "width": "50", "class": "", "id": "" } }
    ],
    "location": [ [ { "param": "post_type", "operator": "==", "value": "stockist" } ] ],
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

- [ ] **Step 4: `acf-json/stockists-page.json`**

```json
{
    "key": "group_gt_stockists_page",
    "title": "Stockist Finder page",
    "fields": [
        { "key": "field_gt_stockists_intro", "label": "Intro", "name": "intro", "type": "textarea", "instructions": "Short paragraph to the right of the title.", "rows": 3, "new_lines": "" },
        { "key": "field_gt_stockists_trade_tab", "label": "\"Run a store?\" band", "name": "", "type": "tab", "placement": "top" },
        { "key": "field_gt_stockists_trade_heading", "label": "Heading", "name": "trade_heading", "type": "text", "placeholder": "Run a store? Stock the originals.", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_stockists_trade_image", "label": "Image", "name": "trade_image", "type": "image", "return_format": "id", "library": "all", "preview_size": "medium", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_stockists_trade_text", "label": "Text", "name": "trade_text", "type": "textarea", "rows": 3, "new_lines": "" },
        { "key": "field_gt_stockists_trade_link", "label": "Button", "name": "trade_link", "type": "link", "return_format": "array" }
    ],
    "location": [ [ { "param": "page_template", "operator": "==", "value": "page-templates/page-stockists.php" } ] ],
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

- [ ] **Step 5: Run the test**

Run: `tests/bin/wpx eval-file tests/stockists-cpt.test.php` → `Success: 32 assertions passed`; `tests/run.sh` green.

- [ ] **Step 6: Commit**

```bash
git add gt_system/themes/growth_tech/inc/stockists.php gt_system/themes/growth_tech/functions.php gt_system/themes/growth_tech/acf-json/stockist.json gt_system/themes/growth_tech/acf-json/stockists-page.json tests/stockists-cpt.test.php
git commit -m "Add stockist post type, type taxonomy and fields with a geocode admin column"
```

---

### Task 2: Geocoding — key helper, cached lookup, save hook, REST proxy

**Files:**
- Modify: `inc/stockists.php` (append)
- Create: `tests/stockists-geocode.test.php`

**Interfaces:**
- Produces: `gt_maps_key(): string` (Theme Settings `shop_google_maps_key`, trimmed); `gt_stockist_geocode( string $address, string $region = '' ): array|WP_Error` — `['lat' => float, 'lng' => float, 'label' => string]` via Google Geocoding (`region` is an ISO-2 bias like `gb`), cached 30 days in a transient keyed `gt_geocode_<md5>`; errors: `no_key` (no key set), `http` (request failed), `zero_results`, `bad_response`. `gt_stockist_maybe_geocode( int $post_id ): void` on `acf/save_post` priority 20: manual coords (lat & lng present, `geocoded_address` empty) → status `manual`; unchanged address with coords → untouched; otherwise geocode and store `lat`, `lng`, `geocoded_address`, `geocode_status` (`ok` / `failed` / `no_key`). REST `GET /wp-json/gt/v1/geocode?q=…&region=gb` (public): 400 when `q` < 2 chars, 503 `{"code":"no_key"}` without a key, 404 `{"code":"zero_results"}`, 200 `{lat,lng,label}`.
- Tests stub Google with the `pre_http_request` filter and set the key via `update_field( 'field_gt_shop_maps_key', …, 'option' )`, restoring afterwards.

- [ ] **Step 1: Write the failing test**

`tests/stockists-geocode.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

$google_ok = function ( $pre, $args, $url ) {
	if ( false === strpos( $url, 'maps.googleapis.com/maps/api/geocode' ) ) {
		return $pre;
	}
	$GLOBALS['gt_geocode_calls'][] = $url;
	$body = ( false !== strpos( $url, 'Nowhere' ) )
		? array( 'status' => 'ZERO_RESULTS', 'results' => array() )
		: array( 'status' => 'OK', 'results' => array( array( 'formatted_address' => 'Taunton, UK', 'geometry' => array( 'location' => array( 'lat' => 51.0153, 'lng' => -3.1069 ) ) ) ) );
	return array( 'response' => array( 'code' => 200, 'message' => 'OK' ), 'body' => wp_json_encode( $body ), 'headers' => array(), 'cookies' => array(), 'filename' => null );
};

$saved_key = get_field( 'shop_google_maps_key', 'option' );
$GLOBALS['gt_geocode_calls'] = array();
add_filter( 'pre_http_request', $google_ok, 10, 3 );
delete_transient( 'gt_geocode_' . md5( 'gb|Taunton, UK' ) );
delete_transient( 'gt_geocode_' . md5( '|Nowhere' ) );

try {
	// No key.
	update_field( 'field_gt_shop_maps_key', '', 'option' );
	gt_assert_equal( '', gt_maps_key(), 'no key by default' );
	$result = gt_stockist_geocode( 'Taunton, UK', 'gb' );
	gt_assert( is_wp_error( $result ) && 'no_key' === $result->get_error_code(), 'geocode returns no_key without a key' );
	$request  = new WP_REST_Request( 'GET', '/gt/v1/geocode' );
	$request->set_query_params( array( 'q' => 'Taunton', 'region' => 'gb' ) );
	$response = rest_do_request( $request );
	gt_assert_equal( 503, $response->get_status(), 'proxy is 503 without a key' );
	gt_assert_equal( 'no_key', $response->get_data()['code'], 'proxy names the reason' );

	// With a (fake) key and stubbed Google.
	update_field( 'field_gt_shop_maps_key', 'TEST-KEY', 'option' );
	gt_assert_equal( 'TEST-KEY', gt_maps_key(), 'key read from Theme Settings' );
	$result = gt_stockist_geocode( 'Taunton, UK', 'gb' );
	gt_assert( is_array( $result ) && 51.0153 === $result['lat'] && -3.1069 === $result['lng'], 'geocode parses Google\'s response' );
	gt_assert_equal( 'Taunton, UK', $result['label'], 'geocode label' );
	gt_assert_contains( 'key=TEST-KEY', end( $GLOBALS['gt_geocode_calls'] ), 'request carries the key' );
	gt_assert_contains( 'region=gb', end( $GLOBALS['gt_geocode_calls'] ), 'request carries the region bias' );
	$calls = count( $GLOBALS['gt_geocode_calls'] );
	gt_stockist_geocode( 'Taunton, UK', 'gb' );
	gt_assert_equal( $calls, count( $GLOBALS['gt_geocode_calls'] ), 'second lookup is served from the transient cache' );
	$miss = gt_stockist_geocode( 'Nowhere' );
	gt_assert( is_wp_error( $miss ) && 'zero_results' === $miss->get_error_code(), 'ZERO_RESULTS becomes a zero_results error' );

	$request = new WP_REST_Request( 'GET', '/gt/v1/geocode' );
	$request->set_query_params( array( 'q' => 'Taunton, UK', 'region' => 'gb' ) );
	$response = rest_do_request( $request );
	gt_assert_equal( 200, $response->get_status(), 'proxy 200 with a key' );
	gt_assert_equal( 51.0153, $response->get_data()['lat'], 'proxy returns lat' );
	$request = new WP_REST_Request( 'GET', '/gt/v1/geocode' );
	$request->set_query_params( array( 'q' => 'T' ) );
	gt_assert_equal( 400, rest_do_request( $request )->get_status(), 'proxy rejects short queries' );
	$request = new WP_REST_Request( 'GET', '/gt/v1/geocode' );
	$request->set_query_params( array( 'q' => 'Nowhere' ) );
	gt_assert_equal( 404, rest_do_request( $request )->get_status(), 'proxy 404 on zero results' );

	// Save hook.
	$id = wp_insert_post( array( 'post_type' => 'stockist', 'post_status' => 'publish', 'post_title' => 'Geocode Test' ) );
	try {
		update_field( 'field_gt_stockist_town', 'Taunton', $id );
		update_field( 'field_gt_stockist_country', 'GB', $id );
		gt_stockist_maybe_geocode( $id );
		gt_assert_equal( 'ok', get_field( 'geocode_status', $id ), 'save hook geocodes a new stockist' );
		gt_assert_equal( 51.0153, (float) get_field( 'lat', $id ), 'lat stored' );
		gt_assert_equal( gt_stockist_address_string( $id ), get_field( 'geocoded_address', $id ), 'geocoded address recorded' );

		$calls = count( $GLOBALS['gt_geocode_calls'] );
		gt_stockist_maybe_geocode( $id );
		gt_assert_equal( $calls, count( $GLOBALS['gt_geocode_calls'] ), 'unchanged address is not looked up again' );

		update_field( 'field_gt_stockist_lat', 50.0, $id );
		update_field( 'field_gt_stockist_lng', -1.0, $id );
		update_field( 'field_gt_stockist_geocoded_address', '', $id );
		gt_stockist_maybe_geocode( $id );
		gt_assert_equal( 'manual', get_field( 'geocode_status', $id ), 'typed coordinates are kept as manual' );
		gt_assert_equal( 50.0, (float) get_field( 'lat', $id ), 'manual lat untouched' );

		update_field( 'field_gt_shop_maps_key', '', 'option' );
		update_field( 'field_gt_stockist_lat', '', $id );
		update_field( 'field_gt_stockist_lng', '', $id );
		gt_stockist_maybe_geocode( $id );
		gt_assert_equal( 'no_key', get_field( 'geocode_status', $id ), 'without a key the status says so' );
	} finally {
		wp_delete_post( $id, true );
	}
} finally {
	remove_filter( 'pre_http_request', $google_ok, 10 );
	update_field( 'field_gt_shop_maps_key', $saved_key ? $saved_key : '', 'option' );
	delete_transient( 'gt_geocode_' . md5( 'gb|Taunton, UK' ) );
	delete_transient( 'gt_geocode_' . md5( '|Nowhere' ) );
	delete_transient( 'gt_geocode_' . md5( 'gb|Taunton' ) );
}

gt_test_done();
```

Run: `tests/bin/wpx eval-file tests/stockists-geocode.test.php` → FAIL (`gt_maps_key` undefined).

- [ ] **Step 2: Append to `inc/stockists.php`**

```php

// -- Geocoding -------------------------------------------------------------------

/** Google Maps Platform key from Theme Settings, '' when unset. */
function gt_maps_key() {
	$key = function_exists( 'get_field' ) ? get_field( 'shop_google_maps_key', 'option' ) : '';
	return trim( (string) $key );
}

/**
 * Look an address up with the Google Geocoding API.
 *
 * @param string $address Free-text address or place.
 * @param string $region  Optional ISO-2 bias, e.g. "gb".
 * @return array|WP_Error ['lat' => float, 'lng' => float, 'label' => string]
 */
function gt_stockist_geocode( $address, $region = '' ) {
	$address = trim( (string) $address );
	$region  = strtolower( trim( (string) $region ) );
	if ( '' === $address ) {
		return new WP_Error( 'empty', __( 'No address to look up.', 'gt' ) );
	}
	$key = gt_maps_key();
	if ( '' === $key ) {
		return new WP_Error( 'no_key', __( 'No Google Maps API key is set in Theme Settings.', 'gt' ) );
	}

	$cache_key = 'gt_geocode_' . md5( $region . '|' . $address );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$url = add_query_arg( array_filter( array(
		'address' => rawurlencode( $address ),
		'region'  => $region ? rawurlencode( $region ) : '',
		'key'     => rawurlencode( $key ),
	) ), 'https://maps.googleapis.com/maps/api/geocode/json' );

	$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'http', __( 'The geocoding request failed.', 'gt' ) );
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) || empty( $data['status'] ) ) {
		return new WP_Error( 'bad_response', __( 'Unexpected geocoding response.', 'gt' ) );
	}
	if ( 'ZERO_RESULTS' === $data['status'] || empty( $data['results'][0]['geometry']['location'] ) ) {
		return new WP_Error( 'zero_results', __( 'No location found for that address.', 'gt' ) );
	}
	if ( 'OK' !== $data['status'] ) {
		return new WP_Error( 'bad_response', sprintf( 'Geocoding status: %s', sanitize_text_field( $data['status'] ) ) );
	}

	$location = $data['results'][0]['geometry']['location'];
	$result   = array(
		'lat'   => (float) $location['lat'],
		'lng'   => (float) $location['lng'],
		'label' => isset( $data['results'][0]['formatted_address'] ) ? sanitize_text_field( $data['results'][0]['formatted_address'] ) : $address,
	);
	set_transient( $cache_key, $result, 30 * DAY_IN_SECONDS );
	return $result;
}

/**
 * Keep coordinates in step with the address whenever a stockist is saved.
 * Typed-in coordinates (no geocoded_address on record) are left alone.
 */
function gt_stockist_maybe_geocode( $post_id ) {
	if ( ! is_numeric( $post_id ) || 'stockist' !== get_post_type( $post_id ) || ! function_exists( 'get_field' ) ) {
		return;
	}
	$post_id = (int) $post_id;
	$lat     = get_field( 'lat', $post_id );
	$lng     = get_field( 'lng', $post_id );
	$has_xy  = '' !== (string) $lat && null !== $lat && '' !== (string) $lng && null !== $lng;
	$address = gt_stockist_address_string( $post_id );
	$last    = (string) get_field( 'geocoded_address', $post_id );

	if ( $has_xy && '' === $last ) {
		update_field( 'field_gt_stockist_geocode_status', 'manual', $post_id );
		return;
	}
	if ( $has_xy && $last === $address ) {
		return; // Nothing changed.
	}
	if ( '' === $address ) {
		return;
	}

	$region = strtolower( (string) get_field( 'country', $post_id ) );
	$result = gt_stockist_geocode( $address, $region );
	if ( is_wp_error( $result ) ) {
		update_field( 'field_gt_stockist_geocode_status', 'no_key' === $result->get_error_code() ? 'no_key' : 'failed', $post_id );
		return;
	}
	update_field( 'field_gt_stockist_lat', $result['lat'], $post_id );
	update_field( 'field_gt_stockist_lng', $result['lng'], $post_id );
	update_field( 'field_gt_stockist_geocoded_address', $address, $post_id );
	update_field( 'field_gt_stockist_geocode_status', 'ok', $post_id );
}
add_action( 'acf/save_post', 'gt_stockist_maybe_geocode', 20 );

// -- REST proxy (keeps the key server-side) -----------------------------------------

function gt_stockist_register_rest() {
	register_rest_route( 'gt/v1', '/geocode', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'args'                => array(
			'q'      => array( 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
			'region' => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_key' ),
		),
		'callback'            => function ( WP_REST_Request $request ) {
			$q = trim( (string) $request->get_param( 'q' ) );
			if ( mb_strlen( $q ) < 2 ) {
				return new WP_REST_Response( array( 'code' => 'too_short' ), 400 );
			}
			$result = gt_stockist_geocode( $q, (string) $request->get_param( 'region' ) );
			if ( is_wp_error( $result ) ) {
				$codes = array( 'no_key' => 503, 'zero_results' => 404, 'http' => 502, 'bad_response' => 502, 'empty' => 400 );
				$code  = $result->get_error_code();
				return new WP_REST_Response( array( 'code' => $code ), isset( $codes[ $code ] ) ? $codes[ $code ] : 500 );
			}
			return new WP_REST_Response( $result, 200 );
		},
	) );
}
add_action( 'rest_api_init', 'gt_stockist_register_rest' );
```

- [ ] **Step 3: Run the test**

Run: `tests/bin/wpx eval-file tests/stockists-geocode.test.php` → `Success: 22 assertions passed`; `tests/run.sh` green. (`rest_do_request` needs the REST server bootstrapped — `rest_get_server()` is invoked implicitly; if the route is missing, call `do_action( 'rest_api_init' )` is NOT needed because `rest_do_request` triggers it via `rest_get_server()`.)

- [ ] **Step 4: Commit**

```bash
git add gt_system/themes/growth_tech/inc/stockists.php tests/stockists-geocode.test.php
git commit -m "Add stockist geocoding with a cached lookup, save hook and REST proxy"
```

---

### Task 3: Seed stockists and the Find a Stockist page

**Files:**
- Create: `tests/seed/seed-stockists.php`
- Create: `tests/stockists-seed.test.php`

**Interfaces:**
- Produces (DB, idempotent — stockists matched by title): 8 published stockists with coordinates, types and products: Somerset Hydro Centre (Taunton, GB, 51.0153/−3.1069, Hydroponics specialist, 7 products incl. GT-001, GT-003, GT-002), Bristol Grow Room (GB, 51.4545/−2.5879, Hydroponics specialist, 6 incl. GT-001), Exe Valley Growshop (Exeter GB 50.7184/−3.5339, Grow shop, 5 incl. GT-003 not GT-001), Urban Roots London (GB 51.5074/−0.1278, Grow shop, 5 incl. GT-001), Manchester Hydro (GB 53.4808/−2.2426, Hydroponics specialist, 3: GT-008/009/010), Edinburgh Grow (GB 55.9533/−3.1883, Grow shop, 2: GT-011/013), Dublin Hydro (IE 53.3498/−6.2603, Hydroponics specialist, 4 incl. GT-001), Amsterdam Grow Store (NL 52.3676/4.9041, Grow shop, 3: GT-002/003/004). All have phone; all but Edinburgh Grow have a website; `geocode_status` = `manual`. The `find-a-stockist` page (created by Plan 2's seed) gets template `page-templates/page-stockists.php`, intro, trade band fields and a generated trade image.

- [ ] **Step 1: Write the failing test**

`tests/stockists-seed.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

if ( ! function_exists( 'gt_find_stockist_by_title' ) ) {
	function gt_find_stockist_by_title( $title ) {
		$posts = get_posts( array( 'post_type' => 'stockist', 'post_status' => 'any', 'title' => $title, 'posts_per_page' => 1 ) );
		return $posts ? $posts[0] : null;
	}
}

$all = get_posts( array( 'post_type' => 'stockist', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
gt_assert_equal( 8, count( $all ), 'eight stockists seeded' );

$somerset = gt_find_stockist_by_title( 'Somerset Hydro Centre' );
gt_assert( $somerset instanceof WP_Post, 'Somerset Hydro Centre exists' );
gt_assert_equal( 'GB', get_field( 'country', $somerset->ID ), 'country code' );
gt_assert_equal( 51.0153, (float) get_field( 'lat', $somerset->ID ), 'coordinates seeded' );
gt_assert_equal( 'manual', get_field( 'geocode_status', $somerset->ID ), 'seeded coordinates are marked manual' );
gt_assert_equal( 7, count( (array) get_field( 'products', $somerset->ID ) ), 'Somerset stocks 7 products' );
gt_assert( in_array( wc_get_product_id_by_sku( 'GT-001' ), array_map( 'intval', (array) get_field( 'products', $somerset->ID ) ), true ), 'Somerset stocks Clonex Mist' );
gt_assert_equal( array( 'Hydroponics specialist' ), wp_get_post_terms( $somerset->ID, 'stockist_type', array( 'fields' => 'names' ) ), 'type term' );
gt_assert_contains( '01823', get_field( 'phone', $somerset->ID ), 'phone' );
gt_assert_contains( 'https://', get_field( 'website', $somerset->ID ), 'website' );

$edinburgh = gt_find_stockist_by_title( 'Edinburgh Grow' );
gt_assert_equal( '', (string) get_field( 'website', $edinburgh->ID ), 'Edinburgh has no website (tel fallback case)' );

$intl = array_filter( $all, function ( $p ) { return 'GB' !== get_field( 'country', $p->ID ); } );
gt_assert_equal( 2, count( $intl ), 'two international stockists' );

$page = get_page_by_path( 'find-a-stockist' );
gt_assert_equal( 'page-templates/page-stockists.php', get_page_template_slug( $page->ID ), 'stockist page uses the template' );
gt_assert_contains( 'independent specialists', (string) get_field( 'intro', $page->ID ), 'intro seeded' );
gt_assert_equal( 'Run a store? Stock the originals.', get_field( 'trade_heading', $page->ID ), 'trade heading' );
gt_assert( (int) get_field( 'trade_image', $page->ID ) > 0, 'trade image' );
gt_assert_contains( 'Contact our trade team', get_field( 'trade_link', $page->ID )['title'], 'trade link' );

gt_test_done();
```

Run: `tests/bin/wpx eval-file tests/stockists-seed.test.php` → FAIL (0 stockists).

- [ ] **Step 2: `tests/seed/seed-stockists.php`**

```php
<?php
/**
 * Seed stockists and the Find a Stockist page. Idempotent: stockists are
 * matched by title, the page by slug. Run after seed-shop.php and
 * seed-product-content.php.
 *
 * Run: tests/bin/wpx eval-file tests/seed/seed-stockists.php
 */

if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'update_field' ) ) {
	WP_CLI::error( 'WooCommerce and ACF must be active.' );
}
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

if ( ! function_exists( 'gt_seed_image' ) ) {
	function gt_seed_image( $slug, $label, $width, $height, array $rgb ) {
		$filename = 'gt-seed-' . sanitize_title( $slug ) . '.png';
		$existing = get_posts( array( 'post_type' => 'attachment', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_gt_seed_image', 'meta_value' => $filename ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $existing ) {
			return (int) $existing[0];
		}
		$img = imagecreatetruecolor( $width, $height );
		imagefill( $img, 0, 0, imagecolorallocate( $img, $rgb[0], $rgb[1], $rgb[2] ) );
		$fg = imagecolorallocate( $img, max( 0, $rgb[0] - 60 ), max( 0, $rgb[1] - 60 ), max( 0, $rgb[2] - 60 ) );
		imagefilledrectangle( $img, (int) ( $width * 0.3 ), (int) ( $height * 0.1 ), (int) ( $width * 0.7 ), (int) ( $height * 0.9 ), $fg );
		imagestring( $img, 5, 10, 10, $label, imagecolorallocate( $img, 255, 255, 255 ) );
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
}

if ( ! function_exists( 'gt_find_stockist_by_title' ) ) {
	function gt_find_stockist_by_title( $title ) {
		$posts = get_posts( array( 'post_type' => 'stockist', 'post_status' => 'any', 'title' => $title, 'posts_per_page' => 1 ) );
		return $posts ? $posts[0] : null;
	}
}

$sku = function ( $code ) {
	return (int) wc_get_product_id_by_sku( $code );
};

// name => [addr1, town, region, postcode, country, lat, lng, type, phone, website, skus]
$stockists = array(
	'Somerset Hydro Centre'  => array( '12 Station Road', 'Taunton', 'Somerset', 'TA1 1NL', 'GB', 51.0153, -3.1069, 'Hydroponics specialist', '01823 000 000', 'https://example.com/somerset-hydro', array( 'GT-001', 'GT-003', 'GT-002', 'GT-008', 'GT-009', 'GT-011', 'GT-013' ) ),
	'Bristol Grow Room'      => array( '5 Harbour Way', 'Bristol', '', 'BS1 5AA', 'GB', 51.4545, -2.5879, 'Hydroponics specialist', '0117 000 0000', 'https://example.com/bristol-grow', array( 'GT-001', 'GT-003', 'GT-008', 'GT-002', 'GT-004', 'GT-005' ) ),
	'Exe Valley Growshop'    => array( '8 Mill Lane', 'Exeter', 'Devon', 'EX1 1BB', 'GB', 50.7184, -3.5339, 'Grow shop', '01392 000 000', 'https://example.com/exe-valley', array( 'GT-003', 'GT-011', 'GT-013', 'GT-002', 'GT-006' ) ),
	'Urban Roots London'     => array( '221 Camden High Street', 'London', '', 'NW1 7BU', 'GB', 51.5074, -0.1278, 'Grow shop', '020 0000 0000', 'https://example.com/urban-roots', array( 'GT-001', 'GT-011', 'GT-013', 'GT-002', 'GT-007' ) ),
	'Manchester Hydro'       => array( '40 Deansgate', 'Manchester', '', 'M3 2EG', 'GB', 53.4808, -2.2426, 'Hydroponics specialist', '0161 000 0000', 'https://example.com/manchester-hydro', array( 'GT-008', 'GT-009', 'GT-010' ) ),
	'Edinburgh Grow'         => array( '3 Leith Walk', 'Edinburgh', '', 'EH6 8LN', 'GB', 55.9533, -3.1883, 'Grow shop', '0131 000 0000', '', array( 'GT-011', 'GT-013' ) ),
	'Dublin Hydro'           => array( '14 Dame Street', 'Dublin', '', 'D02 X285', 'IE', 53.3498, -6.2603, 'Hydroponics specialist', '+353 1 000 0000', 'https://example.com/dublin-hydro', array( 'GT-001', 'GT-003', 'GT-008', 'GT-012' ) ),
	'Amsterdam Grow Store'   => array( 'Prinsengracht 100', 'Amsterdam', '', '1015 EA', 'NL', 52.3676, 4.9041, 'Grow shop', '+31 20 000 0000', 'https://example.com/amsterdam-grow', array( 'GT-002', 'GT-003', 'GT-004' ) ),
);

foreach ( $stockists as $name => $s ) {
	$existing = gt_find_stockist_by_title( $name );
	$id       = $existing ? $existing->ID : wp_insert_post( array( 'post_type' => 'stockist', 'post_status' => 'publish', 'post_title' => $name ) );
	if ( ! $id || is_wp_error( $id ) ) {
		WP_CLI::error( "could not create {$name}" );
	}
	update_field( 'field_gt_stockist_address_1', $s[0], $id );
	update_field( 'field_gt_stockist_address_2', '', $id );
	update_field( 'field_gt_stockist_town', $s[1], $id );
	update_field( 'field_gt_stockist_region', $s[2], $id );
	update_field( 'field_gt_stockist_postcode', $s[3], $id );
	update_field( 'field_gt_stockist_country', $s[4], $id );
	update_field( 'field_gt_stockist_lat', $s[5], $id );
	update_field( 'field_gt_stockist_lng', $s[6], $id );
	update_field( 'field_gt_stockist_geocoded_address', '', $id );
	update_field( 'field_gt_stockist_geocode_status', 'manual', $id );
	update_field( 'field_gt_stockist_phone', $s[8], $id );
	update_field( 'field_gt_stockist_website', $s[9], $id );
	update_field( 'field_gt_stockist_email', '', $id );
	update_field( 'field_gt_stockist_products', array_values( array_filter( array_map( $sku, $s[10] ) ) ), $id );
	$type = term_exists( $s[7], 'stockist_type' );
	if ( ! $type ) {
		$type = wp_insert_term( $s[7], 'stockist_type' );
	}
	wp_set_object_terms( $id, array( (int) $type['term_id'] ), 'stockist_type' );
	WP_CLI::log( ( $existing ? 'updated ' : 'created ' ) . $name );
}

$page = get_page_by_path( 'find-a-stockist' );
if ( ! $page ) {
	$page_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Find a Stockist', 'post_name' => 'find-a-stockist' ) );
} else {
	$page_id = $page->ID;
}
update_post_meta( $page_id, '_wp_page_template', 'page-templates/page-stockists.php' );
update_field( 'field_gt_stockists_intro', 'We don’t sell direct — our products are stocked by independent specialists who know them inside out. Search by place, or by the product you’re after.', $page_id );
update_field( 'field_gt_stockists_trade_heading', 'Run a store? Stock the originals.', $page_id );
update_field( 'field_gt_stockists_trade_text', 'Offer your customers professional-grade formulas with proven repeat demand. From eye-catching POS displays to dedicated launch support, we make stocking Growth Technology simple and profitable.', $page_id );
update_field( 'field_gt_stockists_trade_image', gt_seed_image( 'trade-band', 'Trade', 2680, 600, array( 40, 40, 60 ) ), $page_id );
update_field( 'field_gt_stockists_trade_link', array( 'title' => 'Contact our trade team today', 'url' => home_url( '/contact-us/' ), 'target' => '' ), $page_id );

WP_CLI::success( 'Stockists seeded.' );
```

- [ ] **Step 3: Run the seed twice, then the test**

Run: `tests/bin/wpx eval-file tests/seed/seed-stockists.php` (twice — second run says `updated` for all eight and creates no attachments), then `tests/bin/wpx eval-file tests/stockists-seed.test.php` → `Success: 17 assertions passed`; `tests/run.sh` green.

- [ ] **Step 4: Commit**

```bash
git add tests/seed/seed-stockists.php tests/stockists-seed.test.php
git commit -m "Seed stockists and the Find a Stockist page"
```

---

### Task 4: Page template, controls, cards, map placeholder, trade band (server-rendered)

**Files:**
- Modify: `inc/stockists.php` (append card-data + preselect helpers, enqueue)
- Modify: `acf-json/theme-settings-shop.json` (add optional server-side geocoding key)
- Create: `page-templates/page-stockists.php`
- Create: `template-parts/stockists/controls.php`, `template-parts/stockists/card.php`, `template-parts/stockists/map.php`, `template-parts/stockists/trade-band.php`
- Create: `assets/images/icons/pin.svg`
- Create: `assets/sass/components/shop/_s.stockists.scss`
- Modify: `assets/sass/main.scss`, `assets/sass/components/shop/_s.knowledge.scss` (`--trade` modifier)
- Create: `tests/stockists-page.test.php`

**Interfaces:**
- Produces: `gt_geocoding_key(): string` (Theme Settings `shop_google_geocoding_key`, else `gt_maps_key()`) — **Task 2's `gt_stockist_geocode()` must switch to `gt_geocoding_key()`** (do it in this task); `gt_stockists_all(): array` of `['id','name','town','region','postcode','country','country_name','type','lat','lng','phone','website','email','address','directions','products' => [['id','name']…],'brands' => [slugs],'search' => lower-cased "name town postcode region"]` for published stockists ordered by title; `gt_stockist_product_options(): array` (`id => name`, published visible products by title); `gt_stockist_preselect(): array` `['product' => int|0, 'brand' => string, 'region' => 'uk'|'international', 'q' => string]` from `$_GET`; `gt_stockist_is_visible( array $s, array $pre ): bool` (server-side initial visibility: region match, product match when preselected, brand match when preselected); `gt_stockist_countries_present(): array` (code => name for non-GB stockists). Markup hooks for Task 5: `main.stockists[data-stockists][data-has-key]`, `[data-stockists-region]` buttons (`data-region="uk|international"`, `aria-pressed`), `select[data-stockists-country]`, `input[data-stockists-search]`, `select[data-stockists-product]`, `select[data-stockists-sort]` (`az`, `nearest` disabled without key), `[data-stockists-count]`, `ul[data-stockists-cards] > li.stockists-card[data-id][data-country][data-lat][data-lng][data-products][data-brands][data-search][data-name]`, `[data-stockists-empty]`, `[data-stockists-note]`, per card `button[data-card-map]`, `button[data-card-more]`, `.stockists-card__chip.is-extra`, `[data-stockists-map]` with `[data-stockists-canvas]`, `[data-map-zoom="in|out"]`, `.stockists-map__placeholder` (only without a key).

- [ ] **Step 1: Write the failing test**

`tests/stockists-page.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

$mist_id = wc_get_product_id_by_sku( 'GT-001' );
$html    = gt_fetch( '/find-a-stockist/' );

gt_assert_contains( 'class="page-wrapper stockists"', $html, 'template renders' );
gt_assert_contains( '<h1 class="stockists__title">Find a Stockist</h1>', $html, 'title' );
gt_assert_contains( 'independent specialists', $html, 'intro' );
gt_assert_contains( 'data-has-key="0"', $html, 'no maps key flag' );
gt_assert_contains( 'stockists-map__placeholder', $html, 'map placeholder without a key' );
gt_assert_not_contains( 'maps.googleapis.com/maps/api/js', $html, 'Maps JS not loaded without a key' );
gt_assert_contains( 'option value="nearest" disabled', $html, 'nearest-first disabled without a key' );

gt_assert_equal( 8, substr_count( $html, '<li class="stockists-card' ), 'all eight cards rendered' );
gt_assert_equal( 2, substr_count( $html, '<li class="stockists-card" hidden' ), 'the two international cards start hidden' );
gt_assert_contains( '>6 stockists<', $html, 'count reflects the UK default' );
gt_assert_contains( 'data-region="uk" aria-pressed="true"', $html, 'UK toggle active' );
gt_assert_contains( 'data-stockists-country', $html, 'country select present' );
gt_assert_contains( '<option value="IE">Ireland</option>', $html, 'country options from stockists present' );

// Somerset card.
$start = strpos( $html, 'data-name="Somerset Hydro Centre"' );
$card  = substr( $html, $start - 200, 3000 );
gt_assert_contains( 'data-country="GB"', $card, 'card country attr' );
gt_assert_contains( 'data-lat="51.0153"', $card, 'card lat attr' );
gt_assert_contains( 'data-products="', $card, 'card products attr' );
gt_assert_contains( 'data-brands="', $card, 'card brands attr' );
gt_assert_contains( 'Taunton, United Kingdom (UK)', $card, 'town + country' );
gt_assert_contains( 'Hydroponics specialist', $card, 'type' );
gt_assert_equal( 7, substr_count( $card, 'stockists-card__chip' ), 'seven product chips' );
gt_assert_equal( 4, substr_count( $card, 'is-extra' ), 'chips beyond three are marked extra' );
gt_assert_contains( '>+4 more<', $card, 'more toggle label' );
gt_assert_contains( 'google.com/maps/dir/?api=1&#038;destination=51.0153,-3.1069', $card, 'directions link uses the coordinates' );
gt_assert_contains( 'href="https://example.com/somerset-hydro"', $card, 'check stock links to the website' );
gt_assert_contains( 'data-card-map', $card, 'show on map button' );

$start = strpos( $html, 'data-name="Edinburgh Grow"' );
$card  = substr( $html, $start - 200, 3000 );
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
```

Run: `tests/bin/wpx eval-file tests/stockists-page.test.php` → FAIL (default page template renders).

- [ ] **Step 2: Theme Settings — optional server-side key.** In `acf-json/theme-settings-shop.json` add after `field_gt_shop_maps_key`:

```json
        { "key": "field_gt_shop_geocoding_key", "label": "Google Geocoding key (server)", "name": "shop_google_geocoding_key", "type": "text", "instructions": "Optional. A browser key restricted by HTTP referrer is rejected by the Geocoding web service — use a second key restricted by IP (or unrestricted) here. Leave empty to reuse the Maps key.", "wrapper": { "width": "50", "class": "", "id": "" } }
```

- [ ] **Step 3: Append to `inc/stockists.php`** and switch `gt_stockist_geocode()` to `gt_geocoding_key()` (replace its `$key = gt_maps_key();` line).

```php

// -- Data for the finder page ------------------------------------------------------

/** Server-side Geocoding key: the dedicated field, else the Maps key. */
function gt_geocoding_key() {
	$key = function_exists( 'get_field' ) ? trim( (string) get_field( 'shop_google_geocoding_key', 'option' ) ) : '';
	return '' !== $key ? $key : gt_maps_key();
}

/** Every published stockist as a flat array the template and JS both read. */
function gt_stockists_all() {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}
	$countries = gt_stockist_countries();
	$posts     = get_posts( array( 'post_type' => 'stockist', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	$out       = array();

	foreach ( $posts as $post ) {
		$id       = $post->ID;
		$code     = strtoupper( trim( (string) get_field( 'country', $id ) ) );
		$lat      = get_field( 'lat', $id );
		$lng      = get_field( 'lng', $id );
		$has_xy   = '' !== (string) $lat && null !== $lat && '' !== (string) $lng && null !== $lng;
		$products = array();
		$brands   = array();
		foreach ( array_map( 'intval', (array) get_field( 'products', $id ) ) as $pid ) {
			$product = $pid ? wc_get_product( $pid ) : null;
			if ( ! $product instanceof WC_Product || 'publish' !== $product->get_status() ) {
				continue;
			}
			$products[] = array( 'id' => $pid, 'name' => $product->get_name() );
			$brand      = gt_product_brand( $product );
			if ( $brand ) {
				$brands[ $brand->slug ] = $brand->slug;
			}
		}
		$types   = wp_get_post_terms( $id, 'stockist_type', array( 'fields' => 'names' ) );
		$address = gt_stockist_address_string( $id );
		$town    = trim( (string) get_field( 'town', $id ) );

		$out[] = array(
			'id'           => $id,
			'name'         => get_the_title( $post ),
			'town'         => $town,
			'region'       => trim( (string) get_field( 'region', $id ) ),
			'postcode'     => trim( (string) get_field( 'postcode', $id ) ),
			'country'      => $code,
			'country_name' => isset( $countries[ $code ] ) ? $countries[ $code ] : $code,
			'type'         => ( ! is_wp_error( $types ) && $types ) ? $types[0] : '',
			'lat'          => $has_xy ? (float) $lat : null,
			'lng'          => $has_xy ? (float) $lng : null,
			'phone'        => trim( (string) get_field( 'phone', $id ) ),
			'website'      => trim( (string) get_field( 'website', $id ) ),
			'email'        => trim( (string) get_field( 'email', $id ) ),
			'address'      => $address,
			'directions'   => 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $has_xy ? $lat . ',' . $lng : $address ),
			'products'     => $products,
			'brands'       => array_values( $brands ),
			'search'       => strtolower( trim( get_the_title( $post ) . ' ' . $town . ' ' . get_field( 'postcode', $id ) . ' ' . get_field( 'region', $id ) ) ),
		);
	}
	return $out;
}

/** id => name for the "Stocking any product" select. */
function gt_stockist_product_options() {
	$options = array();
	foreach ( wc_get_products( array( 'limit' => -1, 'status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) ) as $product ) {
		if ( $product->is_visible() ) {
			$options[ $product->get_id() ] = $product->get_name();
		}
	}
	return $options;
}

/** Country options for the International tab: codes present on non-GB stockists. */
function gt_stockist_countries_present( array $stockists ) {
	$present = array();
	foreach ( $stockists as $s ) {
		if ( 'GB' !== $s['country'] && $s['country'] ) {
			$present[ $s['country'] ] = $s['country_name'];
		}
	}
	asort( $present );
	return $present;
}

/** What the URL asked for: ?product=, ?brand=, ?region=, ?q=. */
function gt_stockist_preselect() {
	$product = isset( $_GET['product'] ) ? (int) $_GET['product'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $product && ( ! wc_get_product( $product ) || 'publish' !== get_post_status( $product ) ) ) {
		$product = 0;
	}
	$brand = isset( $_GET['brand'] ) ? sanitize_title( wp_unslash( $_GET['brand'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $brand && ! term_exists( $brand, 'product_brand' ) ) {
		$brand = '';
	}
	$region = isset( $_GET['region'] ) && 'international' === sanitize_key( $_GET['region'] ) ? 'international' : 'uk'; // phpcs:ignore WordPress.Security.NonceVerification
	$q      = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	return array( 'product' => $product, 'brand' => $brand, 'region' => $region, 'q' => $q );
}

/** Initial visibility of a card for the requested region/product/brand. */
function gt_stockist_is_visible( array $s, array $pre ) {
	$is_uk = 'GB' === $s['country'];
	if ( ( 'uk' === $pre['region'] ) !== $is_uk ) {
		return false;
	}
	if ( $pre['product'] && ! in_array( $pre['product'], wp_list_pluck( $s['products'], 'id' ), true ) ) {
		return false;
	}
	if ( $pre['brand'] && ! in_array( $pre['brand'], $s['brands'], true ) ) {
		return false;
	}
	return true;
}

/** Front-end script (+ Google Maps when a key is set) on the finder template only. */
function gt_stockists_enqueue() {
	if ( ! is_page_template( 'page-templates/page-stockists.php' ) ) {
		return;
	}
	$key = gt_maps_key();
	wp_enqueue_script( 'gt-stockists', get_template_directory_uri() . '/assets/js/stockists.js', array(), gt_asset_version( '/assets/js/stockists.js' ), true );
	wp_localize_script( 'gt-stockists', 'gtStockists', array(
		'geocodeUrl' => rest_url( 'gt/v1/geocode' ),
		'hasKey'     => '' !== $key,
		'pin'        => 'data:image/svg+xml;utf8,' . rawurlencode( '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="24.75" viewBox="0 0 18 24.75"><path fill="#000" d="M0 8.84063C0 3.95625 4.03125 0 9 0C13.9688 0 18 3.95625 18 8.84063C18 15.9094 9 24.75 9 24.75C9 24.75 0 15.9094 0 8.84063ZM9 12C9.79565 12 10.5587 11.6839 11.1213 11.1213C11.6839 10.5587 12 9.79565 12 9C12 8.20435 11.6839 7.44129 11.1213 6.87868C10.5587 6.31607 9.79565 6 9 6C8.20435 6 7.44129 6.31607 6.87868 6.87868C6.31607 7.44129 6 8.20435 6 9C6 9.79565 6.31607 10.5587 6.87868 11.1213C7.44129 11.6839 8.20435 12 9 12Z"/></svg>' ),
		'strings'    => array(
			/* translators: %d: number of stockists */
			'count'    => __( '%d stockists', 'gt' ),
			'one'      => __( '1 stockist', 'gt' ),
			'nearest'  => __( 'Showing stockists nearest to %s', 'gt' ),
			'showLess' => __( 'Show less', 'gt' ),
			'more'     => __( '+%d more', 'gt' ),
		),
	) );
	if ( '' !== $key ) {
		wp_enqueue_script(
			'google-maps',
			add_query_arg( array( 'key' => rawurlencode( $key ), 'callback' => 'gtStockistsMapReady', 'loading' => 'async' ), 'https://maps.googleapis.com/maps/api/js' ),
			array( 'gt-stockists' ),
			null,
			array( 'in_footer' => true, 'strategy' => 'async' )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'gt_stockists_enqueue' );
```

- [ ] **Step 4: `page-templates/page-stockists.php`**

```php
<?php
/* Template Name: Find a Stockist */
/**
 * Stockist finder — Figma 384:2719. Title + intro, controls, the stockist
 * list beside the map, and the "Run a store?" band. Every card is rendered
 * server-side with data attributes; stockists.js filters/sorts them and
 * drives the map. Without a Google Maps key the map is a placeholder and
 * the list still works.
 */

get_header();

$page_id   = get_queried_object_id();
$intro     = function_exists( 'get_field' ) ? (string) get_field( 'intro', $page_id ) : '';
$stockists = gt_stockists_all();
$pre       = gt_stockist_preselect();
$visible   = array_filter( $stockists, function ( $s ) use ( $pre ) { return gt_stockist_is_visible( $s, $pre ); } );
$has_key   = '' !== gt_maps_key();
?>

<main class="page-wrapper stockists" data-stockists data-has-key="<?php echo $has_key ? '1' : '0'; ?>"
	data-region="<?php echo esc_attr( $pre['region'] ); ?>" data-product="<?php echo esc_attr( $pre['product'] ); ?>" data-brand="<?php echo esc_attr( $pre['brand'] ); ?>">
	<div class="stockists__inner">

		<header class="stockists__head">
			<h1 class="stockists__title"><?php echo esc_html( get_the_title( $page_id ) ); ?></h1>
			<?php if ( $intro ) : ?>
				<p class="stockists__intro"><?php echo wp_kses_post( $intro ); ?></p>
			<?php endif; ?>
		</header>

		<?php get_template_part( 'template-parts/stockists/controls', null, array( 'stockists' => $stockists, 'pre' => $pre, 'has_key' => $has_key ) ); ?>

		<div class="stockists__results">
			<div class="stockists__list-col">
				<div class="stockists__toolbar">
					<p class="stockists__count" data-stockists-count aria-live="polite"><?php
						$n = count( $visible );
						/* translators: %d: number of stockists */
						echo esc_html( 1 === $n ? __( '1 stockist', 'gt' ) : sprintf( __( '%d stockists', 'gt' ), $n ) );
					?></p>
					<label class="stockists__sort">
						<span class="stockists__sort-label"><?php esc_html_e( 'Sort by', 'gt' ); ?></span>
						<span class="stockists__select-wrap">
							<select class="stockists__sort-select" data-stockists-sort>
								<option value="nearest"<?php echo $has_key ? '' : ' disabled'; ?><?php echo $has_key ? '' : ' title="' . esc_attr__( 'Search for a place to sort by distance', 'gt' ) . '"'; ?>><?php esc_html_e( 'Nearest first', 'gt' ); ?></option>
								<option value="az" selected><?php esc_html_e( 'A – Z', 'gt' ); ?></option>
							</select>
						</span>
					</label>
				</div>
				<p class="stockists__note" data-stockists-note hidden></p>
				<div class="stockists__list">
					<ul class="stockists__cards" data-stockists-cards>
						<?php foreach ( $stockists as $s ) : ?>
							<?php get_template_part( 'template-parts/stockists/card', null, array( 'stockist' => $s, 'visible' => gt_stockist_is_visible( $s, $pre ) ) ); ?>
						<?php endforeach; ?>
					</ul>
					<p class="stockists__empty" data-stockists-empty<?php echo $visible ? ' hidden' : ''; ?>><?php esc_html_e( 'No stockists match your search yet. Try another place or product.', 'gt' ); ?></p>
				</div>
			</div>

			<div class="stockists__map-col">
				<?php get_template_part( 'template-parts/stockists/map', null, array( 'has_key' => $has_key ) ); ?>
			</div>
		</div>

		<?php get_template_part( 'template-parts/stockists/trade-band', null, array( 'page_id' => $page_id ) ); ?>
	</div>

<?php
get_footer();
```

- [ ] **Step 5: `template-parts/stockists/controls.php`**

```php
<?php
/**
 * Finder controls — Figma 384:2726. Region toggle, place/store search,
 * product filter; the country select appears for International.
 *
 * @param array $args ['stockists' => array, 'pre' => array, 'has_key' => bool]
 */

$stockists = isset( $args['stockists'] ) ? $args['stockists'] : array();
$pre       = isset( $args['pre'] ) ? $args['pre'] : gt_stockist_preselect();
$countries = gt_stockist_countries_present( $stockists );
$products  = gt_stockist_product_options();
$intl      = 'international' === $pre['region'];
?>
<div class="stockists-controls" data-stockists-controls>
	<div class="stockists-controls__region" role="group" aria-label="<?php esc_attr_e( 'Region', 'gt' ); ?>" data-stockists-region>
		<button type="button" class="stockists-controls__toggle<?php echo $intl ? '' : ' is-active'; ?>" data-region="uk" aria-pressed="<?php echo $intl ? 'false' : 'true'; ?>"><?php esc_html_e( 'United Kingdom', 'gt' ); ?></button>
		<button type="button" class="stockists-controls__toggle<?php echo $intl ? ' is-active' : ''; ?>" data-region="international" aria-pressed="<?php echo $intl ? 'true' : 'false'; ?>"><?php esc_html_e( 'International', 'gt' ); ?></button>
	</div>

	<label class="stockists-controls__country<?php echo $intl ? '' : ' is-hidden'; ?>" data-stockists-country-wrap>
		<span class="screen-reader-text"><?php esc_html_e( 'Country', 'gt' ); ?></span>
		<span class="stockists-controls__select-wrap">
			<select class="stockists-controls__select" data-stockists-country>
				<option value=""><?php esc_html_e( 'All countries', 'gt' ); ?></option>
				<?php foreach ( $countries as $code => $name ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
		</span>
	</label>

	<label class="stockists-controls__search">
		<span class="screen-reader-text"><?php esc_html_e( 'Search by town, city or store name', 'gt' ); ?></span>
		<span class="stockists-controls__search-icon" aria-hidden="true"><?php gt_icon_svg( 'search' ); ?></span>
		<input type="search" class="stockists-controls__input" data-stockists-search value="<?php echo esc_attr( $pre['q'] ); ?>"
			placeholder="<?php esc_attr_e( 'Search by town, city or store name...', 'gt' ); ?>" autocomplete="off" />
	</label>

	<label class="stockists-controls__product">
		<span class="screen-reader-text"><?php esc_html_e( 'Product', 'gt' ); ?></span>
		<span class="stockists-controls__select-wrap">
			<select class="stockists-controls__select" data-stockists-product>
				<option value=""><?php esc_html_e( 'Stocking any product', 'gt' ); ?></option>
				<?php foreach ( $products as $id => $name ) : ?>
					<option value="<?php echo esc_attr( $id ); ?>"<?php selected( $pre['product'], $id ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
		</span>
	</label>
</div>
```

- [ ] **Step 6: `template-parts/stockists/card.php`**

```php
<?php
/**
 * Stockist card — Figma 384:2739.
 *
 * @param array $args ['stockist' => array (from gt_stockists_all), 'visible' => bool]
 */

$s       = isset( $args['stockist'] ) ? $args['stockist'] : null;
$visible = ! isset( $args['visible'] ) || $args['visible'];
if ( ! is_array( $s ) ) {
	return;
}
$chips     = $s['products'];
$extra     = max( 0, count( $chips ) - 3 );
$tel       = preg_replace( '/[^\d+]/', '', $s['phone'] );
$check_url = $s['website'] ? $s['website'] : ( $tel ? 'tel:' . $tel : '' );
$is_site   = (bool) $s['website'];
?>
<li class="stockists-card"<?php echo $visible ? '' : ' hidden'; ?>
	data-id="<?php echo esc_attr( $s['id'] ); ?>"
	data-name="<?php echo esc_attr( $s['name'] ); ?>"
	data-country="<?php echo esc_attr( $s['country'] ); ?>"
	data-lat="<?php echo esc_attr( null === $s['lat'] ? '' : $s['lat'] ); ?>"
	data-lng="<?php echo esc_attr( null === $s['lng'] ? '' : $s['lng'] ); ?>"
	data-products="<?php echo esc_attr( implode( ',', wp_list_pluck( $s['products'], 'id' ) ) ); ?>"
	data-brands="<?php echo esc_attr( implode( ',', $s['brands'] ) ); ?>"
	data-search="<?php echo esc_attr( $s['search'] ); ?>">
	<div class="stockists-card__head">
		<div class="stockists-card__id">
			<h2 class="stockists-card__name"><?php echo esc_html( $s['name'] ); ?></h2>
			<p class="stockists-card__meta">
				<span><?php echo esc_html( trim( $s['town'] . ( $s['town'] && $s['country_name'] ? ', ' : '' ) . $s['country_name'] ) ); ?></span>
				<?php if ( $s['type'] ) : ?>
					<span class="stockists-card__dot" aria-hidden="true"></span>
					<span><?php echo esc_html( $s['type'] ); ?></span>
				<?php endif; ?>
			</p>
		</div>
		<?php if ( null !== $s['lat'] ) : ?>
			<button type="button" class="stockists-card__map" data-card-map><?php esc_html_e( 'Show on map', 'gt' ); ?></button>
		<?php endif; ?>
	</div>

	<?php if ( $chips ) : ?>
		<ul class="stockists-card__chips">
			<?php foreach ( $chips as $i => $chip ) : ?>
				<li class="stockists-card__chip<?php echo $i >= 3 ? ' is-extra' : ''; ?>"><?php echo esc_html( $chip['name'] ); ?></li>
			<?php endforeach; ?>
			<?php if ( $extra ) : ?>
				<li class="stockists-card__chip stockists-card__chip--more">
					<button type="button" class="stockists-card__more" data-card-more data-count="<?php echo esc_attr( $extra ); ?>" aria-expanded="false"><?php echo esc_html( sprintf( /* translators: %d: hidden chips */ __( '+%d more', 'gt' ), $extra ) ); ?></button>
				</li>
			<?php endif; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $s['phone'] || $s['directions'] || $check_url ) : ?>
		<div class="stockists-card__actions">
			<?php if ( $s['phone'] ) : ?>
				<a class="stockists-card__phone" href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo esc_html( $s['phone'] ); ?></a>
			<?php endif; ?>
			<a class="stockists-card__link" href="<?php echo esc_url( $s['directions'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get Directions', 'gt' ); ?></a>
			<?php if ( $check_url ) : ?>
				<a class="stockists-card__link" href="<?php echo esc_url( $check_url ); ?>"<?php echo $is_site ? ' target="_blank" rel="noopener"' : ''; ?>><?php esc_html_e( 'Check stock first', 'gt' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</li>
```

- [ ] **Step 7: `template-parts/stockists/map.php`**

```php
<?php
/**
 * Map column — Figma 384:2843. Google Maps renders into the canvas when a
 * key is set; otherwise a grey placeholder (with a hint for admins).
 *
 * @param array $args ['has_key' => bool]
 */

$has_key = ! empty( $args['has_key'] );
?>
<div class="stockists-map" data-stockists-map>
	<div class="stockists-map__canvas" data-stockists-canvas role="region" aria-label="<?php esc_attr_e( 'Map of stockists', 'gt' ); ?>"></div>
	<?php if ( ! $has_key ) : ?>
		<div class="stockists-map__placeholder">
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<p class="stockists-map__hint"><?php esc_html_e( 'Add a Google Maps API key under Theme Settings → Shop Settings to show the map.', 'gt' ); ?></p>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="stockists-map__zoom">
			<button type="button" class="stockists-map__zoom-btn" data-map-zoom="in" aria-label="<?php esc_attr_e( 'Zoom in', 'gt' ); ?>">+</button>
			<button type="button" class="stockists-map__zoom-btn" data-map-zoom="out" aria-label="<?php esc_attr_e( 'Zoom out', 'gt' ); ?>">&minus;</button>
		</div>
	<?php endif; ?>
</div>
```

- [ ] **Step 8: `template-parts/stockists/trade-band.php`**

```php
<?php
/**
 * "Run a store? Stock the originals." — Figma 384:2866. Same component as
 * the product page's knowledge band, fed by the page's ACF fields.
 *
 * @param array $args ['page_id' => int]
 */

$page_id = isset( $args['page_id'] ) ? (int) $args['page_id'] : get_queried_object_id();
if ( ! function_exists( 'get_field' ) ) {
	return;
}
$heading  = (string) get_field( 'trade_heading', $page_id );
$text     = (string) get_field( 'trade_text', $page_id );
$image_id = (int) get_field( 'trade_image', $page_id );
$link     = get_field( 'trade_link', $page_id );
if ( '' === trim( $heading ) ) {
	return;
}
?>
<section class="knowledge-band knowledge-band--trade" aria-labelledby="trade-band-title">
	<?php
	if ( $image_id ) {
		echo wp_get_attachment_image( $image_id, 'gt-knowledge', false, array( 'class' => 'knowledge-band__img', 'alt' => '', 'sizes' => '(max-width: 1439px) calc(100vw - 100px), 1340px', 'loading' => 'lazy' ) );
	}
	?>
	<span class="knowledge-band__scrim" aria-hidden="true"></span>
	<div class="knowledge-band__content">
		<h2 id="trade-band-title" class="knowledge-band__title"><?php echo esc_html( $heading ); ?></h2>
		<?php if ( $text ) : ?>
			<p class="knowledge-band__text"><?php echo wp_kses_post( $text ); ?></p>
		<?php endif; ?>
		<?php if ( is_array( $link ) && ! empty( $link['url'] ) ) : ?>
			<a class="btn-flat" href="<?php echo esc_url( $link['url'] ); ?>"<?php echo ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; ?>>
				<span><?php echo esc_html( ! empty( $link['title'] ) ? $link['title'] : __( 'Contact our trade team today', 'gt' ) ); ?></span>
				<?php gt_arrow_svg(); ?>
			</a>
		<?php endif; ?>
	</div>
</section>
```

In `_s.knowledge.scss` add at the end (before the media queries): `.knowledge-band--trade { margin-top: 60px; }`.

- [ ] **Step 9: `assets/images/icons/pin.svg`** (Figma 384:2845; used by the placeholder/legend, the map uses the data URI)

```svg
<svg width="18" height="25" viewBox="0 0 18 24.75" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill="black" d="M0 8.84063C0 3.95625 4.03125 0 9 0C13.9688 0 18 3.95625 18 8.84063C18 15.9094 9 24.75 9 24.75C9 24.75 0 15.9094 0 8.84063ZM9 12C9.79565 12 10.5587 11.6839 11.1213 11.1213C11.6839 10.5587 12 9.79565 12 9C12 8.20435 11.6839 7.44129 11.1213 6.87868C10.5587 6.31607 9.79565 6 9 6C8.20435 6 7.44129 6.31607 6.87868 6.87868C6.31607 7.44129 6 8.20435 6 9C6 9.79565 6.31607 10.5587 6.87868 11.1213C7.44129 11.6839 8.20435 12 9 12Z"/></svg>
```

- [ ] **Step 10: SCSS — `assets/sass/components/shop/_s.stockists.scss`**

```scss
// ---------------------------------------------------------------------------
// Stockist finder — Figma 384:2719
//
// 50px gutters / 1340 content. Head row 60 under the header with a rule;
// controls 45 below (49px tall); results 29 below: 562 list column + 20 gap
// + 758 map (724 tall). Cards 550 wide, 20 padding, 15 gaps. Every text
// element sets its own face and size (theme base rules would win otherwise).
// ---------------------------------------------------------------------------

$st-font: "DM Sans", #{$primary_font};
$st-line: rgba(0, 0, 0, 0.1);
$st-muted: rgba(0, 0, 0, 0.5);

.stockists {
  padding: 60px 0 0;

  &__inner {
    max-width: 1440px;
    margin: 0 auto;
    padding: 0 50px;
  }

  // -- head --------------------------------------------------------------------
  &__head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 40px;
    padding-bottom: 50px;
    border-bottom: 1px solid $st-line;
  }

  &__title {
    max-width: 743px;
    margin: 0;
    font-family: "Cormorant Garamond", serif;
    font-size: 60px;
    font-weight: $font-700;
    line-height: 65px;
    letter-spacing: -1.2px;
    color: #000;
  }

  &__intro {
    flex: 0 0 356px;
    max-width: 356px;
    margin: 0;
    font-family: $st-font;
    font-size: 15px;
    font-weight: $font-400;
    line-height: 25px;
    color: rgba(0, 0, 0, 0.75);
  }

  // -- results -------------------------------------------------------------------
  &__results {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-top: 29px;
  }

  &__list-col {
    flex: 0 0 562px;
    max-width: 562px;
    min-width: 0;
  }

  &__map-col {
    flex: 1 1 auto;
    min-width: 0;
  }

  &__toolbar {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    width: 550px;
    max-width: 100%;
    margin-bottom: 15px;
  }

  &__count,
  &__note,
  &__empty {
    margin: 0;
    font-family: $st-font;
    font-size: 12px;
    font-weight: $font-400;
    line-height: normal;
    color: $st-muted;
  }

  &__note {
    margin-bottom: 12px;
    font-size: 13px;
  }

  &__empty {
    padding: 30px 0;
    font-size: 15px;
  }

  &__sort {
    display: flex;
    align-items: flex-start;
    gap: 10px;
  }

  &__sort-label {
    font-family: $st-font;
    font-size: 12px;
    line-height: normal;
    color: $st-muted;
  }

  &__select-wrap {
    display: inline-block;
    border-bottom: 1px solid #000;
  }

  &__sort-select {
    appearance: none;
    -webkit-appearance: none;
    min-width: 0;
    height: auto;
    padding: 0 12px 5px 0;
    border: 0;
    border-radius: 0;
    font-family: $st-font;
    font-size: 12px;
    line-height: normal;
    color: #000;
    cursor: pointer;
    background-color: transparent;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='7' height='4' viewBox='0 0 7 4'%3E%3Cpath fill='%23000' d='M3.5 4 0 .64.67 0 3.5 2.72 6.33 0 7 .64Z'/%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 6px !important;
    background-size: 7px 4px !important;

    &:focus-visible {
      outline: 2px solid #000;
      outline-offset: 3px;
    }
  }

  &__list {
    position: relative;
    max-height: 773px;
    padding-right: 12px;
    overflow-y: auto;

    // The white fade at the bottom of the scroll box (Figma "Rectangle 24").
    &::after {
      content: "";
      position: sticky;
      bottom: 0;
      display: block;
      height: 60px;
      margin-top: -60px;
      background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, #fff 100%);
      pointer-events: none;
    }
  }

  &__cards {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin: 0;
    padding: 0 0 60px;
    list-style: none;
  }
}

// -- controls — Figma 384:2726 ---------------------------------------------------
.stockists-controls {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 15px;
  margin-top: 45px;

  &__region {
    display: flex;
    height: 49px;
  }

  &__toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 49px;
    padding: 10px 15px;
    border: 1px solid $st-line;
    background: none;
    font-family: $st-font;
    font-size: 13px;
    font-weight: $font-400;
    line-height: normal;
    color: #000;
    cursor: pointer;
    transition: background-color 0.2s ease, color 0.2s ease;

    & + & {
      border-left: 0;
    }

    &.is-active {
      border-color: #000;
      background-color: #000;
      color: color(white);
    }

    &:focus-visible {
      outline: 2px solid #000;
      outline-offset: 2px;
    }
  }

  &__search {
    display: flex;
    align-items: center;
    gap: 7px;
    width: 350px;
    height: 49px;
    padding-left: 16px;
    border: 1px solid rgba(35, 31, 32, 0.1);
    background-color: color(white);

    &:focus-within {
      border-color: rgba(35, 31, 32, 0.4);
    }
  }

  &__search-icon {
    flex: 0 0 auto;
    display: block;
    width: 20px;
    height: 20px;
    color: #000;

    svg {
      display: block;
      width: 20px;
      height: 20px;
    }
  }

  &__input {
    flex: 1 1 auto;
    min-width: 0;
    height: 100%;
    width: auto;
    padding: 0 12px 0 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    font-family: $st-font;
    font-size: 13px;
    font-weight: $font-400;
    line-height: normal;
    color: #000;

    &::placeholder {
      font-weight: $font-300;
      color: rgba(35, 31, 32, 0.35);
      opacity: 1;
    }

    &::-webkit-search-cancel-button {
      -webkit-appearance: none;
    }
  }

  &__country,
  &__product {
    display: block;

    &.is-hidden {
      display: none;
    }
  }

  &__select-wrap {
    display: flex;
    align-items: center;
    height: 49px;
    border: 1px solid rgba(35, 31, 32, 0.1);
    background-color: color(white);
  }

  &__select {
    appearance: none;
    -webkit-appearance: none;
    min-width: 0;
    height: 100%;
    padding: 0 40px 0 15px;
    border: 0;
    border-radius: 0;
    font-family: $st-font;
    font-size: 13px;
    font-weight: $font-400;
    line-height: normal;
    color: #000;
    cursor: pointer;
    background-color: transparent;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%23000' d='M5 6 0 .96.95 0 5 4.08 9.05 0 10 .96Z'/%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 15px center !important;
    background-size: 10px 6px !important;

    &:focus-visible {
      outline: 2px solid #000;
      outline-offset: -2px;
    }
  }
}

// -- card — Figma 384:2739 ---------------------------------------------------------
.stockists-card {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin: 0;
  padding: 20px;
  border: 1px solid $st-line;
  background-color: color(white);
  transition: border-color 0.2s ease;

  &[hidden] {
    display: none;
  }

  &.is-active {
    border-color: #000;
  }

  &__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
  }

  &__id {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 0;
  }

  &__name {
    margin: 0;
    font-family: $st-font;
    font-size: 18px;
    font-weight: $font-500;
    line-height: normal;
    color: #000;
  }

  &__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-family: $st-font;
    font-size: 12px;
    font-weight: $font-400;
    line-height: normal;
    color: $st-muted;

    span {
      font: inherit;
    }
  }

  &__dot {
    display: block;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background-color: #d9d9d9;
  }

  &__map {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 112px;
    height: 44px;
    padding: 10px 15px;
    background-color: #000;
    font-family: $st-font;
    font-size: 13px;
    font-weight: $font-400;
    line-height: normal;
    color: color(white);
    cursor: pointer;
    transition: background-color 0.2s ease;

    &:hover,
    &:focus-visible {
      background-color: #231f20;
    }

    &:focus-visible {
      outline: 2px solid #000;
      outline-offset: 2px;
    }

    // Nothing to show without a map.
    .stockists[data-has-key="0"] & {
      display: none;
    }
  }

  &__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  &__chip {
    display: inline-flex;
    align-items: center;
    margin: 0;
    padding: 10px 15px;
    background-color: #f4f4f4;
    font-family: $st-font;
    font-size: 12px;
    font-weight: $font-400;
    line-height: normal;
    color: #000;

    &.is-extra {
      display: none;
    }

    &--more {
      padding: 0;
      background: none;
    }
  }

  &.is-expanded &__chip.is-extra {
    display: inline-flex;
  }

  &__more {
    display: inline-flex;
    align-items: center;
    height: 36px;
    padding: 10px 15px;
    background-color: #f4f4f4;
    font-family: $st-font;
    font-size: 12px;
    line-height: normal;
    color: #000;
    cursor: pointer;

    &:hover,
    &:focus-visible {
      background-color: #e6e6e6;
    }

    &:focus-visible {
      outline: 2px solid #000;
      outline-offset: 2px;
    }
  }

  &__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 15px;
    padding-top: 15px;
    border-top: 1px solid $st-line;
  }

  &__phone,
  &__link {
    font-family: $st-font;
    font-size: 13px;
    line-height: normal;
    color: #000;
    text-decoration: none;
  }

  &__link {
    font-weight: $font-700;
    text-decoration: underline;
    text-underline-position: from-font;
  }
}

// -- map — Figma 384:2843 -----------------------------------------------------------
.stockists-map {
  position: relative;
  height: 724px;
  overflow: hidden;
  background-color: #f4f4f4;

  &__canvas {
    position: absolute;
    inset: 0;
  }

  &__placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f4f4f4;
  }

  &__hint {
    max-width: 320px;
    margin: 0;
    padding: 15px 20px;
    background-color: color(white);
    font-family: $st-font;
    font-size: 13px;
    line-height: 20px;
    color: $st-muted;
    text-align: center;
  }

  &__zoom {
    position: absolute;
    top: 20px;
    left: 20px;
    z-index: 2;
    display: flex;
    flex-direction: column;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
  }

  &__zoom-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    padding: 0;
    background-color: color(white);
    font-family: $st-font;
    font-size: 20px;
    line-height: 1;
    color: #000;
    cursor: pointer;

    & + & {
      border-top: 1px solid $st-line;
    }

    &:hover,
    &:focus-visible {
      background-color: #f4f4f4;
    }

    &:focus-visible {
      outline: 2px solid #000;
      outline-offset: -2px;
    }
  }
}

// -- responsive --------------------------------------------------------------------
@media (max-width: $xl - 1px) {
  .stockists {
    &__inner {
      padding: 0 25px;
    }

    &__list-col {
      flex-basis: 480px;
      max-width: 480px;
    }

    &__toolbar {
      width: auto;
    }
  }

  .stockists-card {
    width: auto;
  }
}

@media (max-width: $md - 1px) {
  .stockists {
    padding-top: 40px;

    &__head {
      flex-direction: column;
      align-items: flex-start;
      gap: 20px;
      padding-bottom: 35px;
    }

    &__title {
      font-size: 44px;
      line-height: 48px;
      letter-spacing: -0.9px;
    }

    &__intro {
      flex-basis: auto;
      max-width: 560px;
    }

    &__results {
      flex-direction: column;
    }

    &__list-col {
      flex-basis: auto;
      width: 100%;
      max-width: none;
    }

    &__map-col {
      width: 100%;
      order: -1;
    }

    &__list {
      max-height: none;
      padding-right: 0;
      overflow: visible;

      &::after {
        content: none;
      }
    }

    &__cards {
      padding-bottom: 0;
    }
  }

  .stockists-map {
    height: 420px;
  }

  .stockists-controls {
    margin-top: 30px;
  }
}

@media (max-width: $sm - 1px) {
  .stockists {
    padding-top: 30px;

    &__title {
      font-size: 36px;
      line-height: 40px;
      letter-spacing: -0.7px;
    }
  }

  .stockists-controls {
    &__region,
    &__search,
    &__country,
    &__product,
    &__select-wrap,
    &__select {
      width: 100%;
    }

    &__toggle {
      flex: 1 1 0;
    }
  }

  .stockists-card {
    padding: 16px;

    &__head {
      flex-direction: column;
      gap: 12px;
    }

    &__map {
      width: 100%;
    }
  }

  .stockists-map {
    height: 320px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .stockists-controls__toggle,
  .stockists-card,
  .stockists-card__map {
    transition: none;
  }
}
```

- [ ] **Step 11: Import (`@import "components/shop/s.stockists";` in the `//Shop` block), compile, run the test**

Run: `tests/bin/wpx eval-file tests/stockists-page.test.php` → `Success: 37 assertions passed` (the `assets/js/stockists.js` file does not exist yet — the enqueue still prints the tag; Task 5 adds the file). Then `tests/bin/wpx eval-file tests/stockists-geocode.test.php` (still green after the `gt_geocoding_key()` switch — it sets the maps key and leaves the geocoding key empty) and `tests/run.sh`.

- [ ] **Step 12: Look at it** — screenshot `/find-a-stockist/` at 1440×1500 and compare with Figma 384:2719 (spec tokens in Global Constraints): head row with rule, controls row, 562/758 split, cards with chips and the black "Show on map" hidden (no key), grey map placeholder, trade band. Fix SCSS drift.

- [ ] **Step 13: Commit**

```bash
git add gt_system/themes/growth_tech/inc/stockists.php gt_system/themes/growth_tech/acf-json/theme-settings-shop.json gt_system/themes/growth_tech/page-templates/page-stockists.php gt_system/themes/growth_tech/template-parts/stockists gt_system/themes/growth_tech/assets/images/icons/pin.svg gt_system/themes/growth_tech/assets/sass/components/shop/_s.stockists.scss gt_system/themes/growth_tech/assets/sass/components/shop/_s.knowledge.scss gt_system/themes/growth_tech/assets/sass/main.scss gt_system/themes/growth_tech/assets/css/main.css gt_system/themes/growth_tech/assets/css/main.css.map tests/stockists-page.test.php
git commit -m "Add the Find a Stockist page template with controls, cards, map placeholder and trade band"
```

---

### Task 5: Finder behaviour — filters, sort, chips, URL sync, map

**Files:**
- Create: `assets/js/stockists.js`
- Create: `tests/stockists-js.test.php`

**Interfaces:**
- Consumes Task 4's hooks and `gtStockists` (`geocodeUrl`, `hasKey`, `pin`, `strings`). Global `window.gtStockistsMapReady` is the Maps callback.
- Behaviour: region toggle (UK = country GB; International = everything else, with the country select), text search (substring on `data-search`; with a key, debounced geocode via the proxy sets an origin → "Nearest first" becomes available and, when the substring matches nothing, all region cards show sorted by distance with a note), product filter (`data-products` contains id), brand preselect from `data-brand` (`data-brands` contains slug; cleared when the user picks a product), sort A–Z / nearest (haversine), count/empty/note, URL `replaceState` (`region`, `product`, `q`), "+N more" toggles `.is-expanded` and its label, "Show on map" pans/zooms the map, opens an info window and marks the card `is-active`; map: greyscale style, custom pin, one marker per card with coords, visibility follows the filters, `fitBounds` on visible markers, +/− zoom.

- [ ] **Step 1: Write the failing test**

`tests/stockists-js.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

$js_path = get_template_directory() . '/assets/js/stockists.js';
gt_assert( file_exists( $js_path ), 'stockists.js exists' );
$js = file_get_contents( $js_path );
foreach ( array( 'data-stockists', 'data-stockists-region', 'data-stockists-country', 'data-stockists-search', 'data-stockists-product', 'data-stockists-sort', 'data-stockists-count', 'data-stockists-cards', 'data-stockists-empty', 'data-stockists-note', 'data-card-map', 'data-card-more', 'data-stockists-canvas', 'data-map-zoom', 'gtStockistsMapReady', 'replaceState', 'haversine', 'fitBounds', 'geocodeUrl' ) as $needle ) {
	gt_assert_contains( $needle, $js, "script handles {$needle}" );
}
$html = gt_fetch( '/find-a-stockist/' );
gt_assert_contains( 'var gtStockists = ', $html, 'settings localised' );
gt_assert_contains( '"hasKey":false', $html, 'hasKey false without a key' );
gt_assert_contains( '"geocodeUrl":"', $html, 'geocode url present' );
gt_assert_not_contains( 'assets/js/stockists.js', gt_fetch( '/shop/' ), 'not enqueued elsewhere' );

gt_test_done();
```

Run → FAIL (file missing).

- [ ] **Step 2: `assets/js/stockists.js`**

```js
/**
 * Stockist finder. The list is server-rendered; this filters, sorts and
 * reorders the cards from their data attributes, and drives the Google Map
 * when a key is set (window.gtStockistsMapReady is the Maps callback).
 */
(function () {
	'use strict';

	var settings = window.gtStockists || {};
	var root = document.querySelector('[data-stockists]');
	if (!root) { return; }

	var list = root.querySelector('[data-stockists-cards]');
	var cards = Array.prototype.slice.call(list.querySelectorAll('.stockists-card')).map(function (el) {
		return {
			el: el,
			id: el.getAttribute('data-id'),
			name: el.getAttribute('data-name') || '',
			country: el.getAttribute('data-country') || '',
			lat: parseFloat(el.getAttribute('data-lat')),
			lng: parseFloat(el.getAttribute('data-lng')),
			products: (el.getAttribute('data-products') || '').split(',').filter(Boolean),
			brands: (el.getAttribute('data-brands') || '').split(',').filter(Boolean),
			search: el.getAttribute('data-search') || '',
			marker: null,
			distance: null
		};
	});

	var ui = {
		regionButtons: Array.prototype.slice.call(root.querySelectorAll('[data-stockists-region] [data-region]')),
		countryWrap: root.querySelector('[data-stockists-country-wrap]'),
		country: root.querySelector('[data-stockists-country]'),
		search: root.querySelector('[data-stockists-search]'),
		product: root.querySelector('[data-stockists-product]'),
		sort: root.querySelector('[data-stockists-sort]'),
		count: root.querySelector('[data-stockists-count]'),
		empty: root.querySelector('[data-stockists-empty]'),
		note: root.querySelector('[data-stockists-note]'),
		canvas: root.querySelector('[data-stockists-canvas]')
	};

	var state = {
		region: root.getAttribute('data-region') === 'international' ? 'international' : 'uk',
		country: '',
		q: ui.search ? ui.search.value.trim() : '',
		product: ui.product ? ui.product.value : '',
		brand: root.getAttribute('data-brand') || '',
		sort: ui.sort ? ui.sort.value : 'az',
		origin: null,
		originLabel: ''
	};

	var map = null;
	var info = null;
	var geocodeTimer = null;
	var geocodeSeq = 0;

	function sprintf(format, value) { return String(format).replace('%d', value).replace('%s', value); }

	function haversine(a, b) {
		var R = 6371;
		var dLat = (b.lat - a.lat) * Math.PI / 180;
		var dLng = (b.lng - a.lng) * Math.PI / 180;
		var s = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
			Math.cos(a.lat * Math.PI / 180) * Math.cos(b.lat * Math.PI / 180) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
		return 2 * R * Math.asin(Math.sqrt(s));
	}

	function inRegion(card) {
		if (state.region === 'uk') { return card.country === 'GB'; }
		if (card.country === 'GB') { return false; }
		return !state.country || card.country === state.country;
	}

	function matchesFilters(card) {
		if (state.product && card.products.indexOf(state.product) === -1) { return false; }
		if (!state.product && state.brand && card.brands.indexOf(state.brand) === -1) { return false; }
		return true;
	}

	function apply() {
		var q = state.q.toLowerCase();
		var pool = cards.filter(function (c) { return inRegion(c) && matchesFilters(c); });
		var visible = q ? pool.filter(function (c) { return c.search.indexOf(q) !== -1; }) : pool;
		var noteText = '';

		if (state.origin) {
			pool.forEach(function (c) {
				c.distance = isNaN(c.lat) ? null : haversine(state.origin, { lat: c.lat, lng: c.lng });
			});
			// A place name that matches no store still deserves an answer.
			if (q && !visible.length) {
				visible = pool;
				noteText = sprintf(settings.strings.nearest || 'Showing stockists nearest to %s', state.originLabel || state.q);
			}
		} else {
			pool.forEach(function (c) { c.distance = null; });
		}

		var sortBy = state.sort === 'nearest' && state.origin ? 'nearest' : 'az';
		visible.sort(function (a, b) {
			if (sortBy === 'nearest') {
				var da = a.distance === null ? Infinity : a.distance;
				var db = b.distance === null ? Infinity : b.distance;
				if (da !== db) { return da - db; }
			}
			return a.name.localeCompare(b.name);
		});

		cards.forEach(function (c) { c.el.hidden = true; c.el.classList.remove('is-active'); });
		visible.forEach(function (c) { c.el.hidden = false; list.appendChild(c.el); });

		if (ui.count) {
			ui.count.textContent = visible.length === 1 ? (settings.strings.one || '1 stockist') : sprintf(settings.strings.count || '%d stockists', visible.length);
		}
		if (ui.empty) { ui.empty.hidden = visible.length > 0; }
		if (ui.note) { ui.note.textContent = noteText; ui.note.hidden = !noteText; }

		syncUrl();
		syncMarkers(visible);
	}

	function syncUrl() {
		if (!window.history || !window.history.replaceState) { return; }
		var params = new URLSearchParams(window.location.search);
		params.delete('region'); params.delete('product'); params.delete('q'); params.delete('brand');
		if (state.region === 'international') { params.set('region', 'international'); }
		if (state.product) { params.set('product', state.product); } else if (state.brand) { params.set('brand', state.brand); }
		if (state.q) { params.set('q', state.q); }
		var qs = params.toString();
		window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
	}

	function setRegion(region) {
		state.region = region;
		ui.regionButtons.forEach(function (btn) {
			var on = btn.getAttribute('data-region') === region;
			btn.classList.toggle('is-active', on);
			btn.setAttribute('aria-pressed', on ? 'true' : 'false');
		});
		if (ui.countryWrap) { ui.countryWrap.classList.toggle('is-hidden', region !== 'international'); }
		if (region !== 'international') { state.country = ''; if (ui.country) { ui.country.value = ''; } }
		apply();
	}

	// -- geocoding (only with a key) ----------------------------------------------
	function geocode(query) {
		if (!settings.hasKey || !settings.geocodeUrl || !window.fetch) { return; }
		var seq = ++geocodeSeq;
		var url = settings.geocodeUrl + (settings.geocodeUrl.indexOf('?') === -1 ? '?' : '&') +
			'q=' + encodeURIComponent(query) + '&region=' + (state.region === 'uk' ? 'gb' : '');
		fetch(url, { credentials: 'same-origin' })
			.then(function (r) { return r.ok ? r.json() : null; })
			.then(function (data) {
				if (seq !== geocodeSeq) { return; }
				if (data && typeof data.lat === 'number') {
					state.origin = { lat: data.lat, lng: data.lng };
					state.originLabel = data.label || query;
					if (ui.sort) {
						ui.sort.querySelector('option[value="nearest"]').disabled = false;
						ui.sort.value = 'nearest';
						state.sort = 'nearest';
					}
				} else {
					state.origin = null;
				}
				apply();
			})
			.catch(function () { state.origin = null; apply(); });
	}

	// -- map --------------------------------------------------------------------------
	function syncMarkers(visible) {
		if (!map || !window.google) { return; }
		var bounds = new google.maps.LatLngBounds();
		var any = false;
		cards.forEach(function (c) {
			if (!c.marker) { return; }
			var show = visible.indexOf(c) !== -1;
			c.marker.setMap(show ? map : null);
			if (show) { bounds.extend(c.marker.getPosition()); any = true; }
		});
		if (any) {
			map.fitBounds(bounds, 60);
			if (visible.length === 1) { map.setZoom(Math.min(map.getZoom(), 12)); }
		}
	}

	function showOnMap(card) {
		if (!map || !card.marker) { return; }
		map.panTo(card.marker.getPosition());
		map.setZoom(Math.max(map.getZoom(), 12));
		if (info) { info.close(); }
		info = new google.maps.InfoWindow({ content: '<strong>' + card.name.replace(/</g, '&lt;') + '</strong>' });
		info.open({ map: map, anchor: card.marker });
		cards.forEach(function (c) { c.el.classList.toggle('is-active', c === card); });
		if (window.innerWidth < 1024 && ui.canvas) { ui.canvas.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
	}

	window.gtStockistsMapReady = function () {
		if (!ui.canvas || !window.google || !google.maps) { return; }
		map = new google.maps.Map(ui.canvas, {
			center: { lat: 54.5, lng: -3 },
			zoom: 6,
			disableDefaultUI: true,
			gestureHandling: 'cooperative',
			styles: [
				{ elementType: 'all', stylers: [{ saturation: -100 }, { lightness: 15 }] },
				{ featureType: 'poi', stylers: [{ visibility: 'off' }] },
				{ featureType: 'transit', stylers: [{ visibility: 'off' }] },
				{ featureType: 'road', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] }
			]
		});
		cards.forEach(function (c) {
			if (isNaN(c.lat) || isNaN(c.lng)) { return; }
			c.marker = new google.maps.Marker({
				position: { lat: c.lat, lng: c.lng },
				title: c.name,
				icon: { url: settings.pin, scaledSize: new google.maps.Size(18, 24.75), anchor: new google.maps.Point(9, 24.75) }
			});
			c.marker.addListener('click', function () { showOnMap(c); c.el.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); });
		});
		apply();
	};

	// -- events ---------------------------------------------------------------------
	root.addEventListener('click', function (event) {
		var region = event.target.closest('[data-region]');
		if (region) { setRegion(region.getAttribute('data-region')); return; }

		var more = event.target.closest('[data-card-more]');
		if (more) {
			var card = more.closest('.stockists-card');
			var expanded = card.classList.toggle('is-expanded');
			more.setAttribute('aria-expanded', expanded ? 'true' : 'false');
			more.textContent = expanded ? (settings.strings.showLess || 'Show less') : sprintf(settings.strings.more || '+%d more', more.getAttribute('data-count'));
			return;
		}

		var zoom = event.target.closest('[data-map-zoom]');
		if (zoom && map) { map.setZoom(map.getZoom() + (zoom.getAttribute('data-map-zoom') === 'in' ? 1 : -1)); return; }

		var show = event.target.closest('[data-card-map]');
		if (show) {
			var el = show.closest('.stockists-card');
			var target = cards.filter(function (c) { return c.el === el; })[0];
			if (target) { showOnMap(target); }
		}
	});

	if (ui.country) { ui.country.addEventListener('change', function () { state.country = ui.country.value; apply(); }); }
	if (ui.product) {
		ui.product.addEventListener('change', function () {
			state.product = ui.product.value;
			state.brand = ''; // An explicit product choice replaces the brand preselect.
			apply();
		});
	}
	if (ui.sort) { ui.sort.addEventListener('change', function () { state.sort = ui.sort.value; apply(); }); }
	if (ui.search) {
		ui.search.addEventListener('input', function () {
			state.q = ui.search.value.trim();
			clearTimeout(geocodeTimer);
			if (!state.q) {
				state.origin = null;
				if (ui.sort && ui.sort.value === 'nearest') { ui.sort.value = 'az'; state.sort = 'az'; }
				if (ui.sort && !settings.hasKey) { ui.sort.querySelector('option[value="nearest"]').disabled = true; }
			}
			apply();
			if (state.q && settings.hasKey) { geocodeTimer = setTimeout(function () { geocode(state.q); }, 450); }
		});
		ui.search.closest('form') && ui.search.closest('form').addEventListener('submit', function (e) { e.preventDefault(); });
	}

	apply();
	if (state.q && settings.hasKey) { geocode(state.q); }
})();
```

- [ ] **Step 3: Run the test** → `Success: 24 assertions passed`; `tests/run.sh` green.

- [ ] **Step 4: Browser check (puppeteer-core, `stockists-check.js`)** at 1440 on `/find-a-stockist/` (no key):
1. Count reads "6 stockists"; two cards hidden; International/country wrap hidden; "Show on map" buttons not visible (computed display none); sort "nearest" option disabled.
2. Click International → count "2 stockists", Dublin + Amsterdam visible, country select visible with IE/NL; pick NL → "1 stockist"; URL has `region=international`.
3. Back to UK → 6; choose "Clonex Mist" in the product select → "3 stockists" (Somerset, Bristol, Urban Roots), URL has `product=`.
4. Reset product; type "Exeter" → "1 stockist" (Exe Valley), URL has `q=Exeter`; type "zzz" → "0 stockists"/empty message shown; clear → 6.
5. Somerset "+4 more" → seven chips visible, label "Show less", aria-expanded true; click again → collapsed.
6. Load `/find-a-stockist/?product={GT-001 id}&region=uk` → select preselected, count 3; `/find-a-stockist/?brand=ionic` → count 3 (Somerset, Bristol, Manchester); then choosing "Stocking any product" → 6 (brand preselect cleared).
7. Sort select A–Z order check: first visible card is "Bristol Grow Room".
Paste the output; fix real JS defects.

- [ ] **Step 5: Commit**

```bash
git add gt_system/themes/growth_tech/assets/js/stockists.js tests/stockists-js.test.php
git commit -m "Add stockist finder filtering, sorting, chips and map behaviour"
```

---

### Task 6: Verification, docs, key hand-off

**Files:**
- Modify: `tests/shots.sh` (add `/find-a-stockist/`), `tests/README.md`

- [ ] **Step 1:** Add `shot stockists "https://growth-tech.local/find-a-stockist/" "$w" 1800` to `tests/shots.sh`; capture 1440/1024/768 and a puppeteer 390 capture; check: head row (title 743 / intro 356 bottom-aligned), controls row, 562/758 split with the list scroll box and the grey map, cards to the tokens; <1024 map above the list at 420 tall, list unboxed; <768 controls full-width, toggle halves, actions wrap. Bounding-rect overflow scan at 1024/768/390. Fix SCSS drift.

- [ ] **Step 2:** Journey (puppeteer): `/product/clonex-mist/` → click "Find a local stockist" → lands on `/find-a-stockist/?product={id}&region=uk` with the product preselected and "3 stockists"; `/brand/clonex/` → "Find a stockist" → `?brand=clonex` → count of stockists carrying any Clonex product (Somerset, Bristol, Exe Valley, Urban Roots = 4).

- [ ] **Step 3: README.** Add a "Stockist finder" section to `tests/README.md`: seed order (`seed-shop.php` → `seed-product-content.php` → `seed-stockists.php`); Google Maps Platform setup — enable **Maps JavaScript API** and **Geocoding API**; create a browser key restricted by HTTP referrer (`growth-tech.local/*`, the production domain) → Theme Settings → Shop Settings → "Google Maps API key"; create a second key restricted by IP (the server's egress IP) or unrestricted with quota → "Google Geocoding key (server)" (optional; the maps key is used otherwise, which fails if it is referrer-restricted); geocoding happens on stockist save and results are cached 30 days (`gt_geocode_*` transients); re-save a stockist (or clear `geocoded_address`) to re-run it; the map + "Nearest first" activate automatically once the key is set; `tests/stockists-geocode.test.php` stubs Google and needs no key.

- [ ] **Step 4:** `tests/run.sh` — all suites green. Commit:

```bash
git add tests/shots.sh tests/README.md
git commit -m "Document the stockist finder setup and extend the screenshot script"
```

---

## Self-review notes

- Spec §4.5 → Tasks 1–3 (CPT, taxonomy, fields, geocode on save, admin column). §9 → Tasks 4–5 (head, controls incl. UK/International + country select, search, product select preselected from `?product=`, count, sort nearest/A–Z, cards with chips/+N more/phone/directions/check stock/show on map, map with greyscale style/custom pin/zoom/fit bounds, `?brand=` prefilter, trade band). §10 → labels/roles/aria-pressed/live count/focus rings. §11 → Task 6.
- Deviations from the spec, all deliberate: no `gt/v1/stockists` REST endpoint — the server renders every card with data attributes and the JS filters the DOM (no duplicate card template, works without JS); an optional second (server) Geocoding key because referrer-restricted browser keys are rejected by the Geocoding web service; "Nearest first" needs a geocoded search (no browser-geolocation button — not in the design).
- Names used across tasks: `gt_maps_key`, `gt_geocoding_key`, `gt_stockist_geocode`, `gt_stockist_maybe_geocode`, `gt_stockists_all`, `gt_stockist_product_options`, `gt_stockist_countries`, `gt_stockist_countries_present`, `gt_stockist_preselect`, `gt_stockist_is_visible`, `gt_stockist_address_string`; data hooks listed in Task 4's Interfaces ↔ Task 5's JS.
