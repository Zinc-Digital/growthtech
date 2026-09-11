# WooCommerce Shop, Brand, Product & Stockist Finder — Design

**Date:** 2026-09-11
**Status:** Approved in brainstorming; awaiting spec review
**Theme:** `gt_system/themes/growth_tech`

## 1. Goal

Build the Growth Technology shop as a standard WooCommerce implementation whose
templates match the supplied Figma frames pixel-for-pixel and are fully
responsive, with an enquiry-based journey in Phase One and no rebuild required
to enable purchasing in Phase Two.

Journey: **Shop → Category → Brand (range) → Product → Find a stockist**

| Figma node | Page | Template |
|---|---|---|
| 384:1608 | Shop (all products, filters) | `woocommerce/archive-product.php` |
| 384:1267 | Category listing (hero + filters) | `woocommerce/archive-product.php` |
| 384:1489 | Brand landing page (Clonex) | `woocommerce/taxonomy-product_brand.php` |
| 384:1816 | Single product (Clonex Mist) | `woocommerce/single-product.php` |
| 384:2719 | Find a Stockist | `page-templates/page-stockists.php` |

"Product range" = **brand** (Clonex, Root Riot, Ionic…). Categories
(Propagation, Nutrients…) are a separate axis. The product breadcrumb is
*Our Products / {Category} / {Brand} / {Product}*.

## 2. Environment & conventions

- WordPress classic theme, WooCommerce 11.1, ACF Pro 6.8, Gravity Forms 3.1.
- WooCommerce 11.x ships the `product_brand` taxonomy; we use it rather than
  a custom one.
- Existing conventions to follow: ACF JSON in `acf-json/` with hand-named
  files; BEM SCSS partials (`_c.*`, `_b.*`) imported from `assets/sass/main.scss`
  and compiled to `assets/css/main.css`; `gt_asset_version()` cache busting;
  `btn-flat` button component; `gt_arrow_svg()` / `gt_icon_svg()` helpers;
  Slick slider via the registered `gt-block-slider` script; DM Sans body,
  Cormorant Garamond display; theme breakpoints `$sm 768 / $md 1024 / $xl 1266`.
- Templates escape output (`esc_html`, `esc_url`, `wp_kses_post`) and hide any
  section whose fields are empty.

## 3. Approach

Standard WooCommerce template overrides under `woocommerce/` with
`add_theme_support('woocommerce')`. WooCommerce keeps ownership of the
product query, pagination, layered-nav query vars, sorting, structured data
and product URLs. Design-specific content lives in ACF field groups attached
to WC products and terms. Rejected alternatives: custom `WP_Query` page
templates (re-implements WC; Phase Two would still need WC templates) and WC
block templates (needs a block theme).

## 4. Data model

### 4.1 WooCommerce native

| Object | Used for |
|---|---|
| Product (simple) | title, short description (intro under title), gallery, SKU, upsells ("Complete the system"), category, brand, attributes. Price left empty in Phase One. |
| `product_cat` | Categories. ACF: `hero_image`, `hero_heading`, `hero_text` (falls back to term description). |
| `product_brand` | Brands/ranges. ACF (see 4.3). |
| Global attribute `pa_size` | "Available sizes" chips; card meta ("100ml/300ml/750ml"). Becomes the variation attribute in Phase Two. |
| Global attribute `pa_growing_medium` | Sidebar filter "Growing Medium". |
| Global attribute `pa_growing_stage` | Sidebar filter "Growing Stage". |

### 4.2 ACF: Product Details (`acf-json/product-details.json`, location: post_type = product)

| Field | Type | Renders |
|---|---|---|
| `features` | repeater: `lead` (text), `text` (text) | tick-list bullets |
| `downloads` | repeater: `label`, `file` | download links beside the CTAs |
| `science` | repeater: `heading`, `text` (wysiwyg) | "The science" tab, two-up |
| `how_to_use` | wysiwyg | "How to use" tab |
| `specification` | repeater: `label`, `value` | "Specification" tab, definition list |
| `documents` | repeater: `label`, `file` | "Useful Documents" tab |
| `knowledge_override` | group (heading, text, image, link) | overrides Theme Settings band |
| `hide_join_club` | true/false | extends the existing footer toggle to products |

