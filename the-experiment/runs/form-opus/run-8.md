Here's a complete single-file WordPress plugin. Save it as `simple-testimonials.php` in `wp-content/plugins/simple-testimonials/` (or upload as a zip), then activate it.

```php
<?php
/**
 * Plugin Name:       Simple Testimonials
 * Description:       Lets visitors submit short text testimonials via a form, stores them, and displays approved ones. Use the [testimonial_form] and [testimonials] shortcodes.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * Text Domain:       simple-testimonials
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Simple_Testimonials {

	const CPT       = 'st_testimonial';
	const NONCE     = 'st_submit_testimonial';
	const FORM_FLAG = 'st_form_submit';

	/**
	 * Boot the plugin.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'handle_submission' ) );

		add_shortcode( 'testimonial_form', array( __CLASS__, 'render_form' ) );
		add_shortcode( 'testimonials', array( __CLASS__, 'render_list' ) );

		// Admin column showing the testimonial text.
		add_filter( 'manage_' . self::CPT . '_posts_columns', array( __CLASS__, 'admin_columns' ) );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( __CLASS__, 'admin_column_content' ), 10, 2 );
	}

	/**
	 * Register the testimonial custom post type.
	 *
	 * New submissions are saved as "pending" so an admin must approve them
	 * before they appear publicly.
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
	 */
	public static function handle_submission() {
		if ( empty( $_POST[ self::FORM_FLAG ] ) ) {
			return;
		}

		// Verify nonce (CSRF protection).
		if ( ! isset( $_POST['st_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['st_nonce'] ) ), self::NONCE ) ) {
			self::redirect_with( 'error' );
		}

		$name    = isset( $_POST['st_name'] ) ? sanitize_text_field( wp_unslash( $_POST['st_name'] ) ) : '';
		$content = isset( $_POST['st_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['st_content'] ) ) : '';

		$name    = trim( $name );
		$content = trim( $content );

		// Basic validation.
		if ( '' === $name || '' === $content ) {
			self::redirect_with( 'empty' );
		}

		// Keep it short.
		if ( mb_strlen( $content ) > 600 ) {
			$content = mb_substr( $content, 0, 600 );
		}
		if ( mb_strlen( $name ) > 100 ) {
			$name = mb_substr( $name, 0, 100 );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::CPT,
				'post_title'   => $name,
				'post_content' => $content,
				'post_status'  => 'pending', // Requires admin approval.
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
	 *
	 * @param string $status One of: success, empty, error.
	 */
	private static function redirect_with( $status ) {
		$referer = wp_get_referer();
		if ( ! $referer ) {
			$referer = home_url( '/' );
		}
		$url = add_query_arg( 'st_status', rawurlencode( $status ), $referer );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Render the submission form. Shortcode: [testimonial_form]
	 *
	 * @return string
	 */
	public static function render_form() {
		ob_start();

		// Show a status message after a redirect.
		if ( isset( $_GET['st_status'] ) ) {
			$status   = sanitize_key( wp_unslash( $_GET['st_status'] ) );
			$messages = array(
				'success' => array( 'notice', __( 'Thank you! Your testimonial has been submitted and is awaiting approval.', 'simple-testimonials' ) ),
				'empty'   => array( 'error', __( 'Please fill in both your name and your testimonial.', 'simple-testimonials' ) ),
				'error'   => array( 'error', __( 'Something went wrong. Please try again.', 'simple-testimonials' ) ),
			);
			if ( isset( $messages[ $status ] ) ) {
				printf(
					'<p class="st-message st-%1$s" style="padding:10px;border-radius:4px;%2$s">%3$s</p>',
					esc_attr( $messages[ $status ][0] ),
					'error' === $messages[ $status ][0] ? 'background:#fde2e2;color:#8a1f1f;' : 'background:#e2f6e2;color:#1f7a1f;',
					esc_html( $messages[ $status ][1] )
				);
			}
		}
		?>
		<form class="st-form" method="post" action="">
			<?php wp_nonce_field( self::NONCE, 'st_nonce' ); ?>
			<input type="hidden" name="<?php echo esc_attr( self::FORM_FLAG ); ?>" value="1" />

			<p>
				<label for="st_name"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label><br />
				<input type="text" id="st_name" name="st_name" maxlength="100" required style="width:100%;max-width:400px;" />
			</p>
			<p>
				<label for="st_content"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label><br />
				<textarea id="st_content" name="st_content" rows="4" maxlength="600" required style="width:100%;max-width:400px;"></textarea>
			</p>
			<p>
				<button type="submit"><?php esc_html_e( 'Submit testimonial', 'simple-testimonials' ); ?></button>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render approved testimonials. Shortcode: [testimonials count="10"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
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
				'post_type'      => self::CPT,
				'post_status'    => 'publish', // Only approved testimonials.
				'posts_per_page' => (int) $atts['count'],
				'orderby'        => 'date',
				'order'          => 'DESC',
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
			<blockquote class="st-item" style="margin:0 0 1.5em;padding:1em;border-left:4px solid #ccc;">
				<div class="st-item-content"><?php echo wp_kses_post( wpautop( get_the_content() ) ); ?></div>
				<cite class="st-item-author" style="display:block;margin-top:.5em;font-style:normal;font-weight:bold;">
					&mdash; <?php echo esc_html( get_the_title() ); ?>
				</cite>
			</blockquote>
			<?php
		}
		echo '</div>';
		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Add a "Testimonial" column to the admin list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function admin_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new[ $key ]       = __( 'Name', 'simple-testimonials' );
				$new['st_excerpt'] = __( 'Testimonial', 'simple-testimonials' );
			} else {
				$new[ $key ] = $label;
			}
		}
		return $new;
	}

	/**
	 * Render custom admin column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function admin_column_content( $column, $post_id ) {
		if ( 'st_excerpt' === $column ) {
			echo esc_html( wp_trim_words( get_post_field( 'post_content', $post_id ), 20 ) );
		}
	}
}

Simple_Testimonials::init();
```

## How to use it

1. **Install & activate** — Place the file in `wp-content/plugins/simple-testimonials/simple-testimonials.php` and activate it from **Plugins**.
2. **Show the form** — Add the shortcode `[testimonial_form]` to any page or post.
3. **Show testimonials** — Add `[testimonials]` (optionally `[testimonials count="5"]`) to any page.
4. **Moderate** — New submissions land under the **Testimonials** menu in wp-admin as *Pending*. Open one and click **Publish** to make it public.

## Key design choices

- **Stored as a custom post type**, so you get the admin UI, editing, and trash for free — no custom database table needed.
- **Moderation by default**: submissions save as `pending`, so spam/junk never appears until you approve it. (If you'd rather auto-publish, change `'post_status' => 'pending'` to `'publish'`.)
- **Security**: nonce verification (CSRF), `sanitize_text_field`/`sanitize_textarea_field` on input, and `esc_html`/`wp_kses_post` on output. The form uses post-redirect-get so a refresh won't resubmit.

Want me to add anything — a star rating, email notification on new submissions, a honeypot/CAPTCHA for spam, or a Gutenberg block instead of shortcodes?

Note: I tried to save the file to your working directory but the write permission wasn't granted, so I've shown the full code here instead. Let me know if you'd like me to write it to disk.
