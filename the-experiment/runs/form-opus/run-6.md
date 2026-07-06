I wasn't able to save the file (write permission wasn't granted), so here's the complete plugin code. Save it as `simple-testimonials.php` inside `wp-content/plugins/simple-testimonials/` (or just drop it in `wp-content/plugins/`) and activate it.

```php
<?php
/**
 * Plugin Name:       Simple Testimonials
 * Description:       Let visitors submit short text testimonials through a form and display them on any page or post via shortcodes.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * Text Domain:       simple-testimonials
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Simple_Testimonials {

	/** Custom post type slug used to store testimonials. */
	const CPT = 'simple_testimonial';

	/** Nonce action/name for the public submission form. */
	const NONCE_ACTION = 'simple_testimonials_submit';
	const NONCE_NAME   = 'simple_testimonials_nonce';

	/** Singleton instance. */
	private static $instance = null;

	/** Bootstrap the plugin (singleton). */
	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Shortcodes: [testimonial_form] and [testimonials].
		add_shortcode( 'testimonial_form', array( $this, 'render_form' ) );
		add_shortcode( 'testimonials', array( $this, 'render_list' ) );

		// Handle form submissions for both logged-in and anonymous visitors.
		add_action( 'admin_post_nopriv_' . self::NONCE_ACTION, array( $this, 'handle_submission' ) );
		add_action( 'admin_post_' . self::NONCE_ACTION, array( $this, 'handle_submission' ) );

		// Minimal front-end styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Register the testimonial custom post type.
	 *
	 * Testimonials are not publicly queryable on their own; they are surfaced
	 * only through the [testimonials] shortcode. They appear in wp-admin so an
	 * administrator can moderate (publish/trash) submissions.
	 */
	public function register_post_type() {
		register_post_type(
			self::CPT,
			array(
				'labels'              => array(
					'name'          => __( 'Testimonials', 'simple-testimonials' ),
					'singular_name' => __( 'Testimonial', 'simple-testimonials' ),
					'add_new_item'  => __( 'Add New Testimonial', 'simple-testimonials' ),
					'edit_item'     => __( 'Edit Testimonial', 'simple-testimonials' ),
					'all_items'     => __( 'All Testimonials', 'simple-testimonials' ),
					'menu_name'     => __( 'Testimonials', 'simple-testimonials' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_icon'           => 'dashicons-format-quote',
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'supports'            => array( 'title', 'editor' ),
				'capability_type'     => 'post',
			)
		);
	}

	/** Register a tiny stylesheet (inline, no extra HTTP request). */
	public function enqueue_styles() {
		$css = '
		.stm-form{max-width:520px;margin:1.5em 0}
		.stm-form p{margin:0 0 1em}
		.stm-form label{display:block;font-weight:600;margin-bottom:.25em}
		.stm-form input[type=text],.stm-form textarea{width:100%;padding:.5em;box-sizing:border-box}
		.stm-notice{padding:.75em 1em;border-radius:4px;margin:1em 0}
		.stm-notice.success{background:#e6f4ea;border:1px solid #34a853}
		.stm-notice.error{background:#fdecea;border:1px solid #d93025}
		.stm-list{list-style:none;margin:1.5em 0;padding:0}
		.stm-item{border-left:4px solid #ccc;padding:.5em 1em;margin:0 0 1.25em}
		.stm-item blockquote{margin:0 0 .5em;font-style:italic}
		.stm-item cite{color:#555;font-style:normal}
		';
		wp_register_style( 'simple-testimonials', false );
		wp_enqueue_style( 'simple-testimonials' );
		wp_add_inline_style( 'simple-testimonials', $css );
	}

	/**
	 * Render the submission form. Shortcode: [testimonial_form]
	 *
	 * After a redirect back from handle_submission(), a status query arg is read
	 * to show a success or error message.
	 */
	public function render_form() {
		ob_start();

		// Show feedback from a prior submission.
		if ( isset( $_GET['stm_status'] ) ) {
			$status = sanitize_key( wp_unslash( $_GET['stm_status'] ) );
			if ( 'success' === $status ) {
				echo '<div class="stm-notice success">'
					. esc_html__( 'Thank you! Your testimonial has been submitted and is awaiting review.', 'simple-testimonials' )
					. '</div>';
			} elseif ( 'error' === $status ) {
				echo '<div class="stm-notice error">'
					. esc_html__( 'Sorry, your testimonial could not be submitted. Please fill in all fields and try again.', 'simple-testimonials' )
					. '</div>';
			}
		}
		?>
		<form class="stm-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::NONCE_ACTION ); ?>" />
			<input type="hidden" name="stm_redirect" value="<?php echo esc_url( $this->current_url() ); ?>" />
			<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

			<p>
				<label for="stm-name"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label>
				<input type="text" id="stm-name" name="stm_name" maxlength="100" required />
			</p>
			<p>
				<label for="stm-text"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label>
				<textarea id="stm-text" name="stm_text" rows="4" maxlength="1000" required></textarea>
			</p>

			<?php // Honeypot field: hidden from humans, often filled by bots. ?>
			<p style="position:absolute;left:-9999px;" aria-hidden="true">
				<label for="stm-website"><?php esc_html_e( 'Leave this field empty', 'simple-testimonials' ); ?></label>
				<input type="text" id="stm-website" name="stm_website" tabindex="-1" autocomplete="off" />
			</p>

			<p>
				<button type="submit"><?php esc_html_e( 'Submit testimonial', 'simple-testimonials' ); ?></button>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Process a submitted testimonial, then redirect back to the form page.
	 *
	 * Submissions are saved as 'pending' so an administrator must approve them
	 * (by publishing) before they appear publicly.
	 */
	public function handle_submission() {
		$redirect = isset( $_POST['stm_redirect'] )
			? esc_url_raw( wp_unslash( $_POST['stm_redirect'] ) )
			: home_url( '/' );

		// Verify nonce.
		if (
			! isset( $_POST[ self::NONCE_NAME ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION )
		) {
			$this->redirect_with_status( $redirect, 'error' );
		}

		// Honeypot: if filled, silently treat as success without saving.
		if ( ! empty( $_POST['stm_website'] ) ) {
			$this->redirect_with_status( $redirect, 'success' );
		}

		$name = isset( $_POST['stm_name'] ) ? sanitize_text_field( wp_unslash( $_POST['stm_name'] ) ) : '';
		$text = isset( $_POST['stm_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['stm_text'] ) ) : '';

		// Enforce limits and require both fields.
		$name = trim( mb_substr( $name, 0, 100 ) );
		$text = trim( mb_substr( $text, 0, 1000 ) );

		if ( '' === $name || '' === $text ) {
			$this->redirect_with_status( $redirect, 'error' );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::CPT,
				'post_status'  => 'pending', // Hidden until an admin publishes it.
				'post_title'   => $name,
				'post_content' => $text,
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			$this->redirect_with_status( $redirect, 'error' );
		}

		$this->redirect_with_status( $redirect, 'success' );
	}

	/**
	 * Render approved testimonials. Shortcode: [testimonials count="10"]
	 *
	 * @param array $atts Shortcode attributes.
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
				'post_type'              => self::CPT,
				'post_status'            => 'publish',
				'posts_per_page'         => (int) $atts['count'],
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( ! $query->have_posts() ) {
			return '<p class="stm-empty">' . esc_html__( 'No testimonials yet.', 'simple-testimonials' ) . '</p>';
		}

		ob_start();
		echo '<ul class="stm-list">';
		while ( $query->have_posts() ) {
			$query->the_post();
			?>
			<li class="stm-item">
				<blockquote><?php echo esc_html( get_the_content() ); ?></blockquote>
				<cite>&mdash; <?php echo esc_html( get_the_title() ); ?></cite>
			</li>
			<?php
		}
		echo '</ul>';
		wp_reset_postdata();

		return ob_get_clean();
	}

	/** Redirect back to the form page with a status flag, then stop. */
	private function redirect_with_status( $url, $status ) {
		$url = add_query_arg( 'stm_status', $status, $url );
		wp_safe_redirect( $url );
		exit;
	}

	/** Best-effort current front-end URL, used as the redirect target. */
	private function current_url() {
		global $wp;
		if ( $wp instanceof WP && ! empty( $wp->request ) ) {
			return home_url( add_query_arg( array(), $wp->request ) );
		}
		return home_url( '/' );
	}
}

Simple_Testimonials::init();
```

