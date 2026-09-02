<?php
/**
 * WordPress Playground integration checks for the feed synchronizer.
 */

require_once '/wordpress/wp-load.php';
require_once dirname( __DIR__ ) . '/inc/products.php';
require_once dirname( __DIR__ ) . '/inc/products-import.php';

function flyb_integration_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

flyb_register_products();

$feed_fixture = '/flyb-feed/BearsDenbearsdenflyfishing_358385_datafeed.txt';
if ( file_exists( $feed_fixture ) ) {
	$parsed = flyb_parse_product_feed( file_get_contents( $feed_fixture ) );
	flyb_integration_assert( ! is_wp_error( $parsed ), 'the supplied AvantLink feed parses' );
	flyb_integration_assert( 1217 === count( $parsed['products'] ), 'the supplied feed contains 1,217 usable unique products' );
	flyb_integration_assert( 2 === $parsed['skipped'], 'the two numeric-department rows are skipped' );
}

$headers = array(
	'SKU',
	'Product Name',
	'Short Description',
	'Brand Name',
	'Department',
	'Medium Image URL',
	'Buy Link',
	'Retail Price',
	'Sale Price',
	'Product Page View Tracking',
);
$rows    = array(
	$headers,
	array( 'reel-1', 'Test Reel', 'A reel', 'Orvis', 'Fly Reels', 'https://example.com/reel.jpg', 'https://classic.avantlink.com/click.php?p=1', '149.99', '119.99', '' ),
	array( 'rod-1', 'Test Rod', 'A rod', 'Sage', 'Fly Rods', 'https://example.com/rod.jpg', 'https://classic.avantlink.com/click.php?p=2', '249.99', '249.99', '' ),
);

function flyb_integration_tsv( $rows ) {
	$stream = fopen( 'php://temp', 'w+' );
	foreach ( $rows as $row ) {
		fputcsv( $stream, $row, "\t" );
	}
	rewind( $stream );
	return stream_get_contents( $stream );
}

$GLOBALS['flyb_integration_feed'] = flyb_integration_tsv( $rows );
add_filter(
	'pre_http_request',
	function () {
		return array(
			'headers'  => array(),
			'body'     => $GLOBALS['flyb_integration_feed'],
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}
);

update_option( 'flyb_product_feed_url', 'https://datafeed.avantlink.com/download_feed.php?id=123&auth=test-token' );
$first_import = flyb_import_products();
flyb_integration_assert( ! is_wp_error( $first_import ), 'first synchronization succeeds' );
flyb_integration_assert( 2 === $first_import['inserted'], 'first synchronization inserts two products' );
flyb_integration_assert( 2 === (int) wp_count_posts( 'flyb_product' )->publish, 'both synchronized products are published' );

$reel_id = flyb_get_product_ids_by_sku()['reel-1'];
flyb_integration_assert( has_term( 'fly-reels', 'flyb_department', $reel_id ), 'department term is assigned' );
flyb_integration_assert( has_term( 'orvis', 'flyb_brand', $reel_id ), 'brand term is assigned' );

add_option(
	'flyb_product_import_lock',
	array(
		'token' => 'another-import',
		'time'  => time(),
	),
	'',
	false
);
$locked_import = flyb_import_products();
flyb_integration_assert( is_wp_error( $locked_import ), 'a concurrent synchronization cannot acquire the import lock' );
delete_option( 'flyb_product_import_lock' );

add_option(
	'flyb_product_import_lock',
	array(
		'token' => 'stale-import',
		'time'  => time() - ( 3 * HOUR_IN_SECONDS ),
	),
	'',
	false
);
$stale_token = flyb_acquire_product_import_lock();
flyb_integration_assert( '' !== $stale_token, 'a stale lock is replaced with an owned lock' );
flyb_release_product_import_lock( $stale_token );
flyb_integration_assert( false === get_option( 'flyb_product_import_lock', false ), 'owned importer lock is released' );

$GLOBALS['flyb_integration_feed'] = flyb_integration_tsv( array( $headers, $rows[1] ) );
$previous_count_safety             = 100;
update_option( 'flyb_product_last_success_count', $previous_count_safety );
$truncated_import = flyb_import_products();
flyb_integration_assert( is_wp_error( $truncated_import ), 'a feed far below the last successful count is rejected' );
flyb_integration_assert( 2 === (int) wp_count_posts( 'flyb_product' )->publish, 'a rejected feed does not draft products' );

$second_import = flyb_import_products();
flyb_integration_assert( ! is_wp_error( $second_import ), 'second synchronization succeeds' );
flyb_integration_assert( 1 === $second_import['updated'], 'remaining SKU is updated' );
flyb_integration_assert( 1 === $second_import['drafted'], 'missing SKU becomes a draft' );
flyb_integration_assert( 'draft' === get_post_status( flyb_get_product_ids_by_sku()['rod-1'] ), 'removed product is no longer published' );

$failing_rows = array(
	$headers,
	array( 'fail-1', 'Incomplete Product', 'Should stay private', 'Broken Brand', 'Fly Reels', 'https://example.com/fail.jpg', 'https://classic.avantlink.com/click.php?p=3', '99.99', '89.99', '' ),
);
$fail_term    = function ( $term, $taxonomy ) {
	return 'flyb_brand' === $taxonomy && 'Broken Brand' === $term
		? new WP_Error( 'test_term_failure', 'Simulated term failure.' )
		: $term;
};
add_filter( 'pre_insert_term', $fail_term, 10, 2 );
$GLOBALS['flyb_integration_feed'] = flyb_integration_tsv( $failing_rows );
$failed_write                     = flyb_import_products();
remove_filter( 'pre_insert_term', $fail_term, 10 );
flyb_integration_assert( is_wp_error( $failed_write ), 'a product write failure fails the synchronization' );
$failed_id = flyb_get_product_ids_by_sku()['fail-1'];
flyb_integration_assert( 'draft' === get_post_status( $failed_id ), 'an incompletely synchronized product stays in draft' );
flyb_integration_assert( 'publish' === get_post_status( $reel_id ), 'write failure does not draft unrelated missing products' );

update_option( 'flyb_product_import_enabled', true );
flyb_sync_product_import_schedule();
flyb_integration_assert( false !== wp_next_scheduled( 'flyb_product_import_daily' ), 'daily synchronization is scheduled when enabled' );
update_option( 'flyb_product_import_enabled', false );
flyb_sync_product_import_schedule();
flyb_integration_assert( false === wp_next_scheduled( 'flyb_product_import_daily' ), 'daily synchronization is cleared when disabled' );

echo "Catalog import integration checks passed.\n";
