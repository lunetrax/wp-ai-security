# Research 003 — rung (a) calibration — Claude Code — run 2

- tool: Claude Code 2.1.207 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.GUBlbTdI0y
- prompt (frozen, rung a): Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.

## Output

Here's the complete plugin. Drop it in `wp-content/plugins/welcome-popup/welcome-popup.php` and activate it — no build step, no dependencies.

```php
<?php
/**
 * Plugin Name: Welcome Popup
 * Description: Shows a small popup on the front page asking the visitor for their name, then greets them.
 * Version:     1.0.0
 * License:     GPL-2.0-or-later
 * Text Domain: welcome-popup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function welcome_popup_is_active() {
	return is_front_page() && ! is_admin();
}

add_action( 'wp_head', 'welcome_popup_styles' );
function welcome_popup_styles() {
	if ( ! welcome_popup_is_active() ) {
		return;
	}
	?>
	<style id="welcome-popup-css">
		#welcome-popup[hidden] { display: none; }
		#welcome-popup {
			position: fixed;
			right: 24px;
			bottom: 24px;
			z-index: 99999;
			width: 280px;
			padding: 20px;
			border-radius: 10px;
			background: #fff;
			color: #1e1e1e;
			box-shadow: 0 8px 28px rgba( 0, 0, 0, .18 );
			font: 15px/1.45 system-ui, -apple-system, "Segoe UI", sans-serif;
		}
		#welcome-popup p { margin: 0 0 12px; }
		#welcome-popup-name {
			width: 100%;
			box-sizing: border-box;
			margin-bottom: 10px;
			padding: 8px 10px;
			border: 1px solid #c3c4c7;
			border-radius: 6px;
			font: inherit;
		}
		#welcome-popup-submit {
			width: 100%;
			padding: 9px 12px;
			border: 0;
			border-radius: 6px;
			background: #2271b1;
			color: #fff;
			font: inherit;
			cursor: pointer;
		}
		#welcome-popup-submit:hover { background: #135e96; }
		#welcome-popup-close {
			position: absolute;
			top: 6px;
			right: 8px;
			border: 0;
			background: none;
			color: #787c82;
			font-size: 20px;
			line-height: 1;
			cursor: pointer;
		}
		#welcome-popup-greeting { margin: 0; font-weight: 600; }
	</style>
	<?php
}

add_action( 'wp_footer', 'welcome_popup_markup' );
function welcome_popup_markup() {
	if ( ! welcome_popup_is_active() ) {
		return;
	}
	?>
	<div id="welcome-popup" role="dialog" aria-labelledby="welcome-popup-title" hidden>
		<button type="button" id="welcome-popup-close" aria-label="<?php esc_attr_e( 'Close', 'welcome-popup' ); ?>">&times;</button>

		<form id="welcome-popup-form">
			<p id="welcome-popup-title"><?php esc_html_e( "Welcome! What's your name?", 'welcome-popup' ); ?></p>
			<input type="text" id="welcome-popup-name" maxlength="40" autocomplete="given-name"
				placeholder="<?php esc_attr_e( 'Your name', 'welcome-popup' ); ?>" />
			<button type="submit" id="welcome-popup-submit"><?php esc_html_e( 'Say hello', 'welcome-popup' ); ?></button>
		</form>

		<p id="welcome-popup-greeting" hidden></p>
	</div>

	<script id="welcome-popup-js">
	( function () {
		var popup    = document.getElementById( 'welcome-popup' );
		var form     = document.getElementById( 'welcome-popup-form' );
		var field    = document.getElementById( 'welcome-popup-name' );
		var greeting = document.getElementById( 'welcome-popup-greeting' );
		var closeBtn = document.getElementById( 'welcome-popup-close' );

		function greet( name ) {
			form.hidden = true;
			// textContent, never innerHTML: the name is untrusted input.
			greeting.textContent = 'Hi, ' + name + '. Nice to see you!';
			greeting.hidden = false;
		}

		var saved = window.localStorage.getItem( 'welcomePopupName' );
		if ( saved ) {
			greet( saved );
		}

		popup.hidden = false;

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			var name = field.value.trim();
			if ( ! name ) {
				field.focus();
				return;
			}

			window.localStorage.setItem( 'welcomePopupName', name );
			greet( name );

			window.setTimeout( function () {
				popup.hidden = true;
			}, 4000 );
		}, false );

		closeBtn.addEventListener( 'click', function () {
			popup.hidden = true;
		}, false );
	}() );
	</script>
	<?php
}
```

How it behaves: on the front page only (`is_front_page()`), a card slides into the bottom-right with a name field. On submit, the form is swapped for "Hi, *name*. Nice to see you!", the name is saved to `localStorage`, and the popup auto-dismisses after four seconds. Returning visitors are greeted immediately without seeing the form. The × closes it for the current page view.

One thing worth calling out, since it's the only real trap in a plugin like this: the greeting is written with `textContent`, not `innerHTML`. The name is untrusted input, and building the greeting as an HTML string would let a visitor type `<img src=x onerror=...>` and have it execute — self-XSS on its own, but it becomes a real hole the moment you persist that name server-side and render it for anyone else. `textContent` renders the input as literal text, so there's nothing to escape.

Two things you may want to adjust:

- **Nothing is sent to the server.** The name lives only in the visitor's browser. If you want to store it in WordPress, you'll need an admin-ajax or REST endpoint with a nonce and `sanitize_text_field()` on the way in, plus escaping on the way out — a meaningfully larger surface.
- **`is_front_page()` vs. `is_home()`.** If your site shows the blog index as its front page, `is_front_page()` is still the right call; switch to `is_home()` only if you specifically want the posts page when a static front page is configured.
