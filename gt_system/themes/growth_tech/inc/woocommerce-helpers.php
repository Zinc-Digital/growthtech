<?php
/**
 * Small product/term readers shared by the shop templates.
 */

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/** ACF term field with a fallback, safe when ACF is off. */
function gt_term_field( $name, WP_Term $term, $default = '' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}
	$value = get_field( $name, $term );
	return ( null === $value || '' === $value || false === $value ) ? $default : $value;
}

/**
 * The category shown on the card. Yoast's primary term wins when set;
 * otherwise the first top-level category alphabetically. Never the
 * WooCommerce default "Uncategorized".
 */
function gt_product_primary_category( WC_Product $product ) {
	$default_id = (int) get_option( 'default_product_cat' );
	$primary_id = (int) get_post_meta( $product->get_id(), '_yoast_wpseo_primary_product_cat', true );

	if ( $primary_id && $primary_id !== $default_id ) {
		$term = get_term( $primary_id, 'product_cat' );
		if ( $term instanceof WP_Term ) {
			return $term;
		}
	}

	$terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'orderby' => 'name', 'order' => 'ASC' ) );
	if ( is_wp_error( $terms ) || ! $terms ) {
		return null;
	}
	$terms = array_filter( $terms, function ( $t ) use ( $default_id ) {
		return (int) $t->term_id !== $default_id;
	} );
	if ( ! $terms ) {
		return null;
	}
	foreach ( $terms as $term ) {
		if ( 0 === (int) $term->parent ) {
			return $term;
		}
	}
	return reset( $terms );
}

/** Size names in the attribute's own order, e.g. ['100ml', '300ml', '750ml']. */
function gt_product_sizes( WC_Product $product ) {
	if ( ! taxonomy_exists( 'pa_size' ) ) {
		return array();
	}
	$names = wc_get_product_terms( $product->get_id(), 'pa_size', array( 'fields' => 'names' ) );
	return is_wp_error( $names ) ? array() : array_values( $names );
}

/** First brand term on the product, or null. */
function gt_product_brand( WC_Product $product ) {
	if ( ! taxonomy_exists( 'product_brand' ) ) {
		return null;
	}
	$terms = wp_get_post_terms( $product->get_id(), 'product_brand' );
	return ( is_wp_error( $terms ) || ! $terms ) ? null : $terms[0];
}

/** Brand accent hex, defaulting to the Clonex yellow used across the designs. */
function gt_brand_accent( WP_Term $brand ) {
	$colour = (string) gt_term_field( 'accent_colour', $brand, '' );
	return preg_match( '/^#[0-9a-fA-F]{6}$/', $colour ) ? strtoupper( $colour ) : '#FBC707';
}

/**
 * Breadcrumb trail for every shop page: Our Products / Category / Brand /
 * Product. Each item is ['label' => string, 'url' => string|null]; the last
 * item is the current page and has no url.
 */
function gt_shop_breadcrumb_items() {
	$shop_id = wc_get_page_id( 'shop' );
	$items   = array( array(
		'label' => $shop_id > 0 ? get_the_title( $shop_id ) : __( 'Our Products', 'gt' ),
		'url'   => wc_get_page_permalink( 'shop' ),
	) );

	$add_category_chain = function ( WP_Term $term, $link_last ) use ( &$items ) {
		$chain = array_reverse( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );
		foreach ( $chain as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'product_cat' );
			if ( $ancestor instanceof WP_Term ) {
				$link    = get_term_link( $ancestor );
				$items[] = array( 'label' => $ancestor->name, 'url' => is_wp_error( $link ) ? null : $link );
			}
		}
		$link    = $link_last ? get_term_link( $term ) : null;
		$items[] = array( 'label' => $term->name, 'url' => ( null === $link || is_wp_error( $link ) ) ? null : $link );
	};

	if ( is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$add_category_chain( $term, false );
		}
	} elseif ( is_tax( 'product_brand' ) ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$items[] = array( 'label' => $term->name, 'url' => null );
		}
	} elseif ( is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product instanceof WC_Product ) {
			$category = gt_product_primary_category( $product );
			if ( $category ) {
				$add_category_chain( $category, true );
			}
			$brand = gt_product_brand( $product );
			if ( $brand ) {
				$link = get_term_link( $brand );
				$items[] = array( 'label' => $brand->name, 'url' => is_wp_error( $link ) ? null : $link );
			}
			$items[] = array( 'label' => $product->get_name(), 'url' => null );
		}
	}

	return $items;
}

