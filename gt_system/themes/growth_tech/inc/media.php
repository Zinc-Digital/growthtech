<?php
/**
 * Media helpers shared by the blocks that offer a video in place of an image.
 *
 * The link parsing itself already lives in gt_video_embed() (inc/woocommerce-
 * helpers.php), written for the product page's "How to use" steps. This only
 * resolves a repeater row into something the lightbox can open, using the same
 * field shape those steps use: `media` of image|file|youtube|vimeo.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve a row's media fields into something the lightbox can play.
 *
 * A video that cannot be resolved — a missing file, an unparseable link, a
 * link to the other provider — returns null, so the row falls back to opening
 * its image rather than a broken player.
 *
 * @param array $row Fields carrying media, video_file and video_url.
 * @return array{kind: string, src: string}|null Null when the row is an image.
 */
function gt_media_row_video( array $row ) {
	$media = ! empty( $row['media'] ) ? (string) $row['media'] : 'image';

	if ( 'image' === $media ) {
		return null;
	}

	if ( 'file' === $media ) {
		$file = $row['video_file'] ?? 0;
		$file = is_array( $file ) ? (int) ( $file['ID'] ?? 0 ) : (int) $file;
		$url  = $file ? wp_get_attachment_url( $file ) : '';

		return $url ? array( 'kind' => 'file', 'src' => $url ) : null;
	}

	$embed = gt_video_embed( (string) ( $row['video_url'] ?? '' ) );

	return $embed && $embed['provider'] === $media
		? array( 'kind' => $media, 'src' => $embed['src'] )
		: null;
}

/**
 * The attributes a shared-lightbox trigger needs, ready to print.
 *
 * @param array|null $video   The result of gt_media_row_video().
 * @param string     $full    The full-size image URL, used when there is no video.
 * @param string     $caption The row's title, used for the label.
 * @return string
 */
function gt_lightbox_attrs( $video, $full, $caption = '' ) {
	if ( $video ) {
		return sprintf(
			' data-media-zoom data-media-type="video" data-video-kind="%s" data-video-src="%s" data-title="%s"',
			esc_attr( $video['kind'] ),
			esc_url( $video['src'] ),
			esc_attr( $caption )
		);
	}

	return sprintf(
		' data-media-zoom data-src="%s" data-title="%s"',
		esc_url( $full ),
		esc_attr( $caption )
	);
}
