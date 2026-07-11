<?php
/**
 * Plugin Name: URL Link Display
 * Description: Displays a clickable website link passed through the URL with ?website_url=https://example.com.
 * Version: 1.0.0
 * Author: Codex
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: url-link-display
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Read and validate the website URL supplied through the query string.
 */
function uld_get_website_url() {
	if (!isset($_GET['website_url'])) {
		return '';
	}

	$raw_url = wp_unslash($_GET['website_url']);

	if (is_array($raw_url)) {
		return '';
	}

	$url = trim(sanitize_text_field($raw_url));

	if ('' === $url || strlen($url) > 2048) {
		return '';
	}

	if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
		$url = 'https://' . ltrim($url, '/');
	}

	$parts = wp_parse_url($url);

	if (
		empty($parts['scheme']) ||
		empty($parts['host']) ||
		!in_array(strtolower($parts['scheme']), array('http', 'https'), true)
	) {
		return '';
	}

	return esc_url_raw($url, array('http', 'https'));
}

/**
 * Render the URL as a clickable link on public-facing pages.
 */
function uld_render_website_link() {
	if (is_admin()) {
		return;
	}

	$url = uld_get_website_url();

	if ('' === $url) {
		return;
	}

	$link_text = wp_parse_url($url, PHP_URL_HOST);

	if ('' === $link_text || null === $link_text) {
		$link_text = $url;
	}
	?>
	<div id="url-link-display">
		<a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer">
			<?php echo esc_html($link_text); ?>
		</a>
	</div>
	<style>
		#url-link-display {
			position: fixed;
			right: 16px;
			bottom: 16px;
			z-index: 999999;
			max-width: min(320px, calc(100vw - 32px));
			padding: 8px 10px;
			border-radius: 4px;
			background: #1f2937;
			box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
			font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
			font-size: 14px;
			line-height: 1.4;
			overflow-wrap: anywhere;
		}

		#url-link-display a {
			color: #ffffff;
			text-decoration: underline;
			text-underline-offset: 2px;
		}
	</style>
	<?php
}
add_action('wp_footer', 'uld_render_website_link');
