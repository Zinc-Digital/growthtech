/**
 * "How to use" steps — opens each step's media in the shared lightbox.
 *
 * The page is complete without this script (the steps and their images are
 * server-rendered); it only adds the enlarge/play behaviour. A YouTube or
 * Vimeo iframe is created when its step is opened and removed on close, so
 * nothing third-party loads until someone asks for it and playback always
 * stops with the lightbox.
 */
(function () {
	'use strict';

	var lightbox = document.querySelector('[data-howto-lightbox]');
	var stage = lightbox ? lightbox.querySelector('[data-howto-stage]') : null;
	var closeBtn = lightbox ? lightbox.querySelector('[data-howto-close]') : null;
	if (!lightbox || !stage || !closeBtn) { return; }

	var lastFocus = null;

	function build(button) {
		var media = button.getAttribute('data-media');
		var src = button.getAttribute('data-src') || '';
		var title = button.getAttribute('data-title') || '';
		var el;

		if (media === 'file' && src) {
			el = document.createElement('video');
			el.className = 'product-lightbox__video';
			el.src = src;
			el.controls = true;
			el.autoplay = true;
			el.playsInline = true;
			el.preload = 'metadata';
			var poster = button.getAttribute('data-full');
			if (poster) { el.poster = poster; }
			return el;
		}

		if ((media === 'youtube' || media === 'vimeo') && src) {
			el = document.createElement('iframe');
			el.className = 'product-lightbox__frame';
			el.src = src;
			el.title = title;
			el.allow = 'autoplay; fullscreen; picture-in-picture; encrypted-media';
			el.referrerPolicy = 'strict-origin-when-cross-origin';
			return el;
		}

		el = document.createElement('img');
		el.className = 'product-lightbox__img';
		el.src = button.getAttribute('data-full') || (button.parentNode.querySelector('img') || {}).currentSrc || '';
		el.alt = title;
		return el;
	}

	function open(button) {
		lastFocus = button;
		stage.innerHTML = '';
		stage.appendChild(build(button));
		lightbox.hidden = false;
		document.body.classList.add('product-lightbox-open');
		closeBtn.focus();
	}

	function close() {
		if (lightbox.hidden) { return; }
		// Emptying the stage stops <video> and tears down any embed.
		stage.innerHTML = '';
		lightbox.hidden = true;
		document.body.classList.remove('product-lightbox-open');
		if (lastFocus) { lastFocus.focus({ preventScroll: true }); }
	}

	document.addEventListener('click', function (event) {
		var button = event.target.closest('[data-howto-open]');
		if (button) { event.preventDefault(); open(button); return; }
		if (event.target.closest('[data-howto-close]')) { close(); return; }
		// A click on the dark surround (not on the media) closes too.
		if (event.target === lightbox) { close(); }
	});

	document.addEventListener('keydown', function (event) {
		if (lightbox.hidden) { return; }
		if (event.key === 'Escape') { close(); return; }
		// Close is the only focusable control of ours; the embed's own controls
		// live inside the iframe. Keep Tab cycling within the dialog.
		if (event.key === 'Tab') {
			var inside = lightbox.contains(document.activeElement);
			var frame = stage.querySelector('iframe, video');
			var stops = frame ? [closeBtn, frame] : [closeBtn];
			var first = stops[0];
			var last = stops[stops.length - 1];
			if (!inside || (event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last)) {
				event.preventDefault();
				(event.shiftKey ? last : first).focus();
			}
		}
	});
})();
