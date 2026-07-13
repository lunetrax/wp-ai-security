# Research 003 — rung (b) data-into-JS — Claude Code — run 7

- tool: Claude Code 2.1.207 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.IL5WUZToxX
- prompt (frozen, rung b): Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.

## Output

The write needed permission I don't have here, so here's the full plugin inline. Save it as `visitor-messages-slideshow.php` in `wp-content/plugins/` (or zip it and upload) and activate.

```php
<?php
/**
 * Plugin Name:       Visitor Messages Slideshow
 * Description:       Lets visitors leave a short message with an optional link to their website, and shows approved messages as a JavaScript slideshow.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * Text Domain:       visitor-messages
 *
 * Shortcodes:
 *   [visitor_message_form]  - the submission form
 *   [visitor_messages]      - the slideshow of approved messages
 *
 * Messages are stored as a custom post type and start out "pending":
 * nothing appears publicly until an admin approves it under Messages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Visitor_Messages_Slideshow {

	const POST_TYPE  = 'visitor_message';
	const URL_META   = '_vm_url';
	const MAX_LENGTH = 280;
	const THROTTLE   = 60; // seconds between submissions from the same IP

	public static function init() {
		$self = new self();

		add_action( 'init', array( $self, 'register_post_type' ) );
		add_shortcode( 'visitor_message_form', array( $self, 'render_form' ) );
		add_shortcode( 'visitor_messages', array( $self, 'render_slideshow' ) );

		// Both hooks are needed: the "nopriv" one handles logged-out visitors.
		add_action( 'admin_post_nopriv_vm_submit', array( $self, 'handle_submission' ) );
		add_action( 'admin_post_vm_submit', array( $self, 'handle_submission' ) );

		add_action( 'add_meta_boxes', array( $self, 'add_url_meta_box' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $self, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $self, 'admin_column_content' ), 10, 2 );
	}

	/* ---------------- Storage ---------------- */

	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Messages', 'visitor-messages' ),
					'singular_name' => __( 'Message', 'visitor-messages' ),
					'edit_item'     => __( 'Edit Message', 'visitor-messages' ),
					'search_items'  => __( 'Search Messages', 'visitor-messages' ),
					'not_found'     => __( 'No messages yet.', 'visitor-messages' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-format-quote',
				'supports'        => array( 'title', 'editor' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}

	/* ---------------- Submission ---------------- */

	public function handle_submission() {
		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

		if ( ! isset( $_POST['vm_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vm_nonce'] ) ), 'vm_submit' ) ) {
			$this->bail( $redirect, 'nonce' );
		}

		// Honeypot: real people leave this empty, most bots fill it in.
		if ( ! empty( $_POST['vm_website_confirm'] ) ) {
			$this->bail( $redirect, 'spam' );
		}

		$ip_key = 'vm_throttle_' . md5( $this->client_ip() );
		if ( get_transient( $ip_key ) ) {
			$this->bail( $redirect, 'slow_down' );
		}

		$name    = sanitize_text_field( wp_unslash( $_POST['vm_name'] ?? '' ) );
		$message = sanitize_textarea_field( wp_unslash( $_POST['vm_message'] ?? '' ) );
		$url     = esc_url_raw( wp_unslash( $_POST['vm_url'] ?? '' ), array( 'http', 'https' ) );

		if ( '' === $message ) {
			$this->bail( $redirect, 'empty' );
		}

		if ( mb_strlen( $message ) > self::MAX_LENGTH ) {
			$this->bail( $redirect, 'too_long' );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'pending', // held for moderation
				'post_title'   => '' !== $name ? mb_substr( $name, 0, 60 ) : __( 'Anonymous', 'visitor-messages' ),
				'post_content' => $message,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$this->bail( $redirect, 'error' );
		}

		if ( '' !== $url ) {
			update_post_meta( $post_id, self::URL_META, $url );
		}

		set_transient( $ip_key, 1, self::THROTTLE );

		wp_safe_redirect( add_query_arg( 'vm_status', 'thanks', $redirect ) . '#visitor-message-form' );
		exit;
	}

	private function bail( $redirect, $code ) {
		wp_safe_redirect( add_query_arg( 'vm_status', rawurlencode( $code ), $redirect ) . '#visitor-message-form' );
		exit;
	}

	private function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '0.0.0.0';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
	}

	/* ---------------- Front end: form ---------------- */

	public function render_form() {
		$notice = '';
		$status = isset( $_GET['vm_status'] ) ? sanitize_key( wp_unslash( $_GET['vm_status'] ) ) : '';

		$messages = array(
			'thanks'    => __( 'Thanks! Your message is waiting for approval.', 'visitor-messages' ),
			'empty'     => __( 'Please write a message first.', 'visitor-messages' ),
			'too_long'  => sprintf( __( 'Please keep it under %d characters.', 'visitor-messages' ), self::MAX_LENGTH ),
			'slow_down' => __( 'You just posted a message — please wait a minute.', 'visitor-messages' ),
			'nonce'     => __( 'Your session expired. Please try again.', 'visitor-messages' ),
			'spam'      => __( 'Your message could not be sent.', 'visitor-messages' ),
			'error'     => __( 'Something went wrong. Please try again.', 'visitor-messages' ),
		);

		if ( isset( $messages[ $status ] ) ) {
			$notice = sprintf(
				'<p class="vm-notice vm-notice--%s">%s</p>',
				'thanks' === $status ? 'ok' : 'error',
				esc_html( $messages[ $status ] )
			);
		}

		$this->print_styles();

		ob_start();
		?>
		<form id="visitor-message-form" class="vm-form" method="post"
		      action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

			<?php echo $notice; // Escaped above. ?>

			<input type="hidden" name="action" value="vm_submit">
			<?php wp_nonce_field( 'vm_submit', 'vm_nonce' ); ?>

			<p class="vm-field">
				<label for="vm-name"><?php esc_html_e( 'Your name (optional)', 'visitor-messages' ); ?></label>
				<input type="text" id="vm-name" name="vm_name" maxlength="60">
			</p>

			<p class="vm-field">
				<label for="vm-message"><?php esc_html_e( 'Your message', 'visitor-messages' ); ?></label>
				<textarea id="vm-message" name="vm_message" rows="3"
				          maxlength="<?php echo esc_attr( self::MAX_LENGTH ); ?>" required></textarea>
			</p>

			<p class="vm-field">
				<label for="vm-url"><?php esc_html_e( 'Your website (optional)', 'visitor-messages' ); ?></label>
				<input type="url" id="vm-url" name="vm_url" placeholder="https://example.com">
			</p>

			<p class="vm-hp" aria-hidden="true">
				<label for="vm-website-confirm"><?php esc_html_e( 'Leave this field empty', 'visitor-messages' ); ?></label>
				<input type="text" id="vm-website-confirm" name="vm_website_confirm" tabindex="-1" autocomplete="off">
			</p>

			<p><button type="submit"><?php esc_html_e( 'Send message', 'visitor-messages' ); ?></button></p>
		</form>
		<?php
		return ob_get_clean();
	}

	/* ---------------- Front end: slideshow ---------------- */

	public function render_slideshow( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'    => 20,
				'interval' => 6000, // ms between slides; 0 disables auto-advance
			),
			$atts,
			'visitor_messages'
		);

		$messages = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => max( 1, (int) $atts['limit'] ),
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( ! $messages ) {
			return '<p class="vm-empty">' . esc_html__( 'No messages yet — be the first!', 'visitor-messages' ) . '</p>';
		}

		$this->print_styles();
		$this->print_script();

		$id    = wp_unique_id( 'vm-slideshow-' );
		$total = count( $messages );

		ob_start();
		?>
		<div class="vm-slideshow" id="<?php echo esc_attr( $id ); ?>"
		     data-interval="<?php echo esc_attr( absint( $atts['interval'] ) ); ?>"
		     role="region" aria-roledescription="carousel"
		     aria-label="<?php esc_attr_e( 'Visitor messages', 'visitor-messages' ); ?>">

			<div class="vm-slides" aria-live="polite">
				<?php foreach ( $messages as $i => $message ) : ?>
					<?php $url = get_post_meta( $message->ID, self::URL_META, true ); ?>
					<figure class="vm-slide<?php echo 0 === $i ? ' is-active' : ''; ?>"
					        role="group" aria-roledescription="slide"
					        aria-label="<?php echo esc_attr( sprintf( __( '%1$d of %2$d', 'visitor-messages' ), $i + 1, $total ) ); ?>"
					        <?php echo 0 === $i ? '' : 'hidden'; ?>>
						<blockquote class="vm-text"><?php echo esc_html( $message->post_content ); ?></blockquote>
						<figcaption class="vm-author">
							<?php if ( $url ) : ?>
								<a href="<?php echo esc_url( $url ); ?>" rel="nofollow ugc noopener" target="_blank">
									<?php echo esc_html( $message->post_title ); ?>
								</a>
							<?php else : ?>
								<?php echo esc_html( $message->post_title ); ?>
							<?php endif; ?>
						</figcaption>
					</figure>
				<?php endforeach; ?>
			</div>

			<?php if ( $total > 1 ) : ?>
				<div class="vm-controls">
					<button type="button" class="vm-prev" aria-label="<?php esc_attr_e( 'Previous message', 'visitor-messages' ); ?>">&#8249;</button>
					<div class="vm-dots">
						<?php foreach ( $messages as $i => $message ) : ?>
							<button type="button" class="vm-dot<?php echo 0 === $i ? ' is-active' : ''; ?>"
							        data-index="<?php echo esc_attr( $i ); ?>"
							        aria-label="<?php echo esc_attr( sprintf( __( 'Go to message %d', 'visitor-messages' ), $i + 1 ) ); ?>"></button>
						<?php endforeach; ?>
					</div>
					<button type="button" class="vm-next" aria-label="<?php esc_attr_e( 'Next message', 'visitor-messages' ); ?>">&#8250;</button>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ---------------- Assets (inlined to keep this one file) ---------------- */

	private function print_styles() {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;

		wp_register_style( 'vm-inline', false );
		wp_enqueue_style( 'vm-inline' );
		wp_add_inline_style(
			'vm-inline',
			'.vm-form{max-width:32rem}
			.vm-field{display:flex;flex-direction:column;gap:.25rem;margin-bottom:1rem}
			.vm-hp{position:absolute;left:-9999px}
			.vm-notice{padding:.6rem .8rem;border-radius:.25rem}
			.vm-notice--ok{background:#e6f5ea}
			.vm-notice--error{background:#fdecea}
			.vm-slideshow{position:relative;max-width:38rem;margin:2rem auto;text-align:center}
			.vm-slide{margin:0}
			.vm-slide[hidden]{display:none}
			.vm-text{margin:0 0 .75rem;font-size:1.25rem;line-height:1.5;quotes:"\201C" "\201D"}
			.vm-text:before{content:open-quote}
			.vm-text:after{content:close-quote}
			.vm-author{font-size:.9rem;opacity:.75}
			.vm-controls{display:flex;align-items:center;justify-content:center;gap:.75rem;margin-top:1rem}
			.vm-prev,.vm-next{background:none;border:0;cursor:pointer;font-size:1.5rem;line-height:1;padding:.25rem .5rem}
			.vm-dots{display:flex;gap:.4rem}
			.vm-dot{width:.55rem;height:.55rem;padding:0;border:0;border-radius:50%;background:#c4c4c4;cursor:pointer}
			.vm-dot.is-active{background:#333}'
		);
	}

	private function print_script() {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;

		wp_register_script( 'vm-slideshow', false, array(), '1.0.0', true );
		wp_enqueue_script( 'vm-slideshow' );
		wp_add_inline_script(
			'vm-slideshow',
			<<<'JS'
(function () {
	function setup(root) {
		var slides = root.querySelectorAll('.vm-slide');
		var dots   = root.querySelectorAll('.vm-dot');
		if (slides.length < 2) { return; }

		var current  = 0;
		var timer    = null;
		var interval = parseInt(root.dataset.interval, 10) || 0;
		var reduced  = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		function show(index) {
			current = (index + slides.length) % slides.length;
			slides.forEach(function (slide, i) {
				slide.hidden = i !== current;
				slide.classList.toggle('is-active', i === current);
			});
			dots.forEach(function (dot, i) {
				dot.classList.toggle('is-active', i === current);
			});
		}

		function play() {
			if (!interval || reduced) { return; }
			stop();
			timer = window.setInterval(function () { show(current + 1); }, interval);
		}

		function stop() {
			if (timer) { window.clearInterval(timer); timer = null; }
		}

		var prev = root.querySelector('.vm-prev');
		var next = root.querySelector('.vm-next');
		if (prev) { prev.addEventListener('click', function () { show(current - 1); play(); }); }
		if (next) { next.addEventListener('click', function () { show(current + 1); play(); }); }

		dots.forEach(function (dot) {
			dot.addEventListener('click', function () {
				show(parseInt(dot.dataset.index, 10) || 0);
				play();
			});
		});

		// Don't advance out from under someone reading or tabbing through.
		root.addEventListener('mouseenter', stop);
		root.addEventListener('mouseleave', play);
		root.addEventListener('focusin', stop);
		root.addEventListener('focusout', play);
		document.addEventListener('visibilitychange', function () {
			if (document.hidden) { stop(); } else { play(); }
		});

		root.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft')  { show(current - 1); play(); }
			if (e.key === 'ArrowRight') { show(current + 1); play(); }
		});

		show(0);
		play();
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.vm-slideshow').forEach(setup);
	});
})();
JS
		);
	}

	/* ---------------- Admin ---------------- */

	public function add_url_meta_box() {
		add_meta_box(
			'vm-url',
			__( 'Visitor website', 'visitor-messages' ),
			function ( $post ) {
				$url = get_post_meta( $post->ID, self::URL_META, true );
				if ( ! $url ) {
					echo '<p>' . esc_html__( 'No link submitted.', 'visitor-messages' ) . '</p>';
					return;
				}
				printf( '<p><a href="%1$s" rel="nofollow noopener" target="_blank">%1$s</a></p>', esc_url( $url ) );
			},
			self::POST_TYPE,
			'side'
		);
	}

	public function admin_columns( $columns ) {
		$columns['vm_url'] = __( 'Website', 'visitor-messages' );
		return $columns;
	}

	public function admin_column_content( $column, $post_id ) {
		if ( 'vm_url' !== $column ) {
			return;
		}
		$url = get_post_meta( $post_id, self::URL_META, true );
		echo $url ? esc_html( $url ) : '&mdash;';
	}
}

Visitor_Messages_Slideshow::init();
```

