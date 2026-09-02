<?php
/**
 * Theme-provided advertising slots and disclosure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register widget areas for plugin-provided ad and product markup.
 */
function flyb_register_ad_widget_areas() {
	$widget_args = array(
		'before_widget' => '<div id="%1$s" class="flyb-ad-slot-widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="flyb-ad-slot-title">',
		'after_title'   => '</h2>',
	);

	register_sidebar(
		array_merge(
			$widget_args,
			array(
				'name'        => 'Homepage Mid Ad',
				'id'          => 'flyb-homepage-mid-ad',
				'description' => 'Displays between Latest Articles and Featured Articles on the first homepage.',
			)
		)
	);

	register_sidebar(
		array_merge(
			$widget_args,
			array(
				'name'        => 'Sidebar Ad',
				'id'          => 'flyb-sidebar-ad',
				'description' => 'Displays below the normal right-sidebar widgets, including Most Popular.',
			)
		)
	);

	register_sidebar(
		array_merge(
			$widget_args,
			array(
				'name'        => 'After Post Content Ad',
				'id'          => 'flyb-after-content-ad',
				'description' => 'Displays once after single-post content and before related-post filters that run at priority 19 or later.',
			)
		)
	);
}
add_action( 'widgets_init', 'flyb_register_ad_widget_areas' );

/**
 * Render an active ad widget area in a consistently styled wrapper.
 *
 * @param string $sidebar_id Widget area ID.
 * @param string $modifier   Wrapper modifier class.
 * @return bool Whether the slot rendered.
 */
function flyb_render_ad_slot( $sidebar_id, $modifier ) {
	if ( ! is_active_sidebar( $sidebar_id ) ) {
		return false;
	}
	?>
	<div class="flyb-ad-slot <?php echo esc_attr( $modifier ); ?>">
		<?php dynamic_sidebar( $sidebar_id ); ?>
	</div>
	<?php
	return true;
}

/**
 * Render the homepage slot after Latest Articles.
 */
function flyb_homepage_mid_ad() {
	if ( ! is_front_page() || is_paged() ) {
		return;
	}

	flyb_render_ad_slot( 'flyb-homepage-mid-ad', 'flyb-ad-slot--homepage-mid' );
}
add_action( 'generate_before_main_content', 'flyb_homepage_mid_ad', 6 );

/**
 * Render the dedicated ad area after all normal right-sidebar widgets.
 */
function flyb_sidebar_ad() {
	flyb_render_ad_slot( 'flyb-sidebar-ad', 'flyb-ad-slot--sidebar' );
}
add_action( 'generate_after_right_sidebar_content', 'flyb_sidebar_ad' );

/**
 * Append the after-content slot once on single posts.
 *
 * Priority 18 places it after the Scribe footer at 15 and before the common
 * priority-19 related-post insertion point.
 */
function flyb_append_after_content_ad( $content ) {
	if ( ! is_singular( 'post' ) || ! is_main_query() || ! in_the_loop() || post_password_required() ) {
		return $content;
	}

	static $appended = false;
	if ( $appended || ! is_active_sidebar( 'flyb-after-content-ad' ) ) {
		return $content;
	}
	$appended = true;

	ob_start();
	flyb_render_ad_slot( 'flyb-after-content-ad', 'flyb-ad-slot--after-content' );
	return $content . ob_get_clean();
}
add_filter( 'the_content', 'flyb_append_after_content_ad', 18 );

/**
 * Register the sitewide affiliate and advertising disclosure.
 */
function flyb_customize_monetization( $wp_customize ) {
	$wp_customize->add_section(
		'flyb_monetization',
		array(
			'title'       => 'Affiliate / Ads Disclosure',
			'description' => 'Plain-text disclosure shown above the site footer. Leave blank to hide it.',
			'priority'    => 33,
		)
	);

	$wp_customize->add_setting(
		'flyb_ads_disclosure',
		array(
			'default'           => '',
			'type'              => 'theme_mod',
			'capability'        => 'edit_theme_options',
			'transport'         => 'refresh',
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);

	$wp_customize->add_control(
		'flyb_ads_disclosure',
		array(
			'label'       => 'Disclosure text',
			'description' => 'Text only; ad code belongs in the designated widget areas.',
			'section'     => 'flyb_monetization',
			'type'        => 'textarea',
		)
	);
}
add_action( 'customize_register', 'flyb_customize_monetization' );

/**
 * Render the disclosure once above the footer.
 */
function flyb_ads_disclosure() {
	static $rendered = false;

	$disclosure = trim( (string) get_theme_mod( 'flyb_ads_disclosure', '' ) );
	if ( $rendered || '' === $disclosure ) {
		return;
	}
	$rendered = true;
	?>
	<aside class="flyb-disclosure" aria-label="Affiliate and advertising disclosure">
		<div class="flyb-disclosure-inner">
			<p><?php echo esc_html( $disclosure ); ?></p>
		</div>
	</aside>
	<?php
}
add_action( 'generate_before_footer', 'flyb_ads_disclosure' );
