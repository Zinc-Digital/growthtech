<?php
/**
 * Header helpers.
 *
 * The header is driven entirely by WordPress menus so the client can edit it:
 *   header            -> left-hand nav (Our Products, Plant Academy, Find a Stockist)
 *   header_secondary  -> right-hand nav (About Us, Contact Us)
 *   header_mobile     -> slide-in panel (falls back to `header` if unset)
 */

/**
 * Should the header float transparently on top of a banner?
 *
 * True on the front page and the home page template by default. Any template
 * can opt in or out with the `gt_header_overlay` filter.
 */
function gt_header_is_overlay() {
	$overlay = is_front_page() || is_page_template( 'page-templates/page-home.php' );

	return (bool) apply_filters( 'gt_header_overlay', $overlay );
}

/**
 * Logo from the ACF Theme Settings option page, with a bundled fallback so the
 * header still renders on a fresh install.
 */
function gt_header_logo() {
	$logo = function_exists( 'get_field' ) ? get_field( 'logo', 'option' ) : null;

	if ( is_array( $logo ) && ! empty( $logo['url'] ) ) {
		return array(
			'url' => $logo['url'],
			'alt' => ! empty( $logo['alt'] ) ? $logo['alt'] : get_bloginfo( 'name' ),
		);
	}

	return array(
		'url' => get_template_directory_uri() . '/assets/images/logo-gt.png',
		'alt' => get_bloginfo( 'name' ),
	);
}

/**
 * Menu items for a location as a two-level tree.
 *
 * Each top-level item gains a `gt_children` array. Returns an empty array when
 * the location has no menu assigned — the navigation is driven entirely by
 * Appearance > Menus.
 *
 * @param string $location Registered nav menu location.
 * @return array
 */
function gt_nav_tree( $location ) {
	$locations = get_nav_menu_locations();
	$menu      = empty( $locations[ $location ] ) ? false : wp_get_nav_menu_object( $locations[ $location ] );
	$items     = $menu ? wp_get_nav_menu_items( $menu->term_id ) : array();

	if ( empty( $items ) ) {
		return array();
	}

	// Populates current-menu-item / current-menu-ancestor on $item->classes.
	if ( function_exists( '_wp_menu_item_classes_by_context' ) ) {
		_wp_menu_item_classes_by_context( $items );
	}

	$top      = array();
	$children = array();

	foreach ( $items as $item ) {
		if ( $item->menu_item_parent ) {
			$children[ (int) $item->menu_item_parent ][] = $item;
		} else {
			$top[] = $item;
		}
	}

	foreach ( $top as $item ) {
		$item->gt_children = isset( $children[ $item->ID ] ) ? $children[ $item->ID ] : array();
	}

	return $top;
}

/**
 * Class string for a menu item, keeping the core state classes.
 */
function gt_nav_item_classes( $item, $extra = array() ) {
	$classes = array_filter( (array) $item->classes );
	$classes = array_merge( $classes, $extra );

	return implode( ' ', array_map( 'sanitize_html_class', array_unique( $classes ) ) );
}

/**
 * A child item that acts as the "view all" link — one titled "All …".
 * Returned separately so it can head the mega menu instead of becoming a tile.
 */
function gt_nav_all_child( $children ) {
	foreach ( $children as $child ) {
		if ( preg_match( '/^all\b/i', trim( $child->title ) ) ) {
			return $child;
		}
	}

	return null;
}

/**
 * Attachment ID for a menu item's mega menu tile image.
 *
 * Checked in order:
 *   1. the "Mega menu image" ACF field on the menu item itself — works for
 *      Custom Links, and is set in Appearance > Menus;
 *   2. the featured image of the page/post the item links to;
 *   3. nothing, and the tile renders as the plain grey panel.
 */
function gt_nav_item_image_id( $item ) {
	if ( function_exists( 'get_field' ) ) {
		$image = get_field( 'mega_image', $item->ID );

		if ( $image ) {
			return is_array( $image ) ? (int) $image['ID'] : (int) $image;
		}
	}

	if ( 'post_type' === $item->type && $item->object_id ) {
		$thumb = get_post_thumbnail_id( $item->object_id );

		if ( $thumb ) {
			return (int) $thumb;
		}
	}

	return 0;
}

/**
 * Desktop nav list, with a mega menu appended to any item that has children.
 *
 * @param string $location Registered nav menu location.
 * @param string $modifier BEM modifier ("left" or "right").
 */
