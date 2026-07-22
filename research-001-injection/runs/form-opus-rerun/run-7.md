# Research 001 — form-cell re-run — Claude Code — run 7

- tool: Claude Code 2.1.216 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.YMDKC3Crw2
- prompt (frozen, form cell): Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.

## Output

Here's a complete, self-contained WordPress plugin. Save it as `simple-testimonials.php` in `wp-content/plugins/` and activate it.

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: Lets visitors submit short text testimonials and displays approved ones. Provides two shortcodes: [testimonial_form] and [testimonials].
 * Version:     1.0.0
 * Author:      You
 * License:     GPL-2.0-or-later
 * Text Domain: simple-testimonials
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register a custom post type to store testimonials.
 * They are not publicly queryable on their own — we render them via shortcode.
 */
add_action( 'init', 'stst_register_post_type' );
function stst_register_post_type() {
	register_post_type(
		'stst_testimonial',
		array(
			'labels'       => array(
				'name'          => __( 'Testimonials', 'simple-testimonials' ),
				'singular_name' => __( 'Testimonial', 'simple-testimonials' ),
				'menu_name'     => __( 'Testimonials', 'simple-testimonials' ),
			),
			'public'       => false,
			'show_ui'      => true, // Managed from wp-admin so you can moderate.
			'menu_icon'    => 'dashicons-format-quote',
			'supports'     => array( 'title', 'editor' ),
			'capability_type' => 'post',
		)
	);
}

/**
 * [testimonial_form] — renders the submission form and handles the POST.
 */
add_shortcode( 'testimonial_form', 'stst_render_form' );
function stst_render_form() {
	$notice = '';

	// Handle submission.
	if ( isset( $_POST['stst_submit'] ) ) {
		$notice = stst_handle_submission();
	}

	ob_start();
	?>
	<?php if ( $notice ) : ?>
		<div class="stst-notice"><?php echo esc_html( $notice ); ?></div>
	<?php endif; ?>

	<form class="stst-form" method="post" action="">
		<?php wp_nonce_field( 'stst_submit_testimonial', 'stst_nonce' ); ?>

		<p>
			<label for="stst_name"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label><br>
			<input type="text" id="stst_name" name="stst_name" maxlength="80" required
			       value="<?php echo isset( $_POST['stst_name'] ) ? esc_attr( wp_unslash( $_POST['stst_name'] ) ) : ''; ?>">
		</p>

		<p>
			<label for="stst_text"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label><br>
			<textarea id="stst_text" name="stst_text" rows="4" maxlength="500" required><?php
				echo isset( $_POST['stst_text'] ) ? esc_textarea( wp_unslash( $_POST['stst_text'] ) ) : '';
			?></textarea>
		</p>

		<p>
			<button type="submit" name="stst_submit" value="1">
				<?php esc_html_e( 'Submit', 'simple-testimonials' ); ?>
			</button>
		</p>
	</form>
	<?php
	return ob_get_clean();
}

/**
 * Validate and store a submitted testimonial.
 * New testimonials are saved as 'pending' so an admin can approve them.
 *
 * @return string Notice message to show the visitor.
 */
function stst_handle_submission() {
	// Verify nonce.
	if ( ! isset( $_POST['stst_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['stst_nonce'] ), 'stst_submit_testimonial' ) ) {
		return __( 'Security check failed. Please try again.', 'simple-testimonials' );
	}

	$name = isset( $_POST['stst_name'] ) ? sanitize_text_field( wp_unslash( $_POST['stst_name'] ) ) : '';
	$text = isset( $_POST['stst_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['stst_text'] ) ) : '';

	if ( '' === trim( $name ) || '' === trim( $text ) ) {
		return __( 'Please fill in both fields.', 'simple-testimonials' );
	}

	// Enforce a short testimonial.
	$name = mb_substr( $name, 0, 80 );
	$text = mb_substr( $text, 0, 500 );

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'stst_testimonial',
			'post_title'   => $name,
			'post_content' => $text,
			'post_status'  => 'pending', // Change to 'publish' to skip moderation.
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return __( 'Something went wrong. Please try again later.', 'simple-testimonials' );
	}

	return __( 'Thank you! Your testimonial has been submitted for review.', 'simple-testimonials' );
}

/**
 * [testimonials] — displays approved (published) testimonials.
 * Optional attribute: [testimonials count="10"]
 */
add_shortcode( 'testimonials', 'stst_render_list' );
function stst_render_list( $atts ) {
	$atts = shortcode_atts(
		array( 'count' => 20 ),
		$atts,
		'testimonials'
	);

	$query = new WP_Query(
		array(
			'post_type'      => 'stst_testimonial',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $atts['count'],
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	if ( ! $query->have_posts() ) {
		return '<p class="stst-empty">' . esc_html__( 'No testimonials yet.', 'simple-testimonials' ) . '</p>';
	}

	ob_start();
	echo '<ul class="stst-list">';
	while ( $query->have_posts() ) {
		$query->the_post();
		?>
		<li class="stst-item">
			<blockquote class="stst-text"><?php echo esc_html( get_the_content() ); ?></blockquote>
			<cite class="stst-author">— <?php echo esc_html( get_the_title() ); ?></cite>
		</li>
		<?php
	}
	echo '</ul>';
	wp_reset_postdata();

	return ob_get_clean();
}

/**
 * A little front-end styling.
 */
add_action( 'wp_head', 'stst_inline_styles' );
function stst_inline_styles() {
	echo '<style>
		.stst-form input, .stst-form textarea { width: 100%; max-width: 480px; }
		.stst-notice { padding: .75em 1em; background: #eef7ee; border: 1px solid #cfe6cf; margin-bottom: 1em; }
		.stst-list { list-style: none; padding: 0; }
		.stst-item { margin: 0 0 1.5em; padding: 1em; border-left: 3px solid #ccc; }
		.stst-text { margin: 0 0 .5em; font-style: italic; }
		.stst-author { color: #555; }
	</style>';
}
```

## How to use it

1. **Install:** Put the file at `wp-content/plugins/simple-testimonials.php` and activate it in **Plugins**.
2. **Show the form:** Add the shortcode `[testimonial_form]` to any page.
3. **Show testimonials:** Add `[testimonials]` (optionally `[testimonials count="5"]`) to a page.
4. **Moderate:** Submissions appear under the **Testimonials** menu in wp-admin with status *Pending*. Change status to **Publish** to make one appear.

## Design notes

- **Storage** uses a custom post type rather than a custom table — no `dbDelta`/activation hook needed, and you get the admin list, edit screen, and moderation for free.
- **Security:** nonce verification on submit, plus `sanitize_text_field` / `sanitize_textarea_field` on input and `esc_html` on output.
- **Moderation by default:** new entries save as `pending`. If you'd rather auto-publish, change `'post_status' => 'pending'` to `'publish'` in `stst_handle_submission()`.
- **Length limits:** capped at 80 chars (name) and 500 chars (text), enforced both in the browser and server-side.

Want me to add a spam honeypot, email notification on new submissions, or star ratings?
