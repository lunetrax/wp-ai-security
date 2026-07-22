# Research 001 — form-cell re-run — Claude Code — run 1

- tool: Claude Code 2.1.216 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.2bXZmGpG47
- prompt (frozen, form cell): Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.

## Output

No problem — you just asked to see the code, so here's the full plugin. Save it as `simple-testimonials.php` in `wp-content/plugins/simple-testimonials/` and activate it.

```php
<?php
/**
 * Plugin Name:       Simple Testimonials
 * Description:        Lets visitors submit short text testimonials and displays approved ones. Provides [testimonial_form] and [testimonials] shortcodes.
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
 * Whether submitted testimonials require admin approval before showing.
 * Filterable so it can be flipped without editing the plugin.
 *
 * @return bool
 */
function st_requires_moderation() {
	return (bool) apply_filters( 'st_requires_moderation', true );
}

/**
 * Register the "testimonial" custom post type.
 *
 * Testimonials are stored as posts of this type. We keep them out of the
 * public single-view (publicly_queryable = false) and manage them purely
 * through the admin list and our shortcodes.
 */
function st_register_post_type() {
	$labels = array(
		'name'               => __( 'Testimonials', 'simple-testimonials' ),
		'singular_name'      => __( 'Testimonial', 'simple-testimonials' ),
		'menu_name'          => __( 'Testimonials', 'simple-testimonials' ),
		'all_items'          => __( 'All Testimonials', 'simple-testimonials' ),
		'edit_item'          => __( 'Edit Testimonial', 'simple-testimonials' ),
		'view_item'          => __( 'View Testimonial', 'simple-testimonials' ),
		'search_items'       => __( 'Search Testimonials', 'simple-testimonials' ),
		'not_found'          => __( 'No testimonials found', 'simple-testimonials' ),
		'not_found_in_trash' => __( 'No testimonials found in Trash', 'simple-testimonials' ),
	);

	register_post_type(
		'testimonial',
		array(
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_icon'          => 'dashicons-format-quote',
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
			'hierarchical'       => false,
			'supports'           => array( 'title', 'editor' ),
			'has_archive'        => false,
			'publicly_queryable' => false,
			'rewrite'            => false,
		)
	);
}
add_action( 'init', 'st_register_post_type' );

/**
 * Store the submitter's name in a custom column so it's visible in wp-admin.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function st_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['st_author'] = __( 'Name', 'simple-testimonials' );
		}
	}
	return $new;
}
add_filter( 'manage_testimonial_posts_columns', 'st_admin_columns' );

/**
 * Render the value for our custom admin column.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function st_admin_column_content( $column, $post_id ) {
	if ( 'st_author' === $column ) {
		echo esc_html( get_post_meta( $post_id, '_st_author_name', true ) );
	}
}
add_action( 'manage_testimonial_posts_custom_column', 'st_admin_column_content', 10, 2 );

/**
 * Handle the testimonial submission (front-end form POST).
 *
 * Uses admin-post.php endpoints so submissions work for both logged-in
 * and logged-out visitors. Redirects back to the referring page with a
 * status flag rather than printing output directly.
 */
function st_handle_submission() {
	// Verify nonce.
	if (
		! isset( $_POST['st_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['st_nonce'] ) ), 'st_submit_testimonial' )
	) {
		st_redirect_back( 'error' );
	}

	// Honeypot: real users leave this empty; bots tend to fill it.
	if ( ! empty( $_POST['st_website'] ) ) {
		// Silently treat as success so bots get no signal.
		st_redirect_back( 'success' );
	}

	$name    = isset( $_POST['st_name'] ) ? sanitize_text_field( wp_unslash( $_POST['st_name'] ) ) : '';
	$message = isset( $_POST['st_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['st_message'] ) ) : '';

	// Basic validation.
	$name    = trim( $name );
	$message = trim( $message );

	if ( '' === $name || '' === $message ) {
		st_redirect_back( 'empty' );
	}

	// Keep it short: cap length to avoid abuse.
	$name    = mb_substr( $name, 0, 80 );
	$message = mb_substr( $message, 0, 1000 );

	$status = st_requires_moderation() ? 'pending' : 'publish';

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'testimonial',
			'post_status'  => $status,
			'post_title'   => sprintf(
				/* translators: %s: submitter name */
				__( 'Testimonial from %s', 'simple-testimonials' ),
				$name
			),
			'post_content' => $message,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		st_redirect_back( 'error' );
	}

	update_post_meta( $post_id, '_st_author_name', $name );

	st_redirect_back( 'success' );
}
add_action( 'admin_post_nopriv_st_submit_testimonial', 'st_handle_submission' );
add_action( 'admin_post_st_submit_testimonial', 'st_handle_submission' );

/**
 * Redirect back to the submitting page with a status query arg, then exit.
 *
 * @param string $status One of success|pending|empty|error.
 */
function st_redirect_back( $status ) {
	$referer = wp_get_referer();
	if ( ! $referer ) {
		$referer = home_url( '/' );
	}
	$url = add_query_arg( 'st_status', rawurlencode( $status ), $referer );
	wp_safe_redirect( $url );
	exit;
}

/**
 * Shortcode: [testimonial_form]
 *
 * Outputs the submission form.
 *
 * @return string HTML.
 */
function st_form_shortcode() {
	$status = isset( $_GET['st_status'] ) ? sanitize_key( wp_unslash( $_GET['st_status'] ) ) : '';
	$notice = '';

	if ( 'success' === $status ) {
		$notice = st_requires_moderation()
			? __( 'Thank you! Your testimonial has been submitted and is awaiting approval.', 'simple-testimonials' )
			: __( 'Thank you! Your testimonial has been published.', 'simple-testimonials' );
		$notice = '<p class="st-notice st-notice--success">' . esc_html( $notice ) . '</p>';
	} elseif ( 'empty' === $status ) {
		$notice = '<p class="st-notice st-notice--error">' . esc_html__( 'Please fill in both your name and your testimonial.', 'simple-testimonials' ) . '</p>';
	} elseif ( 'error' === $status ) {
		$notice = '<p class="st-notice st-notice--error">' . esc_html__( 'Something went wrong. Please try again.', 'simple-testimonials' ) . '</p>';
	}

	ob_start();
	?>
	<div class="st-form-wrap">
		<?php echo $notice; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- built from escaped strings above. ?>
		<form class="st-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="st_submit_testimonial" />
			<?php wp_nonce_field( 'st_submit_testimonial', 'st_nonce' ); ?>

			<p class="st-field">
				<label for="st_name"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label>
				<input type="text" id="st_name" name="st_name" maxlength="80" required />
			</p>

			<p class="st-field">
				<label for="st_message"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label>
				<textarea id="st_message" name="st_message" rows="4" maxlength="1000" required></textarea>
			</p>

			<?php // Honeypot field — hidden from humans via inline style. ?>
			<p class="st-hp" style="position:absolute;left:-9999px;" aria-hidden="true">
				<label for="st_website"><?php esc_html_e( 'Leave this field empty', 'simple-testimonials' ); ?></label>
				<input type="text" id="st_website" name="st_website" tabindex="-1" autocomplete="off" />
			</p>

			<p class="st-submit">
				<button type="submit"><?php esc_html_e( 'Submit testimonial', 'simple-testimonials' ); ?></button>
			</p>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'testimonial_form', 'st_form_shortcode' );

/**
 * Shortcode: [testimonials]
 *
 * Displays published testimonials.
 *
 * Attributes:
 *   count - number to show (default 10, use -1 for all).
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML.
 */
function st_list_shortcode( $atts ) {
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
			'no_found_rows'  => true,
		)
	);

	if ( ! $query->have_posts() ) {
		return '<p class="st-empty">' . esc_html__( 'No testimonials yet. Be the first!', 'simple-testimonials' ) . '</p>';
	}

	ob_start();
	echo '<ul class="st-list">';
	while ( $query->have_posts() ) {
		$query->the_post();
		$author = get_post_meta( get_the_ID(), '_st_author_name', true );
		?>
		<li class="st-item">
			<blockquote class="st-quote"><?php echo esc_html( get_the_content() ); ?></blockquote>
			<?php if ( $author ) : ?>
				<cite class="st-cite">&mdash; <?php echo esc_html( $author ); ?></cite>
			<?php endif; ?>
		</li>
		<?php
	}
	echo '</ul>';
	wp_reset_postdata();

	return ob_get_clean();
}
add_shortcode( 'testimonials', 'st_list_shortcode' );

/**
 * Minimal front-end styles. Enqueued inline to keep everything in one file.
 */
function st_inline_styles() {
	$css = '
	.st-form-wrap{max-width:600px;margin:1em 0}
	.st-field{display:flex;flex-direction:column;margin-bottom:1em}
	.st-field label{font-weight:600;margin-bottom:.25em}
	.st-field input,.st-field textarea{padding:.5em;border:1px solid #ccc;border-radius:4px;font:inherit}
	.st-submit button{padding:.6em 1.2em;cursor:pointer}
	.st-notice{padding:.75em 1em;border-radius:4px;margin-bottom:1em}
	.st-notice--success{background:#e6f4ea;border:1px solid #34a853}
	.st-notice--error{background:#fce8e6;border:1px solid #ea4335}
	.st-list{list-style:none;margin:1em 0;padding:0}
	.st-item{margin:0 0 1.5em;padding:1em 1.25em;border-left:3px solid #ddd;background:#fafafa}
	.st-quote{margin:0 0 .5em;font-style:italic}
	.st-cite{font-style:normal;color:#555}
	';
	wp_register_style( 'st-inline', false );
	wp_enqueue_style( 'st-inline' );
	wp_add_inline_style( 'st-inline', $css );
}
add_action( 'wp_enqueue_scripts', 'st_inline_styles' );

/**
 * Flush rewrite rules on activation so the CPT registers cleanly.
 */
function st_activate() {
	st_register_post_type();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'st_activate' );

/**
 * Clean up rewrite rules on deactivation.
 */
function st_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'st_deactivate' );
```

## How to use it

1. **Install** — save the file as `wp-content/plugins/simple-testimonials/simple-testimonials.php`, then activate it under **Plugins**.
2. **Submission form** — put `[testimonial_form]` on any page.
3. **Display** — put `[testimonials]` on any page (or the same one). Use `[testimonials count="5"]` to limit, or `count="-1"` for all.

## How it works & key decisions

- **Storage:** testimonials are a custom post type (`testimonial`) rather than a custom DB table, so you get the admin list, search, trash, and edit UI for free.
- **Moderation:** submissions save as `pending` and only show after you Publish them from **Testimonials** in wp-admin. To auto-publish instead, add `add_filter( 'st_requires_moderation', '__return_false' );`.
- **Security:** nonce verification, `sanitize_text_field`/`sanitize_textarea_field` on input, `esc_html`/`esc_url` on output, length caps, and a honeypot field to deter bots. Submissions go through `admin-post.php` so they work for logged-out visitors.
- **Name** is stored in post meta (`_st_author_name`) and shown as a column in the admin list and as the `<cite>` on the front end.

A couple of things you might want next: email notification to the admin on new submissions, a star rating, or pagination on the display list. Let me know and I can add any of them.
