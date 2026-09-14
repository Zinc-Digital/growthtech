/**
 * Shop filters — progressive enhancement over the GET form.
 *
 * Any change to a checkbox, the search box or the sort select fetches the
 * grid, sidebar, count and load-more from admin-ajax and swaps them in,
 * then pushes the canonical URL. Load more appends the next page. Back and
 * forward re-fetch from history state. Without JS everything still works
 * as plain GET requests.
 */
(function () {
	'use strict';

	var settings = window.gtShop || {};
	var page = document.querySelector('.shop');
	if (!page || !settings.ajaxUrl || !window.fetch) {
		return;
	}

	var sidebar = page.querySelector('[data-shop-sidebar]');
	var main = page.querySelector('.shop__main');
	var toggle = page.querySelector('[data-shop-filters-toggle]');
	var collapsed = {};
	var controller = null;
	var searchTimer = null;

	// -- helpers ---------------------------------------------------------------

	function form() { return page.querySelector('[data-shop-form]'); }
	function grid() { return page.querySelector('[data-shop-grid]'); }
	function sortSelect() { return page.querySelector('[data-shop-sort]'); }
	function moreWrap() { return page.querySelector('[data-shop-more]'); }

	function html(string) {
		var tpl = document.createElement('template');
		tpl.innerHTML = string.trim();
		return tpl.content;
	}

	/** Read the current selection from the form + sort select as flat params. */
	function readParams() {
		var params = {};
		var f = form();
		if (f) {
			var groups = {};
			f.querySelectorAll('input[type="checkbox"]:checked').forEach(function (box) {
				var key = box.name.replace('[]', '');
				groups[key] = groups[key] || [];
				groups[key].push(box.value);
			});
			Object.keys(groups).forEach(function (key) { params[key] = groups[key].join(','); });
			var q = f.querySelector('input[name="q"]');
			if (q && q.value.trim()) { params.q = q.value.trim(); }
		}
		var sort = sortSelect();
		if (sort && sort.value && sort.value !== 'brands') { params.orderby = sort.value; }
		return params;
	}

	function query(params) {
		var parts = ['action=gt_shop_filter'];
		Object.keys(params).forEach(function (key) {
			parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(params[key]));
		});
		return settings.ajaxUrl + (settings.ajaxUrl.indexOf('?') === -1 ? '?' : '&') + parts.join('&');
	}

	function setBusy(busy) {
		var g = grid();
		if (g) { g.setAttribute('aria-busy', busy ? 'true' : 'false'); }
		var m = moreWrap();
		if (m) { m.classList.toggle('is-loading', busy); }
	}

	function rememberCollapsed() {
		collapsed = {};
		page.querySelectorAll('[data-shop-group]').forEach(function (group) {
			collapsed[group.getAttribute('data-shop-group')] = group.classList.contains('is-collapsed');
		});
	}

	function restoreCollapsed() {
		page.querySelectorAll('[data-shop-group]').forEach(function (group) {
			var key = group.getAttribute('data-shop-group');
			if (key in collapsed) { setGroup(group, collapsed[key]); }
		});
	}

	function setGroup(group, isCollapsed) {
		group.classList.toggle('is-collapsed', isCollapsed);
		var button = group.querySelector('[data-shop-group-toggle]');
		if (button) { button.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true'); }
	}

	// -- fetch + swap ----------------------------------------------------------

	function load(params, options) {
		options = options || {};
		if (controller) { controller.abort(); }
		controller = new AbortController();
		var mine = controller;
		setBusy(true);

		var focusValue = options.focusValue || null;
		var focusName = options.focusName || null;

		return fetch(query(params), { signal: controller.signal, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res || !res.success) { throw new Error('bad response'); }
				var d = res.data;
				var g = grid();

				if (options.append && g) {
					g.appendChild(html(d.grid));
				} else if (g) {
					g.innerHTML = d.grid;
				}

				if (!options.append && sidebar) {
					rememberCollapsed();
					// The search input is about to be destroyed by the innerHTML
					// swap below. Capture what's actually in it first — the user
					// may have typed more while this request was in flight, so
					// the value this response reflects can already be stale.
					var liveQuery = focusName === 'q' ? sidebar.querySelector('input[name="q"]') : null;
					var liveQueryValue = liveQuery ? liveQuery.value : null;
					// Only restore focus/caret if the search input actually had
					// focus before the swap — e.g. a checkbox change can also
					// land here with focusName 'q' from a queued search request,
					// and it must not steal focus away from the checkbox.
					var liveQueryWasFocused = !!liveQuery && liveQuery === document.activeElement;
					sidebar.innerHTML = d.sidebar;
					restoreCollapsed();
					if (focusValue) {
						var box = sidebar.querySelector('input[name="' + focusName + '"][value="' + focusValue + '"]');
						if (box) { box.focus({ preventScroll: true }); }
					} else if (focusName === 'q') {
						var searchInput = sidebar.querySelector('input[name="q"]');
						if (searchInput) {
							if (null !== liveQueryValue) { searchInput.value = liveQueryValue; }
							if (liveQueryWasFocused) {
								searchInput.focus({ preventScroll: true });
								var caret = searchInput.value.length;
								searchInput.setSelectionRange(caret, caret);
							}
						}
					}
				}

				var s = sortSelect();
				if (s && d.selection && d.selection.orderby) { s.value = d.selection.orderby; }

				var count = page.querySelector('[data-shop-count]');
				if (count) { count.textContent = d.count; }

				var oldMore = moreWrap();
				if (oldMore) { oldMore.remove(); }
				if (d.more && main) { main.appendChild(html(d.more)); }

				var empty = main ? main.querySelector('.shop__empty') : null;
				if (empty) { empty.remove(); }
				if (d.total === 0 && main) {
					var p = document.createElement('p');
					p.className = 'shop__empty';
					p.textContent = (settings.strings && settings.strings.empty) || 'No products match those filters. Try removing one.';
					main.appendChild(p);
				}

				if (options.append) {
					// Load more never creates its own history entry — replace in
					// place so Back/Forward can't land on an append-only state
					// that would render just the appended page.
					history.replaceState({ gtShop: readParams() }, '', window.location.href);
				} else if (options.push !== false) {
					history.pushState({ gtShop: params }, '', d.url);
				}
				if (toggle) { updateToggleBadge(d.selection); }
			})
			.catch(function (err) {
				if (err.name !== 'AbortError') {
					// Fall back to a full page load with the same parameters.
					var f = form();
					if (f) { f.submit(); }
				}
			})
			.then(function () { if (controller === mine) { setBusy(false); } });
	}

	function updateToggleBadge(selection) {
		var active = 0;
		['brands', 'growing-medium', 'growing-stage'].forEach(function (key) {
			active += (selection[key] || []).length;
		});
		if (!settings.categoryLocked) { active += (selection.categories || []).length; }
		var badge = toggle.querySelector('.shop__filters-badge');
		if (active && !badge) {
			badge = document.createElement('span');
			badge.className = 'shop__filters-badge';
			toggle.firstElementChild.appendChild(badge);
		}
		if (badge) {
			if (active) { badge.textContent = String(active); } else { badge.remove(); }
		}
	}

	// -- events ----------------------------------------------------------------

	page.addEventListener('change', function (event) {
		var target = event.target;
		if (target.matches('[data-shop-form] input[type="checkbox"]')) {
			load(readParams(), { focusValue: target.value, focusName: target.name });
		} else if (target.matches('[data-shop-sort]')) {
			load(readParams());
		}
	});

	page.addEventListener('input', function (event) {
		if (!event.target.matches('[data-shop-form] input[name="q"]')) { return; }
		clearTimeout(searchTimer);
		searchTimer = setTimeout(function () { load(readParams(), { focusName: 'q' }); }, 350);
	});

	page.addEventListener('submit', function (event) {
		if (event.target.matches('[data-shop-form], [data-shop-sort-form]')) {
			event.preventDefault();
			clearTimeout(searchTimer);
			load(readParams());
		}
	});

	page.addEventListener('click', function (event) {
		var groupToggle = event.target.closest('[data-shop-group-toggle]');
		if (groupToggle) {
			var group = groupToggle.closest('[data-shop-group]');
			setGroup(group, !group.classList.contains('is-collapsed'));
			return;
		}

		var more = event.target.closest('[data-shop-more-link]');
		if (more) {
			event.preventDefault();
			var wrap = more.closest('[data-shop-more]');
			var params = readParams();
			params.paged = wrap.getAttribute('data-next-page');
			params.append = '1';
			var g = grid();
			var before = g ? g.children.length : 0;
			load(params, { append: true, push: false }).then(function () {
				var g2 = grid();
				if (g2 && g2.children[before]) {
					var link = g2.children[before].querySelector('a');
					if (link) { link.focus({ preventScroll: true }); }
				}
			});
			return;
		}

		if (event.target.closest('[data-shop-filters-toggle]')) {
			openSidebar(true);
			return;
		}
		if (event.target.closest('[data-shop-filters-close]')) {
			openSidebar(false);
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && sidebar && sidebar.classList.contains('is-open')) {
			openSidebar(false);
		}
	});

	function openSidebar(open) {
		if (!sidebar) { return; }
		sidebar.classList.toggle('is-open', open);
		document.body.classList.toggle('shop-filters-open', open);
		if (toggle) { toggle.setAttribute('aria-expanded', open ? 'true' : 'false'); }
		if (open) {
			var first = sidebar.querySelector('input, button');
			if (first) { first.focus(); }
		} else if (toggle) {
			toggle.focus();
		}
	}

	window.addEventListener('popstate', function (event) {
		if (event.state && event.state.gtShop) {
			load(event.state.gtShop, { push: false });
		} else {
			window.location.reload();
		}
	});

	// Seed history so the first back-navigation restores the initial grid.
	history.replaceState({ gtShop: readParams() }, '', window.location.href);
})();
