/**
 * Stockist finder. The list is server-rendered; this filters, sorts and
 * reorders the cards from their data attributes, and drives the Google Map
 * when a key is set (window.gtStockistsMapReady is the Maps callback).
 */
(function () {
	'use strict';

	var settings = window.gtStockists || {};
	var strings = settings.strings || {}; // A missing gtStockists (e.g. script loaded off-page) must never throw.
	var root = document.querySelector('[data-stockists]');
	if (!root) { return; }

	var list = root.querySelector('[data-stockists-cards]');
	var cards = Array.prototype.slice.call(list.querySelectorAll('.stockists-card')).map(function (el) {
		return {
			el: el,
			id: el.getAttribute('data-id'),
			name: el.getAttribute('data-name') || '',
			country: el.getAttribute('data-country') || '',
			lat: parseFloat(el.getAttribute('data-lat')),
			lng: parseFloat(el.getAttribute('data-lng')),
			products: (el.getAttribute('data-products') || '').split(',').filter(Boolean),
			brands: (el.getAttribute('data-brands') || '').split(',').filter(Boolean),
			search: el.getAttribute('data-search') || '',
			marker: null,
			distance: null
		};
	});

	var ui = {
		regionButtons: Array.prototype.slice.call(root.querySelectorAll('[data-stockists-region] [data-region]')),
		countryWrap: root.querySelector('[data-stockists-country-wrap]'),
		country: root.querySelector('[data-stockists-country]'),
		search: root.querySelector('[data-stockists-search]'),
		product: root.querySelector('[data-stockists-product]'),
		sort: root.querySelector('[data-stockists-sort]'),
		count: root.querySelector('[data-stockists-count]'),
		empty: root.querySelector('[data-stockists-empty]'),
		note: root.querySelector('[data-stockists-note]'),
		canvas: root.querySelector('[data-stockists-canvas]')
	};

	var state = {
		region: root.getAttribute('data-region') === 'international' ? 'international' : 'uk',
		country: '',
		q: ui.search ? ui.search.value.trim() : '',
		product: ui.product ? ui.product.value : '',
		brand: root.getAttribute('data-brand') || '',
		sort: ui.sort ? ui.sort.value : 'az',
		origin: null,
		originLabel: ''
	};

	var map = null;
	var info = null;
	var geocodeTimer = null;
	var geocodeSeq = 0;

	function sprintf(format, value) { return String(format).replace('%d', value).replace('%s', value); }

	function haversine(a, b) {
		var R = 6371;
		var dLat = (b.lat - a.lat) * Math.PI / 180;
		var dLng = (b.lng - a.lng) * Math.PI / 180;
		var s = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
			Math.cos(a.lat * Math.PI / 180) * Math.cos(b.lat * Math.PI / 180) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
		return 2 * R * Math.asin(Math.sqrt(s));
	}

	function hasCoords(card) { return isFinite(card.lat) && isFinite(card.lng); }

	function inRegion(card) {
		if (state.region === 'uk') { return card.country === 'GB'; }
		if (card.country === 'GB') { return false; }
		return !state.country || card.country === state.country;
	}

	function matchesFilters(card) {
		if (state.product && card.products.indexOf(state.product) === -1) { return false; }
		if (!state.product && state.brand && card.brands.indexOf(state.brand) === -1) { return false; }
		return true;
	}

	function apply() {
		var q = state.q.toLowerCase();
		var pool = cards.filter(function (c) { return inRegion(c) && matchesFilters(c); });
		var visible = q ? pool.filter(function (c) { return c.search.indexOf(q) !== -1; }) : pool;
		var noteText = '';

		if (state.origin) {
			pool.forEach(function (c) {
				c.distance = hasCoords(c) ? haversine(state.origin, { lat: c.lat, lng: c.lng }) : null;
			});
			// A place name that matches no store still deserves an answer.
			if (q && !visible.length) {
				visible = pool;
				noteText = sprintf(strings.nearest || 'Showing stockists nearest to %s', state.originLabel || state.q);
			}
		} else {
			pool.forEach(function (c) { c.distance = null; });
		}

		var sortBy = state.sort === 'nearest' && state.origin ? 'nearest' : 'az';
		visible.sort(function (a, b) {
			if (sortBy === 'nearest') {
				var da = a.distance === null ? Infinity : a.distance;
				var db = b.distance === null ? Infinity : b.distance;
				if (da !== db) { return da - db; }
			}
			return a.name.localeCompare(b.name);
		});

		cards.forEach(function (c) { c.el.hidden = true; c.el.classList.remove('is-active'); });
		visible.forEach(function (c) { c.el.hidden = false; list.appendChild(c.el); });

		if (ui.count) {
			ui.count.textContent = visible.length === 1 ? (strings.one || '1 stockist') : sprintf(strings.count || '%d stockists', visible.length);
		}
		if (ui.empty) { ui.empty.hidden = visible.length > 0; }
		if (ui.note) { ui.note.textContent = noteText; ui.note.hidden = !noteText; }

		syncUrl();
		syncMarkers(visible);
	}

	function syncUrl() {
		if (!window.history || !window.history.replaceState) { return; }
		var params = new URLSearchParams(window.location.search);
		params.delete('region'); params.delete('product'); params.delete('q'); params.delete('brand');
		if (state.region === 'international') { params.set('region', 'international'); }
		if (state.product) { params.set('product', state.product); } else if (state.brand) { params.set('brand', state.brand); }
		if (state.q) { params.set('q', state.q); }
		var qs = params.toString();
		window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : '') + window.location.hash);
	}

	function setRegion(region) {
		state.region = region;
		ui.regionButtons.forEach(function (btn) {
			var on = btn.getAttribute('data-region') === region;
			btn.classList.toggle('is-active', on);
			btn.setAttribute('aria-pressed', on ? 'true' : 'false');
		});
		if (ui.countryWrap) { ui.countryWrap.classList.toggle('is-hidden', region !== 'international'); }
		if (region !== 'international') { state.country = ''; if (ui.country) { ui.country.value = ''; } }
		apply();
	}

	// -- geocoding (only with a key) ----------------------------------------------

	/**
	 * Keeps the "Nearest first" option honest: disabled whenever there is no
	 * origin to sort by (and always disabled without a key), and drops a
	 * selected 'nearest' sort back to 'az' the moment its origin goes away —
	 * otherwise the select can keep claiming a sort that apply() silently
	 * isn't applying. Call this everywhere state.origin is set or cleared.
	 */
	function setOriginAvailable(available) {
		if (!ui.sort) { return; }
		var opt = ui.sort.querySelector('option[value="nearest"]');
		if (opt) { opt.disabled = !settings.hasKey || !available; }
		if (!available && state.sort === 'nearest') {
			state.sort = 'az';
			ui.sort.value = 'az';
		}
	}

	function geocode(query) {
		// The proxy 400s anything under 2 chars; skip the round trip.
		if (!settings.hasKey || !settings.geocodeUrl || !window.fetch || String(query).trim().length < 2) { return; }
		var seq = ++geocodeSeq;
		var url = settings.geocodeUrl + (settings.geocodeUrl.indexOf('?') === -1 ? '?' : '&') +
			'q=' + encodeURIComponent(query) + '&region=' + (state.region === 'uk' ? 'gb' : '');
		// Note: a 429 (rate limited) response's Retry-After is not read here; the
		// next attempt is simply gated by the normal input debounce below.
		fetch(url, { credentials: 'same-origin' })
			.then(function (r) { return r.ok ? r.json() : null; })
			.then(function (data) {
				if (seq !== geocodeSeq) { return; }
				if (data && typeof data.lat === 'number') {
					state.origin = { lat: data.lat, lng: data.lng };
					state.originLabel = data.label || query;
					setOriginAvailable(true);
					state.sort = 'nearest';
					if (ui.sort) { ui.sort.value = 'nearest'; }
				} else {
					state.origin = null;
					setOriginAvailable(false);
				}
				apply();
			})
			.catch(function () {
				if (seq !== geocodeSeq) { return; }
				state.origin = null;
				setOriginAvailable(false);
				apply();
			});
	}

	// -- map --------------------------------------------------------------------------
	function syncMarkers(visible) {
		if (!map || !window.google) { return; }
		var bounds = new google.maps.LatLngBounds();
		var any = false;
		cards.forEach(function (c) {
			if (!c.marker) { return; }
			var show = visible.indexOf(c) !== -1;
			c.marker.setMap(show ? map : null);
			if (show) { bounds.extend(c.marker.getPosition()); any = true; }
		});
		if (any) {
			map.fitBounds(bounds, 60);
			if (visible.length === 1) { map.setZoom(Math.min(map.getZoom(), 12)); }
		}
	}

	function showOnMap(card) {
		if (!map || !card.marker) { return; }
		map.panTo(card.marker.getPosition());
		map.setZoom(Math.max(map.getZoom(), 12));
		if (info) { info.close(); }
		info = new google.maps.InfoWindow({ content: '<strong>' + card.name.replace(/</g, '&lt;') + '</strong>' });
		info.open({ map: map, anchor: card.marker });
		cards.forEach(function (c) { c.el.classList.toggle('is-active', c === card); });
		if (window.innerWidth < 1024 && ui.canvas) { ui.canvas.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
	}

	window.gtStockistsMapReady = function () {
		if (!ui.canvas || !window.google || !google.maps) { return; }
		map = new google.maps.Map(ui.canvas, {
			center: { lat: 54.5, lng: -3 },
			zoom: 6,
			disableDefaultUI: true,
			gestureHandling: 'cooperative',
			styles: [
				{ elementType: 'all', stylers: [{ saturation: -100 }, { lightness: 15 }] },
				{ featureType: 'poi', stylers: [{ visibility: 'off' }] },
				{ featureType: 'transit', stylers: [{ visibility: 'off' }] },
				{ featureType: 'road', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] }
			]
		});
		cards.forEach(function (c) {
			if (!hasCoords(c)) { return; }
			c.marker = new google.maps.Marker({
				position: { lat: c.lat, lng: c.lng },
				title: c.name,
				icon: { url: settings.pin, scaledSize: new google.maps.Size(18, 24.75), anchor: new google.maps.Point(9, 24.75) }
			});
			c.marker.addListener('click', function () { showOnMap(c); c.el.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); });
		});
		apply();
	};

	// -- events ---------------------------------------------------------------------
	root.addEventListener('click', function (event) {
		// Scoped to the toggle buttons: root itself also carries data-region (its
		// initial-state attribute), so a bare "[data-region]" closest() would match
		// root for every click anywhere inside it and swallow all other delegation.
		var region = event.target.closest('[data-stockists-region] [data-region]');
		if (region) { setRegion(region.getAttribute('data-region')); return; }

		var more = event.target.closest('[data-card-more]');
		if (more) {
			var card = more.closest('.stockists-card');
			var expanded = card.classList.toggle('is-expanded');
			more.setAttribute('aria-expanded', expanded ? 'true' : 'false');
			more.textContent = expanded ? (strings.showLess || 'Show less') : sprintf(strings.more || '+%d more', more.getAttribute('data-count'));
			return;
		}

		var zoom = event.target.closest('[data-map-zoom]');
		if (zoom && map) { map.setZoom(map.getZoom() + (zoom.getAttribute('data-map-zoom') === 'in' ? 1 : -1)); return; }

		var show = event.target.closest('[data-card-map]');
		if (show) {
			var el = show.closest('.stockists-card');
			var target = cards.filter(function (c) { return c.el === el; })[0];
			if (target) { showOnMap(target); }
		}
	});

	if (ui.country) { ui.country.addEventListener('change', function () { state.country = ui.country.value; apply(); }); }
	if (ui.product) {
		ui.product.addEventListener('change', function () {
			state.product = ui.product.value;
			state.brand = ''; // An explicit product choice replaces the brand preselect.
			apply();
		});
	}
	if (ui.sort) { ui.sort.addEventListener('change', function () { state.sort = ui.sort.value; apply(); }); }
	if (ui.search) {
		ui.search.addEventListener('input', function () {
			state.q = ui.search.value.trim();
			clearTimeout(geocodeTimer);
			if (!state.q) {
				state.origin = null;
				setOriginAvailable(false);
			}
			apply();
			if (state.q && settings.hasKey) { geocodeTimer = setTimeout(function () { geocode(state.q); }, 450); }
		});
		ui.search.closest('form') && ui.search.closest('form').addEventListener('submit', function (e) { e.preventDefault(); });
	}

	apply();
	if (state.q && settings.hasKey) { geocode(state.q); }
})();
