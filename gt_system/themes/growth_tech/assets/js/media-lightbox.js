/**
 * Shared enlarge-image lightbox.
 *
 * Any block can add a `[data-media-zoom]` button carrying `data-src` (and
 * optionally `data-title`); the first one on the page builds the dialog and
 * every button afterwards reuses it. The page is complete without this
 * script — the images are already on it — so this only adds the enlargement.
 */
(function () {
	'use strict';

	var dialog = null;
	var stage = null;
	var lastFocus = null;

	function build() {
		dialog = document.createElement('div');
		dialog.className = 'media-lightbox';
		dialog.setAttribute('role', 'dialog');
		dialog.setAttribute('aria-modal', 'true');
		dialog.hidden = true;

		var close = document.createElement('button');
		close.type = 'button';
		close.className = 'media-lightbox__close';
		close.setAttribute('aria-label', 'Close');
		close.innerHTML = '<svg viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false">' +
			'<path fill="currentColor" d="M15.7 4.3a1 1 0 0 0-1.4 0L10 8.6 5.7 4.3a1 1 0 1 0-1.4 1.4L8.6 10l-4.3 4.3a1 1 0 1 0 1.4 1.4L10 11.4l4.3 4.3a1 1 0 0 0 1.4-1.4L11.4 10l4.3-4.3a1 1 0 0 0 0-1.4Z"/></svg>';
		close.addEventListener('click', hide);

		stage = document.createElement('div');
		stage.className = 'media-lightbox__stage';

		dialog.appendChild(close);
		dialog.appendChild(stage);
		dialog.addEventListener('click', function (event) {
			if (event.target === dialog) { hide(); }
		});
		document.body.appendChild(dialog);
	}

	function show(button) {
		if (!dialog) { build(); }

		var src = button.getAttribute('data-src');
		if (!src) { return; }
		var title = button.getAttribute('data-title') || '';

		var img = document.createElement('img');
		img.className = 'media-lightbox__img';
		img.src = src;
		img.alt = title;

		stage.innerHTML = '';
		stage.appendChild(img);
		dialog.setAttribute('aria-label', title || 'Image');
		dialog.hidden = false;
		document.body.classList.add('has-media-lightbox');

		lastFocus = button;
		dialog.querySelector('.media-lightbox__close').focus();
	}

	function hide() {
		if (!dialog || dialog.hidden) { return; }
		dialog.hidden = true;
		// Emptying the stage drops the full-size image from memory again.
		stage.innerHTML = '';
		document.body.classList.remove('has-media-lightbox');
		if (lastFocus) { lastFocus.focus(); lastFocus = null; }
	}

	document.addEventListener('click', function (event) {
		var button = event.target.closest('[data-media-zoom]');
		if (button) {
			event.preventDefault();
			show(button);
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') { hide(); }
	});
}());