Badges are a small non-hierarchical taxonomy `product_badge` (Registered
product, Independently tested, Made in Somerset) so editors can add more.

### 4.3 ACF: Brand (`acf-json/brand.json`, location: taxonomy = product_brand)

| Field | Purpose |
|---|---|
| `logo` (image/SVG), `accent_colour` (colour) | brand mark on cards, hero, product page |
| `own_brand` (true/false) | drives "Our brands first" sort |
| `promo_enabled`, `promo_image`, `promo_tagline` (text with `<em>` allowed for the accent line), `promo_position` (number) | "Explore the {Brand} Range" tile in shop/category grids |
| `hero_heading`, `hero_accent_line`, `hero_intro`, `hero_image` | brand hero |
| `science_heading`, `science_accent_line`, `science_text` | "Rooted in science" copy |
| `steps` repeater: `label`, `image`, `product` (post object), `text` | product-step slider |
| `faq` group: `eyebrow`, `question`, `answer`, `link` | FAQ callout |
| `pair_brand` (taxonomy), `pair_heading`, `pair_text`, `pair_image`, `pair_link` | cross-sell band |
| `guides_heading`, `guides_accent_line`, `guides_text`, `guides_cta_1`, `guides_cta_2`, `guides` repeater: `image`, `lead`, `title`, `link` | guides band |

### 4.4 Theme Settings → Shop tab (`acf-json/theme-settings-shop.json`, options page)

`stockist_page` (page link), `experts_link` (link), `google_maps_key`,
`knowledge_heading`, `knowledge_text`, `knowledge_image`, `knowledge_link`,
`products_per_page` (default 12).

### 4.5 Stockists

- CPT `stockist` (not publicly queryable as single pages; `show_in_rest` for
  admin only). Taxonomy `stockist_type` (Hydroponics specialist, Grow shop…).
- ACF (`acf-json/stockist.json`): `address_1`, `address_2`, `town`, `region`,
  `postcode`, `country` (select, ISO code; default GB), `phone`, `website`,
  `email`, `products` (relationship → product), `lat`, `lng`, `geocode_status`.
- On save: if address fields changed or lat/lng empty, geocode via Google
  Geocoding API (server-side, key from Theme Settings) and store lat/lng.
  Admin list column shows geocode status.
- ACF on the Stockists page (`acf-json/stockists-page.json`): `intro`,
  `trade_heading`, `trade_text`, `trade_image`, `trade_link`.

## 5. Templates & files

```
woocommerce/
  archive-product.php            shop + category
  taxonomy-product_brand.php     brand landing
  single-product.php             product detail
  content-product.php            product card
  content-brand-promo.php        brand promo tile
inc/
  woocommerce.php                theme support, hook removal, sort options, breadcrumb, image sizes
  woocommerce-filters.php        sidebar term counts, AJAX endpoint
  stockists.php                  CPT, taxonomy, geocoding, REST endpoint
template-parts/shop/
  breadcrumb.php  category-hero.php  filters.php  toolbar.php
  product-gallery.php  product-summary.php  product-tabs.php
  complete-system.php  knowledge-band.php
  brand-hero.php  brand-science.php  brand-range.php  brand-pair.php  brand-guides.php
page-templates/page-stockists.php
template-parts/stockists/controls.php  card.php  trade-band.php
assets/sass/components/shop/
  _s.layout.scss _s.card.scss _s.promo.scss _s.filters.scss _s.toolbar.scss
  _s.category-hero.scss _s.product.scss _s.tabs.scss _s.gallery.scss
  _s.brand.scss _s.stockists.scss
assets/js/shop-filters.js  product-gallery.js  product-tabs.js  stockists.js
```

URLs (WC defaults): `/shop/` (page titled "Our Products"),
`/product-category/{slug}/`, `/brand/{slug}/`, `/product/{slug}/`.

Image sizes added in `gt_theme_support`: `gt-product-card` (1x/2x, square-ish
light-grey tile), `gt-product-main`, `gt-product-thumb`, `gt-brand-hero`.

## 6. Shop & category page

- Layout: breadcrumb; optional category hero (image, heading, intro) when
  `is_product_category()`; sidebar (search, Filters heading, Category, Brands,
  Growing Medium, Growing Stage groups with counts, collapsible); toolbar
  ("Showing X of Y products", Sort by); 3-column product grid.
