<?php
/**
 * Block: Timeline — Figma 384:2551 (Frame 241).
 *
 * A heading with the arrow pair, then milestones on a horizontal rail that can
 * be dragged, scrolled or stepped through. The rail markers, the progress bar
 * and its end labels all come from the milestones themselves.
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (unused).
 * @param bool   $is_preview True when rendering in the editor.
 */

$heading    = (string) get_field( 'heading' );
$hint       = (string) get_field( 'hint' );
$end_label  = (string) get_field( 'end_label' );
$milestones = get_field( 'milestones' );
$milestones = is_array( $milestones ) ? array_values( array_filter( $milestones, function ( $m ) {
	return ! empty( $m['year'] ) || ! empty( $m['title'] );
} ) ) : array();

if ( ! $milestones ) {
	if ( $is_preview ) {
		echo '<p class="block-empty">' . esc_html__( 'Timeline — add a milestone.', 'gt' ) . '</p>';
	}
	return;
}

$classes = 'b-timeline';
if ( ! empty( $block['className'] ) ) {
	$classes .= ' ' . sanitize_html_class( $block['className'] );
}
$anchor    = ! empty( $block['anchor'] ) ? ' id="' . esc_attr( $block['anchor'] ) . '"' : '';
$id        = wp_unique_id( 'timeline-' );
$hint      = '' !== trim( $hint ) ? $hint : __( 'Drag, scroll or use the arrows', 'gt' );
$end_label = '' !== trim( $end_label ) ? $end_label : __( 'Today', 'gt' );
$first     = isset( $milestones[0]['year'] ) ? $milestones[0]['year'] : '';

wp_enqueue_script( 'gt-timeline' );
?>
<section class="<?php echo esc_attr( $classes ); ?>"<?php echo $anchor; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-timeline<?php echo trim( $heading ) ? ' aria-labelledby="' . esc_attr( $id ) . '-title"' : ' aria-label="' . esc_attr__( 'Timeline', 'gt' ) . '"'; ?>>
	<div class="b-timeline__inner">
		<div class="b-timeline__head">
			<?php if ( trim( $heading ) ) : ?>
				<h2 class="b-timeline__title" id="<?php echo esc_attr( $id ); ?>-title"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<div class="b-timeline__arrows shop-slider__arrows">
				<button type="button" class="slick-prev" data-timeline-prev aria-label="<?php esc_attr_e( 'Previous milestones', 'gt' ); ?>"><?php gt_arrow_svg(); ?></button>
				<button type="button" class="slick-next" data-timeline-next aria-label="<?php esc_attr_e( 'Next milestones', 'gt' ); ?>"><?php gt_arrow_svg(); ?></button>
			</div>
		</div>

		<div class="b-timeline__rail" data-timeline-rail>
			<span class="b-timeline__rail-line" aria-hidden="true"></span>
			<ol class="b-timeline__items" data-timeline-items>
				<?php foreach ( $milestones as $milestone ) : ?>
					<li class="b-timeline__item">
						<span class="b-timeline__marker" aria-hidden="true"></span>

						<p class="b-timeline__year">
							<span class="b-timeline__year-num"><?php echo esc_html( $milestone['year'] ); ?></span>
							<?php if ( ! empty( $milestone['tag'] ) ) : ?>
								<span class="b-timeline__year-tag"><?php echo esc_html( $milestone['tag'] ); ?></span>
							<?php endif; ?>
						</p>

						<div class="b-timeline__copy">
							<?php if ( ! empty( $milestone['title'] ) ) : ?>
								<h3 class="b-timeline__item-title"><?php echo esc_html( $milestone['title'] ); ?></h3>
							<?php endif; ?>
							<?php if ( ! empty( $milestone['text'] ) ) : ?>
								<p class="b-timeline__item-text"><?php echo esc_html( $milestone['text'] ); ?></p>
							<?php endif; ?>
						</div>

						<?php if ( ! empty( $milestone['image'] ) ) : ?>
							<?php
							echo wp_get_attachment_image( (int) $milestone['image'], 'gt-guide-sm', false, array(
								'class'   => 'b-timeline__img',
								'alt'     => '',
								'sizes'   => '350px',
								'loading' => 'lazy',
							) );
							?>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>

		<div class="b-timeline__footer">
			<div class="b-timeline__progress">
				<span class="b-timeline__progress-bar" data-timeline-progress></span>
			</div>
			<div class="b-timeline__labels">
				<span class="b-timeline__label"><?php echo esc_html( $first ); ?></span>
				<span class="b-timeline__hint"><?php echo esc_html( $hint ); ?></span>
				<span class="b-timeline__label"><?php echo esc_html( $end_label ); ?></span>
			</div>
		</div>
	</div>
</section>
