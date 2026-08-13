<?php 
//Theme Options
if(function_exists('acf_add_options_page')) {
	acf_add_options_page(array(
		'page_title' 	=> 'Theme General Settings',
		'menu_title'	=> 'Theme Settings',
		'menu_slug' 	=> 'theme-general-settings',
		'capability'	=> 'edit_posts',
		'redirect'		=> false
	));
}

//Register blocks
function register_acf_block_types() {


	// Two Column Content With Stats
	// acf_register_block_type(array(
	// 	'name'				=> 'card-two-column-stats',
	// 	'title'				=> __('Two Columns With Stats'),
	// 	'description'		=> __('Two Columns With Stats'),
	// 	'render_template'	=> 'template-parts/blocks/card-two-column-with-stats.php',
	// 	'category'			=> 'formatting',
	// 	'mode'	=> 'edit',
	// 	'supports' => array('mode' => false, 'anchor' => true),
	// 	'icon'				=> 'layout'
	// ));

	// What's your growing ecosystem?
	acf_register_block_type(array(
		'name'				=> 'growing-ecosystem',
		'title'				=> __('Growing Ecosystem', 'gt'),
		'description'		=> __('Centred intro with a row of image cards and a closing call to action.', 'gt'),
		'render_template'	=> 'template-parts/blocks/growing-ecosystem.php',
		'category'			=> 'formatting',
		'keywords'			=> array('ecosystem', 'cards', 'grid'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array('is_example' => true),
			),
		),
		'icon'				=> 'grid-view'
	));

	// Formulated by experts — full-bleed image band with pinned tags
	acf_register_block_type(array(
		'name'				=> 'feature-band',
		'title'				=> __('Feature Band', 'gt'),
		'description'		=> __('Full-width image with copy, a call to action and tags pinned over the image.', 'gt'),
		'render_template'	=> 'template-parts/blocks/feature-band.php',
		'category'			=> 'formatting',
		'keywords'			=> array('band', 'image', 'tags', 'feature'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array('is_example' => true),
			),
		),
		'icon'				=> 'format-image'
	));

	// Become the expert — intro, guide cards, closing CTA
	acf_register_block_type(array(
		'name'				=> 'guide-cards',
		'title'				=> __('Guide Cards', 'gt'),
		'description'		=> __('Centred intro with a row of image cards and a closing call to action.', 'gt'),
		'render_template'	=> 'template-parts/blocks/guide-cards.php',
		'category'			=> 'formatting',
		'keywords'			=> array('guides', 'cards', 'academy', 'slider'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array('is_example' => true),
			),
		),
		'icon'				=> 'index-card'
	));

	// Bring the science home — copy one side, image the other
	acf_register_block_type(array(
		'name'				=> 'split-band',
		'title'				=> __('Split Band', 'gt'),
		'description'		=> __('Black band with copy and a call to action on one side and an image on the other.', 'gt'),
		'render_template'	=> 'template-parts/blocks/split-band.php',
		'category'			=> 'formatting',
		'keywords'			=> array('split', 'image', 'band', 'text'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array('is_example' => true),
			),
		),
		'icon'				=> 'align-pull-right'
	));

}

/**
 * Drag-to-position UI for the Feature Band tags. Editor only.
 */
function gt_block_editor_assets() {
	$version = wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'gt-admin',
		get_template_directory_uri() . '/assets/css/admin.css',
		array(),
		$version
	);

	wp_enqueue_script(
		'gt-admin-tag-map',
		get_template_directory_uri() . '/assets/js/admin-tag-map.js',
		array( 'jquery' ),
		$version,
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'gt_block_editor_assets' );

/**
 * Register — but do not enqueue — the shared card slider. Block templates
 * enqueue it only when they hold more cards than their row can show, so pages
 * using a block in its designed form ship no extra JavaScript.
 */
function gt_register_block_scripts() {
	wp_register_script(
		'gt-block-slider',
		get_template_directory_uri() . '/assets/js/block-slider.js',
		array( 'jquery', 'slick-js' ),
		wp_get_theme()->get( 'Version' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'gt_register_block_scripts' );

if( function_exists('acf_register_block_type') ) {
	add_action('acf/init', 'register_acf_block_types');
}


//Keep hand-named acf-json files instead of reverting to the group key
add_filter( 'acf/json/save_file_name', 'gt_acf_json_file_name', 10, 3 );
function gt_acf_json_file_name( $filename, $post, $load_path ) {
	return $load_path ? basename( $load_path ) : $filename;
}

add_filter( 'acf/the_field/escape_html_optin', '__return_true' );
add_action( 'acf/init', 'set_acf_settings' );
function set_acf_settings() {
    acf_update_setting( 'enable_shortcode', false );
}
?>