/**
 * Shared card slider for ACF blocks.
 *
 * A block marks its list with `data-block-slider` and configures it with data
 * attributes:
 *
 *   data-slides        cards shown at desktop        (default 3)
 *   data-slides-md     cards shown on tablet         (default 2)
 *   data-slides-sm     cards shown on mobile         (default 1)
 *   data-arrows        "1" to show prev/next         (default 1)
 *   data-dots          "1" to show dots              (default 0)
 *   data-progress      "1" for the square/bar progress indicator
 *   data-autoplay      milliseconds per slide, omitted or 0 for no autoplay
 *
 * Controls are appended to the slider itself unless the block wraps everything
 * in `[data-slider-scope]` and provides `[data-slider-dots]` and/or
 * `[data-slider-arrows]` targets — which is what lets a block place them in its
 * own layout. Scoping the lookup to the wrapper keeps multiple instances of the
 * same block on one page independent.
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

	/**
	 * A control target belonging to this slider's own block, so two instances on
	 * a page never write into each other's controls.
	 */
	function targetFor($slider, selector) {
		var $scope = $slider.closest('[data-slider-scope]');

		if (!$scope.length) {
			return null;
		}

		var $target = $scope.find(selector).first();

		return $target.length ? $target : null;
	}

	$(function () {
		var $sliders = $('[data-block-slider]');

		if (!$sliders.length || !$.fn.slick) {
			return;
		}

		$sliders.each(function () {
			var $slider = $(this);
			var isProgress = '1' === $slider.attr('data-progress');
			var autoplay = intAttr($slider, 'data-autoplay', 0);

			var $dotsTarget = targetFor($slider, '[data-slider-dots]');
			var $arrowsTarget = targetFor($slider, '[data-slider-arrows]');

			// The progress indicator is built out of Slick's dots.
			var wantsDots = isProgress || '1' === $slider.attr('data-dots');

			var settings = {
				slidesToShow: intAttr($slider, 'data-slides', 3),
				slidesToScroll: 1,
				infinite: false,
				dots: wantsDots,
				arrows: '0' !== $slider.attr('data-arrows'),
				// Slick's arrows are <button>s, so they are already reachable and
				// operable by keyboard; these add the labels and the icon.
				prevArrow: '<button type="button" class="slick-prev" aria-label="Previous">' + ARROW + '</button>',
				nextArrow: '<button type="button" class="slick-next" aria-label="Next">' + ARROW + '</button>',
				speed: reduced ? 0 : 400,
				autoplay: !!autoplay && !reduced,
				autoplaySpeed: autoplay || 6000,
				pauseOnHover: true,
				pauseOnFocus: true,
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

			if ($dotsTarget) {
				settings.appendDots = $dotsTarget;
			}

			if ($arrowsTarget) {
				settings.appendArrows = $arrowsTarget;
			}

			/**
			 * Flag every marker left of the active one as seen, so the indicator
			 * reads as progress rather than a plain dot row.
			 */
			function markProgress(index) {
				if (!isProgress) {
					return;
				}

				var $dots = ($dotsTarget || $slider).find('.slick-dots li');

				$dots.each(function (i) {
					$(this).toggleClass('is-past', i < index);
				});
			}

			// Cloned slides would otherwise be reachable by keyboard and read out
			// twice; hide them whenever Slick rebuilds them.
			$slider.on('init reInit afterChange', function (event, slick, current) {
				$slider.find('.slick-cloned')
					.attr('aria-hidden', 'true')
					.find('a, button')
					.attr('tabindex', '-1');

				markProgress(typeof current === 'number' ? current : (slick.currentSlide || 0));
			});

			if (autoplay && isProgress) {
				// Keep the bar's fill in step with the autoplay timer.
				var scope = $slider.closest('[data-slider-scope]')[0] || $slider[0];
				scope.style.setProperty('--slider-autoplay', autoplay + 'ms');
			}

			$slider.slick(settings);
		});
	});
})(jQuery);
