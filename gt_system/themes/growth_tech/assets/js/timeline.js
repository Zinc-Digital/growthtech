/**
 * Timeline block — Figma 384:2551.
 *
 * The rail is an ordinary horizontal scroller, so dragging, a trackpad swipe
 * and keyboard scrolling all work with no JavaScript at all. This adds the
 * arrow buttons, the progress bar and pointer dragging on top, and keeps the
 * arrows' disabled states in step with the scroll position.
 */
(function () {
	'use strict';

	function setup(root) {
		var rail = root.querySelector('[data-timeline-rail]');
		var items = root.querySelector('[data-timeline-items]');
		var progress = root.querySelector('[data-timeline-progress]');
		var prev = root.querySelector('[data-timeline-prev]');
		var next = root.querySelector('[data-timeline-next]');
		if (!rail || !items) { return; }

		var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		function maxScroll() {
			return Math.max(0, rail.scrollWidth - rail.clientWidth);
		}

		function step() {
			var item = items.querySelector('.b-timeline__item');
			if (!item) { return rail.clientWidth; }
			var styles = window.getComputedStyle(items);
			var gap = parseFloat(styles.columnGap || styles.gap) || 0;

			return item.getBoundingClientRect().width + gap;
		}

		function update() {
			var max = maxScroll();

			// The bar is a scrollbar thumb, as the design draws it: its width is
			// the share of the rail on screen and it slides with the scroll.
			if (progress) {
				var visible = rail.scrollWidth > 0 ? rail.clientWidth / rail.scrollWidth : 1;
				var offset = rail.scrollWidth > 0 ? rail.scrollLeft / rail.scrollWidth : 0;
				progress.style.width = Math.min(100, visible * 100).toFixed(2) + '%';
				progress.style.left = (offset * 100).toFixed(2) + '%';
			}
			if (prev) { prev.classList.toggle('slick-disabled', rail.scrollLeft <= 1); }
			if (next) { next.classList.toggle('slick-disabled', max <= 1 || rail.scrollLeft >= max - 1); }
		}

		function scrollBy(direction) {
			rail.scrollBy({ left: direction * step(), behavior: reduced ? 'auto' : 'smooth' });
		}

		if (prev) { prev.addEventListener('click', function () { scrollBy(-1); }); }
		if (next) { next.addEventListener('click', function () { scrollBy(1); }); }
		rail.addEventListener('scroll', update, { passive: true });
		window.addEventListener('resize', update);

		// Drag to pan. Pointer events cover mouse and pen; touch already scrolls.
		var dragging = false;
		var startX = 0;
		var startScroll = 0;

		rail.addEventListener('pointerdown', function (event) {
			if (event.pointerType === 'touch') { return; }
			dragging = true;
			startX = event.clientX;
			startScroll = rail.scrollLeft;
			rail.classList.add('is-dragging');
		});

		rail.addEventListener('pointermove', function (event) {
			if (!dragging) { return; }
			var moved = event.clientX - startX;
			// Only take over once it is clearly a drag, so clicks still land.
			if (Math.abs(moved) > 3) {
				event.preventDefault();
				rail.scrollLeft = startScroll - moved;
			}
		});

		['pointerup', 'pointercancel', 'pointerleave'].forEach(function (name) {
			rail.addEventListener(name, function () {
				dragging = false;
				rail.classList.remove('is-dragging');
			});
		});

		update();
	}

	document.addEventListener('DOMContentLoaded', function () {
		Array.prototype.forEach.call(document.querySelectorAll('[data-timeline]'), setup);
	});
}());
