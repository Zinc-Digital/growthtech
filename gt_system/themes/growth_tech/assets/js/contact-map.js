/**
 * Contact page map — Figma 384:2873. One pin on a desaturated map, matching
 * the stockist finder's treatment. gtContactMap is printed by inc/contact.php
 * and Google calls gtContactMapReady once its script has loaded.
 */
(function () {
	'use strict';

	var settings = window.gtContactMap || {};
	var canvas = document.querySelector('[data-contact-map]');

	window.gtContactMapReady = function () {
		if (!canvas || !window.google || !google.maps) { return; }

		var position = { lat: settings.lat, lng: settings.lng };
		var map = new google.maps.Map(canvas, {
			center: position,
			zoom: settings.zoom || 14,
			disableDefaultUI: true,
			gestureHandling: 'cooperative',
			styles: [
				{ elementType: 'all', stylers: [{ saturation: -100 }, { lightness: 15 }] },
				{ featureType: 'poi', stylers: [{ visibility: 'off' }] },
				{ featureType: 'transit', stylers: [{ visibility: 'off' }] },
				{ featureType: 'road', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] }
			]
		});

		new google.maps.Marker({
			map: map,
			position: position,
			title: settings.title || '',
			icon: { url: settings.pin, scaledSize: new google.maps.Size(35, 48), anchor: new google.maps.Point(17.5, 48) }
		});

		// The card covers the left of the band, so nudge the centre right of it
		// at desktop to keep the pin clear of the card.
		if (window.matchMedia('(min-width: 1024px)').matches) {
			map.panBy(-180, 0);
		}
	};
}());
