<?php
/**
 * "Catch up on other news stories" — Figma 387:5948 (Frame 28). A grey band
 * with the heading, a line of copy and a slider of story cards. Used at the
 * end of every article and available as the News Carousel block.
 *
 * @param array $args ['exclude' => int, 'heading' => string, 'text' => string, 'limit' => int]
 */

$exclude = isset( $args['exclude'] ) ? (int) $args['exclude'] : 0;
$limit   = isset( $args['limit'] ) ? (int) $args['limit'] : 9;
$stories = gt_news_related( $exclude, $limit );
if ( ! $stories ) {
	return;
}

$has_acf = function_exists( 'get_field' );
$heading = isset( $args['heading'] ) ? (string) $args['heading'] : '';
if ( '' === trim( $heading ) ) {
	$heading = $has_acf ? (string) get_field( 'news_related_heading', 'option' ) : '';
}
if ( '' === trim( $heading ) ) {
	$heading = __( 'Catch up on other news stories.', 'gt' );
}
$text = isset( $args['text'] ) ? (string) $args['text'] : '';
if ( '' === trim( $text ) ) {
	$text = $has_acf ? (string) get_field( 'news_related_text', 'option' ) : '';
}

$id = wp_unique_id( 'news-carousel-' );
wp_enqueue_script( 'gt-block-slider' );
?>
<section class="news-carousel" aria-labelledby="<?php echo esc_attr( $id ); ?>-title" data-slider-scope>
	<div class="news-carousel__inner">
		<div class="news-carousel__head">
			<div class="news-carousel__copy">
				<h2 class="news-carousel__title" id="<?php echo esc_attr( $id ); ?>-title"><?php echo esc_html( $heading ); ?></h2>
				<?php if ( trim( $text ) ) : ?>
					<p class="news-carousel__text"><?php echo nl2br( esc_html( $text ) ); ?></p>
				<?php endif; ?>
			</div>
			<div class="news-carousel__arrows shop-slider__arrows" data-slider-arrows></div>
		</div>

		<ul class="news-carousel__slides" data-block-slider data-slides="3" data-slides-md="2" data-slides-sm="1" data-arrows="1" data-dots="0">
			<?php foreach ( $stories as $story ) : ?>
				<li class="news-carousel__slide">
					<?php get_template_part( 'template-parts/news/card', null, array( 'post' => $story ) ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