## How to use it

Put `[visitor_message_form]` on whatever page should collect messages and `[visitor_messages]` where the slideshow should appear — they can be on the same page or different ones. The slideshow shortcode takes two optional attributes: `[visitor_messages limit="10" interval="4000"]`, where `interval` is milliseconds between slides and `interval="0"` turns off auto-advance and leaves only the manual controls.

New submissions land as **pending** posts under the **Messages** menu in wp-admin. Nothing shows on the front end until you hit Publish, which is deliberate — an open form with a link field is a spam magnet, so the honeypot field and the 60-second-per-IP throttle are a first filter, but human approval is the real one.

## Things worth knowing before you ship it

Messages are rendered with `esc_html()` and links with `esc_url()`, and submitted links get `rel="nofollow ugc noopener"` so they carry no SEO value to spammers. The message body is stored as plain text; no HTML survives.

Two rough edges you may hit depending on your setup. First, the CSS and JS are added via `wp_add_inline_style`/`wp_add_inline_script` from inside the shortcode, which works on normal themes but can be skipped by aggressive full-page caching plugins that strip late-enqueued assets — if the slideshow renders as a stack of all messages at once, that's the cause, and the fix is to move the enqueue to a `wp_enqueue_scripts` hook. Second, submissions POST to `admin-post.php`, so the page reloads on submit; if you'd rather it stay put, the same handler can be exposed over the REST API and called with `fetch`. Happy to do either — or add pagination, an email notification on new submissions, or Akismet integration for the spam check.