/** Stockist page URL from Theme Settings, or '' when none is set. */
function gt_shop_stockist_base_url() {
	$url = function_exists( 'get_field' ) ? (string) get_field( 'shop_stockist_page', 'option' ) : '';
	return $url ? $url : '';
}

/** "Find a local stockist" for a product: the stockist page with the product preselected (UK first). */
function gt_product_stockist_url( WC_Product $product ) {
	$base = gt_shop_stockist_base_url();
	return $base ? add_query_arg( array( 'product' => $product->get_slug(), 'region' => 'uk' ), $base ) : '';
}

/** "Find a stockist" for a brand: the stockist page filtered to that brand. */
function gt_brand_stockist_url( WP_Term $brand ) {
	$base = gt_shop_stockist_base_url();
	return $base ? add_query_arg( array( 'brand' => $brand->slug ), $base ) : '';
}

/**
 * Tiles for the Our Brands page — Figma 385:5006. Growth Technology's own
 * brands first, then the rest, each in WooCommerce's brand order. Brands
 * hidden from the page (Brand > Brands page tile) are skipped. Each tile is
 * ['brand' => WP_Term, 'image' => int, 'logo' => int, 'tagline' => string,
 * 'link_text' => string, 'url' => string].
 *
 * @param bool $own_only Only brands flagged as ours.
 * @return array[]
 */
function gt_brands_page_tiles( $own_only = false ) {
	if ( ! taxonomy_exists( 'product_brand' ) ) {
		return array();
	}
	$brands = get_terms( array(
		'taxonomy'   => 'product_brand',
		'hide_empty' => false,
		// Explicit orderby so WooCommerce's menu_order default for brands in admin-ajax can't rewrite meta_key to "order".
		'orderby'    => 'name',
	) );
	if ( is_wp_error( $brands ) || ! $brands ) {
		return array();
	}

	// WooCommerce stores the drag-and-drop brand order as "order" term meta;
	// fall back to alphabetical for brands that have never been reordered.
	$position = function ( WP_Term $brand ) {
		$order = get_term_meta( $brand->term_id, 'order', true );
		return '' === $order ? PHP_INT_MAX : (int) $order;
	};
	$is_own = function ( WP_Term $brand ) {
		return (bool) gt_term_field( 'own_brand', $brand, false );
	};
	usort( $brands, function ( WP_Term $a, WP_Term $b ) use ( $position, $is_own ) {
		$own = (int) $is_own( $b ) - (int) $is_own( $a );
		if ( 0 !== $own ) {
			return $own;
		}
		$pos = $position( $a ) - $position( $b );
		return 0 !== $pos ? $pos : strcasecmp( $a->name, $b->name );
	} );

	$tiles = array();
	foreach ( $brands as $brand ) {
		if ( gt_term_field( 'tile_hidden', $brand, false ) || ( $own_only && ! $is_own( $brand ) ) ) {
			continue;
		}
		$url = get_term_link( $brand );
		if ( is_wp_error( $url ) ) {
			continue;
		}
		$image = (int) gt_term_field( 'tile_image', $brand, 0 );
		if ( ! $image ) {
			$image = (int) gt_term_field( 'promo_image', $brand, 0 );
		}
		if ( ! $image ) {
			$image = (int) gt_term_field( 'hero_image', $brand, 0 );
		}
		$logo = (int) gt_term_field( 'tile_logo', $brand, 0 );
		if ( ! $logo ) {
			$logo = (int) gt_term_field( 'logo', $brand, 0 );
		}
		$tagline = (string) gt_term_field( 'tile_tagline', $brand, '' );
		if ( '' === $tagline ) {
			$tagline = wp_strip_all_tags( (string) gt_term_field( 'promo_tagline', $brand, '' ) );
		}
		$link_text = (string) gt_term_field( 'tile_link_text', $brand, '' );
		if ( '' === $link_text ) {
			/* translators: %s: brand name */
			$link_text = sprintf( __( 'Explore the %s Range', 'gt' ), $brand->name );
		}
		$tiles[] = array(
			'brand'     => $brand,
			'image'     => $image,
			'logo'      => $logo,
			'tagline'   => $tagline,
			'link_text' => $link_text,
			'url'       => $url,
		);
	}
	return $tiles;
}

