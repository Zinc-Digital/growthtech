<?php
/**
 * Plant Academy — Figma 384:2106 (landing), 384:2243 (category) and
 * 384:2407 (guide).
 *
 * Guides are their own post type sitting under a Page at /plant-academy/, so
 * the landing is built from blocks like any other page. Categories live in the
 * guide's own URL, matching the breadcrumb the design draws:
 *
 *   /plant-academy/                                    the landing Page
 *   /plant-academy/growing-methods/                    the category archive
 *   /plant-academy/growing-methods/the-coco-coir-…/    a guide
 */

defined( 'ABSPATH' ) || exit;

const GT_ACADEMY_BASE = 'plant-academy';

/** The slug used when a guide has no category yet. */
const GT_ACADEMY_UNFILED = 'guides';

/**
 * The guide post type and its category taxonomy.
 *
 * The post type carries no archive of its own — the Page at /plant-academy/
 * owns that URL — and its permalink embeds the category.
 */
function gt_academy_register() {
	register_taxonomy(
		'academy_cat',
		'guide',
		array(
			'labels'            => array(
				'name'          => __( 'Categories', 'gt' ),
				'singular_name' => __( 'Category', 'gt' ),
				'menu_name'     => __( 'Categories', 'gt' ),
				'add_new_item'  => __( 'Add category', 'gt' ),
				'edit_item'     => __( 'Edit category', 'gt' ),
				'all_items'     => __( 'All categories', 'gt' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			// One segment only, so a guide's two-segment URL cannot be
			// swallowed by the category rule.
			'rewrite'           => array(
				'slug'         => GT_ACADEMY_BASE,
				'with_front'   => false,
				'hierarchical' => false,
			),
		)
	);

	register_post_type(
		'guide',
		array(
			'labels'        => array(
				'name'               => __( 'Plant Academy', 'gt' ),
				'singular_name'      => __( 'Guide', 'gt' ),
				'add_new'            => __( 'Add guide', 'gt' ),
				'add_new_item'       => __( 'Add guide', 'gt' ),
				'edit_item'          => __( 'Edit guide', 'gt' ),
				'new_item'           => __( 'New guide', 'gt' ),
				'view_item'          => __( 'View guide', 'gt' ),
				'view_items'         => __( 'View guides', 'gt' ),
				'search_items'       => __( 'Search guides', 'gt' ),
				'not_found'          => __( 'No guides yet', 'gt' ),
				'not_found_in_trash' => __( 'No guides in the bin', 'gt' ),
				'all_items'          => __( 'All guides', 'gt' ),
				'menu_name'          => __( 'Plant Academy', 'gt' ),
			),
			'public'        => true,
			'has_archive'   => false, // the landing Page owns /plant-academy/
			'rewrite'       => array(
				'slug'       => GT_ACADEMY_BASE . '/%academy_cat%',
				'with_front' => false,
			),
			'menu_icon'     => 'dashicons-welcome-learn-more',
			'menu_position' => 6,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
			'show_in_rest'  => true,
			'taxonomies'    => array( 'academy_cat' ),
		)
	);
}
add_action( 'init', 'gt_academy_register' );

/** Swap %academy_cat% for the guide's own category. */
function gt_academy_permalink( $link, $post ) {
	if ( ! $post instanceof WP_Post || 'guide' !== $post->post_type || false === strpos( $link, '%academy_cat%' ) ) {
		return $link;
	}

	$term = gt_academy_primary_term( $post->ID );

	return str_replace( '%academy_cat%', $term ? $term->slug : GT_ACADEMY_UNFILED, $link );
}
add_filter( 'post_type_link', 'gt_academy_permalink', 10, 2 );

/**
 * Guides filed under no category still need a URL, so allow the placeholder
 * slug through as a real route.
 */
function gt_academy_unfiled_rule( $rules ) {
	$extra = array(
		GT_ACADEMY_BASE . '/' . GT_ACADEMY_UNFILED . '/([^/]+)/?$' => 'index.php?guide=$matches[1]',
	);

	return $extra + $rules;
}
add_filter( 'rewrite_rules_array', 'gt_academy_unfiled_rule' );

/** A guide's category — the first one alphabetically when several are set. */
function gt_academy_primary_term( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$terms   = get_the_terms( $post_id, 'academy_cat' );

	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}

	usort( $terms, function ( $a, $b ) {
		return strcmp( $a->name, $b->name );
	} );

	return $terms[0];
}

/** A Theme Settings value, falling back to the wording the design shows. */
function gt_academy_setting( $name, $default = '' ) {
	$value = function_exists( 'get_field' ) ? (string) get_field( $name, 'option' ) : '';

	return '' !== trim( $value ) ? $value : $default;
}

/** The landing Page, found by its slug. */
function gt_academy_landing_url() {
	$page = get_page_by_path( GT_ACADEMY_BASE );

	return $page ? get_permalink( $page ) : home_url( '/' . GT_ACADEMY_BASE . '/' );
}

/** A guide's standfirst — the ACF field, or the excerpt. */
function gt_academy_standfirst( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$text    = function_exists( 'get_field' ) ? (string) get_field( 'standfirst', $post_id ) : '';
	if ( '' === trim( $text ) ) {
		$text = get_the_excerpt( $post_id );
	}

	return trim( wp_strip_all_tags( $text ) );
}

/**
 * Reading time in whole minutes. An explicit value on the guide wins,
 * otherwise it is counted from the rendered blocks at 200 words a minute.
 */
function gt_academy_read_time( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$set     = function_exists( 'get_field' ) ? (int) get_field( 'read_time', $post_id ) : 0;
	if ( $set > 0 ) {
		return $set;
	}

	// gt_news_read_time() already counts a post's words; reuse rather than
	// keep a second copy of the same sum.
	return function_exists( 'gt_news_read_time' ) ? gt_news_read_time( $post_id ) : 0;
}

/** Who wrote the guide — the guide's own byline, or the Theme Settings default. */
function gt_academy_byline( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$byline  = function_exists( 'get_field' ) ? (string) get_field( 'byline', $post_id ) : '';
	if ( '' === trim( $byline ) && function_exists( 'get_field' ) ) {
		$byline = (string) get_field( 'academy_default_byline', 'option' );
	}

	return trim( $byline );
}

/**
 * Every category, in the order the Academy teaches them.
 *
 * The design runs Propagation first and Plant care last, which is neither
 * alphabetical nor by count, so each category carries its own Order value.
 * Categories without one fall in behind, by name.
 */
function gt_academy_categories( $exclude = 0 ) {
	$terms = get_terms( array(
		'taxonomy'   => 'academy_cat',
		'hide_empty' => false,
		'orderby'    => 'name',
		'exclude'    => $exclude ? array( (int) $exclude ) : array(),
	) );

	if ( is_wp_error( $terms ) ) {
		return array();
	}

	usort( $terms, function ( $a, $b ) {
		$oa = (int) gt_term_field( 'order', $a, 10 );
		$ob = (int) gt_term_field( 'order', $b, 10 );

		return $oa === $ob ? strcmp( $a->name, $b->name ) : $oa - $ob;
	} );

	return $terms;
}

/**
 * How the category archive is ordered, and the label for the control.
 *
 * @return array{key: string, label: string, args: array}
 */
function gt_academy_sort() {
	$sorts = array(
		'recent' => array( 'label' => __( 'Most recent first', 'gt' ), 'args' => array( 'orderby' => 'date', 'order' => 'DESC' ) ),
		'oldest' => array( 'label' => __( 'Oldest first', 'gt' ), 'args' => array( 'orderby' => 'date', 'order' => 'ASC' ) ),
		'title'  => array( 'label' => __( 'A to Z', 'gt' ), 'args' => array( 'orderby' => 'title', 'order' => 'ASC' ) ),
		'read'   => array( 'label' => __( 'Quickest read', 'gt' ), 'args' => array( 'meta_key' => 'read_time', 'orderby' => 'meta_value_num', 'order' => 'ASC' ) ),
	);

	$key = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$key = array_key_exists( $key, $sorts ) ? $key : 'recent';

	return array( 'key' => $key, 'label' => $sorts[ $key ]['label'], 'args' => $sorts[ $key ]['args'], 'all' => $sorts );
}

/** Twelve guides to a category page, in the chosen order. */
function gt_academy_archive_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_tax( 'academy_cat' ) ) {
		return;
	}

	$sort = gt_academy_sort();
	$query->set( 'posts_per_page', 12 );
	foreach ( $sort['args'] as $key => $value ) {
		$query->set( $key, $value );
	}
}
add_action( 'pre_get_posts', 'gt_academy_archive_query' );

