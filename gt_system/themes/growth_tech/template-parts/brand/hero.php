<?php
/**
 * Brand hero — Figma 384:1492 / 384:1497. Full-bleed black, image right,
 * logo + two-line headline + intro + CTAs left. Falls back to the brand
 * name and description when no landing copy is set.
 *
 * @param array $args ['brand' => WP_Term]
 */

$brand = isset( $args['brand'] ) ? $args['brand'] : null;
if ( ! $brand instanceof WP_Term ) {
	return;
}
$logo_id  = (int) gt_term_field( 'logo', $brand, 0 );
$heading  = (string) gt_term_field( 'hero_heading', $brand, '' );
$accent   = (string) gt_term_field( 'hero_accent_line', $brand, '' );
$intro    = (string) gt_term_field( 'hero_intro', $brand, $brand->description );
$image_id = (int) gt_term_field( 'hero_image', $brand, 0 );
$stockist = gt_brand_stockist_url( $brand );
$has_copy = '' !== $heading;
/* translators: %s: brand name */
$explore = sprintf( __( 'Explore the %s Range', 'gt' ), $brand->name );
?>
<section class="brand-hero" aria-label="<?php echo esc_attr( $brand->name ); ?>">
	<?php
	if ( $image_id ) {
		echo wp_get_attachment_image( $image_id, 'gt-brand-hero', false, array(
			'class' => 'brand-hero__img',
			'alt'   => '',
			'sizes' => '100vw',
		) );
	}
	?>
	<span class="brand-hero__scrim" aria-hidden="true"></span>
	<div class="brand-hero__inner">
		<div class="brand-hero__content">
			<?php if ( $logo_id ) : ?>
				<?php echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'brand-hero__logo', 'alt' => $brand->name ) ); ?>
			<?php endif; ?>

			<h1 class="brand-hero__title"><?php
				if ( $has_copy ) {
					echo esc_html( $heading );
					if ( $accent ) {
						echo ' <em class="brand-hero__accent">' . esc_html( $accent ) . '</em>';
					}
				} else {
					echo esc_html( $brand->name );
				}
			?></h1>

			<?php if ( $intro ) : ?>
				<p class="brand-hero__intro"><?php echo wp_kses_post( $intro ); ?></p>
			<?php endif; ?>

			<div class="brand-hero__actions">
				<?php if ( $stockist ) : ?>
					<a class="btn-flat brand-hero__stockist" href="<?php echo esc_url( $stockist ); ?>">
						<span><?php esc_html_e( 'Find a stockist', 'gt' ); ?></span>
						<?php gt_arrow_svg(); ?>
					</a>
				<?php endif; ?>
				<a class="brand-hero__explore" href="#range"><?php echo esc_html( $explore ); ?></a>
			</div>
		</div>
	</div>
</section>
