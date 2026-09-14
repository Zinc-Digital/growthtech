<?php
/**
 * Stockists → Import / Export: a CSV of every stockist, and a two-step
 * (preview, then run) CSV import that creates or updates stockists by id or
 * name and never deletes. Rows imported without coordinates are queued and
 * geocoded a few at a time by WP-Cron through the same save-hook geocoder
 * a manual edit uses.
 */

const GT_STOCKISTS_IMPORT_CAP    = 'edit_others_posts';
const GT_STOCKISTS_GEOCODE_BATCH = 10;

// -- Columns ---------------------------------------------------------------------

/** CSV columns, in order. `products` is a |-separated list of product names. */
function gt_stockists_csv_columns() {
	return array( 'id', 'name', 'status', 'type', 'address_1', 'address_2', 'town', 'region', 'postcode', 'country', 'phone', 'website', 'email', 'products', 'latitude', 'longitude', 'geocode_status' );
}

// -- Export ----------------------------------------------------------------------

/** @return array[] one associative row per stockist (any status), ordered by name. */
function gt_stockists_export_rows() {
	$posts = get_posts( array(
		'post_type'      => 'stockist',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );
	$rows = array();
	foreach ( $posts as $post ) {
		$id   = $post->ID;
		$names = array();
		foreach ( array_map( 'intval', (array) get_field( 'products', $id ) ) as $pid ) {
			$product = $pid ? wc_get_product( $pid ) : null;
			if ( $product ) {
				$names[] = $product->get_name();
			}
		}
		$types  = wp_get_post_terms( $id, 'stockist_type', array( 'fields' => 'names' ) );
		$rows[] = array(
			'id'             => (string) $id,
			'name'           => $post->post_title,
			'status'         => $post->post_status,
			'type'           => is_array( $types ) && $types ? $types[0] : '',
			'address_1'      => (string) get_field( 'address_1', $id ),
			'address_2'      => (string) get_field( 'address_2', $id ),
			'town'           => (string) get_field( 'town', $id ),
			'region'         => (string) get_field( 'region', $id ),
			'postcode'       => (string) get_field( 'postcode', $id ),
			'country'        => (string) get_field( 'country', $id ),
			'phone'          => (string) get_field( 'phone', $id ),
			'website'        => (string) get_field( 'website', $id ),
			'email'          => (string) get_field( 'email', $id ),
			'products'       => implode( '|', $names ),
			'latitude'       => (string) get_field( 'lat', $id ),
			'longitude'      => (string) get_field( 'lng', $id ),
			'geocode_status' => (string) get_field( 'geocode_status', $id ),
		);
	}
	return $rows;
}

/** Rows → CSV text with a UTF-8 BOM so Excel reads accents correctly. */
function gt_stockists_csv_string( array $rows ) {
	$columns = gt_stockists_csv_columns();
	$fh      = fopen( 'php://temp', 'w+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fputcsv( $fh, $columns );
	foreach ( $rows as $row ) {
		$line = array();
		foreach ( $columns as $column ) {
			$value = isset( $row[ $column ] ) ? (string) $row[ $column ] : '';
			// A leading =, +, -, @ would be run as a formula by spreadsheets.
			if ( '' !== $value && false !== strpos( '=+-@', $value[0] ) && ! is_numeric( $value ) ) {
				$value = "'" . $value;
			}
			$line[] = $value;
		}
		fputcsv( $fh, $line );
	}
	rewind( $fh );
	$csv = stream_get_contents( $fh );
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return "\xEF\xBB\xBF" . $csv;
}

// -- Import: parse into a plan ---------------------------------------------------

/**
 * Read a CSV and decide what each row would do, without writing anything.
 *
 * @return array|WP_Error ['columns' => string[], 'rows' => array[], 'totals' => ['create','update','skip']]
 *   Each row: ['line', 'action' => create|update|skip, 'post_id', 'name', 'data', 'errors', 'warnings'].
 */
function gt_stockists_import_parse( $path ) {
	if ( ! is_readable( $path ) ) {
		return new WP_Error( 'unreadable', __( 'The file could not be read.', 'gt' ) );
	}
	$fh = fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $fh ) {
		return new WP_Error( 'unreadable', __( 'The file could not be read.', 'gt' ) );
	}
	$header = fgetcsv( $fh );
	if ( ! is_array( $header ) ) {
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return new WP_Error( 'empty', __( 'The file is empty.', 'gt' ) );
	}
	// Strip a BOM and normalise "Address 1" / "address_1" / " ADDRESS_1 " alike.
	$header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $header[0] );
	$header    = array_map( function ( $h ) {
		return str_replace( ' ', '_', strtolower( trim( $h ) ) );
	}, $header );
	$known   = gt_stockists_csv_columns();
	$columns = array_values( array_intersect( $header, $known ) );
	if ( ! in_array( 'name', $columns, true ) ) {
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return new WP_Error( 'missing_columns', __( 'The file needs at least a "name" column.', 'gt' ) );
	}

	$countries = gt_stockist_countries();
	$rows      = array();
	$totals    = array( 'create' => 0, 'update' => 0, 'skip' => 0 );
	$line      = 1;
	$seen      = array();
	while ( ( $record = fgetcsv( $fh ) ) !== false ) {
		$line++;
		if ( 1 === count( $record ) && null === $record[0] ) {
			continue; // Blank line.
		}
		$raw = array();
		foreach ( $header as $i => $column ) {
			if ( in_array( $column, $known, true ) ) {
				$raw[ $column ] = isset( $record[ $i ] ) ? trim( (string) $record[ $i ] ) : '';
			}
		}
		$rows[] = gt_stockists_import_plan_row( $raw, $line, $countries, $seen );
	}
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	foreach ( $rows as $row ) {
		$totals[ $row['action'] ]++;
	}
	return array( 'columns' => $columns, 'rows' => $rows, 'totals' => $totals );
}

