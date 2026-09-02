<?php
/**
 * Fly Fishing Basics - GeneratePress Child Theme bootstrap.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$flyb_includes = array(
	'/inc/template-tags.php',
	'/inc/social.php',
	'/inc/blog.php',
	'/inc/single.php',
	'/inc/setup.php',
	'/inc/customizer.php',
	'/inc/homepage.php',
	'/inc/monetization.php',
	'/inc/widgets.php',
	'/inc/products.php',
	'/inc/products-import.php',
);

foreach ( $flyb_includes as $flyb_file ) {
	require_once get_stylesheet_directory() . $flyb_file;
}

unset( $flyb_file, $flyb_includes );
