<?php
/**
 * Search results — Figma 384:2947.
 *
 * Results are grouped rather than listed flat: each group is one post type
 * with its own tab, heading and layout. The groups are the single source of
 * truth for the tab row, the counts and the sections, so adding a content
 * type to search means adding one entry here.
 */

defined( 'ABSPATH' ) || exit;

/** Results to a page. Fewer than this and the pagination stays hidden. */
const GT_SEARCH_PER_PAGE = 12;

/**
 * The result groups, in the order the design stacks them.
 *
 * 'layout' is 'grid' for the product card grid or 'list' for the thumbnail
 * rows. 'post_type' may name a type that is not registered yet — the group
 * then reads zero, exactly as the design draws the empty News tab.
 *
 * @return array<string, array> Keyed by tab slug.
 */
function gt_search_groups() {
	$groups = array(
		'products'  => array(
			'label'     => __( 'Products', 'gt' ),
			'post_type' => 'product',
			'layout'    => 'grid',
			/* translators: %s: number of results */
			'singular'  => __( '%s product', 'gt' ),
			'plural'    => __( '%s products', 'gt' ),
			'cta'       => '',
			'read_time' => false,
		),
		'academy'   => array(
			'label'     => __( 'Plant Academy', 'gt' ),
			'post_type' => 'guide',
			'layout'    => 'list',
			/* translators: %s: number of results */
			'singular'  => __( '%s guide', 'gt' ),
			'plural'    => __( '%s guides', 'gt' ),
			'cta'       => __( 'Read guide', 'gt' ),
			'read_time' => true,
		),
		'news'      => array(
			'label'     => __( 'News', 'gt' ),
			'post_type' => 'news',
			'layout'    => 'list',
			/* translators: %s: number of results */
			'singular'  => __( '%s story', 'gt' ),
			'plural'    => __( '%s stories', 'gt' ),
			'cta'       => __( 'Read story', 'gt' ),
			'read_time' => true,
		),
		'documents' => array(
			'label'     => __( 'Useful Documents', 'gt' ),
			'post_type' => 'document',
			'layout'    => 'list',
			/* translators: %s: number of results */
			'singular'  => __( '%s document', 'gt' ),
			'plural'    => __( '%s documents', 'gt' ),
			'cta'       => __( 'Open document', 'gt' ),
			'read_time' => false,
		),
		'pages'     => array(
			'label'     => __( 'Pages', 'gt' ),
			'post_type' => 'page',
			'layout'    => 'list',
			/* translators: %s: number of results */
			'singular'  => __( '%s page', 'gt' ),
			'plural'    => __( '%s pages', 'gt' ),
			'cta'       => __( 'View page', 'gt' ),
			'read_time' => false,
		),
	);

	/**
	 * Filter the search result groups.
	 *
	 * @param array $groups Keyed by tab slug.
	 */
	return apply_filters( 'gt_search_groups', $groups );
}

/** The post types the search covers, skipping groups whose type is not registered. */
function gt_search_post_types() {
	$types = array();
	foreach ( gt_search_groups() as $group ) {
		if ( post_type_exists( $group['post_type'] ) ) {
			$types[] = $group['post_type'];
		}
	}

	return $types;
}

/** The chosen tab: a group slug, or 'all'. */
function gt_search_tab() {
	$tab = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return array_key_exists( $tab, gt_search_groups() ) ? $tab : 'all';
}

/** The URL for one tab, keeping the current search term. */
function gt_search_tab_url( $tab, $term ) {
	$args = array( 's' => $term );
	if ( 'all' !== $tab ) {
		$args['type'] = $tab;
	}

	return add_query_arg( array_map( 'rawurlencode', $args ), home_url( '/' ) );
}

/** The page being viewed. */
function gt_search_paged() {
	$paged = (int) get_query_var( 'paged' );
	if ( ! $paged ) {
		$paged = (int) get_query_var( 'page' );
	}

	return max( 1, $paged );
}

/** A results URL for one tab and page. */
function gt_search_page_url( $tab, $term, $page = 1 ) {
	$url = gt_search_tab_url( $tab, $term );

	return $page > 1 ? add_query_arg( 'paged', (int) $page, $url ) : $url;
}

