<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>" />
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <script src="https://kit.fontawesome.com/ef400fc0cd.js" crossorigin="anonymous"></script>

  <?php wp_head(); ?>
</head>

<body <?php body_class( gt_header_is_overlay() ? 'has-overlay-header' : '' ); ?>>
  <?php wp_body_open(); ?>

  <?php
  $gt_logo    = gt_header_logo();
  $gt_overlay = gt_header_is_overlay();
  ?>

  <div class="site-header-outer <?php echo $gt_overlay ? 'site-header-outer--overlay' : ''; ?>" data-header-outer>
    <header class="site-header <?php echo $gt_overlay ? 'site-header--overlay' : ''; ?>" data-header>
      <div class="site-header__inner">

        <?php gt_render_desktop_nav( 'header', 'left' ); ?>

        <a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"
          aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
          <img src="<?php echo esc_url( $gt_logo['url'] ); ?>" alt="<?php echo esc_attr( $gt_logo['alt'] ); ?>"
            width="175" height="97" />
        </a>

        <div class="site-header__end">
          <?php gt_render_desktop_nav( 'header_secondary', 'right' ); ?>

          <div class="site-header__search">
            <?php get_search_form(); ?>
          </div>
        </div>

        <button type="button" class="site-header__burger" data-nav-open aria-expanded="false"
          aria-controls="gt-mobile-nav">
          <span></span>
          <span class="screen-reader-text"><?php esc_html_e( 'Menu', 'gt' ); ?></span>
        </button>

      </div>
    </header>
  </div>

  <div class="gt-mega-scrim" data-mega-scrim aria-hidden="true"></div>

  <!-- Mobile slide-in panel -->
  <div class="site-nav-mobile" id="gt-mobile-nav" data-mobile-nav>
    <div class="site-nav-mobile__backdrop" data-nav-close></div>

    <div class="site-nav-mobile__panel" role="dialog" aria-modal="true"
      aria-label="<?php esc_attr_e( 'Menu', 'gt' ); ?>">
      <button type="button" class="site-nav-mobile__close" data-nav-close>
        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/icons/close.svg' ); ?>" alt=""
          width="21" height="21" />
        <span class="screen-reader-text"><?php esc_html_e( 'Close menu', 'gt' ); ?></span>
      </button>

      <div class="site-nav-mobile__search">
        <?php get_search_form(); ?>
      </div>

      <?php gt_render_mobile_nav(); ?>
    </div>
  </div>
