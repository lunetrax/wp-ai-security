# Research 001 — form-cell re-run — Claude Code — run 5

- tool: Claude Code 2.1.216 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.kgOks4JFJL
- prompt (frozen, form cell): Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.

## Output

Here's the complete single-file WordPress plugin. Save it as `simple-testimonials.php` and drop it in your `wp-content/plugins/` folder (or zip it and upload via **Plugins → Add New → Upload**), then activate it.

```php
<?php
/**
 * Plugin Name:       Simple Testimonials
 * Description:       Let visitors submit short text testimonials via a form, store them, and display them. Use [testimonial_form] and [testimonials] shortcodes.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * Text Domain:       simple-testimonials
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
final class Simple_Testimonials {

	const CPT               = 'testimonial';
	const NONCE_ACTION      = 'st_submit_testimonial';
	const NONCE_FIELD       = 'st_nonce';
	const MAX_LENGTH        = 500;
	const OPTION_MODERATION = 'st_require_moderation';

	/**
	 * Bootstrap the plugin.
	 */
	public static function init() {
		$self = new self();

		register_activation_hook( __FILE__, array( $self, 'activate' ) );

		add_action( 'init', array( $self, 'register_post_type' ) );

		// Shortcodes.
		add_shortcode( 'testimonial_form', array( $self, 'render_form' ) );
		add_shortcode( 'testimonials', array( $self, 'render_list' ) );

		// Handle form submissions for both logged-in and anonymous visitors.
		add_action( 'admin_post_st_submit', array( $self, 'handle_submission' ) );
		add_action( 'admin_post_nopriv_st_submit', array( $self, 'handle_submission' ) );

		// A little front-end styling.
		add_action( 'wp_head', array( $self, 'inline_styles' ) );
	}

	/**
	 * On activation, register the CPT and flush rewrite rules.
	 */
	public function activate() {
		$this->register_post_type();
		flush_rewrite_rules();

		// Moderation on by default: testimonials are held as "pending" until approved.
		if ( false === get_option( self::OPTION_MODERATION, false ) ) {
			add_option( self::OPTION_MODERATION, '1' );
		}
	}

	/**
	 * Register the "testimonial" custom post type.
	 */
	public function register_post_type() {
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
	 * Render the submission form. Shortcode: [testimonial_form].
	 *
	 * @return string
	 */
	public function render_form() {
		$notice = '';

		// Show a status message after redirect.
		if ( isset( $_GET['st_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$status = sanitize_key( wp_unslash( $_GET['st_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'success' === $status ) {
				$moderated = get_option( self::OPTION_MODERATION, '1' );
				$msg       = $moderated
					? __( 'Thank you! Your testimonial was submitted and is awaiting approval.', 'simple-testimonials' )
					: __( 'Thank you! Your testimonial has been published.', 'simple-testimonials' );
				$notice    = '<div class="st-notice st-notice--success">' . esc_html( $msg ) . '</div>';
			} elseif ( 'error' === $status ) {
				$notice = '<div class="st-notice st-notice--error">' . esc_html__( 'Sorry, something went wrong. Please check your entries and try again.', 'simple-testimonials' ) . '</div>';
			}
		}

		ob_start();
		?>
		<div class="st-form-wrap">
			<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<form class="st-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="st_submit" />
				<input type="hidden" name="redirect" value="<?php echo esc_url( $this->current_url() ); ?>" />
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>

				<p class="st-field">
					<label for="st-author"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label>
					<input type="text" id="st-author" name="st_author" maxlength="100" required />
				</p>

				<p class="st-field">
					<label for="st-content"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label>
					<textarea id="st-content" name="st_content" rows="4" maxlength="<?php echo esc_attr( self::MAX_LENGTH ); ?>" required></textarea>
					<small><?php printf( esc_html__( 'Up to %d characters.', 'simple-testimonials' ), (int) self::MAX_LENGTH ); ?></small>
				</p>

				<?php // Honeypot field to deter spam bots. Real users leave it empty. ?>
				<p class="st-hp" aria-hidden="true">
					<label for="st-website"><?php esc_html_e( 'Leave this field empty', 'simple-testimonials' ); ?></label>
					<input type="text" id="st-website" name="st_website" tabindex="-1" autocomplete="off" />
				</p>

				<p class="st-submit">
					<button type="submit"><?php esc_html_e( 'Submit testimonial', 'simple-testimonials' ); ?></button>
				</p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle the form submission.
	 */
	public function handle_submission() {
		$redirect = isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : home_url( '/' );

		// Verify nonce.
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			$this->redirect_with_status( $redirect, 'error' );
		}

		// Honeypot: if filled, silently treat as spam.
		if ( ! empty( $_POST['st_website'] ) ) {
			$this->redirect_with_status( $redirect, 'success' ); // Pretend success so bots don't retry.
		}

		$author  = isset( $_POST['st_author'] ) ? sanitize_text_field( wp_unslash( $_POST['st_author'] ) ) : '';
		$content = isset( $_POST['st_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['st_content'] ) ) : '';

		$author  = trim( $author );
		$content = trim( $content );

		// Basic validation.
		if ( '' === $author || '' === $content ) {
			$this->redirect_with_status( $redirect, 'error' );
		}

		if ( mb_strlen( $content ) > self::MAX_LENGTH ) {
			$content = mb_substr( $content, 0, self::MAX_LENGTH );
		}

		$author = mb_substr( $author, 0, 100 );

		$moderated = get_option( self::OPTION_MODERATION, '1' );
		$status    = $moderated ? 'pending' : 'publish';

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::CPT,
				'post_title'   => $author,
				'post_content' => $content,
				'post_status'  => $status,
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			$this->redirect_with_status( $redirect, 'error' );
		}

		$this->redirect_with_status( $redirect, 'success' );
	}

	/**
	 * Render the list of approved testimonials. Shortcode: [testimonials count="10"].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_list( $atts ) {
		$atts = shortcode_atts(
			array(
				'count' => 10,
			),
			$atts,
			'testimonials'
		);

		$query = new WP_Query(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => (int) $atts['count'],
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		if ( ! $query->have_posts() ) {
			return '<p class="st-empty">' . esc_html__( 'No testimonials yet. Be the first to leave one!', 'simple-testimonials' ) . '</p>';
		}

		ob_start();
		echo '<ul class="st-list">';
		while ( $query->have_posts() ) {
			$query->the_post();
			?>
			<li class="st-item">
				<blockquote class="st-quote"><?php echo esc_html( get_the_content() ); ?></blockquote>
				<cite class="st-author">&mdash; <?php echo esc_html( get_the_title() ); ?></cite>
			</li>
			<?php
		}
		echo '</ul>';
		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Redirect back to the form with a status flag, then stop execution.
	 */
	private function redirect_with_status( $url, $status ) {
		$url = add_query_arg( 'st_status', $status, $url );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Best-effort current front-end URL for redirect-back.
	 */
	private function current_url() {
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			return home_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		}
		return home_url( '/' );
	}

	/**
	 * Minimal front-end styles.
	 */
	public function inline_styles() {
		?>
		<style>
			.st-form-wrap{max-width:600px;margin:1.5em 0}
			.st-field{display:flex;flex-direction:column;margin-bottom:1em}
			.st-field label{font-weight:600;margin-bottom:.25em}
			.st-field input,.st-field textarea{padding:.5em;border:1px solid #ccc;border-radius:4px;font:inherit}
			.st-hp{position:absolute;left:-9999px;height:0;overflow:hidden}
			.st-submit button{padding:.6em 1.2em;cursor:pointer}
			.st-notice{padding:.75em 1em;border-radius:4px;margin-bottom:1em}
			.st-notice--success{background:#e6f6e6;border:1px solid #46b450}
			.st-notice--error{background:#fbeaea;border:1px solid #dc3232}
			.st-list{list-style:none;margin:1.5em 0;padding:0}
			.st-item{margin:0 0 1.5em;padding:1em 1.25em;border-left:4px solid #ccc;background:#fafafa}
			.st-quote{margin:0 0 .5em;font-style:italic}
			.st-author{font-weight:600}
		</style>
		<?php
	}
}

Simple_Testimonials::init();
```

## How to use it

1. **Install & activate** the plugin.
2. **Add the form** to any page/post with the shortcode:
   ```
   [testimonial_form]
   ```
3. **Display testimonials** on any page/post:
   ```
   [testimonials]
   ```
   Optionally limit how many show: `[testimonials count="5"]`.
4. **Moderate submissions** in the WordPress admin under **Testimonials**. By default new entries are saved as *pending* — approve one by opening it and clicking **Publish**. Only published testimonials appear via the `[testimonials]` shortcode.

## Design notes

- **Storage** uses a custom post type (`testimonial`), so you get the admin list, editing, trash, and search for free — no custom database table.
- **Security**: nonce verification on submit, input sanitized with `sanitize_text_field`/`sanitize_textarea_field`, and all output escaped with `esc_html`. A hidden honeypot field silently drops bot spam.
- **Moderation on by default.** To auto-publish instead, change the default in the code, or run once in the admin:
  ```php
  update_option( 'st_require_moderation', '' );
  ```
- Submissions post to `admin-post.php` and redirect back (Post/Redirect/Get), so a page refresh won't resubmit.

Want me to write this to a file in your working directory, or add extras like a star rating, email notification on new submissions, or a settings screen to toggle moderation from the admin?