/**
 * Run one group's search.
 *
 * Counts are always the group's true total; the limit and offset only choose
 * the slice on this page, so a heading can read "24 products" while the page
 * shows four of them.
 *
 * @param array  $group  One entry from gt_search_groups().
 * @param string $term   The search term.
 * @param int    $limit  How many posts to return.
 * @param int    $offset How many to skip.
 * @return WP_Query
 */
function gt_search_group_query( array $group, $term, $limit, $offset = 0 ) {
	if ( ! post_type_exists( $group['post_type'] ) || '' === trim( $term ) ) {
		return new WP_Query( array( 'post__in' => array( 0 ), 'post_type' => 'post' ) );
	}

	$args = array(
		's'                   => $term,
		'gt_search'           => true, // switches the search onto the indexed text
		'post_type'           => $group['post_type'],
		'post_status'         => 'publish',
		'posts_per_page'      => (int) $limit,
		'offset'              => (int) $offset,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => false,
	);

	// Respect the "hidden" visibility WooCommerce sets on products.
	if ( 'product' === $group['post_type'] && taxonomy_exists( 'product_visibility' ) ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'exclude-from-search' ),
				'operator' => 'NOT IN',
			),
		);
	}

	/**
	 * Filter one group's query arguments.
	 *
	 * @param array  $args  WP_Query arguments.
	 * @param array  $group The group definition.
	 * @param string $term  The search term.
	 */
	$args = apply_filters( 'gt_search_group_args', $args, $group, $term );

	return new WP_Query( $args );
}

/**
 * Every group's results for this request, ready for the template.
 *
 * Results are paged as one run of GT_SEARCH_PER_PAGE, in group order, so a
 * page can end part-way through a group and the next one carries on where it
 * left off. Group headings only appear when that group has rows on this page.
 *
 * @param string $term The search term.
 * @return array{groups: array, total: int, tab: string, paged: int, pages: int}
 */
function gt_search_results( $term ) {
	$tab    = gt_search_tab();
	$defs   = gt_search_groups();
	$counts = array();
	$total  = 0;

	// Every group is counted, whichever tab is showing — the tab row needs them.
	foreach ( $defs as $slug => $group ) {
		$counts[ $slug ] = (int) gt_search_group_query( $group, $term, 1 )->found_posts;
		$total          += $counts[ $slug ];
	}

	$scope = 'all' === $tab ? $total : ( $counts[ $tab ] ?? 0 );
	$pages = max( 1, (int) ceil( $scope / GT_SEARCH_PER_PAGE ) );
	$paged = min( gt_search_paged(), $pages );

	// Walk the groups, skipping whatever sits before this page's window.
	$skip   = ( $paged - 1 ) * GT_SEARCH_PER_PAGE;
	$room   = GT_SEARCH_PER_PAGE;
	$groups = array();

	foreach ( $defs as $slug => $group ) {
		$count = $counts[ $slug ];
		$query = null;

		if ( ( 'all' === $tab || $tab === $slug ) && $count > 0 && $room > 0 ) {
			if ( $skip >= $count ) {
				$skip -= $count;
			} else {
				$take  = min( $room, $count - $skip );
				$query = gt_search_group_query( $group, $term, $take, $skip );
				$skip  = 0;
				$room -= $take;
			}
		}

		$groups[ $slug ] = array_merge( $group, array(
			'slug'  => $slug,
			'query' => $query,
			'count' => $count,
		) );
	}

	return array(
		'groups' => $groups,
		'total'  => $total,
		'tab'    => $tab,
		'paged'  => $paged,
		'pages'  => $pages,
	);
}

/** "6 products" / "1 guide", using the group's own wording. */
function gt_search_group_count_label( array $group, $count ) {
	$pattern = 1 === (int) $count ? $group['singular'] : $group['plural'];

	return sprintf( $pattern, number_format_i18n( (int) $count ) );
}

// ---------------------------------------------------------------------------
// Searchable text
//
// Bodies are built from ACF blocks, so post_content is JSON inside HTML
// comments. WordPress searches that raw string, which means "title", "image"
// and "data" match every article while real copy held in an ACF field matches
// nothing. Each post therefore keeps a plain-text mirror of what it actually
// says, and the group queries search that instead of post_content.
// ---------------------------------------------------------------------------

const GT_SEARCH_META = '_gt_search_text';

/**
 * Pull the readable text out of a post's blocks.
 *
 * Block field values live under their own names in the attributes; the
 * underscore-prefixed twins hold field keys, and blockName holds the block's
 * slug — neither is anything a visitor would search for, so both are skipped.
 *
 * @param array $blocks Parsed blocks.
 * @return string[]
 */
