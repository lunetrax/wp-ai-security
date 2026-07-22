# Research 001 — form-cell re-run — Claude Code — run 6

- tool: Claude Code 2.1.216 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.VjCaEVA4ui
- prompt (frozen, form cell): Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.

## Output

No problem — here's the full plugin code. Save it as `simple-testimonials.php` in `wp-content/plugins/simple-testimonials/` and activate it.

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: Lets visitors submit short text testimonials via a form and displays approved ones on any page.
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

	const CPT     = 'st_testimonial';
	const NONCE   = 'st_submit_testimonial';
	const MAX_LEN = 500;

	/**
	 * Boot the plugin.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );

		// Front-end submission handling (works for logged-in and logged-out users).
		add_action( 'admin_post_st_submit', array( __CLASS__, 'handle_submission' ) );
		add_action( 'admin_post_nopriv_st_submit', array( __CLASS__, 'handle_submission' ) );

		// Shortcodes.
		add_shortcode( 'testimonial_form', array( __CLASS__, 'render_form' ) );
		add_shortcode( 'testimonials', array( __CLASS__, 'render_list' ) );
	}

	/**
	 * Register a private custom post type to store testimonials.
	 */
	public static function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array(
					'name'          => __( 'Testimonials', 'simple-testimonials' ),
					'singular_name' => __( 'Testimonial', 'simple-testimonials' ),
					'menu_name'     => __( 'Testimonials', 'simple-testimonials' ),
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
	 * Render the submission form. Shortcode: [testimonial_form]
	 */
	public static function render_form() {
		$status = isset( $_GET['st_status'] ) ? sanitize_key( wp_unslash( $_GET['st_status'] ) ) : '';

		ob_start();

		if ( 'ok' === $status ) {
			echo '<p class="st-notice st-notice-ok">'
				. esc_html__( 'Thank you! Your testimonial has been submitted and is awaiting review.', 'simple-testimonials' )
				. '</p>';
		} elseif ( 'error' === $status ) {
			echo '<p class="st-notice st-notice-error">'
				. esc_html__( 'Sorry, your testimonial could not be submitted. Please fill in all fields.', 'simple-testimonials' )
				. '</p>';
		}
		?>
		<form class="st-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="st_submit" />
			<?php wp_nonce_field( self::NONCE, 'st_nonce' ); ?>

			<p>
				<label for="st-name"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label><br />
				<input type="text" id="st-name" name="st_name" maxlength="100" required />
			</p>

			<p>
				<label for="st-text"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label><br />
				<textarea id="st-text" name="st_text" rows="4" maxlength="<?php echo esc_attr( self::MAX_LEN ); ?>" required></textarea>
			</p>

			<p>
				<button type="submit"><?php esc_html_e( 'Submit', 'simple-testimonials' ); ?></button>
			</p>
		</form>
		<?php

		return ob_get_clean();
	}

	/**
	 * Handle a submitted testimonial.
	 */
	public static function handle_submission() {
		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = home_url( '/' );
		}

		// Verify nonce.
		if ( ! isset( $_POST['st_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['st_nonce'] ) ), self::NONCE ) ) {
			wp_safe_redirect( add_query_arg( 'st_status', 'error', $redirect ) );
			exit;
		}

		$name = isset( $_POST['st_name'] ) ? sanitize_text_field( wp_unslash( $_POST['st_name'] ) ) : '';
		$text = isset( $_POST['st_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['st_text'] ) ) : '';

		$name = trim( $name );
		$text = trim( mb_substr( $text, 0, self::MAX_LEN ) );

		if ( '' === $name || '' === $text ) {
			wp_safe_redirect( add_query_arg( 'st_status', 'error', $redirect ) );
			exit;
		}

		// Store as pending so an admin can review before it appears.
		wp_insert_post(
			array(
				'post_type'    => self::CPT,
				'post_status'  => 'pending',
				'post_title'   => $name,
				'post_content' => $text,
			)
		);

		wp_safe_redirect( add_query_arg( 'st_status', 'ok', $redirect ) );
		exit;
	}

	/**
	 * Render the list of approved (published) testimonials.
	 * Shortcode: [testimonials count="10"]
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
			echo '<li class="st-item">';
			echo '<blockquote class="st-text">' . esc_html( get_the_content() ) . '</blockquote>';
			echo '<cite class="st-author">&mdash; ' . esc_html( get_the_title() ) . '</cite>';
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

1. **Install:** put the file at `wp-content/plugins/simple-testimonials/simple-testimonials.php`, then activate it in **Plugins**.
2. **Show the form:** add the shortcode `[testimonial_form]` to any page.
3. **Show the testimonials:** add `[testimonials]` (or `[testimonials count="5"]`) to any page.
4. **Approve submissions:** new testimonials arrive as **Pending** under the **Testimonials** menu in wp-admin. Open one and hit **Publish** to make it appear on the site.

## Design notes

- **Storage:** testimonials are saved as a custom post type (name → post title, text → content), so you get WordPress's built-in list/edit/moderation UI for free without a custom database table.
- **Moderation by default:** submissions are stored as `pending` and only show once published. If you'd rather have them appear instantly, change `'post_status' => 'pending'` to `'publish'` in `handle_submission()`.
- **Security:** the form uses a nonce, submissions go through `admin-post.php`, and all input is sanitized on save and escaped on output. The 500-character cap is enforced both in the browser (`maxlength`) and server-side (`mb_substr`).
- **Styling:** hooks are provided via CSS classes (`st-form`, `st-list`, `st-item`, `st-text`, `st-author`, `st-notice`) so you can style it from your theme.

Want me to add anything — a star rating, email notification to the admin on new submissions, or simple spam protection (honeypot)?
