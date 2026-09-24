<?php
/* Template Name: About Us */
/**
 * About Us — Figma 384:2551. A block canvas: the page is built entirely from
 * the theme's ACF blocks, so sections can be added, removed and reordered in
 * the editor. The wrapper only strips the default page padding so the
 * full-bleed blocks can reach the edges.
 */

get_header();
?>

<main class="page-wrapper about-page">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>

<?php
get_footer();
