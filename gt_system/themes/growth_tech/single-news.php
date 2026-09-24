<?php
/**
 * News article — Figma 387:5948. Breadcrumb, the article head (kicker, title,
 * standfirst, byline rule), the hero image, then the body, which is whatever
 * ACF blocks the editor has placed. The carousel of other stories closes the
 * page unless the story hides it.
 */

get_header();

while ( have_posts() ) :
	the_post();

	$post_id    = get_the_ID();
	$standfirst = gt_news_standfirst( $post_id );
	$read       = gt_news_read_time( $post_id );
	$byline     = gt_news_byline( $post_id );
	$hero_id    = get_post_thumbnail_id( $post_id );
	$hide_rel   = function_exists( 'get_field' ) && get_field( 'hide_related', $post_id );
	?>

	<main class="page-wrapper news-article">
		<nav class="news-article__crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'gt' ); ?>">
			<a class="news-article__crumb" href="<?php echo esc_url( get_post_type_archive_link( 'news' ) ); ?>"><?php esc_html_e( 'News', 'gt' ); ?></a>
			<span class="news-article__crumb-sep" aria-hidden="true">/</span>
			<span class="news-article__crumb is-current" aria-current="page">
				<?php
				echo esc_html( get_the_title() );
				echo $standfirst ? esc_html( ': ' . $standfirst ) : '';
				?>
			</span>
		</nav>

		<header class="news-article__head">
			<p class="news-article__kicker">
				<span><?php esc_html_e( 'News', 'gt' ); ?></span>
				<?php if ( $read ) : ?>
					<span><?php
						/* translators: %d: reading time in minutes */
						printf( esc_html__( '%d min read', 'gt' ), (int) $read );
					?></span>
				<?php endif; ?>
			</p>

			<h1 class="news-article__title"><?php the_title(); ?></h1>

			<?php if ( $standfirst ) : ?>
				<p class="news-article__standfirst"><?php echo esc_html( $standfirst ); ?></p>
			<?php endif; ?>

			<p class="news-article__meta">
				<?php if ( $byline ) : ?>
					<span><?php
						/* translators: %s: author or team name */
						printf( esc_html__( 'Written by %s', 'gt' ), esc_html( $byline ) );
					?></span>
				<?php endif; ?>
				<span><?php
					/* translators: %s: publication date */
					printf( esc_html__( 'Posted %s', 'gt' ), esc_html( get_the_date( 'F Y' ) ) );
				?></span>
			</p>
		</header>

		<?php if ( $hero_id ) : ?>
			<figure class="news-article__hero">
				<?php
				echo wp_get_attachment_image( $hero_id, 'gt-news-hero', false, array(
					'class' => 'news-article__hero-img',
					'alt'   => '',
					'sizes' => '(max-width: 1439px) calc(100vw - 100px), 1240px',
				) );
				?>
			</figure>
		<?php endif; ?>

		<div class="news-article__body">
			<?php the_content(); ?>
		</div>

		<?php
		if ( ! $hide_rel ) {
			get_template_part( 'template-parts/news/carousel', null, array( 'exclude' => $post_id ) );
		}
		?>
	</main>

	<?php
endwhile;

get_footer();
