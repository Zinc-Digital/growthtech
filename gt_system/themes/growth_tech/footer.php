</main>

<?php
$gt_club_heading = function_exists( 'get_field' ) ? get_field( 'club_heading', 'option' ) : '';
$gt_club_text    = function_exists( 'get_field' ) ? get_field( 'club_text', 'option' ) : '';
$gt_club_link    = function_exists( 'get_field' ) ? get_field( 'club_link', 'option' ) : null;
$gt_club_image   = function_exists( 'get_field' ) ? (int) get_field( 'club_image', 'option' ) : 0;

// Nothing to say means nothing to show, whatever the per-page toggle says.
$gt_has_club = ( $gt_club_heading || $gt_club_text ) && gt_show_join_club();

$gt_address = function_exists( 'get_field' ) ? get_field( 'address', 'option' ) : '';
$gt_phone   = function_exists( 'get_field' ) ? get_field( 'telephone', 'option' ) : '';
$gt_email   = function_exists( 'get_field' ) ? get_field( 'email', 'option' ) : '';
$gt_credit  = function_exists( 'get_field' ) ? get_field( 'footer_credit', 'option' ) : '';
$gt_socials = gt_social_links();
?>

<footer class="site-footer">

	<?php if ( $gt_has_club ) : ?>
		<section class="site-footer__club">
			<?php
			if ( $gt_club_image ) {
				echo wp_get_attachment_image(
					$gt_club_image,
					'full',
					false,
					array( 'class' => 'site-footer__club-img', 'alt' => '', 'loading' => 'lazy' )
				);
			}
			?>
			<span class="site-footer__club-scrim" aria-hidden="true"></span>

			<div class="site-footer__inner">
				<div class="site-footer__club-row">
					<div class="site-footer__club-text">
						<?php if ( $gt_club_heading ) : ?>
							<p class="site-footer__club-heading"><?php echo esc_html( $gt_club_heading ); ?></p>
						<?php endif; ?>

						<?php if ( $gt_club_text ) : ?>
							<p class="site-footer__club-intro"><?php echo wp_kses_post( $gt_club_text ); ?></p>
						<?php endif; ?>
					</div>

					<?php if ( $gt_club_link && ! empty( $gt_club_link['url'] ) ) : ?>
						<a class="btn-flat" href="<?php echo esc_url( $gt_club_link['url'] ); ?>"
							<?php echo ! empty( $gt_club_link['target'] ) ? 'target="' . esc_attr( $gt_club_link['target'] ) . '" rel="noopener"' : ''; ?>>
							<span class="btn-flat__label">
								<?php echo esc_html( ! empty( $gt_club_link['title'] ) ? $gt_club_link['title'] : __( 'Sign up', 'gt' ) ); ?>
							</span>
							<?php gt_arrow_svg(); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<div class="site-footer__main">
		<div class="site-footer__inner">
			<div class="site-footer__cols">

				<!-- Contact -->
				<div class="site-footer__col site-footer__col--contact">
					<p class="site-footer__heading"><?php esc_html_e( 'Contact Information', 'gt' ); ?></p>

					<div class="site-footer__contact">
						<?php if ( $gt_address ) : ?>
							<address class="site-footer__address"><?php echo nl2br( esc_html( $gt_address ) ); ?></address>
						<?php endif; ?>

						<?php if ( $gt_phone || $gt_email ) : ?>
							<p class="site-footer__reach">
								<?php if ( $gt_phone ) : ?>
									<?php esc_html_e( 'Phone:', 'gt' ); ?>
									<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $gt_phone ) ); ?>">
										<?php echo esc_html( $gt_phone ); ?>
									</a><br />
								<?php endif; ?>

								<?php if ( $gt_email ) : ?>
									<?php esc_html_e( 'Email:', 'gt' ); ?>
									<a href="mailto:<?php echo esc_attr( $gt_email ); ?>"><?php echo esc_html( $gt_email ); ?></a>
								<?php endif; ?>
							</p>
						<?php endif; ?>

						<?php if ( $gt_socials ) : ?>
							<ul class="site-footer__social">
								<?php foreach ( $gt_socials as $gt_social ) : ?>
									<li>
										<a href="<?php echo esc_url( $gt_social['url'] ); ?>" target="_blank" rel="noopener">
											<?php
											if ( ! empty( $gt_social['svg'] ) ) {
												gt_icon_svg( $gt_social['svg'] );
											} else {
												echo '<i class="' . esc_attr( $gt_social['icon'] ) . '" aria-hidden="true"></i>';
											}
											?>
											<span class="screen-reader-text"><?php echo esc_html( $gt_social['label'] ); ?></span>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>

				<!-- Two link columns, headed by the assigned menu's name -->
				<?php
				foreach ( array( 'footer_nav_1', 'footer_nav_2' ) as $gt_location ) :
					$gt_items = gt_nav_tree( $gt_location );

					if ( empty( $gt_items ) ) {
						continue;
					}
					?>
					<div class="site-footer__col">
						<p class="site-footer__heading"><?php echo esc_html( gt_menu_title( $gt_location ) ); ?></p>
						<ul class="site-footer__links">
							<?php foreach ( $gt_items as $gt_item ) : ?>
								<li>
									<a href="<?php echo esc_url( $gt_item->url ); ?>"
										<?php echo $gt_item->target ? 'target="' . esc_attr( $gt_item->target ) . '" rel="noopener"' : ''; ?>>
										<?php echo esc_html( $gt_item->title ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>

				<!-- Standalone links + legal -->
				<div class="site-footer__col site-footer__col--end">
					<?php $gt_nav3 = gt_nav_tree( 'footer_nav_3' ); ?>

					<?php if ( $gt_nav3 ) : ?>
						<ul class="site-footer__major">
							<?php foreach ( $gt_nav3 as $gt_item ) : ?>
								<li>
									<a href="<?php echo esc_url( $gt_item->url ); ?>"
										<?php echo $gt_item->target ? 'target="' . esc_attr( $gt_item->target ) . '" rel="noopener"' : ''; ?>>
										<?php echo esc_html( $gt_item->title ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<ul class="site-footer__legal">
						<?php foreach ( gt_nav_tree( 'footer_legal' ) as $gt_item ) : ?>
							<li>
								<a href="<?php echo esc_url( $gt_item->url ); ?>"
									<?php echo $gt_item->target ? 'target="' . esc_attr( $gt_item->target ) . '" rel="noopener"' : ''; ?>>
									<?php echo esc_html( $gt_item->title ); ?>
								</a>
							</li>
						<?php endforeach; ?>

						<li>
							<?php
							/* translators: 1: site name, 2: current year. */
							printf(
								esc_html__( 'Copyright © %1$s %2$s', 'gt' ),
								esc_html( get_bloginfo( 'name' ) ),
								esc_html( gmdate( 'Y' ) )
							);
							?>
						</li>

						<?php if ( $gt_credit ) : ?>
							<li><?php echo esc_html( $gt_credit ); ?></li>
						<?php endif; ?>
					</ul>
				</div>

			</div>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>

</html>