function gt_render_desktop_nav( $location, $modifier ) {
	$items = gt_nav_tree( $location );

	if ( empty( $items ) ) {
		return;
	}
	?>
	<nav class="site-header__nav site-header__nav--<?php echo esc_attr( $modifier ); ?>"
		aria-label="<?php echo 'left' === $modifier ? esc_attr__( 'Primary', 'gt' ) : esc_attr__( 'Secondary', 'gt' ); ?>">
		<ul>
			<?php foreach ( $items as $item ) : ?>
				<?php
				$has_mega = ! empty( $item->gt_children );
				$mega_id  = 'gt-mega-' . $item->ID;
				?>
				<li class="<?php echo esc_attr( gt_nav_item_classes( $item, $has_mega ? array( 'has-mega' ) : array() ) ); ?>"
					<?php echo $has_mega ? 'data-mega-parent' : ''; ?>>
					<a href="<?php echo esc_url( $item->url ); ?>"
						<?php echo $item->target ? 'target="' . esc_attr( $item->target ) . '" rel="noopener"' : ''; ?>
						<?php echo $has_mega ? 'aria-expanded="false" aria-controls="' . esc_attr( $mega_id ) . '"' : ''; ?>>
						<?php echo esc_html( $item->title ); ?>
					</a>

					<?php
					if ( $has_mega ) {
						gt_render_mega_menu( $item, $mega_id );
					}
					?>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
}

/**
 * Mega menu panel for a top-level item.
 *
 * Heading uses the menu item's Description field (Screen Options -> Description
 * in Appearance > Menus) so it stays editable; falls back to the item title.
 */