/**
 * A product by name (what the export writes) or by SKU (what older files and
 * price lists tend to carry). Names match case-insensitively; only published
 * products count.
 *
 * @return int product id or 0
 */
function gt_stockists_find_product( $entry ) {
	static $by_name = null;
	if ( null === $by_name ) {
		$by_name = array();
		foreach ( wc_get_products( array( 'limit' => -1, 'status' => 'publish', 'return' => 'ids' ) ) as $pid ) {
			$product = wc_get_product( $pid );
			if ( $product ) {
				$by_name[ mb_strtolower( trim( $product->get_name() ) ) ] = (int) $pid;
			}
		}
	}
	$key = mb_strtolower( trim( (string) $entry ) );
	if ( isset( $by_name[ $key ] ) ) {
		return $by_name[ $key ];
	}
	$pid = wc_get_product_id_by_sku( $entry );
	return $pid && 'publish' === get_post_status( $pid ) ? (int) $pid : 0;
}

/** Validate one raw row and decide create / update / skip. $seen tracks names already matched in this file. */
function gt_stockists_import_plan_row( array $raw, $line, array $countries, array &$seen ) {
	$errors   = array();
	$warnings = array();
	$name     = isset( $raw['name'] ) ? sanitize_text_field( $raw['name'] ) : '';
	$data     = array();

	if ( '' === $name ) {
		$errors[] = __( 'The name is missing.', 'gt' );
	}

	// Match: id first, then an exact title.
	$post_id = 0;
	if ( ! empty( $raw['id'] ) ) {
		$candidate = (int) $raw['id'];
		if ( $candidate && 'stockist' === get_post_type( $candidate ) ) {
			$post_id = $candidate;
		} else {
			$errors[] = sprintf( /* translators: %s: the id */ __( 'No stockist has the id %s.', 'gt' ), $raw['id'] );
		}
	} elseif ( '' !== $name ) {
		$existing = get_posts( array( 'post_type' => 'stockist', 'post_status' => 'any', 'title' => $name, 'posts_per_page' => 1, 'fields' => 'ids' ) );
		$post_id  = $existing ? (int) $existing[0] : 0;
	}
	if ( '' !== $name && isset( $seen[ strtolower( $name ) ] ) ) {
		$errors[] = sprintf( /* translators: %d: line number */ __( 'Duplicate of line %d.', 'gt' ), $seen[ strtolower( $name ) ] );
	} elseif ( '' !== $name ) {
		$seen[ strtolower( $name ) ] = $line;
	}

	foreach ( array( 'address_1', 'address_2', 'town', 'region', 'postcode', 'phone', 'type' ) as $key ) {
		if ( array_key_exists( $key, $raw ) ) {
			$data[ $key ] = sanitize_text_field( $raw[ $key ] );
		}
	}
	if ( array_key_exists( 'status', $raw ) ) {
		$status = strtolower( $raw['status'] );
		if ( '' !== $status && ! in_array( $status, array( 'publish', 'draft' ), true ) ) {
			$errors[] = sprintf( /* translators: %s: the status */ __( 'Status must be publish or draft, not "%s".', 'gt' ), $raw['status'] );
		} elseif ( '' !== $status ) {
			$data['status'] = $status;
		}
	}
	if ( array_key_exists( 'country', $raw ) ) {
		$code = strtoupper( $raw['country'] );
		if ( '' !== $code && ! isset( $countries[ $code ] ) ) {
			$errors[] = sprintf( /* translators: %s: the code */ __( 'Unknown country code "%s" — use the two-letter ISO code, e.g. GB.', 'gt' ), $raw['country'] );
		} else {
			$data['country'] = $code;
		}
	}
	if ( array_key_exists( 'website', $raw ) ) {
		$data['website'] = '' === $raw['website'] ? '' : esc_url_raw( $raw['website'] );
		if ( '' !== $raw['website'] && '' === $data['website'] ) {
			$warnings[] = sprintf( /* translators: %s: the value */ __( 'Website "%s" is not a valid URL and was dropped.', 'gt' ), $raw['website'] );
		}
	}
	if ( array_key_exists( 'email', $raw ) ) {
		$data['email'] = '' === $raw['email'] ? '' : sanitize_email( $raw['email'] );
		if ( '' !== $raw['email'] && '' === $data['email'] ) {
			$warnings[] = sprintf( /* translators: %s: the value */ __( 'Email "%s" is not valid and was dropped.', 'gt' ), $raw['email'] );
		}
	}
	if ( array_key_exists( 'products', $raw ) ) {
		$ids = array();
		foreach ( array_filter( array_map( 'trim', explode( '|', $raw['products'] ) ) ) as $entry ) {
			$pid = gt_stockists_find_product( $entry );
			if ( $pid ) {
				$ids[] = $pid;
			} else {
				$warnings[] = sprintf( /* translators: %s: the entry */ __( 'No product is called "%s" (or has that SKU); it was left out.', 'gt' ), $entry );
			}
		}
		$data['products'] = array_values( array_unique( $ids ) );
	}
	if ( array_key_exists( 'latitude', $raw ) || array_key_exists( 'longitude', $raw ) ) {
		$lat = isset( $raw['latitude'] ) ? $raw['latitude'] : '';
		$lng = isset( $raw['longitude'] ) ? $raw['longitude'] : '';
		if ( '' !== $lat && '' !== $lng && is_numeric( $lat ) && is_numeric( $lng ) && abs( (float) $lat ) <= 90 && abs( (float) $lng ) <= 180 ) {
			$data['lat'] = (float) $lat;
			$data['lng'] = (float) $lng;
		} else {
			if ( '' !== $lat || '' !== $lng ) {
				$warnings[] = __( 'Latitude/longitude were not a valid pair and were ignored; the address will be geocoded instead.', 'gt' );
			}
			$data['lat'] = '';
			$data['lng'] = '';
		}
	}

	return array(
		'line'     => $line,
		'action'   => $errors ? 'skip' : ( $post_id ? 'update' : 'create' ),
		'post_id'  => $post_id,
		'name'     => $name,
		'data'     => $data,
		'errors'   => $errors,
		'warnings' => $warnings,
	);
}

