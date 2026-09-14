# Tests

WP-CLI-driven integration tests against the real MAMP site — no PHPUnit, no
mocked WordPress.

## Running

- `tests/run.sh` — runs every `tests/*.test.php` through `tests/bin/wpx
  eval-file` and exits 1 if any file fails. Safe to run any time — almost
  every file is read-only, with three exceptions that briefly mutate a
  record and restore it in a `finally` block before the file finishes:
  `tests/product-helpers.test.php` (Clonex Rooting Hormone's catalog
  visibility and Clonex Mist's knowledge band override, two records),
  `tests/product-page.test.php` (Clonex Mist's `hide_join_club` field) and
  `tests/stockists-geocode.test.php` (the "Google Maps API key" option and a
  temporary stockist record — see "Stockist finder" below).
- `tests/bin/wpx <wp args>` — WP-CLI wired to MAMP's PHP 8.3 binary, MySQL
  socket and the site URL. Use it directly for one-off checks, e.g.
  `tests/bin/wpx eval-file tests/archive.test.php`.
- `tests/shots.sh [outdir]` — headless Chrome screenshots of the shop pages
  at the design breakpoints (default `/tmp/gt-shots`). Below ~440px wide
  these are unreliable: headless Chrome enforces a minimum window width, so
  `--window-size` is clamped and the capture isn't actually phone-width. Use
  a puppeteer script with `page.setViewport()` instead for phone-width
  checks.

## Writing tests

Each file: `require_once __DIR__ . '/lib/bootstrap.php';`, then assertions,
then `gt_test_done();`. `tests/lib/bootstrap.php` provides `gt_assert()`,
`gt_assert_equal()`, `gt_assert_contains()`, `gt_assert_not_contains()` and
`gt_fetch( $path )` (a GET over HTTPS with `sslverify` off, for the
self-signed local cert).

## MAMP specifics

- PHP: `/Applications/MAMP/bin/php/php8.3.30/bin/php`
- MySQL socket: `/Applications/MAMP/tmp/mysql/mysql.sock`
- Site: `https://growth-tech.local` — self-signed cert, so `curl -k` and
  `gt_fetch()`'s `sslverify => false`.
- Both MAMP's servers must actually be running for `gt_fetch()` and
  `tests/shots.sh` to reach the site (WP-CLI eval commands don't need them).

## Site preconditions

Tests assume the site is already configured this way:

- Pretty permalinks: `tests/bin/wpx rewrite structure '/%postname%/' --hard`
- The WordPress `.htaccess` mod_rewrite block must be present (regenerated
  by the `--hard` rewrite flush above; check it wasn't stripped).
- WooCommerce's "coming soon" mode must be off:
  `tests/bin/wpx option update woocommerce_coming_soon no` — otherwise every
  front-end fetch returns the coming-soon page instead of the shop.

## Seed data

`tests/seed/seed-shop.php` creates the products/terms the tests assert
against (13 products, Clonex/Ionic brands, etc). It **writes to the
database** and is **local-only** — do not run it against anything but a
disposable local DB, and never as part of routine test runs.

`tests/seed/seed-product-content.php` — product page / brand landing content
and generated placeholder images (run after seed-shop.php).

`tests/seed/seed-stockists.php` — eight stockists (address, coordinates,
type, products) and the Find a Stockist page's template/intro/trade band
content (run after seed-shop.php and seed-product-content.php).

## Stockist finder

Seed order: `seed-shop.php` → `seed-product-content.php` →
`seed-stockists.php` — each depends on terms/content the one before it
creates.

The map and geocoding need a Google Maps Platform project with the **Maps
JavaScript API** and **Geocoding API** enabled:

1. Create a browser key restricted by HTTP referrer
   (`growth-tech.local/*`, plus the production domain) and put it in Theme
   Settings → Shop Settings → "Google Maps API key". This alone drives both
   the map and geocoding.
2. Optionally, create a second key restricted by IP (the server's egress
   IP) or left unrestricted with a quota, and put it in "Google Geocoding
   key (server)". A referrer-restricted key is rejected by the Geocoding
   web service (it's a server-to-server call, no referrer to check), so
   without this second key geocoding silently fails unless the Maps key
   happens to be unrestricted.

Geocoding runs on a stockist's `acf/save_post` — no separate step. A
successful lookup is cached for 30 days (`gt_geocode_*` transients, keyed by
region + the lower-cased address), so editing an already-geocoded stockist
without changing its address won't re-hit Google. Changing the address
(town/country) and saving triggers a fresh lookup automatically, since it no
longer matches the stored "Geocoded address". To force a fresh lookup on an
*unchanged* address, clear Latitude and Longitude and save: the cached
result for that address is discarded and the address is looked up again. To
use hand-typed coordinates instead, enter Latitude/Longitude and leave
Geocoded address empty — this is recorded as status `manual` and is never
overwritten by the save hook. A failed lookup clears Latitude, Longitude and
Geocoded address and shows `failed` in the admin "Geocode" column until the
address is corrected and saved again.

The `gt/v1/geocode` REST proxy that the front-end search box calls keeps the
key server-side; it also rate-limits to 30 requests per IP per minute,
returning 429 once exceeded. Since the proxy is public (no auth, keyed only
by IP), put a daily quota cap on the Geocoding key in Google Cloud so a
scripted flood can't run up billing. The per-IP limit keys on
`REMOTE_ADDR`, so a site served through a reverse proxy or CDN needs the
real client IP passed through (e.g. `X-Forwarded-For` trusted and mapped
back onto `REMOTE_ADDR`) or every visitor shares one limit.

No key is required, and everything degrades cleanly without one: the finder
still lists, filters and searches by name/town, the map area shows a
placeholder instead of a canvas, and "Nearest first" stays disabled. Once a
key is saved, the map and "Nearest first" sort activate automatically —
nothing else to wire up.

`tests/stockists-geocode.test.php` stubs Google's HTTP response (via
`pre_http_request`) rather than calling the real API, so it needs no key and
is safe to run at any time; like `product-helpers.test.php` and
`product-page.test.php`, it briefly sets the Maps key option and a test
stockist record and restores both in a `finally` block.

`tests/stockists-page.test.php` reads `gt_maps_key()` and asserts the
matching branch (map canvas + Maps JS + enabled "Nearest first" with a key;
placeholder + no Maps JS + disabled "Nearest first" without one), so the
whole suite is green regardless of whether this install has a key
configured — it doesn't assume either state.

## Image sizes

Plan 2 registered ten new image sizes (`gt-product-main`, `gt-product-thumb`,
`gt-brand-hero`, `gt-brand-hero-sm`, `gt-brand-step`, `gt-brand-step-sm`,
`gt-brand-pair`, `gt-brand-pair-sm`, `gt-knowledge`, `gt-knowledge-sm`),
asserted by `tests/product-helpers.test.php`. Registering a size only affects
uploads made after that point — real media added to the library before this
deploy will not have these cropped variants. After deploying, regenerate
thumbnails for existing media with `tests/bin/wpx media regenerate` (or an
equivalent regen plugin) so the new sizes exist for pre-existing images too.

## Manual Phase Two check

The shop currently runs in "enquiry mode" (`GT_SHOP_ENQUIRY_MODE` is `true`
in `inc/woocommerce.php`), which hides WooCommerce's price and add-to-cart
markup in favour of stockist/expert CTAs. To confirm the Phase Two path
(real price + cart) still works once enquiry mode is switched off:

1. On a scratch copy of the site (not production), add to `wp-config.php`,
   above the `wp-settings.php` require: `define( 'GT_SHOP_ENQUIRY_MODE',
   false );`
2. Load a product page and confirm WooCommerce's price and add-to-cart
   (including the size variation form) render in the summary, between the
   feature tick list and the stockist/expert CTAs.