function gt_render_mega_menu( $item, $mega_id ) {
	$children  = $item->gt_children;
	$all_child = gt_nav_all_child( $children );
	$heading   = trim( (string) $item->description );

	if ( '' === $heading ) {
		$heading = $item->title;
	}

	// The "All …" entry heads the panel rather than sitting in the grid.
	$tiles = array_filter(
		$children,
		function ( $child ) use ( $all_child ) {
			return ! $all_child || $child->ID !== $all_child->ID;
		}
	);

	$all_url   = $all_child ? $all_child->url : $item->url;
	$all_label = $all_child ? $all_child->title : sprintf( __( 'View all %s', 'gt' ), $item->title );
	?>
	<div class="c-mega-menu" id="<?php echo esc_attr( $mega_id ); ?>" data-mega-panel>
		<div class="c-mega-menu__inner">
			<div class="c-mega-menu__head">
				<p class="c-mega-menu__title"><?php echo esc_html( $heading ); ?></p>
				<a class="c-mega-menu__all" href="<?php echo esc_url( $all_url ); ?>">
					<?php echo esc_html( $all_label ); ?>
				</a>
			</div>

			<?php if ( ! empty( $tiles ) ) : ?>
				<ul class="c-mega-menu__grid">
					<?php foreach ( $tiles as $child ) : ?>
						<?php $image_id = gt_nav_item_image_id( $child ); ?>
						<li class="c-mega-menu__item <?php echo esc_attr( gt_nav_item_classes( $child ) ); ?>">
							<a class="c-mega-menu__link" href="<?php echo esc_url( $child->url ); ?>"
								<?php echo $child->target ? 'target="' . esc_attr( $child->target ) . '" rel="noopener"' : ''; ?>>
								<span class="c-mega-menu__thumb">
									<?php
									if ( $image_id ) {
										echo wp_get_attachment_image(
											$image_id,
											'medium',
											false,
											array( 'alt' => '', 'loading' => 'lazy' )
										);
									}
									?>
								</span>
								<span class="c-mega-menu__label"><?php echo esc_html( $child->title ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Mobile slide-in nav. Items with children become accordions.
 */
function gt_render_mobile_nav() {
	$items = gt_nav_tree( 'header_mobile' );

	// Fall back to the desktop menus so the panel is never empty.
	if ( empty( $items ) ) {
		$items = array_merge( gt_nav_tree( 'header' ), gt_nav_tree( 'header_secondary' ) );
	}

	if ( empty( $items ) ) {
		return;
	}
	?>
	<nav aria-label="<?php esc_attr_e( 'Mobile', 'gt' ); ?>">
		<ul>
			<?php foreach ( $items as $item ) : ?>
				<?php
				$children = ! empty( $item->gt_children ) ? $item->gt_children : array();
				$sub_id   = 'gt-msub-' . $item->ID;
				?>
				<li class="<?php echo esc_attr( gt_nav_item_classes( $item ) ); ?>">
					<?php if ( $children ) : ?>
						<div class="site-nav-mobile__row">
							<a href="<?php echo esc_url( $item->url ); ?>"><?php echo esc_html( $item->title ); ?></a>
							<button type="button" class="site-nav-mobile__toggle"
								aria-expanded="false" aria-controls="<?php echo esc_attr( $sub_id ); ?>">
								<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/icons/chevron.svg' ); ?>"
									alt="" width="14" height="8" />
								<span class="screen-reader-text">
									<?php
									/* translators: %s: menu item title. */
									printf( esc_html__( 'Toggle %s submenu', 'gt' ), esc_html( $item->title ) );
									?>
								</span>
							</button>
						</div>

						<ul class="sub-menu" id="<?php echo esc_attr( $sub_id ); ?>">
							<?php foreach ( $children as $child ) : ?>
								<?php
								$is_all  = preg_match( '/^all\b/i', trim( $child->title ) );
								$classes = gt_nav_item_classes( $child, $is_all ? array( 'gt-all-link' ) : array() );
								?>
								<li class="<?php echo esc_attr( $classes ); ?>">
									<a href="<?php echo esc_url( $child->url ); ?>"><?php echo esc_html( $child->title ); ?></a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<a href="<?php echo esc_url( $item->url ); ?>"><?php echo esc_html( $item->title ); ?></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
}

/**
 * Name of the menu assigned to a location — used as a footer column heading so
 * the client controls it from Appearance > Menus.
 */
function gt_menu_title( $location, $fallback = '' ) {
	$locations = get_nav_menu_locations();
	$menu      = empty( $locations[ $location ] ) ? false : wp_get_nav_menu_object( $locations[ $location ] );

	return $menu ? $menu->name : $fallback;
}

/**
 * Should the "Join the growth club" band show on the current view?
 *
 * Hidden when the page ticks the Page Settings toggle. The field defaults to
 * off, so pages created before the field existed still show the section.
 */
function gt_show_join_club() {
	$show = true;

	if ( is_singular() && function_exists( 'get_field' ) ) {
		$show = ! get_field( 'hide_join_club', get_queried_object_id() );
	}

	return (bool) apply_filters( 'gt_show_join_club', $show );
}

/**
 * Social links from Theme Settings, in the order the design shows them.
 * Facebook, Instagram and LinkedIn ship as inline SVG (exported from Figma);
 * the other two fall back to the Font Awesome kit the theme already loads.
 */
function gt_social_links() {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}

	$networks = array(
		'facebook'  => array( 'label' => 'Facebook', 'svg' => 'social-facebook' ),
		'instagram' => array( 'label' => 'Instagram', 'svg' => 'social-instagram' ),
		'linkedin'  => array( 'label' => 'LinkedIn', 'svg' => 'social-linkedin' ),
		'twitter'   => array( 'label' => 'X', 'icon' => 'fa-brands fa-x-twitter' ),
		'youtube'   => array( 'label' => 'YouTube', 'icon' => 'fa-brands fa-youtube' ),
	);

	$links = array();

	foreach ( $networks as $key => $network ) {
		$url = get_field( $key, 'option' );

		if ( $url ) {
			$network['url'] = $url;
			$links[]        = $network;
		}
	}

	return $links;
}

/**
 * The arrow used by the flat buttons (Figma 165:1907), inline so CSS can
 * recolour it with `currentColor`.
 */
function gt_arrow_svg() {
	?>
	<svg class="btn-flat__arrow" viewBox="0 0 21 18" width="21" height="18" focusable="false" aria-hidden="true">
		<path fill="currentColor"
			d="M20.6531 9.81831C20.8734 9.60737 21 9.31205 21 9.00268C21 8.6933 20.8734 8.40268 20.6531 8.18706L12.4031 0.312055C11.9531 -0.119195 11.2406 -0.100445 10.8141 0.349555C10.3875 0.799555 10.4016 1.51206 10.8516 1.93862L17.0672 7.87768H1.125C0.501562 7.87768 0 8.37924 0 9.00268C0 9.62612 0.501562 10.1277 1.125 10.1277H17.0672L10.8469 16.0621C10.3969 16.4933 10.3828 17.2012 10.8094 17.6512C11.2359 18.1012 11.9484 18.1152 12.3984 17.6887L20.6484 9.81362L20.6531 9.81831Z" />
	</svg>
	<?php
}

/**
 * Print a bundled icon inline so it can inherit `currentColor`.
 *
 * Only reads from the theme's own icon folder, and the filename is reduced to
 * a safe slug first.
 *
 * @param string $name Icon filename without the extension.
 */
function gt_icon_svg( $name ) {
	$slug = preg_replace( '/[^a-z0-9_-]/', '', strtolower( $name ) );
	$file = get_template_directory() . '/assets/images/icons/' . $slug . '.svg';

	if ( ! $slug || ! file_exists( $file ) ) {
		return;
	}

	$svg = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local theme asset.

	// The exports are hard-coded black; let CSS drive the colour instead.
	$svg = str_replace( array( 'fill="black"', 'fill="#000000"', 'fill="#000"' ), 'fill="currentColor"', $svg );

	echo wp_kses(
		$svg,
		array(
			'svg'  => array( 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'xmlns' => true, 'class' => true, 'aria-hidden' => true, 'focusable' => true, 'style' => true, 'preserveaspectratio' => true, 'overflow' => true ),
			'path' => array( 'd' => true, 'fill' => true, 'fill-rule' => true, 'clip-rule' => true, 'id' => true ),
			'g'    => array( 'id' => true, 'fill' => true ),
			'rect' => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'fill' => true, 'id' => true ),
		)
	);
}
