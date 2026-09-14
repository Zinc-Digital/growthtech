<?php
/**
 * Product summary — Figma 384:1840. Brand mark, title, intro, tick list,
 * sizes, the Phase Two price/cart slot, enquiry CTAs; then downloads and
 * badges under a rule.
 *
 * @param array $args ['product' => WC_Product, 'brand' => WP_Term|null]
 */

$product = isset( $args['product'] ) ? $args['product'] : null;
$brand   = isset( $args['brand'] ) ? $args['brand'] : null;
if ( ! $product instanceof WC_Product ) {
	return;
}

$features  = function_exists( 'get_field' ) ? get_field( 'features', $product->get_id() ) : array();
$downloads = function_exists( 'get_field' ) ? get_field( 'downloads', $product->get_id() ) : array();
$sizes     = gt_product_sizes( $product );
$badges    = gt_product_badges( $product );
$stockist  = gt_product_stockist_url( $product );
$experts   = gt_product_experts_url( $product );
$logo_id   = $brand instanceof WP_Term ? (int) gt_term_field( 'logo', $brand, 0 ) : 0;
$intro     = $product->get_short_description();
?>
<div class="product-summary">
	<div class="product-summary__main">

		<div class="product-summary__head">
			<?php if ( $brand instanceof WP_Term ) : ?>
				<a class="product-summary__brand" href="<?php echo esc_url( get_term_link( $brand ) ); ?>">
					<?php if ( $logo_id ) : ?>
						<?php echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'product-summary__brand-img', 'alt' => $brand->name ) ); ?>
					<?php else : ?>
						<span class="product-summary__brand-text"><?php echo esc_html( $brand->name ); ?></span>
					<?php endif; ?>
				</a>
			<?php endif; ?>
			<h1 class="product-summary__title"><?php echo esc_html( $product->get_name() ); ?></h1>
		</div>

		<?php if ( $intro ) : ?>
			<div class="product-summary__intro"><?php echo wp_kses_post( wpautop( $intro ) ); ?></div>
		<?php endif; ?>

		<?php if ( is_array( $features ) && $features ) : ?>
			<ul class="product-summary__features">
				<?php foreach ( $features as $feature ) : ?>
					<?php
					$lead = isset( $feature['lead'] ) ? trim( (string) $feature['lead'] ) : '';
					$text = isset( $feature['text'] ) ? trim( (string) $feature['text'] ) : '';
					if ( ! $lead && ! $text ) {
						continue;
					}
					?>
					<li class="product-summary__feature">
						<span class="product-summary__tick" aria-hidden="true"><?php gt_icon_svg( 'tick-check' ); ?></span>
						<p class="product-summary__feature-text">
							<?php if ( $lead ) : ?><strong><?php echo esc_html( $lead ); ?></strong><?php endif; ?>
							<?php if ( $lead && $text ) : ?> — <?php endif; ?>
							<?php echo esc_html( $text ); ?>
						</p>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $sizes ) : ?>
			<div class="product-sizes">
				<p class="product-summary__eyebrow"><?php esc_html_e( 'Available sizes', 'gt' ); ?></p>
				<ul class="product-sizes__list" aria-label="<?php esc_attr_e( 'Available sizes', 'gt' ); ?>">
					<?php foreach ( $sizes as $index => $size ) : ?>
						<li class="product-sizes__chip<?php echo 0 === $index ? ' is-selected' : ''; ?>"><?php echo esc_html( $size ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php
		/*
		 * Phase Two: WooCommerce's price and add-to-cart (with the size
		 * variation form) render here once GT_SHOP_ENQUIRY_MODE is off.
		 */
		do_action( 'woocommerce_single_product_summary' );
		?>

		<?php if ( $stockist || $experts ) : ?>
			<div class="product-summary__actions">
				<?php if ( $stockist ) : ?>
					<a class="btn-flat btn-flat--dark product-summary__stockist" href="<?php echo esc_url( $stockist ); ?>">
						<span><?php esc_html_e( 'Find a local stockist', 'gt' ); ?></span>
						<?php gt_arrow_svg(); ?>
					</a>
				<?php endif; ?>
				<?php if ( $experts ) : ?>
					<a class="product-summary__experts" href="<?php echo esc_url( $experts ); ?>"><?php echo esc_html( gt_product_experts_label() ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( ( is_array( $downloads ) && $downloads ) || $badges ) : ?>
		<div class="product-summary__extra">
			<?php if ( is_array( $downloads ) && $downloads ) : ?>
				<ul class="product-downloads">
					<?php foreach ( $downloads as $download ) : ?>
						<?php
						$file_id = ! empty( $download['file'] ) ? (int) $download['file'] : 0;
						$label   = ! empty( $download['label'] ) ? $download['label'] : get_the_title( $file_id );
						$url     = $file_id ? wp_get_attachment_url( $file_id ) : '';
						if ( ! $url ) {
							continue;
						}
						?>
						<li class="product-downloads__item">
							<a class="product-downloads__link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
								<span class="product-downloads__icon" aria-hidden="true"><?php gt_icon_svg( 'download' ); ?></span>
								<span><?php echo esc_html( $label ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $badges ) : ?>
				<ul class="product-badges">
					<?php foreach ( $badges as $badge ) : ?>
						<li class="product-badges__item"><?php echo esc_html( $badge->name ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
