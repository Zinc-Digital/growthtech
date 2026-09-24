/**
 * Guide Hotspots — drag-to-position pins.
 *
 * Draws a preview of each photo above its Pinned guides repeater and gives
 * every row a draggable chip; dropping it writes the position back into that
 * row's Across/Down number fields.
 *
 * Unlike the Feature Band's map, the photo and the pins live inside the same
 * row of an outer Photos repeater, so everything is scoped to that row rather
 * than to the page. The chips carry their own class too: both maps share the
 * frame's styling, but a chip must only answer to the script that built it.
 *
 * The number fields stay the source of truth — this only drives them — so
 * typing exact values still works and nothing extra is stored.
 *
 * Editor only. Requires jQuery (ACF's own dependency).
 */
(function ($) {
	'use strict';

	var PHOTOS_KEY = 'field_gt_hs_photos';
	var IMAGE_KEY = 'field_gt_hs_image';
	var PINS_KEY = 'field_gt_hs_pins';
	var GUIDE_KEY = 'field_gt_hs_pin_guide';
	var LABEL_KEY = 'field_gt_hs_pin_label';
	var X_KEY = 'field_gt_hs_pin_x';
	var Y_KEY = 'field_gt_hs_pin_y';

	var STEP = 1; // percentage points moved per arrow key press

	// Rebuilding empties the stage and recreates every chip, which would tear
	// the dragged one out from under the pointer. Held while a drag is live.
	var dragging = false;

	function clamp(n) {
		return Math.max(0, Math.min(100, n));
	}

	/**
	 * The rows of a repeater field, ignoring ACF's hidden template row.
	 *
	 * Row and block layouts nest slightly differently, so fall back to any
	 * descendant row belonging to this field rather than a fixed path.
	 */
	function rowsOf($field) {
		var $rows = $field.find('> .acf-input > .acf-repeater > table > tbody > .acf-row').not('.acf-clone');

		if (!$rows.length) {
			$rows = $field.find('> .acf-input .acf-row').not('.acf-clone');
		}

		return $rows;
	}

	function inputFor($row, key) {
		return $row.find('[data-key="' + key + '"]').find('input, select, textarea').first();
	}

	// True while this script is the one writing the number fields, so the
	// change listener below can tell an editor typing from our own drag.
	var writing = false;

	function setPosition($row, x, y) {
		var $x = inputFor($row, X_KEY);
		var $y = inputFor($row, Y_KEY);

		writing = true;
		if ($x.length) {
			$x.val(Math.round(x * 10) / 10).trigger('change');
		}
		if ($y.length) {
			$y.val(Math.round(y * 10) / 10).trigger('change');
		}
		writing = false;
	}

	function positionOf($row) {
		return {
			x: clamp(parseFloat(inputFor($row, X_KEY).val()) || 50),
			y: clamp(parseFloat(inputFor($row, Y_KEY).val()) || 50)
		};
	}

	/** What the chip should read: the typed label, else the chosen guide. */
	function labelOf($row) {
		var typed = $.trim(inputFor($row, LABEL_KEY).val() || '');
		if (typed) {
			return typed;
		}
		var $guide = $row.find('[data-key="' + GUIDE_KEY + '"]').find('.acf-selection, option:selected').first();
		return $.trim($guide.text() || '') || 'Pin';
	}

	/** The photo chosen in this row of the outer repeater, if any. */
	function backgroundSrc($photoRow) {
		var $img = $photoRow
			.find('[data-key="' + IMAGE_KEY + '"]')
			.find('.acf-image-uploader img')
			.first();
		return $img.length ? $img.attr('src') : '';
	}

	/**
	 * Build (or rebuild) the map above one photo row's pins repeater.
	 */
	function buildMap($photoRow) {
		var $pins = $photoRow.find('.acf-field[data-key="' + PINS_KEY + '"]').first();
		if (!$pins.length) {
			return;
		}

		var $input = $pins.children('.acf-input');
		var $map = $input.children('.gt-tag-map');

		if (!$map.length) {
			$map = $(
				'<div class="gt-tag-map gt-hotspot-map">' +
					'<p class="gt-tag-map__hint">Drag each pin onto the photo to place it. Selected pins can also be nudged with the arrow keys.</p>' +
					'<div class="gt-tag-map__stage"></div>' +
				'</div>'
			);
			$input.prepend($map);
		}

		var $stage = $map.find('.gt-tag-map__stage');
		var src = backgroundSrc($photoRow);

		$stage.empty();
		if (src) {
			$stage.append($('<img class="gt-tag-map__img" alt="" />').attr('src', src));
		} else {
			$stage.append('<span class="gt-tag-map__empty">Choose a photo above to place pins on it.</span>');
		}

		rowsOf($pins).each(function () {
			var $row = $(this);
			var pos = positionOf($row);
			var $chip = $('<button type="button" class="gt-hotspot-map__chip"></button>')
				.text(labelOf($row))
				.css({ left: pos.x + '%', top: pos.y + '%' })
				.data('gtRow', $row);
			$stage.append($chip);
		});
	}

	/**
	 * Drag a chip across its stage.
	 *
	 * The position is written back once, on release. Writing it on every move
	 * fires `change` on the number fields, which schedules a rebuild — and a
	 * rebuild replaces the very chip being dragged, so it appears to stick and
	 * never let go. During the drag only the chip's CSS moves.
	 */
	function startDrag(event) {
		var $chip = $(event.currentTarget);
		var $stage = $chip.closest('.gt-tag-map__stage');
		var $row = $chip.data('gtRow');

		if (!$row || !$stage.length) {
			return;
		}

		var stage = $stage[0].getBoundingClientRect();
		var chip = $chip[0].getBoundingClientRect();

		// Keep the grab point under the cursor rather than snapping the corner.
		var grabX = event.clientX - chip.left;
		var grabY = event.clientY - chip.top;

		event.preventDefault();
		dragging = true;
		$chip.addClass('is-dragging');

		// Not critical — the document listeners below still carry the drag.
		try {
			event.currentTarget.setPointerCapture(event.pointerId);
		} catch (e) {}

		function at(e) {
			return {
				x: clamp(((e.clientX - grabX - stage.left) / stage.width) * 100),
				y: clamp(((e.clientY - grabY - stage.top) / stage.height) * 100)
			};
		}

		function move(e) {
			var pos = at(e);
			$chip.css({ left: pos.x + '%', top: pos.y + '%' });
		}

		function end(e) {
			var pos = at(e);

			$chip.removeClass('is-dragging');
			dragging = false;
			document.removeEventListener('pointermove', move);
			document.removeEventListener('pointerup', end);
			document.removeEventListener('pointercancel', cancel);

			setPosition($row, pos.x, pos.y);
		}

		function cancel() {
			$chip.removeClass('is-dragging');
			dragging = false;
			document.removeEventListener('pointermove', move);
			document.removeEventListener('pointerup', end);
			document.removeEventListener('pointercancel', cancel);
		}

		document.addEventListener('pointermove', move);
		document.addEventListener('pointerup', end);
		document.addEventListener('pointercancel', cancel);
	}

	function onKey(event) {
		var keys = { 37: [-STEP, 0], 38: [0, -STEP], 39: [STEP, 0], 40: [0, STEP] };
		var delta = keys[event.which];
		if (!delta) {
			return;
		}

		var $chip = $(event.currentTarget);
		var $row = $chip.data('gtRow');
		if (!$row) {
			return;
		}

		event.preventDefault();
		var pos = positionOf($row);
		var x = clamp(pos.x + delta[0]);
		var y = clamp(pos.y + delta[1]);
		$chip.css({ left: x + '%', top: y + '%' });
		setPosition($row, x, y);

		// Writing the number field hands focus to it, which would end the nudge
		// after a single press. Take it back so the keys keep working.
		$chip.trigger('focus');
	}

	function refreshAll() {
		if (dragging) {
			return;
		}

		$('.acf-field[data-key="' + PHOTOS_KEY + '"]').each(function () {
			rowsOf($(this)).each(function () {
				buildMap($(this));
			});
		});
	}

	var pending = null;
	function refreshSoon() {
		clearTimeout(pending);
		pending = setTimeout(refreshAll, 120);
	}

	$(document).on('pointerdown', '.gt-hotspot-map__chip', startDrag);
	$(document).on('keydown', '.gt-hotspot-map__chip', onKey);

	// Anything that changes what a chip says, or which photo sits behind it.
	$(document).on('input', '[data-key="' + LABEL_KEY + '"] input', refreshSoon);
	$(document).on('change', '[data-key="' + GUIDE_KEY + '"] select, [data-key="' + GUIDE_KEY + '"] input', refreshSoon);
	$(document).on('change', '[data-key="' + X_KEY + '"] input, [data-key="' + Y_KEY + '"] input', function () {
		// Typed by hand — move the chip to match. Written by us — leave it be.
		if (!writing) {
			refreshSoon();
		}
	});
	$(document).on('change', '[data-key="' + IMAGE_KEY + '"] input', refreshSoon);

	if (window.acf) {
		window.acf.addAction('append', refreshSoon);
		window.acf.addAction('remove', refreshSoon);
		window.acf.addAction('ready', refreshSoon);
	}

	$(window).on('load', refreshSoon);
	$(refreshSoon);
})(jQuery);
