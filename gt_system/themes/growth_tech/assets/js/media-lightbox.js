/**
 * Shared enlarge-image and play-video lightbox.
 *
 * Any block can add a `[data-media-zoom]` button; the first one on the page
 * builds the dialog and every button afterwards reuses it.
 *
 *   image   data-src, optionally data-title
 *   video   data-media-type="video", data-video-kind="file|youtube|vimeo"
 *           and data-video-src — a ready embed URL, worked out in PHP so this
 *           script never has to parse a pasted link.
 *
 * The page is complete without this script — the stills are already on it — so
 * this only adds the enlargement and playback.
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

	/** An uploaded file plays natively; YouTube and Vimeo go in an iframe. */
	function buildVideo(kind, src, title) {
		if (kind === 'file') {
			var video = document.createElement('video');
			video.className = 'media-lightbox__video';
			video.src = src;
			video.controls = true;
			video.autoplay = true;
			video.playsInline = true;
			return video;
		}

		var wrap = document.createElement('div');
		wrap.className = 'media-lightbox__player';

		var frame = document.createElement('iframe');
		frame.src = src;
		frame.title = title || 'Video';
		frame.allow = 'autoplay; fullscreen; picture-in-picture; encrypted-media';
		frame.allowFullscreen = true;
		frame.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');

		wrap.appendChild(frame);
		return wrap;
	}

	function show(button) {
		if (!dialog) { build(); }

		var title = button.getAttribute('data-title') || '';
		var isVideo = button.getAttribute('data-media-type') === 'video';
		var node;

		if (isVideo) {
			var videoSrc = button.getAttribute('data-video-src');
			if (!videoSrc) { return; }
			node = buildVideo(button.getAttribute('data-video-kind'), videoSrc, title);
		} else {
			var src = button.getAttribute('data-src');
			if (!src) { return; }
			node = document.createElement('img');
			node.className = 'media-lightbox__img';
			node.src = src;
			node.alt = title;
		}

		stage.innerHTML = '';
		stage.appendChild(node);
		dialog.setAttribute('aria-label', title || (isVideo ? 'Video' : 'Image'));
		dialog.hidden = false;
		document.body.classList.add('has-media-lightbox');

		lastFocus = button;
		dialog.querySelector('.media-lightbox__close').focus();
	}

	function hide() {
		if (!dialog || dialog.hidden) { return; }
		dialog.hidden = true;
		// Emptying the stage drops the full-size image from memory again — and
		// for a video it is what actually stops playback, iframe included.
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
