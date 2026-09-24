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

	// Formulated by Growth Technology — copy plus a brand slider
	acf_register_block_type(array(
		'name'				=> 'content-slider',
		'title'				=> __('Content Slider', 'gt'),
		'description'		=> __('Copy and two calls to action beside a slider of image cards with a progress bar.', 'gt'),
		'render_template'	=> 'template-parts/blocks/content-slider.php',
		'category'			=> 'formatting',
		'keywords'			=> array('slider', 'brands', 'carousel', 'content'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array(
			'attributes' => array(
				'mode' => 'preview',
				'data' => array('is_example' => true),
			),
		),
		'icon'				=> 'slides'
	));

	// News article body — Figma 387:5948. Reusable anywhere, not just on News.
	acf_register_block_type(array(
		'name'				=> 'section-text',
		'title'				=> __('Section Text', 'gt'),
		'description'		=> __('A serif sub-heading over body copy.', 'gt'),
		'render_template'	=> 'template-parts/blocks/section-text.php',
		'category'			=> 'formatting',
		'keywords'			=> array('text', 'copy', 'heading', 'section'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array('attributes' => array('mode' => 'preview', 'data' => array('is_example' => true))),
		'icon'				=> 'editor-paragraph'
	));

	acf_register_block_type(array(
		'name'				=> 'info-band',
		'title'				=> __('Info Band', 'gt'),
		'description'		=> __('A black band with a serif label beside the copy — a top tip, a note or a pull quote.', 'gt'),
		'render_template'	=> 'template-parts/blocks/info-band.php',
		'category'			=> 'formatting',
		'keywords'			=> array('tip', 'quote', 'callout', 'band'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array('attributes' => array('mode' => 'preview', 'data' => array('is_example' => true))),
		'icon'				=> 'format-quote'
	));

	acf_register_block_type(array(
		'name'				=> 'media-list',
		'title'				=> __('Media List', 'gt'),
		'description'		=> __('Rows of a thumbnail beside a title and copy, each image openable full size.', 'gt'),
		'render_template'	=> 'template-parts/blocks/media-list.php',
		'category'			=> 'formatting',
		'keywords'			=> array('list', 'media', 'steps', 'images'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array('attributes' => array('mode' => 'preview', 'data' => array('is_example' => true))),
		'icon'				=> 'list-view'
	));

	acf_register_block_type(array(
		'name'				=> 'media-gallery',
		'title'				=> __('Media Gallery', 'gt'),
		'description'		=> __('A full-width image, or several as a slider, each openable full size.', 'gt'),
		'render_template'	=> 'template-parts/blocks/media-gallery.php',
		'category'			=> 'formatting',
		'keywords'			=> array('gallery', 'image', 'slider', 'photos'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array('attributes' => array('mode' => 'preview', 'data' => array('is_example' => true))),
		'icon'				=> 'format-gallery'
	));

	acf_register_block_type(array(
		'name'				=> 'news-carousel',
		'title'				=> __('News Carousel', 'gt'),
		'description'		=> __('The grey band of latest News stories, as a slider.', 'gt'),
		'render_template'	=> 'template-parts/blocks/news-carousel.php',
		'category'			=> 'formatting',
		'keywords'			=> array('news', 'stories', 'carousel', 'slider'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array('attributes' => array('mode' => 'preview', 'data' => array('is_example' => true))),
		'icon'				=> 'megaphone'
	));

	// About Us — Figma 384:2551. All reusable across the site.
	acf_register_block_type(array(
		'name'				=> 'page-hero',
		'title'				=> __('Page Hero', 'gt'),
		'description'		=> __('Full-bleed image banner with a heading, copy and up to two calls to action.', 'gt'),
		'render_template'	=> 'template-parts/blocks/page-hero.php',
		'category'			=> 'formatting',
		'keywords'			=> array('hero', 'banner', 'header', 'intro'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array('attributes' => array('mode' => 'preview', 'data' => array('is_example' => true))),
		'icon'				=> 'cover-image'
	));

	acf_register_block_type(array(
		'name'				=> 'image-copy',
		'title'				=> __('Image & Copy', 'gt'),
		'description'		=> __('An image beside a heading, copy, an optional quote card and calls to action.', 'gt'),
		'render_template'	=> 'template-parts/blocks/image-copy.php',
		'category'			=> 'formatting',
		'keywords'			=> array('image', 'copy', 'split', 'text'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array('attributes' => array('mode' => 'preview', 'data' => array('is_example' => true))),
		'icon'				=> 'align-pull-left'
	));

	acf_register_block_type(array(
		'name'				=> 'stats-row',
		'title'				=> __('Stats Row', 'gt'),
		'description'		=> __('A row of large figures with small labels, divided by hairlines.', 'gt'),
		'render_template'	=> 'template-parts/blocks/stats-row.php',
		'category'			=> 'formatting',
		'keywords'			=> array('stats', 'numbers', 'figures', 'facts'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array('attributes' => array('mode' => 'preview', 'data' => array('is_example' => true))),
		'icon'				=> 'chart-bar'
	));

	acf_register_block_type(array(
		'name'				=> 'statement-band',
		'title'				=> __('Statement Band', 'gt'),
		'description'		=> __('A black band with a centred statement, an eyebrow and a supporting line.', 'gt'),
		'render_template'	=> 'template-parts/blocks/statement-band.php',
		'category'			=> 'formatting',
		'keywords'			=> array('statement', 'band', 'goal', 'mission'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array('attributes' => array('mode' => 'preview', 'data' => array('is_example' => true))),
		'icon'				=> 'megaphone'
	));

	acf_register_block_type(array(
		'name'				=> 'fact-list',
		'title'				=> __('Fact List', 'gt'),
		'description'		=> __('An image beside a heading and a list of claims with their detail.', 'gt'),
		'render_template'	=> 'template-parts/blocks/fact-list.php',
		'category'			=> 'formatting',
		'keywords'			=> array('facts', 'list', 'claims', 'credentials'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array('attributes' => array('mode' => 'preview', 'data' => array('is_example' => true))),
		'icon'				=> 'editor-ul'
	));

	acf_register_block_type(array(
		'name'				=> 'timeline',
		'title'				=> __('Timeline', 'gt'),
		'description'		=> __('Milestones on a rail that can be dragged, scrolled or stepped through.', 'gt'),
		'render_template'	=> 'template-parts/blocks/timeline.php',
		'category'			=> 'formatting',
		'keywords'			=> array('timeline', 'history', 'milestones', 'years'),
		'mode'				=> 'preview',
		'supports'			=> array('mode' => true, 'anchor' => true, 'align' => false, 'jsx' => false),
		'example'			=> array('attributes' => array('mode' => 'preview', 'data' => array('is_example' => true))),
		'icon'				=> 'clock'
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
		gt_asset_version( '/assets/css/admin.css' )
	);

	wp_enqueue_script(
		'gt-admin-tag-map',
		get_template_directory_uri() . '/assets/js/admin-tag-map.js',
		array( 'jquery' ),
		gt_asset_version( '/assets/js/admin-tag-map.js' ),
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
		gt_asset_version( '/assets/js/block-slider.js' ),
		true
	);

	// Shared enlarge-image lightbox, pulled in by the blocks that offer one.
	wp_register_script(
		'gt-media-lightbox',
		get_template_directory_uri() . '/assets/js/media-lightbox.js',
		array(),
		gt_asset_version( '/assets/js/media-lightbox.js' ),
		true
	);

	// Arrows, drag and the progress bar for the Timeline block.
	wp_register_script(
		'gt-timeline',
		get_template_directory_uri() . '/assets/js/timeline.js',
		array(),
		gt_asset_version( '/assets/js/timeline.js' ),
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