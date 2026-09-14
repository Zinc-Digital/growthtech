<?php
/**
 * Stockist card — Figma 384:2739.
 *
 * @param array $args ['stockist' => array (from gt_stockists_all), 'visible' => bool]
 */

$s       = isset( $args['stockist'] ) ? $args['stockist'] : null;
$visible = ! isset( $args['visible'] ) || $args['visible'];
if ( ! is_array( $s ) ) {
	return;
}
$chips     = $s['products'];
$extra     = max( 0, count( $chips ) - 3 );
$tel       = preg_replace( '/[^\d+]/', '', $s['phone'] );
$check_url = $s['website'] ? $s['website'] : ( $tel ? 'tel:' . $tel : '' );
$is_site   = (bool) $s['website'];
?>
<li class="stockists-card"<?php echo $visible ? '' : ' hidden'; ?>
	data-id="<?php echo esc_attr( $s['id'] ); ?>"
	data-name="<?php echo esc_attr( $s['name'] ); ?>"
	data-country="<?php echo esc_attr( $s['country'] ); ?>"
	data-lat="<?php echo esc_attr( null === $s['lat'] ? '' : $s['lat'] ); ?>"
	data-lng="<?php echo esc_attr( null === $s['lng'] ? '' : $s['lng'] ); ?>"
	data-products="<?php echo esc_attr( implode( ',', wp_list_pluck( $s['products'], 'id' ) ) ); ?>"
	data-brands="<?php echo esc_attr( implode( ',', $s['brands'] ) ); ?>"
	data-search="<?php echo esc_attr( $s['search'] ); ?>">
	<div class="stockists-card__head">
		<div class="stockists-card__id">
			<h2 class="stockists-card__name"><?php echo esc_html( $s['name'] ); ?></h2>
			<p class="stockists-card__meta">
				<span><?php echo esc_html( trim( $s['town'] . ( $s['town'] && $s['country_name'] ? ', ' : '' ) . $s['country_name'] ) ); ?></span>
				<?php if ( $s['type'] ) : ?>
					<span class="stockists-card__dot" aria-hidden="true"></span>
					<span><?php echo esc_html( $s['type'] ); ?></span>
				<?php endif; ?>
			</p>
		</div>
		<?php if ( null !== $s['lat'] ) : ?>
			<button type="button" class="stockists-card__map" data-card-map><?php esc_html_e( 'Show on map', 'gt' ); ?></button>
		<?php endif; ?>
	</div>

	<?php if ( $chips ) : ?>
		<ul class="stockists-card__chips">
			<?php foreach ( $chips as $i => $chip ) : ?>
				<li class="stockists-card__chip<?php echo $i >= 3 ? ' is-extra' : ''; ?>"><?php echo esc_html( $chip['name'] ); ?></li>
			<?php endforeach; ?>
			<?php if ( $extra ) : ?>
				<li class="stockists-card__chip stockists-card__chip--more">
					<button type="button" class="stockists-card__more" data-card-more data-count="<?php echo esc_attr( $extra ); ?>" aria-expanded="false"><?php echo esc_html( sprintf( /* translators: %d: hidden chips */ __( '+%d more', 'gt' ), $extra ) ); ?></button>
				</li>
			<?php endif; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $s['phone'] || $s['directions'] || $check_url ) : ?>
		<div class="stockists-card__actions">
			<?php if ( $s['phone'] ) : ?>
				<a class="stockists-card__phone" href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo esc_html( $s['phone'] ); ?></a>
			<?php endif; ?>
			<a class="stockists-card__link" href="<?php echo esc_url( $s['directions'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get Directions', 'gt' ); ?></a>
			<?php if ( $check_url ) : ?>
				<a class="stockists-card__link" href="<?php echo esc_url( $check_url ); ?>"<?php echo $is_site ? ' target="_blank" rel="noopener"' : ''; ?>><?php esc_html_e( 'Check stock first', 'gt' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</li>
