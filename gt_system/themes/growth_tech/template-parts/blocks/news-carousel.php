<?php
/**
 * News Carousel block — Figma 387:5948 (Frame 28). The grey "Catch up on
 * other news stories" band, pulling the latest News posts. The same part the
 * article template appends, so it can be dropped on any page too.
 */

$heading = (string) get_field( 'heading' );
$text    = (string) get_field( 'text' );
$limit   = (int) get_field( 'limit' );

get_template_part( 'template-parts/news/carousel', null, array(
	'exclude' => is_singular( 'news' ) ? get_the_ID() : 0,
	'heading' => $heading,
	'text'    => $text,
	'limit'   => $limit > 0 ? $limit : 9,
) );
