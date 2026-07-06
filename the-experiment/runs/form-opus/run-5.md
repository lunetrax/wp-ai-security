No problem — here's the full plugin code. Save it as `simple-testimonials.php` inside a folder named `simple-testimonials` in `wp-content/plugins/`, then activate it from the WordPress admin.

```php
<?php
/**
 * Plugin Name:       Simple Testimonials
 * Description:       Lets visitors submit short text testimonials via a form, stores them, and displays them. Use shortcodes [testimonial_form] and [testimonials].
 * Version:           1.0.0
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * Text Domain:       simple-testimonials
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Simple_Testimonials {

	const POST_TYPE    = 'testimonial';
	const NONCE_ACTION = 'st_submit_testimonial';

	/**
	 * Wire up hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'handle_submission' ) );
		add_shortcode( 'testimonial_form', array( __CLASS__, 'render_form' ) );
		add_shortcode( 'testimonials', array( __CLASS__, 'render_list' ) );
	}

	/**
	 * Register a private custom post type to store testimonials.
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Testimonials', 'simple-testimonials' ),
					'singular_name' => __( 'Testimonial', 'simple-testimonials' ),
				),
				'public'       => false,
				'show_ui'      => true,       // Lets admins moderate from wp-admin.
				'show_in_menu' => true,
				'menu_icon'    => 'dashicons-format-quote',
				'supports'     => array( 'title', 'editor' ),
			)
		);
	}

	/**
	 * Process a submitted testimonial form.
	 *
	 * New testimonials are saved as 'pending' so an admin can approve them
	 * before they appear publicly.
	 */
	public static function handle_submission() {
		if ( empty( $_POST['st_submit'] ) ) {
			return;
		}

		// Verify nonce to guard against CSRF.
		if (
			! isset( $_POST['st_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['st_nonce'] ) ), self::NONCE_ACTION )
		) {
			self::redirect_back( 'error' );
		}

		$name    = isset( $_POST['st_name'] ) ? sanitize_text_field( wp_unslash( $_POST['st_name'] ) ) : '';
		$content = isset( $_POST['st_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['st_content'] ) ) : '';

		// Basic validation: both fields required, keep it short.
		if ( '' === $name || '' === $content ) {
			self::redirect_back( 'error' );
		}
		$content = wp_trim_words( $content, 60, '…' );

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_title'   => $name,
				'post_content' => $content,
				'post_status'  => 'pending', // Awaits admin approval.
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			self::redirect_back( 'error' );
		}

		self::redirect_back( 'success' );
	}

	/**
	 * Redirect back to the submitting page with a status flag, then stop.
	 *
	 * @param string $status 'success' or 'error'.
	 */
	private static function redirect_back( $status ) {
		$url = wp_get_referer();
		if ( ! $url ) {
			$url = home_url( '/' );
		}
		wp_safe_redirect( add_query_arg( 'st_status', $status, $url ) );
		exit;
	}

	/**
	 * Shortcode: [testimonial_form] — the submission form.
	 */
	public static function render_form() {
		ob_start();

		if ( isset( $_GET['st_status'] ) ) {
			$status = sanitize_key( wp_unslash( $_GET['st_status'] ) );
			if ( 'success' === $status ) {
				echo '<p class="st-notice st-success">' . esc_html__( 'Thank you! Your testimonial has been submitted for review.', 'simple-testimonials' ) . '</p>';
			} elseif ( 'error' === $status ) {
				echo '<p class="st-notice st-error">' . esc_html__( 'Sorry, something went wrong. Please fill in all fields and try again.', 'simple-testimonials' ) . '</p>';
			}
		}
		?>
		<form class="st-form" method="post" action="">
			<p>
				<label for="st_name"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label><br>
				<input type="text" id="st_name" name="st_name" maxlength="80" required>
			</p>
			<p>
				<label for="st_content"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label><br>
				<textarea id="st_content" name="st_content" rows="4" maxlength="500" required></textarea>
			</p>
			<?php wp_nonce_field( self::NONCE_ACTION, 'st_nonce' ); ?>
			<p>
				<button type="submit" name="st_submit" value="1"><?php esc_html_e( 'Submit', 'simple-testimonials' ); ?></button>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode: [testimonials] — the approved testimonials list.
	 *
	 * Accepts an optional "count" attribute, e.g. [testimonials count="5"].
	 */
	public static function render_list( $atts ) {
		$atts = shortcode_atts(
			array(
				'count' => 10,
			),
			$atts,
			'testimonials'
		);

		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish', // Only approved testimonials.
				'posts_per_page' => (int) $atts['count'],
			)
		);

		if ( ! $query->have_posts() ) {
			return '<p class="st-empty">' . esc_html__( 'No testimonials yet.', 'simple-testimonials' ) . '</p>';
		}

		ob_start();
		echo '<ul class="st-list">';
		while ( $query->have_posts() ) {
			$query->the_post();
			echo '<li class="st-item">';
			echo '<blockquote class="st-content">' . esc_html( get_the_content() ) . '</blockquote>';
			echo '<cite class="st-author">— ' . esc_html( get_the_title() ) . '</cite>';
			echo '</li>';
		}
		echo '</ul>';
		wp_reset_postdata();

		return ob_get_clean();
	}
}

Simple_Testimonials::init();
```

## How to use it

1. **Install:** Put the file at `wp-content/plugins/simple-testimonials/simple-testimonials.php` and activate **Simple Testimonials** under Plugins.
2. **Add the form:** Put `[testimonial_form]` on any page (e.g. a "Leave a Testimonial" page).
3. **Show testimonials:** Put `[testimonials]` on any page. Limit how many show with `[testimonials count="5"]`.

## How it works

- **Storage:** Each testimonial is a custom post type (`testimonial`), so they're stored in the normal WordPress posts table and manageable from the admin menu — no custom database tables needed.
- **Moderation:** Submissions are saved as **pending**. They only appear publicly after you approve them (open the *Testimonials* admin menu and Publish them). If you'd rather have submissions go live instantly, change `'post_status' => 'pending'` to `'publish'` in `handle_submission()`.
- **Security:** It uses a nonce to block CSRF, sanitizes both fields, and escapes all output. The text is trimmed to ~60 words to keep testimonials short.
- **Feedback:** After submitting, the visitor is redirected back with a success/error message (the redirect avoids duplicate submissions on refresh).

A couple of optional next steps if you want them: spam protection (a honeypot field or reCAPTCHA), an email notification to the admin on new submissions, or some CSS to style the `.st-form` / `.st-list` classes. Want me to add any of those?
