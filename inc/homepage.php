<?php
/**
 * Homepage sections and main-loop behavior.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the homepage welcome row.
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
						<span class="flyb-intro-chevron" aria-hidden="true"><?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hardcoded SVG from a whitelist. ?></span>
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
 * Render the latest post as the homepage hero.
 */
function flyb_homepage_hero() {
	if ( ! is_front_page() || is_paged() ) {
		return;
	}

	$featured_query = new WP_Query(
		array(
			'posts_per_page'      => 1,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => false,
		)
	);

	if ( ! $featured_query->have_posts() ) {
		wp_reset_postdata();
		return;
	}

	$featured_query->the_post();
	$categories   = get_the_category();
	$category_name = ! empty( $categories ) ? esc_html( $categories[0]->name ) : 'Featured';

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
 * Render the latest article cards, excluding the hero post.
 */
function flyb_homepage_latest_articles() {
	if ( ! is_front_page() || is_paged() ) {
		return;
	}

	$exclude_id = ! empty( $GLOBALS['flyb_featured_post_id'] ) ? array( $GLOBALS['flyb_featured_post_id'] ) : array();

	$articles_query = new WP_Query(
		array(
			'posts_per_page' => 6,
			'post_status'    => 'publish',
			'post__not_in'   => $exclude_id,
		)
	);

	if ( ! $articles_query->have_posts() ) {
		wp_reset_postdata();
		return;
	}
	?>
	<div class="flyb-latest-articles">
		<h2 class="flyb-section-heading">Latest Articles</h2>
		<div class="flyb-articles-grid">
			<?php while ( $articles_query->have_posts() ) : ?>
				<?php
				$articles_query->the_post();
				flyb_render_post_card();
				?>
			<?php endwhile; ?>
		</div>
	</div>
	<?php
	wp_reset_postdata();
}
add_action( 'generate_before_main_content', 'flyb_homepage_latest_articles', 6 );

/**
 * Render the curated Featured Articles grid.
 */
function flyb_homepage_featured_articles() {
	if ( ! is_front_page() || is_paged() ) {
		return;
	}

	$ids = flyb_get_featured_article_ids();
	if ( empty( $ids ) ) {
		return;
	}

	$articles_query = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'post__in'            => $ids,
			'orderby'             => 'post__in',
			'posts_per_page'      => count( $ids ),
			'ignore_sticky_posts' => true,
		)
	);

	if ( ! $articles_query->have_posts() ) {
		wp_reset_postdata();
		return;
	}
	?>
	<div class="flyb-latest-articles">
		<h2 class="flyb-section-heading">Featured Articles</h2>
		<div class="flyb-articles-grid">
			<?php while ( $articles_query->have_posts() ) : ?>
				<?php
				$articles_query->the_post();
				flyb_render_post_card();
				?>
			<?php endwhile; ?>
		</div>
	</div>
	<?php
	wp_reset_postdata();
}
add_action( 'generate_before_main_content', 'flyb_homepage_featured_articles', 7 );

/**
 * Register the flexible homepage widget area.
 */
function flyb_register_homepage_widget_area() {
	register_sidebar(
		array(
			'name'          => 'Homepage Flexible Section',
			'id'            => 'flyb-homepage-flexible',
			'description'   => 'Displays below Featured Articles on the homepage. Add a newsletter signup, CTA, or any widget here.',
			'before_widget' => '<div class="flyb-flexible-widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="flyb-section-heading">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'flyb_register_homepage_widget_area' );

/**
 * Render the flexible widget area only when populated.
 */
function flyb_homepage_flexible_section() {
	if ( ! is_front_page() || is_paged() ) {
		return;
	}

	if ( ! is_active_sidebar( 'flyb-homepage-flexible' ) ) {
		return;
	}
	?>
	<div class="flyb-homepage-flexible">
		<?php dynamic_sidebar( 'flyb-homepage-flexible' ); ?>
	</div>
	<?php
}
add_action( 'generate_before_main_content', 'flyb_homepage_flexible_section', 8 );

/**
 * Suppress GP's default loop on the first homepage only.
 */
function flyb_suppress_homepage_default_loop( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_home() && is_front_page() && ! is_paged() ) {
		$query->set( 'post__in', array( 0 ) );
	}
}
add_action( 'pre_get_posts', 'flyb_suppress_homepage_default_loop' );

/**
 * Render the View All Posts button.
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
add_action( 'generate_before_main_content', 'flyb_view_all_posts_button', 9 );
