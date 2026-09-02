<?php
/**
 * Affiliate product catalog registration and display.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the internal product catalog and its filter taxonomies.
 */
function flyb_register_products() {
	register_post_type(
		'flyb_product',
		array(
			'labels'             => array(
				'name'          => 'Gear Products',
				'singular_name' => 'Gear Product',
				'menu_name'     => 'Gear Products',
				'add_new_item'  => 'Add Gear Product',
				'edit_item'     => 'Edit Gear Product',
				'view_item'     => 'View Gear Product',
				'search_items'  => 'Search Gear Products',
			),
			'public'             => false,
			'publicly_queryable' => false,
			'has_archive'        => false,
			'rewrite'            => false,
			'query_var'          => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => false,
			'menu_icon'          => 'dashicons-products',
			'supports'           => array( 'title', 'excerpt' ),
		)
	);

	$taxonomy_args = array(
		'public'            => false,
		'publicly_queryable' => false,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_nav_menus' => false,
		'show_in_rest'      => false,
		'rewrite'           => false,
		'hierarchical'      => false,
	);

	register_taxonomy(
		'flyb_department',
		'flyb_product',
		array_merge(
			$taxonomy_args,
			array(
				'labels' => array(
					'name'          => 'Departments',
					'singular_name' => 'Department',
				),
			)
		)
	);

	register_taxonomy(
		'flyb_brand',
		'flyb_product',
		array_merge(
			$taxonomy_args,
			array(
				'labels' => array(
					'name'          => 'Brands',
					'singular_name' => 'Brand',
				),
			)
		)
	);
}
add_action( 'init', 'flyb_register_products' );

/**
 * Build the catalog query from shortcode attributes and URL filters.
 *
 * @param array $attributes Shortcode attributes.
 * @param array $request    Request values.
 * @param int   $paged      Current catalog page.
 * @return array
 */
function flyb_build_product_query_args( $attributes, $request, $paged ) {
	$locked_department = isset( $attributes['department'] ) && is_scalar( $attributes['department'] ) ? sanitize_title( (string) $attributes['department'] ) : '';
	$department        = $locked_department;
	$brand             = isset( $request['flyb_brand'] ) && is_scalar( $request['flyb_brand'] ) ? sanitize_title( (string) $request['flyb_brand'] ) : '';

	if ( '' === $department && isset( $request['flyb_department'] ) && is_scalar( $request['flyb_department'] ) ) {
		$department = sanitize_title( (string) $request['flyb_department'] );
	}

	$args = array(
		'post_type'           => 'flyb_product',
		'post_status'         => 'publish',
		'posts_per_page'      => 24,
		'paged'               => max( 1, (int) $paged ),
		'orderby'             => 'title',
		'order'               => 'ASC',
		'ignore_sticky_posts' => true,
	);

	if ( '' !== $department ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'flyb_department',
			'field'    => 'slug',
			'terms'    => $department,
		);
	}

	if ( '' !== $brand ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'flyb_brand',
			'field'    => 'slug',
			'terms'    => $brand,
		);
	}

	return $args;
}

/**
 * Render one taxonomy filter.
 *
 * @param string $taxonomy Taxonomy name.
 * @param string $name     Request field name.
 * @param string $label    Visible label.
 * @param string $selected Selected term slug.
 */
function flyb_render_product_filter_select( $taxonomy, $name, $label, $selected ) {
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return;
	}
	?>
	<label class="flyb-product-filter-field">
		<span><?php echo esc_html( $label ); ?></span>
		<select name="<?php echo esc_attr( $name ); ?>">
			<option value="">All <?php echo esc_html( strtolower( $label ) ); ?></option>
			<?php foreach ( $terms as $term ) : ?>
				<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $selected, $term->slug ); ?>>
					<?php echo esc_html( $term->name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</label>
	<?php
}

/**
 * Render catalog filter controls.
 *
 * @param string $locked_department Department fixed by the shortcode.
 * @param string $department        Selected department slug.
 * @param string $brand             Selected brand slug.
 * @param string $page_url          Containing WordPress Page URL.
 */
function flyb_render_product_filters( $locked_department, $department, $brand, $page_url ) {
	$has_filter = ( '' === $locked_department && '' !== $department ) || '' !== $brand;
	?>
	<form class="flyb-product-filters" action="<?php echo esc_url( $page_url ); ?>" method="get">
		<?php if ( '' === $locked_department ) : ?>
			<?php flyb_render_product_filter_select( 'flyb_department', 'flyb_department', 'Departments', $department ); ?>
		<?php endif; ?>
		<?php flyb_render_product_filter_select( 'flyb_brand', 'flyb_brand', 'Brands', $brand ); ?>
		<div class="flyb-product-filter-actions">
			<button class="button" type="submit">Filter products</button>
			<?php if ( $has_filter ) : ?>
				<a href="<?php echo esc_url( $page_url ); ?>">Clear</a>
			<?php endif; ?>
		</div>
	</form>
	<?php
}

/**
 * Render a price, showing a discount when the sale price is lower.
 *
 * @param string $retail_price Retail price.
 * @param string $sale_price   Sale price.
 */
