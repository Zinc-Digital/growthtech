<?php
/**
 * Media List block — Figma 387:5948 (Frame 174). An optional serif heading
 * over rows of thumbnail + eyebrow + title + copy.
 *
 * A row is either an image, which opens full size in the shared lightbox, or a
 * video — uploaded, YouTube or Vimeo — which plays there instead. Either way
 * the row shows the same still; only the control on it changes.
 */

$heading = (string) get_field( 'heading' );
$rows    = get_field( 'rows' );
$rows    = is_array( $rows ) ? array_values( array_filter( $rows, function ( $row ) {
	return ! empty( $row['image'] ) || ! empty( $row['title'] ) || ! empty( $row['text'] )
		|| ! empty( $row['video_file'] ) || ! empty( $row['video_url'] );
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
			$video    = gt_media_row_video( $row );
			// Nothing to open if the row is an image with no full size behind it.
			$openable = $video || $full;
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
						<?php if ( $openable ) : ?>
							<?php
							$label = $video
								? ( $caption ? sprintf( /* translators: %s: row title */ __( 'Play: %s', 'gt' ), $caption ) : __( 'Play video', 'gt' ) )
								: ( $caption ? sprintf( /* translators: %s: row title */ __( 'Enlarge: %s', 'gt' ), $caption ) : __( 'Enlarge image', 'gt' ) );
							?>
							<button type="button" class="b-media-list__zoom media-zoom<?php echo $video ? ' media-zoom--play' : ''; ?>"
								<?php echo gt_lightbox_attrs( $video, $full, $caption ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?>
								aria-label="<?php echo esc_attr( $label ); ?>">
								<?php gt_icon_svg( $video ? 'play' : 'zoom' ); ?>
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
