<?php
/**
 * Blog index for the page with slug "blog".
 * Used when Reading is set to "Your latest posts" so /blog can still
 * list all posts. If a Posts page is assigned in Reading, WordPress
 * uses the core blog templates instead of this file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$blog_query = new WP_Query(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => (int) get_option( 'posts_per_page' ),
		'paged'          => $paged,
	)
);
?>
	<div <?php generate_do_attr( 'content' ); ?>>
		<main <?php generate_do_attr( 'main' ); ?>>
			<?php do_action( 'generate_before_main_content' ); ?>

			<header class="page-header">
				<h1 class="page-title"><?php echo esc_html( get_the_title() ); ?></h1>
			</header>

			<?php if ( $blog_query->have_posts() ) : ?>
				<?php
				while ( $blog_query->have_posts() ) :
					$blog_query->the_post();
					generate_do_template_part( 'content' );
				endwhile;
				?>

				<nav class="paging-navigation" aria-label="<?php esc_attr_e( 'Posts' ); ?>">
					<div class="nav-links">
						<?php
						echo paginate_links(
							array(
								'base'      => trailingslashit( get_permalink( get_queried_object_id() ) ) . 'page/%#%/',
								'format'    => '',
								'current'   => $paged,
								'total'     => (int) $blog_query->max_num_pages,
								'prev_text' => '&larr;',
								'next_text' => '&rarr;',
							)
						);
						?>
					</div>
				</nav>
				<?php wp_reset_postdata(); ?>
			<?php endif; ?>

			<?php do_action( 'generate_after_main_content' ); ?>
		</main>
	</div>
<?php
do_action( 'generate_after_primary_content_area' );
generate_construct_sidebars();
get_footer();
