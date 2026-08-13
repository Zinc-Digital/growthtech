<?php
//Register theme support
function gt_theme_support() {
	add_theme_support('post-thumbnails');
	add_theme_support('title-tag');
	add_theme_support('html5', array('search-form', 'navigation-widgets'));
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
	wp_enqueue_style('main-css', get_template_directory_uri() . '/assets/css/main.css', array(), $theme_version);
	wp_enqueue_style('style-css', get_template_directory_uri() . '/assets/css/style.css', array(), $theme_version, false);
}
add_action('wp_enqueue_scripts', 'gt_register_styles');


//Register scripts
function gt_register_scripts() {
	$theme_version = wp_get_theme()->get('Version');
	wp_enqueue_script( 'boot', get_template_directory_uri() . '/assets/js/bootstrap.min.js', array('jquery'), $theme_version, false);
	wp_enqueue_script('main-js', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), $theme_version, false);
	wp_enqueue_script('gt-header', get_template_directory_uri() . '/assets/js/header.js', array(), $theme_version, true);
	wp_enqueue_script('slick-js', get_template_directory_uri() . '/assets/js/slick.min.js', array('jquery'), $theme_version, false);
	wp_enqueue_script( 'slick-slider', get_template_directory_uri() . '/assets/js/slick.js');
	wp_enqueue_script('gt-banner', get_template_directory_uri() . '/assets/js/banner.js', array('jquery', 'slick-js'), $theme_version, true);
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