- **Card** (`content-product.php`): image on `#F4F4F4` tile, title, meta line
  "{Primary category} • {sizes joined with /}". Whole card links to the product.
- **Promo tile**: for each brand with `promo_enabled`, spliced into the loop at
  `promo_position` (1-based index in the grid, capped to the page length) on the
  shop page and on category pages where the brand has products in that
  category. Black tile: brand logo, tagline (accent-coloured `<em>`), "Explore
  the {Brand} Range" link to the brand page. Not counted in "Showing X of Y".
- **Filters**: GET form using WC/WP query vars — `product_cat`, `product_brand`
  (comma-joined slugs), `filter_growing-medium`, `filter_growing-stage`,
  `orderby`, `s`. Counts computed per term against the current selection
  (other groups' filters applied, own group's not) so they show what is
  selectable; zero-count terms rendered disabled/greyed. On a category page
  that category is pre-ticked.
- **AJAX**: `shop-filters.js` intercepts form changes, requests
  `admin-ajax.php?action=gt_shop_filter` with the same query string, receives
  JSON `{grid, sidebar, count, url}`, swaps the three regions, `pushState`s
  `url`. `popstate` re-fetches. Loading state on the grid; focus management
  after swap. No JS → normal GET page load renders the same result.
- **Sort options** (`woocommerce_catalog_orderby`): `brands` "Our brands first"
  (default: own_brand desc, menu_order, title), `title` A–Z, `title-desc` Z–A,
  `date` Newest. Price sorts removed while `GT_SHOP_ENQUIRY_MODE` is true.
- **Pagination**: WC paginates by `products_per_page`; rendered as a "Load
  more" button that appends the next page via the AJAX endpoint and updates the
  URL (`/page/2/`). No JS → standard WC page links, styled.
- **Sidebar search**: filters product title within the current context.
- **Responsive**: ≥1266 sidebar 228px + 3 cols; 1024–1265 sidebar 200px + 2
  cols; <1024 sidebar becomes a "Filters" button opening an off-canvas panel,
  2 cols; <768 1 col. Exact values taken from the Figma tablet/mobile frames
  where they exist.

## 7. Brand landing page

`taxonomy-product_brand.php`; each section hidden when its fields are empty.

1. **Hero** — black; logo; heading + accent line; intro; `btn-flat` "Find a
   stockist" → stockist page `?brand={slug}`; underlined link "Explore the
   {Brand} range" → `#range`.
2. **Science** — heading/accent/text left column; product-step slider
   (step label, image, product "+" hotspot link, name, text, "View Product"
   link; slick with progress bar and prev/next — reuses `gt-block-slider`
   conventions); FAQ callout (black card: eyebrow, question in accent,
   answer, link).
3. **Range** (`#range`) — "The {Brand} Range", "Showing N of N products",
   4-column grid of `content-product.php` cards (2 cols tablet, 1 mobile).
4. **Pair band** — black band: paired brand logo, heading, text, image,
   `btn-flat` link.
5. **Guides band** — light-grey band: heading/accent/text, two CTAs
   (`btn-flat` + underlined link), two guide cards (image, panel with bold
   lead + title, "Read the Guide" link).

## 8. Single product page

`single-product.php`, built on WC's `woocommerce_single_product_summary` hook
so Phase Two can insert price/cart in place.

- Breadcrumb: WC breadcrumb filtered to insert the brand crumb before the
  product.
- **Gallery**: main image with zoom button (opens lightbox), prev/next arrows,
  thumbnail row. Slick-based (`product-gallery.js`); WC's zoom/PhotoSwipe
  scripts dequeued. Single image → no arrows/thumbs.
- **Summary**: brand logo (accent colour); title (Cormorant); short
  description; feature tick-list; "Available sizes" chips from `pa_size`
  (display-only in Phase One, first chip styled selected); CTAs — `btn-flat
  btn-flat--dark` "Find a local stockist" → stockist page
  `?product={id}&region=uk`, underlined "Ask our experts" → `experts_link`
  `?product={id}`; rule; downloads row; badge pills.
