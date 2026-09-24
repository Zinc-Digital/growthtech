<?php
/**
 * Contact details — Figma 384:2873 (Frame 212). Label / value rows with a
 * hairline between them. Rows with no value are dropped.
 *
 * @param array $args ['page_id' => int]
 */

$page_id = isset( $args['page_id'] ) ? (int) $args['page_id'] : get_queried_object_id();
if ( ! function_exists( 'get_field' ) ) {
	return;
}

$address    = trim( (string) get_field( 'address', $page_id ) );
$phone      = trim( (string) get_field( 'phone', $page_id ) );
$email      = trim( (string) get_field( 'email', $page_id ) );
$hours      = trim( (string) get_field( 'hours', $page_id ) );
$hours_note = trim( (string) get_field( 'hours_note', $page_id ) );

$rows = array();
if ( '' !== $address ) {
	$rows[] = array( 'label' => __( 'Address', 'gt' ), 'value' => nl2br( esc_html( $address ) ) );
}
if ( '' !== $phone ) {
	// tel: needs the number without spaces or punctuation; the label keeps the readable form.
	$rows[] = array(
		'label' => __( 'Phone', 'gt' ),
		'value' => '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>',
	);
}
if ( '' !== $email ) {
	$rows[] = array(
		'label' => __( 'Email', 'gt' ),
		'value' => '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>',
	);
}
if ( '' !== $hours || '' !== $hours_note ) {
	$value = '' !== $hours ? nl2br( esc_html( $hours ) ) : '';
	if ( '' !== $hours_note ) {
		$value .= '<span class="contact-details__note">' . esc_html( $hours_note ) . '</span>';
	}
	$rows[] = array( 'label' => __( 'Hours', 'gt' ), 'value' => $value );
}

if ( ! $rows ) {
	return;
}
?>
<dl class="contact-details">
	<?php foreach ( $rows as $row ) : ?>
		<div class="contact-details__row">
			<dt class="contact-details__label"><?php echo esc_html( $row['label'] ); ?></dt>
			<dd class="contact-details__value"><?php echo wp_kses_post( $row['value'] ); ?></dd>
		</div>
	<?php endforeach; ?>
</dl>
