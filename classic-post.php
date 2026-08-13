<?php
/**
 * Template Name: Classic Post
 * Template Post Type: post
 *
 * Opt out of the Scribe single header. Loads GeneratePress's normal single.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require get_parent_theme_file_path( 'single.php' );
