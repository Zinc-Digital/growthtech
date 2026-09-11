<?php
/**
 * Brand promo tile — Figma 384:1750 ("Frame 66": Explore the Clonex Range).
 *
 * A grid cell the same size as a product card: lifestyle image, dark
 * gradient, brand logo, tagline with accent-coloured <em>, underlined link.
 *
 * @var WP_Term $brand Passed by wc_get_template().
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $brand ) || ! $brand instanceof WP_Term || ! gt_term_field( 'promo_enabled', $brand, false ) ) {
	return;
}

$image_id = (int) gt_term_field( 'promo_image', $brand, 0 );
$logo_id  = (int) gt_term_field( 'logo', $brand, 0 );
$tagline  = (string) gt_term_field( 'promo_tagline', $brand, '' );
$accent   = gt_brand_accent( $brand );
$link     = get_term_link( $brand );

if ( is_wp_error( $link ) ) {
	return;
}
/* translators: %s: brand name */
$label = sprintf( __( 'Explore the %s Range', 'gt' ), $brand->name );
?>
<li class="brand-promo-cell">
	<a class="brand-promo" href="<?php echo esc_url( $link ); ?>" style="--brand-accent: <?php echo esc_attr( $accent ); ?>">
		<?php
		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, 'gt-promo', false, array(
				'class'   => 'brand-promo__img',
				'alt'     => '',
				'sizes'   => '(max-width: 767px) calc(100vw - 50px), (max-width: 1023px) calc(50vw - 38px), (max-width: 1265px) calc((100vw - 365px) / 2), 322px',
				'loading' => 'lazy',
			) );
		}
		?>
		<span class="brand-promo__scrim" aria-hidden="true"></span>
		<?php if ( $logo_id ) : ?>
			<?php echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'brand-promo__logo', 'alt' => $brand->name ) ); ?>
		<?php else : ?>
			<span class="brand-promo__logo-text"><?php echo esc_html( $brand->name ); ?></span>
		<?php endif; ?>
		<?php if ( $tagline ) : ?>
			<span class="brand-promo__tagline"><?php echo wp_kses( $tagline, array( 'em' => array(), 'br' => array() ) ); ?></span>
		<?php endif; ?>
		<span class="brand-promo__link"><?php echo esc_html( $label ); ?></span>
	</a>
</li>
