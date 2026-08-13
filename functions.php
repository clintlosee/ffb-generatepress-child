<?php
/**
 * Fly Fishing Basics - GeneratePress Child Theme functions.
 *
 * Adds:
 * - Parent + child stylesheet loading
 * - Customizer controls for the homepage welcome row
 * - A Scribe-style featured hero on the homepage
 * - Category chips above post titles in archives
 * - An Info-style "Most Popular" sidebar widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Prevent direct access.
}

/**
 * 0. Custom image size for the Latest Articles grid.
 * WordPress's built-in sizes (medium_large, etc.) scale proportionally,
 * which is why thumbnails were showing at different heights depending
 * on each photo's original aspect ratio. This registers a fixed
 * 600x400 size with hard cropping (the "true" flag) so every image in
 * the grid is identically sized, cropped from the center.
 */
function flyb_register_image_sizes() {
	add_image_size( 'flyb-card-thumb', 600, 400, true );
}
add_action( 'after_setup_theme', 'flyb_register_image_sizes' );

/**
 * 1. Load parent and child stylesheets.
 * GeneratePress recommends loading the parent style first so the child
 * stylesheet can safely override it.
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

	wp_enqueue_style(
		'flyb-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'generatepress-parent-style', 'flyb-fonts' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'flyb_enqueue_styles' );

/**
 * 1b. Sidebar layout.
 * Homepage page 1 stays full-width (Scribe-style). Paged blog lists,
 * archives, and search get a right sidebar. Singles/pages keep Customizer.
 */
function flyb_sidebar_layout( $layout ) {
	if ( is_front_page() && ! is_paged() ) {
		return 'no-sidebar';
	}

	if ( is_page( 'blog' ) || is_home() || is_archive() || is_search() ) {
		return 'right-sidebar';
	}

	return $layout;
}
add_filter( 'generate_sidebar_layout', 'flyb_sidebar_layout' );

/**
 * 1c. Mobile nav breakpoint.
 * GP default is 768px, so extra header links wrap on tablet before the
 * hamburger appears. 1024px covers phone + tablet (iPad landscape).
 * Both filters required: https://docs.generatepress.com/article/generate_mobile_menu_media_query/
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
 * 1d. /blog listing.
 * "Latest posts" as the homepage means / is the blog index — there is no
 * /blog URL unless a page exists. Create one (once) so the menu and
 * View All Posts can link to a real post list with pagination.
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
			'post_title'  => 'Blog',
			'post_name'   => 'blog',
			'post_status' => 'publish',
			'post_type'   => 'page',
			'post_content'=> '',
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

function flyb_blog_pagination_rewrites() {
	add_rewrite_rule( '^blog/page/([0-9]+)/?$', 'index.php?pagename=blog&paged=$matches[1]', 'top' );
}
add_action( 'init', 'flyb_blog_pagination_rewrites' );

function flyb_flush_blog_rewrites() {
	if ( '1.4' === get_option( 'flyb_rewrite_ver' ) ) {
		return;
	}
	flyb_blog_pagination_rewrites();
	flush_rewrite_rules( false );
	update_option( 'flyb_rewrite_ver', '1.4' );
}
add_action( 'init', 'flyb_flush_blog_rewrites', 20 );

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

/**
 * 2a. Homepage welcome Customizer controls.
 * Appearance > Customize > Homepage Welcome.
 * Empty title/tagline fall back to Site Title + Tagline from Settings > General.
 * Uploaded icon image overrides the selected SVG icon.
 */
