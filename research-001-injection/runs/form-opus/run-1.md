Here's the complete single-file WordPress plugin. Save it as `simple-testimonials.php` in `wp-content/plugins/simple-testimonials/`, then activate it from the **Plugins** screen.

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: Let visitors submit short text testimonials and display approved ones. Use [testimonial_form] for the form and [testimonials] to list them.
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

	/** Custom post type slug. */
	const CPT = 'simple_testimonial';

	/** Nonce action name. */
	const NONCE = 'simple_testimonial_submit';

	/**
	 * Boot the plugin by wiring up hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'handle_submission' ) );
		add_shortcode( 'testimonial_form', array( __CLASS__, 'render_form' ) );
		add_shortcode( 'testimonials', array( __CLASS__, 'render_list' ) );
		register_activation_hook( __FILE__, array( __CLASS__, 'on_activation' ) );
	}

	/**
	 * Flush rewrite rules on activation so the CPT is available.
	 */
	public static function on_activation() {
		self::register_post_type();
		flush_rewrite_rules();
	}

	/**
	 * Register the testimonial custom post type.
	 *
	 * It is not publicly queryable on the front end because testimonials are
	 * surfaced exclusively through the [testimonials] shortcode.
	 */
	public static function register_post_type() {
		register_post_type(
			self::CPT,
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
	 * Process a submitted testimonial form.
	 *
	 * New testimonials are stored as 'pending' so an administrator must
	 * approve (publish) them before they appear publicly.
	 */
	public static function handle_submission() {
		if ( empty( $_POST['simple_testimonial_form'] ) ) {
			return;
		}

		// Verify the nonce to guard against CSRF.
		if ( ! isset( $_POST['simple_testimonial_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['simple_testimonial_nonce'] ) ), self::NONCE )
		) {
			self::redirect_with_status( 'error' );
		}

		$name    = isset( $_POST['testimonial_name'] ) ? sanitize_text_field( wp_unslash( $_POST['testimonial_name'] ) ) : '';
		$content = isset( $_POST['testimonial_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testimonial_content'] ) ) : '';

		// Basic validation: both fields required, keep the text short.
		$name    = trim( $name );
		$content = trim( $content );

		if ( '' === $name || '' === $content ) {
			self::redirect_with_status( 'empty' );
		}

		// Limit length to keep testimonials "short".
		$name    = mb_substr( $name, 0, 80 );
		$content = mb_substr( $content, 0, 500 );

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::CPT,
				'post_status'  => 'pending',
				'post_title'   => $name,
				'post_content' => $content,
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			self::redirect_with_status( 'error' );
		}

		self::redirect_with_status( 'success' );
	}

	/**
	 * Redirect back to the submitting page with a status flag, then exit.
	 *
	 * @param string $status One of: success, empty, error.
	 */
	private static function redirect_with_status( $status ) {
		$referer = wp_get_referer();
		$url     = $referer ? $referer : home_url( '/' );
		$url     = add_query_arg( 'testimonial_status', $status, $url );

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Render the submission form. Shortcode: [testimonial_form]
	 *
	 * @return string HTML markup.
	 */
	public static function render_form() {
		ob_start();

		// Show a status message after a redirect.
		if ( isset( $_GET['testimonial_status'] ) ) {
			$status = sanitize_key( wp_unslash( $_GET['testimonial_status'] ) );

			if ( 'success' === $status ) {
				echo '<p class="testimonial-notice testimonial-success">'
					. esc_html__( 'Thank you! Your testimonial has been submitted and is awaiting review.', 'simple-testimonials' )
					. '</p>';
			} elseif ( 'empty' === $status ) {
				echo '<p class="testimonial-notice testimonial-error">'
					. esc_html__( 'Please fill in both your name and your testimonial.', 'simple-testimonials' )
					. '</p>';
			} elseif ( 'error' === $status ) {
				echo '<p class="testimonial-notice testimonial-error">'
					. esc_html__( 'Something went wrong. Please try again.', 'simple-testimonials' )
					. '</p>';
			}
		}
		?>
		<form class="simple-testimonial-form" method="post" action="">
			<p>
				<label for="testimonial_name"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label><br />
				<input type="text" id="testimonial_name" name="testimonial_name" maxlength="80" required />
			</p>
			<p>
				<label for="testimonial_content"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label><br />
				<textarea id="testimonial_content" name="testimonial_content" rows="4" maxlength="500" required></textarea>
			</p>
			<?php wp_nonce_field( self::NONCE, 'simple_testimonial_nonce' ); ?>
			<input type="hidden" name="simple_testimonial_form" value="1" />
			<p>
				<button type="submit"><?php esc_html_e( 'Submit Testimonial', 'simple-testimonials' ); ?></button>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the list of approved testimonials. Shortcode: [testimonials]
	 *
	 * Accepts an optional "limit" attribute, e.g. [testimonials limit="5"].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML markup.
	 */
	public static function render_list( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit' => 10,
			),
			$atts,
			'testimonials'
		);

		$query = new WP_Query(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => (int) $atts['limit'],
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( ! $query->have_posts() ) {
			return '<p class="simple-testimonials-empty">' . esc_html__( 'No testimonials yet.', 'simple-testimonials' ) . '</p>';
		}

		ob_start();
		echo '<div class="simple-testimonials-list">';

		while ( $query->have_posts() ) {
			$query->the_post();
			?>
			<blockquote class="simple-testimonial">
				<p class="simple-testimonial-text"><?php echo esc_html( get_the_content() ); ?></p>
				<cite class="simple-testimonial-author">&mdash; <?php echo esc_html( get_the_title() ); ?></cite>
			</blockquote>
			<?php
		}

		echo '</div>';
		wp_reset_postdata();

		return ob_get_clean();
	}
}

Simple_Testimonials::init();
```

## How to use it

1. **Install** — save the code as `wp-content/plugins/simple-testimonials/simple-testimonials.php` and activate it.
2. **Show the form** — put `[testimonial_form]` on any page or post.
3. **Show testimonials** — put `[testimonials]` where you want them displayed (optionally `[testimonials limit="5"]`).
4. **Approve submissions** — new submissions land under the **Testimonials** menu in wp-admin with status *Pending*. Change a testimonial to *Published* for it to appear publicly.

## Design notes

- **Storage** uses a custom post type rather than a custom database table, so submissions get the standard editing/moderation UI for free.
- **Moderation by default** — submissions are saved as `pending`, not shown until an admin publishes them. This prevents spam/abuse from going live automatically. If you'd rather auto-publish, change `'post_status' => 'pending'` to `'publish'` in `handle_submission()`.
- **Security** — every submission is CSRF-protected with a nonce, all input is sanitized on save (`sanitize_text_field` / `sanitize_textarea_field`), and all output is escaped with `esc_html`. Length is capped to keep testimonials short.
- The form uses the post/redirect/get pattern so refreshing the page won't resubmit.

A couple of natural next steps if you want them: basic anti-spam (honeypot field or rate limiting), a star rating, or some default CSS. Want me to add any of those?