function gt_search_block_text( array $blocks ) {
	$out = array();

	foreach ( $blocks as $block ) {
		if ( ! empty( $block['attrs']['data'] ) && is_array( $block['attrs']['data'] ) ) {
			foreach ( $block['attrs']['data'] as $key => $value ) {
				if ( is_string( $key ) && 0 === strpos( $key, '_' ) ) {
					continue; // field key, not content
				}
				if ( is_scalar( $value ) ) {
					$out[] = (string) $value;
				}
			}
		}

		if ( ! empty( $block['innerHTML'] ) ) {
			$out[] = (string) $block['innerHTML'];
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$out = array_merge( $out, gt_search_block_text( $block['innerBlocks'] ) );
		}
	}

	return $out;
}

/**
 * Build and store one post's searchable text.
 *
 * @param int $post_id Post to index.
 */
function gt_search_index_post( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || ! in_array( $post->post_type, gt_search_post_types(), true ) ) {
		return;
	}

	$parts = gt_search_block_text( parse_blocks( $post->post_content ) );

	// Copy that lives in ACF fields rather than the body.
	if ( function_exists( 'get_field' ) ) {
		$parts[] = (string) get_field( 'standfirst', $post_id );
	}
	$parts[] = $post->post_excerpt;

	$text = wp_strip_all_tags( implode( ' ', array_filter( $parts, 'is_string' ) ) );
	$text = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) ) );

	if ( '' === $text ) {
		delete_post_meta( $post_id, GT_SEARCH_META );

		return;
	}

	update_post_meta( $post_id, GT_SEARCH_META, $text );
}

/** Re-index whenever a post is saved. */
function gt_search_reindex_on_save( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	gt_search_index_post( $post_id );
}
add_action( 'save_post', 'gt_search_reindex_on_save', 20, 2 );

/** Index every searchable post — used by the one-off backfill. */
function gt_search_reindex_all() {
	$ids = get_posts( array(
		'post_type'      => gt_search_post_types(),
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );
	foreach ( $ids as $id ) {
		gt_search_index_post( $id );
	}

	return count( $ids );
}

/** Join the searchable-text row onto a group query. */
function gt_search_posts_join( $join, $query ) {
	global $wpdb;

	if ( ! $query->get( 'gt_search' ) ) {
		return $join;
	}

	return $join . $wpdb->prepare(
		" LEFT JOIN {$wpdb->postmeta} AS gt_search ON ( gt_search.post_id = {$wpdb->posts}.ID AND gt_search.meta_key = %s ) ",
		GT_SEARCH_META
	);
}
add_filter( 'posts_join', 'gt_search_posts_join', 10, 2 );

/**
 * Search the title, the excerpt and the indexed text instead of post_content.
 *
 * Every word must appear somewhere, which is how WordPress's own search
 * behaves for multiple terms.
 */
function gt_search_posts_search( $search, $query ) {
	global $wpdb;

	$term = (string) $query->get( 's' );
	if ( ! $query->get( 'gt_search' ) || '' === trim( $term ) ) {
		return $search;
	}

	$words = preg_split( '/\s+/u', trim( $term ), -1, PREG_SPLIT_NO_EMPTY );
	$sql   = '';

	foreach ( $words as $word ) {
		$like = '%' . $wpdb->esc_like( $word ) . '%';
		$sql .= $wpdb->prepare(
			" AND ( {$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s OR gt_search.meta_value LIKE %s )",
			$like,
			$like,
			$like
		);
	}

	return $sql;
}
add_filter( 'posts_search', 'gt_search_posts_search', 10, 2 );

/**
 * Keep the main search query to the types the groups cover.
 *
 * The template runs its own query per group, so the main query is only needed
 * for is_search() and the term — hence no_found_rows and a single row.
 */
function gt_search_main_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}

	$types = gt_search_post_types();
	if ( $types ) {
		$query->set( 'post_type', $types );
	}
	// Anything else reading the main search query — a feed, a plugin — should
	// see the same results the page shows, so it searches the index too.
	$query->set( 'gt_search', true );
	$query->set( 'posts_per_page', 1 );
	$query->set( 'no_found_rows', true );
}
add_action( 'pre_get_posts', 'gt_search_main_query' );
