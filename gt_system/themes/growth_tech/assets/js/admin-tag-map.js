/**
 * Feature Band — drag-to-position tags.
 *
 * Adds a small preview of the background image above the Tags repeater. Each
 * repeater row gets a draggable chip; dropping it writes the position back into
 * that row's Across/Down number fields.
 *
 * The number fields remain the source of truth — this only drives them — so
 * typing exact values still works, and nothing new is stored.
 *
 * Editor only. Requires jQuery (ACF's own dependency).
 */
(function ($) {
	'use strict';

	var TAGS_KEY = 'field_gt_fb_tags';
	var IMAGE_KEY = 'field_gt_fb_image';
	var X_KEY = 'field_gt_fb_tag_x';
	var Y_KEY = 'field_gt_fb_tag_y';
	var LABEL_KEY = 'field_gt_fb_tag_label';

	var STEP = 1; // percentage points moved per arrow key press

	function clamp(n) {
		return Math.max(0, Math.min(100, n));
	}

	/**
	 * The rows of a repeater field, ignoring ACF's hidden template row.
	 */
	function rowsOf($field) {
		return $field.find('.acf-row').not('.acf-clone');
	}

	function inputFor($row, key) {
		return $row.find('[data-key="' + key + '"]').find('input').first();
	}

	/**
	 * Write a position back to the row and let ACF know it changed, so the
	 * block re-renders its preview.
	 */
	function setPosition($row, x, y) {
		var $x = inputFor($row, X_KEY);
		var $y = inputFor($row, Y_KEY);

		if ($x.length) {
			$x.val(Math.round(clamp(x) * 10) / 10).trigger('change');
		}

		if ($y.length) {
			$y.val(Math.round(clamp(y) * 10) / 10).trigger('change');
		}
	}

	/**
	 * Source image for the map, taken from the block's own background field.
	 */
	function backgroundSrc($wrap) {
		// Scope to this block's own field container so two Feature Bands on one
		// page cannot read each other's image.
		var $field = $wrap
			.closest('.acf-fields, .acf-block-fields, form, body')
			.find('[data-key="' + IMAGE_KEY + '"]')
			.first();

		if (!$field.length) {
			return '';
		}

		// ACF leaves the old <img> in the DOM after an image is removed, so the
		// hidden input — not the img — is what says whether there is a value.
		var value = $field.find('.acf-image-uploader input[type="hidden"]').first().val();

		if (!value) {
			return '';
		}

		var $img = $field.find('.acf-image-uploader img').first();

		return $img.length ? $img.attr('src') : '';
	}

	function buildMap($field) {
		var $input = $field.children('.acf-input');

		if (!$input.length) {
			return;
		}

		var $map = $input.find('.gt-tag-map');

		if (!$map.length) {
			$map = $(
				'<div class="gt-tag-map">' +
					'<p class="gt-tag-map__hint">Drag a tag to place it, or use the arrow keys once it has focus. The Across and Down values below update as you move.</p>' +
					'<div class="gt-tag-map__stage"><img class="gt-tag-map__img" alt="" /><div class="gt-tag-map__empty">Choose a background image to position tags against.</div></div>' +
				'</div>'
			);
			$input.prepend($map);
		}

		var $stage = $map.find('.gt-tag-map__stage');
		var src = backgroundSrc($field);

		$map.find('.gt-tag-map__img').attr('src', src).toggle(!!src);
		$map.find('.gt-tag-map__empty').toggle(!src);

		// Rebuild the chips to match the current rows.
		$stage.find('.gt-tag-map__chip').remove();

		rowsOf($field).each(function (i) {
			var $row = $(this);
			var label = inputFor($row, LABEL_KEY).val() || 'Tag ' + (i + 1);
			var x = parseFloat(inputFor($row, X_KEY).val());
			var y = parseFloat(inputFor($row, Y_KEY).val());

			if (isNaN(x)) { x = 60; }
			if (isNaN(y)) { y = 40; }

			var $chip = $('<button type="button" class="gt-tag-map__chip"></button>')
				.text(label)
				.attr('aria-label', 'Position for ' + label)
				.css({ left: x + '%', top: y + '%' })
				.data('row', $row);

			$stage.append($chip);
		});
	}

	/**
	 * Pointer dragging. Uses pointer events so mouse, pen and touch all work.
	 */
	function startDrag(event) {
		var $chip = $(event.currentTarget);
		var $stage = $chip.parent();
		var $row = $chip.data('row');
		var stage = $stage[0].getBoundingClientRect();
		var chip = $chip[0].getBoundingClientRect();

		// Keep the grab point under the cursor rather than snapping the corner.
		var grabX = event.clientX - chip.left;
		var grabY = event.clientY - chip.top;

		$chip.addClass('is-dragging');
		event.preventDefault();

		// Not critical — dragging still works through the document listeners.
		try {
			event.currentTarget.setPointerCapture(event.pointerId);
		} catch (e) {}

		function move(e) {
			var x = ((e.clientX - grabX - stage.left) / stage.width) * 100;
			var y = ((e.clientY - grabY - stage.top) / stage.height) * 100;

			$chip.css({ left: clamp(x) + '%', top: clamp(y) + '%' });
		}

		function end(e) {
			var x = ((e.clientX - grabX - stage.left) / stage.width) * 100;
			var y = ((e.clientY - grabY - stage.top) / stage.height) * 100;

			setPosition($row, x, y);

			$chip.removeClass('is-dragging');
			document.removeEventListener('pointermove', move);
			document.removeEventListener('pointerup', end);
		}

		document.addEventListener('pointermove', move);
		document.addEventListener('pointerup', end);
	}

	/**
	 * Arrow keys nudge a focused chip, so the map is not mouse-only.
	 */
	function onKey(event) {
		var moves = {
			ArrowLeft: [-STEP, 0],
			ArrowRight: [STEP, 0],
			ArrowUp: [0, -STEP],
			ArrowDown: [0, STEP]
		};

		var delta = moves[event.key];

		if (!delta) {
			return;
		}

		event.preventDefault();

		var $chip = $(event.currentTarget);
		var $row = $chip.data('row');
		var x = parseFloat($chip[0].style.left) || 0;
		var y = parseFloat($chip[0].style.top) || 0;

		x = clamp(x + delta[0]);
		y = clamp(y + delta[1]);

		$chip.css({ left: x + '%', top: y + '%' });
		setPosition($row, x, y);
	}

	function refreshAll() {
		$('.acf-field[data-key="' + TAGS_KEY + '"]').each(function () {
			buildMap($(this));
		});
	}

	var pending = null;

	function refreshSoon() {
		window.clearTimeout(pending);
		pending = window.setTimeout(refreshAll, 120);
	}

	$(document).on('pointerdown', '.gt-tag-map__chip', startDrag);
	$(document).on('keydown', '.gt-tag-map__chip', onKey);

	// Typing a value, adding/removing/reordering rows, or swapping the image
	// should all be reflected on the map.
	$(document).on('change', '[data-key="' + X_KEY + '"] input, [data-key="' + Y_KEY + '"] input', refreshSoon);
	$(document).on('input', '[data-key="' + LABEL_KEY + '"] input', refreshSoon);

	// Selecting or removing an image updates the field's hidden input.
	$(document).on('change', '[data-key="' + IMAGE_KEY + '"] input', refreshSoon);

	// ACF swaps the whole image markup rather than editing the existing <img>,
	// and the media modal closes asynchronously, so watch the DOM as well.
	// Without this the map keeps showing the previous background.
	if (window.MutationObserver) {
		var observer = new MutationObserver(function (records) {
			for (var i = 0; i < records.length; i++) {
				var target = records[i].target;

				if (target && target.closest && target.closest('[data-key="' + IMAGE_KEY + '"]')) {
					refreshSoon();
					return;
				}
			}
		});

		observer.observe(document.body, {
			childList: true,
			subtree: true,
			attributes: true,
			attributeFilter: ['src', 'value', 'class']
		});
	}

	if (window.acf) {
		acf.addAction('ready', refreshSoon);
		acf.addAction('append', refreshSoon);
		acf.addAction('remove', refreshSoon);
		acf.addAction('sortstop', refreshSoon);
		acf.addAction('change', refreshSoon);
	}

	$(window).on('load', refreshSoon);
})(jQuery);
