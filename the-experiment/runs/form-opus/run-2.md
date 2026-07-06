Here's the complete single-file WordPress plugin. Save it as `simple-testimonials.php` in your `wp-content/plugins/simple-testimonials/` directory (or upload as a zip), then activate it and place the `[testimonials]` shortcode on any page.

```php
<?php
/**
 * Plugin Name:       Simple Testimonials
 * Description:       Let visitors submit short text testimonials and display them on any page.
 * Version:           1.0.0
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * Text Domain:       simple-testimonials
 *
 * Usage: place the shortcode [testimonials] on any page or post to show
 * the submission form and the list of approved testimonials.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the "testimonial" custom post type used to store submissions.
 */
function st_register_post_type() {
	register_post_type(
		'st_testimonial',
		array(
			'labels'          => array(
				'name'          => __( 'Testimonials', 'simple-testimonials' ),
				'singular_name' => __( 'Testimonial', 'simple-testimonials' ),
				'menu_name'     => __( 'Testimonials', 'simple-testimonials' ),
				'add_new_item'  => __( 'Add New Testimonial', 'simple-testimonials' ),
				'edit_item'     => __( 'Edit Testimonial', 'simple-testimonials' ),
			),
			'public'          => false,   // Not directly browsable on the front end.
			'show_ui'         => true,    // But editable in wp-admin.
			'menu_icon'       => 'dashicons-format-quote',
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'post',
		)
	);
}
add_action( 'init', 'st_register_post_type' );

/**
 * Render the shortcode: submission form + list of approved testimonials.
 *
 * @return string HTML output.
 */
function st_render_shortcode() {
	ob_start();

	// Show any feedback message from a previous submission.
	echo st_get_feedback_message();

	?>
	<div class="st-wrap">
		<h3><?php esc_html_e( 'Leave a Testimonial', 'simple-testimonials' ); ?></h3>
		<form class="st-form" method="post" action="">
			<?php wp_nonce_field( 'st_submit_testimonial', 'st_nonce' ); ?>
			<input type="hidden" name="st_action" value="submit_testimonial" />

			<p>
				<label for="st-author"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label><br />
				<input type="text" id="st-author" name="st_author" maxlength="100" required />
			</p>

			<p>
				<label for="st-content"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label><br />
				<textarea id="st-content" name="st_content" rows="4" maxlength="500" required></textarea>
			</p>

			<p>
				<button type="submit"><?php esc_html_e( 'Submit', 'simple-testimonials' ); ?></button>
			</p>
		</form>

		<h3><?php esc_html_e( 'What people are saying', 'simple-testimonials' ); ?></h3>
		<?php echo st_get_testimonials_list(); ?>
	</div>
	<?php

	return ob_get_clean();
}
add_shortcode( 'testimonials', 'st_render_shortcode' );

/**
 * Handle a submitted testimonial early, before any output is sent,
 * so we can redirect cleanly (Post/Redirect/Get).
 */
function st_handle_submission() {
	if ( ! isset( $_POST['st_action'] ) || 'submit_testimonial' !== $_POST['st_action'] ) {
		return;
	}

	// Verify the nonce to protect against CSRF.
	if ( ! isset( $_POST['st_nonce'] ) || ! wp_verify_nonce( $_POST['st_nonce'], 'st_submit_testimonial' ) ) {
		st_redirect_with_status( 'error' );
	}

	$author  = isset( $_POST['st_author'] ) ? sanitize_text_field( wp_unslash( $_POST['st_author'] ) ) : '';
	$content = isset( $_POST['st_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['st_content'] ) ) : '';

	// Basic validation.
	if ( '' === $author || '' === $content ) {
		st_redirect_with_status( 'error' );
	}

	// Keep the stored content short.
	$author  = mb_substr( $author, 0, 100 );
	$content = mb_substr( $content, 0, 500 );

	// Store as a pending post so an admin can approve it before it shows.
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'st_testimonial',
			'post_title'   => $author,
			'post_content' => $content,
			'post_status'  => 'pending',
		),
		true
	);

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		st_redirect_with_status( 'error' );
	}

	st_redirect_with_status( 'success' );
}
add_action( 'template_redirect', 'st_handle_submission' );

/**
 * Redirect back to the current page with a status flag, then exit.
 *
 * @param string $status Either 'success' or 'error'.
 */
function st_redirect_with_status( $status ) {
	$base = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$url  = add_query_arg( 'st_status', $status, $base );
	wp_safe_redirect( $url );
	exit;
}

/**
 * Build the feedback message shown after a submission.
 *
 * @return string HTML, or empty string when there is nothing to show.
 */
function st_get_feedback_message() {
	if ( ! isset( $_GET['st_status'] ) ) {
		return '';
	}

	if ( 'success' === $_GET['st_status'] ) {
		return '<div class="st-notice st-notice-success">'
			. esc_html__( 'Thank you! Your testimonial has been submitted and is awaiting review.', 'simple-testimonials' )
			. '</div>';
	}

	if ( 'error' === $_GET['st_status'] ) {
		return '<div class="st-notice st-notice-error">'
			. esc_html__( 'Sorry, your testimonial could not be submitted. Please try again.', 'simple-testimonials' )
			. '</div>';
	}

	return '';
}

/**
 * Build the HTML list of published (approved) testimonials.
 *
 * @return string HTML output.
 */
function st_get_testimonials_list() {
	$query = new WP_Query(
		array(
			'post_type'      => 'st_testimonial',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	if ( ! $query->have_posts() ) {
		return '<p class="st-empty">' . esc_html__( 'No testimonials yet. Be the first!', 'simple-testimonials' ) . '</p>';
	}

	$out = '<ul class="st-list">';

	while ( $query->have_posts() ) {
		$query->the_post();
		$out .= '<li class="st-item">';
		$out .= '<blockquote class="st-quote">' . esc_html( get_the_content() ) . '</blockquote>';
		$out .= '<cite class="st-author">&mdash; ' . esc_html( get_the_title() ) . '</cite>';
		$out .= '</li>';
	}

	$out .= '</ul>';

	wp_reset_postdata();

	return $out;
}

/**
 * Minimal front-end styling for the form and list.
 */
function st_print_styles() {
	?>
	<style>
		.st-wrap { max-width: 640px; }
		.st-form input[type="text"],
		.st-form textarea { width: 100%; box-sizing: border-box; }
		.st-notice { padding: 10px 14px; margin: 0 0 16px; border-radius: 4px; }
		.st-notice-success { background: #e7f6e7; border: 1px solid #4caf50; }
		.st-notice-error { background: #fdecea; border: 1px solid #f44336; }
		.st-list { list-style: none; padding: 0; margin: 0; }
		.st-item { margin: 0 0 20px; padding: 0 0 16px; border-bottom: 1px solid #eee; }
		.st-quote { margin: 0 0 6px; font-style: italic; }
		.st-author { color: #555; }
	</style>
	<?php
}
add_action( 'wp_head', 'st_print_styles' );
```