function flyb_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'flyb_welcome',
		array(
			'title'       => 'Homepage Welcome',
			'description' => 'Title, tagline, and icon for the homepage welcome row. Leave title or tagline blank to use Settings > General.',
			'priority'    => 30,
		)
	);

	$wp_customize->add_setting(
		'flyb_intro_title',
		array(
			'default'           => '',
			'type'              => 'theme_mod',
			'capability'        => 'edit_theme_options',
			'transport'         => 'refresh',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'flyb_intro_title',
		array(
			'label'       => 'Title',
			'description' => 'Leave blank for “Welcome to [Site Title]”.',
			'section'     => 'flyb_welcome',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'flyb_intro_tagline',
		array(
			'default'           => '',
			'type'              => 'theme_mod',
			'capability'        => 'edit_theme_options',
			'transport'         => 'refresh',
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);
	$wp_customize->add_control(
		'flyb_intro_tagline',
		array(
			'label'       => 'Tagline',
			'description' => 'Leave blank to use the site tagline from Settings > General.',
			'section'     => 'flyb_welcome',
			'type'        => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'flyb_intro_icon',
		array(
			'default'           => 'chevron',
			'type'              => 'theme_mod',
			'capability'        => 'edit_theme_options',
			'transport'         => 'refresh',
			'sanitize_callback' => 'flyb_sanitize_intro_icon',
		)
	);
	$wp_customize->add_control(
		'flyb_intro_icon',
		array(
			'label'       => 'Icon',
			'description' => 'Shown between title and tagline. Upload below overrides this.',
			'section'     => 'flyb_welcome',
			'type'        => 'select',
			'choices'     => array(
				'chevron' => 'Chevron',
				'arrow'   => 'Arrow',
				'caret'   => 'Caret',
				'none'    => 'None',
			),
		)
	);

	$wp_customize->add_setting(
		'flyb_intro_icon_image',
		array(
			'default'           => '',
			'type'              => 'theme_mod',
			'capability'        => 'edit_theme_options',
			'transport'         => 'refresh',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'flyb_intro_icon_image',
			array(
				'label'       => 'Icon image',
				'description' => 'Optional. Replaces the selected icon when set.',
				'section'     => 'flyb_welcome',
				'settings'    => 'flyb_intro_icon_image',
			)
		)
	);

	$wp_customize->add_section(
		'flyb_social',
		array(
			'title'       => 'Social Links',
			'description' => 'Profile URLs for icons next to the main menu. Leave a field blank to hide that icon.',
			'priority'    => 31,
		)
	);

	foreach ( flyb_social_networks() as $key => $network ) {
		$wp_customize->add_setting(
			'flyb_social_' . $key,
			array(
				'default'           => '',
				'type'              => 'theme_mod',
				'capability'        => 'edit_theme_options',
				'transport'         => 'refresh',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			'flyb_social_' . $key,
			array(
				'label'   => $network['label'],
				'section' => 'flyb_social',
				'type'    => 'url',
			)
		);
	}
}
add_action( 'customize_register', 'flyb_customize_register' );

/**
 * Whitelist for the welcome icon select.
 */
function flyb_sanitize_intro_icon( $value ) {
	$allowed = array( 'chevron', 'arrow', 'caret', 'none' );
	return in_array( $value, $allowed, true ) ? $value : 'chevron';
}

/**
 * Inline SVGs for the welcome icon picker. Keys only from flyb_sanitize_intro_icon.
 */
function flyb_intro_icon_svg( $icon ) {
	$svg = array(
		'chevron' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M8 6l16 18L8 42" stroke="currentColor" stroke-width="5" stroke-linecap="square" stroke-linejoin="miter"/><path d="M22 6l16 18-16 18" stroke="currentColor" stroke-width="5" stroke-linecap="square" stroke-linejoin="miter"/></svg>',
		'arrow'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M6 24h30M24 10l16 14-16 14" stroke="currentColor" stroke-width="5" stroke-linecap="square" stroke-linejoin="miter"/></svg>',
		'caret'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M14 6l20 18-20 18" stroke="currentColor" stroke-width="5" stroke-linecap="square" stroke-linejoin="miter"/></svg>',
	);

	return isset( $svg[ $icon ] ) ? $svg[ $icon ] : '';
}

/**
 * Social networks for Customizer URLs + nav icons.
 */
function flyb_social_networks() {
	return array(
		'facebook'  => array(
			'label'   => 'Facebook',
			'viewbox' => '0 0 320 512',
			'path'    => 'M80 299.3V512H196V299.3h86.5l18-97.8H196V166.9c0-51.7 20.3-71.5 72.7-71.5c16.3 0 29.4 .4 37 1.2V7.9C291.4 4 256.4 0 236.2 0C129.3 0 80 50.5 80 159.4v42.1H14v97.8H80z',
		),
		'instagram' => array(
			'label'   => 'Instagram',
			'viewbox' => '0 0 448 512',
			'path'    => 'M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z',
		),
		'youtube'   => array(
			'label'   => 'YouTube',
			'viewbox' => '0 0 576 512',
			'path'    => 'M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.781 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z',
		),
		'x'         => array(
			'label'   => 'X',
			'viewbox' => '0 0 512 512',
			'path'    => 'M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42l255.3 333.8z',
		),
		'tiktok'    => array(
			'label'   => 'TikTok',
			'viewbox' => '0 0 448 512',
			'path'    => 'M448 209.91a210.06 210.06 0 0 1-122.77-39.25V349.38A162.55 162.55 0 1 1 185 188.31V278.2a74.62 74.62 0 1 0 52.23 71.18V0h88.17a121.11 121.11 0 0 0 1.86 22.17A122.18 122.18 0 0 0 381 102.39a121.43 121.43 0 0 0 67 20.14Z',
		),
		'pinterest' => array(
			'label'   => 'Pinterest',
			'viewbox' => '0 0 384 512',
			'path'    => 'M204 6.5C101.4 6.5 0 74.9 0 185.6 0 256 39.6 296 63.6 296c9.9 0 15.6-27.6 15.6-35.4 0-9.3-23.7-29.1-23.7-67.8 0-80.4 61.2-137.4 140.4-137.4 68.1 0 118.5 38.7 118.5 109.8 0 67.8-43.5 125.4-102.3 125.4-31.8 0-55.5-26.4-47.7-58.8 8.1-34.2 23.7-71.1 23.7-95.7 0-22.2-11.7-40.8-36.3-40.8-28.8 0-52.2 29.7-52.2 69.6 0 22.5 7.5 37.8 7.5 37.8s-25.5 107.7-30 126.9c-8.7 36.6-5.4 81.6-2.7 112.8 2.4 26.4 3.6 26.7 6.3 26.7 3.6 0 5.1-3.3 7.2-9.6 13.2-40.2 36.3-109.2 36.3-109.2 18 34.2 70.5 57.6 126.3 57.6 166.5 0 279.6-129 279.6-288.3C384 70.2 298.2 6.5 204 6.5z',
		),
	);
}

/**
 * Social icons after the primary menu, Scribe-style.
 * GP places generate_menu_bar_items in the nav and the mobile header.
 */
function flyb_nav_social_icons() {
	$links = array();

	foreach ( flyb_social_networks() as $key => $network ) {
		$url = get_theme_mod( 'flyb_social_' . $key, '' );
		if ( '' === $url ) {
			continue;
		}

		$links[] = sprintf(
			'<a class="flyb-social-link" href="%1$s" target="_blank" rel="noopener noreferrer" aria-label="%2$s"><svg xmlns="http://www.w3.org/2000/svg" viewBox="%3$s" aria-hidden="true"><path fill="currentColor" d="%4$s"/></svg></a>',
			esc_url( $url ),
			esc_attr( $network['label'] ),
			esc_attr( $network['viewbox'] ),
			esc_attr( $network['path'] )
		);
	}

	if ( empty( $links ) ) {
		return;
	}

	echo '<span class="menu-bar-item flyb-social-links">';
	echo implode( '', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_url/esc_attr above.
	echo '</span>';
}
add_action( 'generate_menu_bar_items', 'flyb_nav_social_icons' );

/**
 * 2a. Homepage welcome intro.
 * Scribe-style title | icon | tagline row. Copy and icon come from
 * Appearance > Customize > Homepage Welcome, with Settings > General fallbacks.
 */
function flyb_homepage_intro() {
	if ( ! is_front_page() || is_paged() ) {
		return;
	}

	$title = get_theme_mod( 'flyb_intro_title', '' );
	if ( '' === $title ) {
		$title = 'Welcome to ' . get_bloginfo( 'name' );
	}

	$tagline = get_theme_mod( 'flyb_intro_tagline', '' );
	if ( '' === $tagline ) {
		$tagline = get_bloginfo( 'description' );
	}

	$icon_image = get_theme_mod( 'flyb_intro_icon_image', '' );
	$icon_key   = flyb_sanitize_intro_icon( get_theme_mod( 'flyb_intro_icon', 'chevron' ) );
	$icon_svg   = flyb_intro_icon_svg( $icon_key );
	$has_icon   = ! empty( $icon_image ) || '' !== $icon_svg;
	?>
	<div class="flyb-intro">
		<div class="flyb-intro-inner">
			<h1 class="flyb-intro-title"><?php echo esc_html( $title ); ?></h1>
			<?php if ( $has_icon || ! empty( $tagline ) ) : ?>
				<div class="flyb-intro-copy">
					<?php if ( ! empty( $icon_image ) ) : ?>
						<span class="flyb-intro-chevron flyb-intro-chevron--image" aria-hidden="true">
							<img src="<?php echo esc_url( $icon_image ); ?>" alt="">
						</span>
					<?php elseif ( '' !== $icon_svg ) : ?>
						<span class="flyb-intro-chevron" aria-hidden="true"><?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded SVG from whitelist. ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $tagline ) ) : ?>
						<p class="flyb-intro-tagline"><?php echo esc_html( $tagline ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
add_action( 'generate_before_main_content', 'flyb_homepage_intro', 4 );

/**
 * 2b. Featured hero on the homepage.
 * Pulls the most recent published post and displays it as a large
 * headline + image hero, similar to the Scribe starter template.
 * Only runs on the main blog homepage, not on paged archives.
 */
function flyb_homepage_hero() {
	if ( ! is_front_page() || is_paged() ) {
		return;
	}

	$featured_query = new WP_Query( array(
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'ignore_sticky_posts' => false, // Respects a manually "stuck" post if you set one.
	) );

	if ( ! $featured_query->have_posts() ) {
		return;
	}

	$featured_query->the_post();
	$categories = get_the_category();
	$category_name = ! empty( $categories ) ? esc_html( $categories[0]->name ) : 'Featured';

	// Remember which post is featured so the Latest Articles grid below
	// can exclude it and avoid showing the same post twice.
	$GLOBALS['flyb_featured_post_id'] = get_the_ID();
	?>
	<div class="flyb-hero">
		<div class="flyb-hero-inner">
			<div class="flyb-hero-text">
				<span class="flyb-hero-eyebrow"><?php echo esc_html( $category_name ); ?></span>
				<h2 class="flyb-hero-title">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h2>
				<p class="flyb-hero-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></p>
				<a href="<?php the_permalink(); ?>" class="button">Read More</a>
			</div>
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="flyb-hero-image">
					<a href="<?php the_permalink(); ?>">
						<?php the_post_thumbnail( 'large' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	wp_reset_postdata();
}
add_action( 'generate_before_main_content', 'flyb_homepage_hero', 5 );

/**
 * 2b. "Latest Articles" grid on the homepage.
 * Shows 6 recent posts in a 3-column grid, excluding whatever post is
 * currently shown in the hero above so nothing repeats. Runs right after
 * the hero (priority 6, one step after the hero's priority 5).
 */
function flyb_homepage_latest_articles() {
	if ( ! is_front_page() || is_paged() ) {
		return;
	}

	$exclude_id = ! empty( $GLOBALS['flyb_featured_post_id'] ) ? array( $GLOBALS['flyb_featured_post_id'] ) : array();

	$articles_query = new WP_Query( array(
		'posts_per_page' => 6,
		'post_status'    => 'publish',
		'post__not_in'   => $exclude_id,
	) );

	if ( ! $articles_query->have_posts() ) {
		return;
	}
	?>
	<div class="flyb-latest-articles">
		<h2 class="flyb-section-heading">Latest Articles</h2>
		<div class="flyb-articles-grid">
			<?php while ( $articles_query->have_posts() ) : $articles_query->the_post(); ?>
				<div class="flyb-post-card">
					<?php if ( has_post_thumbnail() ) : ?>
						<a href="<?php the_permalink(); ?>">
							<?php the_post_thumbnail( 'flyb-card-thumb' ); ?>
						</a>
					<?php endif; ?>
					<div class="entry-content-inner">
						<?php flyb_category_chips(); ?>
						<h3 class="flyb-card-title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h3>
						<p class="flyb-card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 16 ) ); ?></p>
					</div>
				</div>
			<?php endwhile; ?>
		</div>
	</div>
	<?php
	wp_reset_postdata();
}
add_action( 'generate_before_main_content', 'flyb_homepage_latest_articles', 6 );

/**
 * 2c. Flexible homepage widget area.
 * Registers a widget area you fully control from Appearance > Widgets.
 * Drop in a newsletter signup widget, a promo/CTA block, an ad unit,
 * or anything else, no code changes needed to swap it out later.
 */
function flyb_register_homepage_widget_area() {
	register_sidebar( array(
		'name'          => 'Homepage Flexible Section',
		'id'            => 'flyb-homepage-flexible',
		'description'   => 'Displays below the Latest Articles grid on the homepage. Add a newsletter signup, CTA, or any widget here.',
		'before_widget' => '<div class="flyb-flexible-widget">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="flyb-section-heading">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'flyb_register_homepage_widget_area' );

function flyb_homepage_flexible_section() {
	if ( ! is_front_page() || is_paged() ) {
		return;
	}

	if ( ! is_active_sidebar( 'flyb-homepage-flexible' ) ) {
		return; // Nothing added yet, so render nothing rather than an empty box.
	}
	?>
	<div class="flyb-homepage-flexible">
		<?php dynamic_sidebar( 'flyb-homepage-flexible' ); ?>
	</div>
	<?php
}
add_action( 'generate_before_main_content', 'flyb_homepage_flexible_section', 7 );

/**
 * 2d. Suppress the default blog loop on the homepage.
 * GeneratePress normally runs its own post loop (with pagination) after
 * whatever the hooks above add. Since the hero + Latest Articles grid
 * already cover the homepage, this stops that default loop from also
 * rendering and duplicating the same posts. Only affects the front
 * page itself, not page 2, 3, etc., so normal chronological browsing
 * still works once someone clicks past page 1.
 */
function flyb_suppress_homepage_default_loop( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_home() && is_front_page() && ! is_paged() ) {
		// Note: setting posts_per_page to 0 does NOT work here, WordPress
		// treats 0 as falsy and skips the limit entirely, which actually
		// returns every post instead of none. Matching against a post ID
		// of 0 (which can never exist) reliably returns zero results.
		$query->set( 'post__in', array( 0 ) );
	}
}
add_action( 'pre_get_posts', 'flyb_suppress_homepage_default_loop' );

/**
 * 2e. "View All Posts" button.
 * Replaces the old pagination with a single button linking to page 2
 * of the standard chronological archive, since the default loop above
 * is suppressed on page 1 specifically. Page 2 onward is untouched, so
 * clicking through still works like a normal blog archive.
 */
function flyb_view_all_posts_button() {
	if ( ! is_front_page() || is_paged() ) {
		return;
	}
	?>
	<div class="flyb-view-all-wrap">
		<a href="<?php echo esc_url( flyb_get_blog_url() ); ?>" class="button flyb-view-all-button">
			View All Posts
		</a>
	</div>
	<?php
}
add_action( 'generate_before_main_content', 'flyb_view_all_posts_button', 8 );

/**
 * 3. Category chips above post titles.
 * Displays each post's categories as small colored pills, matching
 * the tag style seen in the Scribe/Info reference designs.
 * Hooks into GeneratePress's entry header action.
 */
function flyb_category_chips() {
	if ( is_singular( 'post' ) ) {
		return; // Skip on single posts, show in lists/grids.
	}

	$categories = get_the_category();
	if ( empty( $categories ) ) {
		return;
	}
	?>
	<div class="flyb-category-chips">
		<?php foreach ( $categories as $category ) : ?>
			<a href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>">
				<?php echo esc_html( $category->name ); ?>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}
add_action( 'generate_before_entry_title', 'flyb_category_chips' );

/**
 * 4. "Most Popular" sidebar widget (Info-style).
 * Ranks posts by comment count within the last 90 days as a simple,
 * no-plugin-required proxy for popularity. Swap the 'orderby' logic
 * later for real analytics data (e.g. from PostHog) if you want to.
 */
class Flyb_Popular_Posts_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'flyb_popular_posts',
			'Fly Basics: Most Popular Posts',
			array( 'description' => 'Shows recent high-engagement posts, styled like the Info template sidebar.' )
		);
	}

	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : 'Most Popular';

		$popular_query = new WP_Query( array(
			'posts_per_page' => 5,
			'post_status'    => 'publish',
			'orderby'        => 'comment_count',
			'order'          => 'DESC',
			'date_query'     => array(
				array( 'after' => '90 days ago' ),
			),
		) );

		// Fall back to most recent posts if nothing has comments yet.
		if ( ! $popular_query->have_posts() ) {
			$popular_query = new WP_Query( array(
				'posts_per_page' => 5,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			) );
		}

		echo $args['before_widget'];
		echo '<div class="flyb-popular-posts">';
		echo '<h3>' . esc_html( $title ) . '</h3>';

		while ( $popular_query->have_posts() ) {
			$popular_query->the_post();
			?>
			<div class="flyb-popular-post-item">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="flyb-popular-post-thumb">
						<a href="<?php the_permalink(); ?>">
							<?php the_post_thumbnail( 'thumbnail' ); ?>
						</a>
					</div>
				<?php endif; ?>
				<a href="<?php the_permalink(); ?>" class="flyb-popular-post-title">
					<?php the_title(); ?>
				</a>
			</div>
			<?php
		}

		echo '</div>';
		echo $args['after_widget'];

		wp_reset_postdata();
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : 'Most Popular';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Title:</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		return $instance;
	}
}

function flyb_register_widgets() {
	register_widget( 'Flyb_Popular_Posts_Widget' );
}
add_action( 'widgets_init', 'flyb_register_widgets' );
