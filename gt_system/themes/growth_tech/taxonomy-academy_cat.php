<?php
/**
 * Plant Academy category — Figma 384:2243.
 *
 * Breadcrumb, the category's banner, a sort control, then the guides as
 * thumbnail rows. The category list and the products band below it are
 * blocks, taken from the landing Page so they stay editable in one place.
 */

get_header();

$term  = get_queried_object();
$hero  = $term instanceof WP_Term ? (int) gt_term_field( 'hero', $term, 0 ) : 0;
$sort  = gt_academy_sort();
$paged = max( 1, (int) get_query_var( 'paged' ) );
$pages = (int) $GLOBALS['wp_query']->max_num_pages;
$total = (int) $GLOBALS['wp_query']->found_posts;
?>

<main class="page-wrapper academy-cat">
	<div class="academy-cat__inner">

		<nav class="academy-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'gt' ); ?>">
			<a class="academy-crumbs__crumb" href="<?php echo esc_url( gt_academy_landing_url() ); ?>"><?php esc_html_e( 'Plant Academy', 'gt' ); ?></a>
			<span class="academy-crumbs__sep" aria-hidden="true">/</span>
			<span class="academy-crumbs__crumb is-current" aria-current="page"><?php echo esc_html( $term->name ); ?></span>
		</nav>

		<?php if ( $hero ) : ?>
			<div class="academy-cat__hero">
				<?php
				echo wp_get_attachment_image( $hero, 'gt-academy-hero', false, array(
					'class' => 'academy-cat__hero-img',
					'alt'   => '',
					'sizes' => '(max-width: 1439px) calc(100vw - 100px), 1340px',
				) );
				?>
			</div>
		<?php endif; ?>

		<h1 class="screen-reader-text"><?php echo esc_html( $term->name ); ?></h1>

		<?php if ( have_posts() ) : ?>
			<form class="academy-sort news-sort" method="get" action="<?php echo esc_url( get_term_link( $term ) ); ?>">
				<label class="news-sort__label" for="academy-sort"><?php esc_html_e( 'Sort by', 'gt' ); ?></label>
				<span class="news-sort__control">
					<select class="news-sort__select" name="sort" id="academy-sort" onchange="this.form.submit()">
						<?php foreach ( $sort['all'] as $key => $option ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $sort['key'], $key ); ?>><?php echo esc_html( $option['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
				<noscript><button type="submit" class="news-sort__go"><?php esc_html_e( 'Sort', 'gt' ); ?></button></noscript>
			</form>

			<ul class="guide-list">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/guide-row', null, array(
						'post'      => get_post(),
						'cta'       => __( 'Read guide', 'gt' ),
						'read_time' => true,
					) );
				endwhile;
				?>
			</ul>

			<?php
			get_template_part( 'template-parts/pagination', null, array(
				'paged' => $paged,
				'pages' => $pages,
				'label' => __( 'Guide pages', 'gt' ),
				'link'  => function ( $page ) use ( $term, $sort ) {
					$url = 1 === (int) $page ? get_term_link( $term ) : trailingslashit( get_term_link( $term ) ) . 'page/' . (int) $page . '/';

					return 'recent' === $sort['key'] ? $url : add_query_arg( 'sort', $sort['key'], $url );
				},
			) );
			?>
		<?php else : ?>
			<p class="academy-cat__empty"><?php esc_html_e( 'No guides in this category yet.', 'gt' ); ?></p>
		<?php endif; ?>

	</div>

	<?php
	// The category band and the products band are blocks on the landing Page,
	// so they are written once and appear on every category too.
	gt_academy_render_landing_tail();
	?>

<?php
get_footer();
