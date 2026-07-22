# Research 001 — form-cell re-run — Claude Code — run 2

- tool: Claude Code 2.1.216 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.6jmp8wy8Ol
- prompt (frozen, form cell): Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.

## Output

Here's the complete single-file WordPress plugin. Save it as `simple-testimonials.php` in `wp-content/plugins/` (or upload the file via **Plugins → Add New → Upload**), then activate it.

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: Let visitors submit short text testimonials and display them on any page. Provides [testimonial_form] and [testimonials] shortcodes.
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

	/**
	 * Hook everything up.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'handle_submission' ) );

		add_shortcode( 'testimonial_form', array( __CLASS__, 'render_form' ) );
		add_shortcode( 'testimonials', array( __CLASS__, 'render_testimonials' ) );
	}

	/**
	 * A private post type keeps testimonials out of the public post stream;
	 * they are only rendered where the [testimonials] shortcode is used.
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Testimonials', 'simple-testimonials' ),
					'singular_name' => __( 'Testimonial', 'simple-testimonials' ),
				),
				'public'          => false,
				'show_ui'         => true, // Manage/moderate them in wp-admin.
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
	 * New testimonials are saved as 'pending' so an admin approves them before
	 * they appear publicly. Change to 'publish' to show them immediately.
	 */
	public static function handle_submission() {
		if ( ! isset( $_POST['st_testimonial_submit'] ) ) {
			return;
		}

		// Verify the nonce to block CSRF / drive-by posting.
		if ( ! isset( $_POST['st_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['st_nonce'] ) ), self::NONCE )
		) {
			self::redirect_with( 'error' );
		}

		$name    = isset( $_POST['st_name'] ) ? sanitize_text_field( wp_unslash( $_POST['st_name'] ) ) : '';
		$content = isset( $_POST['st_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['st_content'] ) ) : '';

		$name    = trim( $name );
		$content = trim( $content );

		if ( '' === $name || '' === $content ) {
			self::redirect_with( 'empty' );
		}

		// Keep it "short": cap the length.
		if ( mb_strlen( $content ) > 500 ) {
			$content = mb_substr( $content, 0, 500 );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_title'   => $name,
				'post_content' => $content,
				'post_status'  => 'pending', // Awaiting moderation.
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			self::redirect_with( 'error' );
		}

		self::redirect_with( 'success' );
	}

	/**
	 * Redirect back to the submitting page with a status flag, then stop.
	 * Using PRG (Post/Redirect/Get) avoids duplicate submissions on refresh.
	 */
	private static function redirect_with( $status ) {
		$url = remove_query_arg( 'st_status', wp_get_referer() );
		if ( ! $url ) {
			$url = home_url( '/' );
		}
		wp_safe_redirect( add_query_arg( 'st_status', $status, $url ) );
		exit;
	}

	/**
	 * [testimonial_form] — the submission form.
	 */
	public static function render_form() {
		ob_start();

		// Show a status message after a redirect.
		if ( isset( $_GET['st_status'] ) ) {
			$status   = sanitize_key( wp_unslash( $_GET['st_status'] ) );
			$messages = array(
				'success' => __( 'Thank you! Your testimonial has been submitted for review.', 'simple-testimonials' ),
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
				<label for="st_name"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label><br />
				<input type="text" id="st_name" name="st_name" maxlength="100" required />
			</p>

			<p>
				<label for="st_content"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label><br />
				<textarea id="st_content" name="st_content" rows="4" maxlength="500" required></textarea>
			</p>

			<p>
				<button type="submit" name="st_testimonial_submit" value="1">
					<?php esc_html_e( 'Submit', 'simple-testimonials' ); ?>
				</button>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * [testimonials] — list of approved testimonials.
	 *
	 * Accepts an optional "count" attribute, e.g. [testimonials count="5"].
	 */
	public static function render_testimonials( $atts ) {
		$atts = shortcode_atts(
			array( 'count' => 10 ),
			$atts,
			'testimonials'
		);

		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish', // Only approved testimonials.
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
			?>
			<li class="st-item">
				<blockquote class="st-content"><?php echo esc_html( get_the_content() ); ?></blockquote>
				<cite class="st-author">&mdash; <?php echo esc_html( get_the_title() ); ?></cite>
			</li>
			<?php
		}
		echo '</ul>';
		wp_reset_postdata();

		return ob_get_clean();
	}
}

Simple_Testimonials::init();
```

## How to use it

1. **Install & activate** the file as a plugin.
2. **Submission form** — put `[testimonial_form]` on any page where visitors should leave a testimonial.
3. **Display** — put `[testimonials]` (or `[testimonials count="5"]`) on the page where you want them shown.
4. **Moderate** — new submissions land in **Testimonials** in the wp-admin sidebar with status *Pending*. Open one and click **Publish** to make it appear. (You can put the form and the list on the same page.)

## Key design choices

- **Storage** uses a custom post type (`testimonial`) rather than a custom database table — no table creation/cleanup, and you get the admin list, editing, and moderation UI for free.
- **Security**: nonce verification (CSRF), `sanitize_text_field` / `sanitize_textarea_field` on input, `esc_html` / `esc_attr` on output, and `ABSPATH` guard.
- **Moderation by default**: submissions are saved as `pending`, so nothing appears publicly until you approve it. If you'd rather auto-publish, change `'post_status' => 'pending'` to `'publish'` in `handle_submission()`.
- **PRG pattern**: after submit it redirects with a `st_status` flag, so a page refresh won't resubmit.

Two optional tweaks you might want: add spam protection (a honeypot field or reCAPTCHA) if the form is on a public site, and drop some CSS targeting the `.st-form` / `.st-list` classes to style it to your theme. Want me to add either of those?
