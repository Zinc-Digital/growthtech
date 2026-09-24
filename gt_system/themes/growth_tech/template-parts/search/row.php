<?php
/**
 * One result row — Figma 384:3045 (Frame 148). A 284 x 190 thumbnail, the
 * read time over a serif title and a standfirst, and the call to action
 * pinned to the right. Rows are divided by a hairline.
 *
 * @param array $args ['post' => WP_Post|int, 'cta' => string]
 */

$post_obj = isset( $args['post'] ) ? get_post( $args['post'] ) : get_post();
if ( ! $post_obj ) {
	return;
}

$post_id    = $post_obj->ID;
$cta        = isset( $args['cta'] ) && '' !== trim( (string) $args['cta'] ) ? (string) $args['cta'] : __( 'Read more', 'gt' );
$standfirst = gt_news_standfirst( $post_id );
$minutes    = gt_news_read_time( $post_id );
$image_id   = get_post_thumbnail_id( $post_id );
$permalink  = get_permalink( $post_id );
?>
<li class="search-row">
	<a class="search-row__link" href="<?php echo esc_url( $permalink ); ?>">
		<span class="search-row__media">
			<?php
			if ( $image_id ) {
				echo wp_get_attachment_image( $image_id, 'gt-search-thumb', false, array(
					'class'   => 'search-row__img',
					'alt'     => '',
					'sizes'   => '(max-width: 767px) calc(100vw - 50px), 284px',
					'loading' => 'lazy',
				) );
			}
			?>
		</span>

		<span class="search-row__body">
			<?php if ( $minutes ) : ?>
				<span class="search-row__kicker"><?php
					/* translators: %d: reading time in minutes */
					printf( esc_html__( '%d min read', 'gt' ), (int) $minutes );
				?></span>
			<?php endif; ?>

			<span class="search-row__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></span>

			<?php if ( $standfirst ) : ?>
				<span class="search-row__text"><?php echo esc_html( $standfirst ); ?></span>
			<?php endif; ?>
		</span>

		<span class="search-row__cta">
			<span class="search-row__cta-label"><?php echo esc_html( $cta ); ?></span>
			<?php gt_icon_svg( 'arrow-sm' ); ?>
		</span>
	</a>
</li>