/**
 * Guides related to this one: the editor's picks first, topped up with others
 * from the same category.
 *
 * @param int $post_id The guide being read.
 * @param int $limit   How many to return.
 * @return WP_Post[]
 */
function gt_academy_related( $post_id, $limit = 3 ) {
	$picked = function_exists( 'get_field' ) ? get_field( 'related', $post_id ) : array();
	$picked = is_array( $picked ) ? array_filter( array_map( 'get_post', $picked ) ) : array();
	$picked = array_slice( $picked, 0, $limit );

	if ( count( $picked ) >= $limit ) {
		return $picked;
	}

	$term = gt_academy_primary_term( $post_id );
	$fill = get_posts( array(
		'post_type'        => 'guide',
		'posts_per_page'   => $limit - count( $picked ),
		'post__not_in'     => array_merge( array( (int) $post_id ), wp_list_pluck( $picked, 'ID' ) ),
		'ignore_sticky_posts' => true,
		'tax_query'        => $term ? array( array(
			'taxonomy' => 'academy_cat',
			'field'    => 'term_id',
			'terms'    => $term->term_id,
		) ) : array(),
	) );

	return array_merge( $picked, $fill );
}

/**
 * The guide's own sections, for the sidebar's contents list.
 *
 * Any block that opens with a heading counts as a section — Section Text and
 * Media List both do. A block only carries an id when the editor has set an
 * anchor, so one is derived from the heading otherwise, and
 * gt_academy_section_id() puts the same id on the rendered block.
 *
 * @param int $post_id The guide.
 * @return array<int, array{id: string, title: string}>
 */