// -- Import: apply a plan --------------------------------------------------------

/** @return array ['created', 'updated', 'skipped', 'queued', 'rows' => [line => outcome]] */
function gt_stockists_import_apply( array $plan ) {
	$result = array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'queued' => 0, 'rows' => array() );
	$queue  = array();
	foreach ( $plan['rows'] as $row ) {
		if ( 'skip' === $row['action'] ) {
			$result['skipped']++;
			$result['rows'][ $row['line'] ] = 'skipped';
			continue;
		}
		$data    = $row['data'];
		$post_id = (int) $row['post_id'];
		$args    = array( 'post_type' => 'stockist', 'post_title' => $row['name'] );
		if ( isset( $data['status'] ) ) {
			$args['post_status'] = $data['status'];
		}
		if ( $post_id ) {
			$args['ID'] = $post_id;
			$saved      = wp_update_post( $args, true );
		} else {
			$args['post_status'] = isset( $args['post_status'] ) ? $args['post_status'] : 'publish';
			$saved               = wp_insert_post( $args, true );
		}
		if ( is_wp_error( $saved ) ) {
			$result['skipped']++;
			$result['rows'][ $row['line'] ] = 'failed: ' . $saved->get_error_message();
			continue;
		}
		$id = (int) $saved;

		foreach ( array( 'address_1', 'address_2', 'town', 'region', 'postcode', 'country', 'phone', 'website', 'email' ) as $key ) {
			if ( array_key_exists( $key, $data ) ) {
				update_field( 'field_gt_stockist_' . $key, $data[ $key ], $id );
			}
		}
		if ( array_key_exists( 'products', $data ) ) {
			// An empty relationship must be cleared, never written as [].
			if ( $data['products'] ) {
				update_field( 'field_gt_stockist_products', $data['products'], $id );
			} else {
				delete_field( 'field_gt_stockist_products', $id );
			}
		}
		if ( isset( $data['type'] ) ) {
			if ( '' === $data['type'] ) {
				wp_set_object_terms( $id, array(), 'stockist_type' );
			} else {
				$term = term_exists( $data['type'], 'stockist_type' );
				if ( ! $term ) {
					$term = wp_insert_term( $data['type'], 'stockist_type' );
				}
				if ( ! is_wp_error( $term ) ) {
					wp_set_object_terms( $id, array( (int) $term['term_id'] ), 'stockist_type' );
				}
			}
		}
		if ( array_key_exists( 'lat', $data ) ) {
			update_field( 'field_gt_stockist_lat', $data['lat'], $id );
			update_field( 'field_gt_stockist_lng', $data['lng'], $id );
			if ( '' !== $data['lat'] ) {
				// Typed coordinates: the same rule as the editor — manual, never overwritten.
				update_field( 'field_gt_stockist_geocoded_address', '', $id );
				update_field( 'field_gt_stockist_geocode_status', 'manual', $id );
			} else {
				update_field( 'field_gt_stockist_geocoded_address', '', $id );
				update_field( 'field_gt_stockist_geocode_status', '', $id );
				$queue[] = $id;
			}
		} elseif ( ! $post_id ) {
			$queue[] = $id; // New, with no coordinate columns at all.
		}

		$result[ $post_id ? 'updated' : 'created' ]++;
		$result['rows'][ $row['line'] ] = $post_id ? 'updated' : 'created';
	}
	if ( $queue ) {
		gt_stockists_geocode_enqueue( $queue );
		$result['queued'] = count( $queue );
	}
	return $result;
}

