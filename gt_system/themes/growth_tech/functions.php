<?php
/**
 * Version a theme asset by its file modification time.
 *
 * The theme's own version in style.css never changes, so every asset shipped as
 * ?ver=1.0 and browsers cached it indefinitely — rebuilt CSS and JS would not
 * reach anyone who had already loaded the old file. Falling back to the theme
 * version keeps third-party or missing files behaving as before.
 *
 * @param string $relative_path Path from the theme root, e.g. /assets/css/main.css
 * @return string
 */
function gt_asset_version($relative_path) {
	$file = get_template_directory() . $relative_path;

	return file_exists($file) ? (string) filemtime($file) : wp_get_theme()->get('Version');
}

//Register theme support
function gt_theme_support() {
	add_theme_support('post-thumbnails');
	add_theme_support('title-tag');
	add_theme_support('html5', array('search-form', 'navigation-widgets'));

	// Ecosystem card: 413 x 402 in the design, plus a 2x version. Both share the
	// aspect ratio, which is what lets WordPress build a srcset from them.
	add_image_size('gt-card', 826, 804, true);
	add_image_size('gt-card-sm', 413, 402, true);

	// Guide card: 347 x 402 in the design, plus a 2x version.
	add_image_size('gt-guide', 694, 804, true);
	add_image_size('gt-guide-sm', 347, 402, true);

	// Content slider card: 317.5 x 400 in the design, plus a 2x version.
	add_image_size('gt-slide', 636, 800, true);
	add_image_size('gt-slide-sm', 318, 400, true);

	// Split band: half the 1440 frame, plus a 2x version.
	add_image_size('gt-split', 1440, 1120, true);
	add_image_size('gt-split-sm', 720, 560, true);

	// Feature band: 1440 x 660 full-bleed, plus a 1x-ish step for the srcset.
	add_image_size('gt-band', 2160, 990, true);
	add_image_size('gt-band-sm', 1080, 495, true);

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
}
add_action('after_setup_theme', 'gt_theme_support');

//Register stylesheets
function gt_register_styles() {
	$theme_version = wp_get_theme()->get('Version');
	// DM Sans + Cormorant Garamond are the header/mega-menu faces in the Figma file.
	wp_enqueue_style('gt-fonts', 'https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,700;9..40,900&family=Cormorant+Garamond:wght@700&display=swap', array(), null);
	wp_enqueue_style('bootstrap', get_template_directory_uri() . '/assets/css/bootstrap.min.css', array(), $theme_version, false);
	wp_enqueue_style( 'slick-theme-css', get_template_directory_uri() . '/assets/css/slick-theme.css', array(), $theme_version );
	wp_enqueue_style('slick-css', get_template_directory_uri() . '/assets/css/slick.css', array(), $theme_version, false);
	wp_enqueue_style('main-css', get_template_directory_uri() . '/assets/css/main.css', array(), gt_asset_version('/assets/css/main.css'));
	wp_enqueue_style('style-css', get_template_directory_uri() . '/assets/css/style.css', array(), $theme_version, false);
}
add_action('wp_enqueue_scripts', 'gt_register_styles');


//Register scripts
function gt_register_scripts() {
	$theme_version = wp_get_theme()->get('Version');
	wp_enqueue_script( 'boot', get_template_directory_uri() . '/assets/js/bootstrap.min.js', array('jquery'), $theme_version, false);
	wp_enqueue_script('main-js', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), gt_asset_version('/assets/js/main.js'), false);
	wp_enqueue_script('gt-header', get_template_directory_uri() . '/assets/js/header.js', array(), gt_asset_version('/assets/js/header.js'), true);
	wp_enqueue_script('slick-js', get_template_directory_uri() . '/assets/js/slick.min.js', array('jquery'), $theme_version, false);
	wp_enqueue_script( 'slick-slider', get_template_directory_uri() . '/assets/js/slick.js');
	wp_enqueue_script('gt-banner', get_template_directory_uri() . '/assets/js/banner.js', array('jquery', 'slick-js'), gt_asset_version('/assets/js/banner.js'), true);
}
add_action('wp_enqueue_scripts', 'gt_register_scripts');

//Register Menus
function gt_menus() {
	$locations = array(
		'header'				=> __('Header Menu',	'gt'),
		'footer_nav_1'			=> __('Footer Nav 1',	'gt'),
		'footer_nav_2'			=> __('Footer Nav 2',	'gt'),
		'footer_nav_3'			=> __('Footer Nav 3',	'gt'),
		'footer_legal'			=> __('Footer Legal',	'gt')
	);

	register_nav_menus($locations);
}
add_action( 'init', 'gt_menus' );



// Register widget areas
function register_vel_sidebars(){
	register_sidebar( array(
		'name'			=> 'Footer company info',
		'id'			=> 'footer_company_info',
		'before_widget'	=> '<div>',
		'after_widget'	=> '</div>',
	));
	register_sidebar( array(
		'name'			=> 'Footer Nav 1',
		'id'			=> 'footer_nav_1',
		'before_widget'	=> '<div>',
		'after_widget'	=> '</div>',
		'before_title'	=> '<h3>',
		'after_title'	=> '</h3>',
	));
	register_sidebar( array(
		'name'			=> 'Footer Nav 2',
		'id'			=> 'footer_nav_2',
		'before_widget'	=> '<div>',
		'after_widget'	=> '</div>',
		'before_title'	=> '<h3>',
		'after_title'	=> '</h3>',
	));
	register_sidebar( array(
		'name'			=> 'Footer contact info',
		'id'			=> 'footer_contact_info',
		'before_widget'	=> '<div>',
		'after_widget'	=> '</div>',
	));
}
add_action('widgets_init', 'register_vel_sidebars');



// Enable svg support
add_filter( 'wp_check_filetype_and_ext', function($data, $file, $filename, $mimes) {

$filetype = wp_check_filetype( $filename, $mimes );
	return [
		'ext'             => $filetype['ext'],
		'type'            => $filetype['type'],
		'proper_filename' => $data['proper_filename']
	];
}, 10, 4 );
	
function cc_mime_types( $mimes ){
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}
add_filter( 'upload_mimes', 'cc_mime_types' );
	function fix_svg() {
	echo '<style type="text/css">
			.attachment-266x266, .thumbnail img {
				width: 100% !important;
				height: auto !important;
			}
			</style>';
	}
add_action( 'admin_head', 'fix_svg' );


// Clear pre fill customer details bug
add_filter('woocommerce_checkout_get_value','__return_empty_string', 1, 1);


// Call posts news
/*
add_action( 'init', 'news_register_taxonomy_for_object_type' );
function news_register_taxonomy_for_object_type() {
    register_taxonomy_for_object_type( 'post_tag', 'news' );
}; */

include_once __DIR__ . '/inc/header.php';
include_once __DIR__ . '/inc/post-types.php';
include_once __DIR__ . '/inc/register-blocks.php';
include_once __DIR__ . '/inc/taxonomies.php';
include_once __DIR__ . '/inc/woocommerce.php';
include_once __DIR__ . '/inc/woocommerce-helpers.php';
include_once __DIR__ . '/inc/woocommerce-shop.php';