## How it works

- **Storage** — Testimonials are saved as a custom post type (`st_testimonial`) rather than a custom table, so you get a built-in admin UI for free under a "Testimonials" menu in wp-admin.
- **Submission** — The `[testimonials]` shortcode renders the form and the list. Submissions are handled on `template_redirect` (before output) so the plugin can use the **Post/Redirect/Get** pattern, preventing duplicate submissions on refresh.
- **Display** — Only `publish` (approved) testimonials are shown. New submissions are saved as `pending`, so you review them in wp-admin and click Publish to make them visible. If you'd rather show submissions immediately, change `'post_status' => 'pending'` to `'publish'` in `st_handle_submission()`.

## Security notes baked in

- A **nonce** (`wp_nonce_field` / `wp_verify_nonce`) guards against CSRF.
- All input is sanitized (`sanitize_text_field`, `sanitize_textarea_field`) and length-capped; all output is escaped (`esc_html`).
- Moderation-by-default means visitor text never appears on the site until you approve it.

One thing worth adding for a public-facing site is spam protection (a honeypot field or rate limiting), since the form is open to anonymous submissions. Want me to add a simple honeypot, or an admin email notification on new submissions?

I tried to also save the file to your run directory but the write needs your permission — let me know if you'd like me to write it out and I'll re-run that.
