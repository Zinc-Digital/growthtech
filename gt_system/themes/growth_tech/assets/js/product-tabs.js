/**
 * Product tabs: a tablist on wide screens, an accordion under 768px. The
 * markup is the same; the mode only changes which controls drive it.
 */
(function () {
	'use strict';

	var mq = window.matchMedia('(max-width: 767px)');

	document.querySelectorAll('[data-product-tabs]').forEach(function (root) {
		var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
		var panels = Array.prototype.slice.call(root.querySelectorAll('[role="tabpanel"]'));
		var active = 0;

		function panelFor(tab) {
			return root.querySelector('[data-tab-panel="' + tab.getAttribute('data-tab') + '"]');
		}

		function select(index, focus) {
			active = index;
			tabs.forEach(function (tab, i) {
				var on = i === index;
				tab.classList.toggle('is-active', on);
				tab.setAttribute('aria-selected', on ? 'true' : 'false');
				tab.setAttribute('tabindex', on ? '0' : '-1');
				var panel = panelFor(tab);
				if (panel) {
					panel.hidden = !on;
					panel.classList.toggle('is-open', on);
					var acc = panel.querySelector('[data-tab-acc]');
					if (acc) { acc.setAttribute('aria-expanded', on ? 'true' : 'false'); }
				}
			});
			if (focus) { tabs[index].focus(); }
		}

		function applyMode() {
			var accordion = mq.matches;
			root.classList.toggle('product-tabs--accordion', accordion);
			if (accordion) {
				// Every panel is present; each one opens/closes on its own.
				panels.forEach(function (panel) { panel.hidden = false; });
			} else {
				select(active, false);
			}
		}

		tabs.forEach(function (tab, i) {
			tab.addEventListener('click', function () { select(i, false); });
			tab.addEventListener('keydown', function (event) {
				var next = null;
				if (event.key === 'ArrowRight') { next = (i + 1) % tabs.length; }
				if (event.key === 'ArrowLeft') { next = (i - 1 + tabs.length) % tabs.length; }
				if (event.key === 'Home') { next = 0; }
				if (event.key === 'End') { next = tabs.length - 1; }
				if (next !== null) { event.preventDefault(); select(next, true); }
			});
		});

		root.addEventListener('click', function (event) {
			var acc = event.target.closest('[data-tab-acc]');
			if (!acc || !root.classList.contains('product-tabs--accordion')) { return; }
			var panel = acc.closest('[role="tabpanel"]');
			var open = !panel.classList.contains('is-open');
			panel.classList.toggle('is-open', open);
			acc.setAttribute('aria-expanded', open ? 'true' : 'false');
		});

		applyMode();
		if (mq.addEventListener) { mq.addEventListener('change', applyMode); } else { mq.addListener(applyMode); }
	});
})();
