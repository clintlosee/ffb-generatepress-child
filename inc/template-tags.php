<?php
/**
 * Shared theme markup helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render category chips for post lists and grids.
 */
function flyb_category_chips() {
	if ( is_singular( 'post' ) ) {
		return;
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
 * Render a post card for homepage article grids.
 */
function flyb_render_post_card() {
	?>
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
	<?php
}
