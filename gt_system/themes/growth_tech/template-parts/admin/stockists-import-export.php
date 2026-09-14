<?php
/**
 * Stockists → Import / Export screen. Three states: the default (export
 * button + upload form), the preview of an uploaded file, and the results
 * of a run.
 *
 * @param array $args ['step','error','plan','done','queued','has_key']
 */

$step    = isset( $args['step'] ) ? $args['step'] : '';
$error   = isset( $args['error'] ) ? $args['error'] : '';
$plan    = isset( $args['plan'] ) ? $args['plan'] : null;
$done    = isset( $args['done'] ) ? $args['done'] : null;
$queued  = isset( $args['queued'] ) ? (int) $args['queued'] : 0;
$has_key = ! empty( $args['has_key'] );
$labels  = array(
	'create' => __( 'Create', 'gt' ),
	'update' => __( 'Update', 'gt' ),
	'skip'   => __( 'Skip', 'gt' ),
);
?>
<div class="wrap gt-stockists-io">
	<h1><?php esc_html_e( 'Import / Export Stockists', 'gt' ); ?></h1>

	<?php if ( $error ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
	<?php endif; ?>

	<?php if ( $queued ) : ?>
		<div class="notice notice-info"><p>
			<?php
			echo esc_html( sprintf( /* translators: %d: count */ _n( '%d stockist is waiting to be geocoded; it will be looked up in the background over the next few minutes.', '%d stockists are waiting to be geocoded; they will be looked up in the background over the next few minutes.', $queued, 'gt' ), $queued ) );
			if ( ! $has_key ) {
				echo ' ' . esc_html__( 'No Google Maps key is set, so they will be marked "No Google Maps key set" until one is added and they are saved again.', 'gt' );
			}
			?>
		</p></div>
	<?php endif; ?>

	<?php if ( $done ) : ?>
		<?php $r = $done['result']; ?>
		<div class="notice notice-success"><p>
			<?php echo esc_html( sprintf( /* translators: 1: created 2: updated 3: skipped */ __( 'Import finished: %1$d created, %2$d updated, %3$d skipped.', 'gt' ), $r['created'], $r['updated'], $r['skipped'] ) ); ?>
		</p></div>
		<table class="widefat striped gt-stockists-io__table">
			<thead><tr><th><?php esc_html_e( 'Line', 'gt' ); ?></th><th><?php esc_html_e( 'Name', 'gt' ); ?></th><th><?php esc_html_e( 'Result', 'gt' ); ?></th><th><?php esc_html_e( 'Notes', 'gt' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $done['plan']['rows'] as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['line'] ); ?></td>
						<td><?php echo esc_html( $row['name'] ?: '—' ); ?></td>
						<td><?php echo esc_html( isset( $r['rows'][ $row['line'] ] ) ? $r['rows'][ $row['line'] ] : '' ); ?></td>
						<td><?php echo esc_html( implode( ' ', array_merge( $row['errors'], $row['warnings'] ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p><a class="button" href="<?php echo esc_url( gt_stockists_import_export_url() ); ?>"><?php esc_html_e( 'Back', 'gt' ); ?></a></p>

	<?php elseif ( $plan ) : ?>
		<?php $t = $plan['totals']; ?>
		<h2><?php esc_html_e( 'Preview', 'gt' ); ?></h2>
		<p>
			<?php echo esc_html( sprintf( /* translators: 1: create 2: update 3: skip */ __( 'This file would create %1$d, update %2$d and skip %3$d stockists. Nothing has been written yet.', 'gt' ), $t['create'], $t['update'], $t['skip'] ) ); ?>
			<?php esc_html_e( 'Stockists that are not in the file are left untouched.', 'gt' ); ?>
		</p>
		<table class="widefat striped gt-stockists-io__table">
			<thead><tr><th><?php esc_html_e( 'Line', 'gt' ); ?></th><th><?php esc_html_e( 'Name', 'gt' ); ?></th><th><?php esc_html_e( 'Action', 'gt' ); ?></th><th><?php esc_html_e( 'Notes', 'gt' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $plan['rows'] as $row ) : ?>
					<tr class="gt-stockists-io__row--<?php echo esc_attr( $row['action'] ); ?>">
						<td><?php echo esc_html( $row['line'] ); ?></td>
						<td><?php echo esc_html( $row['name'] ?: '—' ); ?></td>
						<td><?php echo esc_html( $labels[ $row['action'] ] ); ?></td>
						<td><?php echo esc_html( implode( ' ', array_merge( $row['errors'], $row['warnings'] ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<form method="post" action="<?php echo esc_url( gt_stockists_import_export_url() ); ?>" style="display:inline">
			<?php wp_nonce_field( 'gt_stockists_run' ); ?>
			<input type="hidden" name="gt_action" value="run" />
			<?php submit_button( __( 'Run import', 'gt' ), 'primary', 'submit', false, $t['create'] + $t['update'] ? array() : array( 'disabled' => 'disabled' ) ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( gt_stockists_import_export_url() ); ?>" style="display:inline; margin-left: 8px">
			<?php wp_nonce_field( 'gt_stockists_cancel' ); ?>
			<input type="hidden" name="gt_action" value="cancel" />
			<?php submit_button( __( 'Cancel', 'gt' ), 'secondary', 'submit', false ); ?>
		</form>

	<?php else : ?>
		<h2><?php esc_html_e( 'Export', 'gt' ); ?></h2>
		<p><?php esc_html_e( 'Download every stockist as a CSV — one row each, with the products they stock listed by name. Edit it in a spreadsheet and import it back to update in bulk.', 'gt' ); ?></p>
		<form method="post" action="<?php echo esc_url( gt_stockists_import_export_url() ); ?>">
			<?php wp_nonce_field( 'gt_stockists_export' ); ?>
			<input type="hidden" name="gt_action" value="export" />
			<?php submit_button( __( 'Download CSV', 'gt' ), 'secondary', 'submit', false ); ?>
		</form>

		<h2 style="margin-top: 2em"><?php esc_html_e( 'Import', 'gt' ); ?></h2>
		<p><?php esc_html_e( 'Upload a CSV in the same layout as the export. You will see what each row would do before anything is saved.', 'gt' ); ?></p>
		<ul class="ul-disc">
			<li><?php esc_html_e( 'Rows with an id update that stockist; rows without an id update a stockist with exactly the same name, or create a new one.', 'gt' ); ?></li>
			<li><?php esc_html_e( 'products: product names separated by | (e.g. Clonex Mist|Root Riot) — SKUs are accepted too. country: the two-letter code (GB, IE). status: publish or draft. type: the stockist type name — new names are created.', 'gt' ); ?></li>
			<li><?php esc_html_e( 'Leave latitude/longitude empty to have the address looked up automatically after the import; fill both in to pin the stockist by hand.', 'gt' ); ?></li>
			<li><?php esc_html_e( 'Columns you leave out are not changed. Stockists missing from the file are never deleted.', 'gt' ); ?></li>
		</ul>
		<form method="post" action="<?php echo esc_url( gt_stockists_import_export_url() ); ?>" enctype="multipart/form-data">
			<?php wp_nonce_field( 'gt_stockists_preview' ); ?>
			<input type="hidden" name="gt_action" value="preview" />
			<p><input type="file" name="gt_csv" accept=".csv,text/csv" required /></p>
			<?php submit_button( __( 'Preview import', 'gt' ), 'primary', 'submit', false ); ?>
		</form>
	<?php endif; ?>
</div>
