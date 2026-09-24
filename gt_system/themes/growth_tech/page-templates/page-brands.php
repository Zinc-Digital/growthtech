<?php
/* Template Name: Our Brands */
/**
 * Our Brands — Figma 385:5006. Centred heading + intro, a four-up grid of
 * brand tiles (image under a black fade, white logo, tagline, "Explore the
 * … Range"), then the shared "Better knowledge" guides band. Tiles come
 * from the product_brand taxonomy; the heading and copy from the page's ACF
 * fields (the heading falls back to the page title).
 */

get_header();

$page_id = get_queried_object_id();
$has_acf = function_exists( 'get_field' );
$heading = $has_acf ? trim( (string) get_field( 'heading', $page_id ) ) : '';
$heading = '' !== $heading ? $heading : get_the_title( $page_id );
$intro   = $has_acf ? (string) get_field( 'intro', $page_id ) : '';
$tiles   = gt_brands_page_tiles( $has_acf && get_field( 'own_only', $page_id ) );

$accent = $has_acf ? (string) get_field( 'guides_accent_colour', $page_id ) : '';
$accent = preg_match( '/^#[0-9a-fA-F]{6}$/', $accent ) ? strtoupper( $accent ) : '#FFC400';
$guides = $has_acf ? array(
	'heading' => (string) get_field( 'guides_heading', $page_id ),
	'accent'  => (string) get_field( 'guides_accent_line', $page_id ),
	'text'    => (string) get_field( 'guides_text', $page_id ),
	'cta1'    => get_field( 'guides_cta_1', $page_id ),
	'cta2'    => get_field( 'guides_cta_2', $page_id ),
	'guides'  => get_field( 'guides', $page_id ),
	'label'   => __( 'Guides', 'gt' ),
) : null;
?>

<main class="page-wrapper brands-page" style="--brand-accent: <?php echo esc_attr( $accent ); ?>">
	<div class="brands-page__inner">

		<header class="brands-page__head">
			<h1 class="brands-page__title"><?php echo esc_html( $heading ); ?></h1>
			<?php if ( $intro ) : ?>
				<p class="brands-page__intro"><?php echo wp_kses_post( $intro ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( $tiles ) : ?>
			<ul class="brands-grid">
				<?php foreach ( $tiles as $i => $tile ) : ?>
					<?php $brand = $tile['brand']; ?>
					<li class="brands-grid__item">
						<a class="brands-grid__tile" href="<?php echo esc_url( $tile['url'] ); ?>" aria-label="<?php echo esc_attr( $tile['link_text'] ); ?>">
							<?php
							if ( $tile['image'] ) {
								echo wp_get_attachment_image( $tile['image'], 'gt-brand-tile', false, array(
									'class'   => 'brands-grid__img',
									'alt'     => '',
									'sizes'   => '(max-width: 599px) calc(100vw - 50px), (max-width: 1023px) calc(50vw - 40px), (max-width: 1439px) calc(25vw - 44px), 292px',
									'loading' => $i < 4 ? 'eager' : 'lazy',
								) );
							}
							?>
							<span class="brands-grid__scrim" aria-hidden="true"></span>
							<span class="brands-grid__content">
								<?php if ( $tile['logo'] ) : ?>
									<?php
									if ( 'image/svg+xml' === get_post_mime_type( $tile['logo'] ) ) {
										// WordPress can't read SVG dimensions, so print the tag ourselves.
										echo '<img class="brands-grid__logo" src="' . esc_url( wp_get_attachment_url( $tile['logo'] ) ) . '" alt="' . esc_attr( $brand->name ) . '" loading="lazy">';
									} else {
										echo wp_get_attachment_image( $tile['logo'], 'medium', false, array( 'class' => 'brands-grid__logo', 'alt' => $brand->name, 'loading' => 'lazy' ) );
									}
									?>
								<?php else : ?>
									<span class="brands-grid__name"><?php echo esc_html( $brand->name ); ?></span>
								<?php endif; ?>
								<?php if ( $tile['tagline'] ) : ?>
									<span class="brands-grid__tagline"><?php echo esc_html( $tile['tagline'] ); ?></span>
								<?php endif; ?>
								<span class="brands-grid__link"><?php echo esc_html( $tile['link_text'] ); ?></span>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="brands-page__empty"><?php esc_html_e( 'No brands have been added yet.', 'gt' ); ?></p>
		<?php endif; ?>
	</div>

	<?php
	if ( $guides ) {
		get_template_part( 'template-parts/brand/guides', null, array( 'fields' => $guides ) );
	}
	?>

<?php
get_footer();
