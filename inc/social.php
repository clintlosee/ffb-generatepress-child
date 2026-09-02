<?php
/**
 * Social network definitions and link markup.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Social networks used by the Customizer and nav icons.
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
			'path'    => 'M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448s170.781 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z',
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
 * Build escaped social-link markup from Customizer URLs.
 *
 * @return string[]
 */
function flyb_get_social_links_html() {
	$links = array();

	foreach ( flyb_social_networks() as $key => $network ) {
		$url = get_theme_mod( 'flyb_social_' . $key, '' );
		if ( '' === $url ) {
			continue;
		}

		$links[] = sprintf(
			'<a class="flyb-social-link" href="%1$s" target="_blank" rel="noopener noreferrer" aria-label="%2$s"><svg xmlns="http://www.w3.org/2000/svg" viewBox="%3$s" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="%4$s"/></svg></a>',
			esc_url( $url ),
			esc_attr( $network['label'] ),
			esc_attr( $network['viewbox'] ),
			esc_attr( $network['path'] )
		);
	}

	return $links;
}

/**
 * Render social icons after the primary menu.
 */
function flyb_nav_social_icons() {
	$links = flyb_get_social_links_html();
	if ( empty( $links ) ) {
		return;
	}

	echo '<span class="menu-bar-item flyb-social-links">';
	echo implode( '', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with esc_url and esc_attr above.
	echo '</span>';
}
add_action( 'generate_menu_bar_items', 'flyb_nav_social_icons' );
