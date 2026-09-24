<?php
/**
 * Guides band — Figma 384:1589 / 385:5502. Heading + copy + two CTAs left,
 * two guide cards right on a grey band. Fed either by a brand term (the
 * brand landing page) or by a ready-made fields array (the Brands page).
 *
 * @param array $args ['brand' => WP_Term] or ['fields' => [
 *   'heading' => string, 'accent' => string, 'text' => string,
 *   'cta1' => array|null, 'cta2' => array|null, 'guides' => array, 'label' => string
 * ]]
 */

$brand  = isset( $args['brand'] ) && $args['brand'] instanceof WP_Term ? $args['brand'] : null;
$fields = isset( $args['fields'] ) && is_array( $args['fields'] ) ? $args['fields'] : null;
if ( ! $brand && ! $fields ) {
	return;
}
if ( $brand ) {
	$fields = array(
		'heading' => gt_term_field( 'guides_heading', $brand, '' ),
		'accent'  => gt_term_field( 'guides_accent_line', $brand, '' ),
		'text'    => gt_term_field( 'guides_text', $brand, '' ),
		'cta1'    => gt_term_field( 'guides_cta_1', $brand, null ),
		'cta2'    => gt_term_field( 'guides_cta_2', $brand, null ),
		'guides'  => gt_term_field( 'guides', $brand, array() ),
		/* translators: %s: brand name */
		'label'   => sprintf( __( '%s guides', 'gt' ), $brand->name ),
	);
}
$fields  = array_merge( array( 'heading' => '', 'accent' => '', 'text' => '', 'cta1' => null, 'cta2' => null, 'guides' => array(), 'label' => __( 'Guides', 'gt' ) ), $fields );
$heading = (string) $fields['heading'];
$accent  = (string) $fields['accent'];
$text    = (string) $fields['text'];
$cta1    = $fields['cta1'];
$cta2    = $fields['cta2'];
$guides  = $fields['guides'];
$guides  = is_array( $guides ) ? array_values( array_filter( $guides, function ( $g ) {
	return ! empty( $g['title'] ) || ! empty( $g['lead'] ) || ! empty( $g['image'] );
} ) ) : array();

if ( ! $heading && ! $accent && ! $guides ) {
	return;
}
$has_title = (bool) ( $heading || $accent );
?>
<section class="brand-guides"<?php echo $has_title ? ' aria-labelledby="brand-guides-title"' : ' aria-label="' . esc_attr( $fields['label'] ) . '"'; ?>>
	<div class="brand-guides__inner">
		<div class="brand-guides__copy">
			<?php if ( $heading || $accent ) : ?>
				<h2 class="brand-guides__title" id="brand-guides-title"><?php
					echo esc_html( $heading );
					if ( $accent ) {
						echo ' <em class="brand-guides__accent">' . esc_html( $accent ) . '</em>';
					}
				?></h2>
			<?php endif; ?>
			<?php if ( $text ) : ?>
				<p class="brand-guides__text"><?php echo wp_kses_post( $text ); ?></p>
			<?php endif; ?>
			<?php if ( ( is_array( $cta1 ) && ! empty( $cta1['url'] ) ) || ( is_array( $cta2 ) && ! empty( $cta2['url'] ) ) ) : ?>
				<div class="brand-guides__actions">
					<?php if ( is_array( $cta1 ) && ! empty( $cta1['url'] ) ) : ?>
						<a class="btn-flat" href="<?php echo esc_url( $cta1['url'] ); ?>"
							<?php echo ! empty( $cta1['target'] ) ? 'target="' . esc_attr( $cta1['target'] ) . '" rel="noopener"' : ''; ?>>
							<span><?php echo esc_html( ! empty( $cta1['title'] ) ? $cta1['title'] : __( 'Guides', 'gt' ) ); ?></span>
							<?php gt_arrow_svg(); ?>
						</a>
					<?php endif; ?>
					<?php if ( is_array( $cta2 ) && ! empty( $cta2['url'] ) ) : ?>
						<a class="brand-guides__link" href="<?php echo esc_url( $cta2['url'] ); ?>"
							<?php echo ! empty( $cta2['target'] ) ? 'target="' . esc_attr( $cta2['target'] ) . '" rel="noopener"' : ''; ?>>
							<?php echo esc_html( ! empty( $cta2['title'] ) ? $cta2['title'] : __( 'Explore the Plant Academy', 'gt' ) ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $guides ) : ?>
			<ul class="brand-guides__cards">
				<?php foreach ( $guides as $guide ) : ?>
					<?php
					$image_id = ! empty( $guide['image'] ) ? (int) $guide['image'] : 0;
					$link     = ! empty( $guide['link'] ) && is_array( $guide['link'] ) ? $guide['link'] : null;
					?>
					<li class="brand-guides__card">
						<?php
						if ( $image_id ) {
							echo wp_get_attachment_image( $image_id, 'gt-guide', false, array(
								'class'   => 'brand-guides__card-img',
								'alt'     => '',
								'sizes'   => '(max-width: 767px) calc(100vw - 50px), 347px',
								'loading' => 'lazy',
							) );
						}
						?>
						<div class="brand-guides__panel">
							<?php if ( ! empty( $guide['lead'] ) || ! empty( $guide['title'] ) ) : ?>
								<p class="brand-guides__card-title">
									<?php if ( ! empty( $guide['lead'] ) ) : ?><strong><?php echo esc_html( $guide['lead'] ); ?></strong> <?php endif; ?>
									<?php echo esc_html( ! empty( $guide['title'] ) ? $guide['title'] : '' ); ?>
								</p>
							<?php endif; ?>
							<?php if ( $link && ! empty( $link['url'] ) ) : ?>
								<a class="brand-guides__card-link" href="<?php echo esc_url( $link['url'] ); ?>"
									<?php echo ! empty( $link['target'] ) ? 'target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; ?>>
									<?php echo esc_html( ! empty( $link['title'] ) ? $link['title'] : __( 'Read the Guide', 'gt' ) ); ?>
								</a>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
