<?php
/**
 * One guide row — Figma 384:2259 (Plant Academy category) and 384:3045
 * (search). A 284 x 190 thumbnail, a kicker over a serif title and a
 * standfirst, and the call to action pinned right. Rows are divided by a
 * hairline. Shared by both listings, which draw the identical row.
 *
 * 'kicker' overrides the reading time — the category archive shows the read
 * time, while a mixed listing may want the category name instead.
 *
 * @param array $args ['post' => WP_Post|int, 'cta' => string, 'read_time' => bool, 'kicker' => string]
 */

$post_obj = isset( $args['post'] ) ? get_post( $args['post'] ) : get_post();
if ( ! $post_obj ) {
	return;
}

$post_id    = $post_obj->ID;
$cta        = isset( $args['cta'] ) && '' !== trim( (string) $args['cta'] ) ? (string) $args['cta'] : __( 'Read more', 'gt' );
$standfirst = gt_news_standfirst( $post_id );
// Only the types that read like articles carry a reading time.
$minutes    = ! empty( $args['read_time'] ) ? gt_news_read_time( $post_id ) : 0;
$kicker     = isset( $args['kicker'] ) ? trim( (string) $args['kicker'] ) : '';
if ( '' === $kicker && $minutes ) {
	/* translators: %d: reading time in minutes */
	$kicker = sprintf( __( '%d min read', 'gt' ), (int) $minutes );
}
$image_id   = get_post_thumbnail_id( $post_id );
$permalink  = get_permalink( $post_id );
?>
<li class="guide-row">
	<a class="guide-row__link" href="<?php echo esc_url( $permalink ); ?>">
		<?php if ( $image_id ) : ?>
			<span class="guide-row__media">
				<?php
				echo wp_get_attachment_image( $image_id, 'gt-search-thumb', false, array(
					'class'   => 'guide-row__img',
					'alt'     => '',
					'sizes'   => '(max-width: 767px) calc(100vw - 50px), 284px',
					'loading' => 'lazy',
				) );
				?>
			</span>
		<?php endif; ?>

		<span class="guide-row__body">
			<?php if ( '' !== $kicker ) : ?>
				<span class="guide-row__kicker"><?php echo esc_html( $kicker ); ?></span>
			<?php endif; ?>

			<span class="guide-row__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></span>

			<?php if ( $standfirst ) : ?>
				<span class="guide-row__text"><?php echo esc_html( $standfirst ); ?></span>
			<?php endif; ?>
		</span>

		<span class="guide-row__cta">
			<span class="guide-row__cta-label"><?php echo esc_html( $cta ); ?></span>
			<?php gt_icon_svg( 'arrow-sm' ); ?>
		</span>
	</a>
</li>
