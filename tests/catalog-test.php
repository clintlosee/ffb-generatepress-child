<?php
/**
 * Lightweight catalog checks runnable without a WordPress installation.
 */

define( 'ABSPATH', __DIR__ );

$flyb_test_actions     = array();
$flyb_test_shortcodes  = array();
$flyb_test_post_types  = array();
$flyb_test_taxonomies  = array();

function add_action( $hook, $callback ) {
	global $flyb_test_actions;
	$flyb_test_actions[ $hook ][] = $callback;
}

function add_filter( $hook, $callback ) {
	add_action( $hook, $callback );
}

function add_shortcode( $tag, $callback ) {
	global $flyb_test_shortcodes;
	$flyb_test_shortcodes[ $tag ] = $callback;
}

function esc_url_raw( $url ) {
	return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : '';
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

function sanitize_title( $value ) {
	$value = strtolower( trim( (string) $value ) );
	return trim( preg_replace( '/[^a-z0-9]+/', '-', $value ), '-' );
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function wp_strip_all_tags( $value ) {
	return strip_tags( (string) $value );
}

function wp_parse_url( $url ) {
	return parse_url( $url );
}

function has_shortcode( $content, $tag ) {
	return false !== strpos( (string) $content, '[' . $tag );
}

function add_query_arg( $key, $value, $url ) {
	return $url . '?' . rawurlencode( $key ) . '=' . rawurlencode( $value );
}

function register_post_type( $post_type, $args ) {
	global $flyb_test_post_types;
	$flyb_test_post_types[ $post_type ] = $args;
}

function register_taxonomy( $taxonomy, $object_type, $args ) {
	global $flyb_test_taxonomies;
	$flyb_test_taxonomies[ $taxonomy ] = array(
		'object_type' => $object_type,
		'args'        => $args,
	);
}

function get_theme_mod( $name, $default = false ) {
	return $default;
}

function flyb_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$products_file = dirname( __DIR__ ) . '/inc/products.php';
flyb_test_assert( file_exists( $products_file ), 'inc/products.php exists' );
require_once $products_file;

flyb_register_products();

flyb_test_assert( isset( $flyb_test_post_types['flyb_product'] ), 'flyb_product is registered' );
flyb_test_assert( false === $flyb_test_post_types['flyb_product']['public'], 'products are not public' );
flyb_test_assert( false === $flyb_test_post_types['flyb_product']['publicly_queryable'], 'products have no public permalink' );
flyb_test_assert( false === $flyb_test_post_types['flyb_product']['has_archive'], 'products have no archive' );
flyb_test_assert( true === $flyb_test_post_types['flyb_product']['show_ui'], 'products are visible in wp-admin' );
flyb_test_assert( isset( $flyb_test_taxonomies['flyb_department'] ), 'department taxonomy is registered' );
flyb_test_assert( isset( $flyb_test_taxonomies['flyb_brand'] ), 'brand taxonomy is registered' );
flyb_test_assert( isset( $flyb_test_shortcodes['flyb_products'] ), 'product shortcode is registered' );

$query_args = flyb_build_product_query_args(
	array( 'department' => 'Fly Reels' ),
	array(
		'flyb_department' => 'fly-lines',
		'flyb_brand'      => 'Orvis',
	),
	2
);
flyb_test_assert( 24 === $query_args['posts_per_page'], 'catalog pages contain 24 products' );
flyb_test_assert( 2 === $query_args['paged'], 'catalog query uses the current page' );
flyb_test_assert( 'fly-reels' === $query_args['tax_query'][0]['terms'], 'shortcode department overrides the URL filter' );
flyb_test_assert( 'orvis' === $query_args['tax_query'][1]['terms'], 'brand URL filter is applied' );

$query_args = flyb_build_product_query_args(
	array( 'department' => '' ),
	array( 'flyb_department' => 'fly-lines' ),
	1
);
flyb_test_assert( 'fly-lines' === $query_args['tax_query'][0]['terms'], 'URL department is used on the full catalog' );

$query_args = flyb_build_product_query_args(
	array( 'department' => '' ),
	array(
		'flyb_department' => array( 'fly-lines' ),
		'flyb_brand'      => array( 'orvis' ),
	),
	1
);
flyb_test_assert( ! isset( $query_args['tax_query'] ), 'non-scalar URL filters are ignored' );

flyb_test_assert(
	'https://example.test/gear/?flyb_page=%#%' === flyb_product_pagination_base( 'https://example.test/gear/' ),
	'pagination is based on the containing WordPress Page'
);

$import_file = dirname( __DIR__ ) . '/inc/products-import.php';
flyb_test_assert( file_exists( $import_file ), 'inc/products-import.php exists' );
require_once $import_file;

$valid_feed_url = 'https://datafeed.avantlink.com/download_feed.php?id=123&auth=test-token';
flyb_test_assert( $valid_feed_url === flyb_sanitize_product_feed_url( $valid_feed_url ), 'valid AvantLink feed URL is accepted' );
flyb_test_assert( '' === flyb_sanitize_product_feed_url( 'https://example.com/download_feed.php?id=123&auth=test-token' ), 'non-AvantLink feed URL is rejected' );

$feed_row = array(
	'SKU'                        => ' reel-1 ',
	'Product Name'               => 'Abel &amp; Reel',
	'Short Description'          => '<b>Strong</b> &amp; smooth',
	'Brand Name'                 => 'Abel Reels',
	'Department'                 => 'Fly Reels',
	'Thumb URL'                  => 'https://images.example.com/thumb.jpg',
	'Image URL'                  => 'https://images.example.com/large.jpg',
	'Medium Image URL'           => 'https://images.example.com/medium.jpg',
	'Buy Link'                   => 'https://classic.avantlink.com/click.php?p=1',
	'Retail Price'               => '$399.95',
	'Sale Price'                 => '$349.95',
	'Product Page View Tracking' => '<img src="https://classic.avantlink.com/dfpv.php?p=1&amp;pri=2" width="0" height="0" />',
);
$normalized = flyb_normalize_product_row( $feed_row );

flyb_test_assert( 'reel-1' === $normalized['sku'], 'SKU is trimmed' );
flyb_test_assert( 'Abel & Reel' === $normalized['title'], 'product entities are decoded' );
flyb_test_assert( 'Strong & smooth' === $normalized['excerpt'], 'description HTML is stripped and entities are decoded' );
flyb_test_assert( 'https://images.example.com/medium.jpg' === $normalized['image_url'], 'medium product image is preferred' );
flyb_test_assert( '399.95' === $normalized['retail_price'], 'retail price is normalized' );
flyb_test_assert( 'https://classic.avantlink.com/dfpv.php?p=1&pri=2' === $normalized['tracking_url'], 'tracking pixel URL is safely extracted' );

$feed_row['Department'] = '12345';
$bad_department         = flyb_normalize_product_row( $feed_row );
flyb_test_assert( null === $bad_department, 'numeric-only departments are skipped' );

$setup_file = dirname( __DIR__ ) . '/inc/setup.php';
require_once $setup_file;
flyb_test_assert( flyb_content_has_product_catalog( '[flyb_products]' ), 'plain catalog shortcode is detected' );
flyb_test_assert( flyb_content_has_product_catalog( '[flyb_products department="Fly Reels"]' ), 'department catalog shortcode is detected' );
flyb_test_assert( ! flyb_content_has_product_catalog( '[gallery]' ), 'unrelated content is not detected as a catalog' );

flyb_test_assert( 'Check Price' === flyb_get_product_button_label(), 'product button defaults to Check Price' );

$customizer = file_get_contents( dirname( __DIR__ ) . '/inc/customizer.php' );
flyb_test_assert( false !== strpos( $customizer, "'flyb_product_button_label'" ), 'product button text is a Customizer setting' );

$style = file_get_contents( dirname( __DIR__ ) . '/style.css' );
$setup = file_get_contents( $setup_file );
flyb_test_assert( false !== strpos( $setup, 'flyb-theme-' ), 'stylesheet handle is versioned so minify cannot keep a stale flyb-theme.min.css' );
flyb_test_assert( false !== strpos( $style, 'Version: 1.0.7' ), 'theme version is bumped for catalog CSS' );
flyb_test_assert( false !== strpos( $style, '.flyb-product-grid' ), 'product grid styles exist' );
flyb_test_assert( false !== strpos( $style, '.flyb-product-filters' ), 'product filter styles exist' );

echo "Catalog checks passed.\n";