// -- Geocode queue (WP-Cron, a few at a time) ------------------------------------

/** @return int[] post ids waiting to be geocoded. */
function gt_stockists_geocode_queue() {
	return array_values( array_map( 'intval', (array) get_option( 'gt_stockists_geocode_queue', array() ) ) );
}

function gt_stockists_geocode_enqueue( array $ids ) {
	$queue = array_values( array_unique( array_merge( gt_stockists_geocode_queue(), array_map( 'intval', $ids ) ) ) );
	update_option( 'gt_stockists_geocode_queue', $queue, false );
	if ( $queue && ! wp_next_scheduled( 'gt_stockist_geocode_batch' ) ) {
		wp_schedule_single_event( time() + 5, 'gt_stockist_geocode_batch' );
	}
}

/** Geocode the next batch through the save-hook geocoder; reschedule while anything is left. */
function gt_stockists_geocode_batch() {
	$queue = gt_stockists_geocode_queue();
	$now   = array_splice( $queue, 0, GT_STOCKISTS_GEOCODE_BATCH );
	foreach ( $now as $id ) {
		if ( 'stockist' === get_post_type( $id ) ) {
			gt_stockist_maybe_geocode( $id );
		}
	}
	if ( $queue ) {
		update_option( 'gt_stockists_geocode_queue', $queue, false );
		if ( ! wp_next_scheduled( 'gt_stockist_geocode_batch' ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'gt_stockist_geocode_batch' );
		}
	} else {
		delete_option( 'gt_stockists_geocode_queue' );
		wp_clear_scheduled_hook( 'gt_stockist_geocode_batch' );
	}
}
add_action( 'gt_stockist_geocode_batch', 'gt_stockists_geocode_batch' );

// -- Admin screen ----------------------------------------------------------------

function gt_stockists_import_export_menu() {
	add_submenu_page(
		'edit.php?post_type=stockist',
		__( 'Import / Export Stockists', 'gt' ),
		__( 'Import / Export', 'gt' ),
		GT_STOCKISTS_IMPORT_CAP,
		'gt-stockists-import-export',
		'gt_stockists_import_export_screen'
	);
}
add_action( 'admin_menu', 'gt_stockists_import_export_menu' );

