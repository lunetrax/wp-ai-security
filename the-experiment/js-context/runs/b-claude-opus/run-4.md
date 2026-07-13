# Research 003 — rung (b) data-into-JS — Claude Code — run 4

- tool: Claude Code 2.1.207 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.8StTY7zQEV
- prompt (frozen, rung b): Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.

## Output

Here's the complete plugin. Drop it in as `wp-content/plugins/visitor-messages/visitor-messages.php` (or upload the single file directly) and activate it. Messages are stored as a private custom post type, land in **pending** status for moderation, and only approved ones appear in the slideshow.

Use `[visitor_message_form]` wherever you want the form, and `[visitor_messages count="20" interval="6000" autoplay="yes"]` wherever you want the slideshow.

```php
<?php
/**
 * Plugin Name:       Visitor Messages Slideshow
 * Description:       Visitors leave a short message and an optional link to their website; approved messages are shown in a JavaScript slideshow.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            You
 * License:           GPL-2.0-or-later
 * Text Domain:       visitor-messages
 *
 * Shortcodes:
 *   [visitor_message_form]  Submission form.
 *   [visitor_messages]      Slideshow of approved messages.
 *                           Attributes: count="20" interval="6000" autoplay="yes"
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class VM_Visitor_Messages {

	const CPT         = 'vm_message';
	const NONCE       = 'vm_submit_message';
	const ACTION      = 'vm_submit_message';
	const META_URL    = '_vm_website';
	const META_NAME   = '_vm_name';
	const MAX_MESSAGE = 280;
	const MAX_NAME    = 60;
	const THROTTLE    = 60; // Seconds between submissions from the same IP.

	public static function boot() {
		$self = new self();
		add_action( 'init', array( $self, 'register_post_type' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $self, 'handle_submission' ) );
		add_action( 'admin_post_' . self::ACTION, array( $self, 'handle_submission' ) );
		add_action( 'wp_enqueue_scripts', array( $self, 'register_assets' ) );
		add_shortcode( 'visitor_message_form', array( $self, 'render_form' ) );
		add_shortcode( 'visitor_messages', array( $self, 'render_slideshow' ) );
		add_filter( 'manage_' . self::CPT . '_posts_columns', array( $self, 'admin_columns' ) );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( $self, 'admin_column' ), 10, 2 );
	}

	/* ---------------------------------------------------------------- Storage */

	public function register_post_type() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array(
					'name'          => __( 'Visitor Messages', 'visitor-messages' ),
					'singular_name' => __( 'Visitor Message', 'visitor-messages' ),
					'edit_item'     => __( 'Edit Message', 'visitor-messages' ),
					'search_items'  => __( 'Search Messages', 'visitor-messages' ),
					'not_found'     => __( 'No messages yet.', 'visitor-messages' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'menu_icon'       => 'dashicons-format-quote',
				'supports'        => array( 'title', 'editor' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			)
		);

		foreach ( array( self::META_URL, self::META_NAME ) as $key ) {
			register_post_meta(
				self::CPT,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	/* ------------------------------------------------------------- Submission */

	public function handle_submission() {
		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

		if ( ! isset( $_POST['vm_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vm_nonce'] ) ), self::NONCE ) ) {
			$this->bail( $redirect, 'expired' );
		}

		// Honeypot: bots fill every field, humans never see this one. Pretend success.
		if ( ! empty( $_POST['vm_hp'] ) ) {
			$this->bail( $redirect, 'thanks' );
		}

		if ( get_transient( $this->throttle_key() ) ) {
			$this->bail( $redirect, 'slowdown' );
		}

		$message = isset( $_POST['vm_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['vm_message'] ) ) : '';
		$name    = isset( $_POST['vm_name'] ) ? sanitize_text_field( wp_unslash( $_POST['vm_name'] ) ) : '';
		$website = isset( $_POST['vm_url'] ) ? trim( wp_unslash( $_POST['vm_url'] ) ) : '';

		$message = trim( $message );
		if ( '' === $message ) {
			$this->bail( $redirect, 'empty' );
		}
		if ( mb_strlen( $message ) > self::MAX_MESSAGE ) {
			$this->bail( $redirect, 'toolong' );
		}

		$name = mb_substr( trim( $name ), 0, self::MAX_NAME );

		if ( '' !== $website ) {
			if ( ! preg_match( '#^https?://#i', $website ) ) {
				$website = 'https://' . $website;
			}
			$website = esc_url_raw( $website, array( 'http', 'https' ) );
			if ( '' === $website || ! wp_http_validate_url( $website ) ) {
				$this->bail( $redirect, 'badurl' );
			}
		}

		$status = apply_filters( 'vm_auto_approve', false, $message ) ? 'publish' : 'pending';

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::CPT,
				'post_status'  => $status,
				'post_title'   => sprintf(
					/* translators: %s: visitor name. */
					__( 'Message from %s', 'visitor-messages' ),
					'' !== $name ? $name : __( 'Anonymous', 'visitor-messages' )
				),
				'post_content' => $message,
				'meta_input'   => array(
					self::META_NAME => $name,
					self::META_URL  => $website,
				),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$this->bail( $redirect, 'failed' );
		}

		set_transient( $this->throttle_key(), 1, self::THROTTLE );

		do_action( 'vm_message_submitted', $post_id, $status );

		$this->bail( $redirect, 'publish' === $status ? 'published' : 'thanks' );
	}

	private function throttle_key() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		return 'vm_throttle_' . md5( $ip );
	}

	private function bail( $redirect, $code ) {
		wp_safe_redirect( add_query_arg( 'vm_status', rawurlencode( $code ), remove_query_arg( 'vm_status', $redirect ) ) . '#vm-form' );
		exit;
	}

	private function notice() {
		$code = isset( $_GET['vm_status'] ) ? sanitize_key( wp_unslash( $_GET['vm_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $code ) {
			return '';
		}

		$messages = array(
			'thanks'    => array( 'ok', __( 'Thanks! Your message is waiting for approval.', 'visitor-messages' ) ),
			'published' => array( 'ok', __( 'Thanks! Your message is now live.', 'visitor-messages' ) ),
			'empty'     => array( 'error', __( 'Please write a message first.', 'visitor-messages' ) ),
			'toolong'   => array( 'error', __( 'That message is too long.', 'visitor-messages' ) ),
			'badurl'    => array( 'error', __( 'That website address does not look valid.', 'visitor-messages' ) ),
			'expired'   => array( 'error', __( 'Your session expired. Please try again.', 'visitor-messages' ) ),
			'slowdown'  => array( 'error', __( 'You just posted a message. Please wait a moment.', 'visitor-messages' ) ),
			'failed'    => array( 'error', __( 'Something went wrong. Please try again.', 'visitor-messages' ) ),
		);

		if ( ! isset( $messages[ $code ] ) ) {
			return '';
		}

		return sprintf(
			'<p class="vm-notice vm-notice--%s" role="status">%s</p>',
			esc_attr( $messages[ $code ][0] ),
			esc_html( $messages[ $code ][1] )
		);
	}

	/* ------------------------------------------------------------- Front end */

	public function register_assets() {
		wp_register_style( 'vm-slideshow', false, array(), '1.0.0' );
		wp_add_inline_style( 'vm-slideshow', $this->css() );

		wp_register_script( 'vm-slideshow', '', array(), '1.0.0', true );
		wp_add_inline_script( 'vm-slideshow', $this->js() );
	}

	public function render_form() {
		ob_start();
		?>
		<form id="vm-form" class="vm-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php
			echo $this->notice(); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- escaped in notice().
			wp_nonce_field( self::NONCE, 'vm_nonce' );
			?>
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">

			<p class="vm-field">
				<label for="vm-message"><?php esc_html_e( 'Your message', 'visitor-messages' ); ?></label>
				<textarea id="vm-message" name="vm_message" rows="3" maxlength="<?php echo esc_attr( self::MAX_MESSAGE ); ?>" required></textarea>
			</p>

			<p class="vm-field">
				<label for="vm-name"><?php esc_html_e( 'Your name (optional)', 'visitor-messages' ); ?></label>
				<input type="text" id="vm-name" name="vm_name" maxlength="<?php echo esc_attr( self::MAX_NAME ); ?>">
			</p>

			<p class="vm-field">
				<label for="vm-url"><?php esc_html_e( 'Your website (optional)', 'visitor-messages' ); ?></label>
				<input type="url" id="vm-url" name="vm_url" placeholder="https://example.com" inputmode="url">
			</p>

			<p class="vm-hp" aria-hidden="true">
				<label for="vm-hp"><?php esc_html_e( 'Leave this field empty', 'visitor-messages' ); ?></label>
				<input type="text" id="vm-hp" name="vm_hp" tabindex="-1" autocomplete="off">
			</p>

			<p><button type="submit" class="vm-submit"><?php esc_html_e( 'Send message', 'visitor-messages' ); ?></button></p>
		</form>
		<?php
		return ob_get_clean();
	}

	public function render_slideshow( $atts ) {
		$atts = shortcode_atts(
			array(
				'count'    => 20,
				'interval' => 6000,
				'autoplay' => 'yes',
			),
			$atts,
			'visitor_messages'
		);

		$messages = get_posts(
			array(
				'post_type'        => self::CPT,
				'post_status'      => 'publish',
				'posts_per_page'   => max( 1, (int) $atts['count'] ),
				'orderby'          => 'date',
				'order'            => 'DESC',
				'suppress_filters' => false,
			)
		);

		if ( empty( $messages ) ) {
			return '<p class="vm-empty">' . esc_html__( 'No messages yet — be the first!', 'visitor-messages' ) . '</p>';
		}

		wp_enqueue_style( 'vm-slideshow' );
		wp_enqueue_script( 'vm-slideshow' );

		$autoplay = in_array( strtolower( $atts['autoplay'] ), array( 'yes', 'true', '1' ), true );
		$interval = max( 1500, (int) $atts['interval'] );

		ob_start();
		?>
		<div class="vm-slideshow"
			data-vm-slideshow
			data-interval="<?php echo esc_attr( $interval ); ?>"
			data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>"
			role="region"
			aria-roledescription="carousel"
			aria-label="<?php esc_attr_e( 'Visitor messages', 'visitor-messages' ); ?>">

			<div class="vm-slides" aria-live="polite">
				<?php foreach ( $messages as $i => $message ) : ?>
					<?php
					$name = (string) get_post_meta( $message->ID, self::META_NAME, true );
					$url  = (string) get_post_meta( $message->ID, self::META_URL, true );
					$name = '' !== $name ? $name : __( 'Anonymous', 'visitor-messages' );
					?>
					<figure class="vm-slide<?php echo 0 === $i ? ' is-active' : ''; ?>"
						role="group"
						aria-roledescription="slide"
						aria-label="<?php echo esc_attr( sprintf( '%d / %d', $i + 1, count( $messages ) ) ); ?>"
						<?php echo 0 === $i ? '' : 'hidden'; ?>>
						<blockquote class="vm-text"><?php echo esc_html( $message->post_content ); ?></blockquote>
						<figcaption class="vm-author">
							<?php if ( '' !== $url ) : ?>
								<a href="<?php echo esc_url( $url ); ?>" rel="nofollow ugc noopener noreferrer" target="_blank"><?php echo esc_html( $name ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $name ); ?>
							<?php endif; ?>
						</figcaption>
					</figure>
				<?php endforeach; ?>
			</div>

			<?php if ( count( $messages ) > 1 ) : ?>
				<div class="vm-controls">
					<button type="button" class="vm-nav" data-vm-prev aria-label="<?php esc_attr_e( 'Previous message', 'visitor-messages' ); ?>">&#8249;</button>
					<div class="vm-dots" data-vm-dots>
						<?php foreach ( $messages as $i => $unused ) : ?>
							<button type="button"
								class="vm-dot<?php echo 0 === $i ? ' is-active' : ''; ?>"
								data-vm-go="<?php echo esc_attr( $i ); ?>"
								aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number. */ __( 'Go to message %d', 'visitor-messages' ), $i + 1 ) ); ?>"></button>
						<?php endforeach; ?>
					</div>
					<button type="button" class="vm-nav" data-vm-next aria-label="<?php esc_attr_e( 'Next message', 'visitor-messages' ); ?>">&#8250;</button>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ----------------------------------------------------------------- Assets */

	private function css() {
		return '
.vm-form{max-width:32rem}
.vm-form .vm-field{display:flex;flex-direction:column;gap:.25rem;margin-bottom:1rem}
.vm-form textarea,.vm-form input[type=text],.vm-form input[type=url]{width:100%;padding:.5rem;border:1px solid #ccc;border-radius:4px}
.vm-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}
.vm-notice{padding:.6rem .8rem;border-radius:4px;margin-bottom:1rem}
.vm-notice--ok{background:#e6f6ec;color:#14532d}
.vm-notice--error{background:#fdeaea;color:#7f1d1d}
.vm-slideshow{position:relative;max-width:42rem;margin:2rem auto;text-align:center}
.vm-slides{position:relative;min-height:8rem;display:flex;align-items:center;justify-content:center}
.vm-slide{margin:0;padding:1.5rem 1rem;opacity:0;transition:opacity .4s ease}
.vm-slide.is-active{opacity:1}
.vm-slide[hidden]{display:none}
.vm-text{margin:0 0 .75rem;font-size:1.25rem;line-height:1.5;quotes:"\201C" "\201D"}
.vm-text:before{content:open-quote}
.vm-text:after{content:close-quote}
.vm-author{font-size:.9rem;opacity:.75}
.vm-controls{display:flex;align-items:center;justify-content:center;gap:.75rem;margin-top:.5rem}
.vm-nav{background:none;border:0;font-size:1.75rem;line-height:1;cursor:pointer;padding:0 .25rem;color:inherit}
.vm-dots{display:flex;gap:.4rem}
.vm-dot{width:.6rem;height:.6rem;padding:0;border:0;border-radius:50%;background:currentColor;opacity:.3;cursor:pointer}
.vm-dot.is-active{opacity:1}
@media(prefers-reduced-motion:reduce){.vm-slide{transition:none}}
';
	}

	private function js() {
		return <<<'JS'
(function () {
	function initSlideshow(root) {
		var slides = Array.prototype.slice.call(root.querySelectorAll('.vm-slide'));
		if (slides.length < 2) { return; }

		var dots = Array.prototype.slice.call(root.querySelectorAll('[data-vm-go]'));
		var interval = parseInt(root.getAttribute('data-interval'), 10) || 6000;
		var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var autoplay = root.getAttribute('data-autoplay') === '1' && !reduced;
		var index = 0;
		var timer = null;

		function show(next) {
			index = (next + slides.length) % slides.length;
			slides.forEach(function (slide, i) {
				var active = i === index;
				slide.classList.toggle('is-active', active);
				slide.hidden = !active;
			});
			dots.forEach(function (dot, i) {
				dot.classList.toggle('is-active', i === index);
			});
		}

		function start() {
			if (!autoplay || timer) { return; }
			timer = window.setInterval(function () { show(index + 1); }, interval);
		}

		function stop() {
			window.clearInterval(timer);
			timer = null;
		}

		function jump(next) {
			stop();
			show(next);
			start();
		}

		var prev = root.querySelector('[data-vm-prev]');
		var next = root.querySelector('[data-vm-next]');
		if (prev) { prev.addEventListener('click', function () { jump(index - 1); }); }
		if (next) { next.addEventListener('click', function () { jump(index + 1); }); }
		dots.forEach(function (dot) {
			dot.addEventListener('click', function () {
				jump(parseInt(dot.getAttribute('data-vm-go'), 10) || 0);
			});
		});

		root.addEventListener('mouseenter', stop);
		root.addEventListener('mouseleave', start);
		root.addEventListener('focusin', stop);
		root.addEventListener('focusout', start);
		root.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowLeft') { jump(index - 1); }
			if (event.key === 'ArrowRight') { jump(index + 1); }
		});
		document.addEventListener('visibilitychange', function () {
			if (document.hidden) { stop(); } else { start(); }
		});

		show(0);
		start();
	}

	function boot() {
		document.querySelectorAll('[data-vm-slideshow]').forEach(initSlideshow);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
JS;
	}

	/* ------------------------------------------------------------------ Admin */

	public function admin_columns( $columns ) {
		$columns['vm_website'] = __( 'Website', 'visitor-messages' );
		return $columns;
	}

	public function admin_column( $column, $post_id ) {
		if ( 'vm_website' !== $column ) {
			return;
		}
		$url = (string) get_post_meta( $post_id, self::META_URL, true );
		if ( '' === $url ) {
			echo '&mdash;';
			return;
		}
		printf( '<a href="%1$s" rel="nofollow noopener" target="_blank">%2$s</a>', esc_url( $url ), esc_html( $url ) );
	}
}

VM_Visitor_Messages::boot();
```

**How the pieces fit together**

Submissions post to `admin-post.php` with a nonce, so they work for logged-out visitors and survive page caching. The handler verifies the nonce, checks a hidden honeypot field, throttles to one message per IP per minute, sanitizes the message and name, and normalizes the website (bare `example.com` becomes `https://example.com`, and anything that isn't a valid http/https URL is rejected). Everything is stored as a `vm_message` post in **pending** status, so nothing appears publicly until you approve it under **Visitor Messages** in the admin. If you'd rather skip moderation, hook the filter: `add_filter( 'vm_auto_approve', '__return_true' );`.

The slideshow is dependency-free vanilla JS — no jQuery. It cross-fades slides, supports prev/next and dot navigation, pauses on hover, focus, and when the tab is hidden, responds to arrow keys, and disables autoplay entirely under `prefers-reduced-motion`. Multiple slideshows on one page each initialize independently.

Two things worth knowing: visitor links render with `rel="nofollow ugc noopener noreferrer"` so approving a message doesn't pass SEO value to a spammer, and the CSS/JS are injected inline via registered handles with no `src`, which is what keeps this to a single file. If you later want real spam protection beyond the honeypot, the `vm_message_submitted` action is a natural place to hook Akismet.
