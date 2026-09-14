<?php
require_once __DIR__ . '/lib/bootstrap.php';

// -- video URL parsing ---------------------------------------------------------
$cases = array(
	'https://www.youtube.com/watch?v=dQw4w9WgXcQ'            => array( 'youtube', 'dQw4w9WgXcQ' ),
	'https://youtu.be/dQw4w9WgXcQ?t=10'                      => array( 'youtube', 'dQw4w9WgXcQ' ),
	'https://www.youtube.com/embed/dQw4w9WgXcQ'              => array( 'youtube', 'dQw4w9WgXcQ' ),
	'https://www.youtube.com/shorts/dQw4w9WgXcQ'             => array( 'youtube', 'dQw4w9WgXcQ' ),
	'https://vimeo.com/76979871'                             => array( 'vimeo', '76979871' ),
	'https://player.vimeo.com/video/76979871?h=abc'          => array( 'vimeo', '76979871' ),
	'https://vimeo.com/channels/staffpicks/76979871'         => array( 'vimeo', '76979871' ),
);
foreach ( $cases as $url => $expect ) {
	$embed = gt_video_embed( $url );
	gt_assert_equal( $expect[0], $embed ? $embed['provider'] : null, "provider for {$url}" );
	gt_assert_equal( $expect[1], $embed ? $embed['id'] : null, "id for {$url}" );
}
gt_assert_contains( 'youtube-nocookie.com/embed/dQw4w9WgXcQ', gt_video_embed( 'https://youtu.be/dQw4w9WgXcQ' )['src'], 'youtube embeds via the no-cookie host' );
gt_assert_contains( 'player.vimeo.com/video/76979871?', gt_video_embed( 'https://vimeo.com/76979871' )['src'], 'vimeo embeds via the player host' );
gt_assert_contains( 'dnt=1', gt_video_embed( 'https://vimeo.com/76979871' )['src'], 'vimeo embed asks for do-not-track' );
gt_assert_equal( null, gt_video_embed( 'https://example.com/watch?v=abc' ), 'unknown hosts are rejected' );
gt_assert_equal( null, gt_video_embed( 'not a url' ), 'garbage is rejected' );
gt_assert_equal( null, gt_video_embed( 'javascript:alert(1)' ), 'javascript: is rejected' );

// -- rendered tab --------------------------------------------------------------
$html = gt_fetch( '/product/clonex-mist/' );
$at   = strpos( $html, 'data-tab-panel="how-to-use"' );
gt_assert( false !== $at, 'how-to-use panel present' );
$panel = substr( $html, $at, strpos( $html, '</section>', $at ) - $at );

gt_assert_contains( 'class="product-howto"', $panel, 'steps grid rendered' );
gt_assert_contains( 'do not dilute', $panel, 'existing rich text kept as the intro' );
gt_assert_equal( 4, substr_count( $panel, '<li class="product-howto__step"' ), 'four steps' );
gt_assert_contains( '<span class="product-howto__eyebrow">Step 1</span>', $panel, 'auto-numbered eyebrow' );
gt_assert_contains( '<span class="product-howto__eyebrow">Step 4</span>', $panel, 'numbering continues' );
gt_assert_contains( '<h3 class="product-howto__title">Choose a suitable pot</h3>', $panel, 'step title' );
gt_assert_contains( 'class="product-howto__text"', $panel, 'step text' );
gt_assert_contains( '<a href="https://growth-tech.local/product/ionic-hydro-grow/">Ionic Hydro Grow</a>', $panel, 'links survive in step text' );

// Media buttons: one per step, typed so the JS knows what to open.
gt_assert_equal( 4, substr_count( $panel, 'data-howto-open' ), 'a media button per step' );
gt_assert_contains( 'data-media="image"', $panel, 'image-only step' );
gt_assert_contains( 'data-media="youtube" data-src="https://www.youtube-nocookie.com/embed/', $panel, 'youtube step carries its embed src' );
gt_assert_contains( 'data-media="vimeo" data-src="https://player.vimeo.com/video/76979871?', $panel, 'vimeo step carries its embed src' );
gt_assert_contains( 'data-media="file" data-src="', $panel, 'uploaded video step carries its file url' );
gt_assert_contains( '.mp4"', $panel, 'file url is the mp4' );
gt_assert_equal( 1, substr_count( $panel, 'class="product-howto__badge product-howto__badge--zoom"' ), 'zoom badge on the image-only step' );
gt_assert_equal( 3, substr_count( $panel, 'class="product-howto__badge product-howto__badge--play"' ), 'play badge on the three video steps' );
gt_assert_contains( 'aria-label="Enlarge: Choose a suitable pot"', $panel, 'image button labelled' );
gt_assert_contains( 'aria-label="Play video: Dampen the medium"', $panel, 'video button labelled' );
gt_assert_not_contains( '<iframe', $panel, 'no third-party iframes until a video is opened' );
gt_assert_contains( 'data-full="', $panel, 'image step carries the full-size image for the lightbox' );

// Lightbox shell + script.
gt_assert_contains( 'class="product-lightbox product-lightbox--howto" data-howto-lightbox hidden', $html, 'howto lightbox shell present and hidden' );
gt_assert_contains( 'data-howto-stage', $html, 'lightbox stage' );
gt_assert_contains( 'assets/js/product-howto.js', $html, 'howto script enqueued' );

// A product without steps keeps the plain rich-text tab and no script.
$html = gt_fetch( '/product/nitrozyme/' );
gt_assert_not_contains( 'class="product-howto"', $html, 'no grid without steps' );
gt_assert_not_contains( 'assets/js/product-howto.js', $html, 'script only where there are steps' );
gt_assert_not_contains( 'data-howto-lightbox', $html, 'no lightbox shell without steps' );

gt_test_done();
