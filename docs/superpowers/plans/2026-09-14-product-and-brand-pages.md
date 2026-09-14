# Product & Brand Pages (Plan 2 of 3) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship the single product page (Figma 384:1816) and the brand landing page (Figma 384:1489) on top of Plan 1's shop foundation — gallery, enquiry CTAs, tabs, "Complete the system", knowledge band; brand hero, science/steps slider + FAQ, range grid, cross-sell band, guides band.

**Architecture:** WooCommerce template overrides `woocommerce/single-product.php` and `woocommerce/taxonomy-product_brand.php`, composed from small parts under `template-parts/shop/` and `template-parts/brand/`. Product-specific content lives in an ACF "Product Details" group on the product plus a `product_badge` taxonomy; brand-landing content lives on the `product_brand` term (extending Plan 1's brand field group). Plan 1's helpers, card template, breadcrumb part, selection layer and test harness are reused; `GT_SHOP_ENQUIRY_MODE` keeps gating the cart slots. The two sliders reuse the theme's existing `gt-block-slider` script; the gallery and tabs get small dedicated scripts.

**Tech Stack:** WordPress classic theme, WooCommerce 11.1, ACF Pro 6.8 (JSON), Slick (already bundled), SCSS via `npx sass`, vanilla JS + jQuery (slick needs it), WP-CLI tests via `tests/bin/wpx`, headless Chrome + puppeteer-core for checks.

**Spec:** `docs/superpowers/specs/2026-09-11-woocommerce-shop-design.md` — §4.2 (product ACF), §4.3 (brand ACF landing fields), §4.4 (knowledge defaults), §7 (brand landing), §8 (single product + enquiry association + Phase Two), §10, §11. Plan 1 (`docs/superpowers/plans/2026-09-11-shop-listing.md`) is merged; Plan 3 (stockist finder) follows.

## Global Constraints

- Theme root: `gt_system/themes/growth_tech` — **theme paths below are relative to it**; `tests/` and `docs/` are repo-root paths. Work from the repo root; branch from `main`.
- Local site `https://growth-tech.local` (MAMP, self-signed → `curl -k`; servers must be running). WP-CLI only via `tests/bin/wpx`. `tests/run.sh` runs all `tests/*.test.php`. Helpers: `gt_assert`, `gt_assert_equal`, `gt_assert_contains`, `gt_assert_not_contains`, `gt_fetch($path)`, `gt_test_done`. See `tests/README.md`.
- Compile CSS after every SCSS change from the theme root: `npx sass assets/sass/main.scss assets/css/main.css --style=compressed --source-map` (pre-existing `/`-division deprecation warnings are normal). **Cascade rule learned in Plan 1:** the theme's base `p, li, span, button, label, h1–h6` rules set `font-family: $primary_font` (museo-sans, not loaded) and `font-size: 1rem`, and `_c.forms.scss` styles `form input`/`form select` — every new text element needs an explicit `font-family: "DM Sans", #{$primary_font}` (or Cormorant) and explicit `font-size`/`line-height`; never rely on inheritance through those elements.
- Conventions: BEM; SCSS partials `assets/sass/components/shop/_s.*.scss` imported from `assets/sass/main.scss` under the `//Shop` block; all output escaped; sections with empty data render nothing; `gt_asset_version()` for enqueues; `btn-flat` / `btn-flat--dark` buttons with `gt_arrow_svg()`; `gt_icon_svg('name')` prints an icon from `assets/images/icons/` with `fill="black"` swapped to `currentColor`.
- Plan 1 interfaces reused (do not re-implement): `gt_product_primary_category(WC_Product): ?WP_Term`, `gt_product_sizes(WC_Product): string[]`, `gt_product_brand(WC_Product): ?WP_Term`, `gt_brand_accent(WP_Term): string`, `gt_term_field(name, WP_Term, default)`, `gt_shop_count_text(shown, total)`, `gt_shop_per_page()`, `woocommerce/content-product.php` (`<li class="product-card …">`, fires `woocommerce_after_shop_loop_item_title` / `woocommerce_after_shop_loop_item`), `template-parts/shop/breadcrumb.php` (accepts `$args['items']`), `GT_SHOP_ENQUIRY_MODE`, `gt_shop_own_brand_term_ids()`, `gt_shop_main_query()` on `woocommerce_product_query`, `gt-block-slider` script (registered, `data-block-slider` + `[data-slider-scope]`/`[data-slider-dots]`/`[data-slider-arrows]`).
- Figma tokens (desktop 1440), product page: gutters 50 (content 1340); gallery column 600 (tile 600×600 `rgba(35,31,32,.05)`, image contained; 35px square controls: zoom black/white bottom-left inset 20/25, prev `#F4F4F4`/black + next black/white bottom-right gap 10); thumbs 3 × 187, gap 19, 15 below; summary column 621 at x=669 (gap 69): brand logo 16px tall, gap 10, title Cormorant 700 60/65 −1.2 #000, gap 30, intro DM Sans 15/25 `rgba(0,0,0,.75)`, gap 30, feature list (width 564, gap 15, tick `#FFC400` 13×12 in a 13×22 box, gap 10, text 15 with bold lead + 75% remainder, line-height 25), gap 30, "AVAILABLE SIZES" 12 medium 50% tracking 1.2 + gap 10 + chips (1px #000 border, padding 10/15, 14px; selected = black bg white text; gap 10), gap 30, actions (`btn-flat--dark` + gap 25 + bold 14 underlined link), 35 padding-bottom + 1px `rgba(0,0,0,.25)` rule, gap 35, downloads row (gap 30; icon 14×18 + gap 10 + 14px), gap 26, badges (`#F4F4F4`, padding 10/15, 12px tracking 1.2 uppercase, gap 10). Tabs 90 below: tab 15 medium, padding 10/5, active #000 with 1px #000 bottom rule, inactive `rgba(0,0,0,.25)` text and rule, 25px rule spacers, gap 50 to panel, panel padding 0 25, two 550 columns space-between, heading Cormorant 35 −0.7, gap 20, text DM Sans 300 15/25 75%. "Complete the system" 77 below: full-bleed `#F4F4F4`, padding 60/74, header (title Cormorant 35 −0.7; right: "View all Products" 14 medium underlined + gap 15 + arrows 35 gap 10) gap 25, 4 columns gap 20. Knowledge band 77 below: 1340×300, padding 30/50, black with image right and gradient `#000 40.865% → transparent 82.692%`, title Cormorant 60 −1.2 white, gap 10, text 15/25 `rgba(255,255,255,.75)` width 622, gap 25, white `btn-flat`. Footer 60 below.
- Figma tokens, brand page: hero full-bleed 1440×500 black directly under the header, image cover on the right, content 732 wide at x=50 y=116: logo 150×27, gap 20, headline Cormorant 60/65 −1.2 white with accent second line, gap 20, intro 15/25 white, gap 20, actions (white `btn-flat` "Find a stockist" + gap 25 + bold 14 underlined white link). Science section 72 below hero: left column 429 at x=160 (steps slider: image 429×420 with step badge black p-5 at (19,20) — label 12 medium accent tracking 1.2 with a 3px dot; plus hotspot 30×30 white/black; gap 15; title 18 medium; gap 10; text 15/25 75%; gap 25; "View Product" bold 14 underlined; controls 47 below: 15px square progress with 80×3 active bar left, 35px arrows right), gap 135, right column 616 (heading 60/65 −1.2 with accent second line, gap 40, body 15/25 75%; FAQ box 59 below: black p-30, eyebrow 12 medium `rgba(255,255,255,.5)` tracking 1.2, gap 15, question Cormorant 30 −0.6 accent, gap 15, answer 15/25 `rgba(255,255,255,.75)`, gap 30, bold 14 white underlined link). Range 90 below: title Cormorant 35 −0.7 + "Showing N of N products" 12 grey bottom-right, gap 25, 4 × 320 grid gap 20. Pair band 58 below: 1340×289 black, padding 30/50, image cover right (from 42.6%), logo 125×38, gap 10, title Cormorant 30 −0.6 white, text 15/25 `rgba(255,255,255,.75)` width 622, gap 25, white `btn-flat`. Guides band 59 below: full-bleed `#F4F4F4` padding 81/50, left 555 (heading 60/65 accent 2nd line, gap 40, text 15/25 75%, gap 40, white `btn-flat` + gap 25 + bold 14 underlined link), right two cards 347.33×402 gap 25 (image cover, bottom panel `rgba(255,255,255,.9)` p-20 gap 20: 18px text with 900-weight lead + 400 remainder, bold 14 underlined "Read the Guide").
- Accent colour is the brand's `accent_colour` (Clonex `#FBC707`; the product-page tick and the science/guides accent in Figma are `#FFC400` — use the brand accent everywhere for consistency, via CSS var `--brand-accent`).
- Enquiry association: stockist CTA → `{stockist page}?product={id}&region=uk`; "Ask our experts" → `{shop_experts_link.url}?product={id}`; brand hero stockist → `{stockist page}?brand={slug}`.
- Commit after each task; keep whatever Co-Authored-By trailer the environment adds. Stage only the files the task names (the working tree may hold unrelated uncommitted homepage-block files — never stage those; main.scss/main.css/.map hunks from them may ride along, by standing ruling).

---

### Task 1: ACF field groups — product details, badges taxonomy, brand landing fields, knowledge defaults

**Files:**
- Create: `acf-json/product-details.json`
- Modify: `acf-json/brand.json` (append a "Landing page" tab + fields)
- Modify: `acf-json/theme-settings-shop.json` (append knowledge-band defaults)
- Modify: `acf-json/page-settings.json` (add `post_type == product` location)
- Modify: `inc/taxonomies.php` (register `product_badge`)
- Create: `tests/product-acf.test.php`

**Interfaces:**
- Produces (product, group `group_gt_product_details`): `features` repeater (`lead`, `text`), `downloads` repeater (`label`, `file` id), `science` repeater (`heading`, `text` wysiwyg), `how_to_use` wysiwyg, `specification` repeater (`label`, `value`), `documents` repeater (`label`, `file` id), `knowledge_override` group (`heading`, `text`, `image` id, `link` array). Keys `field_gt_pd_*` as listed in Step 2.
- Produces taxonomy `product_badge` (non-hierarchical, on `product`, admin UI, no front-end archive).
- Produces (brand term, appended to `group_gt_brand`): `hero_heading`, `hero_accent_line`, `hero_intro`, `hero_image`, `science_heading`, `science_accent_line`, `science_text`, `steps` repeater (`label`, `image`, `product` post object id, `text`, `hotspot_x`, `hotspot_y`), `faq` group (`eyebrow`, `question`, `answer`, `link`), `pair_brand` (term id), `pair_heading`, `pair_text`, `pair_image`, `pair_link`, `guides_heading`, `guides_accent_line`, `guides_text`, `guides_cta_1`, `guides_cta_2`, `guides` repeater (`image`, `lead`, `title`, `link`). Keys `field_gt_brand_lp_*` as listed in Step 3.
- Produces (options): `shop_knowledge_heading`, `shop_knowledge_text`, `shop_knowledge_image`, `shop_knowledge_link` (keys `field_gt_shop_knowledge_*`).
- `hide_join_club` now applies to products (existing `gt_show_join_club()` reads it on any singular).

- [ ] **Step 1: Write the failing test**

`tests/product-acf.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

gt_assert( ! empty( acf_get_field_group( 'group_gt_product_details' ) ), 'product details group registered' );
$pd = wp_list_pluck( acf_get_fields( 'group_gt_product_details' ) ?: array(), 'name' );
foreach ( array( 'features', 'downloads', 'science', 'how_to_use', 'specification', 'documents', 'knowledge_override' ) as $name ) {
	gt_assert( in_array( $name, $pd, true ), "product details has {$name}" );
}

$brand = wp_list_pluck( acf_get_fields( 'group_gt_brand' ) ?: array(), 'name' );
foreach ( array( 'hero_heading', 'hero_accent_line', 'hero_intro', 'hero_image', 'science_heading', 'science_accent_line', 'science_text', 'steps', 'faq', 'pair_brand', 'pair_heading', 'pair_text', 'pair_image', 'pair_link', 'guides_heading', 'guides_accent_line', 'guides_text', 'guides_cta_1', 'guides_cta_2', 'guides' ) as $name ) {
	gt_assert( in_array( $name, $brand, true ), "brand group has {$name}" );
}

$shop = wp_list_pluck( acf_get_fields( 'group_gt_shop_settings' ) ?: array(), 'name' );
foreach ( array( 'shop_knowledge_heading', 'shop_knowledge_text', 'shop_knowledge_image', 'shop_knowledge_link' ) as $name ) {
	gt_assert( in_array( $name, $shop, true ), "shop settings has {$name}" );
}

gt_assert( taxonomy_exists( 'product_badge' ), 'product_badge taxonomy registered' );
gt_assert( in_array( 'product', (array) get_taxonomy( 'product_badge' )->object_type, true ), 'product_badge attaches to products' );
gt_assert_equal( false, get_taxonomy( 'product_badge' )->publicly_queryable, 'product_badge has no front-end archive' );

$locations = acf_get_field_group( 'group_gt_page_settings' )['location'];
$has_product = false;
foreach ( $locations as $group ) {
	foreach ( $group as $rule ) {
		if ( 'post_type' === $rule['param'] && 'product' === $rule['value'] ) {
			$has_product = true;
		}
	}
}
gt_assert( $has_product, 'hide_join_club is available on products' );

gt_test_done();
```

Run: `tests/bin/wpx eval-file tests/product-acf.test.php`
Expected: FAIL — "product details group registered" fails first.

- [ ] **Step 2: Create `acf-json/product-details.json`**

```json
{
    "key": "group_gt_product_details",
    "title": "Product Details",
    "fields": [
        {
            "key": "field_gt_pd_tab_summary",
            "label": "Summary",
            "name": "",
            "type": "tab",
            "placement": "top"
        },
        {
            "key": "field_gt_pd_features",
            "label": "Feature bullets",
            "name": "features",
            "type": "repeater",
            "instructions": "The tick list under the intro. Lead is shown bold, then the text.",
            "layout": "table",
            "button_label": "Add feature",
            "sub_fields": [
                { "key": "field_gt_pd_feature_lead", "label": "Lead", "name": "lead", "type": "text", "placeholder": "Direct foliar absorption", "wrapper": { "width": "35", "class": "", "id": "" } },
                { "key": "field_gt_pd_feature_text", "label": "Text", "name": "text", "type": "text", "placeholder": "delivers immediate amino acids and vital minerals directly to foliage", "wrapper": { "width": "65", "class": "", "id": "" } }
            ]
        },
        {
            "key": "field_gt_pd_downloads",
            "label": "Downloads",
            "name": "downloads",
            "type": "repeater",
            "instructions": "PDF links shown under the buttons, e.g. Safety Data Sheet (PDF).",
            "layout": "table",
            "button_label": "Add download",
            "sub_fields": [
                { "key": "field_gt_pd_download_label", "label": "Label", "name": "label", "type": "text", "placeholder": "Safety Data Sheet (PDF)", "wrapper": { "width": "50", "class": "", "id": "" } },
                { "key": "field_gt_pd_download_file", "label": "File", "name": "file", "type": "file", "return_format": "id", "library": "all", "mime_types": "pdf", "wrapper": { "width": "50", "class": "", "id": "" } }
            ]
        },
        {
            "key": "field_gt_pd_tab_science",
            "label": "The science",
            "name": "",
            "type": "tab",
            "placement": "top"
        },
        {
            "key": "field_gt_pd_science",
            "label": "The science",
            "name": "science",
            "type": "repeater",
            "instructions": "Shown two columns wide in the first tab. Leave empty to hide the tab.",
            "layout": "block",
            "button_label": "Add column",
            "sub_fields": [
                { "key": "field_gt_pd_science_heading", "label": "Heading", "name": "heading", "type": "text" },
                { "key": "field_gt_pd_science_text", "label": "Text", "name": "text", "type": "wysiwyg", "tabs": "visual", "toolbar": "basic", "media_upload": 0 }
            ]
        },
        {
            "key": "field_gt_pd_tab_use",
            "label": "How to use",
            "name": "",
            "type": "tab",
            "placement": "top"
        },
        {
            "key": "field_gt_pd_how_to_use",
            "label": "How to use",
            "name": "how_to_use",
            "type": "wysiwyg",
            "instructions": "Leave empty to hide the tab.",
            "tabs": "visual",
            "toolbar": "basic",
            "media_upload": 0
        },
        {
            "key": "field_gt_pd_tab_spec",
            "label": "Specification",
            "name": "",
            "type": "tab",
            "placement": "top"
        },
        {
            "key": "field_gt_pd_specification",
            "label": "Specification",
            "name": "specification",
            "type": "repeater",
            "instructions": "Label / value pairs. Leave empty to hide the tab.",
            "layout": "table",
            "button_label": "Add row",
            "sub_fields": [
                { "key": "field_gt_pd_spec_label", "label": "Label", "name": "label", "type": "text", "wrapper": { "width": "40", "class": "", "id": "" } },
                { "key": "field_gt_pd_spec_value", "label": "Value", "name": "value", "type": "text", "wrapper": { "width": "60", "class": "", "id": "" } }
            ]
        },
        {
            "key": "field_gt_pd_tab_docs",
            "label": "Useful documents",
            "name": "",
            "type": "tab",
            "placement": "top"
        },
        {
            "key": "field_gt_pd_documents",
            "label": "Useful documents",
            "name": "documents",
            "type": "repeater",
            "instructions": "Leave empty to hide the tab.",
            "layout": "table",
            "button_label": "Add document",
            "sub_fields": [
                { "key": "field_gt_pd_doc_label", "label": "Label", "name": "label", "type": "text", "wrapper": { "width": "50", "class": "", "id": "" } },
                { "key": "field_gt_pd_doc_file", "label": "File", "name": "file", "type": "file", "return_format": "id", "library": "all", "wrapper": { "width": "50", "class": "", "id": "" } }
            ]
        },
        {
            "key": "field_gt_pd_tab_knowledge",
            "label": "Knowledge band",
            "name": "",
            "type": "tab",
            "placement": "top"
        },
        {
            "key": "field_gt_pd_knowledge",
            "label": "Override the \"Better knowledge\" band",
            "name": "knowledge_override",
            "type": "group",
            "instructions": "Leave empty to use the defaults from Theme Settings > Shop Settings.",
            "layout": "block",
            "sub_fields": [
                { "key": "field_gt_pd_knowledge_heading", "label": "Heading", "name": "heading", "type": "text", "wrapper": { "width": "50", "class": "", "id": "" } },
                { "key": "field_gt_pd_knowledge_image", "label": "Image", "name": "image", "type": "image", "return_format": "id", "library": "all", "preview_size": "medium", "wrapper": { "width": "50", "class": "", "id": "" } },
                { "key": "field_gt_pd_knowledge_text", "label": "Text", "name": "text", "type": "textarea", "rows": 3, "new_lines": "" },
                { "key": "field_gt_pd_knowledge_link", "label": "Button", "name": "link", "type": "link", "return_format": "array" }
            ]
        }
    ],
    "location": [ [ { "param": "post_type", "operator": "==", "value": "product" } ] ],
    "menu_order": 5,
    "position": "normal",
    "style": "default",
    "label_placement": "top",
    "instruction_placement": "label",
    "hide_on_screen": "",
    "active": true,
    "description": "Content for the product page beyond WooCommerce's own fields.",
    "show_in_rest": 0
}
```

- [ ] **Step 3: Append the landing-page fields to `acf-json/brand.json`**

Insert the following objects at the end of the `fields` array (after `field_gt_brand_promo_tagline`), and change the group `description` to `"Brand identity, shop promo tile and brand landing page content."`:

```json
        {
            "key": "field_gt_brand_lp_tab_hero",
            "label": "Landing: hero",
            "name": "",
            "type": "tab",
            "placement": "top"
        },
        { "key": "field_gt_brand_lp_hero_heading", "label": "Hero heading", "name": "hero_heading", "type": "text", "placeholder": "The original rooting gel.", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_brand_lp_hero_accent", "label": "Hero accent line", "name": "hero_accent_line", "type": "text", "instructions": "Second line, in the accent colour.", "placeholder": "A complete propagation system.", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_brand_lp_hero_intro", "label": "Hero intro", "name": "hero_intro", "type": "textarea", "rows": 3, "new_lines": "" },
        { "key": "field_gt_brand_lp_hero_image", "label": "Hero image", "name": "hero_image", "type": "image", "instructions": "Product group shot on black, sits on the right of the 1440 x 500 hero.", "return_format": "id", "library": "all", "preview_size": "medium" },
        {
            "key": "field_gt_brand_lp_tab_science",
            "label": "Landing: science",
            "name": "",
            "type": "tab",
            "placement": "top"
        },
        { "key": "field_gt_brand_lp_science_heading", "label": "Heading", "name": "science_heading", "type": "text", "placeholder": "Rooted in science.", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_brand_lp_science_accent", "label": "Accent line", "name": "science_accent_line", "type": "text", "placeholder": "Built for success.", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_brand_lp_science_text", "label": "Text", "name": "science_text", "type": "wysiwyg", "tabs": "visual", "toolbar": "basic", "media_upload": 0 },
        {
            "key": "field_gt_brand_lp_steps",
            "label": "Product steps",
            "name": "steps",
            "type": "repeater",
            "instructions": "One slide per step. The plus marker is positioned as a percentage of the image.",
            "layout": "block",
            "button_label": "Add step",
            "sub_fields": [
                { "key": "field_gt_brand_lp_step_label", "label": "Step label", "name": "label", "type": "text", "placeholder": "Step 2 • Mist", "wrapper": { "width": "50", "class": "", "id": "" } },
                { "key": "field_gt_brand_lp_step_product", "label": "Product", "name": "product", "type": "post_object", "post_type": [ "product" ], "return_format": "id", "allow_null": 1, "ui": 1, "wrapper": { "width": "50", "class": "", "id": "" } },
                { "key": "field_gt_brand_lp_step_image", "label": "Image", "name": "image", "type": "image", "return_format": "id", "library": "all", "preview_size": "medium", "wrapper": { "width": "50", "class": "", "id": "" } },
                { "key": "field_gt_brand_lp_step_hx", "label": "Marker X (%)", "name": "hotspot_x", "type": "number", "default_value": 73, "min": 0, "max": 100, "wrapper": { "width": "25", "class": "", "id": "" } },
                { "key": "field_gt_brand_lp_step_hy", "label": "Marker Y (%)", "name": "hotspot_y", "type": "number", "default_value": 47, "min": 0, "max": 100, "wrapper": { "width": "25", "class": "", "id": "" } },
                { "key": "field_gt_brand_lp_step_text", "label": "Text", "name": "text", "type": "textarea", "rows": 3, "new_lines": "" }
            ]
        },
        {
            "key": "field_gt_brand_lp_faq",
            "label": "FAQ callout",
            "name": "faq",
            "type": "group",
            "layout": "block",
            "sub_fields": [
                { "key": "field_gt_brand_lp_faq_eyebrow", "label": "Eyebrow", "name": "eyebrow", "type": "text", "default_value": "What growers ask us most", "wrapper": { "width": "50", "class": "", "id": "" } },
                { "key": "field_gt_brand_lp_faq_link", "label": "Link", "name": "link", "type": "link", "return_format": "array", "wrapper": { "width": "50", "class": "", "id": "" } },
                { "key": "field_gt_brand_lp_faq_question", "label": "Question", "name": "question", "type": "text", "placeholder": "“Can I dip straight into the bottle?”" },
                { "key": "field_gt_brand_lp_faq_answer", "label": "Answer", "name": "answer", "type": "textarea", "rows": 3, "new_lines": "" }
            ]
        },
        {
            "key": "field_gt_brand_lp_tab_pair",
            "label": "Landing: pair band",
            "name": "",
            "type": "tab",
            "placement": "top"
        },
        { "key": "field_gt_brand_lp_pair_brand", "label": "Paired brand", "name": "pair_brand", "type": "taxonomy", "taxonomy": "product_brand", "field_type": "select", "return_format": "id", "allow_null": 1, "add_term": 0, "save_terms": 0, "load_terms": 0, "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_brand_lp_pair_image", "label": "Image", "name": "pair_image", "type": "image", "return_format": "id", "library": "all", "preview_size": "medium", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_brand_lp_pair_heading", "label": "Heading", "name": "pair_heading", "type": "text", "placeholder": "Pair with Root Riot® for maximum propagation success" },
        { "key": "field_gt_brand_lp_pair_text", "label": "Text", "name": "pair_text", "type": "textarea", "rows": 3, "new_lines": "" },
        { "key": "field_gt_brand_lp_pair_link", "label": "Button", "name": "pair_link", "type": "link", "return_format": "array" },
        {
            "key": "field_gt_brand_lp_tab_guides",
            "label": "Landing: guides",
            "name": "",
            "type": "tab",
            "placement": "top"
        },
        { "key": "field_gt_brand_lp_guides_heading", "label": "Heading", "name": "guides_heading", "type": "text", "placeholder": "Better knowledge.", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_brand_lp_guides_accent", "label": "Accent line", "name": "guides_accent_line", "type": "text", "placeholder": "Stronger roots.", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_brand_lp_guides_text", "label": "Text", "name": "guides_text", "type": "textarea", "rows": 3, "new_lines": "" },
        { "key": "field_gt_brand_lp_guides_cta1", "label": "Button", "name": "guides_cta_1", "type": "link", "return_format": "array", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_brand_lp_guides_cta2", "label": "Text link", "name": "guides_cta_2", "type": "link", "return_format": "array", "wrapper": { "width": "50", "class": "", "id": "" } },
        {
            "key": "field_gt_brand_lp_guides",
            "label": "Guide cards",
            "name": "guides",
            "type": "repeater",
            "instructions": "Two cards in the design.",
            "layout": "block",
            "max": 2,
            "button_label": "Add guide",
            "sub_fields": [
                { "key": "field_gt_brand_lp_guide_image", "label": "Image", "name": "image", "type": "image", "return_format": "id", "library": "all", "preview_size": "medium", "wrapper": { "width": "50", "class": "", "id": "" } },
                { "key": "field_gt_brand_lp_guide_link", "label": "Link", "name": "link", "type": "link", "return_format": "array", "wrapper": { "width": "50", "class": "", "id": "" } },
                { "key": "field_gt_brand_lp_guide_lead", "label": "Lead (bold)", "name": "lead", "type": "text", "placeholder": "From Seed to Sprout:", "wrapper": { "width": "50", "class": "", "id": "" } },
                { "key": "field_gt_brand_lp_guide_title", "label": "Title", "name": "title", "type": "text", "placeholder": "The Beginner’s Guide to Perfect Germination", "wrapper": { "width": "50", "class": "", "id": "" } }
            ]
        }
```

Validate with `python3 -c "import json;json.load(open('gt_system/themes/growth_tech/acf-json/brand.json'))"` (from the repo root).

- [ ] **Step 4: Append knowledge defaults to `acf-json/theme-settings-shop.json`**

Add after `field_gt_shop_maps_key` in the `fields` array:

```json
        {
            "key": "field_gt_shop_knowledge_tab",
            "label": "\"Better knowledge\" band",
            "name": "",
            "type": "tab",
            "placement": "top"
        },
        { "key": "field_gt_shop_knowledge_heading", "label": "Heading", "name": "shop_knowledge_heading", "type": "text", "instructions": "Default for the band above the footer on product pages. Products can override it.", "placeholder": "Better knowledge. Stronger roots.", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_shop_knowledge_image", "label": "Image", "name": "shop_knowledge_image", "type": "image", "return_format": "id", "library": "all", "preview_size": "medium", "wrapper": { "width": "50", "class": "", "id": "" } },
        { "key": "field_gt_shop_knowledge_text", "label": "Text", "name": "shop_knowledge_text", "type": "textarea", "rows": 3, "new_lines": "" },
        { "key": "field_gt_shop_knowledge_link", "label": "Button", "name": "shop_knowledge_link", "type": "link", "return_format": "array" }
```

- [ ] **Step 5: Add products to `acf-json/page-settings.json` location**

Change the `location` array to:

```json
    "location": [
        [ { "param": "post_type", "operator": "==", "value": "page" } ],
        [ { "param": "post_type", "operator": "==", "value": "post" } ],
        [ { "param": "post_type", "operator": "==", "value": "product" } ]
    ],
```

- [ ] **Step 6: Register the badge taxonomy in `inc/taxonomies.php`**

Replace the file's contents with:

```php
<?php
/**
 * Theme taxonomies.
 */

/**
 * Product badges — the small grey pills on the product page ("Registered
 * product", "Independently tested", "Made in Somerset"). Editors add new
 * ones in Products > Badges; there is no front-end archive.
 */
function gt_register_product_badge_taxonomy() {
	register_taxonomy(
		'product_badge',
		'product',
		array(
			'labels'             => array(
				'name'          => __( 'Badges', 'gt' ),
				'singular_name' => __( 'Badge', 'gt' ),
				'menu_name'     => __( 'Badges', 'gt' ),
				'add_new_item'  => __( 'Add New Badge', 'gt' ),
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
add_action( 'init', 'gt_register_product_badge_taxonomy' );
```

- [ ] **Step 7: Run the test**

Run: `tests/bin/wpx eval-file tests/product-acf.test.php`
Expected: `Success: 36 assertions passed`

- [ ] **Step 8: Commit**

```bash
git add gt_system/themes/growth_tech/acf-json/product-details.json gt_system/themes/growth_tech/acf-json/brand.json gt_system/themes/growth_tech/acf-json/theme-settings-shop.json gt_system/themes/growth_tech/acf-json/page-settings.json gt_system/themes/growth_tech/inc/taxonomies.php tests/product-acf.test.php
git commit -m "Add product detail, brand landing and knowledge band fields plus badge taxonomy"
```

---

### Task 2: Seed — product content, placeholder images, brand landing content

**Files:**
- Create: `tests/seed/seed-product-content.php`
- Create: `tests/product-seed.test.php`

**Interfaces:**
- Consumes Task 1 field keys and Plan 1's seed (products GT-001…GT-013, brands, categories, shop settings).
- Produces (DB, idempotent): every product has a generated featured image (GD, `gt-seed-{sku}.png`); GT-001 additionally has 2 gallery images; GT-001 has features (4), downloads (2), science (2), how_to_use, specification (3), documents (1), badges (Registered product, Independently tested, Made in Somerset); GT-002 (Root Riot) has upsells [GT-001, GT-003]; Clonex brand has full landing content (hero, science, 3 steps → GT-001/GT-003/GT-004, FAQ, pair brand Root Riot, guides ×2); Theme Settings knowledge defaults + `shop_experts_link` (`/contact-us/` placeholder URL) set; a "Find a Stockist" page (slug `find-a-stockist`) exists and is set as `shop_stockist_page`.

- [ ] **Step 1: Write the failing test**

`tests/product-seed.test.php`:

```php
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
```

Run: `tests/bin/wpx eval-file tests/product-seed.test.php`
Expected: FAIL — "Clonex Mist has a featured image" fails.

- [ ] **Step 2: Write the seed script**

`tests/seed/seed-product-content.php`:

```php
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
	foreach ( array( 'field_gt_pd_features', 'field_gt_pd_downloads', 'field_gt_pd_science', 'field_gt_pd_specification', 'field_gt_pd_documents' ) as $key ) {
		update_field( $key, array(), $product->get_id() );
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
```

- [ ] **Step 3: Run the seed twice, then the test**

Run:
```bash
tests/bin/wpx eval-file tests/seed/seed-product-content.php
tests/bin/wpx eval-file tests/seed/seed-product-content.php
tests/bin/wpx eval-file tests/product-seed.test.php
tests/run.sh
```
Expected: the second run creates no new attachments (`tests/bin/wpx post list --post_type=attachment --format=count` unchanged between runs); `Success: 27 assertions passed`; the full suite still green (Plan 1's tests must not break — the seed only adds content).

- [ ] **Step 4: Commit**

```bash
git add tests/seed/seed-product-content.php tests/product-seed.test.php
git commit -m "Seed product page and brand landing content with generated placeholder images"
```

---

### Task 3: Helpers — breadcrumb items, enquiry URLs, related products, badges, brand archive query

**Files:**
- Modify: `inc/woocommerce-helpers.php` (append)
- Modify: `template-parts/shop/breadcrumb.php` (delegate to the helper)
- Modify: `inc/woocommerce-shop.php` (`gt_shop_main_query`: brand archives show every product)
- Modify: `functions.php` (image sizes)
- Create: `tests/product-helpers.test.php`

**Interfaces:**
- Produces: `gt_shop_breadcrumb_items(): array` of `['label','url'|null]` — shop; + category chain on category archives; + brand on brand archives (Plan 1's archive template only — the Task 8 brand landing page has no breadcrumb, matching Figma); + primary category + brand + product on singles. `gt_product_stockist_url(WC_Product): string` ('' when no stockist page and no experts link). `gt_product_experts_url(WC_Product): string` (''), `gt_product_experts_label(): string`. `gt_brand_stockist_url(WP_Term): string`. `gt_product_related(WC_Product, int $limit = 8): WC_Product[]` (upsells in order, else same-brand products excluding the current, brand-first order irrelevant — menu_order/title). `gt_product_badges(WC_Product): WP_Term[]`. `gt_knowledge_band(?int $product_id): array{heading,text,image,link}` (product override merged over Theme Settings defaults; empty array when no heading). Image sizes `gt-product-main` 1200×1200 soft, `gt-product-thumb` 374×374 soft, `gt-brand-hero` 2880×1000 crop / `gt-brand-hero-sm` 1440×500 crop, `gt-brand-step` 858×840 crop / `gt-brand-step-sm` 429×420 crop, `gt-brand-pair` 2680×578 crop / `gt-brand-pair-sm` 1340×289 crop, `gt-knowledge` 2680×600 crop / `gt-knowledge-sm` 1340×300 crop.
- Brand archives (`is_tax('product_brand')`) get `posts_per_page = -1` from `gt_shop_main_query()`.

- [ ] **Step 1: Write the failing test**

`tests/product-helpers.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

$mist   = wc_get_product( wc_get_product_id_by_sku( 'GT-001' ) );
$rr     = wc_get_product( wc_get_product_id_by_sku( 'GT-002' ) );
$clonex = get_term_by( 'slug', 'clonex', 'product_brand' );
$stockist_url = get_field( 'shop_stockist_page', 'option' );

gt_assert_equal( add_query_arg( array( 'product' => $mist->get_id(), 'region' => 'uk' ), $stockist_url ), gt_product_stockist_url( $mist ), 'stockist url carries product id and region' );
gt_assert_equal( add_query_arg( array( 'product' => $mist->get_id() ), get_field( 'shop_experts_link', 'option' )['url'] ), gt_product_experts_url( $mist ), 'experts url carries product id' );
gt_assert_equal( 'Ask our experts', gt_product_experts_label(), 'experts label from the link title' );
gt_assert_equal( add_query_arg( array( 'brand' => 'clonex' ), $stockist_url ), gt_brand_stockist_url( $clonex ), 'brand stockist url carries the brand slug' );

$related = gt_product_related( $mist );
gt_assert_equal( array( 'Clonex Rooting Hormone', 'Clonex Pro Start', 'Clonex Mist Concentrate' ), array_map( function ( $p ) { return $p->get_name(); }, $related ), 'related falls back to same-brand products excluding the current' );
$related = gt_product_related( $rr );
gt_assert_equal( array( 'Clonex Mist', 'Clonex Rooting Hormone' ), array_map( function ( $p ) { return $p->get_name(); }, $related ), 'related uses upsells in order when set' );
gt_assert_equal( 1, count( gt_product_related( $mist, 1 ) ), 'related respects the limit' );

gt_assert_equal( array( 'Independently tested', 'Made in Somerset', 'Registered product' ), wp_list_pluck( gt_product_badges( $mist ), 'name' ), 'badges in name order' );
gt_assert_equal( array(), gt_product_badges( $rr ), 'no badges → empty array' );

$band = gt_knowledge_band( $mist->get_id() );
gt_assert_equal( 'Better knowledge. Stronger roots.', $band['heading'], 'knowledge band falls back to Theme Settings' );
gt_assert( (int) $band['image'] > 0, 'knowledge band default image' );
update_field( 'field_gt_pd_knowledge', array( 'heading' => 'Override heading', 'text' => '', 'image' => '', 'link' => '' ), $mist->get_id() );
$band = gt_knowledge_band( $mist->get_id() );
gt_assert_equal( 'Override heading', $band['heading'], 'product override wins for the heading' );
gt_assert( (int) $band['image'] > 0, 'unset override fields fall back individually' );
update_field( 'field_gt_pd_knowledge', array( 'heading' => '', 'text' => '', 'image' => '', 'link' => '' ), $mist->get_id() );

// Breadcrumbs rendered on real pages.
$html = gt_fetch( '/product/clonex-mist/' );
gt_assert_contains( 'shop-crumbs__current">Clonex Mist<', $html, 'product breadcrumb ends with the product' );
gt_assert_contains( '>Propagation</a>', $html, 'product breadcrumb has the category' );
gt_assert_contains( '>Clonex</a>', $html, 'product breadcrumb has the brand' );
$html = gt_fetch( '/brand/clonex/' );
gt_assert_contains( 'shop-crumbs__current">Clonex<', $html, 'brand breadcrumb ends with the brand' );
gt_assert_contains( 'Showing 4 of 4 products', $html, 'brand archive lists every brand product' );

foreach ( array( 'gt-product-main', 'gt-product-thumb', 'gt-brand-hero', 'gt-brand-hero-sm', 'gt-brand-step', 'gt-brand-step-sm', 'gt-brand-pair', 'gt-brand-pair-sm', 'gt-knowledge', 'gt-knowledge-sm' ) as $size ) {
	gt_assert( has_image_size( $size ), "image size {$size} registered" );
}

gt_test_done();
```

Run: `tests/bin/wpx eval-file tests/product-helpers.test.php`
Expected: FAIL — `gt_product_stockist_url` undefined.

- [ ] **Step 2: Append to `inc/woocommerce-helpers.php`**

```php

/**
 * Breadcrumb trail for every shop page: Our Products / Category / Brand /
 * Product. Each item is ['label' => string, 'url' => string|null]; the last
 * item is the current page and has no url.
 */
function gt_shop_breadcrumb_items() {
	$shop_id = wc_get_page_id( 'shop' );
	$items   = array( array(
		'label' => $shop_id > 0 ? get_the_title( $shop_id ) : __( 'Our Products', 'gt' ),
		'url'   => wc_get_page_permalink( 'shop' ),
	) );

	$add_category_chain = function ( WP_Term $term, $link_last ) use ( &$items ) {
		$chain = array_reverse( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );
		foreach ( $chain as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'product_cat' );
			if ( $ancestor instanceof WP_Term ) {
				$items[] = array( 'label' => $ancestor->name, 'url' => get_term_link( $ancestor ) );
			}
		}
		$items[] = array( 'label' => $term->name, 'url' => $link_last ? get_term_link( $term ) : null );
	};

	if ( is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$add_category_chain( $term, false );
		}
	} elseif ( is_tax( 'product_brand' ) ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$items[] = array( 'label' => $term->name, 'url' => null );
		}
	} elseif ( is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product instanceof WC_Product ) {
			$category = gt_product_primary_category( $product );
			if ( $category ) {
				$add_category_chain( $category, true );
			}
			$brand = gt_product_brand( $product );
			if ( $brand ) {
				$link = get_term_link( $brand );
				$items[] = array( 'label' => $brand->name, 'url' => is_wp_error( $link ) ? null : $link );
			}
			$items[] = array( 'label' => $product->get_name(), 'url' => null );
		}
	}

	return $items;
}

/** Stockist page URL from Theme Settings, or '' when none is set. */
function gt_shop_stockist_base_url() {
	$url = function_exists( 'get_field' ) ? (string) get_field( 'shop_stockist_page', 'option' ) : '';
	return $url ? $url : '';
}

/** "Find a local stockist" for a product: the stockist page with the product preselected (UK first). */
function gt_product_stockist_url( WC_Product $product ) {
	$base = gt_shop_stockist_base_url();
	return $base ? add_query_arg( array( 'product' => $product->get_id(), 'region' => 'uk' ), $base ) : '';
}

/** "Find a stockist" for a brand: the stockist page filtered to that brand. */
function gt_brand_stockist_url( WP_Term $brand ) {
	$base = gt_shop_stockist_base_url();
	return $base ? add_query_arg( array( 'brand' => $brand->slug ), $base ) : '';
}

/** "Ask our experts" link from Theme Settings with the product appended, or ''. */
function gt_product_experts_url( WC_Product $product ) {
	$link = function_exists( 'get_field' ) ? get_field( 'shop_experts_link', 'option' ) : null;
	if ( ! is_array( $link ) || empty( $link['url'] ) ) {
		return '';
	}
	return add_query_arg( array( 'product' => $product->get_id() ), $link['url'] );
}

function gt_product_experts_label() {
	$link = function_exists( 'get_field' ) ? get_field( 'shop_experts_link', 'option' ) : null;
	return ( is_array( $link ) && ! empty( $link['title'] ) ) ? $link['title'] : __( 'Ask our experts', 'gt' );
}

/**
 * "Complete the system": the product's upsells in the order they were set,
 * otherwise every other product in the same brand.
 *
 * @return WC_Product[]
 */
function gt_product_related( WC_Product $product, $limit = 8 ) {
	$limit = max( 1, (int) $limit );
	$ids   = array_map( 'intval', $product->get_upsell_ids() );

	if ( ! $ids ) {
		$brand = gt_product_brand( $product );
		if ( ! $brand ) {
			return array();
		}
		$ids = get_posts( array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => $limit + 1,
			'post__not_in'   => array( $product->get_id() ),
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'tax_query'      => array( array( 'taxonomy' => 'product_brand', 'field' => 'term_id', 'terms' => $brand->term_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		) );
	}

	$products = array();
	foreach ( $ids as $id ) {
		if ( $id === $product->get_id() ) {
			continue;
		}
		$related = wc_get_product( $id );
		if ( $related instanceof WC_Product && 'publish' === $related->get_status() && $related->is_visible() ) {
			$products[] = $related;
		}
		if ( count( $products ) >= $limit ) {
			break;
		}
	}
	return $products;
}

/** @return WP_Term[] badge terms, alphabetical. */
function gt_product_badges( WC_Product $product ) {
	if ( ! taxonomy_exists( 'product_badge' ) ) {
		return array();
	}
	$terms = wp_get_post_terms( $product->get_id(), 'product_badge', array( 'orderby' => 'name', 'order' => 'ASC' ) );
	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * The "Better knowledge" band: Theme Settings defaults with any non-empty
 * product override field on top. Empty array when there is no heading.
 */
function gt_knowledge_band( $product_id = 0 ) {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}
	$band = array(
		'heading' => (string) get_field( 'shop_knowledge_heading', 'option' ),
		'text'    => (string) get_field( 'shop_knowledge_text', 'option' ),
		'image'   => (int) get_field( 'shop_knowledge_image', 'option' ),
		'link'    => get_field( 'shop_knowledge_link', 'option' ),
	);
	if ( $product_id ) {
		$override = get_field( 'knowledge_override', $product_id );
		if ( is_array( $override ) ) {
			foreach ( array( 'heading', 'text', 'image', 'link' ) as $key ) {
				if ( ! empty( $override[ $key ] ) ) {
					$band[ $key ] = 'image' === $key ? (int) $override[ $key ] : $override[ $key ];
				}
			}
		}
	}
	if ( ! is_array( $band['link'] ) || empty( $band['link']['url'] ) ) {
		$band['link'] = null;
	}
	return '' === trim( $band['heading'] ) ? array() : $band;
}
```

- [ ] **Step 3: Make `template-parts/shop/breadcrumb.php` use the helper**

Replace everything from `$items = isset( $args['items'] ) ? $args['items'] : null;` down to (and including) the closing `}` of the `if ( null === $items ) { … }` block with:

```php
$items = isset( $args['items'] ) ? $args['items'] : gt_shop_breadcrumb_items();
```

(The docblock's `@param` line stays; update its second line to "Optional; defaults to gt_shop_breadcrumb_items().")

- [ ] **Step 4: Brand archives show every product — edit `gt_shop_main_query()` in `inc/woocommerce-shop.php`**

Add before the closing brace of the function:

```php
	// The brand landing page lists its whole range under "The {Brand} Range".
	if ( is_tax( 'product_brand' ) ) {
		$query->set( 'posts_per_page', -1 );
	}
```

- [ ] **Step 5: Image sizes in `functions.php` `gt_theme_support()`** — add after the category-hero sizes:

```php
	// Product page gallery: 600 x 600 tile with the cut-out contained (soft
	// sizes), and the 187px thumbnails.
	add_image_size( 'gt-product-main', 1200, 1200, false );
	add_image_size( 'gt-product-thumb', 374, 374, false );

	// Brand landing page: full-bleed hero, step slide, pair band; plus 2x.
	add_image_size( 'gt-brand-hero', 2880, 1000, true );
	add_image_size( 'gt-brand-hero-sm', 1440, 500, true );
	add_image_size( 'gt-brand-step', 858, 840, true );
	add_image_size( 'gt-brand-step-sm', 429, 420, true );
	add_image_size( 'gt-brand-pair', 2680, 578, true );
	add_image_size( 'gt-brand-pair-sm', 1340, 289, true );

	// "Better knowledge" band on the product page: 1340 x 300, plus 2x.
	add_image_size( 'gt-knowledge', 2680, 600, true );
	add_image_size( 'gt-knowledge-sm', 1340, 300, true );
```

Then regenerate the seeded images' sizes so the new sizes exist: `tests/bin/wpx media regenerate --yes --only-missing` (seeded attachments only; fine locally).

- [ ] **Step 6: Run the tests**

Run: `tests/bin/wpx eval-file tests/product-helpers.test.php`
Expected: `Success: 29 assertions passed`. The two `gt_fetch` breadcrumb checks pass because `/product/…` and `/brand/…` currently render WooCommerce's default single template / Plan 1's archive template respectively — both call the theme breadcrumb part? No: WC's default `single-product.php` does not include the theme part, so the three product breadcrumb assertions will still FAIL here. That is expected until Task 4 lands; the brand assertions pass now (archive template). Record the product-breadcrumb failures in the report and re-run this file after Task 4. If anything else fails, fix it.

Run `tests/run.sh` — every other file green.

- [ ] **Step 7: Commit**

```bash
git add gt_system/themes/growth_tech/inc/woocommerce-helpers.php gt_system/themes/growth_tech/template-parts/shop/breadcrumb.php gt_system/themes/growth_tech/inc/woocommerce-shop.php gt_system/themes/growth_tech/functions.php tests/product-helpers.test.php
git commit -m "Add product page helpers: breadcrumb trail, enquiry URLs, related products, badges, knowledge band"
```

---

### Task 4: Single product template — breadcrumb, gallery markup, summary (server-rendered)

**Files:**
- Create: `woocommerce/single-product.php`
- Create: `template-parts/shop/product-gallery.php`
- Create: `template-parts/shop/product-summary.php`
- Create: `assets/images/icons/zoom.svg`, `assets/images/icons/arrow-square.svg`, `assets/images/icons/tick-check.svg`, `assets/images/icons/download.svg`
- Create: `assets/sass/components/shop/_s.product.scss`
- Create: `assets/sass/components/shop/_s.gallery.scss`
- Create: `assets/sass/components/shop/_s.summary.scss`
- Modify: `assets/sass/main.scss` (`//Shop` block)
- Modify: `inc/woocommerce.php` (`gt_wc_remove_default_hooks`: remove WC's summary title/rating/excerpt/meta/sharing unconditionally; enqueue gallery styles nothing extra)
- Create: `tests/product-page.test.php`

**Interfaces:**
- Consumes Task 3 helpers, Plan 1 helpers, `gt_icon_svg()`, `gt_arrow_svg()`.
- Produces: `single-product.php` renders `<main class="page-wrapper product-page">` with `.product-page__inner` (breadcrumb, `.product-page__top` = gallery + summary) and placeholder slots for Tasks 6–7 (`<?php get_template_part( 'template-parts/shop/product-tabs' ); ?>` etc. are added in those tasks — see Step 3's markers). Gallery markup hooks for Task 5: `[data-product-gallery]`, `[data-gallery-slides]`, `.product-gallery__slide` + `img[data-full]`, `[data-gallery-zoom]`, `[data-gallery-arrows]`, `[data-gallery-thumbs]`, `a[data-gallery-thumb="N"]`, `[data-gallery-lightbox]` with `[data-lightbox-img]`, `[data-lightbox-close]`, `[data-lightbox-prev]`, `[data-lightbox-next]`. `do_action( 'woocommerce_single_product_summary' )` fires between the sizes and the CTA row — the Phase Two slot for price + add-to-cart.

- [ ] **Step 1: Write the failing test**

`tests/product-page.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

$mist_id = wc_get_product_id_by_sku( 'GT-001' );
$html    = gt_fetch( '/product/clonex-mist/' );

gt_assert_contains( 'class="page-wrapper product-page"', $html, 'theme single template renders' );
gt_assert_contains( 'shop-crumbs__current">Clonex Mist<', $html, 'breadcrumb ends with the product' );
gt_assert_contains( '>Propagation</a>', $html, 'breadcrumb has the category' );
gt_assert_contains( '>Clonex</a>', $html, 'breadcrumb has the brand' );

// Gallery
gt_assert_contains( 'data-product-gallery', $html, 'gallery root' );
gt_assert_equal( 3, substr_count( $html, 'class="product-gallery__slide"' ), 'three slides (featured + 2 gallery)' );
gt_assert_equal( 3, substr_count( $html, 'data-gallery-thumb="' ), 'three thumbnails' );
gt_assert_contains( 'data-gallery-zoom', $html, 'zoom button' );
gt_assert_contains( 'data-gallery-lightbox', $html, 'lightbox container' );
gt_assert_contains( 'data-full="', $html, 'slides carry the full-size url' );

// Summary
gt_assert_contains( '<h1 class="product-summary__title">Clonex Mist</h1>', $html, 'title' );
gt_assert_contains( 'product-summary__brand', $html, 'brand mark' );
gt_assert_contains( '--brand-accent: #FBC707', $html, 'brand accent variable on the page' );
gt_assert_contains( 'a short description that appears under the product title', $html, 'short description rendered' );
gt_assert_equal( 4, substr_count( $html, 'class="product-summary__feature"' ), 'four feature bullets' );
gt_assert_contains( '<strong>Direct foliar absorption</strong>', $html, 'feature lead is bold' );
gt_assert_equal( 3, substr_count( $html, 'class="product-sizes__chip' ), 'three size chips' );
gt_assert_contains( 'class="product-sizes__chip is-selected"', $html, 'first chip selected' );
gt_assert_contains( 'product-summary__stockist', $html, 'stockist CTA present' );
gt_assert_contains( 'product=' . $mist_id . '&#038;region=uk', $html, 'stockist CTA carries product + region' );
gt_assert_contains( 'product-summary__experts', $html, 'experts link present' );
gt_assert_contains( '>Ask our experts<', $html, 'experts link label' );
gt_assert_equal( 2, substr_count( $html, 'class="product-downloads__link"' ), 'two downloads' );
gt_assert_contains( 'Safety Data Sheet (PDF)', $html, 'download label' );
gt_assert_equal( 3, substr_count( $html, 'class="product-badges__item"' ), 'three badges' );
gt_assert_contains( 'Made in Somerset', $html, 'badge label' );
gt_assert_not_contains( 'add_to_cart_button', $html, 'no add to cart in enquiry mode' );
gt_assert_not_contains( 'single_add_to_cart_button', $html, 'no single add to cart in enquiry mode' );
gt_assert_not_contains( 'woocommerce-Price-amount', $html, 'no price in enquiry mode' );
gt_assert_not_contains( 'woocommerce-product-details__short-description', $html, 'WC default excerpt markup not duplicated' );
gt_assert_contains( 'site-footer__club', $html, 'join the growth club band shows' );

// A product with no extras renders the essentials only.
$html = gt_fetch( '/product/budget-propagator/' );
gt_assert_contains( '<h1 class="product-summary__title">Budget Propagator</h1>', $html, 'plain product title' );
gt_assert_not_contains( 'product-summary__features', $html, 'no feature list when empty' );
gt_assert_not_contains( 'product-sizes', $html, 'no sizes block when the product has none' );
gt_assert_not_contains( 'product-downloads', $html, 'no downloads block when empty' );
gt_assert_not_contains( 'product-badges', $html, 'no badges block when empty' );
gt_assert_equal( 1, substr_count( $html, 'class="product-gallery__slide"' ), 'single image → one slide' );
gt_assert_not_contains( 'data-gallery-thumb=', $html, 'single image → no thumbnails' );
gt_assert_not_contains( 'data-gallery-arrows', $html, 'single image → no arrows target' );

gt_test_done();
```

Run: `tests/bin/wpx eval-file tests/product-page.test.php`
Expected: FAIL — WooCommerce's default single template renders (`page-wrapper product-page` missing).

- [ ] **Step 2: Icons** (from Figma 384:1829, 384:1834, 384:1852, 384:1882)

`assets/images/icons/zoom.svg` — black square, white magnifier-plus (the square is the button background; only the glyph is in the file so CSS owns the colours):
```svg
<svg width="35" height="35" viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill="black" d="M15.4732 9.3356C17.099 9.3356 18.6583 9.98159 19.8079 11.1315C20.9576 12.2813 21.6034 13.8409 21.6034 15.467C21.6034 17.0932 20.9576 18.6528 19.8079 19.8026C18.6583 20.9525 17.099 21.5985 15.4732 21.5985C13.8473 21.5985 12.2881 20.9525 11.1384 19.8026C9.98878 18.6528 9.34291 17.0932 9.34291 15.467C9.34291 13.8409 9.98878 12.2813 11.1384 11.1315C12.2881 9.98159 13.8473 9.3356 15.4732 9.3356ZM15.4732 23.4379C17.3429 23.4379 19.0632 22.7941 20.4195 21.7173L25.5498 26.8485L26.2012 27.5L27.5 26.2009L26.8487 25.5494L21.7184 20.4182C22.795 19.0578 23.4387 17.341 23.4387 15.4709C23.4387 11.0677 19.8716 7.5 15.4693 7.5C11.067 7.5 7.5 11.0677 7.5 15.4709C7.5 19.874 11.067 23.4418 15.4693 23.4418L15.4732 23.4379ZM14.5536 19.1459H16.3927V16.3868H19.1513V14.5473H16.3927V11.7882H14.5536V14.5473H11.795V16.3868H14.5536V19.1459Z"/></svg>
```

`assets/images/icons/arrow-square.svg` — the 35px arrow glyph (right-pointing; CSS rotates it for "previous"):
```svg
<svg width="35" height="35" viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill="black" d="M26.2027 18.1824C26.3915 18.0066 26.5 17.7605 26.5 17.5027C26.5 17.2449 26.3915 17.0027 26.2027 16.823L19.1313 10.2601C18.7455 9.90066 18.1348 9.91629 17.7692 10.2913C17.4036 10.6663 17.4156 11.2601 17.8013 11.6156L23.129 16.5651H9.46429C8.92991 16.5651 8.5 16.9831 8.5 17.5027C8.5 18.0223 8.92991 18.4403 9.46429 18.4403H23.129L17.7973 23.3859C17.4116 23.7453 17.3996 24.3352 17.7652 24.7102C18.1308 25.0852 18.7415 25.0969 19.1272 24.7414L26.1987 18.1785L26.2027 18.1824Z"/></svg>
```

`assets/images/icons/tick-check.svg` — the feature tick (13×12 glyph in a 15×22 box, colour from CSS):
```svg
<svg width="15" height="22" viewBox="0 0 15.128 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill="black" d="M12.8689 4.69434L14.2898 5.70605L14.6004 5.92773L14.3738 6.23438L13.8582 6.93359V6.93457L6.8367 16.4082L6.83572 16.4072L6.23318 17.2227L5.97732 17.5693L5.66971 17.2676L1.4324 13.1094V13.1104L0.81033 12.502L0.535916 12.2344L0.809354 11.9658L2.05154 10.7471L2.31424 10.4893L2.57693 10.7471L5.67263 13.7852L11.8347 5.47363L12.3504 4.77734L12.5691 4.48047L12.8689 4.69434Z"/></svg>
```

`assets/images/icons/download.svg`:
```svg
<svg width="14" height="18" viewBox="0 0 14 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill="black" d="M13.125 18H14V16.3125H0V18H13.125ZM6.38021 13.2539L7 13.8516L7.61979 13.2539L12.5781 8.47266L13.1979 7.875L11.962 6.6832L11.3422 7.28086L7.87865 10.6207V0H6.12865V10.6207L2.6651 7.28086L2.04531 6.6832L0.809375 7.875L1.42917 8.47266L6.3875 13.2539H6.38021Z"/></svg>
```

(`gt_icon_svg()` swaps `fill="black"` for `currentColor`; it only allows `svg/path/g/rect` elements, which these use.)

- [ ] **Step 3: `woocommerce/single-product.php`**

```php
<?php
/**
 * Single product — Figma 384:1816 (Product Detail).
 *
 * Breadcrumb, gallery + summary, tabs, "Complete the system", the knowledge
 * band. Each part hides itself when it has nothing to show. The Phase Two
 * price/add-to-cart slot is the woocommerce_single_product_summary action
 * fired inside product-summary.php.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	global $product;

	if ( ! $product instanceof WC_Product ) {
		continue;
	}

	$brand  = gt_product_brand( $product );
	$accent = $brand ? gt_brand_accent( $brand ) : '#FBC707';

	do_action( 'woocommerce_before_single_product' );
	?>

<main class="page-wrapper product-page" id="product-<?php the_ID(); ?>" style="--brand-accent: <?php echo esc_attr( $accent ); ?>">
	<div class="product-page__inner">

		<div class="product-page__crumb">
			<?php get_template_part( 'template-parts/shop/breadcrumb' ); ?>
		</div>

		<div class="product-page__top">
			<div class="product-page__gallery">
				<?php get_template_part( 'template-parts/shop/product-gallery', null, array( 'product' => $product ) ); ?>
			</div>
			<div class="product-page__summary">
				<?php get_template_part( 'template-parts/shop/product-summary', null, array( 'product' => $product, 'brand' => $brand ) ); ?>
			</div>
		</div>

		<?php /* TASK 6: tabs go here */ ?>
	</div>

	<?php /* TASK 7: complete the system goes here */ ?>

	<div class="product-page__inner">
		<?php /* TASK 7: knowledge band goes here */ ?>
	</div>

	<?php do_action( 'woocommerce_after_single_product' ); ?>

<?php
endwhile;

get_footer();
```

- [ ] **Step 4: `template-parts/shop/product-gallery.php`**

```php
<?php
/**
 * Product gallery — Figma 384:1827. 600px tile with the cut-out contained,
 * zoom button bottom-left, prev/next bottom-right, three thumbnails under.
 *
 * Without JavaScript the first image shows and each thumbnail links to the
 * full-size file; product-gallery.js turns it into a slider + lightbox.
 *
 * @param array $args ['product' => WC_Product]
 */

$product = isset( $args['product'] ) ? $args['product'] : null;
if ( ! $product instanceof WC_Product ) {
	return;
}

$image_ids = array_filter( array_merge( array( (int) $product->get_image_id() ), array_map( 'intval', $product->get_gallery_image_ids() ) ) );
$image_ids = array_values( array_unique( $image_ids ) );
$multiple  = count( $image_ids ) > 1;
$name      = $product->get_name();
?>
<div class="product-gallery" data-product-gallery>
	<div class="product-gallery__main">
		<div class="product-gallery__slides" data-gallery-slides>
			<?php if ( $image_ids ) : ?>
				<?php foreach ( $image_ids as $index => $image_id ) : ?>
					<div class="product-gallery__slide">
						<?php
						$full = wp_get_attachment_image_url( $image_id, 'full' );
						echo wp_get_attachment_image( $image_id, 'gt-product-main', false, array(
							'class'     => 'product-gallery__img',
							'sizes'     => '(max-width: 767px) calc(100vw - 50px), (max-width: 1265px) 50vw, 600px',
							'data-full' => $full ? $full : '',
							'loading'   => $index > 0 ? 'lazy' : 'eager',
						) );
						?>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="product-gallery__slide">
					<?php echo wc_placeholder_img( 'gt-product-main', array( 'class' => 'product-gallery__img', 'data-full' => '' ) ); ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $image_ids ) : ?>
			<button type="button" class="product-gallery__zoom" data-gallery-zoom aria-label="<?php esc_attr_e( 'Enlarge image', 'gt' ); ?>">
				<?php gt_icon_svg( 'zoom' ); ?>
			</button>
		<?php endif; ?>

		<?php if ( $multiple ) : ?>
			<div class="product-gallery__arrows" data-gallery-arrows></div>
		<?php endif; ?>
	</div>

	<?php if ( $multiple ) : ?>
		<ul class="product-gallery__thumbs" data-gallery-thumbs>
			<?php foreach ( $image_ids as $index => $image_id ) : ?>
				<li class="product-gallery__thumb-item">
					<a class="product-gallery__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( wp_get_attachment_image_url( $image_id, 'full' ) ); ?>"
						data-gallery-thumb="<?php echo esc_attr( $index ); ?>"
						<?php echo 0 === $index ? 'aria-current="true"' : ''; ?>
						aria-label="<?php echo esc_attr( sprintf( /* translators: 1: image number, 2: product name */ __( 'Show image %1$d of %2$s', 'gt' ), $index + 1, $name ) ); ?>">
						<?php echo wp_get_attachment_image( $image_id, 'gt-product-thumb', false, array( 'class' => 'product-gallery__thumb-img', 'sizes' => '187px', 'loading' => 'lazy', 'alt' => '' ) ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $image_ids ) : ?>
		<div class="product-lightbox" data-gallery-lightbox hidden role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( '%s images', 'gt' ), $name ) ); ?>">
			<button type="button" class="product-lightbox__close" data-lightbox-close aria-label="<?php esc_attr_e( 'Close', 'gt' ); ?>"><?php gt_icon_svg( 'close' ); ?></button>
			<?php if ( $multiple ) : ?>
				<button type="button" class="product-lightbox__nav product-lightbox__nav--prev" data-lightbox-prev aria-label="<?php esc_attr_e( 'Previous image', 'gt' ); ?>"><?php gt_icon_svg( 'arrow-square' ); ?></button>
				<button type="button" class="product-lightbox__nav product-lightbox__nav--next" data-lightbox-next aria-label="<?php esc_attr_e( 'Next image', 'gt' ); ?>"><?php gt_icon_svg( 'arrow-square' ); ?></button>
			<?php endif; ?>
			<img class="product-lightbox__img" data-lightbox-img src="" alt="<?php echo esc_attr( $name ); ?>" />
		</div>
	<?php endif; ?>
</div>
```

- [ ] **Step 5: `template-parts/shop/product-summary.php`**

```php
<?php
/**
 * Product summary — Figma 384:1840. Brand mark, title, intro, tick list,
 * sizes, the Phase Two price/cart slot, enquiry CTAs; then downloads and
 * badges under a rule.
 *
 * @param array $args ['product' => WC_Product, 'brand' => WP_Term|null]
 */

$product = isset( $args['product'] ) ? $args['product'] : null;
$brand   = isset( $args['brand'] ) ? $args['brand'] : null;
if ( ! $product instanceof WC_Product ) {
	return;
}

$features  = function_exists( 'get_field' ) ? get_field( 'features', $product->get_id() ) : array();
$downloads = function_exists( 'get_field' ) ? get_field( 'downloads', $product->get_id() ) : array();
$sizes     = gt_product_sizes( $product );
$badges    = gt_product_badges( $product );
$stockist  = gt_product_stockist_url( $product );
$experts   = gt_product_experts_url( $product );
$logo_id   = $brand instanceof WP_Term ? (int) gt_term_field( 'logo', $brand, 0 ) : 0;
$intro     = $product->get_short_description();
?>
<div class="product-summary">
	<div class="product-summary__main">

		<div class="product-summary__head">
			<?php if ( $brand instanceof WP_Term ) : ?>
				<a class="product-summary__brand" href="<?php echo esc_url( get_term_link( $brand ) ); ?>">
					<?php if ( $logo_id ) : ?>
						<?php echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'product-summary__brand-img', 'alt' => $brand->name ) ); ?>
					<?php else : ?>
						<span class="product-summary__brand-text"><?php echo esc_html( $brand->name ); ?></span>
					<?php endif; ?>
				</a>
			<?php endif; ?>
			<h1 class="product-summary__title"><?php echo esc_html( $product->get_name() ); ?></h1>
		</div>

		<?php if ( $intro ) : ?>
			<div class="product-summary__intro"><?php echo wp_kses_post( wpautop( $intro ) ); ?></div>
		<?php endif; ?>

		<?php if ( is_array( $features ) && $features ) : ?>
			<ul class="product-summary__features">
				<?php foreach ( $features as $feature ) : ?>
					<?php
					$lead = isset( $feature['lead'] ) ? trim( (string) $feature['lead'] ) : '';
					$text = isset( $feature['text'] ) ? trim( (string) $feature['text'] ) : '';
					if ( ! $lead && ! $text ) {
						continue;
					}
					?>
					<li class="product-summary__feature">
						<span class="product-summary__tick" aria-hidden="true"><?php gt_icon_svg( 'tick-check' ); ?></span>
						<p class="product-summary__feature-text">
							<?php if ( $lead ) : ?><strong><?php echo esc_html( $lead ); ?></strong><?php endif; ?>
							<?php if ( $lead && $text ) : ?> — <?php endif; ?>
							<?php echo esc_html( $text ); ?>
						</p>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $sizes ) : ?>
			<div class="product-sizes">
				<p class="product-summary__eyebrow"><?php esc_html_e( 'Available sizes', 'gt' ); ?></p>
				<ul class="product-sizes__list" aria-label="<?php esc_attr_e( 'Available sizes', 'gt' ); ?>">
					<?php foreach ( $sizes as $index => $size ) : ?>
						<li class="product-sizes__chip<?php echo 0 === $index ? ' is-selected' : ''; ?>"><?php echo esc_html( $size ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php
		/*
		 * Phase Two: WooCommerce's price and add-to-cart (with the size
		 * variation form) render here once GT_SHOP_ENQUIRY_MODE is off.
		 */
		do_action( 'woocommerce_single_product_summary' );
		?>

		<?php if ( $stockist || $experts ) : ?>
			<div class="product-summary__actions">
				<?php if ( $stockist ) : ?>
					<a class="btn-flat btn-flat--dark product-summary__stockist" href="<?php echo esc_url( $stockist ); ?>">
						<span><?php esc_html_e( 'Find a local stockist', 'gt' ); ?></span>
						<?php gt_arrow_svg(); ?>
					</a>
				<?php endif; ?>
				<?php if ( $experts ) : ?>
					<a class="product-summary__experts" href="<?php echo esc_url( $experts ); ?>"><?php echo esc_html( gt_product_experts_label() ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( ( is_array( $downloads ) && $downloads ) || $badges ) : ?>
		<div class="product-summary__extra">
			<?php if ( is_array( $downloads ) && $downloads ) : ?>
				<ul class="product-downloads">
					<?php foreach ( $downloads as $download ) : ?>
						<?php
						$file_id = ! empty( $download['file'] ) ? (int) $download['file'] : 0;
						$label   = ! empty( $download['label'] ) ? $download['label'] : get_the_title( $file_id );
						$url     = $file_id ? wp_get_attachment_url( $file_id ) : '';
						if ( ! $url ) {
							continue;
						}
						?>
						<li class="product-downloads__item">
							<a class="product-downloads__link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
								<span class="product-downloads__icon" aria-hidden="true"><?php gt_icon_svg( 'download' ); ?></span>
								<span><?php echo esc_html( $label ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $badges ) : ?>
				<ul class="product-badges">
					<?php foreach ( $badges as $badge ) : ?>
						<li class="product-badges__item"><?php echo esc_html( $badge->name ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
```

- [ ] **Step 6: Remove WooCommerce's summary defaults the theme replaces — `inc/woocommerce.php` `gt_wc_remove_default_hooks()`**, in the unconditional block add:

```php
	// product-summary.php renders the title, intro and brand itself; the
	// woocommerce_single_product_summary action is kept as the Phase Two
	// price / add-to-cart slot.
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
```

(Verify each priority against `gt_system/plugins/woocommerce/includes/wc-template-hooks.php` before committing.)

- [ ] **Step 7: SCSS — `assets/sass/components/shop/_s.product.scss`**

```scss
// ---------------------------------------------------------------------------
// Product page frame — Figma 384:1816
//
// 50px gutters / 1340 content. Breadcrumb 30 under the header, 25 to the
// gallery + summary row (600 | 69 gap | 621). Tabs 90 below, the grey
// "Complete the system" band 77 below that (full bleed), the knowledge band
// 77 below, and 60 to the footer.
// ---------------------------------------------------------------------------

.product-page {
  padding-top: 30px;

  &__inner {
    max-width: 1340px;
    margin: 0 auto;
    padding: 0 50px;
  }

  &__crumb {
    margin-bottom: 25px;
  }

  &__top {
    display: flex;
    align-items: flex-start;
    gap: 69px;
  }

  &__gallery {
    flex: 0 0 600px;
    max-width: 600px;
    min-width: 0;
  }

  &__summary {
    flex: 1 1 auto;
    min-width: 0;
    max-width: 621px;
  }
}

@media (max-width: $xl - 1px) {
  .product-page {
    &__inner {
      padding: 0 25px;
    }

    &__top {
      gap: 40px;
    }

    &__gallery {
      flex-basis: 50%;
      max-width: 50%;
    }

    &__summary {
      max-width: none;
    }
  }
}

@media (max-width: $md - 1px) {
  .product-page {
    &__top {
      flex-direction: column;
      gap: 35px;
    }

    &__gallery {
      flex-basis: auto;
      width: 100%;
      max-width: 600px;
    }
  }
}

@media (max-width: $sm - 1px) {
  .product-page {
    padding-top: 20px;

    &__crumb {
      margin-bottom: 20px;
    }
  }
}
```

- [ ] **Step 8: SCSS — `assets/sass/components/shop/_s.gallery.scss`**

```scss
// ---------------------------------------------------------------------------
// Product gallery — Figma 384:1827
//
// 600 x 600 tile on a 5% wash, cut-out contained. Zoom (black) bottom-left
// at 20/25; prev (light) + next (black) 35px squares bottom-right, 10 apart.
// Thumbnails: three 187px tiles, 19 apart, 15 below.
// ---------------------------------------------------------------------------

$gallery-wash: rgba(35, 31, 32, 0.05);

.product-gallery {
  display: flex;
  flex-direction: column;
  gap: 15px;

  &__main {
    position: relative;
    aspect-ratio: 1 / 1;
    background-color: $gallery-wash;
    overflow: hidden;
  }

  &__slides,
  &__slides .slick-list,
  &__slides .slick-track {
    height: 100%;
  }

  &__slide {
    display: flex !important; // slick sets display:block inline on slides
    align-items: center;
    justify-content: center;
    height: 100%;
    padding: 40px 40px 75px;
  }

  // Before slick runs (or without JS) only the first image shows.
  &__slides:not(.slick-initialized) &__slide:not(:first-child) {
    display: none !important;
  }

  &__img {
    display: block;
    width: auto;
    height: 100%;
    max-width: 100%;
    object-fit: contain;
  }

  &__zoom,
  &__arrows .slick-prev,
  &__arrows .slick-next,
  &__lightbox-nav {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    padding: 0;
    border: 0;
    cursor: pointer;
    transition: background-color 0.2s ease, color 0.2s ease;

    svg {
      display: block;
      width: 35px;
      height: 35px;
    }
  }

  &__zoom {
    position: absolute;
    left: 20px;
    bottom: 25px;
    z-index: 2;
    background-color: #000;
    color: color(white);

    &:hover,
    &:focus-visible {
      background-color: #231f20;
    }
  }

  &__arrows {
    position: absolute;
    right: 20px;
    bottom: 25px;
    z-index: 2;
    display: flex;
    gap: 10px;

    // _p.home.scss paints an unscoped .slick-prev/.slick-next background
    // image; these buttons carry their own glyph.
    .slick-prev,
    .slick-next {
      position: static;
      transform: none;
      background-image: none;
      text-indent: 0;

      &::before,
      &::after {
        content: none;
      }
    }

    .slick-prev {
      background-color: #f4f4f4;
      color: #000;

      svg {
        transform: rotate(180deg);
      }

      &:hover,
      &:focus-visible {
        background-color: #e6e6e6;
      }
    }

    .slick-next {
      background-color: #000;
      color: color(white);

      &:hover,
      &:focus-visible {
        background-color: #231f20;
      }
    }

    .slick-disabled {
      opacity: 0.3;
      cursor: default;
    }
  }

  &__thumbs {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 19px;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  &__thumb-item {
    margin: 0;
  }

  &__thumb {
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 1 / 1;
    padding: 18px;
    background-color: $gallery-wash;
    outline: 2px solid transparent;
    outline-offset: -2px;
    transition: outline-color 0.2s ease;

    &:hover,
    &:focus-visible {
      outline-color: rgba(0, 0, 0, 0.35);
    }

    &.is-active {
      outline-color: #000;
    }
  }

  &__thumb-img {
    width: auto;
    height: 100%;
    max-width: 100%;
    object-fit: contain;
  }
}

// -- lightbox -----------------------------------------------------------------
.product-lightbox {
  position: fixed;
  inset: 0;
  z-index: 3000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 70px 80px;
  background-color: rgba(0, 0, 0, 0.92);

  &[hidden] {
    display: none;
  }

  &__img {
    display: block;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
  }

  &__close {
    position: absolute;
    top: 20px;
    right: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    padding: 0;
    color: color(white);
    cursor: pointer;

    svg {
      display: block;
      width: 16px;
      height: 16px;
    }
  }

  &__nav {
    position: absolute;
    top: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    padding: 0;
    background-color: color(white);
    color: #000;
    cursor: pointer;
    transform: translateY(-50%);

    svg {
      display: block;
      width: 35px;
      height: 35px;
    }

    &--prev {
      left: 20px;

      svg {
        transform: rotate(180deg);
      }
    }

    &--next {
      right: 20px;
    }
  }
}

body.product-lightbox-open {
  overflow: hidden;
}

@media (max-width: $sm - 1px) {
  .product-gallery {
    &__slide {
      padding: 25px 25px 70px;
    }

    &__thumbs {
      gap: 12px;
    }

    &__thumb {
      padding: 10px;
    }
  }

  .product-lightbox {
    padding: 70px 20px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .product-gallery__zoom,
  .product-gallery__thumb,
  .product-gallery__arrows .slick-prev,
  .product-gallery__arrows .slick-next {
    transition: none;
  }
}
```

- [ ] **Step 9: SCSS — `assets/sass/components/shop/_s.summary.scss`**

```scss
// ---------------------------------------------------------------------------
// Product summary — Figma 384:1840
//
// Brand mark 16px tall, title 60/65, intro 15/25, tick list, sizes, CTAs;
// 35px + rule; downloads and badges. Every text element sets its own face
// and size because the theme's base element rules would otherwise win.
// ---------------------------------------------------------------------------

$summary-text: rgba(0, 0, 0, 0.75);
$summary-font: "DM Sans", #{$primary_font};

.product-summary {
  display: flex;
  flex-direction: column;
  gap: 35px;

  &__main {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 30px;
    padding-bottom: 35px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.25);
  }

  &__head {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;
  }

  &__brand {
    display: inline-flex;
    align-items: center;
    height: 16px;
    color: var(--brand-accent, #fbc707);
    text-decoration: none;
  }

  &__brand-img {
    display: block;
    width: auto;
    height: 16px;
    max-width: 150px;
    object-fit: contain;
    object-position: left center;
  }

  &__brand-text {
    font-family: $summary-font;
    font-size: 14px;
    font-weight: $font-900;
    line-height: 16px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  &__title {
    margin: 0;
    font-family: "Cormorant Garamond", serif;
    font-size: 60px;
    font-weight: $font-700;
    line-height: 65px;
    letter-spacing: -1.2px;
    color: #000;
  }

  &__intro {
    p {
      margin: 0 0 12px;
      font-family: $summary-font;
      font-size: 15px;
      font-weight: $font-400;
      line-height: 25px;
      color: $summary-text;

      &:last-child {
        margin-bottom: 0;
      }
    }
  }

  &__features {
    display: flex;
    flex-direction: column;
    gap: 15px;
    width: 100%;
    max-width: 564px;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  &__feature {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin: 0;
  }

  &__tick {
    flex: 0 0 auto;
    display: block;
    width: 15px;
    height: 22px;
    color: var(--brand-accent, #fbc707);

    svg {
      display: block;
      width: 15px;
      height: 22px;
    }
  }

  &__feature-text {
    margin: 0;
    font-family: $summary-font;
    font-size: 15px;
    font-weight: $font-400;
    line-height: 25px;
    color: $summary-text;

    strong {
      font-weight: $font-700;
      color: #000;
    }
  }

  &__eyebrow {
    margin: 0;
    font-family: $summary-font;
    font-size: 12px;
    font-weight: $font-500;
    line-height: normal;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: rgba(0, 0, 0, 0.5);
  }

  &__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 25px;
  }

  &__experts {
    font-family: $summary-font;
    font-size: 14px;
    font-weight: $font-700;
    line-height: normal;
    color: #000;
    text-decoration: underline;
    text-underline-position: from-font;

    &:hover,
    &:focus-visible {
      text-decoration-thickness: 2px;
    }
  }

  &__extra {
    display: flex;
    flex-direction: column;
    gap: 26px;
  }
}

// Sizes — display-only chips in Phase One (Figma 384:1867).
.product-sizes {
  display: flex;
  flex-direction: column;
  gap: 10px;

  &__list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  &__chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0;
    padding: 10px 15px;
    border: 1px solid #000;
    font-family: "DM Sans", #{$primary_font};
    font-size: 14px;
    font-weight: $font-400;
    line-height: normal;
    color: #000;

    &.is-selected {
      background-color: #000;
      color: color(white);
    }
  }
}

// Downloads — Figma 384:1880.
.product-downloads {
  display: flex;
  flex-wrap: wrap;
  gap: 18px 30px;
  margin: 0;
  padding: 0;
  list-style: none;

  &__item {
    margin: 0;
  }

  &__link {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-family: "DM Sans", #{$primary_font};
    font-size: 14px;
    font-weight: $font-400;
    line-height: normal;
    color: #000;
    text-decoration: none;

    span {
      font: inherit;
    }

    &:hover span:last-child,
    &:focus-visible span:last-child {
      text-decoration: underline;
    }
  }

  &__icon {
    display: block;
    width: 14px;
    height: 18px;
    color: #000;

    svg {
      display: block;
      width: 14px;
      height: 18px;
    }
  }
}

// Badges — Figma 384:1887.
.product-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin: 0;
  padding: 0;
  list-style: none;

  &__item {
    margin: 0;
    padding: 10px 15px;
    background-color: #f4f4f4;
    font-family: "DM Sans", #{$primary_font};
    font-size: 12px;
    font-weight: $font-400;
    line-height: normal;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #000;
  }
}

@media (max-width: $md - 1px) {
  .product-summary {
    &__title {
      font-size: 44px;
      line-height: 48px;
      letter-spacing: -0.9px;
    }
  }
}

@media (max-width: $sm - 1px) {
  .product-summary {
    gap: 25px;

    &__main {
      gap: 22px;
      padding-bottom: 25px;
    }

    &__title {
      font-size: 36px;
      line-height: 40px;
      letter-spacing: -0.7px;
    }

    &__actions {
      gap: 15px;
    }
  }
}
```

- [ ] **Step 10: Import + compile.** In `assets/sass/main.scss` `//Shop` block, append:

```scss
@import "components/shop/s.product";
@import "components/shop/s.gallery";
@import "components/shop/s.summary";
```

Compile: `npx sass assets/sass/main.scss assets/css/main.css --style=compressed --source-map` (theme root).

- [ ] **Step 11: Run the tests**

Run: `tests/bin/wpx eval-file tests/product-page.test.php` → `Success: 39 assertions passed`; `tests/bin/wpx eval-file tests/product-helpers.test.php` → now fully green; `tests/run.sh` all green.

- [ ] **Step 12: Look at it** — `"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless=new --disable-gpu --ignore-certificate-errors --hide-scrollbars --window-size=1440,1300 --screenshot=/tmp/product-1440.png https://growth-tech.local/product/clonex-mist/` and Read it: gallery tile 600 with the cut-out centred, controls bottom corners, three thumbs; summary column with logo, 60px title, intro, four yellow ticks, chips (first black), black button + underlined link, rule, downloads row, three grey badge pills. Fix drift before committing.

- [ ] **Step 13: Commit**

```bash
git add gt_system/themes/growth_tech/woocommerce/single-product.php gt_system/themes/growth_tech/template-parts/shop/product-gallery.php gt_system/themes/growth_tech/template-parts/shop/product-summary.php gt_system/themes/growth_tech/assets/images/icons/zoom.svg gt_system/themes/growth_tech/assets/images/icons/arrow-square.svg gt_system/themes/growth_tech/assets/images/icons/tick-check.svg gt_system/themes/growth_tech/assets/images/icons/download.svg gt_system/themes/growth_tech/assets/sass/components/shop/_s.product.scss gt_system/themes/growth_tech/assets/sass/components/shop/_s.gallery.scss gt_system/themes/growth_tech/assets/sass/components/shop/_s.summary.scss gt_system/themes/growth_tech/assets/sass/main.scss gt_system/themes/growth_tech/assets/css/main.css gt_system/themes/growth_tech/assets/css/main.css.map gt_system/themes/growth_tech/inc/woocommerce.php tests/product-page.test.php
git commit -m "Add single product template with gallery markup and enquiry summary"
```

---

### Task 5: Gallery behaviour — slider, thumbnails, lightbox

**Files:**
- Create: `assets/js/product-gallery.js`
- Modify: `inc/woocommerce.php` (enqueue on `is_product()`, dequeue WC gallery scripts)
- Create: `tests/product-gallery.test.php`

**Interfaces:**
- Consumes Task 4's data hooks. Produces script handle `gt-product-gallery` (deps `jquery`, `slick-js`), enqueued only on `is_product()`. Behaviour: with >1 image, the slides become a slick slider (`slidesToShow: 1`, no infinite loop, arrows appended into `[data-gallery-arrows]`, speed 0 under reduced motion); thumbnails go to their slide and reflect the active one (`is-active`, `aria-current`); zoom (or clicking the main image) opens the lightbox on the current slide; lightbox prev/next/Esc/close work, focus returns to the zoom button; body gets `product-lightbox-open` while open.

- [ ] **Step 1: Write the failing test**

`tests/product-gallery.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

$html = gt_fetch( '/product/clonex-mist/' );
gt_assert_contains( 'assets/js/product-gallery.js', $html, 'gallery script enqueued on products' );
gt_assert_not_contains( 'photoswipe', $html, 'WooCommerce PhotoSwipe not loaded' );
gt_assert_not_contains( 'flexslider', $html, 'WooCommerce FlexSlider not loaded' );
gt_assert_not_contains( 'zoom.min.js', $html, 'WooCommerce zoom not loaded' );
gt_assert_contains( 'assets/js/slick.min.js', $html, 'slick available for the gallery' );

gt_assert_not_contains( 'assets/js/product-gallery.js', gt_fetch( '/shop/' ), 'gallery script not enqueued elsewhere' );

$js = file_get_contents( get_template_directory() . '/assets/js/product-gallery.js' );
foreach ( array( 'data-product-gallery', 'data-gallery-slides', 'data-gallery-thumb', 'data-gallery-zoom', 'data-gallery-arrows', 'data-gallery-lightbox', 'data-lightbox-img', 'data-lightbox-close', 'Escape', 'slickGoTo', 'product-lightbox-open' ) as $needle ) {
	gt_assert_contains( $needle, $js, "script handles {$needle}" );
}

gt_test_done();
```

Run: `tests/bin/wpx eval-file tests/product-gallery.test.php` → FAIL (script not enqueued).

- [ ] **Step 2: Enqueue — append to `inc/woocommerce.php`**

```php
/** Product gallery slider + lightbox; WooCommerce's own gallery scripts stay off. */
function gt_product_enqueue_scripts() {
	if ( ! is_product() ) {
		return;
	}
	wp_enqueue_script(
		'gt-product-gallery',
		get_template_directory_uri() . '/assets/js/product-gallery.js',
		array( 'jquery', 'slick-js' ),
		gt_asset_version( '/assets/js/product-gallery.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'gt_product_enqueue_scripts' );

function gt_product_dequeue_wc_gallery() {
	foreach ( array( 'photoswipe', 'photoswipe-ui-default', 'photoswipe-default-skin', 'zoom', 'flexslider', 'wc-single-product' ) as $handle ) {
		wp_dequeue_script( $handle );
		wp_dequeue_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'gt_product_dequeue_wc_gallery', 99 );
```

Also in `gt_wc_theme_support()` leave the three `wc-product-gallery-*` supports undeclared (they are not — confirm no `add_theme_support( 'wc-product-gallery-zoom' )` etc. exists).

- [ ] **Step 3: `assets/js/product-gallery.js`**

```js
/**
 * Product gallery — slider, thumbnails and lightbox for the product page.
 *
 * Markup comes from template-parts/shop/product-gallery.php. With one image
 * only the zoom/lightbox is wired; with more the slides become a Slick
 * slider whose arrows are appended into [data-gallery-arrows].
 */
(function ($) {
	'use strict';

	var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	var ARROW = '<svg viewBox="0 0 35 35" width="35" height="35" aria-hidden="true" focusable="false"><path fill="currentColor" d="M26.2027 18.1824C26.3915 18.0066 26.5 17.7605 26.5 17.5027C26.5 17.2449 26.3915 17.0027 26.2027 16.823L19.1313 10.2601C18.7455 9.90066 18.1348 9.91629 17.7692 10.2913C17.4036 10.6663 17.4156 11.2601 17.8013 11.6156L23.129 16.5651H9.46429C8.92991 16.5651 8.5 16.9831 8.5 17.5027C8.5 18.0223 8.92991 18.4403 9.46429 18.4403H23.129L17.7973 23.3859C17.4116 23.7453 17.3996 24.3352 17.7652 24.7102C18.1308 25.0852 18.7415 25.0969 19.1272 24.7414L26.1987 18.1785L26.2027 18.1824Z"/></svg>';

	$(function () {
		$('[data-product-gallery]').each(function () {
			var $gallery = $(this);
			var $slides = $gallery.find('[data-gallery-slides]');
			var $items = $slides.children('.product-gallery__slide');
			var $thumbs = $gallery.find('[data-gallery-thumb]');
			var $arrows = $gallery.find('[data-gallery-arrows]');
			var $zoom = $gallery.find('[data-gallery-zoom]');
			var $lightbox = $gallery.find('[data-gallery-lightbox]');
			var $lightboxImg = $lightbox.find('[data-lightbox-img]');
			var count = $items.length;
			var current = 0;
			var lastFocus = null;

			function fullSrc(index) {
				var $img = $items.eq(index).find('img');
				return $img.attr('data-full') || $img.attr('src') || '';
			}

			function setThumb(index) {
				$thumbs.each(function () {
					var active = parseInt(this.getAttribute('data-gallery-thumb'), 10) === index;
					this.classList.toggle('is-active', active);
					if (active) { this.setAttribute('aria-current', 'true'); } else { this.removeAttribute('aria-current'); }
				});
			}

			// -- slider ---------------------------------------------------------
			if (count > 1 && $.fn.slick) {
				$slides.on('init afterChange', function (event, slick, index) {
					current = typeof index === 'number' ? index : (slick.currentSlide || 0);
					setThumb(current);
				});
				$slides.slick({
					slidesToShow: 1,
					slidesToScroll: 1,
					infinite: false,
					dots: false,
					arrows: $arrows.length > 0,
					appendArrows: $arrows.length ? $arrows : $slides,
					prevArrow: '<button type="button" class="slick-prev" aria-label="Previous image">' + ARROW + '</button>',
					nextArrow: '<button type="button" class="slick-next" aria-label="Next image">' + ARROW + '</button>',
					speed: reduced ? 0 : 350,
					adaptiveHeight: false
				});
			}

			$thumbs.on('click', function (event) {
				event.preventDefault();
				var index = parseInt(this.getAttribute('data-gallery-thumb'), 10) || 0;
				if ($slides.hasClass('slick-initialized')) {
					$slides.slick('slickGoTo', index);
				} else {
					current = index;
					setThumb(index);
				}
			});

			// -- lightbox -------------------------------------------------------
			if (!$lightbox.length) {
				return;
			}

			function showLightbox(index) {
				current = Math.max(0, Math.min(count - 1, index));
				var src = fullSrc(current);
				if (!src) { return; }
				$lightboxImg.attr('src', src);
				$lightbox.prop('hidden', false);
				document.body.classList.add('product-lightbox-open');
				$lightbox.find('[data-lightbox-close]').trigger('focus');
			}

			function hideLightbox() {
				$lightbox.prop('hidden', true);
				document.body.classList.remove('product-lightbox-open');
				if (lastFocus) { lastFocus.focus({ preventScroll: true }); }
			}

			function step(delta) {
				var next = current + delta;
				if (next < 0 || next >= count) { return; }
				showLightbox(next);
				if ($slides.hasClass('slick-initialized')) { $slides.slick('slickGoTo', next); }
			}

			$zoom.on('click', function () {
				lastFocus = this;
				showLightbox(current);
			});
			$items.on('click', 'img', function () {
				lastFocus = $zoom[0] || this;
				showLightbox(current);
			});
			$lightbox.on('click', '[data-lightbox-close]', hideLightbox);
			$lightbox.on('click', '[data-lightbox-prev]', function () { step(-1); });
			$lightbox.on('click', '[data-lightbox-next]', function () { step(1); });
			$lightbox.on('click', function (event) {
				if (event.target === this) { hideLightbox(); }
			});

			$(document).on('keydown', function (event) {
				if ($lightbox.prop('hidden')) { return; }
				if (event.key === 'Escape') { hideLightbox(); }
				if (event.key === 'ArrowLeft') { step(-1); }
				if (event.key === 'ArrowRight') { step(1); }
			});
		});
	});
})(jQuery);
```

- [ ] **Step 4: Run the test**

Run: `tests/bin/wpx eval-file tests/product-gallery.test.php` → `Success: 17 assertions passed`; `tests/run.sh` green.

- [ ] **Step 5: Browser check with puppeteer-core** (setup lives at `/private/tmp/claude-501/-Users-stuartkirkland-Sites-PROJECTS-growthtech/d9986657-5dab-47d9-8de3-b586d2f1f7a3/scratchpad/pptr`; `npm i puppeteer-core` there if missing; Chrome at `/Applications/Google Chrome.app/Contents/MacOS/Google Chrome`, `--ignore-certificate-errors`, viewport 1440×1200). Script `gallery-check.js` visiting `/product/clonex-mist/`, asserting PASS/FAIL:
1. `.product-gallery__slides.slick-initialized` exists; `[data-gallery-arrows] .slick-next` exists; `.slick-prev` has `slick-disabled`.
2. Click `[data-gallery-thumb="2"]` → after 500ms the `.slick-current` slide is the third; thumb 2 has `is-active`.
3. Click `[data-gallery-zoom]` → `[data-gallery-lightbox]` not hidden, `body` has `product-lightbox-open`, `[data-lightbox-img]` src ends with the third image's filename, `document.activeElement` is the close button.
4. Press ArrowLeft → img src changes to the second image; press Escape → lightbox hidden, activeElement is the zoom button, body class removed.
5. On `/product/budget-propagator/`: no `.slick-initialized`, no arrows; click zoom → lightbox opens with the single image.
Paste the output in the report; fix real defects in the JS (not the checks).

- [ ] **Step 6: Commit**

```bash
git add gt_system/themes/growth_tech/assets/js/product-gallery.js gt_system/themes/growth_tech/inc/woocommerce.php tests/product-gallery.test.php
git commit -m "Add product gallery slider, thumbnails and lightbox"
```

---

### Task 6: Product tabs — The science / How to use / Specification / Useful Documents

**Files:**
- Create: `template-parts/shop/product-tabs.php`
- Create: `assets/js/product-tabs.js`
- Create: `assets/sass/components/shop/_s.tabs.scss`
- Modify: `woocommerce/single-product.php` (replace the `TASK 6` marker)
- Modify: `inc/woocommerce.php` (enqueue tabs script on `is_product()`)
- Modify: `assets/sass/main.scss`
- Create: `tests/product-tabs.test.php`

**Interfaces:**
- Consumes Task 1 product fields. Produces `template-parts/shop/product-tabs.php` (`$args['product']`), which renders nothing when no tab has content; tab set is filterable via `apply_filters( 'gt_product_tabs', $tabs, $product )` where `$tabs` is `key => ['label' => string, 'html' => string]`. Markup hooks: `[data-product-tabs]`, `[role=tablist]` with `button[role=tab][data-tab=key]`, `section[role=tabpanel][data-tab-panel=key]` each holding `button[data-tab-acc]` + `.product-tabs__body`. Script handle `gt-product-tabs`.

- [ ] **Step 1: Write the failing test**

`tests/product-tabs.test.php`:

```php
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
gt_assert_equal( 4, substr_count( $html, 'data-tab-acc' ), 'accordion heading per panel' );
gt_assert_contains( 'assets/js/product-tabs.js', $html, 'tabs script enqueued' );
// Non-first panels are hidden server-side so no-JS shows the first tab's content.
gt_assert_contains( 'data-tab-panel="how-to-use" hidden', $html, 'later panels hidden by default' );

$html = gt_fetch( '/product/budget-propagator/' );
gt_assert_not_contains( 'data-product-tabs', $html, 'no tabs when every tab is empty' );

gt_test_done();
```

Run: `tests/bin/wpx eval-file tests/product-tabs.test.php` → FAIL.

- [ ] **Step 2: `template-parts/shop/product-tabs.php`**

```php
<?php
/**
 * Product tabs — Figma 384:1894. The science / How to use / Specification /
 * Useful Documents. Tabs without content are left out; nothing renders when
 * all are empty. Under 768px the same markup works as an accordion
 * (product-tabs.js).
 *
 * @param array $args ['product' => WC_Product]
 */

$product = isset( $args['product'] ) ? $args['product'] : null;
if ( ! $product instanceof WC_Product || ! function_exists( 'get_field' ) ) {
	return;
}
$id   = $product->get_id();
$tabs = array();

$science = get_field( 'science', $id );
if ( is_array( $science ) ) {
	$columns = '';
	foreach ( $science as $column ) {
		$heading = ! empty( $column['heading'] ) ? $column['heading'] : '';
		$text    = ! empty( $column['text'] ) ? $column['text'] : '';
		if ( ! $heading && ! trim( wp_strip_all_tags( $text ) ) ) {
			continue;
		}
		$columns .= '<div class="product-tabs__column">';
		if ( $heading ) {
			$columns .= '<h3 class="product-tabs__heading">' . esc_html( $heading ) . '</h3>';
		}
		if ( $text ) {
			$columns .= '<div class="product-tabs__text">' . wp_kses_post( $text ) . '</div>';
		}
		$columns .= '</div>';
	}
	if ( $columns ) {
		$tabs['science'] = array( 'label' => __( 'The science', 'gt' ), 'html' => '<div class="product-tabs__columns">' . $columns . '</div>' );
	}
}

$how = (string) get_field( 'how_to_use', $id );
if ( trim( wp_strip_all_tags( $how ) ) ) {
	$tabs['how-to-use'] = array( 'label' => __( 'How to use', 'gt' ), 'html' => '<div class="product-tabs__text product-tabs__text--single">' . wp_kses_post( $how ) . '</div>' );
}

$spec = get_field( 'specification', $id );
if ( is_array( $spec ) ) {
	$rows = '';
	foreach ( $spec as $row ) {
		$label = ! empty( $row['label'] ) ? $row['label'] : '';
		$value = ! empty( $row['value'] ) ? $row['value'] : '';
		if ( ! $label && ! $value ) {
			continue;
		}
		$rows .= '<div class="product-spec__row"><dt class="product-spec__label">' . esc_html( $label ) . '</dt><dd class="product-spec__value">' . esc_html( $value ) . '</dd></div>';
	}
	if ( $rows ) {
		$tabs['specification'] = array( 'label' => __( 'Specification', 'gt' ), 'html' => '<dl class="product-spec">' . $rows . '</dl>' );
	}
}

$docs = get_field( 'documents', $id );
if ( is_array( $docs ) ) {
	$links = '';
	foreach ( $docs as $doc ) {
		$file_id = ! empty( $doc['file'] ) ? (int) $doc['file'] : 0;
		$url     = $file_id ? wp_get_attachment_url( $file_id ) : '';
		if ( ! $url ) {
			continue;
		}
		$label = ! empty( $doc['label'] ) ? $doc['label'] : get_the_title( $file_id );
		ob_start();
		gt_icon_svg( 'download' );
		$icon   = ob_get_clean();
		$links .= '<li class="product-docs__item"><a class="product-docs__link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener"><span class="product-docs__icon" aria-hidden="true">' . $icon . '</span><span>' . esc_html( $label ) . '</span></a></li>';
	}
	if ( $links ) {
		$tabs['documents'] = array( 'label' => __( 'Useful Documents', 'gt' ), 'html' => '<ul class="product-docs">' . $links . '</ul>' );
	}
}

$tabs = apply_filters( 'gt_product_tabs', $tabs, $product );
if ( ! $tabs ) {
	return;
}
$base = 'product-tabs-' . $id;
$keys = array_keys( $tabs );
?>
<div class="product-tabs" data-product-tabs>
	<div class="product-tabs__list" role="tablist" aria-label="<?php esc_attr_e( 'Product information', 'gt' ); ?>">
		<?php foreach ( $keys as $i => $key ) : ?>
			<button type="button" class="product-tabs__tab<?php echo 0 === $i ? ' is-active' : ''; ?>" role="tab"
				id="<?php echo esc_attr( "{$base}-tab-{$key}" ); ?>"
				aria-controls="<?php echo esc_attr( "{$base}-panel-{$key}" ); ?>"
				data-tab="<?php echo esc_attr( $key ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"
				tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>"><?php echo esc_html( $tabs[ $key ]['label'] ); ?></button>
		<?php endforeach; ?>
	</div>

	<?php foreach ( $keys as $i => $key ) : ?>
		<section class="product-tabs__panel<?php echo 0 === $i ? ' is-open' : ''; ?>" role="tabpanel"
			id="<?php echo esc_attr( "{$base}-panel-{$key}" ); ?>"
			aria-labelledby="<?php echo esc_attr( "{$base}-tab-{$key}" ); ?>"
			data-tab-panel="<?php echo esc_attr( $key ); ?>"<?php echo 0 === $i ? '' : ' hidden'; ?>>
			<button type="button" class="product-tabs__acc" data-tab-acc aria-expanded="<?php echo 0 === $i ? 'true' : 'false'; ?>"
				aria-controls="<?php echo esc_attr( "{$base}-body-{$key}" ); ?>">
				<span><?php echo esc_html( $tabs[ $key ]['label'] ); ?></span>
				<svg class="product-tabs__chevron" viewBox="0 0 10 6" width="10" height="6" aria-hidden="true" focusable="false"><path fill="currentColor" d="M5.00211 0L5.4783 0.48L10 5.03788L9.04551 6L8.56932 5.52L5 1.92212L1.43068 5.52L0.954488 6L0 5.03788L0.47619 4.55788L4.5217 0.48L4.99789 0H5.00211Z"/></svg>
			</button>
			<div class="product-tabs__body" id="<?php echo esc_attr( "{$base}-body-{$key}" ); ?>">
				<?php echo $tabs[ $key ]['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- built above from escaped pieces. ?>
			</div>
		</section>
	<?php endforeach; ?>
</div>
```

- [ ] **Step 3: Wire it into `woocommerce/single-product.php`** — replace `<?php /* TASK 6: tabs go here */ ?>` with:

```php
		<div class="product-page__tabs">
			<?php get_template_part( 'template-parts/shop/product-tabs', null, array( 'product' => $product ) ); ?>
		</div>
```

- [ ] **Step 4: `assets/js/product-tabs.js`**

```js
/**
 * Product tabs: a tablist on wide screens, an accordion under 768px. The
 * markup is the same; the mode only changes which controls drive it.
 */
(function () {
	'use strict';

	var mq = window.matchMedia('(max-width: 767px)');

	document.querySelectorAll('[data-product-tabs]').forEach(function (root) {
		var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
		var panels = Array.prototype.slice.call(root.querySelectorAll('[role="tabpanel"]'));
		var active = 0;

		function panelFor(tab) {
			return root.querySelector('[data-tab-panel="' + tab.getAttribute('data-tab') + '"]');
		}

		function select(index, focus) {
			active = index;
			tabs.forEach(function (tab, i) {
				var on = i === index;
				tab.classList.toggle('is-active', on);
				tab.setAttribute('aria-selected', on ? 'true' : 'false');
				tab.setAttribute('tabindex', on ? '0' : '-1');
				var panel = panelFor(tab);
				if (panel) {
					panel.hidden = !on;
					panel.classList.toggle('is-open', on);
					var acc = panel.querySelector('[data-tab-acc]');
					if (acc) { acc.setAttribute('aria-expanded', on ? 'true' : 'false'); }
				}
			});
			if (focus) { tabs[index].focus(); }
		}

		function applyMode() {
			var accordion = mq.matches;
			root.classList.toggle('product-tabs--accordion', accordion);
			if (accordion) {
				// Every panel is present; each one opens/closes on its own.
				panels.forEach(function (panel) { panel.hidden = false; });
			} else {
				select(active, false);
			}
		}

		tabs.forEach(function (tab, i) {
			tab.addEventListener('click', function () { select(i, false); });
			tab.addEventListener('keydown', function (event) {
				var next = null;
				if (event.key === 'ArrowRight') { next = (i + 1) % tabs.length; }
				if (event.key === 'ArrowLeft') { next = (i - 1 + tabs.length) % tabs.length; }
				if (event.key === 'Home') { next = 0; }
				if (event.key === 'End') { next = tabs.length - 1; }
				if (next !== null) { event.preventDefault(); select(next, true); }
			});
		});

		root.addEventListener('click', function (event) {
			var acc = event.target.closest('[data-tab-acc]');
			if (!acc || !root.classList.contains('product-tabs--accordion')) { return; }
			var panel = acc.closest('[role="tabpanel"]');
			var open = !panel.classList.contains('is-open');
			panel.classList.toggle('is-open', open);
			acc.setAttribute('aria-expanded', open ? 'true' : 'false');
		});

		applyMode();
		if (mq.addEventListener) { mq.addEventListener('change', applyMode); } else { mq.addListener(applyMode); }
	});
})();
```

- [ ] **Step 5: Enqueue — in `inc/woocommerce.php` `gt_product_enqueue_scripts()` add after the gallery enqueue:**

```php
	wp_enqueue_script(
		'gt-product-tabs',
		get_template_directory_uri() . '/assets/js/product-tabs.js',
		array(),
		gt_asset_version( '/assets/js/product-tabs.js' ),
		true
	);
```

- [ ] **Step 6: SCSS — `assets/sass/components/shop/_s.tabs.scss`**

```scss
// ---------------------------------------------------------------------------
// Product tabs — Figma 384:1894
//
// 90px below the summary row. Tabs are 15px medium with a 1px rule under
// each (black when active, 25% black otherwise) and 25px rule spacers; the
// panel sits 50 below with a 25px inset and two 550px columns. Under 768px
// the tablist hides and each panel's own heading toggles it (accordion).
// ---------------------------------------------------------------------------

$tabs-font: "DM Sans", #{$primary_font};
$tabs-rule: rgba(0, 0, 0, 0.25);

.product-page__tabs {
  margin-top: 90px;
}

.product-tabs {
  display: flex;
  flex-direction: column;
  gap: 50px;

  &__list {
    display: flex;
    align-items: stretch;
    width: 100%;

    // The rule continues to the right edge.
    &::after {
      content: "";
      flex: 1 1 auto;
      border-bottom: 1px solid $tabs-rule;
    }
  }

  &__tab {
    flex: 0 0 auto;
    padding: 10px 5px;
    margin-right: 25px;
    border-bottom: 1px solid $tabs-rule;
    font-family: $tabs-font;
    font-size: 15px;
    font-weight: $font-500;
    line-height: normal;
    color: $tabs-rule;
    cursor: pointer;
    transition: color 0.2s ease, border-color 0.2s ease;

    // The 25px spacer between tabs is part of the rule too.
    position: relative;

    &::after {
      content: "";
      position: absolute;
      right: -25px;
      bottom: -1px;
      width: 25px;
      border-bottom: 1px solid $tabs-rule;
    }

    &:hover,
    &:focus-visible {
      color: #000;
    }

    &.is-active {
      color: #000;
      border-bottom-color: #000;
    }

    &:focus-visible {
      outline: 2px solid #000;
      outline-offset: 2px;
    }
  }

  &__panel {
    padding: 0 25px;

    &[hidden] {
      display: none;
    }
  }

  &__acc {
    display: none;
  }

  &__columns {
    display: flex;
    justify-content: space-between;
    gap: 40px;
  }

  &__column {
    display: flex;
    flex: 0 1 550px;
    flex-direction: column;
    gap: 20px;
    max-width: 550px;
  }

  &__heading {
    margin: 0;
    font-family: "Cormorant Garamond", serif;
    font-size: 35px;
    font-weight: $font-700;
    line-height: normal;
    letter-spacing: -0.7px;
    color: #000;
  }

  &__text {
    p,
    li {
      margin: 0 0 12px;
      font-family: $tabs-font;
      font-size: 15px;
      font-weight: $font-300;
      line-height: 25px;
      color: rgba(0, 0, 0, 0.75);
    }

    p:last-child {
      margin-bottom: 0;
    }

    ul,
    ol {
      margin: 0 0 12px;
      padding-left: 20px;

      li {
        list-style-position: outside;
      }
    }

    &--single {
      max-width: 800px;
    }
  }
}

// Specification — label / value rows.
.product-spec {
  display: flex;
  flex-direction: column;
  max-width: 800px;
  margin: 0;

  &__row {
    display: flex;
    gap: 30px;
    padding: 12px 0;
    border-bottom: 1px solid $tabs-rule;

    &:first-child {
      border-top: 1px solid $tabs-rule;
    }
  }

  &__label,
  &__value {
    margin: 0;
    font-family: $tabs-font;
    font-size: 15px;
    line-height: 25px;
  }

  &__label {
    flex: 0 0 220px;
    font-weight: $font-700;
    color: #000;
  }

  &__value {
    flex: 1 1 auto;
    font-weight: $font-300;
    color: rgba(0, 0, 0, 0.75);
  }
}

// Useful documents — same row style as the summary downloads.
.product-docs {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin: 0;
  padding: 0;
  list-style: none;

  &__item {
    margin: 0;
  }

  &__link {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-family: $tabs-font;
    font-size: 15px;
    font-weight: $font-400;
    line-height: normal;
    color: #000;
    text-decoration: none;

    span {
      font: inherit;
    }

    &:hover span:last-child,
    &:focus-visible span:last-child {
      text-decoration: underline;
    }
  }

  &__icon {
    display: block;
    width: 14px;
    height: 18px;

    svg {
      display: block;
      width: 14px;
      height: 18px;
    }
  }
}

@media (max-width: $md - 1px) {
  .product-page__tabs {
    margin-top: 60px;
  }

  .product-tabs {
    gap: 35px;

    &__panel {
      padding: 0;
    }

    &__columns {
      flex-direction: column;
      gap: 30px;
    }

    &__column {
      flex-basis: auto;
      max-width: none;
    }

    &__heading {
      font-size: 30px;
      letter-spacing: -0.6px;
    }
  }

  .product-spec__label {
    flex-basis: 160px;
  }
}

// -- accordion under 768px --------------------------------------------------
@media (max-width: $sm - 1px) {
  .product-page__tabs {
    margin-top: 45px;
  }

  .product-tabs {
    gap: 0;

    &__list {
      display: none;
    }

    &__panel {
      border-top: 1px solid $tabs-rule;

      &:last-child {
        border-bottom: 1px solid $tabs-rule;
      }
    }

    &__acc {
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
      padding: 16px 0;
      cursor: pointer;
      text-align: left;
      font-family: $tabs-font;
      font-size: 15px;
      font-weight: $font-500;
      line-height: normal;
      color: #000;

      span {
        font: inherit;
      }
    }

    &__chevron {
      flex: 0 0 auto;
      width: 10px;
      height: 6px;
      color: rgba(0, 0, 0, 0.5);
      transform: rotate(180deg);
      transition: transform 0.25s ease;
    }

    &__panel.is-open &__chevron {
      transform: none;
    }

    &__body {
      padding: 0 0 20px;
    }

    &--accordion &__panel:not(.is-open) &__body {
      display: none;
    }
  }

  .product-spec__row {
    flex-direction: column;
    gap: 4px;
  }

  .product-spec__label {
    flex-basis: auto;
  }
}

@media (prefers-reduced-motion: reduce) {
  .product-tabs__tab,
  .product-tabs__chevron {
    transition: none;
  }
}
```

(`$font-300` exists in `core/_settings.scss`? It defines 400–900 only — add `$font-300: 300;` to `assets/sass/core/_settings.scss` next to `$font-400`. DM Sans 300 is already in the Google Fonts request.)

- [ ] **Step 7: Import (`@import "components/shop/s.tabs";` in the `//Shop` block), compile, run the test**

Run: `tests/bin/wpx eval-file tests/product-tabs.test.php` → `Success: 18 assertions passed`; `tests/run.sh` green.

- [ ] **Step 8: Browser check (puppeteer-core, same setup as Task 5)** — `tabs-check.js`: at 1440, only the first panel is visible; click "Specification" → its panel visible, aria-selected moves, others hidden; focus the tab and press ArrowRight → next tab selected and focused; at 390 viewport, `product-tabs--accordion` is on the root, all four accordion headings are visible, the first body visible, click the second heading → its body visible and aria-expanded true. Paste the output.

- [ ] **Step 9: Commit**

```bash
git add gt_system/themes/growth_tech/template-parts/shop/product-tabs.php gt_system/themes/growth_tech/assets/js/product-tabs.js gt_system/themes/growth_tech/assets/sass/components/shop/_s.tabs.scss gt_system/themes/growth_tech/assets/sass/core/_settings.scss gt_system/themes/growth_tech/woocommerce/single-product.php gt_system/themes/growth_tech/inc/woocommerce.php gt_system/themes/growth_tech/assets/sass/main.scss gt_system/themes/growth_tech/assets/css/main.css gt_system/themes/growth_tech/assets/css/main.css.map tests/product-tabs.test.php
git commit -m "Add product information tabs with a mobile accordion"
```

---

### Task 7: "Complete the system" and the knowledge band

**Files:**
- Create: `template-parts/shop/complete-system.php`
- Create: `template-parts/shop/knowledge-band.php`
- Create: `assets/sass/components/shop/_s.slider-controls.scss`
- Create: `assets/sass/components/shop/_s.related.scss`
- Create: `assets/sass/components/shop/_s.knowledge.scss`
- Modify: `woocommerce/single-product.php` (replace both `TASK 7` markers)
- Modify: `assets/sass/main.scss`
- Create: `tests/product-related.test.php`

**Interfaces:**
- Consumes `gt_product_related()`, `gt_knowledge_band()`, `content-product.php`, `gt-block-slider` (registered in Plan 1: `wp_enqueue_script( 'gt-block-slider' )` when needed).
- Produces `template-parts/shop/complete-system.php` (`$args['product']`, optional `$args['items']` = WC_Product[] override for tests) and `template-parts/shop/knowledge-band.php` (`$args['product_id']`). Shared slider control styles `.shop-slider__arrows` and `.shop-slider__progress` (used again by the brand page).

- [ ] **Step 1: Write the failing test**

`tests/product-related.test.php`:

```php
<?php
require_once __DIR__ . '/lib/bootstrap.php';

$html = gt_fetch( '/product/clonex-mist/' );
gt_assert_contains( 'class="product-related__title">Complete the system<', $html, 'related heading' );
$start = strpos( $html, 'class="product-related' );
$end   = strpos( $html, 'class="knowledge-band', $start );
$block = substr( $html, $start, $end - $start );
gt_assert_equal( 3, substr_count( $block, '<li class="product-card ' ), 'three same-brand products' );
gt_assert_not_contains( '>Clonex Mist</h2>', $block, 'current product excluded' );
gt_assert_contains( 'href="' . wc_get_page_permalink( 'shop' ) . '"', $block, 'View all Products links to the shop' );
gt_assert_not_contains( 'data-block-slider', $block, 'four or fewer → static grid, no slider' );
gt_assert_not_contains( 'data-slider-arrows', $block, 'no arrows without a slider' );

gt_assert_contains( 'class="knowledge-band"', $html, 'knowledge band rendered' );
gt_assert_contains( 'knowledge-band__title">Better knowledge. Stronger roots.<', $html, 'knowledge heading from Theme Settings' );
gt_assert_contains( 'Explore the Plant Academy', $html, 'knowledge button label' );

$html = gt_fetch( '/product/root-riot/' );
$start = strpos( $html, 'class="product-related' );
$block = substr( $html, $start, 6000 );
gt_assert_contains( '>Clonex Mist</h2>', $block, 'upsells drive related when set' );
gt_assert_contains( '>Clonex Rooting Hormone</h2>', $block, 'second upsell' );
gt_assert_equal( 2, substr_count( $block, '<li class="product-card ' ), 'only the upsells' );

// Slider mode with more than four items (rendered directly with an override).
$products = wc_get_products( array( 'limit' => 5, 'status' => 'publish', 'orderby' => 'ID', 'order' => 'ASC' ) );
$GLOBALS['product'] = $products[0];
ob_start();
get_template_part( 'template-parts/shop/complete-system', null, array( 'product' => $products[0], 'items' => $products ) );
$part = ob_get_clean();
gt_assert_contains( 'data-block-slider', $part, 'five items → slider' );
gt_assert_contains( 'data-slides="4"', $part, 'slider shows four at desktop' );
gt_assert_contains( 'data-slider-arrows', $part, 'arrows target in the header' );
gt_assert_equal( 5, substr_count( $part, '<li class="product-card ' ), 'all five rendered' );

// No related products → section absent.
$smc = wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'sku' => 'GT-013' ) )[0];
ob_start();
get_template_part( 'template-parts/shop/complete-system', null, array( 'product' => $smc ) );
gt_assert_equal( '', trim( ob_get_clean() ), 'Nitrozyme (alone in its brand, no upsells) renders no section' );

gt_test_done();
```

Run: `tests/bin/wpx eval-file tests/product-related.test.php` → FAIL.

- [ ] **Step 2: `template-parts/shop/complete-system.php`**

```php
<?php
/**
 * "Complete the system" — Figma 384:1915. Upsells (or the rest of the
 * brand) as product cards on a grey band; more than four becomes a slider
 * with the arrows in the header.
 *
 * @param array $args ['product' => WC_Product, 'items' => WC_Product[] (optional override)]
 */

$product = isset( $args['product'] ) ? $args['product'] : null;
if ( ! $product instanceof WC_Product ) {
	return;
}
$items = isset( $args['items'] ) && is_array( $args['items'] ) ? $args['items'] : gt_product_related( $product, 12 );
if ( ! $items ) {
	return;
}
$is_slider = count( $items ) > 4;
if ( $is_slider ) {
	wp_enqueue_script( 'gt-block-slider' );
}
$title_id = 'product-related-' . $product->get_id();
?>
<section class="product-related" aria-labelledby="<?php echo esc_attr( $title_id ); ?>" data-slider-scope>
	<div class="product-related__inner">
		<div class="product-related__head">
			<h2 class="product-related__title" id="<?php echo esc_attr( $title_id ); ?>"><?php esc_html_e( 'Complete the system', 'gt' ); ?></h2>
			<div class="product-related__tools">
				<a class="product-related__all" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'View all Products', 'gt' ); ?></a>
				<?php if ( $is_slider ) : ?>
					<div class="shop-slider__arrows" data-slider-arrows></div>
				<?php endif; ?>
			</div>
		</div>

		<ul class="product-related__grid<?php echo $is_slider ? ' product-related__grid--slider' : ''; ?>"
			<?php if ( $is_slider ) : ?>
				data-block-slider data-slides="4" data-slides-md="2" data-slides-sm="1" data-arrows="1" data-dots="0"
			<?php endif; ?>>
			<?php
			$current = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
			foreach ( $items as $item ) {
				$GLOBALS['product'] = $item; // content-product.php reads the global.
				wc_get_template_part( 'content', 'product' );
			}
			$GLOBALS['product'] = $current;
			?>
		</ul>
	</div>
</section>
```

- [ ] **Step 3: `template-parts/shop/knowledge-band.php`**

```php
<?php
/**
 * "Better knowledge. Stronger roots." band — Figma 384:1958. Theme Settings
 * defaults with a per-product override (gt_knowledge_band()).
 *
 * @param array $args ['product_id' => int]
 */

$band = gt_knowledge_band( isset( $args['product_id'] ) ? (int) $args['product_id'] : 0 );
if ( ! $band ) {
	return;
}
?>
<section class="knowledge-band" aria-label="<?php echo esc_attr( $band['heading'] ); ?>">
	<?php
	if ( $band['image'] ) {
		echo wp_get_attachment_image( $band['image'], 'gt-knowledge', false, array(
			'class'   => 'knowledge-band__img',
			'alt'     => '',
			'sizes'   => '(max-width: 1439px) calc(100vw - 100px), 1340px',
			'loading' => 'lazy',
		) );
	}
	?>
	<span class="knowledge-band__scrim" aria-hidden="true"></span>
	<div class="knowledge-band__content">
		<h2 class="knowledge-band__title"><?php echo esc_html( $band['heading'] ); ?></h2>
		<?php if ( $band['text'] ) : ?>
			<p class="knowledge-band__text"><?php echo wp_kses_post( $band['text'] ); ?></p>
		<?php endif; ?>
		<?php if ( $band['link'] ) : ?>
			<a class="btn-flat" href="<?php echo esc_url( $band['link']['url'] ); ?>"
				<?php echo ! empty( $band['link']['target'] ) ? 'target="' . esc_attr( $band['link']['target'] ) . '" rel="noopener"' : ''; ?>>
				<span><?php echo esc_html( ! empty( $band['link']['title'] ) ? $band['link']['title'] : __( 'Explore the Plant Academy', 'gt' ) ); ?></span>
				<?php gt_arrow_svg(); ?>
			</a>
		<?php endif; ?>
	</div>
</section>
```

- [ ] **Step 4: Wire both into `woocommerce/single-product.php`** — replace `<?php /* TASK 7: complete the system goes here */ ?>` with:

```php
	<?php get_template_part( 'template-parts/shop/complete-system', null, array( 'product' => $product ) ); ?>
```
and `<?php /* TASK 7: knowledge band goes here */ ?>` with:

```php
		<?php get_template_part( 'template-parts/shop/knowledge-band', null, array( 'product_id' => $product->get_id() ) ); ?>
```

- [ ] **Step 5: SCSS — `assets/sass/components/shop/_s.slider-controls.scss`** (shared by the product related slider and the brand steps slider; mirrors the content-slider block's controls, Figma 384:1920 / 384:1578)

```scss
// ---------------------------------------------------------------------------
// Slider controls shared by the shop pages: 35px square arrows and the
// square/bar progress indicator, driven by block-slider.js through
// [data-slider-arrows] / [data-slider-dots] targets.
// ---------------------------------------------------------------------------

$slider-muted: rgba(0, 0, 0, 0.1);

.shop-slider__arrows {
  display: flex;
  align-items: center;
  gap: 10px;

  .slick-prev,
  .slick-next {
    position: static;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    padding: 0;
    background-color: #f4f4f4;
    // _p.home.scss paints an unscoped background image on these classes.
    background-image: none;
    border: 0;
    color: #000;
    cursor: pointer;
    text-indent: 0;
    transform: none;
    transition: background-color 0.3s ease, color 0.3s ease;

    &::before,
    &::after {
      content: none;
    }

    &:not(.slick-disabled):hover,
    &:not(.slick-disabled):focus-visible {
      background-color: #393536;
      color: color(white);
    }

    &.slick-disabled {
      background-color: #000;
      color: color(white);
      opacity: 0.3;
      cursor: default;
    }
  }

  .slick-next {
    background-color: #000;
    color: color(white);
  }

  .slick-prev .block-slider__arrow {
    transform: rotate(180deg);
  }

  .block-slider__arrow {
    display: block;
    width: 18px;
    height: 15px;
  }
}

.shop-slider__progress {
  .slick-dots {
    position: static;
    display: flex;
    align-items: center;
    gap: 10px;
    width: auto;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .slick-dots li {
    width: 15px;
    height: 15px;
    margin: 0;
    line-height: 0;
    transition: width 0.3s ease, height 0.3s ease;
  }

  .slick-dots li button {
    display: block;
    width: 100%;
    height: 100%;
    padding: 0;
    overflow: hidden;
    text-indent: -9999px;
    background-color: #f4f4f4;
    border: 0;
    border-radius: 0;
    cursor: pointer;

    &::before {
      content: none;
    }
  }

  .slick-dots li.is-past button {
    background-color: #000;
  }

  .slick-dots li.slick-active {
    width: 80px;
    height: 3px;

    button {
      position: relative;
      background-color: #f4f4f4;

      &::after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        width: 50%;
        background-color: #000;
      }
    }
  }
}

@media (prefers-reduced-motion: reduce) {
  .shop-slider__arrows .slick-prev,
  .shop-slider__arrows .slick-next,
  .shop-slider__progress .slick-dots li {
    transition: none;
  }
}
```

- [ ] **Step 6: SCSS — `assets/sass/components/shop/_s.related.scss`**

```scss
// ---------------------------------------------------------------------------
// "Complete the system" — Figma 384:1915
//
// Full-bleed #F4F4F4 band, 60px vertical / 74px gutters (1292 content).
// Header: 35px Cormorant title left; "View all Products" + arrows right,
// bottom-aligned. 25 to a 4-column grid with 20px gaps.
// ---------------------------------------------------------------------------

.product-related {
  margin-top: 77px;
  padding: 60px 0;
  background-color: #f4f4f4;

  &__inner {
    display: flex;
    flex-direction: column;
    gap: 25px;
    max-width: 1440px;
    margin: 0 auto;
    padding: 0 74px;
  }

  &__head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
  }

  &__title {
    margin: 0;
    font-family: "Cormorant Garamond", serif;
    font-size: 35px;
    font-weight: $font-700;
    line-height: normal;
    letter-spacing: -0.7px;
    color: #000;
  }

  &__tools {
    display: flex;
    align-items: center;
    gap: 15px;
    padding-bottom: 3px;
  }

  &__all {
    font-family: "DM Sans", #{$primary_font};
    font-size: 14px;
    font-weight: $font-500;
    line-height: normal;
    color: #000;
    text-decoration: underline;
    text-underline-position: from-font;
  }

  &__grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
    margin: 0;
    padding: 0;
    list-style: none;

    // As a slider slick lays the cards out itself; keep the 20px gap.
    &--slider {
      display: block;

      .slick-list {
        margin: 0 -10px;
      }

      .slick-slide {
        padding: 0 10px;
      }

      .slick-slide > div {
        height: 100%;
      }
    }
  }
}

@media (max-width: $xl - 1px) {
  .product-related {
    margin-top: 60px;

    &__inner {
      padding: 0 25px;
    }

    &__grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
}

@media (max-width: $sm - 1px) {
  .product-related {
    margin-top: 45px;
    padding: 40px 0;

    &__head {
      flex-direction: column;
      align-items: flex-start;
      gap: 15px;
    }

    &__title {
      font-size: 30px;
    }

    &__grid {
      grid-template-columns: minmax(0, 1fr);
    }
  }
}
```

- [ ] **Step 7: SCSS — `assets/sass/components/shop/_s.knowledge.scss`**

```scss
// ---------------------------------------------------------------------------
// Knowledge band — Figma 384:1958
//
// 1340 x 300 black band inside the page gutters, image on the right under a
// left-to-right gradient, copy 30/50 inset, 60 to the footer.
// ---------------------------------------------------------------------------

.knowledge-band {
  position: relative;
  display: flex;
  align-items: center;
  min-height: 300px;
  margin: 77px 0 60px;
  padding: 30px 50px;
  overflow: hidden;
  background-color: #000;
  color: color(white);

  &__img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: right center;
  }

  &__scrim {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, #000 40.865%, rgba(0, 0, 0, 0) 82.692%);
  }

  &__content {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 25px;
    max-width: 622px;
  }

  &__title {
    margin: 0;
    font-family: "Cormorant Garamond", serif;
    font-size: 60px;
    font-weight: $font-700;
    line-height: normal;
    letter-spacing: -1.2px;
    color: color(white);
  }

  &__text {
    margin: -15px 0 0;
    font-family: "DM Sans", #{$primary_font};
    font-size: 15px;
    font-weight: $font-400;
    line-height: 25px;
    color: rgba(255, 255, 255, 0.75);
  }
}

@media (max-width: $md - 1px) {
  .knowledge-band {
    margin: 60px 0 50px;
    padding: 30px;

    &__scrim {
      background: linear-gradient(90deg, #000 30%, rgba(0, 0, 0, 0.35) 100%);
    }

    &__title {
      font-size: 44px;
      letter-spacing: -0.9px;
    }
  }
}

@media (max-width: $sm - 1px) {
  .knowledge-band {
    margin: 45px 0 40px;
    padding: 30px 25px;

    &__scrim {
      background: rgba(0, 0, 0, 0.7);
    }

    &__title {
      font-size: 34px;
      letter-spacing: -0.7px;
    }

    &__text {
      margin-top: -10px;
    }
  }
}
```

- [ ] **Step 8: Import the three partials in `main.scss` (`s.slider-controls`, `s.related`, `s.knowledge` in the `//Shop` block), compile, run the test**

Run: `tests/bin/wpx eval-file tests/product-related.test.php` → `Success: 17 assertions passed`; `tests/run.sh` green.

- [ ] **Step 9: Look at the full page** — screenshot `/product/clonex-mist/` at 1440×2600 and compare with Figma 384:1816 (spec §8 tokens): tabs rule, grey related band edge-to-edge with 74px gutters and four cards, knowledge band 300 tall with the gradient. Fix drift, recompile.

- [ ] **Step 10: Commit**

```bash
git add gt_system/themes/growth_tech/template-parts/shop/complete-system.php gt_system/themes/growth_tech/template-parts/shop/knowledge-band.php gt_system/themes/growth_tech/assets/sass/components/shop/_s.slider-controls.scss gt_system/themes/growth_tech/assets/sass/components/shop/_s.related.scss gt_system/themes/growth_tech/assets/sass/components/shop/_s.knowledge.scss gt_system/themes/growth_tech/woocommerce/single-product.php gt_system/themes/growth_tech/assets/sass/main.scss gt_system/themes/growth_tech/assets/css/main.css gt_system/themes/growth_tech/assets/css/main.css.map tests/product-related.test.php
git commit -m "Add Complete the system and knowledge band to the product page"
```

---

### Task 8: Brand landing page

**Files:**
- Create: `woocommerce/taxonomy-product_brand.php`
- Create: `template-parts/brand/hero.php`, `template-parts/brand/science.php`, `template-parts/brand/range.php`, `template-parts/brand/pair.php`, `template-parts/brand/guides.php`
- Create: `assets/images/icons/plus.svg`
- Create: `assets/sass/components/shop/_s.brand.scss`
- Modify: `assets/sass/main.scss`
- Create: `tests/brand-page.test.php`

**Interfaces:**
- Consumes: Task 1 brand fields, `gt_term_field`, `gt_brand_accent`, `gt_brand_stockist_url`, `gt_shop_render_loop( $wp_query, array() )`, `gt_shop_count_text`, `content-product.php`, `gt-block-slider` + `.shop-slider__*` (Task 7), `gt_arrow_svg`, `gt_icon_svg`. Brand archives already return every product (Task 3).
- Produces: `taxonomy-product_brand.php` → `<main class="page-wrapper brand-page" style="--brand-accent: …">` with parts: hero (always), science (when copy/steps/FAQ exist), range (`#range`, always), pair (when a paired brand or heading is set), guides (when heading or cards exist). Each part takes `$args['brand']` (WP_Term).

- [ ] **Step 1: Write the failing test**

`tests/brand-page.test.php`:

```php
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
gt_assert_contains( 'STEP 2 • MIST', $html, 'step label' );
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
```

Run: `tests/bin/wpx eval-file tests/brand-page.test.php` → FAIL (archive template renders).

- [ ] **Step 2: `assets/images/icons/plus.svg`** (Figma 384:1606 — glyph only; the white square is CSS)

```svg
<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill="black" d="M16.0714 8.57143V7.5H13.9286V13.9286H7.5V16.0714H13.9286V22.5H16.0714V16.0714H22.5V13.9286H16.0714V8.57143Z"/></svg>
```

- [ ] **Step 3: `woocommerce/taxonomy-product_brand.php`**

```php
<?php
/**
 * Brand landing page — Figma 384:1489 (Brand Range Template).
 *
 * Hero, "Rooted in science" (steps slider + FAQ), the brand's range, a
 * cross-sell band and a guides band. Optional sections render nothing when
 * their term fields are empty; the range always shows.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$brand  = get_queried_object();
$brand  = $brand instanceof WP_Term ? $brand : null;
$accent = $brand ? gt_brand_accent( $brand ) : '#FBC707';
?>

<main class="page-wrapper brand-page" style="--brand-accent: <?php echo esc_attr( $accent ); ?>">
	<?php if ( $brand ) : ?>
		<?php get_template_part( 'template-parts/brand/hero', null, array( 'brand' => $brand ) ); ?>
		<?php get_template_part( 'template-parts/brand/science', null, array( 'brand' => $brand ) ); ?>
		<?php get_template_part( 'template-parts/brand/range', null, array( 'brand' => $brand ) ); ?>
		<?php get_template_part( 'template-parts/brand/pair', null, array( 'brand' => $brand ) ); ?>
		<?php get_template_part( 'template-parts/brand/guides', null, array( 'brand' => $brand ) ); ?>
	<?php endif; ?>

<?php
get_footer();
```

- [ ] **Step 4: `template-parts/brand/hero.php`**

```php
<?php
/**
 * Brand hero — Figma 384:1492 / 384:1497. Full-bleed black, image right,
 * logo + two-line headline + intro + CTAs left. Falls back to the brand
 * name and description when no landing copy is set.
 *
 * @param array $args ['brand' => WP_Term]
 */

$brand = isset( $args['brand'] ) ? $args['brand'] : null;
if ( ! $brand instanceof WP_Term ) {
	return;
}
$logo_id  = (int) gt_term_field( 'logo', $brand, 0 );
$heading  = (string) gt_term_field( 'hero_heading', $brand, '' );
$accent   = (string) gt_term_field( 'hero_accent_line', $brand, '' );
$intro    = (string) gt_term_field( 'hero_intro', $brand, $brand->description );
$image_id = (int) gt_term_field( 'hero_image', $brand, 0 );
$stockist = gt_brand_stockist_url( $brand );
$has_copy = '' !== $heading;
/* translators: %s: brand name */
$explore = sprintf( __( 'Explore the %s Range', 'gt' ), $brand->name );
?>
<section class="brand-hero<?php echo $image_id ? '' : ' brand-hero--plain'; ?>" aria-labelledby="brand-hero-title">
	<?php
	if ( $image_id ) {
		echo wp_get_attachment_image( $image_id, 'gt-brand-hero', false, array(
			'class' => 'brand-hero__img',
			'alt'   => '',
			'sizes' => '100vw',
		) );
	}
	?>
	<span class="brand-hero__scrim" aria-hidden="true"></span>
	<div class="brand-hero__inner">
		<div class="brand-hero__content">
			<?php if ( $logo_id ) : ?>
				<?php echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'brand-hero__logo', 'alt' => $brand->name ) ); ?>
			<?php endif; ?>

			<h1 class="brand-hero__title" id="brand-hero-title"><?php
				if ( $has_copy ) {
					echo esc_html( $heading );
					if ( $accent ) {
						echo ' <em class="brand-hero__accent">' . esc_html( $accent ) . '</em>';
					}
				} else {
					echo esc_html( $brand->name );
				}
			?></h1>

			<?php if ( $intro ) : ?>
				<p class="brand-hero__intro"><?php echo wp_kses_post( $intro ); ?></p>
			<?php endif; ?>

			<div class="brand-hero__actions">
				<?php if ( $stockist ) : ?>
					<a class="btn-flat brand-hero__stockist" href="<?php echo esc_url( $stockist ); ?>">
						<span><?php esc_html_e( 'Find a stockist', 'gt' ); ?></span>
						<?php gt_arrow_svg(); ?>
					</a>
				<?php endif; ?>
				<a class="brand-hero__explore" href="#range"><?php echo esc_html( $explore ); ?></a>
			</div>
		</div>
	</div>
</section>
```

- [ ] **Step 5: `template-parts/brand/science.php`**

```php
<?php
/**
 * "Rooted in science" — Figma 384:1494 / 384:1564 / 384:1508. Steps slider
 * left, heading + copy + FAQ callout right. Skipped entirely when the brand
 * has no copy, steps or FAQ.
 *
 * @param array $args ['brand' => WP_Term]
 */

$brand = isset( $args['brand'] ) ? $args['brand'] : null;
if ( ! $brand instanceof WP_Term ) {
	return;
}
$heading = (string) gt_term_field( 'science_heading', $brand, '' );
$accent  = (string) gt_term_field( 'science_accent_line', $brand, '' );
$text    = (string) gt_term_field( 'science_text', $brand, '' );
$steps   = gt_term_field( 'steps', $brand, array() );
$faq     = gt_term_field( 'faq', $brand, array() );
$steps   = is_array( $steps ) ? array_values( array_filter( $steps, function ( $s ) {
	return ! empty( $s['image'] ) || ! empty( $s['product'] ) || ! empty( $s['text'] );
} ) ) : array();
$has_faq = is_array( $faq ) && ! empty( $faq['question'] );

if ( ! $heading && ! trim( wp_strip_all_tags( $text ) ) && ! $steps && ! $has_faq ) {
	return;
}
$is_slider = count( $steps ) > 1;
if ( $is_slider ) {
	wp_enqueue_script( 'gt-block-slider' );
}
?>
<section class="brand-science" data-slider-scope>
	<div class="brand-science__inner">

		<?php if ( $steps ) : ?>
			<div class="brand-science__slider">
				<ul class="brand-steps"
					<?php if ( $is_slider ) : ?>
						data-block-slider data-slides="1" data-slides-md="1" data-slides-sm="1" data-arrows="1" data-progress="1"
					<?php endif; ?>>
					<?php foreach ( $steps as $index => $step ) : ?>
						<?php
						$product  = ! empty( $step['product'] ) ? wc_get_product( (int) $step['product'] ) : null;
						$product  = $product instanceof WC_Product ? $product : null;
						$label    = ! empty( $step['label'] ) ? $step['label'] : '';
						$image_id = ! empty( $step['image'] ) ? (int) $step['image'] : ( $product ? (int) $product->get_image_id() : 0 );
						$title    = $product ? $product->get_name() : $label;
						$url      = $product ? $product->get_permalink() : '';
						$x        = isset( $step['hotspot_x'] ) && '' !== $step['hotspot_x'] ? max( 0, min( 100, (float) $step['hotspot_x'] ) ) : 73;
						$y        = isset( $step['hotspot_y'] ) && '' !== $step['hotspot_y'] ? max( 0, min( 100, (float) $step['hotspot_y'] ) ) : 47;
						?>
						<li class="brand-steps__step">
							<div class="brand-steps__media">
								<?php
								if ( $image_id ) {
									echo wp_get_attachment_image( $image_id, 'gt-brand-step', false, array(
										'class'   => 'brand-steps__img',
										'alt'     => '',
										'sizes'   => '(max-width: 767px) calc(100vw - 50px), 429px',
										'loading' => $index > 0 ? 'lazy' : 'eager',
									) );
								}
								?>
								<?php if ( $label ) : ?>
									<span class="brand-steps__badge"><?php echo esc_html( mb_strtoupper( $label ) ); ?></span>
								<?php endif; ?>
								<?php if ( $url ) : ?>
									<a class="brand-steps__marker" href="<?php echo esc_url( $url ); ?>"
										style="left: <?php echo esc_attr( $x ); ?>%; top: <?php echo esc_attr( $y ); ?>%;"
										aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'View %s', 'gt' ), $title ) ); ?>">
										<?php gt_icon_svg( 'plus' ); ?>
									</a>
								<?php endif; ?>
							</div>
							<div class="brand-steps__body">
								<?php if ( $title ) : ?>
									<h3 class="brand-steps__title"><?php echo esc_html( $title ); ?></h3>
								<?php endif; ?>
								<?php if ( ! empty( $step['text'] ) ) : ?>
									<p class="brand-steps__text"><?php echo wp_kses_post( $step['text'] ); ?></p>
								<?php endif; ?>
								<?php if ( $url ) : ?>
									<a class="brand-steps__link" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'View Product', 'gt' ); ?></a>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php if ( $is_slider ) : ?>
					<div class="brand-steps__controls">
						<div class="shop-slider__progress" data-slider-dots></div>
						<div class="shop-slider__arrows" data-slider-arrows></div>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="brand-science__copy">
			<?php if ( $heading || $accent ) : ?>
				<h2 class="brand-science__title"><?php
					echo esc_html( $heading );
					if ( $accent ) {
						echo ' <em class="brand-science__accent">' . esc_html( $accent ) . '</em>';
					}
				?></h2>
			<?php endif; ?>

			<?php if ( trim( wp_strip_all_tags( $text ) ) ) : ?>
				<div class="brand-science__text"><?php echo wp_kses_post( $text ); ?></div>
			<?php endif; ?>

			<?php if ( $has_faq ) : ?>
				<aside class="brand-faq">
					<?php if ( ! empty( $faq['eyebrow'] ) ) : ?>
						<p class="brand-faq__eyebrow"><?php echo esc_html( mb_strtoupper( $faq['eyebrow'] ) ); ?></p>
					<?php endif; ?>
					<p class="brand-faq__question"><?php echo esc_html( $faq['question'] ); ?></p>
					<?php if ( ! empty( $faq['answer'] ) ) : ?>
						<p class="brand-faq__answer"><?php echo wp_kses_post( $faq['answer'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $faq['link']['url'] ) ) : ?>
						<a class="brand-faq__link" href="<?php echo esc_url( $faq['link']['url'] ); ?>"
							<?php echo ! empty( $faq['link']['target'] ) ? 'target="' . esc_attr( $faq['link']['target'] ) . '" rel="noopener"' : ''; ?>>
							<?php echo esc_html( ! empty( $faq['link']['title'] ) ? $faq['link']['title'] : __( 'More answers in the Plant Academy', 'gt' ) ); ?>
						</a>
					<?php endif; ?>
				</aside>
			<?php endif; ?>
		</div>

	</div>
</section>
```

- [ ] **Step 6: `template-parts/brand/range.php`**

```php
<?php
/**
 * "The {Brand} Range" — Figma 384:1526. Every product in the brand (the
 * main archive query, which Task 3 made unpaginated for brands).
 *
 * @param array $args ['brand' => WP_Term]
 */

global $wp_query;
$brand = isset( $args['brand'] ) ? $args['brand'] : null;
if ( ! $brand instanceof WP_Term ) {
	return;
}
$total = (int) $wp_query->found_posts;
/* translators: %s: brand name */
$title = sprintf( __( 'The %s Range', 'gt' ), $brand->name );
?>
<section class="brand-range" id="range" aria-labelledby="brand-range-title">
	<div class="brand-range__inner">
		<div class="brand-range__head">
			<h2 class="brand-range__title" id="brand-range-title"><?php echo esc_html( $title ); ?></h2>
			<?php if ( $total ) : ?>
				<p class="brand-range__count"><?php echo esc_html( gt_shop_count_text( $total, $total ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $wp_query->have_posts() ) : ?>
			<ul class="brand-range__grid">
				<?php gt_shop_render_loop( $wp_query, array() ); ?>
			</ul>
		<?php else : ?>
			<p class="brand-range__empty"><?php esc_html_e( 'Products from this range are coming soon.', 'gt' ); ?></p>
		<?php endif; ?>
	</div>
</section>
```

- [ ] **Step 7: `template-parts/brand/pair.php`**

```php
<?php
/**
 * Cross-sell band — Figma 384:1514. "Pair with Root Riot…": paired brand's
 * logo, heading, text, image and a button.
 *
 * @param array $args ['brand' => WP_Term]
 */

$brand = isset( $args['brand'] ) ? $args['brand'] : null;
if ( ! $brand instanceof WP_Term ) {
	return;
}
$pair_id  = (int) gt_term_field( 'pair_brand', $brand, 0 );
$pair     = $pair_id ? get_term( $pair_id, 'product_brand' ) : null;
$pair     = $pair instanceof WP_Term ? $pair : null;
$heading  = (string) gt_term_field( 'pair_heading', $brand, '' );
$text     = (string) gt_term_field( 'pair_text', $brand, '' );
$image_id = (int) gt_term_field( 'pair_image', $brand, 0 );
$link     = gt_term_field( 'pair_link', $brand, null );

if ( ! $pair && ! $heading ) {
	return;
}
if ( ! is_array( $link ) || empty( $link['url'] ) ) {
	$pair_link = $pair ? get_term_link( $pair ) : '';
	$link      = ( $pair_link && ! is_wp_error( $pair_link ) ) ? array( 'url' => $pair_link, 'title' => sprintf( /* translators: %s: brand name */ __( 'Explore %s', 'gt' ), $pair->name ), 'target' => '' ) : null;
}
$logo_id = $pair ? (int) gt_term_field( 'logo', $pair, 0 ) : 0;
?>
<section class="brand-pair" aria-label="<?php echo esc_attr( $heading ? $heading : ( $pair ? $pair->name : '' ) ); ?>">
	<div class="brand-pair__inner">
		<?php
		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, 'gt-brand-pair', false, array(
				'class'   => 'brand-pair__img',
				'alt'     => '',
				'sizes'   => '(max-width: 1439px) calc(100vw - 100px), 1340px',
				'loading' => 'lazy',
			) );
		}
		?>
		<span class="brand-pair__scrim" aria-hidden="true"></span>
		<div class="brand-pair__content">
			<?php if ( $logo_id ) : ?>
				<?php echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'brand-pair__logo', 'alt' => $pair->name ) ); ?>
			<?php elseif ( $pair ) : ?>
				<span class="brand-pair__logo brand-pair__logo--text"><?php echo esc_html( $pair->name ); ?></span>
			<?php endif; ?>
			<?php if ( $heading ) : ?>
				<h2 class="brand-pair__title"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php if ( $text ) : ?>
				<p class="brand-pair__text"><?php echo wp_kses_post( $text ); ?></p>
			<?php endif; ?>
			<?php if ( $link ) : ?>
				<a class="btn-flat" href="<?php echo esc_url( $link['url'] ); ?>"
					<?php echo ! empty( $link['target'] ) ? 'target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; ?>>
					<span><?php echo esc_html( ! empty( $link['title'] ) ? $link['title'] : __( 'View Product', 'gt' ) ); ?></span>
					<?php gt_arrow_svg(); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</section>
```

- [ ] **Step 8: `template-parts/brand/guides.php`**

```php
<?php
/**
 * Guides band — Figma 384:1589. Heading + copy + two CTAs left, two guide
 * cards right on a grey band.
 *
 * @param array $args ['brand' => WP_Term]
 */

$brand = isset( $args['brand'] ) ? $args['brand'] : null;
if ( ! $brand instanceof WP_Term ) {
	return;
}
$heading = (string) gt_term_field( 'guides_heading', $brand, '' );
$accent  = (string) gt_term_field( 'guides_accent_line', $brand, '' );
$text    = (string) gt_term_field( 'guides_text', $brand, '' );
$cta1    = gt_term_field( 'guides_cta_1', $brand, null );
$cta2    = gt_term_field( 'guides_cta_2', $brand, null );
$guides  = gt_term_field( 'guides', $brand, array() );
$guides  = is_array( $guides ) ? array_values( array_filter( $guides, function ( $g ) {
	return ! empty( $g['title'] ) || ! empty( $g['lead'] ) || ! empty( $g['image'] );
} ) ) : array();

if ( ! $heading && ! $accent && ! $guides ) {
	return;
}
?>
<section class="brand-guides" aria-labelledby="brand-guides-title">
	<div class="brand-guides__inner">
		<div class="brand-guides__copy">
			<?php if ( $heading || $accent ) : ?>
				<h2 class="brand-guides__title" id="brand-guides-title"><?php
					echo esc_html( $heading );
					if ( $accent ) {
						echo ' <em class="brand-guides__accent">' . esc_html( $accent ) . '</em>';
					}
				?></h2>
			<?php endif; ?>
			<?php if ( $text ) : ?>
				<p class="brand-guides__text"><?php echo wp_kses_post( $text ); ?></p>
			<?php endif; ?>
			<?php if ( ( is_array( $cta1 ) && ! empty( $cta1['url'] ) ) || ( is_array( $cta2 ) && ! empty( $cta2['url'] ) ) ) : ?>
				<div class="brand-guides__actions">
					<?php if ( is_array( $cta1 ) && ! empty( $cta1['url'] ) ) : ?>
						<a class="btn-flat" href="<?php echo esc_url( $cta1['url'] ); ?>"
							<?php echo ! empty( $cta1['target'] ) ? 'target="' . esc_attr( $cta1['target'] ) . '" rel="noopener"' : ''; ?>>
							<span><?php echo esc_html( ! empty( $cta1['title'] ) ? $cta1['title'] : __( 'Guides', 'gt' ) ); ?></span>
							<?php gt_arrow_svg(); ?>
						</a>
					<?php endif; ?>
					<?php if ( is_array( $cta2 ) && ! empty( $cta2['url'] ) ) : ?>
						<a class="brand-guides__link" href="<?php echo esc_url( $cta2['url'] ); ?>"
							<?php echo ! empty( $cta2['target'] ) ? 'target="' . esc_attr( $cta2['target'] ) . '" rel="noopener"' : ''; ?>>
							<?php echo esc_html( ! empty( $cta2['title'] ) ? $cta2['title'] : __( 'Explore the Plant Academy', 'gt' ) ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $guides ) : ?>
			<ul class="brand-guides__cards">
				<?php foreach ( $guides as $guide ) : ?>
					<?php
					$image_id = ! empty( $guide['image'] ) ? (int) $guide['image'] : 0;
					$link     = ! empty( $guide['link'] ) && is_array( $guide['link'] ) ? $guide['link'] : null;
					?>
					<li class="brand-guides__card">
						<?php
						if ( $image_id ) {
							echo wp_get_attachment_image( $image_id, 'gt-guide', false, array(
								'class'   => 'brand-guides__card-img',
								'alt'     => '',
								'sizes'   => '(max-width: 767px) calc(100vw - 50px), 347px',
								'loading' => 'lazy',
							) );
						}
						?>
						<div class="brand-guides__panel">
							<p class="brand-guides__card-title">
								<?php if ( ! empty( $guide['lead'] ) ) : ?><strong><?php echo esc_html( $guide['lead'] ); ?></strong> <?php endif; ?>
								<?php echo esc_html( ! empty( $guide['title'] ) ? $guide['title'] : '' ); ?>
							</p>
							<?php if ( $link && ! empty( $link['url'] ) ) : ?>
								<a class="brand-guides__card-link" href="<?php echo esc_url( $link['url'] ); ?>"
									<?php echo ! empty( $link['target'] ) ? 'target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; ?>>
									<?php echo esc_html( ! empty( $link['title'] ) ? $link['title'] : __( 'Read the Guide', 'gt' ) ); ?>
								</a>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
```

- [ ] **Step 9: SCSS — `assets/sass/components/shop/_s.brand.scss`**

```scss
// ---------------------------------------------------------------------------
// Brand landing page — Figma 384:1489
//
// Hero 1440 x 500 (content 732 at 50/116); science row 72 below with the
// 429px steps column at x=160, a 135 gap and a 616 copy column; the range
// 90 below (4 x 320, gap 20); the pair band 58 below (1340 x 289); the
// guides band 59 below (full bleed, 81/50 padding, 555 copy + two 347 cards).
// ---------------------------------------------------------------------------

$brand-font: "DM Sans", #{$primary_font};
$brand-serif: "Cormorant Garamond", serif;
$brand-muted: rgba(0, 0, 0, 0.75);

@mixin brand-display-title {
  margin: 0;
  font-family: $brand-serif;
  font-size: 60px;
  font-weight: $font-700;
  line-height: 65px;
  letter-spacing: -1.2px;
}

@mixin brand-underline-link($colour) {
  font-family: $brand-font;
  font-size: 14px;
  font-weight: $font-700;
  line-height: normal;
  color: $colour;
  text-decoration: underline;
  text-underline-position: from-font;
}

.brand-page {
  padding: 0;
}

// -- hero -----------------------------------------------------------------------
.brand-hero {
  position: relative;
  min-height: 500px;
  overflow: hidden;
  background-color: #000;
  color: color(white);

  &__img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: right center;
  }

  &__scrim {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, #000 35%, rgba(0, 0, 0, 0) 75%);
  }

  &__inner {
    position: relative;
    max-width: 1440px;
    margin: 0 auto;
    padding: 116px 50px 68px;
  }

  &__content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 20px;
    max-width: 732px;
  }

  &__logo {
    display: block;
    width: auto;
    height: 27px;
    max-width: 150px;
    object-fit: contain;
    object-position: left center;
  }

  &__title {
    @include brand-display-title;
    color: color(white);
  }

  &__accent {
    display: block;
    font-style: normal;
    color: var(--brand-accent, #fbc707);
  }

  &__intro {
    margin: 0;
    font-family: $brand-font;
    font-size: 15px;
    font-weight: $font-400;
    line-height: 25px;
    color: color(white);
  }

  &__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 25px;
  }

  &__explore {
    @include brand-underline-link(color(white));
  }
}

// -- science ------------------------------------------------------------------
.brand-science {
  padding-top: 72px;

  &__inner {
    display: flex;
    align-items: flex-start;
    gap: 135px;
    max-width: 1440px;
    margin: 0 auto;
    padding: 0 50px 0 160px;
  }

  &__slider {
    flex: 0 0 429px;
    max-width: 429px;
    min-width: 0;
  }

  &__copy {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    gap: 40px;
    min-width: 0;
    max-width: 616px;
  }

  &__title {
    @include brand-display-title;
    color: #000;
  }

  &__accent {
    display: block;
    font-style: normal;
    color: var(--brand-accent, #fbc707);
  }

  &__text {
    p {
      margin: 0 0 25px;
      font-family: $brand-font;
      font-size: 15px;
      font-weight: $font-400;
      line-height: 25px;
      color: $brand-muted;

      &:last-child {
        margin-bottom: 0;
      }
    }
  }
}

// Steps slider — Figma 384:1564 / 384:1577.
.brand-steps {
  margin: 0;
  padding: 0;
  list-style: none;

  &:not(.slick-initialized) &__step:not(:first-child) {
    display: none;
  }

  &__step {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin: 0;
  }

  &__media {
    position: relative;
    aspect-ratio: 429 / 420;
    overflow: hidden;
    background-color: rgba(35, 31, 32, 0.05);
  }

  &__img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  &__badge {
    position: absolute;
    top: 20px;
    left: 19px;
    padding: 5px;
    background-color: #000;
    font-family: $brand-font;
    font-size: 12px;
    font-weight: $font-500;
    line-height: normal;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: var(--brand-accent, #fbc707);
  }

  &__marker {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    background-color: color(white);
    color: #000;
    transform: translate(-50%, -50%);
    transition: background-color 0.2s ease, color 0.2s ease;

    svg {
      display: block;
      width: 30px;
      height: 30px;
    }

    &:hover,
    &:focus-visible {
      background-color: #000;
      color: color(white);
    }
  }

  &__body {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 25px;
  }

  &__title {
    margin: 0 0 -15px;
    font-family: $brand-font;
    font-size: 18px;
    font-weight: $font-500;
    line-height: normal;
    color: #000;
  }

  &__text {
    margin: 0;
    font-family: $brand-font;
    font-size: 15px;
    font-weight: $font-400;
    line-height: 25px;
    color: $brand-muted;
  }

  &__link {
    @include brand-underline-link(#000);
  }

  &__controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    margin-top: 47px;
  }
}

// FAQ callout — Figma 384:1508.
.brand-faq {
  display: flex;
  flex-direction: column;
  gap: 30px;
  margin-top: 19px; // 40 gap above already from the copy column; Figma has 59
  padding: 30px;
  background-color: #000;
  color: color(white);

  &__eyebrow,
  &__question,
  &__answer {
    margin: 0;
  }

  &__eyebrow {
    font-family: $brand-font;
    font-size: 12px;
    font-weight: $font-500;
    line-height: normal;
    letter-spacing: 1.2px;
    color: rgba(255, 255, 255, 0.5);
  }

  &__question {
    margin-top: -15px;
    font-family: $brand-serif;
    font-size: 30px;
    font-weight: $font-700;
    line-height: normal;
    letter-spacing: -0.6px;
    color: var(--brand-accent, #fbc707);
  }

  &__answer {
    margin-top: -15px;
    font-family: $brand-font;
    font-size: 15px;
    font-weight: $font-400;
    line-height: 25px;
    color: rgba(255, 255, 255, 0.75);
  }

  &__link {
    @include brand-underline-link(color(white));
  }
}

// -- range ---------------------------------------------------------------------
.brand-range {
  padding-top: 90px;

  &__inner {
    display: flex;
    flex-direction: column;
    gap: 25px;
    max-width: 1440px;
    margin: 0 auto;
    padding: 0 50px;
  }

  &__head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
  }

  &__title {
    margin: 0;
    font-family: $brand-serif;
    font-size: 35px;
    font-weight: $font-700;
    line-height: normal;
    letter-spacing: -0.7px;
    color: #000;
  }

  &__count,
  &__empty {
    margin: 0;
    font-family: $brand-font;
    font-size: 12px;
    font-weight: $font-400;
    line-height: normal;
    color: rgba(0, 0, 0, 0.5);
  }

  &__empty {
    font-size: 15px;
  }

  &__grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
    margin: 0;
    padding: 0;
    list-style: none;
  }
}

// -- pair band ---------------------------------------------------------------
.brand-pair {
  padding-top: 58px;

  &__inner {
    position: relative;
    display: flex;
    align-items: center;
    min-height: 289px;
    max-width: 1340px;
    margin: 0 auto;
    padding: 30px 50px;
    overflow: hidden;
    background-color: #000;
    color: color(white);
  }

  &__img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: right center;
  }

  &__scrim {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, #000 42%, rgba(0, 0, 0, 0) 75%);
  }

  &__content {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
    max-width: 661px;
  }

  &__logo {
    display: block;
    width: auto;
    height: 38px;
    max-width: 125px;
    object-fit: contain;
    object-position: left center;

    &--text {
      font-family: $brand-font;
      font-size: 22px;
      font-weight: $font-900;
      line-height: 38px;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: color(white);
    }
  }

  &__title {
    margin: 0;
    font-family: $brand-serif;
    font-size: 30px;
    font-weight: $font-700;
    line-height: normal;
    letter-spacing: -0.6px;
    color: color(white);
  }

  &__text {
    margin: 0 0 15px;
    max-width: 622px;
    font-family: $brand-font;
    font-size: 15px;
    font-weight: $font-400;
    line-height: 25px;
    color: rgba(255, 255, 255, 0.75);
  }
}

// -- guides band ---------------------------------------------------------------
.brand-guides {
  margin-top: 59px;
  padding: 81px 0;
  background-color: #f4f4f4;

  &__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 60px;
    max-width: 1440px;
    margin: 0 auto;
    padding: 0 50px;
  }

  &__copy {
    display: flex;
    flex: 0 1 555px;
    flex-direction: column;
    align-items: flex-start;
    gap: 40px;
    max-width: 555px;
  }

  &__title {
    @include brand-display-title;
    color: #000;
  }

  &__accent {
    display: block;
    font-style: normal;
    color: var(--brand-accent, #fbc707);
  }

  &__text {
    margin: 0;
    font-family: $brand-font;
    font-size: 15px;
    font-weight: $font-400;
    line-height: 25px;
    color: $brand-muted;
  }

  &__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 25px;
  }

  &__link {
    @include brand-underline-link(#000);
  }

  &__cards {
    display: flex;
    gap: 25px;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  &__card {
    position: relative;
    display: flex;
    flex: 0 0 347.33px;
    flex-direction: column;
    justify-content: flex-end;
    height: 402px;
    margin: 0;
    padding: 0 20px 20px;
    overflow: hidden;
    background-color: rgba(35, 31, 32, 0.08);
  }

  &__card-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  &__panel {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 20px;
    background-color: rgba(255, 255, 255, 0.9);
  }

  &__card-title {
    margin: 0;
    font-family: $brand-font;
    font-size: 18px;
    font-weight: $font-400;
    line-height: normal;
    color: #000;

    strong {
      font-weight: $font-900;
    }
  }

  &__card-link {
    @include brand-underline-link(#000);
  }
}

// -- responsive ------------------------------------------------------------------
@media (max-width: $xl - 1px) {
  .brand-hero__inner {
    padding: 90px 25px 60px;
  }

  .brand-science {
    padding-top: 60px;

    &__inner {
      gap: 60px;
      padding: 0 25px;
    }

    &__slider {
      flex-basis: 380px;
      max-width: 380px;
    }
  }

  .brand-range {
    padding-top: 70px;

    &__inner {
      padding: 0 25px;
    }

    &__grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  .brand-pair__inner {
    margin: 0 25px;
  }

  .brand-guides {
    padding: 60px 0;

    &__inner {
      flex-direction: column;
      align-items: flex-start;
      gap: 40px;
      padding: 0 25px;
    }

    &__copy {
      flex-basis: auto;
      max-width: 700px;
    }

    &__card {
      flex: 1 1 0;
    }

    &__cards {
      width: 100%;
    }
  }
}

@media (max-width: $md - 1px) {
  .brand-hero {
    min-height: 420px;

    &__title {
      font-size: 44px;
      line-height: 48px;
      letter-spacing: -0.9px;
    }

    &__scrim {
      background: linear-gradient(90deg, #000 30%, rgba(0, 0, 0, 0.3) 100%);
    }
  }

  .brand-science {
    &__inner {
      flex-direction: column;
      gap: 50px;
    }

    &__slider {
      flex-basis: auto;
      width: 100%;
      max-width: 480px;
    }

    &__copy {
      max-width: none;
    }

    &__title {
      font-size: 44px;
      line-height: 48px;
      letter-spacing: -0.9px;
    }
  }

  .brand-guides__title {
    font-size: 44px;
    line-height: 48px;
    letter-spacing: -0.9px;
  }
}

@media (max-width: $sm - 1px) {
  .brand-hero {
    min-height: 360px;

    &__inner {
      padding: 60px 25px 40px;
    }

    &__scrim {
      background: rgba(0, 0, 0, 0.65);
    }

    &__title {
      font-size: 36px;
      line-height: 40px;
      letter-spacing: -0.7px;
    }
  }

  .brand-science {
    padding-top: 45px;

    &__title {
      font-size: 36px;
      line-height: 40px;
      letter-spacing: -0.7px;
    }
  }

  .brand-steps__controls {
    margin-top: 30px;
  }

  .brand-faq {
    padding: 25px;

    &__question {
      font-size: 26px;
    }
  }

  .brand-range {
    padding-top: 50px;

    &__head {
      flex-direction: column;
      align-items: flex-start;
      gap: 8px;
    }

    &__title {
      font-size: 30px;
    }

    &__grid {
      grid-template-columns: minmax(0, 1fr);
    }
  }

  .brand-pair {
    padding-top: 45px;

    &__inner {
      padding: 30px 25px;
    }

    &__scrim {
      background: rgba(0, 0, 0, 0.7);
    }

    &__title {
      font-size: 26px;
    }
  }

  .brand-guides {
    margin-top: 45px;
    padding: 45px 0;

    &__title {
      font-size: 36px;
      line-height: 40px;
      letter-spacing: -0.7px;
    }

    &__cards {
      flex-direction: column;
    }

    &__card {
      flex-basis: auto;
      height: 340px;
    }
  }
}

@media (prefers-reduced-motion: reduce) {
  .brand-steps__marker {
    transition: none;
  }
}
```

- [ ] **Step 10: Import (`@import "components/shop/s.brand";`), compile, run the tests**

Run: `tests/bin/wpx eval-file tests/brand-page.test.php` → `Success: 46 assertions passed`; `tests/bin/wpx eval-file tests/product-helpers.test.php` (brand assertions) and `tests/run.sh` → all green.

- [ ] **Step 11: Look at it** — screenshot `/brand/clonex/` at 1440×3800 and compare with Figma 384:1489 (spec §7 + tokens): hero 500 tall with the logo/headline/CTAs at 50/116; steps column at x=160 with the black badge, the plus marker, progress squares + bar and the two arrows; the copy column with the 60px heading and the black FAQ box; range title + count + four cards; the pair band; the grey guides band with two cards. Also `/brand/ionic/` (hero fallback + range only). Fix drift, recompile. Browser-check the steps slider with puppeteer: click the next arrow → second step visible, first progress square black (`is-past`), active bar on the second; the plus marker links to the product.

- [ ] **Step 12: Commit**

```bash
git add gt_system/themes/growth_tech/woocommerce/taxonomy-product_brand.php gt_system/themes/growth_tech/template-parts/brand gt_system/themes/growth_tech/assets/images/icons/plus.svg gt_system/themes/growth_tech/assets/sass/components/shop/_s.brand.scss gt_system/themes/growth_tech/assets/sass/main.scss gt_system/themes/growth_tech/assets/css/main.css gt_system/themes/growth_tech/assets/css/main.css.map tests/brand-page.test.php
git commit -m "Add brand landing page with hero, steps slider, range, pair and guides bands"
```

---

### Task 9: Visual verification at all breakpoints + journey check

**Files:**
- Modify: `tests/shots.sh` (add the product and brand pages)
- Modify: `tests/README.md` (mention the new seed script)

- [ ] **Step 1: Extend `tests/shots.sh`** — add inside the `for w in …` loop:

```sh
  shot product "https://growth-tech.local/product/clonex-mist/" "$w" 3400
  shot brand "https://growth-tech.local/brand/clonex/" "$w" 3800
```

and add to `tests/README.md` under the seed section: "`tests/seed/seed-product-content.php` — product page / brand landing content and generated placeholder images (run after seed-shop.php)."

- [ ] **Step 2: Run `tests/shots.sh` and read every product/brand PNG at 1440, 1024 and 768.** For 390, capture with puppeteer-core `setViewport({width: 390, height: 900})` (CLI captures below ~440px are cropped by Chrome's minimum window width). Check against the Global Constraints tokens: product page (gallery 600/summary 621 side by side → stacked under 1024; tabs → accordion under 768; related band 4/2/1 columns; knowledge band gradient/heights) and brand page (hero 500/420/360; science two columns → stacked; range 4/2/1; pair band; guides two cards → stacked). No horizontal overflow: run a puppeteer bounding-rect scan (`every element's getBoundingClientRect().right <= innerWidth`) at 1024/768/390 on both pages. Fix SCSS drift, recompile, re-shoot.

- [ ] **Step 3: Journey check (puppeteer)** — `/shop/` → click the Clonex promo tile → `/brand/clonex/` → click the first range card → `/product/clonex-mist/` → the breadcrumb reads Our Products / Propagation / Clonex / Clonex Mist and each crumb link resolves (200) → "Find a local stockist" href ends with `/find-a-stockist/?product={id}&region=uk` → click "Explore the Clonex Range" on the brand page scrolls to `#range`. Paste the output in the report.

- [ ] **Step 4: Run `tests/run.sh`** — all files green (Plan 1 + Plan 2).

- [ ] **Step 5: Commit**

```bash
git add tests/shots.sh tests/README.md
git commit -m "Extend screenshot script and test docs for the product and brand pages"
```

---

## Self-review notes

- Spec coverage: §4.2 product fields → Task 1; §4.3 brand landing fields → Task 1; §4.4 knowledge defaults → Task 1; §7 brand page (hero, science/steps/FAQ, range, pair, guides) → Task 8; §8 product page (breadcrumb with brand, gallery + lightbox, summary, sizes display-only, CTAs with product association, downloads, badges, tabs, complete the system, knowledge band, join club, Phase Two slot) → Tasks 3–7; §10 accessibility (tabs/accordion keyboard, lightbox focus return + Esc, single-link cards, lazy images with sizes) → Tasks 4–8; §11 verification → Task 9.
- Deviations from the spec, all deliberate: the brand page has no breadcrumb (the Figma frame has none); the plus-marker position is two percentage fields rather than a drag UI; badges are a taxonomy as the spec allows; the "View all Products" link goes to the shop page; "Complete the system" caps at 12 items and only becomes a slider above four.
- Names used across tasks: `gt_shop_breadcrumb_items`, `gt_product_stockist_url`, `gt_brand_stockist_url`, `gt_product_experts_url`, `gt_product_experts_label`, `gt_product_related`, `gt_product_badges`, `gt_knowledge_band` (Task 3) ↔ Tasks 4, 7, 8; `.shop-slider__arrows` / `.shop-slider__progress` (Task 7) ↔ Task 8; gallery data hooks (Task 4) ↔ Task 5; tab markup (Task 6) ↔ its JS.