function flyb_render_product_price( $retail_price, $sale_price ) {
	$retail = is_numeric( $retail_price ) ? (float) $retail_price : 0;
	$sale   = is_numeric( $sale_price ) ? (float) $sale_price : 0;

	if ( $sale > 0 && $retail > $sale ) {
		?>
		<p class="flyb-product-price">
			<del>$<?php echo esc_html( number_format_i18n( $retail, 2 ) ); ?></del>
			<ins>$<?php echo esc_html( number_format_i18n( $sale, 2 ) ); ?></ins>
		</p>
		<?php
		return;
	}

	$price = $sale > 0 ? $sale : $retail;
	if ( $price > 0 ) {
		?>
		<p class="flyb-product-price">$<?php echo esc_html( number_format_i18n( $price, 2 ) ); ?></p>
		<?php
	}
}

/**
 * Render one affiliate product card.
 *
 * @param int $post_id Product post ID.
 */
function flyb_render_product_card( $post_id ) {
	$title          = get_the_title( $post_id );
	$image_url      = get_post_meta( $post_id, '_flyb_image_url', true );
	$buy_url        = get_post_meta( $post_id, '_flyb_buy_url', true );
	$retail_price   = get_post_meta( $post_id, '_flyb_retail_price', true );
	$sale_price     = get_post_meta( $post_id, '_flyb_sale_price', true );
	$tracking_url   = get_post_meta( $post_id, '_flyb_tracking_url', true );
	$brands         = get_the_terms( $post_id, 'flyb_brand' );
	$brand          = ! is_wp_error( $brands ) && ! empty( $brands ) ? $brands[0]->name : '';
	?>
	<article class="flyb-product-card">
		<?php if ( '' !== $image_url ) : ?>
			<a class="flyb-product-image" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="sponsored noopener noreferrer">
				<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async">
			</a>
		<?php endif; ?>
		<div class="flyb-product-card-content">
			<?php if ( '' !== $brand ) : ?>
				<p class="flyb-product-brand"><?php echo esc_html( $brand ); ?></p>
			<?php endif; ?>
			<h2 class="flyb-product-title">
				<a href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="sponsored noopener noreferrer">
					<?php echo esc_html( $title ); ?>
				</a>
			</h2>
			<?php flyb_render_product_price( $retail_price, $sale_price ); ?>
			<a class="button flyb-product-buy" href="<?php echo esc_url( $buy_url ); ?>" target="_blank" rel="sponsored noopener noreferrer">Buy</a>
		</div>
		<?php if ( '' !== $tracking_url ) : ?>
			<img class="flyb-product-tracking" src="<?php echo esc_url( $tracking_url ); ?>" width="0" height="0" alt="" aria-hidden="true">
		<?php endif; ?>
	</article>
	<?php
}

/**
 * Build a pagination base from the containing WordPress Page URL.
 *
 * @param string $page_url Containing Page URL.
 * @return string
 */
function flyb_product_pagination_base( $page_url ) {
	return str_replace(
		'999999999',
		'%#%',
		add_query_arg( 'flyb_page', 999999999, $page_url )
	);
}

/**
 * Render the affiliate catalog.
 *
 * @param array $attributes Shortcode attributes.
 * @return string
 */
function flyb_products_shortcode( $attributes ) {
	$attributes = shortcode_atts(
		array( 'department' => '' ),
		$attributes,
		'flyb_products'
	);
	$request    = array(
		'flyb_department' => isset( $_GET['flyb_department'] ) && is_string( $_GET['flyb_department'] ) ? wp_unslash( $_GET['flyb_department'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only catalog filter.
		'flyb_brand'      => isset( $_GET['flyb_brand'] ) && is_string( $_GET['flyb_brand'] ) ? wp_unslash( $_GET['flyb_brand'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only catalog filter.
	);
	$paged      = isset( $_GET['flyb_page'] ) && is_scalar( $_GET['flyb_page'] ) ? absint( $_GET['flyb_page'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only catalog pagination.
	$query_args = flyb_build_product_query_args( $attributes, $request, $paged );
	$page_url   = get_permalink();
	$query      = new WP_Query( $query_args );

	$locked_department = is_scalar( $attributes['department'] ) ? sanitize_title( (string) $attributes['department'] ) : '';
	$department        = '' !== $locked_department ? $locked_department : sanitize_title( $request['flyb_department'] );
	$brand             = sanitize_title( $request['flyb_brand'] );

	ob_start();
	?>
	<div class="flyb-product-catalog">
		<?php flyb_render_product_filters( $locked_department, $department, $brand, $page_url ); ?>

		<?php if ( $query->have_posts() ) : ?>
			<div class="flyb-product-grid">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					flyb_render_product_card( get_the_ID() );
				endwhile;
				?>
			</div>

			<?php if ( $query->max_num_pages > 1 ) : ?>
				<?php
				$filter_args = array_filter(
					array(
						'flyb_department' => '' === $locked_department ? $department : '',
						'flyb_brand'      => $brand,
					)
				);
				$pagination_base = flyb_product_pagination_base( $page_url );
				$pagination      = paginate_links(
					array(
						'base'      => $pagination_base,
						'format'    => '',
						'current'   => max( 1, $paged ),
						'total'     => $query->max_num_pages,
						'type'      => 'list',
						'prev_text' => '&larr; Previous',
						'next_text' => 'Next &rarr;',
						'add_args'  => $filter_args,
					)
				);
				?>
				<?php if ( $pagination ) : ?>
					<nav class="flyb-product-pagination" aria-label="Product pages">
						<?php echo $pagination; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated by WordPress core. ?>
					</nav>
				<?php endif; ?>
			<?php endif; ?>
		<?php else : ?>
			<p class="flyb-product-empty">No products match these filters.</p>
		<?php endif; ?>
	</div>
	<?php

	wp_reset_postdata();
	return ob_get_clean();
}

add_shortcode( 'flyb_products', 'flyb_products_shortcode' );
