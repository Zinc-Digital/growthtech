/**
 * Site header.
 *
 *  - Sticky: the header scrolls away with the hero, then drops back in as a
 *    white bar once the banner has passed.
 *  - Mega menu: hover on desktop, keyboard/focus everywhere.
 *  - Mobile: slide-in panel with sub-menu accordions and a focus trap.
 *
 * Vanilla JS — the theme loads jQuery, but nothing here needs it.
 */
(function () {
	'use strict';

	var header = document.querySelector('[data-header]');
	var outer = document.querySelector('[data-header-outer]');

	if (!header || !outer) {
		return;
	}

	var isOverlay = header.classList.contains('site-header--overlay');
	var DESKTOP = 1024; // matches $md in _grid.scss

	// -----------------------------------------------------------------------
	// Sticky behaviour
	// -----------------------------------------------------------------------

	/**
	 * Scroll distance after which the header sticks.
	 *
	 * Over a hero that is the bottom of the banner, so the header reappears
	 * only once the artwork has gone. Otherwise it is the header's own height.
	 */
	function threshold() {
		if (!isOverlay) {
			return outer.offsetHeight;
		}

		var hero = document.querySelector('[data-header-hero], .banner, .hero, .page-banner');

		if (hero) {
			var rect = hero.getBoundingClientRect();
			var top = rect.top + window.pageYOffset;
			return top + hero.offsetHeight - header.offsetHeight;
		}

		return window.innerHeight * 0.6;
	}

	var limit = threshold();
	var stuck = false;
	var ticking = false;

	function applyStuck(next) {
		if (next === stuck) {
			return;
		}

		stuck = next;
		header.classList.toggle('is-stuck', next);

		// Once the header leaves the flow, hold its space open so the page
		// underneath does not jump up by the header's height.
		outer.style.height = !isOverlay && next ? outer.offsetHeight + 'px' : '';
	}

	function onScroll() {
		if (ticking) {
			return;
		}

		ticking = true;

		window.requestAnimationFrame(function () {
			var y = window.pageYOffset;

			// 40px of hysteresis stops the header flickering on and off when a
			// scroll lands right on the boundary.
			if (!stuck && y > limit) {
				applyStuck(true);
			} else if (stuck && y < limit - 40) {
				applyStuck(false);
			}

			ticking = false;
		});
	}

	function remeasure() {
		var was = stuck;

		// Measure against the un-stuck layout, then restore.
		applyStuck(false);
		limit = threshold();
		onScroll();

		if (was && window.pageYOffset > limit) {
			applyStuck(true);
		}
	}

	window.addEventListener('scroll', onScroll, { passive: true });
	window.addEventListener('resize', remeasure);
	window.addEventListener('load', remeasure);
	onScroll();

	// -----------------------------------------------------------------------
	// Mega menu
	// -----------------------------------------------------------------------

	var scrim = document.querySelector('[data-mega-scrim]');
	var parents = Array.prototype.slice.call(document.querySelectorAll('[data-mega-parent]'));
	var openParent = null;
	var closeTimer = null;

	function closeMega() {
		if (!openParent) {
			return;
		}

		var panel = openParent.querySelector('[data-mega-panel]');
		var link = openParent.querySelector('a');

		if (panel) {
			panel.classList.remove('is-open');
		}

		if (link) {
			link.setAttribute('aria-expanded', 'false');
		}

		openParent.classList.remove('is-open');
		header.classList.remove('has-mega-open');

		if (scrim) {
			scrim.classList.remove('is-open');
		}

		openParent = null;
	}

	function openMega(parent) {
		if (openParent === parent) {
			return;
		}

		closeMega();

		var panel = parent.querySelector('[data-mega-panel]');
		var link = parent.querySelector('a');

		if (!panel) {
			return;
		}

		panel.classList.add('is-open');
		parent.classList.add('is-open');
		header.classList.add('has-mega-open');

		if (link) {
			link.setAttribute('aria-expanded', 'true');
		}

		if (scrim) {
			scrim.classList.add('is-open');
		}

		openParent = parent;
	}

	function cancelClose() {
		if (closeTimer) {
			window.clearTimeout(closeTimer);
			closeTimer = null;
		}
	}

	function scheduleClose() {
		cancelClose();
		// Small grace period so the pointer can cross the gap into the panel.
		closeTimer = window.setTimeout(closeMega, 180);
	}

	parents.forEach(function (parent) {
		parent.addEventListener('mouseenter', function () {
			if (window.innerWidth >= DESKTOP) {
				cancelClose();
				openMega(parent);
			}
		});

		parent.addEventListener('mouseleave', function () {
			if (window.innerWidth >= DESKTOP) {
				scheduleClose();
			}
		});

		// Keyboard: open when focus enters, close when it leaves entirely.
		parent.addEventListener('focusin', function () {
			if (window.innerWidth >= DESKTOP) {
				cancelClose();
				openMega(parent);
			}
		});

		parent.addEventListener('focusout', function (event) {
			if (!parent.contains(event.relatedTarget)) {
				scheduleClose();
			}
		});
	});

	if (scrim) {
		scrim.addEventListener('mouseenter', closeMega);
		scrim.addEventListener('click', closeMega);
	}

	// -----------------------------------------------------------------------
	// Mobile panel
	// -----------------------------------------------------------------------

	var nav = document.querySelector('[data-mobile-nav]');
	var burger = document.querySelector('[data-nav-open]');

	if (nav && burger) {
		var panel = nav.querySelector('.site-nav-mobile__panel');
		var lastFocused = null;

		var focusableIn = function (root) {
			return Array.prototype.slice
				.call(
					root.querySelectorAll(
						'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
					)
				)
				.filter(function (el) {
					return el.offsetParent !== null;
				});
		};

		var openNav = function () {
			lastFocused = document.activeElement;
			nav.classList.add('is-open');
			document.body.classList.add('gt-nav-open');
			burger.setAttribute('aria-expanded', 'true');

			var first = focusableIn(panel)[0];
			if (first) {
				first.focus();
			}
		};

		var closeNav = function () {
			nav.classList.remove('is-open');
			document.body.classList.remove('gt-nav-open');
			burger.setAttribute('aria-expanded', 'false');

			if (lastFocused) {
				lastFocused.focus();
			}
		};

		burger.addEventListener('click', openNav);

		Array.prototype.forEach.call(nav.querySelectorAll('[data-nav-close]'), function (el) {
			el.addEventListener('click', closeNav);
		});

		// Sub-menu accordions.
		Array.prototype.forEach.call(nav.querySelectorAll('.site-nav-mobile__toggle'), function (toggle) {
			toggle.addEventListener('click', function () {
				var li = toggle.closest('li');

				if (!li) {
					return;
				}

				var open = li.classList.toggle('is-open');
				toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			});
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				if (nav.classList.contains('is-open')) {
					closeNav();
				}

				closeMega();
				return;
			}

			// Keep Tab inside the panel while it is open.
			if (event.key === 'Tab' && nav.classList.contains('is-open')) {
				var items = focusableIn(panel);

				if (!items.length) {
					return;
				}

				var first = items[0];
				var last = items[items.length - 1];

				if (event.shiftKey && document.activeElement === first) {
					event.preventDefault();
					last.focus();
				} else if (!event.shiftKey && document.activeElement === last) {
					event.preventDefault();
					first.focus();
				}
			}
		});

		// Drop back to the desktop header if the panel is open on resize.
		window.addEventListener('resize', function () {
			if (window.innerWidth >= DESKTOP && nav.classList.contains('is-open')) {
				closeNav();
			}
		});
	}
})();
