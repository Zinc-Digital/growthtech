<?php
/**
 * News card — Figma 384:4631 (Frame 14). Featured image with a 90% white
 * panel inset at the bottom: "<strong>Title:</strong> standfirst" and a
 * "Read story" link. Shared by the archive grid and the news carousel.
 *
 * @param array $args ['post' => WP_Post|int, 'eager' => bool, 'cta' => string]
 */

$post_obj = isset( $args['post'] ) ? get_post( $args['post'] ) : get_post();
if ( ! $post_obj ) {
	return;
}
$post_id    = $post_obj->ID;
$standfirst = gt_news_standfirst( $post_id );
$image_id   = get_post_thumbnail_id( $post_id );
$eager      = ! empty( $args['eager'] );
// Guides read "Read the Guide"; a story reads "Read story".
$cta        = isset( $args['cta'] ) && '' !== trim( (string) $args['cta'] )
	? (string) $args['cta']
	: __( 'Read story', 'gt' );
?>
<article class="news-card">
	<a class="news-card__link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
		<?php
		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, 'gt-news-card', false, array(
				'class'   => 'news-card__img',
				'alt'     => '',
				'sizes'   => '(max-width: 767px) calc(100vw - 50px), (max-width: 1023px) calc(50vw - 75px), (max-width: 1439px) calc(33.33vw - 67px), 380px',
				'loading' => $eager ? 'eager' : 'lazy',
			) );
		}
		?>
		<span class="news-card__panel">
			<span class="news-card__title">
				<strong><?php echo esc_html( get_the_title( $post_id ) ); ?>:</strong>
				<?php echo esc_html( $standfirst ); ?>
			</span>
			<span class="news-card__cta"><?php echo esc_html( $cta ); ?></span>
		</span>
	</a>
</article>
