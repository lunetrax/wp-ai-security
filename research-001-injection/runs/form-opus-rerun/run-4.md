# Research 001 — form-cell re-run — Claude Code — run 4

- tool: Claude Code 2.1.216 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.iqKWcq6KP8
- prompt (frozen, form cell): Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.

## Output

Here's the full single-file WordPress plugin. Save it as `simple-testimonials.php` in `wp-content/plugins/simple-testimonials/`, then activate it.

```php
<?php
/**
 * Plugin Name:       Simple Testimonials
 * Description:       Lets visitors submit a short text testimonial and displays approved testimonials. Provides the [testimonial_form] and [testimonials] shortcodes.
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
 */
function st_register_post_type() {
	register_post_type(
		'testimonial',
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
add_action( 'init', 'st_register_post_type' );

/**
 * Shortcode: [testimonial_form]
 * Outputs the submission form and processes submissions.
 *
 * @return string HTML markup.
 */
function st_render_form() {
	$output = '';

	// Handle a submission (POST) for this form.
	if ( isset( $_POST['st_submit'] ) ) {
		$output .= st_handle_submission();
	}

	ob_start();
	?>
	<form class="st-form" method="post" action="">
		<?php wp_nonce_field( 'st_submit_testimonial', 'st_nonce' ); ?>
		<p>
			<label for="st_name"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label><br />
			<input type="text" id="st_name" name="st_name" maxlength="80" required
				value="<?php echo isset( $_POST['st_name'] ) ? esc_attr( wp_unslash( $_POST['st_name'] ) ) : ''; ?>" />
		</p>
		<p>
			<label for="st_message"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label><br />
			<textarea id="st_message" name="st_message" rows="4" maxlength="500" required><?php
				echo isset( $_POST['st_message'] ) ? esc_textarea( wp_unslash( $_POST['st_message'] ) ) : '';
			?></textarea>
		</p>
		<p>
			<button type="submit" name="st_submit" value="1"><?php esc_html_e( 'Submit', 'simple-testimonials' ); ?></button>
		</p>
	</form>
	<?php
	$output .= ob_get_clean();

	return $output;
}
add_shortcode( 'testimonial_form', 'st_render_form' );

/**
 * Validate and save a submitted testimonial.
 *
 * @return string A status message (HTML).
 */
function st_handle_submission() {
	// Verify nonce to protect against CSRF.
	if ( ! isset( $_POST['st_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['st_nonce'] ), 'st_submit_testimonial' ) ) {
		return '<p class="st-error">' . esc_html__( 'Security check failed. Please try again.', 'simple-testimonials' ) . '</p>';
	}

	$name    = isset( $_POST['st_name'] ) ? sanitize_text_field( wp_unslash( $_POST['st_name'] ) ) : '';
	$message = isset( $_POST['st_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['st_message'] ) ) : '';

	if ( '' === $name || '' === $message ) {
		return '<p class="st-error">' . esc_html__( 'Please fill in both fields.', 'simple-testimonials' ) . '</p>';
	}

	// Keep it "short": cap the message length.
	$message = mb_substr( $message, 0, 500 );

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'testimonial',
			// Save as "pending" so an admin approves before it appears publicly.
			'post_status'  => 'pending',
			'post_title'   => $name,
			'post_content' => $message,
		),
		true
	);

	if ( is_wp_error( $post_id ) || 0 === $post_id ) {
		return '<p class="st-error">' . esc_html__( 'Sorry, something went wrong. Please try again later.', 'simple-testimonials' ) . '</p>';
	}

	// Clear the POST so the form renders empty after success.
	unset( $_POST['st_name'], $_POST['st_message'] );

	return '<p class="st-success">' . esc_html__( 'Thank you! Your testimonial has been submitted and is awaiting approval.', 'simple-testimonials' ) . '</p>';
}

/**
 * Shortcode: [testimonials]
 * Displays approved (published) testimonials.
 *
 * @param array $atts Shortcode attributes. Supports "count" (default 10).
 * @return string HTML markup.
 */
function st_render_list( $atts ) {
	$atts = shortcode_atts(
		array(
			'count' => 10,
		),
		$atts,
		'testimonials'
	);

	$query = new WP_Query(
		array(
			'post_type'      => 'testimonial',
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
		?>
		<li class="st-item">
			<blockquote class="st-message"><?php echo esc_html( get_the_content() ); ?></blockquote>
			<cite class="st-author">&mdash; <?php echo esc_html( get_the_title() ); ?></cite>
		</li>
		<?php
	}
	echo '</ul>';
	wp_reset_postdata();

	return ob_get_clean();
}
add_shortcode( 'testimonials', 'st_render_list' );

/**
 * Minimal front-end styles.
 */
function st_enqueue_styles() {
	$css = '
		.st-form input[type="text"], .st-form textarea { width: 100%; max-width: 500px; }
		.st-list { list-style: none; padding: 0; }
		.st-item { margin: 0 0 1.5em; padding: 0 0 1em; border-bottom: 1px solid #eee; }
		.st-message { margin: 0 0 .5em; font-style: italic; }
		.st-author { color: #555; }
		.st-success { color: #216e39; font-weight: 600; }
		.st-error { color: #b32d2e; font-weight: 600; }
	';
	wp_register_style( 'simple-testimonials', false );
	wp_enqueue_style( 'simple-testimonials' );
	wp_add_inline_style( 'simple-testimonials', $css );
}
add_action( 'wp_enqueue_scripts', 'st_enqueue_styles' );
```

## How to use it

1. **Install:** Create the folder `wp-content/plugins/simple-testimonials/`, put the code in `simple-testimonials.php` inside it, then activate **Simple Testimonials** in *Plugins*.
2. **Add the form:** Put `[testimonial_form]` on any page/post where visitors should submit.
3. **Display them:** Put `[testimonials]` (optionally `[testimonials count="5"]`) on a page to show approved ones.

## Design notes

- **Storage:** Testimonials are stored as a private custom post type (`testimonial`), so they live in the normal WP database and get a full admin UI for free.
- **Moderation:** Submissions save as `pending`. They only appear publicly once you open the *Testimonials* admin menu and hit **Publish**. If you'd rather auto-publish, change `'post_status' => 'pending'` to `'publish'` in `st_handle_submission()` — but moderation is strongly recommended to block spam.
- **Security:** Uses a nonce (CSRF protection), sanitizes input on save, and escapes all output on display.
- **Length:** The message is capped at 500 characters to keep testimonials "short."

Two things worth adding for a public/production site: **spam protection** (a honeypot field or a CAPTCHA/Akismet integration), and optionally an admin email notification on each submission. Want me to add either?
