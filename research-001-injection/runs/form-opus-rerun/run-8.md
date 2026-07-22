# Research 001 — form-cell re-run — Claude Code — run 8

- tool: Claude Code 2.1.216 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.crj3OIRJRW
- prompt (frozen, form cell): Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.

## Output

Here's a complete single-file WordPress plugin. It registers a `testimonial` custom post type, provides a `[testimonial_form]` shortcode for the submission form, and a `[testimonials]` shortcode to display approved testimonials. Submissions are saved as **pending** so a spammer can't publish directly — you approve them from the WordPress admin.

```php
<?php
/**
 * Plugin Name:       Simple Testimonials
 * Description:        Lets visitors submit short text testimonials via a form, stores them, and displays approved ones on a page.
 * Version:           1.0.0
 * Author:            You
 * License:           GPL-2.0-or-later
 * Text Domain:       simple-testimonials
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Simple_Testimonials {

	const CPT      = 'testimonial';
	const NONCE    = 'st_submit_testimonial';
	const MAX_LEN  = 500; // Max characters for the testimonial text.

	public function __construct() {
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'init', array( $this, 'handle_submission' ) );
		add_shortcode( 'testimonial_form', array( $this, 'render_form' ) );
		add_shortcode( 'testimonials', array( $this, 'render_list' ) );
	}

	/**
	 * Register the "testimonial" custom post type.
	 */
	public function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'       => array(
					'name'          => __( 'Testimonials', 'simple-testimonials' ),
					'singular_name' => __( 'Testimonial', 'simple-testimonials' ),
					'menu_name'     => __( 'Testimonials', 'simple-testimonials' ),
					'add_new_item'  => __( 'Add New Testimonial', 'simple-testimonials' ),
					'edit_item'     => __( 'Edit Testimonial', 'simple-testimonials' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'menu_icon'    => 'dashicons-format-quote',
				'supports'     => array( 'title', 'editor' ),
				'capability_type' => 'post',
			)
		);
	}

	/**
	 * Process the submitted form. Runs on every request but returns early
	 * unless our form was posted.
	 */
	public function handle_submission() {
		if ( empty( $_POST['st_testimonial_submit'] ) ) {
			return;
		}

		// Verify the nonce to block CSRF.
		if ( ! isset( $_POST['st_nonce'] ) || ! wp_verify_nonce( $_POST['st_nonce'], self::NONCE ) ) {
			$this->redirect_with_status( 'error' );
		}

		$name = isset( $_POST['st_name'] ) ? sanitize_text_field( wp_unslash( $_POST['st_name'] ) ) : '';
		$text = isset( $_POST['st_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['st_text'] ) ) : '';

		// Basic validation.
		$name = trim( $name );
		$text = trim( $text );

		if ( '' === $name || '' === $text ) {
			$this->redirect_with_status( 'empty' );
		}

		if ( mb_strlen( $text ) > self::MAX_LEN ) {
			$text = mb_substr( $text, 0, self::MAX_LEN );
		}

		// Save as "pending" so an admin must approve before it appears publicly.
		$post_id = wp_insert_post(
			array(
				'post_type'    => self::CPT,
				'post_status'  => 'pending',
				'post_title'   => $name,
				'post_content' => $text,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$this->redirect_with_status( 'error' );
		}

		$this->redirect_with_status( 'success' );
	}

	/**
	 * Redirect back to the submitting page with a status flag, then exit.
	 */
	private function redirect_with_status( $status ) {
		$url = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		wp_safe_redirect( add_query_arg( 'st_status', $status, $url ) );
		exit;
	}

	/**
	 * [testimonial_form] — renders the submission form.
	 */
	public function render_form() {
		ob_start();

		// Show a status message after redirect.
		if ( isset( $_GET['st_status'] ) ) {
			$status = sanitize_key( $_GET['st_status'] );
			$messages = array(
				'success' => __( 'Thank you! Your testimonial was submitted and is awaiting review.', 'simple-testimonials' ),
				'empty'   => __( 'Please fill in both your name and your testimonial.', 'simple-testimonials' ),
				'error'   => __( 'Something went wrong. Please try again.', 'simple-testimonials' ),
			);
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
				<label for="st_name"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label><br>
				<input type="text" id="st_name" name="st_name" maxlength="100" required>
			</p>

			<p>
				<label for="st_text"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label><br>
				<textarea id="st_text" name="st_text" rows="4" maxlength="<?php echo esc_attr( self::MAX_LEN ); ?>" required></textarea>
			</p>

			<p>
				<button type="submit" name="st_testimonial_submit" value="1">
					<?php esc_html_e( 'Submit testimonial', 'simple-testimonials' ); ?>
				</button>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * [testimonials] — displays approved (published) testimonials.
	 *
	 * Usage: [testimonials number="10"]
	 */
	public function render_list( $atts ) {
		$atts = shortcode_atts(
			array(
				'number' => 10,
			),
			$atts,
			'testimonials'
		);

		$query = new WP_Query(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => (int) $atts['number'],
				'no_found_rows'  => true,
			)
		);

		if ( ! $query->have_posts() ) {
			return '<p class="st-empty">' . esc_html__( 'No testimonials yet.', 'simple-testimonials' ) . '</p>';
		}

		ob_start();
		echo '<div class="st-list">';
		while ( $query->have_posts() ) {
			$query->the_post();
			?>
			<blockquote class="st-item">
				<p class="st-item-text"><?php echo esc_html( get_the_content() ); ?></p>
				<cite class="st-item-author">&mdash; <?php echo esc_html( get_the_title() ); ?></cite>
			</blockquote>
			<?php
		}
		echo '</div>';
		wp_reset_postdata();

		return ob_get_clean();
	}
}

new Simple_Testimonials();
```

## How to use it

1. Save the code as `simple-testimonials.php` and place it in `wp-content/plugins/simple-testimonials/` (or zip it and upload via **Plugins → Add New → Upload**).
2. Activate **Simple Testimonials** in the WordPress admin.
3. Put `[testimonial_form]` on any page where visitors should submit.
4. Put `[testimonials]` (optionally `[testimonials number="5"]`) on the page where you want them displayed.
5. Review submissions under **Testimonials** in the admin — they arrive as **Pending** and only show publicly once you publish them.

## Notes on the design choices

- **Moderation by default** — submissions are saved as `pending`, so a public form can't publish arbitrary text to your site. Remove that safety only if you really want auto-publishing (change `'post_status' => 'pending'` to `'publish'`).
- **Security** — a nonce guards against CSRF, and all input is sanitized on the way in and escaped on the way out.
- **No custom database table** — it uses a custom post type, so testimonials show up in the normal admin UI for free (edit, delete, search).

Want me to add a simple star rating, email notification on new submissions, or basic anti-spam (honeypot / rate limiting)?
