<?php
/**
 * Form panel — Figma 384:2873 (Frame 14). The chosen Gravity Form inside the
 * bordered panel; the theme skins Gravity's own markup in _p.contact.scss.
 *
 * @param array $args ['page_id' => int]
 */

$page_id = isset( $args['page_id'] ) ? (int) $args['page_id'] : get_queried_object_id();
if ( ! function_exists( 'get_field' ) ) {
	return;
}

$form_id = (int) get_field( 'form_id', $page_id );
$heading = trim( (string) get_field( 'form_heading', $page_id ) );
$heading = '' !== $heading ? $heading : __( 'Send us a message', 'gt' );
$can_edit = current_user_can( 'edit_pages' );

if ( ! $form_id && ! $can_edit ) {
	return;
}
?>
<section class="contact-form" aria-labelledby="contact-form-title">
	<h2 class="contact-form__title" id="contact-form-title"><?php echo esc_html( $heading ); ?></h2>

	<?php
	if ( $form_id && function_exists( 'gravity_form' ) ) {
		// Title and description come from the panel heading above, and AJAX keeps
		// the confirmation inside the panel instead of reloading the page.
		gravity_form( $form_id, false, false, false, null, true );
	} elseif ( $can_edit ) {
		?>
		<p class="contact-form__hint"><?php esc_html_e( 'Choose a form under Contact page → Form to show it here. Only you can see this message.', 'gt' ); ?></p>
		<?php
	}
	?>
</section>
