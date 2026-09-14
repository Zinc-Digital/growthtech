<?php
/**
 * Lightbox shell for the "How to use" steps. One per page; product-howto.js
 * fills [data-howto-stage] with an <img>, <video> or <iframe> on open and
 * empties it on close so playback stops and nothing third-party loads until
 * asked for.
 */
?>
<div class="product-lightbox product-lightbox--howto" data-howto-lightbox hidden role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'How to use', 'gt' ); ?>">
	<button type="button" class="product-lightbox__close" data-howto-close aria-label="<?php esc_attr_e( 'Close', 'gt' ); ?>"><?php gt_icon_svg( 'close' ); ?></button>
	<div class="product-lightbox__stage" data-howto-stage></div>
</div>
