<?php
/**
 * News listing — Figma 384:4631. Centred heading and intro, a sort control,
 * a three-up grid of story cards, pagination, then the shared "Better
 * knowledge" band. Heading, intro and the band come from Theme Settings.
 */

get_header();

$has_acf = function_exists( 'get_field' );
$heading = $has_acf ? trim( (string) get_field( 'news_heading', 'option' ) ) : '';
$heading = '' !== $heading ? $heading : post_type_archive_title( '', false );
$intro   = $has_acf ? (string) get_field( 'news_intro', 'option' ) : '';
$sort    = gt_news_sort();
$total   = (int) $GLOBALS['wp_query']->max_num_pages;
$paged   = max( 1, (int) get_query_var( 'paged' ) );
?>

<main class="page-wrapper news-archive">
	<div class="news-archive__inner">

		<header class="news-archive__head">
			<h1 class="news-archive__title"><?php echo esc_html( $heading ); ?></h1>
			<?php if ( $intro ) : ?>
				<p class="news-archive__intro"><?php echo nl2br( esc_html( $intro ) ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( have_posts() ) : ?>
			<form class="news-sort" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'news' ) ); ?>">
				<label class="news-sort__label" for="news-sort"><?php esc_html_e( 'Sort by', 'gt' ); ?></label>
				<span class="news-sort__control">
					<select class="news-sort__select" name="sort" id="news-sort" onchange="this.form.submit()">
						<option value="recent"<?php selected( $sort, 'recent' ); ?>><?php esc_html_e( 'Most recent first', 'gt' ); ?></option>
						<option value="oldest"<?php selected( $sort, 'oldest' ); ?>><?php esc_html_e( 'Oldest first', 'gt' ); ?></option>
					</select>
				</span>
				<noscript><button type="submit" class="news-sort__go"><?php esc_html_e( 'Sort', 'gt' ); ?></button></noscript>
			</form>

			<div class="news-grid">
				<?php
				$i = 0;
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/news/card', null, array( 'post' => get_post(), 'eager' => $i < 3 ) );
					$i++;
				endwhile;
				?>
			</div>

			<?php get_template_part( 'template-parts/news/pagination', null, array( 'paged' => $paged, 'total' => $total ) ); ?>
		<?php else : ?>
			<p class="news-archive__empty"><?php esc_html_e( 'No news stories have been published yet.', 'gt' ); ?></p>
		<?php endif; ?>
	</div>

	<div class="news-archive__band">
		<?php get_template_part( 'template-parts/shop/knowledge-band' ); ?>
	</div>

<?php
get_footer();
