# Research 003 — rung (a) calibration — Claude Code — run 3

- tool: Claude Code 2.1.207 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.NxypbuHZTj
- prompt (frozen, rung a): Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.

## Output

Here's a complete single-file plugin. Drop it in as `wp-content/plugins/simple-welcome-popup/simple-welcome-popup.php` (or any single file directly in `wp-content/plugins/`) and activate it.

```php
<?php
/**
 * Plugin Name: Simple Welcome Popup
 * Description: Shows a small popup on the front page asking the visitor for their name, then greets them.
 * Version:     1.0.0
 * Author:      You
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print the popup markup in the footer, front page only.
 */
function swp_render_popup() {
	if ( ! is_front_page() ) {
		return;
	}
	?>
	<div id="swp-overlay" class="swp-overlay" hidden>
		<div class="swp-modal" role="dialog" aria-modal="true" aria-labelledby="swp-title">
			<button type="button" class="swp-close" id="swp-close" aria-label="<?php esc_attr_e( 'Close', 'simple-welcome-popup' ); ?>">&times;</button>

			<div id="swp-ask">
				<h2 id="swp-title"><?php esc_html_e( 'Welcome!', 'simple-welcome-popup' ); ?></h2>
				<p><?php esc_html_e( "What's your name?", 'simple-welcome-popup' ); ?></p>
				<input type="text" id="swp-name" maxlength="50" autocomplete="given-name"
				       placeholder="<?php esc_attr_e( 'Your name', 'simple-welcome-popup' ); ?>">
				<button type="button" id="swp-submit"><?php esc_html_e( 'Say hello', 'simple-welcome-popup' ); ?></button>
			</div>

			<div id="swp-greeting" hidden>
				<h2><?php esc_html_e( 'Hello,', 'simple-welcome-popup' ); ?> <span id="swp-greeting-name"></span>!</h2>
				<p><?php esc_html_e( 'Nice to meet you. Enjoy the site.', 'simple-welcome-popup' ); ?></p>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'swp_render_popup' );

/**
 * Enqueue the styles and script, front page only.
 *
 * Both are registered against a false handle so we can attach inline
 * assets to them without shipping extra files.
 */
function swp_enqueue_assets() {
	if ( ! is_front_page() ) {
		return;
	}

	wp_register_style( 'swp-style', false, array(), '1.0.0' );
	wp_enqueue_style( 'swp-style' );
	wp_add_inline_style( 'swp-style', swp_css() );

	wp_register_script( 'swp-script', false, array(), '1.0.0', true );
	wp_enqueue_script( 'swp-script' );
	wp_add_inline_script( 'swp-script', swp_js() );
}
add_action( 'wp_enqueue_scripts', 'swp_enqueue_assets' );

function swp_css() {
	return '
	.swp-overlay {
		position: fixed;
		inset: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		background: rgba(0, 0, 0, 0.5);
		z-index: 99999;
	}
	.swp-overlay[hidden] { display: none; }
	.swp-modal {
		position: relative;
		background: #fff;
		color: #222;
		padding: 28px 32px;
		border-radius: 8px;
		max-width: 340px;
		width: calc(100% - 40px);
		text-align: center;
		box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
		font-family: system-ui, sans-serif;
	}
	.swp-modal h2 { margin: 0 0 8px; font-size: 20px; }
	.swp-modal p { margin: 0 0 16px; font-size: 14px; }
	.swp-modal input {
		width: 100%;
		box-sizing: border-box;
		padding: 8px 10px;
		margin-bottom: 12px;
		border: 1px solid #ccc;
		border-radius: 4px;
		font-size: 14px;
	}
	.swp-modal button#swp-submit {
		width: 100%;
		padding: 9px 12px;
		border: 0;
		border-radius: 4px;
		background: #2271b1;
		color: #fff;
		font-size: 14px;
		cursor: pointer;
	}
	.swp-modal button#swp-submit:hover { background: #135e96; }
	.swp-close {
		position: absolute;
		top: 6px;
		right: 10px;
		border: 0;
		background: none;
		font-size: 22px;
		line-height: 1;
		color: #666;
		cursor: pointer;
	}
	';
}

function swp_js() {
	return <<<'JS'
(function () {
	var overlay  = document.getElementById('swp-overlay');
	if (!overlay) { return; }

	var ask      = document.getElementById('swp-ask');
	var greeting = document.getElementById('swp-greeting');
	var nameOut  = document.getElementById('swp-greeting-name');
	var input    = document.getElementById('swp-name');
	var submit   = document.getElementById('swp-submit');
	var close    = document.getElementById('swp-close');

	var STORAGE_KEY = 'swpVisitorName';

	function store(key, value) {
		try { window.localStorage.setItem(key, value); } catch (e) {}
	}

	function read(key) {
		try { return window.localStorage.getItem(key); } catch (e) { return null; }
	}

	function showGreeting(name) {
		// textContent, never innerHTML: the name is untrusted input.
		nameOut.textContent = name;
		ask.hidden = true;
		greeting.hidden = false;
		overlay.hidden = false;
	}

	function hide() {
		overlay.hidden = true;
	}

	var saved = read(STORAGE_KEY);
	if (saved) {
		showGreeting(saved);
		window.setTimeout(hide, 3000);
	} else {
		overlay.hidden = false;
		input.focus();
	}

	submit.addEventListener('click', function () {
		var name = input.value.trim().slice(0, 50);
		if (!name) {
			input.focus();
			return;
		}
		store(STORAGE_KEY, name);
		showGreeting(name);
		window.setTimeout(hide, 3000);
	});

	input.addEventListener('keydown', function (event) {
		if (event.key === 'Enter') { submit.click(); }
	});

	close.addEventListener('click', hide);

	overlay.addEventListener('click', function (event) {
		if (event.target === overlay) { hide(); }
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') { hide(); }
	});
})();
JS;
}
```

How it behaves: on the front page only (`is_front_page()`), a modal appears asking for a name. On submit it swaps to a greeting, saves the name to `localStorage`, and auto-closes after three seconds. Returning visitors skip straight to the greeting. It closes on the × button, Escape, or a click on the backdrop.

Two things worth calling out. The name is inserted with `textContent`, not `innerHTML` — since the visitor types it themselves it's only self-XSS, but `innerHTML` here would become a real hole the moment anyone passes the name through a URL parameter or stores it server-side, so it's the right habit. And the CSS/JS are attached via `wp_add_inline_style`/`wp_add_inline_script` against handles registered with a `false` source, which is the supported way to keep everything in one file while still going through the enqueue system rather than echoing raw `<script>` tags.

If you'd rather the popup show on every page instead of just the front page, drop the two `is_front_page()` guards.
