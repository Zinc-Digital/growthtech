<?php
/**
 * Custom post types.
 */

defined( 'ABSPATH' ) || exit;

/**
 * News — Figma 384:4631 (listing) and 387:5948 (article).
 *
 * Gutenberg is on so the article body is built from the theme's ACF blocks,
 * and the archive lives at /news/.
 */
function gt_register_post_types() {
	$labels = array(
		'name'               => __( 'News', 'gt' ),
		'singular_name'      => __( 'News story', 'gt' ),
		'add_new'            => __( 'Add story', 'gt' ),
		'add_new_item'       => __( 'Add news story', 'gt' ),
		'edit_item'          => __( 'Edit news story', 'gt' ),
		'new_item'           => __( 'New news story', 'gt' ),
		'view_item'          => __( 'View news story', 'gt' ),
		'view_items'         => __( 'View News', 'gt' ),
		'search_items'       => __( 'Search News', 'gt' ),
		'not_found'          => __( 'No news stories yet', 'gt' ),
		'not_found_in_trash' => __( 'No news stories in the bin', 'gt' ),
		'all_items'          => __( 'All News', 'gt' ),
		'archives'           => __( 'News archive', 'gt' ),
		'menu_name'          => __( 'News', 'gt' ),
	);

	register_post_type( 'news', array(
		'labels'        => $labels,
		'public'        => true,
		'has_archive'   => 'news',
		'rewrite'       => array( 'slug' => 'news', 'with_front' => false ),
		'menu_icon'     => 'dashicons-megaphone',
		'menu_position' => 5,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
		'show_in_rest'  => true,
	) );
}
add_action( 'init', 'gt_register_post_types' );

/**
 * Nine stories to a page, three rows of three, newest first — and the sort
 * control on the archive flips that.
 */
function gt_news_archive_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'news' ) ) {
		return;
	}
	$query->set( 'posts_per_page', 9 );
	$query->set( 'order', 'oldest' === gt_news_sort() ? 'ASC' : 'DESC' );
}
add_action( 'pre_get_posts', 'gt_news_archive_query' );

/** The archive's chosen sort: 'recent' (default) or 'oldest'. */
function gt_news_sort() {
	$sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return 'oldest' === $sort ? 'oldest' : 'recent';
}

/**
 * A story's standfirst — the sentence under the title on the article and the
 * rest of the sentence on the card. Falls back to the excerpt.
 *
 * @param int $post_id
 * @return string
 */
function gt_news_standfirst( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$text    = function_exists( 'get_field' ) ? (string) get_field( 'standfirst', $post_id ) : '';
	if ( '' === trim( $text ) ) {
		$text = get_the_excerpt( $post_id );
	}

	return trim( wp_strip_all_tags( $text ) );
}

/**
 * Reading time in whole minutes, from the rendered blocks, at 200 words a
 * minute. An explicit value on the story wins.
 *
 * @param int $post_id
 * @return int
 */
function gt_news_read_time( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$set     = function_exists( 'get_field' ) ? (int) get_field( 'read_time', $post_id ) : 0;
	if ( $set > 0 ) {
		return $set;
	}
	$post = get_post( $post_id );
	if ( ! $post ) {
		return 0;
	}
	$words = str_word_count( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) );

	return max( 1, (int) ceil( $words / 200 ) );
}

/** The byline under the title, with a Theme Settings default. */
function gt_news_byline( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$byline  = function_exists( 'get_field' ) ? (string) get_field( 'byline', $post_id ) : '';
	if ( '' === trim( $byline ) && function_exists( 'get_field' ) ) {
		$byline = (string) get_field( 'news_default_byline', 'option' );
	}

	return trim( $byline );
}

/**
 * Other stories for the "Catch up on other news stories" carousel: the most
 * recent, never the one being read.
 *
 * @param int $exclude Story to leave out.
 * @param int $limit
 * @return WP_Post[]
 */
function gt_news_related( $exclude = 0, $limit = 9 ) {
	return get_posts( array(
		'post_type'        => 'news',
		'post_status'      => 'publish',
		'posts_per_page'   => (int) $limit,
		'exclude'          => $exclude ? array( (int) $exclude ) : array(),
		'orderby'          => 'date',
		'order'            => 'DESC',
		'suppress_filters' => false,
	) );
}
