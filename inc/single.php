<?php
/**
 * Scribe-style single post presentation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine whether the current post uses the Scribe layout.
 */
function flyb_is_scribe_single() {
	return is_singular( 'post' ) && ! is_page_template( 'classic-post.php' );
}

/**
 * Add the Scribe single body class.
 */
function flyb_scribe_single_body_class( $classes ) {
	if ( flyb_is_scribe_single() ) {
		$classes[] = 'flyb-scribe-single';
	}

	return $classes;
}
add_filter( 'body_class', 'flyb_scribe_single_body_class' );

/**
 * Hide GP title and meta elements supplied by the custom Scribe header.
 */
function flyb_scribe_single_setup() {
	if ( ! flyb_is_scribe_single() ) {
		return;
	}

	add_filter( 'generate_show_title', '__return_false' );
	add_filter( 'generate_header_entry_meta_items', '__return_empty_array' );
	add_filter( 'generate_footer_entry_meta_items', '__return_empty_array' );
	add_filter( 'generate_post_date', '__return_false' );
	add_filter( 'generate_post_author', '__return_false' );
	remove_action( 'generate_after_entry_title', 'generate_post_meta' );
	remove_action( 'generate_after_entry_title', 'generate_do_post_meta' );
}
add_action( 'wp', 'flyb_scribe_single_setup' );

/**
 * Render the full-width post title and author box.
 */
function flyb_scribe_post_header() {
	if ( ! flyb_is_scribe_single() ) {
		return;
	}

	$author_id = (int) get_the_author_meta( 'ID' );
	$bio       = trim( wp_strip_all_tags( get_the_author_meta( 'description', $author_id ) ) );
	$social    = flyb_get_social_links_html();
	?>
	<div class="flyb-post-header">
		<div class="flyb-post-header-inner">
			<div class="flyb-post-header-title">
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				<p class="flyb-post-header-date">
					<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
						<?php echo esc_html( get_the_date( 'F j, Y' ) ); ?>
					</time>
					<?php if ( get_the_modified_date( 'Y-m-d' ) > get_the_date( 'Y-m-d' ) ) : ?>
						<span class="flyb-post-header-updated">
							<?php echo esc_html( ' · Updated: ' ); ?>
							<time datetime="<?php echo esc_attr( get_the_modified_date( DATE_W3C ) ); ?>">
								<?php echo esc_html( get_the_modified_date( 'F j, Y' ) ); ?>
							</time>
						</span>
					<?php endif; ?>
				</p>
			</div>
			<div class="flyb-post-header-author">
				<?php
				echo get_avatar(
					$author_id,
					40,
					'',
					get_the_author(),
					array(
						'class' => 'flyb-post-header-avatar',
					)
				);
				?>
				<div class="flyb-post-header-author-copy">
					<p class="flyb-post-header-byline"><?php echo esc_html( 'Written by ' . get_the_author() ); ?></p>
					<?php if ( '' !== $bio ) : ?>
						<p class="flyb-post-header-bio"><?php echo esc_html( $bio ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $social ) ) : ?>
						<div class="flyb-social-links">
							<?php echo implode( '', $social ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with esc_url and esc_attr in flyb_get_social_links_html. ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'generate_after_header', 'flyb_scribe_post_header', 15 );

/**
 * Append the Scribe footer after the main post body.
 */
function flyb_scribe_append_post_footer( $content ) {
	if ( ! flyb_is_scribe_single() || ! is_main_query() || ! in_the_loop() || post_password_required() ) {
		return $content;
	}

	static $appended = false;
	if ( $appended ) {
		return $content;
	}
	$appended = true;

	ob_start();
	flyb_scribe_post_footer();
	return $content . ob_get_clean();
}
add_filter( 'the_content', 'flyb_scribe_append_post_footer', 15 );

/**
 * Render post categories and previous/next navigation.
 */
function flyb_scribe_post_footer() {
	if ( ! flyb_is_scribe_single() ) {
		return;
	}

	$categories = get_the_category();
	$prev       = get_adjacent_post( false, '', true );
	$next       = get_adjacent_post( false, '', false );

	if ( empty( $categories ) && ! $prev && ! $next ) {
		return;
	}
	?>
	<div class="flyb-post-footer">
		<?php if ( ! empty( $categories ) ) : ?>
			<div class="flyb-post-footer-cats">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 7.5A1.5 1.5 0 0 1 4.5 6h4.17L10.8 8H19.5A1.5 1.5 0 0 1 21 9.5v8A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5v-10z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
				<?php echo get_the_category_list( ', ' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress escapes names and URLs. ?>
			</div>
		<?php endif; ?>
		<?php if ( $prev || $next ) : ?>
			<nav class="flyb-post-nav" aria-label="Post navigation">
				<?php if ( $prev ) : ?>
					<a class="flyb-post-nav-link flyb-post-nav-prev" href="<?php echo esc_url( get_permalink( $prev ) ); ?>">
						<span class="flyb-post-nav-arrow" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="square" stroke-linejoin="miter"/></svg>
						</span>
						<span class="screen-reader-text">Previous post: </span>
						<span class="flyb-post-nav-title"><?php echo esc_html( get_the_title( $prev ) ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( $next ) : ?>
					<a class="flyb-post-nav-link flyb-post-nav-next" href="<?php echo esc_url( get_permalink( $next ) ); ?>">
						<span class="screen-reader-text">Next post: </span>
						<span class="flyb-post-nav-title"><?php echo esc_html( get_the_title( $next ) ); ?></span>
						<span class="flyb-post-nav-arrow" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="square" stroke-linejoin="miter"/></svg>
						</span>
					</a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>
	</div>
	<?php
}
