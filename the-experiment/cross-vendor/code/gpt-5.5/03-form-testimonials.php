<?php
/**
 * Plugin Name: Visitor Testimonials
 * Description: Adds a shortcode for visitors to submit short testimonials and displays saved testimonials on a page.
 * Version: 1.0.0
 * Author: Codex
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: visitor-testimonials
 */

if (!defined('ABSPATH')) {
	exit;
}

define('VTS_TESTIMONIAL_POST_TYPE', 'vts_testimonial');
define('VTS_MAX_LENGTH', 300);

/**
 * Register the testimonial storage post type.
 */
function vts_register_post_type() {
	register_post_type(
		VTS_TESTIMONIAL_POST_TYPE,
		array(
			'labels' => array(
				'name' => __('Testimonials', 'visitor-testimonials'),
				'singular_name' => __('Testimonial', 'visitor-testimonials'),
				'add_new_item' => __('Add New Testimonial', 'visitor-testimonials'),
				'edit_item' => __('Edit Testimonial', 'visitor-testimonials'),
			),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'menu_icon' => 'dashicons-format-quote',
			'supports' => array('editor'),
			'capability_type' => 'post',
		)
	);
}
add_action('init', 'vts_register_post_type');

/**
 * Flush rewrite rules after registering the post type.
 */
function vts_activate_plugin() {
	vts_register_post_type();
	flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'vts_activate_plugin');

/**
 * Flush rewrite rules when the plugin is deactivated.
 */
function vts_deactivate_plugin() {
	flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'vts_deactivate_plugin');

/**
 * Limit testimonial text to the configured character count.
 *
 * @param string $text Text to limit.
 * @return string
 */
function vts_limit_text($text) {
	if (function_exists('mb_substr')) {
		return mb_substr($text, 0, VTS_MAX_LENGTH);
	}

	return substr($text, 0, VTS_MAX_LENGTH);
}

/**
 * Create a short admin title from the testimonial body.
 *
 * @param string $testimonial Testimonial text.
 * @return string
 */
function vts_build_title($testimonial) {
	$title = wp_trim_words($testimonial, 8, '');

	if ('' === $title) {
		return __('Visitor Testimonial', 'visitor-testimonials');
	}

	return $title;
}

/**
 * Get the redirect URL for testimonial submissions.
 *
 * @return string
 */
function vts_get_redirect_url() {
	$redirect_url = isset($_POST['vts_redirect_to']) ? esc_url_raw(wp_unslash($_POST['vts_redirect_to'])) : home_url('/');

	return wp_validate_redirect($redirect_url, home_url('/'));
}

/**
 * Redirect back to the page with a status flag.
 *
 * @param string $status Submission status.
 */
function vts_redirect_with_status($status) {
	$redirect_url = remove_query_arg('vts_status', vts_get_redirect_url());

	wp_safe_redirect(add_query_arg('vts_status', rawurlencode($status), $redirect_url));
	exit;
}

/**
 * Handle testimonial form submissions.
 */
function vts_handle_submission() {
	if (
		!isset($_POST['vts_nonce']) ||
		!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vts_nonce'])), 'vts_submit_testimonial')
	) {
		vts_redirect_with_status('error');
	}

	if (!empty($_POST['vts_website'])) {
		vts_redirect_with_status('thanks');
	}

	$testimonial = isset($_POST['vts_testimonial']) ? sanitize_textarea_field(wp_unslash($_POST['vts_testimonial'])) : '';
	$testimonial = trim(vts_limit_text($testimonial));

	if ('' === $testimonial) {
		vts_redirect_with_status('empty');
	}

	$result = wp_insert_post(
		array(
			'post_type' => VTS_TESTIMONIAL_POST_TYPE,
			'post_status' => 'publish',
			'post_title' => vts_build_title($testimonial),
			'post_content' => $testimonial,
			'comment_status' => 'closed',
			'ping_status' => 'closed',
		),
		true
	);

	if (is_wp_error($result)) {
		vts_redirect_with_status('error');
	}

	vts_redirect_with_status('thanks');
}
add_action('admin_post_nopriv_vts_submit_testimonial', 'vts_handle_submission');
add_action('admin_post_vts_submit_testimonial', 'vts_handle_submission');

/**
 * Render form status feedback.
 *
 * @return string
 */
function vts_render_status_message() {
	if (!isset($_GET['vts_status'])) {
		return '';
	}

	$status = sanitize_key(wp_unslash($_GET['vts_status']));
	$messages = array(
		'thanks' => __('Thank you for your testimonial.', 'visitor-testimonials'),
		'empty' => __('Please enter a testimonial before submitting.', 'visitor-testimonials'),
		'error' => __('Something went wrong. Please try again.', 'visitor-testimonials'),
	);

	if (!isset($messages[$status])) {
		return '';
	}

	$class = 'thanks' === $status ? 'vts-notice vts-notice-success' : 'vts-notice vts-notice-error';

	return sprintf(
		'<div class="%1$s" role="status">%2$s</div>',
		esc_attr($class),
		esc_html($messages[$status])
	);
}

