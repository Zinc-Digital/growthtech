/**
 * Shared card slider for ACF blocks.
 *
 * Any block that renders more cards than its row can hold marks the list with
 * `data-block-slider` and configures it with data attributes:
 *
 *   data-slides       cards shown at desktop      (default 3)
 *   data-slides-md    cards shown on tablet       (default 2)
 *   data-slides-sm    cards shown on mobile       (default 1)
 *   data-arrows       "1" to show prev/next       (default 1)
 *   data-dots         "1" to show dots            (default 0)
 *
 * Loaded only on pages whose blocks actually need it.
 */
(function ($) {
	'use strict';

	var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	// The Figma arrow (21 x 18), inline so it inherits the button's colour.
	var ARROW = '<svg class="block-slider__arrow" viewBox="0 0 21 18" width="21" height="18" ' +
		'aria-hidden="true" focusable="false"><path fill="currentColor" ' +
		'd="M20.6531 9.81831C20.8734 9.60737 21 9.31205 21 9.00268C21 8.6933 20.8734 8.40268 20.6531 8.18706L12.4031 0.312055C11.9531 -0.119195 11.2406 -0.100445 10.8141 0.349555C10.3875 0.799555 10.4016 1.51206 10.8516 1.93862L17.0672 7.87768H1.125C0.501562 7.87768 0 8.37924 0 9.00268C0 9.62612 0.501562 10.1277 1.125 10.1277H17.0672L10.8469 16.0621C10.3969 16.4933 10.3828 17.2012 10.8094 17.6512C11.2359 18.1012 11.9484 18.1152 12.3984 17.6887L20.6484 9.81362L20.6531 9.81831Z"/></svg>';

	function intAttr($el, name, fallback) {
		var value = parseInt($el.attr(name), 10);

		return isNaN(value) ? fallback : value;
	}

	$(function () {
		var $sliders = $('[data-block-slider]');

		if (!$sliders.length || !$.fn.slick) {
			return;
		}

		$sliders.each(function () {
			var $slider = $(this);

			var settings = {
				slidesToShow: intAttr($slider, 'data-slides', 3),
				slidesToScroll: 1,
				infinite: false,
				dots: '1' === $slider.attr('data-dots'),
				arrows: '0' !== $slider.attr('data-arrows'),
				// Slick's arrows are <button>s, so they are already reachable and
				// operable by keyboard; these add the labels and the icon.
				prevArrow: '<button type="button" class="slick-prev" aria-label="Previous">' + ARROW + '</button>',
				nextArrow: '<button type="button" class="slick-next" aria-label="Next">' + ARROW + '</button>',
				speed: reduced ? 0 : 400,
				responsive: [
					{
						breakpoint: 1266,
						settings: { slidesToShow: intAttr($slider, 'data-slides-md', 2) }
					},
					{
						breakpoint: 768,
						settings: { slidesToShow: intAttr($slider, 'data-slides-sm', 1) }
					}
				]
			};

			// Cloned slides would otherwise be reachable by keyboard and read out
			// twice; hide them whenever Slick rebuilds them.
			$slider.on('init reInit afterChange', function () {
				$slider.find('.slick-cloned')
					.attr('aria-hidden', 'true')
					.find('a, button')
					.attr('tabindex', '-1');
			});

			$slider.slick(settings);
		});
	});
})(jQuery);
