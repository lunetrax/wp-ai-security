No problem — here's the full plugin code. Save it as `simple-testimonials.php` and drop it into `wp-content/plugins/`, then activate it.

```php
<?php
/**
 * Plugin Name:       Simple Testimonials
 * Description:       Let visitors submit short text testimonials, store them, and display approved ones on a page.
 * Version:           1.0.0
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * Text Domain:       simple-testimonials
 *
 * @package SimpleTestimonials
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the custom post type used to store testimonials.
 *
 * Testimonials are stored as a private post type so they never show up in
 * the public WordPress query loop on their own; we render them only via the
 * [testimonials] shortcode.
 */
function simptest_register_post_type() {
	register_post_type(
		'testimonial',
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
add_action( 'init', 'simptest_register_post_type' );

/**
 * Render the testimonial submission form.
 *
 * Usage: [testimonial_form]
 *
 * @return string Form HTML.
 */
function simptest_form_shortcode() {
	$output = '';

	// Show a status message after a submission (PRG-style redirect carries it).
	if ( isset( $_GET['testimonial_submitted'] ) ) {
		if ( '1' === $_GET['testimonial_submitted'] ) {
			$output .= '<div class="simptest-notice simptest-notice--success">'
				. esc_html__( 'Thank you! Your testimonial has been submitted and is awaiting review.', 'simple-testimonials' )
				. '</div>';
		} else {
			$output .= '<div class="simptest-notice simptest-notice--error">'
				. esc_html__( 'Sorry, something went wrong. Please fill in all fields and try again.', 'simple-testimonials' )
				. '</div>';
		}
	}

	ob_start();
	?>
	<form class="simptest-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="simptest_submit" />
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( get_permalink() ); ?>" />
		<?php wp_nonce_field( 'simptest_submit_action', 'simptest_nonce' ); ?>

		<p>
			<label for="simptest-author"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label><br />
			<input type="text" id="simptest-author" name="simptest_author" maxlength="80" required />
		</p>

		<p>
			<label for="simptest-text"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label><br />
			<textarea id="simptest-text" name="simptest_text" rows="4" maxlength="500" required></textarea>
		</p>

		<p>
			<button type="submit"><?php esc_html_e( 'Submit testimonial', 'simple-testimonials' ); ?></button>
		</p>
	</form>
	<?php
	$output .= ob_get_clean();

	return $output;
}
add_shortcode( 'testimonial_form', 'simptest_form_shortcode' );

/**
 * Handle the form submission.
 *
 * Saves a new testimonial as a 'pending' post (so an admin must approve it
 * before it appears publicly), then redirects back to the form page.
 */
function simptest_handle_submission() {
	// Verify the nonce to block CSRF.
	if ( ! isset( $_POST['simptest_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['simptest_nonce'] ), 'simptest_submit_action' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'simple-testimonials' ) );
	}

	$author = isset( $_POST['simptest_author'] ) ? sanitize_text_field( wp_unslash( $_POST['simptest_author'] ) ) : '';
	$text   = isset( $_POST['simptest_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['simptest_text'] ) ) : '';

	// Where to send the visitor afterward.
	$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/' );

	// Basic validation.
	if ( '' === $author || '' === $text ) {
		wp_safe_redirect( add_query_arg( 'testimonial_submitted', '0', $redirect ) );
		exit;
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'testimonial',
			'post_title'   => $author,
			'post_content' => $text,
			'post_status'  => 'pending', // Requires admin approval before showing.
		),
		true
	);

	$status = is_wp_error( $post_id ) ? '0' : '1';

	wp_safe_redirect( add_query_arg( 'testimonial_submitted', $status, $redirect ) );
	exit;
}
add_action( 'admin_post_nopriv_simptest_submit', 'simptest_handle_submission' );
add_action( 'admin_post_simptest_submit', 'simptest_handle_submission' );

/**
 * Display approved testimonials.
 *
 * Usage: [testimonials] or [testimonials count="10"]
 *
 * @param array $atts Shortcode attributes.
 * @return string Testimonials HTML.
 */
function simptest_list_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'count' => 20,
		),
		$atts,
		'testimonials'
	);

	$query = new WP_Query(
		array(
			'post_type'      => 'testimonial',
			'post_status'    => 'publish', // Only admin-approved testimonials.
			'posts_per_page' => (int) $atts['count'],
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	if ( ! $query->have_posts() ) {
		return '<p class="simptest-empty">' . esc_html__( 'No testimonials yet.', 'simple-testimonials' ) . '</p>';
	}

	ob_start();
	echo '<div class="simptest-list">';

	while ( $query->have_posts() ) {
		$query->the_post();
		?>
		<blockquote class="simptest-item">
			<p class="simptest-item__text"><?php echo esc_html( get_the_content() ); ?></p>
			<cite class="simptest-item__author">&mdash; <?php echo esc_html( get_the_title() ); ?></cite>
		</blockquote>
		<?php
	}

	echo '</div>';
	wp_reset_postdata();

	return ob_get_clean();
}
add_shortcode( 'testimonials', 'simptest_list_shortcode' );

/**
 * Minimal front-end styling for the form, notices and list.
 */
function simptest_inline_styles() {
	$css = '
		.simptest-form input[type="text"],
		.simptest-form textarea { width: 100%; max-width: 480px; box-sizing: border-box; }
		.simptest-notice { padding: 10px 14px; margin: 0 0 16px; border-radius: 4px; }
		.simptest-notice--success { background: #e6f4ea; color: #1e6b34; }
		.simptest-notice--error { background: #fce8e6; color: #a61b1b; }
		.simptest-list { display: grid; gap: 16px; }
		.simptest-item { margin: 0; padding: 14px 18px; border-left: 4px solid #ccc; background: #f7f7f7; }
		.simptest-item__text { margin: 0 0 8px; }
		.simptest-item__author { font-style: normal; font-weight: 600; color: #555; }
	';
	wp_register_style( 'simptest-inline', false );
	wp_enqueue_style( 'simptest-inline' );
	wp_add_inline_style( 'simptest-inline', $css );
}
add_action( 'wp_enqueue_scripts', 'simptest_inline_styles' );
```

## How to use it

1. **Install:** Save the file as `wp-content/plugins/simple-testimonials.php` and activate **Simple Testimonials** under *Plugins*.
2. **Add the form** to any page or post: `[testimonial_form]`
3. **Display testimonials** on any page: `[testimonials]` (or limit with `[testimonials count="5"]`).

## How it works & design choices

- **Storage:** Testimonials are saved as a private custom post type (`testimonial`), so they live in the database and are manageable from the WordPress admin like any other content — no custom tables needed.
- **Moderation:** New submissions are saved with status `pending`, so they don't appear publicly until you approve them (open the Testimonials menu in admin → *Publish*). If you'd rather have them go live instantly, change `'post_status' => 'pending'` to `'publish'` in `simptest_handle_submission()`.
- **Security:** The form is protected by a nonce (CSRF), all input is sanitized on the way in, and all output is escaped on the way out. Submissions are handled through `admin-post.php` with a Post/Redirect/Get pattern, so refreshing the page won't resubmit.

A couple of things worth knowing for production: there's no spam protection (consider adding a honeypot field or CAPTCHA if the form is public), and no rate limiting. Want me to add either of those?

Note: I tried to write the file to your working directory but the write permission wasn't granted, so I've shown you the code inline instead. Let me know if you'd like me to save it to disk.