- **Tabs**: The science / How to use / Specification / Useful Documents —
  only tabs with content; accessible tab pattern (`product-tabs.js`), stacks
  to accordion <768.
- **Complete the system**: upsells, else same-brand products excluding the
  current one; "View all Products" → shop; prev/next arrows; slick when more
  than four.
- **Knowledge band**: per-product override else Theme Settings.
- **Join the growth club**: existing footer band; `hide_join_club` honoured.

### Enquiry association

Both CTAs carry the product ID. The stockist page reads `?product=` and
preselects the product in its filter. If `experts_link` targets a page with
a Gravity Form, the form's hidden "Product" field is populated from the
`product` parameter (GF dynamic population, parameter name `product`).

### Phase Two switch

`inc/woocommerce.php` defines `GT_SHOP_ENQUIRY_MODE` (true). While true it:
removes `woocommerce_template_single_price`,
`woocommerce_template_single_add_to_cart`, loop price/add-to-cart, price
sort options, and cart/checkout/account from nav; renders sizes display-only.
Setting it false restores WC defaults in the same slots. Cart/checkout/account
pages will need styling in Phase Two; shop/brand/product templates will not
be rebuilt.

## 9. Stockist finder

`page-templates/page-stockists.php` on the "Find a Stockist" page.

- **Head**: page title (H1) + intro (ACF).
- **Controls**: UK / International segmented toggle (default UK; International
  reveals a country select populated from stockists' countries); text search
  (town, city, postcode or store name); product select ("Stocking any
  product" + all published products, preselected from `?product=`); count
  ("10 stockists"); sort — Nearest first (enabled once a location is known:
  geocoded search or browser geolocation), A–Z.
- **List**: card — name, "{Town}, {Country} • {Type}", product chips (first
  three + "+N more" toggle), phone, "Get Directions" (Google Maps directions
  URL), "Check stock first" (website, else `tel:`), "Show on map" button
  (pans/zooms map, opens marker). Scrollable list column matching the map
  height on desktop; stacked list under map on mobile.
- **Map**: Google Maps JS (key from Theme Settings, referrer-restricted),
  greyscale style, custom pin, +/- controls, markers bound to cards; fits
  bounds to the current result set.
- **Data**: REST `GET /wp-json/gt/v1/stockists` → `[{id, name, town, country,
  type, lat, lng, phone, website, products:[{id,name}], directions}]`. JS
  filters (country, product, text) and sorts client-side. Geocoding of the
  search text via `GET /wp-json/gt/v1/geocode?q=` (server-side proxy, cached
  in a transient).
- **Trade band**: "Run a store? Stock the originals." — ACF heading, text,
  image, `btn-flat` link.
- `?brand={slug}` from the brand hero pre-filters to products of that brand.

## 10. Accessibility & performance

- Filters are real checkboxes in a `<form>`; live regions announce result
  counts; tabs/accordions and gallery are keyboard operable; promo tiles and
  cards have a single link each.
- Images use registered sizes + `srcset`/`sizes`; below-the-fold images lazy.
- Only pages that need slick/gallery/map enqueue those scripts (mirrors the
  existing `gt-block-slider` pattern). WC's default styles are dequeued
  (`woocommerce_enqueue_styles` → empty); we style from scratch.

## 11. Testing & verification

No database access from the CLI in this environment, so verification is
browser-based on the local site:

1. Seed content in admin: 3 categories, 3 brands (one with promo tile), ~10
   products with sizes/attributes/features/tabs, 6 stockists across UK and
   one other country.
2. For each page (shop, category, brand, product, stockists) compare against
   the Figma frame at 1440, 1024, 768 and 390px; check spacing, type, sizes.
3. Filter journeys: tick/untick each group, sort, search, load more — with JS
   on (URL updates, back/forward) and JS off (plain GET).
4. Journey links: shop → category → brand → product → stockist page with the
   product preselected; breadcrumbs correct at each step.
5. Keyboard-only pass over filters, tabs, gallery, stockist list.
6. Toggle `GT_SHOP_ENQUIRY_MODE` to false on a scratch copy to confirm price
   and add-to-cart appear in the summary without layout breakage.

## 12. Out of scope

Cart, checkout, payments, accounts, stock, emails (Phase Two); CSV import of
stockists; Plant Academy content; header search behaviour.
