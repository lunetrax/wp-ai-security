# Research 003 — rung (b) data-into-JS — Claude Code — run 6

- tool: Claude Code 2.1.207 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.ikkQ15i3qB
- prompt (frozen, rung b): Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.

## Output

Here's the full plugin. Save it as `visitor-messages-slideshow.php` in `wp-content/plugins/` (or zip it and upload) and activate.

```php
<?php
/**
 * Plugin Name:       Visitor Messages Slideshow
 * Description:       Lets visitors leave a short message with an optional website link, and displays approved messages in a JavaScript slideshow.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * Text Domain:       visitor-messages-slideshow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class VMS_Plugin {

	const POST_TYPE     = 'vms_message';
	const META_URL      = '_vms_website';
	const MAX_LENGTH    = 280;
	const THROTTLE_SECS = 60;

	public static function init() {
		$self = new self();

		add_action( 'init', array( $self, 'register_post_type' ) );
		add_action( 'admin_post_nopriv_vms_submit', array( $self, 'handle_submission' ) );
		add_action( 'admin_post_vms_submit', array( $self, 'handle_submission' ) );

		add_shortcode( 'visitor_message_form', array( $self, 'render_form' ) );
		add_shortcode( 'visitor_messages', array( $self, 'render_slideshow' ) );

		add_action( 'add_meta_boxes', array( $self, 'add_meta_box' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $self, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $self, 'admin_column_content' ), 10, 2 );
	}

	/* ------------------------------------------------------------------ */
	/* Storage                                                            */
	/* ------------------------------------------------------------------ */

	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Visitor Messages', 'visitor-messages-slideshow' ),
					'singular_name' => __( 'Visitor Message', 'visitor-messages-slideshow' ),
					'edit_item'     => __( 'Edit Visitor Message', 'visitor-messages-slideshow' ),
					'not_found'     => __( 'No messages yet.', 'visitor-messages-slideshow' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'menu_icon'       => 'dashicons-format-quote',
				'supports'        => array( 'title', 'editor' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_URL,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => array( $this, 'sanitize_website' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Only http/https survive this, so a stored value is always safe in an href.
	 */
	public function sanitize_website( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '';
		}
		if ( ! preg_match( '#^https?://#i', $url ) ) {
			$url = 'https://' . $url;
		}
		$url = esc_url_raw( $url, array( 'http', 'https' ) );

		return $url ? $url : '';
	}

	/* ------------------------------------------------------------------ */
	/* Submission                                                         */
	/* ------------------------------------------------------------------ */

	public function handle_submission() {
		$redirect = isset( $_POST['vms_redirect'] )
			? wp_validate_redirect( wp_unslash( $_POST['vms_redirect'] ), home_url( '/' ) )
			: home_url( '/' );

		if ( ! isset( $_POST['vms_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vms_nonce'] ) ), 'vms_submit' ) ) {
			$this->bounce( $redirect, 'nonce' );
		}

		// Honeypot: bots fill hidden fields, humans never see them.
		if ( ! empty( $_POST['vms_website_hp'] ) ) {
			$this->bounce( $redirect, 'ok' );
		}

		$ip_key = 'vms_throttle_' . md5( $this->client_ip() );
		if ( get_transient( $ip_key ) ) {
			$this->bounce( $redirect, 'slow' );
		}

		$name    = sanitize_text_field( wp_unslash( $_POST['vms_name'] ?? '' ) );
		$message = trim( sanitize_textarea_field( wp_unslash( $_POST['vms_message'] ?? '' ) ) );
		$website = $this->sanitize_website( wp_unslash( $_POST['vms_url'] ?? '' ) );

		$name = '' === trim( $name ) ? __( 'Anonymous', 'visitor-messages-slideshow' ) : $name;

		if ( '' === $message ) {
			$this->bounce( $redirect, 'empty' );
		}
		if ( mb_strlen( $message ) > self::MAX_LENGTH ) {
			$message = mb_substr( $message, 0, self::MAX_LENGTH );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_title'   => wp_trim_words( $name, 8, '' ),
				'post_content' => $message,
				// Everything waits for a human; nothing a visitor types goes live on its own.
				'post_status'  => 'pending',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$this->bounce( $redirect, 'error' );
		}

		if ( '' !== $website ) {
			update_post_meta( $post_id, self::META_URL, $website );
		}

		set_transient( $ip_key, 1, self::THROTTLE_SECS );

		$this->bounce( $redirect, 'ok' );
	}

	private function bounce( $redirect, $status ) {
		wp_safe_redirect( add_query_arg( 'vms', rawurlencode( $status ), $redirect ) . '#vms-form' );
		exit;
	}

	private function client_ip() {
		return isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '0.0.0.0';
	}

	/* ------------------------------------------------------------------ */
	/* Front end: form                                                    */
	/* ------------------------------------------------------------------ */

	public function render_form() {
		$notices = array(
			'ok'    => array( 'success', __( 'Thanks! Your message is waiting for approval.', 'visitor-messages-slideshow' ) ),
			'empty' => array( 'error', __( 'Please write a message first.', 'visitor-messages-slideshow' ) ),
			'slow'  => array( 'error', __( 'You just posted a message. Please wait a moment.', 'visitor-messages-slideshow' ) ),
			'nonce' => array( 'error', __( 'Your session expired. Please try again.', 'visitor-messages-slideshow' ) ),
			'error' => array( 'error', __( 'Something went wrong. Please try again.', 'visitor-messages-slideshow' ) ),
		);

		$notice = '';
		$key    = isset( $_GET['vms'] ) ? sanitize_key( wp_unslash( $_GET['vms'] ) ) : '';
		if ( isset( $notices[ $key ] ) ) {
			$notice = sprintf(
				'<p class="vms-notice vms-notice--%1$s" role="status">%2$s</p>',
				esc_attr( $notices[ $key ][0] ),
				esc_html( $notices[ $key ][1] )
			);
		}

		$this->enqueue_assets();

		ob_start();
		?>
		<form id="vms-form" class="vms-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php
			echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
			wp_nonce_field( 'vms_submit', 'vms_nonce' );
			?>
			<input type="hidden" name="action" value="vms_submit">
			<input type="hidden" name="vms_redirect" value="<?php echo esc_url( $this->current_url() ); ?>">

			<p class="vms-field">
				<label for="vms-name"><?php esc_html_e( 'Your name', 'visitor-messages-slideshow' ); ?></label>
				<input type="text" id="vms-name" name="vms_name" maxlength="60" autocomplete="name">
			</p>

			<p class="vms-field">
				<label for="vms-message"><?php esc_html_e( 'Your message', 'visitor-messages-slideshow' ); ?> <span aria-hidden="true">*</span></label>
				<textarea id="vms-message" name="vms_message" rows="3" required maxlength="<?php echo esc_attr( self::MAX_LENGTH ); ?>"></textarea>
				<span class="vms-hint"><?php printf( esc_html__( '%d characters max.', 'visitor-messages-slideshow' ), (int) self::MAX_LENGTH ); ?></span>
			</p>

			<p class="vms-field">
				<label for="vms-url"><?php esc_html_e( 'Your website (optional)', 'visitor-messages-slideshow' ); ?></label>
				<input type="url" id="vms-url" name="vms_url" placeholder="https://example.com" autocomplete="url">
			</p>

			<p class="vms-hp" aria-hidden="true">
				<label for="vms-website-hp"><?php esc_html_e( 'Leave this field empty', 'visitor-messages-slideshow' ); ?></label>
				<input type="text" id="vms-website-hp" name="vms_website_hp" tabindex="-1" autocomplete="off">
			</p>

			<p>
				<button type="submit" class="vms-submit"><?php esc_html_e( 'Leave a message', 'visitor-messages-slideshow' ); ?></button>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	private function current_url() {
		global $wp;

		return remove_query_arg( 'vms', home_url( add_query_arg( array(), $wp->request ? $wp->request : '' ) ) );
	}

	/* ------------------------------------------------------------------ */
	/* Front end: slideshow                                               */
	/* ------------------------------------------------------------------ */

	public function render_slideshow( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'    => 20,
				'interval' => 6000, // ms between slides; 0 disables autoplay.
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

		if ( empty( $messages ) ) {
			return '<p class="vms-empty">' . esc_html__( 'No messages yet — be the first!', 'visitor-messages-slideshow' ) . '</p>';
		}

		$this->enqueue_assets();
		$total = count( $messages );

		ob_start();
		?>
		<div class="vms-slideshow"
			data-vms-slideshow
			data-interval="<?php echo esc_attr( max( 0, (int) $atts['interval'] ) ); ?>"
			role="region"
			aria-roledescription="carousel"
			aria-label="<?php esc_attr_e( 'Visitor messages', 'visitor-messages-slideshow' ); ?>">

			<div class="vms-slides">
				<?php foreach ( $messages as $i => $message ) : ?>
					<?php $website = (string) get_post_meta( $message->ID, self::META_URL, true ); ?>
					<figure class="vms-slide<?php echo 0 === $i ? ' is-active' : ''; ?>"
						role="group"
						aria-roledescription="slide"
						aria-label="<?php echo esc_attr( sprintf( __( '%1$d of %2$d', 'visitor-messages-slideshow' ), $i + 1, $total ) ); ?>"
						<?php echo 0 === $i ? '' : 'hidden'; ?>>

						<blockquote class="vms-text"><?php echo esc_html( $message->post_content ); ?></blockquote>

						<figcaption class="vms-meta">
							<span class="vms-author"><?php echo esc_html( get_the_title( $message ) ); ?></span>
							<?php if ( '' !== $website ) : ?>
								<a class="vms-link" href="<?php echo esc_url( $website ); ?>" rel="nofollow ugc noopener external" target="_blank">
									<?php echo esc_html( wp_parse_url( $website, PHP_URL_HOST ) ); ?>
								</a>
							<?php endif; ?>
						</figcaption>
					</figure>
				<?php endforeach; ?>
			</div>

			<?php if ( $total > 1 ) : ?>
				<div class="vms-controls">
					<button type="button" class="vms-nav" data-vms-prev aria-label="<?php esc_attr_e( 'Previous message', 'visitor-messages-slideshow' ); ?>">&#8249;</button>
					<div class="vms-dots">
						<?php for ( $i = 0; $i < $total; $i++ ) : ?>
							<button type="button"
								class="vms-dot<?php echo 0 === $i ? ' is-active' : ''; ?>"
								data-vms-goto="<?php echo esc_attr( $i ); ?>"
								aria-label="<?php echo esc_attr( sprintf( __( 'Go to message %d', 'visitor-messages-slideshow' ), $i + 1 ) ); ?>"></button>
						<?php endfor; ?>
					</div>
					<button type="button" class="vms-nav" data-vms-next aria-label="<?php esc_attr_e( 'Next message', 'visitor-messages-slideshow' ); ?>">&#8250;</button>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ------------------------------------------------------------------ */
	/* Assets (inlined to keep the plugin one file)                       */
	/* ------------------------------------------------------------------ */

	private function enqueue_assets() {
		if ( wp_style_is( 'vms', 'registered' ) ) {
			return;
		}

		wp_register_style( 'vms', false, array(), '1.0.0' );
		wp_enqueue_style( 'vms' );
		wp_add_inline_style( 'vms', $this->css() );

		wp_register_script( 'vms', '', array(), '1.0.0', true );
		wp_enqueue_script( 'vms' );
		wp_add_inline_script( 'vms', $this->js() );
	}

	private function css() {
		return <<<'CSS'
.vms-form{max-width:34rem;margin:0 0 2rem}
.vms-field{display:flex;flex-direction:column;gap:.25rem;margin:0 0 1rem}
.vms-field input,.vms-field textarea{width:100%;padding:.5rem;border:1px solid #ccc;border-radius:4px;font:inherit}
.vms-hint{font-size:.8rem;color:#666}
.vms-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}
.vms-notice{padding:.6rem .8rem;border-radius:4px;margin:0 0 1rem}
.vms-notice--success{background:#e6f4ea;color:#12502a}
.vms-notice--error{background:#fdecea;color:#7a1d16}
.vms-submit{padding:.6rem 1.2rem;border:0;border-radius:4px;background:#1e73be;color:#fff;font:inherit;cursor:pointer}

.vms-slideshow{position:relative;max-width:40rem;margin:2rem auto;text-align:center}
.vms-slides{position:relative;min-height:9rem;display:grid}
.vms-slide{grid-area:1/1;margin:0;padding:1.5rem;opacity:0;transition:opacity .4s ease;pointer-events:none}
.vms-slide.is-active{opacity:1;pointer-events:auto}
.vms-slide[hidden]{display:none}
.vms-text{margin:0 0 .75rem;font-size:1.25rem;line-height:1.5;quotes:"\201C" "\201D"}
.vms-text::before{content:open-quote}
.vms-text::after{content:close-quote}
.vms-meta{font-size:.9rem;color:#555;display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap}
.vms-author{font-weight:600}
.vms-controls{display:flex;align-items:center;justify-content:center;gap:.75rem;margin-top:.5rem}
.vms-nav{border:1px solid #ddd;background:#fff;border-radius:50%;width:2rem;height:2rem;font-size:1.2rem;line-height:1;cursor:pointer}
.vms-dots{display:flex;gap:.35rem}
.vms-dot{width:.6rem;height:.6rem;padding:0;border:0;border-radius:50%;background:#ccc;cursor:pointer}
.vms-dot.is-active{background:#1e73be}
@media (prefers-reduced-motion:reduce){.vms-slide{transition:none}}
CSS;
	}

	private function js() {
		return <<<'JS'
(function () {
	function setup(root) {
		var slides = Array.prototype.slice.call(root.querySelectorAll('.vms-slide'));
		if (slides.length < 2) { return; }

		var dots = Array.prototype.slice.call(root.querySelectorAll('.vms-dot'));
		var interval = parseInt(root.getAttribute('data-interval'), 10) || 0;
		var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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
				dot.setAttribute('aria-current', i === index ? 'true' : 'false');
			});
		}

		function play() {
			if (!interval || reduced) { return; }
			stop();
			timer = window.setInterval(function () { show(index + 1); }, interval);
		}

		function stop() {
			if (timer) { window.clearInterval(timer); timer = null; }
		}

		var prev = root.querySelector('[data-vms-prev]');
		var next = root.querySelector('[data-vms-next]');
		if (prev) { prev.addEventListener('click', function () { show(index - 1); play(); }); }
		if (next) { next.addEventListener('click', function () { show(index + 1); play(); }); }

		dots.forEach(function (dot) {
			dot.addEventListener('click', function () {
				show(parseInt(dot.getAttribute('data-vms-goto'), 10) || 0);
				play();
			});
		});

		root.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft') { show(index - 1); play(); }
			if (e.key === 'ArrowRight') { show(index + 1); play(); }
		});

		root.addEventListener('mouseenter', stop);
		root.addEventListener('mouseleave', play);
		root.addEventListener('focusin', stop);
		root.addEventListener('focusout', play);
		document.addEventListener('visibilitychange', function () {
			if (document.hidden) { stop(); } else { play(); }
		});

		show(0);
		play();
	}

	function boot() {
		document.querySelectorAll('[data-vms-slideshow]').forEach(setup);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
JS;
	}

	/* ------------------------------------------------------------------ */
	/* Admin                                                              */
	/* ------------------------------------------------------------------ */

	public function add_meta_box() {
		add_meta_box(
			'vms_website',
			__( 'Visitor website', 'visitor-messages-slideshow' ),
			array( $this, 'render_meta_box' ),
			self::POST_TYPE,
			'side'
		);
	}

	public function render_meta_box( $post ) {
		$url = (string) get_post_meta( $post->ID, self::META_URL, true );
		if ( '' === $url ) {
			echo '<p>' . esc_html__( 'No link submitted.', 'visitor-messages-slideshow' ) . '</p>';
			return;
		}
		printf(
			'<p><a href="%1$s" target="_blank" rel="noopener nofollow">%1$s</a></p><p class="description">%2$s</p>',
			esc_url( $url ),
			esc_html__( 'Check the link before approving this message.', 'visitor-messages-slideshow' )
		);
	}

	public function admin_columns( $columns ) {
		$columns['vms_message'] = __( 'Message', 'visitor-messages-slideshow' );
		$columns['vms_website'] = __( 'Website', 'visitor-messages-slideshow' );

		return $columns;
	}

	public function admin_column_content( $column, $post_id ) {
		if ( 'vms_message' === $column ) {
			echo esc_html( wp_trim_words( get_post_field( 'post_content', $post_id ), 20 ) );
		}
		if ( 'vms_website' === $column ) {
			$url = (string) get_post_meta( $post_id, self::META_URL, true );
			echo '' === $url ? '—' : '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener nofollow">' . esc_html( $url ) . '</a>';
		}
	}
}

VMS_Plugin::init();
```

