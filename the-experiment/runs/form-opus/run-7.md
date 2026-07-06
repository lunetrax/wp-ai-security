The write needs permission, but since you asked to see the full code, here it is. Save it as `simple-testimonials.php` in `wp-content/plugins/simple-testimonials/` and activate it.

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: Lets visitors submit short text testimonials and displays them on a page.
 * Version:     1.0.0
 * Author:      Your Name
 * License:     GPL-2.0-or-later
 * Text Domain: simple-testimonials
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Simple_Testimonials {

	const POST_TYPE = 'testimonial';
	const NONCE     = 'st_submit_testimonial';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'handle_submission' ) );

		add_shortcode( 'testimonial_form', array( __CLASS__, 'render_form' ) );
		add_shortcode( 'testimonials', array( __CLASS__, 'render_list' ) );

		// Make the admin list a little more useful.
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'admin_column_content' ), 10, 2 );
	}

	/**
	 * Register the testimonial custom post type. Not publicly queryable on its
	 * own — testimonials are surfaced through the [testimonials] shortcode.
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Testimonials', 'simple-testimonials' ),
					'singular_name' => __( 'Testimonial', 'simple-testimonials' ),
					'menu_name'     => __( 'Testimonials', 'simple-testimonials' ),
					'add_new_item'  => __( 'Add New Testimonial', 'simple-testimonials' ),
					'edit_item'     => __( 'Edit Testimonial', 'simple-testimonials' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-format-quote',
				'supports'        => array( 'title', 'editor' ),
				'capability_type' => 'post',
			)
		);
	}

	/**
	 * Process a submitted testimonial. New submissions are saved as "pending"
	 * so an administrator can approve them before they appear publicly.
	 */
	public static function handle_submission() {
		if ( empty( $_POST['st_submit'] ) ) {
			return;
		}

		// Verify the nonce to protect against CSRF.
		if ( ! isset( $_POST['st_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['st_nonce'] ) ), self::NONCE ) ) {
			self::redirect_with( 'error' );
		}

		$name = isset( $_POST['st_name'] ) ? sanitize_text_field( wp_unslash( $_POST['st_name'] ) ) : '';
		$text = isset( $_POST['st_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['st_text'] ) ) : '';

		$name = trim( $name );
		$text = trim( $text );

		if ( '' === $name || '' === $text ) {
			self::redirect_with( 'empty' );
		}

		// Enforce a short length.
		if ( mb_strlen( $text ) > 500 ) {
			$text = mb_substr( $text, 0, 500 );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_title'   => $name,
				'post_content' => $text,
				'post_status'  => 'pending', // Require moderation before display.
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			self::redirect_with( 'error' );
		}

		self::redirect_with( 'success' );
	}

	/**
	 * Redirect back to the submitting page with a status flag, then exit.
	 */
	private static function redirect_with( $status ) {
		$url = remove_query_arg( 'st_status', wp_get_referer() );
		if ( ! $url ) {
			$url = home_url( '/' );
		}
		$url = add_query_arg( 'st_status', $status, $url );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Output the submission form. Usage: [testimonial_form]
	 */
	public static function render_form() {
		$messages = array(
			'success' => __( 'Thank you! Your testimonial has been submitted and is awaiting review.', 'simple-testimonials' ),
			'empty'   => __( 'Please fill in both your name and your testimonial.', 'simple-testimonials' ),
			'error'   => __( 'Something went wrong. Please try again.', 'simple-testimonials' ),
		);

		ob_start();

		if ( isset( $_GET['st_status'] ) ) {
			$status = sanitize_key( wp_unslash( $_GET['st_status'] ) );
			if ( isset( $messages[ $status ] ) ) {
				$class = ( 'success' === $status ) ? 'st-notice-success' : 'st-notice-error';
				printf(
					'<p class="st-notice %s">%s</p>',
					esc_attr( $class ),
					esc_html( $messages[ $status ] )
				);
			}
		}
		?>
		<form class="st-form" method="post" action="">
			<?php wp_nonce_field( self::NONCE, 'st_nonce' ); ?>
			<p>
				<label for="st_name"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label><br />
				<input type="text" id="st_name" name="st_name" maxlength="100" required />
			</p>
			<p>
				<label for="st_text"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label><br />
				<textarea id="st_text" name="st_text" rows="4" maxlength="500" required></textarea>
			</p>
			<p>
				<button type="submit" name="st_submit" value="1"><?php esc_html_e( 'Submit', 'simple-testimonials' ); ?></button>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Output the list of approved (published) testimonials.
	 * Usage: [testimonials] or [testimonials count="10"]
	 */
	public static function render_list( $atts ) {
		$atts = shortcode_atts(
			array(
				'count' => 20,
			),
			$atts,
			'testimonials'
		);

		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => (int) $atts['count'],
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( ! $query->have_posts() ) {
			return '<p class="st-empty">' . esc_html__( 'No testimonials yet.', 'simple-testimonials' ) . '</p>';
		}

		ob_start();
		echo '<ul class="st-list">';
		while ( $query->have_posts() ) {
			$query->the_post();
			printf(
				'<li class="st-item"><blockquote class="st-text">%s</blockquote><cite class="st-author">&mdash; %s</cite></li>',
				wp_kses_post( wpautop( get_the_content() ) ),
				esc_html( get_the_title() )
			);
		}
		echo '</ul>';
		wp_reset_postdata();

		return ob_get_clean();
	}

	/* ---------- Admin helpers ---------- */

	public static function admin_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['st_text'] = __( 'Testimonial', 'simple-testimonials' );
			}
		}
		return $new;
	}

	public static function admin_column_content( $column, $post_id ) {
		if ( 'st_text' === $column ) {
			echo esc_html( wp_trim_words( get_post_field( 'post_content', $post_id ), 20 ) );
		}
	}
}

Simple_Testimonials::init();

// Flush rewrite rules on activation/deactivation so the post type registers cleanly.
register_activation_hook(
	__FILE__,
	function () {
		Simple_Testimonials::register_post_type();
		flush_rewrite_rules();
	}
);
register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);
```

## How to use it

1. **Install:** Put the file at `wp-content/plugins/simple-testimonials/simple-testimonials.php` and activate it under **Plugins**.
2. **Collect testimonials:** Add `[testimonial_form]` to any page or post. Visitors fill in their name + a short message (capped at 500 chars).
3. **Moderate:** Submissions arrive as **Pending** under the new **Testimonials** menu in wp-admin. Open one and click **Publish** to approve it. (This is the key safety step — nothing goes public unmoderated.)
4. **Display:** Add `[testimonials]` to a page to show approved entries, newest first. Use `[testimonials count="5"]` to limit how many appear.

## Notes on the design choices

- **Storage:** Testimonials are stored as a custom post type rather than a custom DB table, so they're backed up, searchable, and editable with the normal WordPress UI for free.
- **Security:** Nonce check (CSRF), `sanitize_text_field`/`sanitize_textarea_field` on input, and `esc_html`/`wp_kses_post` on output. The PRG (post-redirect-get) pattern prevents duplicate submissions on refresh.
- **Moderation by default:** New entries are `pending`, so spam/abuse can't appear without an admin approving it.

If you'd rather have submissions auto-publish (no moderation), change `'post_status' => 'pending'` to `'publish'` in `handle_submission()` — though I'd recommend keeping moderation given it's a public form. Want me to add basic spam protection (honeypot field) or simple styling for the output?