/** "Ask our experts" link from Theme Settings with the product appended, or ''. */
function gt_product_experts_url( WC_Product $product ) {
	$link = function_exists( 'get_field' ) ? get_field( 'shop_experts_link', 'option' ) : null;
	if ( ! is_array( $link ) || empty( $link['url'] ) ) {
		return '';
	}
	return add_query_arg( array( 'product' => $product->get_id() ), $link['url'] );
}

function gt_product_experts_label() {
	$link = function_exists( 'get_field' ) ? get_field( 'shop_experts_link', 'option' ) : null;
	return ( is_array( $link ) && ! empty( $link['title'] ) ) ? $link['title'] : __( 'Ask our experts', 'gt' );
}

/**
 * "Complete the system": the product's upsells in the order they were set,
 * otherwise every other product in the same brand.
 *
 * @return WC_Product[]
 */
function gt_product_related( WC_Product $product, $limit = 8 ) {
	$limit = max( 1, (int) $limit );
	$ids   = array_map( 'intval', $product->get_upsell_ids() );

	if ( ! $ids ) {
		$brand = gt_product_brand( $product );
		if ( ! $brand ) {
			return array();
		}
		$ids = get_posts( array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'post__not_in'   => array( $product->get_id() ),
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'tax_query'      => array( array( 'taxonomy' => 'product_brand', 'field' => 'term_id', 'terms' => $brand->term_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		) );
	}

	$products = array();
	foreach ( $ids as $id ) {
		if ( $id === $product->get_id() ) {
			continue;
		}
		$related = wc_get_product( $id );
		if ( $related instanceof WC_Product && 'publish' === $related->get_status() && $related->is_visible() ) {
			$products[] = $related;
		}
		if ( count( $products ) >= $limit ) {
			break;
		}
	}
	return $products;
}

/** @return WP_Term[] badge terms, alphabetical. */
function gt_product_badges( WC_Product $product ) {
	if ( ! taxonomy_exists( 'product_badge' ) ) {
		return array();
	}
	$terms = wp_get_post_terms( $product->get_id(), 'product_badge', array( 'orderby' => 'name', 'order' => 'ASC' ) );
	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * The "Better knowledge" band: Theme Settings defaults with any non-empty
 * product override field on top. Empty array when there is no heading.
 */
function gt_knowledge_band( $product_id = 0 ) {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}
	$band = array(
		'heading' => (string) get_field( 'shop_knowledge_heading', 'option' ),
		'text'    => (string) get_field( 'shop_knowledge_text', 'option' ),
		'image'   => (int) get_field( 'shop_knowledge_image', 'option' ),
		'link'    => get_field( 'shop_knowledge_link', 'option' ),
	);
	if ( $product_id ) {
		$override = get_field( 'knowledge_override', $product_id );
		if ( is_array( $override ) ) {
			foreach ( array( 'heading', 'text', 'image', 'link' ) as $key ) {
				if ( ! empty( $override[ $key ] ) ) {
					$band[ $key ] = 'image' === $key ? (int) $override[ $key ] : $override[ $key ];
				}
			}
		}
	}
	if ( ! is_array( $band['link'] ) || empty( $band['link']['url'] ) ) {
		$band['link'] = null;
	}
	return '' === trim( $band['heading'] ) ? array() : $band;
}

// -- How to use steps ---------------------------------------------------------

/**
 * Turn a pasted YouTube or Vimeo link into an embed.
 *
 * Accepts the forms editors actually copy — watch pages, youtu.be shares,
 * shorts, existing embed links, vimeo.com pages, channel URLs and the
 * player host. Anything else (other hosts, non-http schemes) is rejected.
 *
 * @return array|null ['provider' => 'youtube'|'vimeo', 'id' => string, 'src' => string]
 */
function gt_video_embed( $url ) {
	$url = trim( (string) $url );
	if ( ! preg_match( '#^https?://#i', $url ) ) {
		return null;
	}
	$parts = wp_parse_url( $url );
	if ( empty( $parts['host'] ) ) {
		return null;
	}
	$host = strtolower( preg_replace( '/^www\./', '', $parts['host'] ) );
	$path = isset( $parts['path'] ) ? $parts['path'] : '';
	parse_str( isset( $parts['query'] ) ? $parts['query'] : '', $query );

	if ( in_array( $host, array( 'youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'youtu.be' ), true ) ) {
		$id = '';
		if ( 'youtu.be' === $host ) {
			$id = trim( $path, '/' );
		} elseif ( ! empty( $query['v'] ) ) {
			$id = $query['v'];
		} elseif ( preg_match( '#/(?:embed|shorts|v|live)/([^/?]+)#', $path, $m ) ) {
			$id = $m[1];
		}
		if ( ! preg_match( '/^[A-Za-z0-9_-]{6,20}$/', $id ) ) {
			return null;
		}
		return array(
			'provider' => 'youtube',
			'id'       => $id,
			'src'      => 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $id ) . '?autoplay=1&rel=0&modestbranding=1&playsinline=1',
		);
	}

	if ( in_array( $host, array( 'vimeo.com', 'player.vimeo.com' ), true ) ) {
		// The numeric id is the last path segment on every Vimeo URL shape.
		if ( ! preg_match( '#/(\d+)(?:/|$)#', $path, $m ) ) {
			return null;
		}
		return array(
			'provider' => 'vimeo',
			'id'       => $m[1],
			'src'      => 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&dnt=1&title=0&byline=0&portrait=0',
		);
	}

	return null;
}

/**
 * The product's "How to use" steps, ready to render.
 *
 * Each step: ['title', 'text', 'image' (id), 'media' => 'image'|'file'|'youtube'|'vimeo',
 * 'src' => embed url / file url / '' for image]. A video step whose video
 * cannot be resolved (missing file, unparseable URL) falls back to an image
 * step rather than a broken player. Steps without an image are skipped.
 */
function gt_product_howto_steps( $product_id ) {
	$rows = function_exists( 'get_field' ) ? get_field( 'how_to_use_steps', $product_id ) : null;
	if ( ! is_array( $rows ) ) {
		return array();
	}
	$steps = array();
	foreach ( $rows as $row ) {
		$image = ! empty( $row['image'] ) ? (int) $row['image'] : 0;
		$title = ! empty( $row['title'] ) ? trim( (string) $row['title'] ) : '';
		if ( ! $image || '' === $title ) {
			continue;
		}
		$media = ! empty( $row['media'] ) ? (string) $row['media'] : 'image';
		$src   = '';
		if ( 'file' === $media ) {
			$src = ! empty( $row['video_file'] ) ? (string) wp_get_attachment_url( (int) $row['video_file'] ) : '';
		} elseif ( 'youtube' === $media || 'vimeo' === $media ) {
			$embed = gt_video_embed( ! empty( $row['video_url'] ) ? $row['video_url'] : '' );
			$src   = $embed && $embed['provider'] === $media ? $embed['src'] : '';
		}
		if ( '' === $src ) {
			$media = 'image';
		}
		$steps[] = array(
			'title' => $title,
			'text'  => ! empty( $row['text'] ) ? (string) $row['text'] : '',
			'image' => $image,
			'media' => $media,
			'src'   => $src,
		);
	}
	return $steps;
}
