<?php
/**
 * Media List block — Figma 387:5948 (Frame 174). An optional serif heading
 * over rows of thumbnail + eyebrow + title + copy. Each thumbnail opens in
 * the shared lightbox.
 */

$heading = (string) get_field( 'heading' );
$rows    = get_field( 'rows' );
$rows    = is_array( $rows ) ? array_values( array_filter( $rows, function ( $row ) {
	return ! empty( $row['image'] ) || ! empty( $row['title'] ) || ! empty( $row['text'] );
} ) ) : array();

if ( ! $rows ) {
	if ( ! empty( $is_preview ) ) {
		echo '<p class="block-empty">' . esc_html__( 'Media List — add a row.', 'gt' ) . '</p>';
	}
	return;
}

$classes = 'b-media-list';
if ( ! empty( $block['className'] ) ) {
	$classes .= ' ' . sanitize_html_class( $block['className'] );
}
$anchor = ! empty( $block['anchor'] ) ? ' id="' . esc_attr( $block['anchor'] ) . '"' : '';
$id     = wp_unique_id( 'media-list-' );

wp_enqueue_script( 'gt-media-lightbox' );
?>
<section class="<?php echo esc_attr( $classes ); ?>"<?php echo $anchor; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo trim( $heading ) ? ' aria-labelledby="' . esc_attr( $id ) . '-title"' : ''; ?>>
	<?php if ( trim( $heading ) ) : ?>
		<h2 class="b-media-list__title" id="<?php echo esc_attr( $id ); ?>-title"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<ul class="b-media-list__rows">
		<?php foreach ( $rows as $row ) : ?>
			<?php
			$image_id = ! empty( $row['image'] ) ? (int) $row['image'] : 0;
			$full     = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
			$caption  = ! empty( $row['title'] ) ? $row['title'] : '';
			?>
			<li class="b-media-list__row">
				<?php if ( $image_id ) : ?>
					<div class="b-media-list__media">
						<?php
						echo wp_get_attachment_image( $image_id, 'gt-news-thumb', false, array(
							'class'   => 'b-media-list__img',
							'alt'     => '',
							'sizes'   => '(max-width: 767px) calc(100vw - 50px), 250px',
							'loading' => 'lazy',
						) );
						?>
						<?php if ( $full ) : ?>
							<button type="button" class="b-media-list__zoom media-zoom" data-media-zoom
								data-src="<?php echo esc_url( $full ); ?>" data-title="<?php echo esc_attr( $caption ); ?>"
								aria-label="<?php echo esc_attr( $caption ? sprintf( /* translators: %s: row title */ __( 'Enlarge: %s', 'gt' ), $caption ) : __( 'Enlarge image', 'gt' ) ); ?>">
								<?php gt_icon_svg( 'zoom' ); ?>
							</button>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="b-media-list__copy">
					<?php if ( ! empty( $row['eyebrow'] ) ) : ?>
						<p class="b-media-list__eyebrow"><?php echo esc_html( $row['eyebrow'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $row['title'] ) ) : ?>
						<h3 class="b-media-list__row-title"><?php echo esc_html( $row['title'] ); ?></h3>
					<?php endif; ?>
					<?php if ( ! empty( $row['text'] ) ) : ?>
						<div class="b-media-list__text"><?php echo wp_kses_post( wpautop( $row['text'] ) ); ?></div>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