## How to use it

1. **Install:** Save the code as `simple-testimonials.php` in `wp-content/plugins/`, then activate it under **Plugins** in wp-admin.
2. **Show the form:** Put `[testimonial_form]` on any page or post.
3. **Show testimonials:** Put `[testimonials]` where you want them displayed. Limit how many with `[testimonials count="5"]`.
4. **Moderate:** New submissions land in **Testimonials → All Testimonials** as *Pending*. Open one and click **Publish** to make it appear publicly.

## Design notes

- **Storage** uses a custom post type (`simple_testimonial`) rather than a custom table — no extra DB schema, and you get the wp-admin moderation UI for free.
- **Moderation by default:** submissions are saved as `pending`, so nothing shows up publicly until you approve it. If you'd rather auto-publish, change `'post_status' => 'pending'` to `'publish'` in `handle_submission()` (not recommended — opens you to spam).
- **Security:** nonce verification, `sanitize_text_field`/`sanitize_textarea_field` on input, `esc_html`/`esc_url` on output, length caps, and a honeypot field to deter bots. Form posts to `admin-post.php` (the standard WP front-end form endpoint) and redirects back (Post/Redirect/Get) so a refresh won't resubmit.
- **Styling** is minimal inline CSS; restyle via the `.stm-*` classes in your theme.

One thing worth adding for a production site: a rate limit or a real CAPCHA if you get heavy spam — the honeypot handles casual bots but not determined ones. Want me to add that?
