/**
 * Hero banner — Slick slider when the ACF repeater holds more than one slide.
 *
 * Timing is editable in WordPress: the banner carries a default duration
 * (`data-autoplay`, from the "Slide duration" field) and any slide may override
 * it (`data-duration`).
 *
 * Slick's own autoplay is deliberately switched off. Its timer is a setInterval
 * that runs independently of the slide transition, while the progress bar (an
 * animation on the active dot) starts when Slick applies `.slick-active` — at
 * the *beginning* of the 700ms transition. The two drift apart, so the bar
 * would finish early and sit still before the slide moved. Driving the advance
 * from one timer that starts at the same moment as the bar keeps them together.
 *
 * jQuery is required here because Slick is a jQuery plugin.
 */
(function ($) {
	'use strict';

	var FALLBACK = 6000;
	var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	$(function () {
		var $slider = $('[data-hero-slider]');

		if (!$slider.length || !$.fn.slick) {
			return;
		}

		var $section = $slider.closest('.banner-hero');
		var $dotsHolder = $section.find('[data-hero-dots]');
		var section = $section[0];

		var defaultSpeed = parseInt($slider.attr('data-autoplay'), 10);

		if (!defaultSpeed || defaultSpeed < 1000) {
			defaultSpeed = FALLBACK;
		}

		// Read per-slide overrides before Slick clones the slides, otherwise the
		// clones show up in the list too.
		var durations = $slider.find('.banner-hero__slide').map(function () {
			return parseInt($(this).attr('data-duration'), 10) || 0;
		}).get();

		function speedFor(index) {
			return durations[index] > 0 ? durations[index] : defaultSpeed;
		}

		// -- timer ------------------------------------------------------------
		var timer = null;
		var startedAt = 0;
		var remaining = 0;

		function advance() {
			timer = null;
			$slider.slick('slickNext');
		}

		function startTimer(ms) {
			window.clearTimeout(timer);
			timer = null;

			if (reduced) {
				return;
			}

			remaining = ms;
			startedAt = Date.now();
			timer = window.setTimeout(advance, ms);
		}

		function pauseTimer() {
			if (!timer) {
				return;
			}

			window.clearTimeout(timer);
			timer = null;
			// Keep what is left so the bar and the slide resume together.
			remaining = Math.max(0, remaining - (Date.now() - startedAt));
		}

		function resumeTimer() {
			if (reduced || timer || remaining <= 0) {
				return;
			}

			startedAt = Date.now();
			timer = window.setTimeout(advance, remaining);
		}

		/**
		 * Point the progress bar at this slide's duration. Must run before Slick
		 * moves `.slick-active`, or the bar starts on the previous duration.
		 */
		function setDuration(index) {
			section.style.setProperty('--gt-autoplay', speedFor(index) + 'ms');
		}

		function markProgress(index) {
			$section.find('.slick-dots li').each(function (i) {
				$(this).toggleClass('is-past', i < index);
			});
		}

		// Slide 0's duration has to be in place before Slick initialises, so the
		// first bar does not animate on the fallback value.
		setDuration(0);

		$slider.on('init reInit', function (event, slick) {
			var index = slick.currentSlide || 0;
			markProgress(index);
			startTimer(speedFor(index));
		});

		// beforeChange fires as the transition starts — the same moment the bar
		// begins — so the duration and the timer are set together here.
		$slider.on('beforeChange', function (event, slick, current, next) {
			setDuration(next);
			markProgress(next);
			startTimer(speedFor(next));
		});

		$slider.slick({
			slidesToShow: 1,
			slidesToScroll: 1,
			fade: true,
			arrows: false,
			dots: true,
			infinite: true,
			appendDots: $dotsHolder.length ? $dotsHolder : $slider,
			// Advancing is handled above so the bar cannot drift out of step.
			autoplay: false,
			speed: reduced ? 0 : 700,
			pauseOnHover: false,
			pauseOnFocus: false
		});

		// Hold both the bar and the timer while the user is on the banner.
		if (!reduced) {
			$section.on('mouseenter focusin', function () {
				$section.addClass('is-paused');
				pauseTimer();
			});

			$section.on('mouseleave', function () {
				$section.removeClass('is-paused');
				resumeTimer();
			});

			// Only resume once focus has actually left the banner — moving
			// between the link and the dots also fires focusout.
			$section.on('focusout', function (event) {
				if (!section.contains(event.relatedTarget)) {
					$section.removeClass('is-paused');
					resumeTimer();
				}
			});

			// A background tab throttles timers; re-sync on return.
			$(document).on('visibilitychange', function () {
				if (document.hidden) {
					pauseTimer();
				} else if (!$section.hasClass('is-paused')) {
					resumeTimer();
				}
			});
		}
	});
})(jQuery);
