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

}

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