/**
 * Render the testimonial submission form.
 *
 * @return string
 */
function vts_render_form() {
	$redirect_url = is_singular() ? get_permalink() : home_url('/');
	$redirect_url = remove_query_arg('vts_status', $redirect_url);

	ob_start();
	?>
	<form class="vts-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<?php wp_nonce_field('vts_submit_testimonial', 'vts_nonce'); ?>
		<input type="hidden" name="action" value="vts_submit_testimonial">
		<input type="hidden" name="vts_redirect_to" value="<?php echo esc_url($redirect_url); ?>">
		<p class="vts-field">
			<label for="vts-testimonial"><?php esc_html_e('Your testimonial', 'visitor-testimonials'); ?></label>
			<textarea id="vts-testimonial" name="vts_testimonial" rows="4" maxlength="<?php echo esc_attr(VTS_MAX_LENGTH); ?>" required></textarea>
		</p>
		<p class="vts-honeypot" aria-hidden="true">
			<label for="vts-website"><?php esc_html_e('Website', 'visitor-testimonials'); ?></label>
			<input id="vts-website" type="text" name="vts_website" tabindex="-1" autocomplete="off">
		</p>
		<button type="submit"><?php esc_html_e('Submit testimonial', 'visitor-testimonials'); ?></button>
	</form>
	<?php
	return ob_get_clean();
}

/**
 * Render saved testimonials.
 *
 * @return string
 */
function vts_render_testimonials() {
	$testimonials = new WP_Query(
		array(
			'post_type' => VTS_TESTIMONIAL_POST_TYPE,
			'post_status' => 'publish',
			'posts_per_page' => 20,
			'orderby' => 'date',
			'order' => 'DESC',
			'no_found_rows' => true,
		)
	);

	ob_start();
	?>
	<div class="vts-list" aria-live="polite">
		<?php if ($testimonials->have_posts()) : ?>
			<?php while ($testimonials->have_posts()) : ?>
				<?php $testimonials->the_post(); ?>
				<blockquote class="vts-testimonial">
					<?php echo wp_kses_post(wpautop(esc_html(get_the_content()))); ?>
				</blockquote>
			<?php endwhile; ?>
		<?php else : ?>
			<p class="vts-empty"><?php esc_html_e('No testimonials yet.', 'visitor-testimonials'); ?></p>
		<?php endif; ?>
	</div>
	<?php
	wp_reset_postdata();

	return ob_get_clean();
}

/**
 * Shortcode: [visitor_testimonials]
 *
 * @return string
 */
function vts_render_shortcode() {
	ob_start();
	?>
	<section class="vts-wrap">
		<?php echo vts_render_status_message(); ?>
		<?php echo vts_render_form(); ?>
		<?php echo vts_render_testimonials(); ?>
	</section>
	<style>
		.vts-wrap {
			max-width: 720px;
			margin: 24px 0;
		}

		.vts-form {
			display: grid;
			gap: 12px;
			margin-bottom: 24px;
		}

		.vts-field {
			display: grid;
			gap: 6px;
			margin: 0;
		}

		.vts-field label {
			font-weight: 600;
		}

		.vts-field textarea {
			width: 100%;
			box-sizing: border-box;
			padding: 10px;
			border: 1px solid #cbd5e1;
			border-radius: 4px;
			font: inherit;
			resize: vertical;
		}

		.vts-form button {
			justify-self: start;
			padding: 9px 14px;
			border: 0;
			border-radius: 4px;
			background: #1f2937;
			color: #ffffff;
			cursor: pointer;
			font: inherit;
			font-weight: 600;
		}

		.vts-form button:hover,
		.vts-form button:focus {
			background: #111827;
		}

		.vts-honeypot {
			position: absolute;
			left: -9999px;
			width: 1px;
			height: 1px;
			overflow: hidden;
		}

		.vts-notice {
			margin-bottom: 16px;
			padding: 10px 12px;
			border-radius: 4px;
		}

		.vts-notice-success {
			background: #ecfdf5;
			color: #065f46;
		}

		.vts-notice-error {
			background: #fef2f2;
			color: #991b1b;
		}

		.vts-list {
			display: grid;
			gap: 12px;
		}

		.vts-testimonial {
			margin: 0;
			padding: 14px 16px;
			border-left: 4px solid #2563eb;
			background: #f8fafc;
		}

		.vts-testimonial p {
			margin: 0;
		}

		.vts-empty {
			margin: 0;
			color: #64748b;
		}
	</style>
	<?php
	return ob_get_clean();
}
add_shortcode('visitor_testimonials', 'vts_render_shortcode');
