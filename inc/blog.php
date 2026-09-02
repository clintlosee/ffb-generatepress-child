<?php
/**
 * Blog page creation, URLs, and pagination rewrites.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensure /blog exists when the homepage displays latest posts.
 */
function flyb_ensure_blog_page() {
	$posts_page = (int) get_option( 'page_for_posts' );
	if ( $posts_page && 'publish' === get_post_status( $posts_page ) ) {
		return $posts_page;
	}

	$id = (int) get_option( 'flyb_blog_page_id' );
	if ( $id && 'publish' === get_post_status( $id ) ) {
		return $id;
	}

	$page = get_page_by_path( 'blog' );
	if ( $page ) {
		update_option( 'flyb_blog_page_id', $page->ID );
		return (int) $page->ID;
	}

	$id = wp_insert_post(
		array(
			'post_title'   => 'Blog',
			'post_name'    => 'blog',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		),
		true
	);

	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}

	update_option( 'flyb_blog_page_id', $id );
	return (int) $id;
}
add_action( 'init', 'flyb_ensure_blog_page' );

/**
 * Add pagination support for the custom Blog page template.
 */
function flyb_blog_pagination_rewrites() {
	add_rewrite_rule( '^blog/page/([0-9]+)/?$', 'index.php?pagename=blog&paged=$matches[1]', 'top' );
}
add_action( 'init', 'flyb_blog_pagination_rewrites' );

/**
 * Flush the Blog rewrite once per rewrite version.
 */
function flyb_flush_blog_rewrites() {
	if ( '1.4' === get_option( 'flyb_rewrite_ver' ) ) {
		return;
	}

	flyb_blog_pagination_rewrites();
	flush_rewrite_rules( false );
	update_option( 'flyb_rewrite_ver', '1.4' );
}
add_action( 'init', 'flyb_flush_blog_rewrites', 20 );

/**
 * Return the configured posts page or the ensured /blog page URL.
 */
function flyb_get_blog_url() {
	$posts_page = (int) get_option( 'page_for_posts' );
	if ( $posts_page && 'publish' === get_post_status( $posts_page ) ) {
		return get_permalink( $posts_page );
	}

	$id = flyb_ensure_blog_page();
	if ( $id ) {
		return get_permalink( $id );
	}

	return home_url( '/blog/' );
}
