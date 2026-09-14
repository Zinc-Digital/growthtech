<?php
/**
 * Cross-sell band — Figma 384:1514. "Pair with Root Riot…": paired brand's
 * logo, heading, text, image and a button.
 *
 * @param array $args ['brand' => WP_Term]
 */

$brand = isset( $args['brand'] ) ? $args['brand'] : null;
if ( ! $brand instanceof WP_Term ) {
	return;
}
$pair_id  = (int) gt_term_field( 'pair_brand', $brand, 0 );
$pair     = $pair_id ? get_term( $pair_id, 'product_brand' ) : null;
$pair     = $pair instanceof WP_Term ? $pair : null;
$heading  = (string) gt_term_field( 'pair_heading', $brand, '' );
$text     = (string) gt_term_field( 'pair_text', $brand, '' );
$image_id = (int) gt_term_field( 'pair_image', $brand, 0 );
$link     = gt_term_field( 'pair_link', $brand, null );

if ( ! $pair && ! $heading ) {
	return;
}
if ( ! is_array( $link ) || empty( $link['url'] ) ) {
	$pair_link = $pair ? get_term_link( $pair ) : '';
	$link      = ( $pair_link && ! is_wp_error( $pair_link ) ) ? array( 'url' => $pair_link, 'title' => sprintf( /* translators: %s: brand name */ __( 'Explore %s', 'gt' ), $pair->name ), 'target' => '' ) : null;
}
$logo_id = $pair ? (int) gt_term_field( 'logo', $pair, 0 ) : 0;
?>
<section class="brand-pair" aria-label="<?php echo esc_attr( $heading ? $heading : ( $pair ? $pair->name : '' ) ); ?>">
	<div class="brand-pair__inner">
		<?php
		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, 'gt-brand-pair', false, array(
				'class'   => 'brand-pair__img',
				'alt'     => '',
				'sizes'   => '(max-width: 1439px) calc(100vw - 100px), 1340px',
				'loading' => 'lazy',
			) );
		}
		?>
		<span class="brand-pair__scrim" aria-hidden="true"></span>
		<div class="brand-pair__content">
			<?php if ( $logo_id ) : ?>
				<?php echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'brand-pair__logo', 'alt' => $pair->name ) ); ?>
			<?php elseif ( $pair ) : ?>
				<span class="brand-pair__logo brand-pair__logo--text"><?php echo esc_html( $pair->name ); ?></span>
			<?php endif; ?>
			<?php if ( $heading ) : ?>
				<h2 class="brand-pair__title"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php if ( $text ) : ?>
				<p class="brand-pair__text"><?php echo wp_kses_post( $text ); ?></p>
			<?php endif; ?>
			<?php if ( $link ) : ?>
				<a class="btn-flat" href="<?php echo esc_url( $link['url'] ); ?>"
					<?php echo ! empty( $link['target'] ) ? 'target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; ?>>
					<span><?php echo esc_html( ! empty( $link['title'] ) ? $link['title'] : __( 'View Product', 'gt' ) ); ?></span>
					<?php gt_arrow_svg(); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</section>
