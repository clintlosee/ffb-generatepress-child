<?php
/**
 * Theme Customizer settings and helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register homepage and social Customizer controls.
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
		'flyb_featured_articles',
		array(
			'title'       => 'Homepage Featured Articles',
			'description' => 'Pick up to six posts for the Featured Articles grid below Latest Articles. Empty slots are skipped. Posts may also appear in the hero or Latest Articles.',
			'priority'    => 31,
		)
	);

	$article_choices = flyb_get_featured_article_choices();
	for ( $i = 1; $i <= 6; $i++ ) {
		$setting_id = 'flyb_featured_article_' . $i;
		$wp_customize->add_setting(
			$setting_id,
			array(
				'default'           => 0,
				'type'              => 'theme_mod',
				'capability'        => 'edit_theme_options',
				'transport'         => 'refresh',
				'sanitize_callback' => 'flyb_sanitize_featured_article_id',
			)
		);
		$wp_customize->add_control(
			$setting_id,
			array(
				'label'   => 'Post ' . $i,
				'section' => 'flyb_featured_articles',
				'type'    => 'select',
				'choices' => $article_choices,
			)
		);
	}

	$wp_customize->add_section(
		'flyb_social',
		array(
			'title'       => 'Social Links',
			'description' => 'Profile URLs for icons next to the main menu. Leave a field blank to hide that icon.',
			'priority'    => 32,
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

	$wp_customize->add_section(
		'flyb_catalog',
		array(
			'title'       => 'Gear Catalog',
			'description' => 'Labels used on catalog product cards.',
			'priority'    => 34,
		)
	);

	$wp_customize->add_setting(
		'flyb_product_button_label',
		array(
			'default'           => 'Check Price',
			'type'              => 'theme_mod',
			'capability'        => 'edit_theme_options',
			'transport'         => 'refresh',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'flyb_product_button_label',
		array(
			'label'       => 'Product button text',
			'description' => 'Shown on every catalog card. Leave blank to use “Check Price”.',
			'section'     => 'flyb_catalog',
			'type'        => 'text',
		)
	);
}
add_action( 'customize_register', 'flyb_customize_register' );

/**
 * Sanitize the welcome icon selection.
 */
function flyb_sanitize_intro_icon( $value ) {
	$allowed = array( 'chevron', 'arrow', 'caret', 'none' );
	return in_array( $value, $allowed, true ) ? $value : 'chevron';
}

/**
 * Sanitize a Featured Articles slot to a published post ID.
 */
function flyb_sanitize_featured_article_id( $value ) {
	$id = absint( $value );
	if ( $id && 'post' === get_post_type( $id ) && 'publish' === get_post_status( $id ) ) {
		return $id;
	}

	return 0;
}

/**
 * Return unique Customizer-selected Featured Articles IDs in slot order.
 */
function flyb_get_featured_article_ids() {
	$ids = array();
	for ( $i = 1; $i <= 6; $i++ ) {
		$id = absint( get_theme_mod( 'flyb_featured_article_' . $i, 0 ) );
		if ( $id && ! in_array( $id, $ids, true ) ) {
			$ids[] = $id;
		}
	}

	return $ids;
}

/**
 * Build the post choices for Featured Articles controls.
 */
function flyb_get_featured_article_choices() {
	$choices = array( 0 => '— Select a post —' );
	$query   = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 200,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	foreach ( $query->posts as $post ) {
		$choices[ $post->ID ] = $post->post_title;
	}
	wp_reset_postdata();

	foreach ( flyb_get_featured_article_ids() as $id ) {
		if ( isset( $choices[ $id ] ) ) {
			continue;
		}

		$title = get_the_title( $id );
		if ( $title ) {
			$choices[ $id ] = $title;
		}
	}

	return $choices;
}

/**
 * Return a whitelisted inline SVG for the welcome icon.
 */
function flyb_intro_icon_svg( $icon ) {
	$svg = array(
		'chevron' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M8 6l16 18L8 42" stroke="currentColor" stroke-width="5" stroke-linecap="square" stroke-linejoin="miter"/><path d="M22 6l16 18-16 18" stroke="currentColor" stroke-width="5" stroke-linecap="square" stroke-linejoin="miter"/></svg>',
		'arrow'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M6 24h30M24 10l16 14-16 14" stroke="currentColor" stroke-width="5" stroke-linecap="square" stroke-linejoin="miter"/></svg>',
		'caret'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M14 6l20 18-20 18" stroke="currentColor" stroke-width="5" stroke-linecap="square" stroke-linejoin="miter"/></svg>',
	);

	return isset( $svg[ $icon ] ) ? $svg[ $icon ] : '';
}
