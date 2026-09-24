<?php
/**
 * Admin tidy-ups.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hide the built-in Posts menu.
 *
 * The site's editorial content is the News post type, so the stock Posts
 * screens only add a second, unused place to write. This hides them rather
 * than unregistering the type, which keeps anything already stored (and
 * anything a plugin expects of it) intact.
 */
function gt_hide_posts_menu() {
	remove_menu_page( 'edit.php' );
}
add_action( 'admin_menu', 'gt_hide_posts_menu', 999 );

/** …and the matching "Post" entry under + New in the toolbar. */
function gt_hide_posts_admin_bar( $wp_admin_bar ) {
	$wp_admin_bar->remove_node( 'new-post' );
}
add_action( 'admin_bar_menu', 'gt_hide_posts_admin_bar', 999 );
