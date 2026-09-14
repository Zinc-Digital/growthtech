<?php
/**
 * Map column — Figma 384:2843. Google Maps renders into the canvas when a
 * key is set; otherwise a grey placeholder (with a hint for admins).
 *
 * @param array $args ['has_key' => bool]
 */

$has_key = ! empty( $args['has_key'] );
?>
<div class="stockists-map" data-stockists-map>
	<div class="stockists-map__canvas" data-stockists-canvas<?php echo $has_key ? ' role="region" aria-label="' . esc_attr__( 'Map of stockists', 'gt' ) . '"' : ''; ?>></div>
	<?php if ( ! $has_key ) : ?>
		<div class="stockists-map__placeholder">
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<p class="stockists-map__hint"><?php esc_html_e( 'Add a Google Maps API key under Theme Settings → Shop Settings to show the map.', 'gt' ); ?></p>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="stockists-map__zoom">
			<button type="button" class="stockists-map__zoom-btn" data-map-zoom="in" aria-label="<?php esc_attr_e( 'Zoom in', 'gt' ); ?>">+</button>
			<button type="button" class="stockists-map__zoom-btn" data-map-zoom="out" aria-label="<?php esc_attr_e( 'Zoom out', 'gt' ); ?>">&minus;</button>
		</div>
	<?php endif; ?>
</div>
