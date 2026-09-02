<?php
/**
 * Theme setup and GeneratePress integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the fixed-crop thumbnail used by article cards.
 */
function flyb_register_image_sizes() {
	add_image_size( 'flyb-card-thumb', 600, 400, true );
}
add_action( 'after_setup_theme', 'flyb_register_image_sizes' );

/**
 * Load parent and child stylesheets.
 */
function flyb_enqueue_styles() {
	wp_enqueue_style(
		'flyb-fonts',
		'https://fonts.googleapis.com/css2?family=Frank+Ruhl+Libre:wght@400&family=Inter:wght@600;700;800&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'generatepress-parent-style',
		get_template_directory_uri() . '/style.css'
	);

	$css_file = get_stylesheet_directory() . '/style.css';

	wp_enqueue_style(
		'flyb-theme',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'generatepress-parent-style', 'flyb-fonts' ),
		file_exists( $css_file ) ? (string) filemtime( $css_file ) : wp_get_theme()->get( 'Version' )
	);

	// Minify writes {handle}.min.css in the theme folder and will keep
	// serving a stale copy until the handle changes. filemtime busts
	// the query string; dequeue drops GP's duplicate child enqueue.
	wp_dequeue_style( 'generate-child' );
	wp_deregister_style( 'generate-child' );
	wp_dequeue_style( 'flyb-style' );
	wp_deregister_style( 'flyb-style' );
}
add_action( 'wp_enqueue_scripts', 'flyb_enqueue_styles', 20 );

/**
 * Keep the first homepage full-width and use a sidebar on post lists and Scribe singles.
 */
function flyb_sidebar_layout( $layout ) {
	if ( is_front_page() && ! is_paged() ) {
		return 'no-sidebar';
	}

	if ( is_page( 'blog' ) || is_home() || is_archive() || is_search() || flyb_is_scribe_single() ) {
		return 'right-sidebar';
	}

	return $layout;
}
add_filter( 'generate_sidebar_layout', 'flyb_sidebar_layout' );

/**
 * Use the same phone and tablet breakpoint for both GP nav queries.
 */
function flyb_mobile_menu_media_query() {
	return '(max-width: 1024px)';
}
add_filter( 'generate_mobile_menu_media_query', 'flyb_mobile_menu_media_query' );

function flyb_not_mobile_menu_media_query() {
	return '(min-width: 1025px)';
}
add_filter( 'generate_not_mobile_menu_media_query', 'flyb_not_mobile_menu_media_query' );

/**
 * Keep the footer copyright to the year and site title.
 */
function flyb_copyright() {
	return sprintf(
		'<span class="copyright">&copy; %1$s %2$s</span>',
		esc_html( wp_date( 'Y' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
}
add_filter( 'generate_copyright', 'flyb_copyright' );