## How to use it

Put `[visitor_message_form]` on whatever page should collect messages, and `[visitor_messages]` where the slideshow should appear — they can be on the same page or different ones. The slideshow shortcode takes two optional attributes: `[visitor_messages limit="10" interval="4000"]` shows the 10 newest messages and advances every 4 seconds; `interval="0"` turns off autoplay and leaves only the manual controls.

## Design decisions worth knowing about

**Submissions are held for moderation, not published.** New messages are saved with `post_status => 'pending'`, so they don't appear in the slideshow until you approve them under **Visitor Messages** in the admin menu. This is the single most important protection here — a public form that publishes straight to a page is a spam magnet, and the optional link field makes it an attractive one. The list table shows the message text and the submitted URL side by side so you can review both before publishing.

**Untrusted input is treated as untrusted throughout.** The form is nonce-protected, the message goes through `sanitize_textarea_field` (so it's plain text, never HTML), and the URL is forced through `esc_url_raw` with only `http`/`https` allowed — that's what blocks a `javascript:` URL from ending up in an `href`. Outbound links get `rel="nofollow ugc noopener external"` so you're not passing SEO value to whatever visitors paste in. There's also a hidden honeypot field and a 60-second per-IP throttle, which together stop the low-effort bot traffic without adding a CAPTCHA.

**The slideshow has no dependencies.** It's vanilla JS inlined via `wp_add_inline_script`, so there's no jQuery requirement and nothing to enqueue from disk — that's what keeps this to one file. It pauses on hover and on keyboard focus, supports arrow keys, and respects `prefers-reduced-motion` by not auto-advancing.

Two things you may want to change depending on your setup: the throttle uses `REMOTE_ADDR`, which behind a proxy or CDN like Cloudflare will be the proxy's IP rather than the visitor's — if that's your setup, you'll want to read the forwarded header your host sets. And if you'd rather have messages go live immediately, change `'post_status' => 'pending'` to `'publish'`, though I'd recommend pairing that with a real spam filter like Akismet first.