function gt_academy_sections( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return array();
	}

	$out  = array();
	$seen = array();

	foreach ( parse_blocks( $post->post_content ) as $block ) {
		if ( empty( $block['blockName'] ) || ! isset( gt_academy_section_blocks()[ $block['blockName'] ] ) ) {
			continue;
		}

		$title = trim( (string) ( $block['attrs']['data']['heading'] ?? '' ) );
		if ( '' === $title ) {
			continue;
		}

		$id = ! empty( $block['attrs']['anchor'] ) ? sanitize_title( $block['attrs']['anchor'] ) : 'section-' . sanitize_title( $title );
		if ( isset( $seen[ $id ] ) ) {
			$seen[ $id ]++;
			$id .= '-' . $seen[ $id ];
		} else {
			$seen[ $id ] = 1;
		}

		$out[] = array( 'id' => $id, 'title' => $title );
	}

	return $out;
}

/**
 * The blocks that open a section, mapped to the class their wrapper carries.
 *
 * @return array<string, string>
 */
function gt_academy_section_blocks() {
	/**
	 * Filter which blocks appear in a guide's contents list.
	 *
	 * @param array $blocks Block name => the class on its <section> wrapper.
	 */
	return apply_filters( 'gt_academy_section_blocks', array(
		'acf/section-text' => 'b-section-text',
		'acf/media-list'   => 'b-media-list',
	) );
}

/**
 * Give each section block on a guide the id its contents link points at.
 *
 * The blocks render in the same order gt_academy_sections() reads them, so an
 * incrementing index keeps the two in step without storing anything.
 */
function gt_academy_section_id( $html, $block ) {
	static $index = 0;

	$blocks = gt_academy_section_blocks();

	if ( ! is_singular( 'guide' ) || empty( $block['blockName'] ) || ! isset( $blocks[ $block['blockName'] ] ) ) {
		return $html;
	}

	$sections = gt_academy_sections( get_the_ID() );
	$section  = $sections[ $index ] ?? null;
	$index++;

	if ( ! $section || ! empty( $block['attrs']['anchor'] ) ) {
		return $html; // an editor-set anchor already renders its own id
	}

	$class = $blocks[ $block['blockName'] ];

	// Only the opening tag matters: a block may carry ids further in for its
	// own aria-labelledby, which is not the same as already being anchored.
	if ( preg_match( '/<section[^>]*\sid=/', $html ) ) {
		return $html;
	}

	return preg_replace(
		'/<section class="' . preg_quote( $class, '/' ) . '/',
		'<section id="' . esc_attr( $section['id'] ) . '" class="' . $class,
		$html,
		1
	);
}
add_filter( 'render_block', 'gt_academy_section_id', 10, 2 );

/**
 * Re-play the closing bands from the landing Page.
 *
 * The category archive ends with the same "Learn the science" list and the
 * same products band the landing carries. Rather than a second copy of both,
 * the landing Page's own blocks are replayed here, so an editor changes the
 * copy once. Only the allowed blocks are rendered — the rest of the landing
 * stays on the landing.
 *
 * @param string[] $allow Block names to replay.
 */
function gt_academy_render_landing_tail( array $allow = array( 'acf/academy-categories', 'acf/content-slider' ) ) {
	$page = get_page_by_path( GT_ACADEMY_BASE );
	if ( ! $page ) {
		return;
	}

	foreach ( parse_blocks( $page->post_content ) as $block ) {
		if ( empty( $block['blockName'] ) || ! in_array( $block['blockName'], $allow, true ) ) {
			continue;
		}
		echo render_block( $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block output.
	}
}

/**
 * Other guides, newest first — the "Explore other guides." band at the foot of
 * a guide.
 *
 * Deliberately broader than gt_academy_related(): the sidebar's "Keep
 * learning" is the curated three, so the band is there to open the library up
 * rather than repeat them.
 *
 * @param int $exclude The guide being read.
 * @param int $limit   How many to return.
 * @return WP_Post[]
 */
function gt_academy_other_guides( $exclude = 0, $limit = 9 ) {
	return get_posts( array(
		'post_type'           => 'guide',
		'posts_per_page'      => (int) $limit,
		'post__not_in'        => $exclude ? array( (int) $exclude ) : array(),
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
	) );
}

/** Flush rewrites once after the post type lands, not on every request. */
function gt_academy_maybe_flush() {
	if ( get_option( 'gt_academy_rewrites' ) === GT_ACADEMY_BASE ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'gt_academy_rewrites', GT_ACADEMY_BASE );
}
add_action( 'init', 'gt_academy_maybe_flush', 99 );
