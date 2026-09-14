/**
 * Product gallery — slider, thumbnails and lightbox for the product page.
 *
 * Markup comes from template-parts/shop/product-gallery.php. With one image
 * only the zoom/lightbox is wired; with more the slides become a Slick
 * slider whose arrows are appended into [data-gallery-arrows].
 */
(function ($) {
	'use strict';

	var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	var ARROW = '<svg viewBox="0 0 35 35" width="35" height="35" aria-hidden="true" focusable="false"><path fill="currentColor" d="M26.2027 18.1824C26.3915 18.0066 26.5 17.7605 26.5 17.5027C26.5 17.2449 26.3915 17.0027 26.2027 16.823L19.1313 10.2601C18.7455 9.90066 18.1348 9.91629 17.7692 10.2913C17.4036 10.6663 17.4156 11.2601 17.8013 11.6156L23.129 16.5651H9.46429C8.92991 16.5651 8.5 16.9831 8.5 17.5027C8.5 18.0223 8.92991 18.4403 9.46429 18.4403H23.129L17.7973 23.3859C17.4116 23.7453 17.3996 24.3352 17.7652 24.7102C18.1308 25.0852 18.7415 25.0969 19.1272 24.7414L26.1987 18.1785L26.2027 18.1824Z"/></svg>';

	$(function () {
		$('[data-product-gallery]').each(function () {
			var $gallery = $(this);
			var $slides = $gallery.find('[data-gallery-slides]');
			var $items = $slides.children('.product-gallery__slide');
			var $thumbs = $gallery.find('[data-gallery-thumb]');
			var $arrows = $gallery.find('[data-gallery-arrows]');
			var $zoom = $gallery.find('[data-gallery-zoom]');
			var $lightbox = $gallery.find('[data-gallery-lightbox]');
			var $lightboxImg = $lightbox.find('[data-lightbox-img]');
			var count = $items.length;
			var current = 0;
			var lastFocus = null;

			function fullSrc(index) {
				var $img = $items.eq(index).find('img');
				return $img.attr('data-full') || $img.attr('src') || '';
			}

			function setThumb(index) {
				$thumbs.each(function () {
					var active = parseInt(this.getAttribute('data-gallery-thumb'), 10) === index;
					this.classList.toggle('is-active', active);
					if (active) { this.setAttribute('aria-current', 'true'); } else { this.removeAttribute('aria-current'); }
				});
			}

			// -- slider ---------------------------------------------------------
			if (count > 1 && $.fn.slick) {
				$slides.on('init afterChange', function (event, slick, index) {
					current = typeof index === 'number' ? index : (slick.currentSlide || 0);
					setThumb(current);
				});
				$slides.slick({
					slidesToShow: 1,
					slidesToScroll: 1,
					infinite: false,
					dots: false,
					arrows: $arrows.length > 0,
					appendArrows: $arrows.length ? $arrows : $slides,
					prevArrow: '<button type="button" class="slick-prev" aria-label="Previous image">' + ARROW + '</button>',
					nextArrow: '<button type="button" class="slick-next" aria-label="Next image">' + ARROW + '</button>',
					speed: reduced ? 0 : 350,
					adaptiveHeight: false
				});
			}

			$thumbs.on('click', function (event) {
				event.preventDefault();
				var index = parseInt(this.getAttribute('data-gallery-thumb'), 10) || 0;
				if ($slides.hasClass('slick-initialized')) {
					$slides.slick('slickGoTo', index);
				} else {
					current = index;
					setThumb(index);
				}
			});

			// -- lightbox -------------------------------------------------------
			if (!$lightbox.length) {
				return;
			}

			function showLightbox(index) {
				current = Math.max(0, Math.min(count - 1, index));
				var src = fullSrc(current);
				if (!src) { return; }
				$lightboxImg.attr('src', src);
				$lightbox.prop('hidden', false);
				document.body.classList.add('product-lightbox-open');
				$lightbox.find('[data-lightbox-close]').trigger('focus');
			}

			function hideLightbox() {
				$lightbox.prop('hidden', true);
				document.body.classList.remove('product-lightbox-open');
				if (lastFocus) { lastFocus.focus({ preventScroll: true }); }
			}

			function step(delta) {
				var next = current + delta;
				if (next < 0 || next >= count) { return; }
				showLightbox(next);
				if ($slides.hasClass('slick-initialized')) { $slides.slick('slickGoTo', next); }
			}

			$zoom.on('click', function () {
				lastFocus = this;
				showLightbox(current);
			});
			$items.on('click', 'img', function () {
				lastFocus = $zoom[0] || this;
				showLightbox(current);
			});
			$lightbox.on('click', '[data-lightbox-close]', hideLightbox);
			$lightbox.on('click', '[data-lightbox-prev]', function () { step(-1); });
			$lightbox.on('click', '[data-lightbox-next]', function () { step(1); });
			$lightbox.on('click', function (event) {
				if (event.target === this) { hideLightbox(); }
			});

			$(document).on('keydown', function (event) {
				if ($lightbox.prop('hidden')) { return; }
				if (event.key === 'Escape') { hideLightbox(); }
				if (event.key === 'ArrowLeft') { step(-1); }
				if (event.key === 'ArrowRight') { step(1); }
			});
		});
	});
})(jQuery);
