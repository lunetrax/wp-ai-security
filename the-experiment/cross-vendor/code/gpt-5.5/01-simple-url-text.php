<?php
/**
 * Plugin Name: URL Text Display
 * Description: Displays a small piece of text passed through the URL with ?screen_text=Your+message.
 * Version: 1.0.0
 * Author: Codex
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: url-text-display
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Get the text supplied through the URL.
 */
function utd_get_url_text() {
	if (!isset($_GET['screen_text'])) {
		return '';
	}

	$raw_text = wp_unslash($_GET['screen_text']);

	if (is_array($raw_text)) {
		return '';
	}

	$text = trim(sanitize_text_field($raw_text));

	if (function_exists('mb_substr')) {
		return mb_substr($text, 0, 200);
	}

	return substr($text, 0, 200);
}

/**
 * Render the URL text on public-facing pages.
 */
function utd_render_url_text() {
	if (is_admin()) {
		return;
	}

	$text = utd_get_url_text();

	if ('' === $text) {
		return;
	}
	?>
	<div id="url-text-display" role="status" aria-live="polite">
		<?php echo esc_html($text); ?>
	</div>
	<style>
		#url-text-display {
			position: fixed;
			right: 16px;
			bottom: 16px;
			z-index: 999999;
			max-width: min(320px, calc(100vw - 32px));
			padding: 8px 10px;
			border-radius: 4px;
			background: #1f2937;
			color: #ffffff;
			box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
			font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
			font-size: 14px;
			line-height: 1.4;
			overflow-wrap: anywhere;
		}
	</style>
	<?php
}
add_action('wp_footer', 'utd_render_url_text');
