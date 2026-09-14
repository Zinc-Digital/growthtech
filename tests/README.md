# Tests

WP-CLI-driven integration tests against the real MAMP site — no PHPUnit, no
mocked WordPress.

## Running

- `tests/run.sh` — runs every `tests/*.test.php` through `tests/bin/wpx
  eval-file` and exits 1 if any file fails. Safe to run any time — almost
  every file is read-only, and the one exception,
  `tests/product-helpers.test.php`, temporarily mutates two records
  (Clonex Rooting Hormone's catalog visibility and Clonex Mist's knowledge
  band override) and restores each in a `finally` block before the file
  finishes.
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
