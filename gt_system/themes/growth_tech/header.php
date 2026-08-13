<!DOCTYPE html>
<html lang="en">

<head <?php language_attributes(); ?>>
  <meta charset="<?php bloginfo( 'charset' ); ?>" />
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php wp_head(); ?>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <!--Replace this with theme font if using Google 
		<link href="https://fonts.googleapis.com/css2?family=Funnel+Display:wght@300..800&display=swap" rel="stylesheet">
	-->

  <script src="https://kit.fontawesome.com/ef400fc0cd.js" crossorigin="anonymous"></script>

</head>

<body <?php body_class(); ?>>

  <?php $logo = get_field('logo', 'option');  ?>

  <!-- Desktop header -->
  <header class="header-desktop">
    <div class="container d-flex flex-wrap align-center">
      <a href="/" class="d-flex align-items-center mb-3 mb-lg-0 me-lg-auto">
        <?php  $logo_url = $logo['url']; 	$logo_alt = $logo['alt']; ?>
        <img class="logo" src="<?php echo esc_html($logo_url); ?>" alt="<?php echo esc_attr($logo_alt); ?>" />
      </a>

      <nav class="primary d-flex">
        <?php wp_nav_menu(array(
				'theme_location'	=> 'header',
				'container'			=> false,
				'menu_class'		=> 'show-sub-menus'
			));?>
      </nav>
    </div>
  </header>

  <!-- Mobile header -->
  <header class="header-mobile">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
      <div class="container-fluid">
        <div class="mobile_logo">
          <a href="<?php echo home_url(); ?>">
            <?php $logo_url = $logo['url']; $logo_alt = $logo['alt']; ?>
            <img class="logo" src="<?php echo esc_html($logo_url); ?>" alt="<?php echo esc_attr($logo_url); ?>" />
          </a>
        </div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
          aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <?php wp_nav_menu(array(
				'theme_location'	=> 'header_mobile',
				'container'			=> false,
				'depth'				=> 2
			)); ?>
        </div>
      </div>
    </nav>
  </header>