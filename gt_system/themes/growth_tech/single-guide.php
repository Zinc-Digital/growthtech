<?php
/**
 * Plant Academy guide — Figma 384:2407.
 *
 * Breadcrumb, a centred head (category and read time, title, standfirst, then
 * the byline under a hairline), the hero, and the body beside a sticky
 * sidebar. The body is whatever blocks the editor has placed.
 */

get_header();

while ( have_posts() ) :
	the_post();

	$post_id    = get_the_ID();
	$term       = gt_academy_primary_term( $post_id );
	$standfirst = gt_academy_standfirst( $post_id );
	$minutes    = gt_academy_read_time( $post_id );
	$byline     = gt_academy_byline( $post_id );
	$updated    = function_exists( 'get_field' ) ? (string) get_field( 'updated', $post_id ) : '';
	$hero_id    = get_post_thumbnail_id( $post_id );
	?>

	<main class="page-wrapper guide">
		<div class="guide__inner">

			<nav class="academy-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'gt' ); ?>">
				<a class="academy-crumbs__crumb" href="<?php echo esc_url( gt_academy_landing_url() ); ?>"><?php esc_html_e( 'Plant Academy', 'gt' ); ?></a>
				<?php if ( $term ) : ?>
					<span class="academy-crumbs__sep" aria-hidden="true">/</span>
					<a class="academy-crumbs__crumb" href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?></a>
				<?php endif; ?>
				<span class="academy-crumbs__sep" aria-hidden="true">/</span>
				<span class="academy-crumbs__crumb is-current" aria-current="page"><?php the_title(); ?></span>
			</nav>

			<header class="guide__head">
				<p class="guide__kicker">
					<?php if ( $term ) : ?>
						<span><?php echo esc_html( $term->name ); ?></span>
					<?php endif; ?>
					<?php if ( $minutes ) : ?>
						<span><?php
							/* translators: %d: reading time in minutes */
							printf( esc_html__( '%d min read', 'gt' ), (int) $minutes );
						?></span>
					<?php endif; ?>
				</p>

				<h1 class="guide__title"><?php the_title(); ?></h1>

				<?php if ( $standfirst ) : ?>
					<p class="guide__standfirst"><?php echo esc_html( $standfirst ); ?></p>
				<?php endif; ?>

				<p class="guide__meta">
					<?php if ( $byline ) : ?>
						<span><?php
							/* translators: %s: author or team name */
							printf( esc_html__( 'Written by %s', 'gt' ), esc_html( $byline ) );
						?></span>
					<?php endif; ?>
					<?php if ( $updated ) : ?>
						<span><?php
							/* translators: %s: month and year */
							printf( esc_html__( 'Updated %s', 'gt' ), esc_html( $updated ) );
						?></span>
					<?php endif; ?>
				</p>
			</header>

			<?php if ( $hero_id ) : ?>
				<figure class="guide__hero">
					<?php
					echo wp_get_attachment_image( $hero_id, 'gt-guide-hero', false, array(
						'class' => 'guide__hero-img',
						'alt'   => '',
						'sizes' => '(max-width: 1439px) calc(100vw - 100px), 1096px',
					) );
					?>
				</figure>
			<?php endif; ?>

			<div class="guide__layout">
				<div class="guide__body">
					<?php the_content(); ?>
				</div>

				<?php get_template_part( 'template-parts/academy/sidebar', null, array( 'post_id' => $post_id ) ); ?>
			</div>

		</div>

		<?php
		// "Explore other guides." — Figma 384:2407. The shared card band, fed
		// with guides rather than News.
		get_template_part( 'template-parts/news/carousel', null, array(
			'posts'   => gt_academy_other_guides( $post_id, 9 ),
			'heading' => gt_academy_setting( 'academy_related_heading', __( 'Explore other guides.', 'gt' ) ),
			'text'    => gt_academy_setting( 'academy_related_text', __( 'Handpicked resources to help your indoor garden thrive.', 'gt' ) ),
			'cta'     => __( 'Read the Guide', 'gt' ),
		) );
		?>

	</main>

	<?php
endwhile;

get_footer();
