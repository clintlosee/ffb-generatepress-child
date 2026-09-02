<?php
/**
 * Theme widgets.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return cached IDs for the Most Popular widget.
 *
 * @return int[]
 */
function flyb_get_popular_post_ids() {
	$cache_key = 'flyb_popular_post_ids';
	$post_ids  = get_transient( $cache_key );

	if ( false !== $post_ids ) {
		return array_map( 'absint', (array) $post_ids );
	}

	$popular_query = new WP_Query(
		array(
			'posts_per_page' => 5,
			'post_status'    => 'publish',
			'orderby'        => 'comment_count',
			'order'          => 'DESC',
			'date_query'     => array(
				array( 'after' => '90 days ago' ),
			),
			'fields'         => 'ids',
		)
	);
	$post_ids = $popular_query->posts;
	wp_reset_postdata();

	if ( empty( $post_ids ) ) {
		$recent_query = new WP_Query(
			array(
				'posts_per_page' => 5,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);
		$post_ids = $recent_query->posts;
		wp_reset_postdata();
	}

	$post_ids = array_map( 'absint', $post_ids );
	set_transient( $cache_key, $post_ids, HOUR_IN_SECONDS );

	return $post_ids;
}

/**
 * Invalidate the Most Popular post cache after post changes.
 */
function flyb_clear_popular_posts_cache() {
	delete_transient( 'flyb_popular_post_ids' );
}
add_action( 'save_post', 'flyb_clear_popular_posts_cache' );
add_action( 'deleted_post', 'flyb_clear_popular_posts_cache' );
add_action( 'trashed_post', 'flyb_clear_popular_posts_cache' );

/**
 * Info-style Most Popular sidebar widget.
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
		global $post;

		$title    = ! empty( $instance['title'] ) ? $instance['title'] : 'Most Popular';
		$post_ids = flyb_get_popular_post_ids();

		echo $args['before_widget'];
		echo '<div class="flyb-popular-posts">';
		echo '<h3>' . esc_html( $title ) . '</h3>';

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required for template tags below.
			if ( ! $post ) {
				continue;
			}

			setup_postdata( $post );
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
		$instance          = array();
		$instance['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		return $instance;
	}
}

/**
 * Register child-theme widgets.
 */
function flyb_register_widgets() {
	register_widget( 'Flyb_Popular_Posts_Widget' );
}
add_action( 'widgets_init', 'flyb_register_widgets' );