function gt_stockists_import_export_url( array $args = array() ) {
	return add_query_arg( array_merge( array( 'post_type' => 'stockist', 'page' => 'gt-stockists-import-export' ), $args ), admin_url( 'edit.php' ) );
}

/** The plan waits in a transient between preview and run; one per user, 30 minutes. */
function gt_stockists_import_plan_key() {
	return 'gt_stockists_import_' . get_current_user_id();
}

/** Handle export download, upload → preview, and run, before any output. */
function gt_stockists_import_export_actions() {
	if ( ! isset( $_GET['page'] ) || 'gt-stockists-import-export' !== $_GET['page'] || ! current_user_can( GT_STOCKISTS_IMPORT_CAP ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$action = isset( $_REQUEST['gt_action'] ) ? sanitize_key( $_REQUEST['gt_action'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! $action ) {
		return;
	}
	check_admin_referer( 'gt_stockists_' . $action );

	if ( 'export' === $action ) {
		$csv = gt_stockists_csv_string( gt_stockists_export_rows() );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="stockists-' . gmdate( 'Y-m-d' ) . '.csv"' );
		header( 'Content-Length: ' . strlen( $csv ) );
		echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput -- CSV download.
		exit;
	}

	if ( 'preview' === $action ) {
		if ( empty( $_FILES['gt_csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['gt_csv']['tmp_name'] ) ) {
			wp_safe_redirect( gt_stockists_import_export_url( array( 'gt_error' => 'no_file' ) ) );
			exit;
		}
		$plan = gt_stockists_import_parse( $_FILES['gt_csv']['tmp_name'] );
		if ( is_wp_error( $plan ) ) {
			wp_safe_redirect( gt_stockists_import_export_url( array( 'gt_error' => $plan->get_error_code() ) ) );
			exit;
		}
		set_transient( gt_stockists_import_plan_key(), $plan, 30 * MINUTE_IN_SECONDS );
		wp_safe_redirect( gt_stockists_import_export_url( array( 'gt_step' => 'preview' ) ) );
		exit;
	}

	if ( 'run' === $action ) {
		$plan = get_transient( gt_stockists_import_plan_key() );
		delete_transient( gt_stockists_import_plan_key() );
		if ( ! is_array( $plan ) ) {
			wp_safe_redirect( gt_stockists_import_export_url( array( 'gt_error' => 'expired' ) ) );
			exit;
		}
		$result = gt_stockists_import_apply( $plan );
		set_transient( gt_stockists_import_plan_key() . '_result', array( 'plan' => $plan, 'result' => $result ), 5 * MINUTE_IN_SECONDS );
		wp_safe_redirect( gt_stockists_import_export_url( array( 'gt_step' => 'done' ) ) );
		exit;
	}

	if ( 'cancel' === $action ) {
		delete_transient( gt_stockists_import_plan_key() );
		wp_safe_redirect( gt_stockists_import_export_url() );
		exit;
	}
}
add_action( 'admin_init', 'gt_stockists_import_export_actions' );

function gt_stockists_import_export_screen() {
	if ( ! current_user_can( GT_STOCKISTS_IMPORT_CAP ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'gt' ) );
	}
	$step   = isset( $_GET['gt_step'] ) ? sanitize_key( $_GET['gt_step'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$error  = isset( $_GET['gt_error'] ) ? sanitize_key( $_GET['gt_error'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$plan   = 'preview' === $step ? get_transient( gt_stockists_import_plan_key() ) : null;
	$done   = 'done' === $step ? get_transient( gt_stockists_import_plan_key() . '_result' ) : null;
	$errors = array(
		'no_file'         => __( 'Choose a CSV file first.', 'gt' ),
		'unreadable'      => __( 'The file could not be read.', 'gt' ),
		'empty'           => __( 'The file is empty.', 'gt' ),
		'missing_columns' => __( 'The file needs at least a "name" column — download the export to see the expected layout.', 'gt' ),
		'expired'         => __( 'That preview has expired; upload the file again.', 'gt' ),
	);
	get_template_part( 'template-parts/admin/stockists-import-export', null, array(
		'step'    => $step,
		'error'   => $error && isset( $errors[ $error ] ) ? $errors[ $error ] : '',
		'plan'    => is_array( $plan ) ? $plan : null,
		'done'    => is_array( $done ) ? $done : null,
		'queued'  => count( gt_stockists_geocode_queue() ),
		'has_key' => '' !== gt_maps_key(),
	) );
}
