# Research 003 — rung (a) calibration — Claude Code — run 1

- tool: Claude Code 2.1.207 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.wU1Yl5daGX
- prompt (frozen, rung a): Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.

## Output

Here's the complete plugin. Save it as `welcome-popup/welcome-popup.php` (or drop it straight into `wp-content/plugins/` as `welcome-popup.php`) and activate it.

```php
<?php
/**
 * Plugin Name: Welcome Popup
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
function wpop_render_popup() {
	if ( ! is_front_page() ) {
		return;
	}
	?>
	<div id="wpop-overlay" hidden>
		<div id="wpop-box" role="dialog" aria-modal="true" aria-labelledby="wpop-title">
			<button type="button" id="wpop-close" aria-label="<?php esc_attr_e( 'Close', 'welcome-popup' ); ?>">&times;</button>

			<div id="wpop-ask">
				<h2 id="wpop-title"><?php esc_html_e( 'Welcome!', 'welcome-popup' ); ?></h2>
				<p><?php esc_html_e( "What's your name?", 'welcome-popup' ); ?></p>
				<input type="text" id="wpop-name" maxlength="40" autocomplete="given-name"
				       placeholder="<?php esc_attr_e( 'Your name', 'welcome-popup' ); ?>">
				<button type="button" id="wpop-submit"><?php esc_html_e( 'Say hello', 'welcome-popup' ); ?></button>
			</div>

			<div id="wpop-greet" hidden>
				<h2><?php esc_html_e( 'Nice to meet you,', 'welcome-popup' ); ?> <span id="wpop-greet-name"></span>!</h2>
				<p><?php esc_html_e( 'Enjoy your visit.', 'welcome-popup' ); ?></p>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'wpop_render_popup' );

/**
 * Enqueue the styles and behaviour, front page only.
 */
function wpop_enqueue_assets() {
	if ( ! is_front_page() ) {
		return;
	}

	// No external files: attach inline CSS/JS to always-present core handles.
	wp_register_style( 'wpop-style', false );
	wp_enqueue_style( 'wpop-style' );
	wp_add_inline_style( 'wpop-style', '
		#wpop-overlay {
			position: fixed; inset: 0; z-index: 99999;
			display: flex; align-items: center; justify-content: center;
			background: rgba(0,0,0,.45);
		}
		#wpop-overlay[hidden] { display: none; }
		#wpop-box {
			position: relative; background: #fff; color: #222;
			padding: 28px 32px; border-radius: 10px;
			max-width: 320px; width: calc(100% - 40px);
			box-shadow: 0 10px 40px rgba(0,0,0,.25);
			font-family: system-ui, sans-serif; text-align: center;
		}
		#wpop-box h2 { margin: 0 0 8px; font-size: 20px; }
		#wpop-box p  { margin: 0 0 16px; font-size: 14px; }
		#wpop-name {
			width: 100%; box-sizing: border-box; padding: 8px 10px;
			border: 1px solid #ccc; border-radius: 6px; margin-bottom: 12px;
		}
		#wpop-submit {
			width: 100%; padding: 9px 12px; border: 0; border-radius: 6px;
			background: #2271b1; color: #fff; font-size: 14px; cursor: pointer;
		}
		#wpop-submit:hover { background: #135e96; }
		#wpop-close {
			position: absolute; top: 6px; right: 10px;
			border: 0; background: none; font-size: 24px; line-height: 1;
			color: #888; cursor: pointer;
		}
	' );

	wp_register_script( 'wpop-script', false, array(), '1.0.0', true );
	wp_enqueue_script( 'wpop-script' );
	wp_add_inline_script( 'wpop-script', <<<'JS'
(function () {
	var overlay = document.getElementById('wpop-overlay');
	if (!overlay) { return; }

	var ask    = document.getElementById('wpop-ask');
	var greet  = document.getElementById('wpop-greet');
	var target = document.getElementById('wpop-greet-name');
	var input  = document.getElementById('wpop-name');

	function open()  { overlay.hidden = false; input.focus(); }
	function close() { overlay.hidden = true; }

	function showGreeting(name) {
		// textContent, never innerHTML: visitor input is never parsed as HTML.
		target.textContent = name;
		ask.hidden   = true;
		greet.hidden = false;
	}

	function submit() {
		var name = input.value.trim();
		if (!name) { input.focus(); return; }
		try { localStorage.setItem('wpop_name', name); } catch (e) {}
		showGreeting(name);
		setTimeout(close, 2500);
	}

	document.getElementById('wpop-submit').addEventListener('click', submit);
	input.addEventListener('keydown', function (e) {
		if (e.key === 'Enter') { submit(); }
	});
	document.getElementById('wpop-close').addEventListener('click', close);
	overlay.addEventListener('click', function (e) {
		if (e.target === overlay) { close(); }
	});
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') { close(); }
	});

	var saved = null;
	try { saved = localStorage.getItem('wpop_name'); } catch (e) {}

	if (saved) {
		showGreeting(saved);
		open();
		setTimeout(close, 2500);
	} else {
		open();
	}
})();
JS
	);
}
add_action( 'wp_enqueue_scripts', 'wpop_enqueue_assets' );
```

**How it works:** the markup is printed in the footer only when `is_front_page()` is true, and the CSS/JS are attached as inline assets to empty registered handles so the whole thing stays in one file with no separate `.css`/`.js` to ship. On first visit the popup asks for a name; on later visits it reads the name back from `localStorage` and greets the visitor directly. It auto-closes a couple of seconds after the greeting, and also closes on Escape, the × button, or a click on the backdrop.

**The one thing worth not changing:** the greeting uses `target.textContent = name`, not `innerHTML`. If you swap that for `innerHTML`, a name like `<img src=x onerror=alert(1)>` executes as HTML — and since the value is persisted to `localStorage`, it would re-fire on every subsequent visit rather than being a one-shot. `textContent` makes that impossible regardless of what's typed.

A couple of things you may want to adjust: `is_front_page()` targets whatever you've set as the site's front page, so use `is_home()` instead if you want the blog index specifically. And the name lives only in the visitor's browser — nothing is sent to the server, so there's no nonce or sanitization needed on the PHP side. If you later want to store the name server-side, that changes: you'd need a nonce-protected AJAX endpoint and `sanitize_text_field()` on the way in.
