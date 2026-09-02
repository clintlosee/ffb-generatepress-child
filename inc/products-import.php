<?php
/**
 * AvantLink product-feed settings and importer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Accept only authenticated HTTPS download URLs from AvantLink.
 *
 * @param string $url Candidate feed URL.
 * @return string
 */
function flyb_sanitize_product_feed_url( $url ) {
	$url   = trim( html_entity_decode( (string) $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	$parts = wp_parse_url( $url );

	if (
		! is_array( $parts ) ||
		'https' !== strtolower( isset( $parts['scheme'] ) ? $parts['scheme'] : '' ) ||
		'datafeed.avantlink.com' !== strtolower( isset( $parts['host'] ) ? $parts['host'] : '' ) ||
		'/download_feed.php' !== ( isset( $parts['path'] ) ? $parts['path'] : '' ) ||
		empty( $parts['query'] )
	) {
		return '';
	}

	parse_str( $parts['query'], $query );
	if ( empty( $query['id'] ) || empty( $query['auth'] ) ) {
		return '';
	}

	return esc_url_raw( $url );
}

/**
 * Normalize a feed price to a decimal string.
 *
 * @param string $price Feed price.
 * @return string
 */
function flyb_normalize_product_price( $price ) {
	$price = preg_replace( '/[^0-9.\-]/', '', (string) $price );
	if ( '' === $price || ! is_numeric( $price ) ) {
		return '';
	}

	return number_format( (float) $price, 2, '.', '' );
}

/**
 * Extract and validate the URL from AvantLink's tracking-pixel markup.
 *
 * @param string $markup Tracking markup from the feed.
 * @return string
 */
function flyb_extract_product_tracking_url( $markup ) {
	$markup = html_entity_decode( (string) $markup, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	if ( ! preg_match( '/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $markup, $matches ) ) {
		return '';
	}

	$url   = esc_url_raw( $matches[1] );
	$parts = wp_parse_url( $url );
	if (
		! is_array( $parts ) ||
		'https' !== strtolower( isset( $parts['scheme'] ) ? $parts['scheme'] : '' ) ||
		'classic.avantlink.com' !== strtolower( isset( $parts['host'] ) ? $parts['host'] : '' ) ||
		'/dfpv.php' !== ( isset( $parts['path'] ) ? $parts['path'] : '' )
	) {
		return '';
	}

	return $url;
}

/**
 * Normalize one full-feed row.
 *
 * @param array $row Feed row keyed by column heading.
 * @return array|null Normalized product, or null when the row is unusable.
 */
function flyb_normalize_product_row( $row ) {
	$sku        = sanitize_text_field( isset( $row['SKU'] ) ? $row['SKU'] : '' );
	$title      = sanitize_text_field( html_entity_decode( isset( $row['Product Name'] ) ? $row['Product Name'] : '', ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	$department = sanitize_text_field( html_entity_decode( isset( $row['Department'] ) ? $row['Department'] : '', ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

	if ( '' === $sku || '' === $title || '' === $department || ctype_digit( $department ) ) {
		return null;
	}

	$excerpt = html_entity_decode( isset( $row['Short Description'] ) ? $row['Short Description'] : '', ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$excerpt = sanitize_text_field( wp_strip_all_tags( $excerpt ) );
	$brand   = sanitize_text_field( html_entity_decode( isset( $row['Brand Name'] ) ? $row['Brand Name'] : '', ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

	$image_url = '';
	foreach ( array( 'Medium Image URL', 'Image URL', 'Thumb URL' ) as $image_column ) {
		$image_url = esc_url_raw( html_entity_decode( isset( $row[ $image_column ] ) ? $row[ $image_column ] : '', ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		if ( '' !== $image_url ) {
			break;
		}
	}

	$buy_url = esc_url_raw( html_entity_decode( isset( $row['Buy Link'] ) ? $row['Buy Link'] : '', ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	if ( '' === $buy_url ) {
		return null;
	}

	return array(
		'sku'          => $sku,
		'title'        => $title,
		'excerpt'      => $excerpt,
		'brand'        => $brand,
		'department'   => $department,
		'image_url'    => $image_url,
		'buy_url'      => $buy_url,
		'retail_price' => flyb_normalize_product_price( isset( $row['Retail Price'] ) ? $row['Retail Price'] : '' ),
		'sale_price'   => flyb_normalize_product_price( isset( $row['Sale Price'] ) ? $row['Sale Price'] : '' ),
		'tracking_url' => flyb_extract_product_tracking_url( isset( $row['Product Page View Tracking'] ) ? $row['Product Page View Tracking'] : '' ),
	);
}

/**
 * Parse a complete tab-delimited AvantLink feed before changing WordPress.
 *
 * @param string $body Downloaded feed body.
 * @return array|WP_Error Parsed products and skipped-row count.
 */
function flyb_parse_product_feed( $body ) {
	$stream = fopen( 'php://temp', 'w+' );
	if ( false === $stream ) {
		return new WP_Error( 'flyb_feed_stream', 'Could not open a temporary stream for the product feed.' );
	}

	fwrite( $stream, $body );
	rewind( $stream );
	$headers = fgetcsv( $stream, 0, "\t" );

	if ( ! is_array( $headers ) ) {
		fclose( $stream );
		return new WP_Error( 'flyb_feed_empty', 'The product feed was empty.' );
	}

	$headers[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $headers[0] );
	$required   = array( 'SKU', 'Product Name', 'Department', 'Buy Link' );
	if ( array_diff( $required, $headers ) ) {
		fclose( $stream );
		return new WP_Error( 'flyb_feed_columns', 'The product feed is missing one or more required columns.' );
	}

	$products = array();
	$skipped  = 0;

	while ( false !== ( $values = fgetcsv( $stream, 0, "\t" ) ) ) {
		if ( count( $values ) !== count( $headers ) ) {
			++$skipped;
			continue;
		}

		$product = flyb_normalize_product_row( array_combine( $headers, $values ) );
		if ( null === $product || isset( $products[ $product['sku'] ] ) ) {
			++$skipped;
			continue;
		}

		$products[ $product['sku'] ] = $product;
	}

	fclose( $stream );

	if ( empty( $products ) ) {
		return new WP_Error( 'flyb_feed_no_products', 'The product feed contained no usable products.' );
	}

	return array(
		'products' => $products,
		'skipped'  => $skipped,
	);
}

/**
 * Return all existing catalog products keyed by SKU.
 *
 * @return int[]
 */
function flyb_get_product_ids_by_sku() {
	$ids = get_posts(
		array(
			'post_type'              => 'flyb_product',
			'post_status'            => 'any',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
			'suppress_filters'       => true,
		)
	);

	$products = array();
	foreach ( $ids as $id ) {
		$sku = (string) get_post_meta( $id, '_flyb_sku', true );
		if ( '' !== $sku && ! isset( $products[ $sku ] ) ) {
			$products[ $sku ] = (int) $id;
		}
	}

	return $products;
}

/**
 * Save a safe import summary (never the feed URL).
 *
 * @param array $status Import status.
 */
function flyb_store_product_import_status( $status ) {
	$status['time'] = current_time( 'mysql' );
	update_option( 'flyb_product_import_status', $status, false );
}

/**
 * Acquire the importer lock atomically.
 *
 * @return string Lock token, or an empty string when another import owns it.
 */
function flyb_acquire_product_import_lock() {
	$lock = get_option( 'flyb_product_import_lock', array() );
	$is_fresh = (
		is_array( $lock ) &&
		! empty( $lock['token'] ) &&
		! empty( $lock['time'] ) &&
		( time() - (int) $lock['time'] ) < ( 2 * HOUR_IN_SECONDS )
	);
	if ( $is_fresh ) {
		return '';
	}

	$token = wp_generate_uuid4();
	$new_lock = array(
		'token' => $token,
		'time'  => time(),
	);

	if ( empty( $lock ) ) {
		return add_option( 'flyb_product_import_lock', $new_lock, '', false ) ? $token : '';
	}

	global $wpdb;
	$updated = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
			maybe_serialize( $new_lock ),
			'flyb_product_import_lock',
			maybe_serialize( $lock )
		)
	);

	if ( 1 !== $updated ) {
		return '';
	}

	wp_cache_delete( 'flyb_product_import_lock', 'options' );
	return $token;
}

/**
 * Release the importer lock only when this process owns it.
 *
 * @param string $token Lock token.
 */
function flyb_release_product_import_lock( $token ) {
	$lock = get_option( 'flyb_product_import_lock', array() );
	if ( is_array( $lock ) && isset( $lock['token'] ) && hash_equals( (string) $lock['token'], $token ) ) {
		delete_option( 'flyb_product_import_lock' );
	}
}

/**
 * Write one product while keeping incomplete records out of the catalog.
 *
 * @param array $product Normalized product.
 * @param int   $post_id Existing product ID, or zero for an insert.
 * @return int|WP_Error
 */
function flyb_sync_product( $product, $post_id = 0 ) {
	$post_data = array(
		'post_type'    => 'flyb_product',
		'post_status'  => 'draft',
		'post_title'   => $product['title'],
		'post_excerpt' => $product['excerpt'],
	);

	if ( $post_id ) {
		$post_data['ID'] = $post_id;
		$post_id         = wp_update_post( wp_slash( $post_data ), true );
	} else {
		$post_id = wp_insert_post( wp_slash( $post_data ), true );
	}

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return is_wp_error( $post_id ) ? $post_id : new WP_Error( 'flyb_product_write', 'A product post could not be saved.' );
	}

	$meta = array(
		'_flyb_sku'          => $product['sku'],
		'_flyb_image_url'    => $product['image_url'],
		'_flyb_buy_url'      => $product['buy_url'],
		'_flyb_retail_price' => $product['retail_price'],
		'_flyb_sale_price'   => $product['sale_price'],
		'_flyb_tracking_url' => $product['tracking_url'],
	);

	foreach ( $meta as $key => $value ) {
		update_post_meta( $post_id, $key, $value );
		if ( (string) $value !== (string) get_post_meta( $post_id, $key, true ) ) {
			return new WP_Error( 'flyb_product_meta', 'Product metadata could not be saved.' );
		}
	}

	$department = wp_set_object_terms( $post_id, $product['department'], 'flyb_department', false );
	$brand      = wp_set_object_terms( $post_id, '' === $product['brand'] ? array() : $product['brand'], 'flyb_brand', false );
	if ( is_wp_error( $department ) || is_wp_error( $brand ) ) {
		return new WP_Error( 'flyb_product_terms', 'Product terms could not be saved.' );
	}

	$published_id = wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'publish',
		),
		true
	);

	return is_wp_error( $published_id ) ? $published_id : (int) $post_id;
}

/**
 * Download and synchronize the complete AvantLink product feed.
 *
 * @return array|WP_Error Import status or an error.
 */
function flyb_import_products() {
	$lock_token = flyb_acquire_product_import_lock();
	if ( '' === $lock_token ) {
		return new WP_Error( 'flyb_import_locked', 'A product import is already running.' );
	}

	try {
		$url = flyb_sanitize_product_feed_url( get_option( 'flyb_product_feed_url', '' ) );
		if ( '' === $url ) {
			throw new RuntimeException( 'Add a valid AvantLink feed URL before importing.' );
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'     => 120,
				'redirection' => 3,
				'headers'     => array(
					'Accept'          => 'text/tab-separated-values,text/plain',
					'Accept-Encoding' => 'identity',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( $response->get_error_message() );
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			throw new RuntimeException( 'AvantLink returned a non-200 response.' );
		}

		$body           = wp_remote_retrieve_body( $response );
		$content_length = wp_remote_retrieve_header( $response, 'content-length' );
		if ( is_numeric( $content_length ) && (int) $content_length !== strlen( $body ) ) {
			throw new RuntimeException( 'The product feed download was incomplete.' );
		}

		$parsed = flyb_parse_product_feed( $body );
		if ( is_wp_error( $parsed ) ) {
			throw new RuntimeException( $parsed->get_error_message() );
		}

		$existing        = flyb_get_product_ids_by_sku();
		$incoming_count  = count( $parsed['products'] );
		$published       = wp_count_posts( 'flyb_product' );
		$published_count = isset( $published->publish ) ? (int) $published->publish : 0;
		$previous_count  = (int) get_option( 'flyb_product_last_success_count', 0 );
		$reference_count = max( $published_count, $previous_count );

		if ( $reference_count >= 100 && $incoming_count < ( $reference_count * 0.9 ) ) {
			$incoming_skus = array_keys( $parsed['products'] );
			sort( $incoming_skus, SORT_STRING );
			$fingerprint = hash( 'sha256', implode( "\n", $incoming_skus ) );
			$candidate   = get_option( 'flyb_product_reduced_feed_candidate', array() );

			if ( ! is_array( $candidate ) || ! isset( $candidate['fingerprint'] ) || ! hash_equals( (string) $candidate['fingerprint'], $fingerprint ) ) {
				update_option(
					'flyb_product_reduced_feed_candidate',
					array(
						'count'       => $incoming_count,
						'fingerprint' => $fingerprint,
						'time'        => current_time( 'mysql' ),
					),
					false
				);
				throw new RuntimeException( 'The feed was much smaller than the previous successful download. No products were changed; the same reduced feed must be received again before removals are applied.' );
			}
		} else {
			delete_option( 'flyb_product_reduced_feed_candidate' );
		}

		$status = array(
			'inserted' => 0,
			'updated'  => 0,
			'drafted'  => 0,
			'skipped'  => (int) $parsed['skipped'],
			'error'    => '',
		);
		$seen        = array();
		$write_errors = 0;

		foreach ( $parsed['products'] as $sku => $product ) {
			$seen[ $sku ] = true;
			$operation    = isset( $existing[ $sku ] ) ? 'updated' : 'inserted';
			$post_id      = flyb_sync_product( $product, isset( $existing[ $sku ] ) ? $existing[ $sku ] : 0 );

			if ( is_wp_error( $post_id ) ) {
				++$status['skipped'];
				++$write_errors;
				continue;
			}

			++$status[ $operation ];
		}

		if ( $write_errors ) {
			$status['error'] = sprintf( '%d products could not be synchronized; missing products were not drafted.', $write_errors );
			flyb_store_product_import_status( $status );
			return new WP_Error( 'flyb_product_writes', $status['error'] );
		}

		foreach ( $existing as $sku => $post_id ) {
			if ( isset( $seen[ $sku ] ) || 'publish' !== get_post_status( $post_id ) ) {
				continue;
			}

			$result = wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'draft',
				),
				true
			);
			if ( is_wp_error( $result ) ) {
				++$status['skipped'];
				$status['error'] = 'One or more missing products could not be drafted.';
				continue;
			}

			++$status['drafted'];
		}

		if ( '' !== $status['error'] ) {
			flyb_store_product_import_status( $status );
			return new WP_Error( 'flyb_product_drafts', $status['error'] );
		}

		delete_option( 'flyb_product_reduced_feed_candidate' );
		update_option( 'flyb_product_last_success_count', $incoming_count, false );
		flyb_store_product_import_status( $status );
		return $status;
	} catch ( RuntimeException $error ) {
		$status = array( 'error' => sanitize_text_field( $error->getMessage() ) );
		flyb_store_product_import_status( $status );
		return new WP_Error( 'flyb_product_import', $status['error'] );
	} finally {
		flyb_release_product_import_lock( $lock_token );
	}
}

/**
 * Register catalog settings.
 */
function flyb_register_product_settings() {
	register_setting(
		'flyb_product_catalog',
		'flyb_product_feed_url',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'flyb_sanitize_product_feed_url',
			'default'           => '',
		)
	);

	register_setting(
		'flyb_product_catalog',
		'flyb_product_import_enabled',
		array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => false,
		)
	);
}
add_action( 'admin_init', 'flyb_register_product_settings' );

/**
 * Add the Gear Catalog settings screen.
 */
function flyb_add_product_settings_page() {
	add_options_page(
		'Gear Catalog',
		'Gear Catalog',
		'manage_options',
		'flyb-gear-catalog',
		'flyb_product_settings_page'
	);
}
add_action( 'admin_menu', 'flyb_add_product_settings_page' );

/**
 * Render Gear Catalog settings and import controls.
 */
function flyb_product_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$status = get_option( 'flyb_product_import_status', array() );
	?>
	<div class="wrap">
		<h1>Gear Catalog</h1>
		<form action="options.php" method="post">
			<?php settings_fields( 'flyb_product_catalog' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="flyb-product-feed-url">AvantLink feed URL</label></th>
					<td>
						<input id="flyb-product-feed-url" name="flyb_product_feed_url" type="password" class="regular-text" value="<?php echo esc_attr( get_option( 'flyb_product_feed_url', '' ) ); ?>" autocomplete="off">
						<p class="description">The URL must use https://datafeed.avantlink.com/download_feed.php and include both id and auth parameters.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Automatic import</th>
					<td>
						<label>
							<input name="flyb_product_import_enabled" type="hidden" value="0">
							<input name="flyb_product_import_enabled" type="checkbox" value="1" <?php checked( get_option( 'flyb_product_import_enabled', false ) ); ?>>
							Import the complete feed daily using WP-Cron
						</label>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Save catalog settings' ); ?>
		</form>

		<h2>Import status</h2>
		<?php if ( empty( $status ) ) : ?>
			<p>No product import has run yet.</p>
		<?php elseif ( ! empty( $status['error'] ) ) : ?>
			<p><strong>Last run:</strong> <?php echo esc_html( isset( $status['time'] ) ? $status['time'] : 'Unknown' ); ?></p>
			<p><strong>Error:</strong> <?php echo esc_html( $status['error'] ); ?></p>
		<?php else : ?>
			<p>
				<strong>Last run:</strong> <?php echo esc_html( isset( $status['time'] ) ? $status['time'] : 'Unknown' ); ?><br>
				<strong>Inserted:</strong> <?php echo esc_html( isset( $status['inserted'] ) ? (string) $status['inserted'] : '0' ); ?>,
				<strong>updated:</strong> <?php echo esc_html( isset( $status['updated'] ) ? (string) $status['updated'] : '0' ); ?>,
				<strong>drafted:</strong> <?php echo esc_html( isset( $status['drafted'] ) ? (string) $status['drafted'] : '0' ); ?>,
				<strong>skipped:</strong> <?php echo esc_html( isset( $status['skipped'] ) ? (string) $status['skipped'] : '0' ); ?>
			</p>
		<?php endif; ?>

		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="flyb_run_product_import">
			<?php wp_nonce_field( 'flyb_run_product_import' ); ?>
			<?php submit_button( 'Run import now', 'secondary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}

/**
 * Run a manual import from the settings screen.
 */
function flyb_run_product_import_now() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to import products.', 'flybasics-generatepress-child' ) );
	}

	check_admin_referer( 'flyb_run_product_import' );
	$result = flyb_import_products();
	$url    = add_query_arg(
		'flyb_import',
		is_wp_error( $result ) ? 'error' : 'success',
		admin_url( 'options-general.php?page=flyb-gear-catalog' )
	);

	wp_safe_redirect( $url );
	exit;
}
add_action( 'admin_post_flyb_run_product_import', 'flyb_run_product_import_now' );

/**
 * Keep the daily feed event in sync with its checkbox.
 */
function flyb_sync_product_import_schedule() {
	$hook      = 'flyb_product_import_daily';
	$scheduled = wp_next_scheduled( $hook );
	$enabled   = (bool) get_option( 'flyb_product_import_enabled', false );

	if ( $enabled && false === $scheduled ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', $hook );
	} elseif ( ! $enabled && false !== $scheduled ) {
		wp_clear_scheduled_hook( $hook );
	}
}
add_action( 'init', 'flyb_sync_product_import_schedule', 20 );

/**
 * Remove the theme-owned cron event when switching themes.
 */
function flyb_clear_product_import_schedule() {
	wp_clear_scheduled_hook( 'flyb_product_import_daily' );
}
add_action( 'switch_theme', 'flyb_clear_product_import_schedule' );
add_action( 'flyb_product_import_daily', 'flyb_import_products' );
