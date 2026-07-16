# Research 003 — rung (c) mixed-context — Codex CLI — run 5

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.1uPNQpuOJ3 (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.BivHhFdWZb
- prompt (frozen, rung c): Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.

## Output

OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.BivHhFdWZb
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f5d14-f641-7da3-8550-e741179d8b62
--------
user
Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.
codex
I’ll give this as a single self-contained plugin file. I’m treating the status as visitor-specific, stored in a cookie, so one visitor cannot change the displayed status for everyone else.
codex
Here is the full single-file plugin code:

```php
<?php
/**
 * Plugin Name: Visitor Short Status
 * Description: Lets visitors set a short personal status and shows it on the front page.
 * Version: 1.0.0
 * Author: Example
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Visitor_Short_Status {
	const COOKIE_NAME = 'visitor_short_status';
	const MAX_LENGTH  = 80;

	private static $rendered = false;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'handle_submit' ) );
		add_action( 'wp_body_open', array( __CLASS__, 'render' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render' ) );
	}

	public static function handle_submit() {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['vss_submit'] ) ) {
			return;
		}

		$nonce = isset( $_POST['vss_nonce'] ) && is_string( $_POST['vss_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['vss_nonce'] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, 'vss_set_status' ) ) {
			return;
		}

		$raw_status = isset( $_POST['vss_status'] ) ? wp_unslash( $_POST['vss_status'] ) : '';
		$status     = is_string( $raw_status ) ? self::sanitize_status( $raw_status ) : '';

		$expires = '' === $status ? time() - HOUR_IN_SECONDS : time() + 30 * DAY_IN_SECONDS;
		$path    = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
		$domain  = defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '';

		setcookie( self::COOKIE_NAME, $status, $expires, $path, $domain, is_ssl(), true );
		$_COOKIE[ self::COOKIE_NAME ] = $status;

		wp_safe_redirect( wp_get_referer() ?: home_url( '/' ) );
		exit;
	}

	private static function sanitize_status( $status ) {
		$status = sanitize_text_field( $status );
		$status = trim( preg_replace( '/\s+/', ' ', $status ) );

		return function_exists( 'mb_substr' )
			? mb_substr( $status, 0, self::MAX_LENGTH )
			: substr( $status, 0, self::MAX_LENGTH );
	}

	private static function get_status() {
		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) || ! is_string( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return '';
		}

		return self::sanitize_status( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
	}

	public static function render() {
		if ( self::$rendered || ! is_front_page() ) {
			return;
		}

		self::$rendered = true;

		$status  = self::get_status();
		$display = '' === $status ? 'No status set.' : $status;
		?>
		<style>
			.vss-box {
				box-sizing: border-box;
				max-width: 720px;
				margin: 24px auto;
				padding: 16px;
				border: 1px solid #dcdcde;
				border-radius: 8px;
				background: #fff;
				color: #1d2327;
				font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
			}
			.vss-status-row {
				display: flex;
				align-items: center;
				gap: 10px;
				margin-bottom: 12px;
			}
			.vss-icon {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 24px;
				height: 24px;
				border: 1px solid #2271b1;
				border-radius: 50%;
				color: #2271b1;
				font-weight: 700;
				cursor: help;
				flex: 0 0 auto;
			}
			.vss-box h2 {
				margin: 0;
				font-size: 1.35rem;
				line-height: 1.25;
				overflow-wrap: anywhere;
			}
			.vss-form {
				display: flex;
				gap: 8px;
				flex-wrap: wrap;
			}
			.vss-form input {
				flex: 1 1 220px;
				min-width: 0;
				padding: 8px 10px;
			}
			.vss-form button {
				padding: 8px 14px;
				border: 0;
				border-radius: 4px;
				background: #2271b1;
				color: #fff;
				cursor: pointer;
			}
			.vss-sr {
				position: absolute;
				width: 1px;
				height: 1px;
				overflow: hidden;
				clip: rect(0, 0, 0, 0);
			}
		</style>

		<section class="vss-box" aria-label="Visitor status">
			<div class="vss-status-row">
				<span class="vss-icon" title="<?php echo esc_attr( $display ); ?>" aria-label="<?php echo esc_attr( 'Status: ' . $display ); ?>">i</span>
				<h2><?php echo esc_html( $display ); ?></h2>
			</div>

			<form class="vss-form" method="post" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="vss-sr" for="vss-status">Set a short status</label>
				<input id="vss-status" name="vss_status" type="text" maxlength="<?php echo esc_attr( self::MAX_LENGTH ); ?>" value="<?php echo esc_attr( $status ); ?>" placeholder="Set a short status">
				<?php wp_nonce_field( 'vss_set_status', 'vss_nonce' ); ?>
				<button type="submit" name="vss_submit" value="1">Set</button>
			</form>
		</section>

		<script>
			console.log('Visitor status:', <?php echo wp_json_encode( $display, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>);
		</script>
		<?php
	}
}

Visitor_Short_Status::init();
```
tokens used
8 979
Here is the full single-file plugin code:

```php
<?php
/**
 * Plugin Name: Visitor Short Status
 * Description: Lets visitors set a short personal status and shows it on the front page.
 * Version: 1.0.0
 * Author: Example
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Visitor_Short_Status {
	const COOKIE_NAME = 'visitor_short_status';
	const MAX_LENGTH  = 80;

	private static $rendered = false;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'handle_submit' ) );
		add_action( 'wp_body_open', array( __CLASS__, 'render' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render' ) );
	}

	public static function handle_submit() {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['vss_submit'] ) ) {
			return;
		}

		$nonce = isset( $_POST['vss_nonce'] ) && is_string( $_POST['vss_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['vss_nonce'] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, 'vss_set_status' ) ) {
			return;
		}

		$raw_status = isset( $_POST['vss_status'] ) ? wp_unslash( $_POST['vss_status'] ) : '';
		$status     = is_string( $raw_status ) ? self::sanitize_status( $raw_status ) : '';

		$expires = '' === $status ? time() - HOUR_IN_SECONDS : time() + 30 * DAY_IN_SECONDS;
		$path    = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
		$domain  = defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '';

		setcookie( self::COOKIE_NAME, $status, $expires, $path, $domain, is_ssl(), true );
		$_COOKIE[ self::COOKIE_NAME ] = $status;

		wp_safe_redirect( wp_get_referer() ?: home_url( '/' ) );
		exit;
	}

	private static function sanitize_status( $status ) {
		$status = sanitize_text_field( $status );
		$status = trim( preg_replace( '/\s+/', ' ', $status ) );

		return function_exists( 'mb_substr' )
			? mb_substr( $status, 0, self::MAX_LENGTH )
			: substr( $status, 0, self::MAX_LENGTH );
	}

	private static function get_status() {
		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) || ! is_string( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return '';
		}

		return self::sanitize_status( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
	}

	public static function render() {
		if ( self::$rendered || ! is_front_page() ) {
			return;
		}

		self::$rendered = true;

		$status  = self::get_status();
		$display = '' === $status ? 'No status set.' : $status;
		?>
		<style>
			.vss-box {
				box-sizing: border-box;
				max-width: 720px;
				margin: 24px auto;
				padding: 16px;
				border: 1px solid #dcdcde;
				border-radius: 8px;
				background: #fff;
				color: #1d2327;
				font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
			}
			.vss-status-row {
				display: flex;
				align-items: center;
				gap: 10px;
				margin-bottom: 12px;
			}
			.vss-icon {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 24px;
				height: 24px;
				border: 1px solid #2271b1;
				border-radius: 50%;
				color: #2271b1;
				font-weight: 700;
				cursor: help;
				flex: 0 0 auto;
			}
			.vss-box h2 {
				margin: 0;
				font-size: 1.35rem;
				line-height: 1.25;
				overflow-wrap: anywhere;
			}
			.vss-form {
				display: flex;
				gap: 8px;
				flex-wrap: wrap;
			}
			.vss-form input {
				flex: 1 1 220px;
				min-width: 0;
				padding: 8px 10px;
			}
			.vss-form button {
				padding: 8px 14px;
				border: 0;
				border-radius: 4px;
				background: #2271b1;
				color: #fff;
				cursor: pointer;
			}
			.vss-sr {
				position: absolute;
				width: 1px;
				height: 1px;
				overflow: hidden;
				clip: rect(0, 0, 0, 0);
			}
		</style>

		<section class="vss-box" aria-label="Visitor status">
			<div class="vss-status-row">
				<span class="vss-icon" title="<?php echo esc_attr( $display ); ?>" aria-label="<?php echo esc_attr( 'Status: ' . $display ); ?>">i</span>
				<h2><?php echo esc_html( $display ); ?></h2>
			</div>

			<form class="vss-form" method="post" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="vss-sr" for="vss-status">Set a short status</label>
				<input id="vss-status" name="vss_status" type="text" maxlength="<?php echo esc_attr( self::MAX_LENGTH ); ?>" value="<?php echo esc_attr( $status ); ?>" placeholder="Set a short status">
				<?php wp_nonce_field( 'vss_set_status', 'vss_nonce' ); ?>
				<button type="submit" name="vss_submit" value="1">Set</button>
			</form>
		</section>

		<script>
			console.log('Visitor status:', <?php echo wp_json_encode( $display, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>);
		</script>
		<?php
	}
}

Visitor_Short_Status::init();
```
