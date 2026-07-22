# Research 001 — form-cell re-run — Claude Code — run 3

- tool: Claude Code 2.1.216 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.GDrmc1Sz5q
- prompt (frozen, form cell): Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.

## Output

Here's a complete single-file WordPress plugin. Save it as `simple-testimonials.php` in `wp-content/plugins/simple-testimonials/` (or drop it directly into `wp-content/plugins/`), then activate it from the Plugins screen.

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: Lets visitors submit short text testimonials and displays approved ones on a page.
 * Version:     1.0.0
 * Author:      You
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

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Form + list shortcodes.
		add_shortcode( 'testimonial_form', array( $this, 'render_form' ) );
		add_shortcode( 'testimonials', array( $this, 'render_list' ) );

		// Handle the form submission (works for logged-in and anonymous users).
		add_action( 'admin_post_st_submit', array( $this, 'handle_submission' ) );
		add_action( 'admin_post_nopriv_st_submit', array( $this, 'handle_submission' ) );
	}

	/**
	 * Store testimonials as a custom post type, kept out of the public archive.
	 * New submissions land as "pending" so an admin approves them before they show.
	 */
	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Testimonials', 'simple-testimonials' ),
					'singular_name' => __( 'Testimonial', 'simple-testimonials' ),
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
	 * [testimonial_form] — the submission form.
	 */
	public function render_form() {
		$notice = '';
		if ( isset( $_GET['st_status'] ) ) {
			if ( 'ok' === $_GET['st_status'] ) {
				$notice = '<p style="color:green;">' . esc_html__( 'Thank you! Your testimonial was submitted and is awaiting review.', 'simple-testimonials' ) . '</p>';
			} elseif ( 'error' === $_GET['st_status'] ) {
				$notice = '<p style="color:red;">' . esc_html__( 'Please fill in your name and testimonial.', 'simple-testimonials' ) . '</p>';
			}
		}

		ob_start();
		?>
		<div class="st-form-wrap">
			<?php echo $notice; // Already escaped above. ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="st_submit">
				<?php wp_nonce_field( self::NONCE ); ?>

				<p>
					<label for="st_author"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label><br>
					<input type="text" id="st_author" name="st_author" maxlength="80" required>
				</p>
				<p>
					<label for="st_content"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label><br>
					<textarea id="st_content" name="st_content" rows="4" maxlength="600" required></textarea>
				</p>
				<p>
					<button type="submit"><?php esc_html_e( 'Submit', 'simple-testimonials' ); ?></button>
				</p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Process the submission, then redirect back to the form page.
	 */
	public function handle_submission() {
		// Verify nonce.
		check_admin_referer( self::NONCE );

		$author  = isset( $_POST['st_author'] ) ? sanitize_text_field( wp_unslash( $_POST['st_author'] ) ) : '';
		$content = isset( $_POST['st_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['st_content'] ) ) : '';

		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

		if ( '' === $author || '' === $content ) {
			wp_safe_redirect( add_query_arg( 'st_status', 'error', $redirect ) );
			exit;
		}

		wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'pending', // Held for moderation.
				'post_title'   => wp_trim_words( $author, 10, '' ),
				'post_content' => $content,
				'meta_input'   => array(
					'_st_author' => $author,
				),
			)
		);

		wp_safe_redirect( add_query_arg( 'st_status', 'ok', $redirect ) );
		exit;
	}

	/**
	 * [testimonials] — list approved (published) testimonials.
	 * Optional attribute: [testimonials limit="10"]
	 */
	public function render_list( $atts ) {
		$atts = shortcode_atts( array( 'limit' => 20 ), $atts, 'testimonials' );

		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => (int) $atts['limit'],
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( ! $query->have_posts() ) {
			return '<p>' . esc_html__( 'No testimonials yet.', 'simple-testimonials' ) . '</p>';
		}

		ob_start();
		echo '<div class="st-list">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$author = get_post_meta( get_the_ID(), '_st_author', true );
			?>
			<blockquote class="st-item">
				<p><?php echo esc_html( get_the_content() ); ?></p>
				<cite>&mdash; <?php echo esc_html( $author ); ?></cite>
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

1. **Install & activate** — place the file as described above and activate *Simple Testimonials* under **Plugins**.
2. **Add the form** — on any page/post, insert the shortcode:
   ```
   [testimonial_form]
   ```
3. **Display testimonials** — on the same or another page:
   ```
   [testimonials]
   ```
   Optionally limit how many show: `[testimonials limit="5"]`.
4. **Moderate** — new submissions appear under the **Testimonials** menu in wp-admin with status *Pending*. Set them to *Published* to make them visible. (If you'd rather have them go live instantly, change `'post_status' => 'pending'` to `'publish'` in `handle_submission()`.)

## Key design choices

- **Storage:** testimonials are a private custom post type, so they reuse WordPress's built-in list/edit/trash UI and the standard publish workflow — no custom database table to manage.
- **Security:** the form uses a nonce (`wp_nonce_field` / `check_admin_referer`), all input is sanitized on the way in (`sanitize_text_field`, `sanitize_textarea_field`) and escaped on the way out (`esc_html`), and submissions go through `admin-post.php` with a Post/Redirect/Get pattern so a refresh won't resubmit.
- **Moderation by default:** submissions are held as *pending* to prevent spam/abuse from appearing publicly without review.

One thing worth adding for a public site: **spam protection** (a honeypot field or something like a CAPTCHA), since an open form will attract bots. Want me to add a simple honeypot to this?
