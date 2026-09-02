<?php
/**
 * Seed a disposable WordPress Playground site for browser verification.
 */

if ( ! defined( 'ABSPATH' ) ) {
	require_once '/wordpress/wp-load.php';
}

update_option( 'blogname', 'Fly Fishing Basics Catalog Test' );
update_option( 'permalink_structure', '/%postname%/' );

foreach (
	array(
		'Gear'  => '[flyb_products]',
		'Reels' => '[flyb_products department="Fly Reels"]',
		'Rods'  => '[flyb_products department="Fly Rods"]',
	) as $title => $content
) {
	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => strtolower( $title ),
			'post_content' => $content,
		)
	);
}

$departments = array( 'Fly Reels', 'Fly Rods', 'Fly Lines' );
$brands      = array( 'Orvis', 'Sage', 'Redington' );

for ( $index = 1; $index <= 30; $index++ ) {
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'flyb_product',
			'post_status'  => 'publish',
			'post_title'   => sprintf( 'Test Fly Product %02d', $index ),
			'post_excerpt' => 'Disposable browser-verification product.',
		)
	);

	$department = $departments[ ( $index - 1 ) % count( $departments ) ];
	$brand      = $brands[ ( $index - 1 ) % count( $brands ) ];

	update_post_meta( $post_id, '_flyb_sku', 'test-' . $index );
	update_post_meta( $post_id, '_flyb_image_url', 'https://placehold.co/600x600/F7F5EF/18303D?text=Fly+Gear' );
	update_post_meta( $post_id, '_flyb_buy_url', 'https://example.com/product-' . $index );
	update_post_meta( $post_id, '_flyb_retail_price', 149.99 );
	update_post_meta( $post_id, '_flyb_sale_price', 119.99 );
	wp_set_object_terms( $post_id, $department, 'flyb_department', false );
	wp_set_object_terms( $post_id, $brand, 'flyb_brand', false );
}

flush_rewrite_rules();
