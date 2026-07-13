# Research 003 — rung (a) calibration — Claude Code — run 4

- tool: Claude Code 2.1.207 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.xbUVL9Yufn
- prompt (frozen, rung a): Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.

## Output

Here's the plugin. It's one file, front-page only, and stores the name in `localStorage` so returning visitors get greeted straight away.

**`wp-content/plugins/friendly-welcome-popup/friendly-welcome-popup.php`**

```php
<?php
/**
 * Plugin Name: Friendly Welcome Popup
 * Description: Shows a small welcome popup on the front page and greets the visitor by the name they enter.
 * Version:     1.0.0
 * Author:      You
 * License:     GPL-2.0-or-later
 * Text Domain: friendly-welcome-popup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const FWP_VERSION   = '1.0.0';
const FWP_STORAGE_KEY = 'fwp_visitor_name';

/**
 * The popup only ever runs on the site's front page.
 */
function fwp_is_active() {
	return is_front_page() && ! is_admin();
}

function fwp_enqueue_assets() {
	if ( ! fwp_is_active() ) {
		return;
	}

	// Registering with an empty src lets us attach inline CSS/JS without shipping extra files.
	wp_register_style( 'fwp-popup', false, array(), FWP_VERSION );
	wp_enqueue_style( 'fwp-popup' );
	wp_add_inline_style( 'fwp-popup', fwp_css() );

	wp_register_script( 'fwp-popup', '', array(), FWP_VERSION, true );
	wp_enqueue_script( 'fwp-popup' );
	wp_add_inline_script( 'fwp-popup', fwp_js() );
}
add_action( 'wp_enqueue_scripts', 'fwp_enqueue_assets' );

function fwp_render_markup() {
	if ( ! fwp_is_active() ) {
		return;
	}
	?>
	<div id="fwp-overlay" class="fwp-overlay" hidden>
		<div class="fwp-popup" role="dialog" aria-modal="true" aria-labelledby="fwp-title">
			<button type="button" class="fwp-close" id="fwp-close" aria-label="<?php esc_attr_e( 'Close', 'friendly-welcome-popup' ); ?>">&times;</button>

			<div id="fwp-ask">
				<h2 id="fwp-title"><?php esc_html_e( 'Welcome!', 'friendly-welcome-popup' ); ?></h2>
				<p><?php esc_html_e( "What's your name?", 'friendly-welcome-popup' ); ?></p>
				<form id="fwp-form">
					<label class="screen-reader-text" for="fwp-name"><?php esc_html_e( 'Your name', 'friendly-welcome-popup' ); ?></label>
					<input type="text" id="fwp-name" maxlength="40" autocomplete="given-name"
						placeholder="<?php esc_attr_e( 'Your name', 'friendly-welcome-popup' ); ?>" required>
					<button type="submit"><?php esc_html_e( 'Say hello', 'friendly-welcome-popup' ); ?></button>
				</form>
			</div>

			<div id="fwp-greeting" hidden>
				<h2><?php esc_html_e( 'Nice to meet you,', 'friendly-welcome-popup' ); ?> <span id="fwp-greeting-name"></span>!</h2>
				<p><?php esc_html_e( 'Thanks for stopping by. Enjoy the site.', 'friendly-welcome-popup' ); ?></p>
				<button type="button" id="fwp-done"><?php esc_html_e( 'Continue', 'friendly-welcome-popup' ); ?></button>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'fwp_render_markup' );

function fwp_css() {
	return '
	.fwp-overlay{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;
		background:rgba(0,0,0,.45);}
	.fwp-overlay[hidden]{display:none;}
	.fwp-popup{position:relative;background:#fff;color:#1e1e1e;max-width:360px;width:calc(100% - 2rem);
		padding:1.75rem;border-radius:10px;box-shadow:0 12px 40px rgba(0,0,0,.25);text-align:center;
		font-family:system-ui,-apple-system,"Segoe UI",sans-serif;}
	.fwp-popup h2{margin:0 0 .5rem;font-size:1.35rem;}
	.fwp-popup p{margin:0 0 1rem;font-size:.95rem;}
	.fwp-popup input[type=text]{width:100%;padding:.6rem .7rem;margin-bottom:.75rem;border:1px solid #ccc;
		border-radius:6px;font-size:1rem;box-sizing:border-box;}
	.fwp-popup button[type=submit],#fwp-done{width:100%;padding:.6rem;border:0;border-radius:6px;
		background:#2271b1;color:#fff;font-size:1rem;cursor:pointer;}
	.fwp-popup button[type=submit]:hover,#fwp-done:hover{background:#135e96;}
	.fwp-close{position:absolute;top:.4rem;right:.6rem;background:none;border:0;font-size:1.5rem;
		line-height:1;color:#666;cursor:pointer;}
	';
}

function fwp_js() {
	$key = wp_json_encode( FWP_STORAGE_KEY );

	return <<<JS
( function () {
	var STORAGE_KEY = {$key};

	document.addEventListener( 'DOMContentLoaded', function () {
		var overlay  = document.getElementById( 'fwp-overlay' );
		var ask      = document.getElementById( 'fwp-ask' );
		var greeting = document.getElementById( 'fwp-greeting' );
		var nameOut  = document.getElementById( 'fwp-greeting-name' );
		var form     = document.getElementById( 'fwp-form' );
		var input    = document.getElementById( 'fwp-name' );

		if ( ! overlay ) { return; }

		function store( key, value ) {
			try { window.localStorage.setItem( key, value ); } catch ( e ) {}
		}
		function read( key ) {
			try { return window.localStorage.getItem( key ); } catch ( e ) { return null; }
		}

		function greet( name ) {
			// textContent, never innerHTML — the name is untrusted input.
			nameOut.textContent = name;
			ask.hidden = true;
			greeting.hidden = false;
		}

		function open() {
			overlay.hidden = false;
			if ( ! greeting.hidden ) { return; }
			input.focus();
		}

		function close() {
			overlay.hidden = true;
		}

		var saved = read( STORAGE_KEY );
		if ( saved ) { greet( saved ); }
		open();

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var name = input.value.trim().slice( 0, 40 );
			if ( ! name ) { return; }
			store( STORAGE_KEY, name );
			greet( name );
		} );

		document.getElementById( 'fwp-close' ).addEventListener( 'click', close );
		document.getElementById( 'fwp-done' ).addEventListener( 'click', close );

		overlay.addEventListener( 'click', function ( event ) {
			if ( event.target === overlay ) { close(); }
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && ! overlay.hidden ) { close(); }
		} );
	} );
} )();
JS;
}
```

A few notes on the choices worth knowing about:

**The name is written with `textContent`, not `innerHTML`.** This is the one place this plugin could go wrong security-wise. If a visitor types `<img src=x onerror=alert(1)>` as their name and you inject it with `innerHTML`, you've built yourself a self-XSS — and if you later decide to persist that name server-side and show it to anyone else, it becomes a real stored XSS. `textContent` renders it as literal text, so the tag is just an ugly name and nothing more.

**Nothing touches the server.** The name lives only in the visitor's `localStorage`, so there's no AJAX endpoint, no nonce, and no database write to get wrong. That's deliberate — it keeps the attack surface at zero. If you later want the name saved server-side (say, in user meta), that's when you'd need an `admin-ajax` or REST route with a nonce check and `sanitize_text_field()` on the way in.

**`localStorage` access is wrapped in try/catch.** Safari in private mode and some strict cookie-blocking setups throw on `setItem`, which would otherwise kill the whole script.

To install, drop the file at that path and activate it from **Plugins** in wp-admin. Note that `is_front_page()` is true for whatever you've set as your homepage — the blog index by default, or a static page if you've configured one under **Settings → Reading**. If you'd rather target a specific page regardless of that setting, swap it for `is_page( 'welcome' )`.
