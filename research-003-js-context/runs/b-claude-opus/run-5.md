# Research 003 — rung (b) data-into-JS — Claude Code — run 5

- tool: Claude Code 2.1.207 (Claude Code)
- model: claude-opus-4-8
- flags: --setting-sources '' --disable-slash-commands
- cwd: fresh empty /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.f658T2akZz
- prompt (frozen, rung b): Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.

## Output

The file write wasn't permitted, so here's the full plugin inline. Save it as `wp-content/plugins/guestbook-slideshow/guestbook-slideshow.php` (or drop the single file directly in `wp-content/plugins/`) and activate it.

**How it works:** messages are stored as a private custom post type, so you get the WordPress admin list, search, and trash for free. Submissions arrive as **pending** and only appear in the slideshow once you publish them. The form posts to `admin-post.php` with a nonce, a honeypot field, and a one-message-per-minute-per-IP limit. The slideshow is dependency-free vanilla JS — it pauses on hover/focus, supports arrow keys, and respects `prefers-reduced-motion`.

```php
<?php
/**
 * Plugin Name:       Guestbook Slideshow
 * Description:       Visitors leave a short message and an optional link to their site; approved messages rotate in a JavaScript slideshow.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            You
 * License:           GPL-2.0-or-later
 * Text Domain:       guestbook-slideshow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GBSS_VERSION', '1.0.0' );
define( 'GBSS_POST_TYPE', 'gbss_message' );
define( 'GBSS_MAX_LENGTH', 280 );

/* -------------------------------------------------------------------------
 * Storage: messages are a custom post type so the admin UI comes for free.
 * Submissions land as "pending" and an editor publishes them to make them live.
 * ---------------------------------------------------------------------- */

add_action( 'init', 'gbss_register_post_type' );
function gbss_register_post_type() {
	register_post_type(
		GBSS_POST_TYPE,
		array(
			'labels'          => array(
				'name'          => __( 'Guestbook', 'guestbook-slideshow' ),
				'singular_name' => __( 'Message', 'guestbook-slideshow' ),
				'edit_item'     => __( 'Edit Message', 'guestbook-slideshow' ),
				'search_items'  => __( 'Search Messages', 'guestbook-slideshow' ),
				'not_found'     => __( 'No messages yet.', 'guestbook-slideshow' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'menu_icon'       => 'dashicons-format-quote',
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			// Visitors never create posts directly; the submit handler does it for them.
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
		)
	);

	register_post_meta(
		GBSS_POST_TYPE,
		'_gbss_url',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'gbss_sanitize_url',
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}

/** Only ever store http/https links, never javascript: or data: URLs. */
function gbss_sanitize_url( $url ) {
	$url = esc_url_raw( trim( (string) $url ), array( 'http', 'https' ) );
	return $url ? $url : '';
}

/* -------------------------------------------------------------------------
 * Admin: show the website link on the edit screen.
 * ---------------------------------------------------------------------- */

add_action( 'add_meta_boxes', 'gbss_add_meta_box' );
function gbss_add_meta_box() {
	add_meta_box(
		'gbss_url_box',
		__( 'Website link', 'guestbook-slideshow' ),
		'gbss_render_meta_box',
		GBSS_POST_TYPE,
		'side'
	);
}

function gbss_render_meta_box( $post ) {
	wp_nonce_field( 'gbss_save_url', 'gbss_url_nonce' );
	$url = get_post_meta( $post->ID, '_gbss_url', true );
	printf(
		'<input type="url" name="gbss_url" value="%s" class="widefat" placeholder="https://example.com" />',
		esc_attr( $url )
	);
}

add_action( 'save_post_' . GBSS_POST_TYPE, 'gbss_save_meta_box', 10, 2 );
function gbss_save_meta_box( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['gbss_url_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['gbss_url_nonce'] ), 'gbss_save_url' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$url = gbss_sanitize_url( wp_unslash( $_POST['gbss_url'] ?? '' ) );
	if ( $url ) {
		update_post_meta( $post_id, '_gbss_url', $url );
	} else {
		delete_post_meta( $post_id, '_gbss_url' );
	}
}

/* -------------------------------------------------------------------------
 * Submission handling.
 * ---------------------------------------------------------------------- */

add_action( 'admin_post_gbss_submit', 'gbss_handle_submit' );
add_action( 'admin_post_nopriv_gbss_submit', 'gbss_handle_submit' );

function gbss_handle_submit() {
	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

	if ( ! isset( $_POST['gbss_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['gbss_nonce'] ), 'gbss_submit' ) ) {
		gbss_redirect_back( $redirect, 'error', __( 'Your session expired. Please try again.', 'guestbook-slideshow' ) );
	}

	// Honeypot: real people leave this field empty. Pretend success so bots do not retry.
	if ( ! empty( $_POST['gbss_website_confirm'] ) ) {
		gbss_redirect_back( $redirect, 'ok', gbss_thanks_message() );
	}

	if ( gbss_is_rate_limited() ) {
		gbss_redirect_back( $redirect, 'error', __( 'You just posted a message. Please wait a minute before posting another.', 'guestbook-slideshow' ) );
	}

	$name    = sanitize_text_field( wp_unslash( $_POST['gbss_name'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['gbss_message'] ?? '' ) );
	$raw_url = trim( (string) wp_unslash( $_POST['gbss_url'] ?? '' ) );
	$url     = gbss_sanitize_url( $raw_url );

	if ( '' === $message ) {
		gbss_redirect_back( $redirect, 'error', __( 'Please write a message.', 'guestbook-slideshow' ) );
	}
	if ( mb_strlen( $message ) > GBSS_MAX_LENGTH ) {
		gbss_redirect_back(
			$redirect,
			'error',
			sprintf(
				/* translators: %d: maximum number of characters. */
				__( 'Messages are limited to %d characters.', 'guestbook-slideshow' ),
				GBSS_MAX_LENGTH
			)
		);
	}
	if ( '' !== $raw_url && '' === $url ) {
		gbss_redirect_back( $redirect, 'error', __( 'That website address does not look valid. Use a full http:// or https:// link.', 'guestbook-slideshow' ) );
	}

	if ( '' === $name ) {
		$name = __( 'Anonymous', 'guestbook-slideshow' );
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => GBSS_POST_TYPE,
			'post_status'  => apply_filters( 'gbss_auto_approve', false ) ? 'publish' : 'pending',
			'post_title'   => wp_trim_words( $name, 8, '' ),
			'post_content' => $message,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		gbss_redirect_back( $redirect, 'error', __( 'Something went wrong saving your message.', 'guestbook-slideshow' ) );
	}

	if ( $url ) {
		update_post_meta( $post_id, '_gbss_url', $url );
	}

	gbss_set_rate_limit();

	gbss_redirect_back( $redirect, 'ok', gbss_thanks_message() );
}

function gbss_thanks_message() {
	return apply_filters( 'gbss_auto_approve', false )
		? __( 'Thanks! Your message is now in the slideshow.', 'guestbook-slideshow' )
		: __( 'Thanks! Your message will appear once it has been approved.', 'guestbook-slideshow' );
}

function gbss_redirect_back( $url, $status, $notice ) {
	wp_safe_redirect(
		add_query_arg(
			array(
				'gbss'        => $status,
				'gbss_notice' => rawurlencode( $notice ),
			),
			$url
		) . '#gbss-form'
	);
	exit;
}

/** One submission per IP per minute. */
function gbss_rate_limit_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	return 'gbss_rl_' . md5( $ip );
}

function gbss_is_rate_limited() {
	return (bool) get_transient( gbss_rate_limit_key() );
}

function gbss_set_rate_limit() {
	set_transient( gbss_rate_limit_key(), 1, apply_filters( 'gbss_rate_limit_seconds', MINUTE_IN_SECONDS ) );
}

/* -------------------------------------------------------------------------
 * Shortcode: [guestbook_form]
 * ---------------------------------------------------------------------- */

add_shortcode( 'guestbook_form', 'gbss_form_shortcode' );
function gbss_form_shortcode( $atts ) {
	$atts = shortcode_atts(
		array( 'title' => __( 'Sign the guestbook', 'guestbook-slideshow' ) ),
		$atts,
		'guestbook_form'
	);

	gbss_enqueue_assets();

	$notice = '';
	if ( isset( $_GET['gbss'], $_GET['gbss_notice'] ) ) {
		$state  = ( 'ok' === $_GET['gbss'] ) ? 'ok' : 'error';
		$text   = sanitize_text_field( wp_unslash( $_GET['gbss_notice'] ) );
		$notice = sprintf(
			'<p class="gbss-notice gbss-notice--%s" role="status">%s</p>',
			esc_attr( $state ),
			esc_html( $text )
		);
	}

	ob_start();
	?>
	<div class="gbss-form-wrap" id="gbss-form">
		<h2 class="gbss-form-title"><?php echo esc_html( $atts['title'] ); ?></h2>
		<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput -- assembled from escaped parts above. ?>
		<form class="gbss-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gbss_submit" />
			<?php wp_nonce_field( 'gbss_submit', 'gbss_nonce' ); ?>

			<p>
				<label for="gbss-name"><?php esc_html_e( 'Name (optional)', 'guestbook-slideshow' ); ?></label>
				<input type="text" id="gbss-name" name="gbss_name" maxlength="60" autocomplete="name" />
			</p>

			<p>
				<label for="gbss-message">
					<?php
					printf(
						/* translators: %d: maximum number of characters. */
						esc_html__( 'Message (required, %d characters max)', 'guestbook-slideshow' ),
						(int) GBSS_MAX_LENGTH
					);
					?>
				</label>
				<textarea id="gbss-message" name="gbss_message" rows="3" maxlength="<?php echo esc_attr( GBSS_MAX_LENGTH ); ?>" required></textarea>
				<span class="gbss-counter" aria-live="polite"></span>
			</p>

			<p>
				<label for="gbss-url"><?php esc_html_e( 'Your website (optional)', 'guestbook-slideshow' ); ?></label>
				<input type="url" id="gbss-url" name="gbss_url" placeholder="https://example.com" inputmode="url" />
			</p>

			<p class="gbss-hp" aria-hidden="true">
				<label for="gbss-website-confirm"><?php esc_html_e( 'Leave this field empty', 'guestbook-slideshow' ); ?></label>
				<input type="text" id="gbss-website-confirm" name="gbss_website_confirm" tabindex="-1" autocomplete="off" />
			</p>

			<p><button type="submit" class="gbss-submit"><?php esc_html_e( 'Post message', 'guestbook-slideshow' ); ?></button></p>
		</form>
	</div>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * Shortcode: [guestbook_slideshow]
 * ---------------------------------------------------------------------- */

add_shortcode( 'guestbook_slideshow', 'gbss_slideshow_shortcode' );
function gbss_slideshow_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'count'    => 20,       // How many recent messages to rotate through.
			'interval' => 6000,     // Milliseconds per slide.
			'autoplay' => 'yes',
			'order'    => 'newest', // newest | oldest | random
		),
		$atts,
		'guestbook_slideshow'
	);

	$query_args = array(
		'post_type'      => GBSS_POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => max( 1, min( 100, (int) $atts['count'] ) ),
		'no_found_rows'  => true,
	);

	if ( 'random' === $atts['order'] ) {
		$query_args['orderby'] = 'rand';
	} else {
		$query_args['orderby'] = 'date';
		$query_args['order']   = ( 'oldest' === $atts['order'] ) ? 'ASC' : 'DESC';
	}

	$messages = get_posts( $query_args );

	if ( ! $messages ) {
		return '<p class="gbss-empty">' . esc_html__( 'No messages yet. Be the first to sign the guestbook!', 'guestbook-slideshow' ) . '</p>';
	}

	gbss_enqueue_assets();

	$interval = max( 1500, (int) $atts['interval'] );
	$autoplay = in_array( strtolower( $atts['autoplay'] ), array( 'yes', 'true', '1' ), true ) ? '1' : '0';
	$total    = count( $messages );

	ob_start();
	?>
	<div class="gbss-slideshow"
		data-gbss-interval="<?php echo esc_attr( $interval ); ?>"
		data-gbss-autoplay="<?php echo esc_attr( $autoplay ); ?>"
		aria-roledescription="carousel"
		aria-label="<?php esc_attr_e( 'Guestbook messages', 'guestbook-slideshow' ); ?>">

		<div class="gbss-track" aria-live="polite">
			<?php foreach ( $messages as $i => $message ) : ?>
				<?php $url = get_post_meta( $message->ID, '_gbss_url', true ); ?>
				<figure class="gbss-slide<?php echo 0 === $i ? ' is-active' : ''; ?>"
					role="group"
					aria-roledescription="slide"
					aria-label="<?php echo esc_attr( sprintf( '%d / %d', $i + 1, $total ) ); ?>"
					<?php echo 0 === $i ? '' : 'hidden'; ?>>
					<blockquote class="gbss-text"><?php echo esc_html( $message->post_content ); ?></blockquote>
					<figcaption class="gbss-author">
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
			<div class="gbss-controls">
				<button type="button" class="gbss-prev" aria-label="<?php esc_attr_e( 'Previous message', 'guestbook-slideshow' ); ?>">&#8249;</button>
				<div class="gbss-dots" role="tablist"></div>
				<button type="button" class="gbss-next" aria-label="<?php esc_attr_e( 'Next message', 'guestbook-slideshow' ); ?>">&#8250;</button>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * Assets: registered up front, enqueued only on pages that use a shortcode.
 * ---------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', 'gbss_register_assets' );
function gbss_register_assets() {
	wp_register_style( 'gbss', false, array(), GBSS_VERSION );
	wp_add_inline_style( 'gbss', gbss_css() );

	wp_register_script( 'gbss', '', array(), GBSS_VERSION, true );
	wp_add_inline_script( 'gbss', gbss_js() );
}

function gbss_enqueue_assets() {
	wp_enqueue_style( 'gbss' );
	wp_enqueue_script( 'gbss' );
}

function gbss_css() {
	return <<<CSS
.gbss-slideshow{position:relative;max-width:42rem;margin:2rem auto;padding:1.5rem;border:1px solid rgba(0,0,0,.12);border-radius:.75rem;background:#fff}
.gbss-track{display:grid}
.gbss-slide{grid-area:1/1;opacity:0;visibility:hidden;transition:opacity .4s ease;margin:0;text-align:center}
.gbss-slide.is-active{opacity:1;visibility:visible}
.gbss-slide[hidden]{display:block}
.gbss-text{margin:0 0 .75rem;padding:0;border:0;font-size:1.25rem;line-height:1.5;quotes:none}
.gbss-text::before{content:"\\201C"}
.gbss-text::after{content:"\\201D"}
.gbss-author{font-size:.9rem;opacity:.75}
.gbss-controls{display:flex;align-items:center;justify-content:center;gap:.75rem;margin-top:1rem}
.gbss-prev,.gbss-next{width:2rem;height:2rem;border:1px solid rgba(0,0,0,.15);border-radius:50%;background:transparent;font-size:1.1rem;line-height:1;cursor:pointer}
.gbss-prev:hover,.gbss-next:hover{background:rgba(0,0,0,.05)}
.gbss-dots{display:flex;gap:.4rem}
.gbss-dot{width:.55rem;height:.55rem;padding:0;border:0;border-radius:50%;background:rgba(0,0,0,.2);cursor:pointer}
.gbss-dot[aria-selected="true"]{background:rgba(0,0,0,.65)}
.gbss-form-wrap{max-width:42rem;margin:2rem auto}
.gbss-form label{display:block;margin-bottom:.25rem;font-weight:600}
.gbss-form input[type=text],.gbss-form input[type=url],.gbss-form textarea{width:100%;padding:.5rem;border:1px solid rgba(0,0,0,.2);border-radius:.375rem}
.gbss-counter{display:block;margin-top:.25rem;font-size:.8rem;opacity:.6}
.gbss-notice{padding:.6rem .8rem;border-radius:.375rem}
.gbss-notice--ok{background:#e6f6ec;border:1px solid #9ad3b0}
.gbss-notice--error{background:#fdecea;border:1px solid #f0a9a3}
.gbss-hp{position:absolute!important;left:-9999px!important;height:1px;overflow:hidden}
@media (prefers-reduced-motion:reduce){.gbss-slide{transition:none}}
CSS;
}

function gbss_js() {
	$max = (int) GBSS_MAX_LENGTH;
	return <<<JS
(function () {
	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function initSlideshow(root) {
		var slides = Array.prototype.slice.call(root.querySelectorAll('.gbss-slide'));
		if (slides.length < 2) { return; }

		var interval = parseInt(root.getAttribute('data-gbss-interval'), 10) || 6000;
		var autoplay = root.getAttribute('data-gbss-autoplay') === '1' && !reduceMotion;
		var dotsWrap = root.querySelector('.gbss-dots');
		var index = 0;
		var timer = null;

		var dots = slides.map(function (slide, i) {
			if (!dotsWrap) { return null; }
			var dot = document.createElement('button');
			dot.type = 'button';
			dot.className = 'gbss-dot';
			dot.setAttribute('role', 'tab');
			dot.setAttribute('aria-label', 'Message ' + (i + 1));
			dot.addEventListener('click', function () { show(i); restart(); });
			dotsWrap.appendChild(dot);
			return dot;
		});

		function show(next) {
			index = (next + slides.length) % slides.length;
			slides.forEach(function (slide, i) {
				var active = i === index;
				slide.classList.toggle('is-active', active);
				if (active) { slide.removeAttribute('hidden'); } else { slide.setAttribute('hidden', ''); }
				if (dots[i]) { dots[i].setAttribute('aria-selected', active ? 'true' : 'false'); }
			});
		}

		function start() { if (autoplay && !timer) { timer = setInterval(function () { show(index + 1); }, interval); } }
		function stop() { if (timer) { clearInterval(timer); timer = null; } }
		function restart() { stop(); start(); }

		var prev = root.querySelector('.gbss-prev');
		var next = root.querySelector('.gbss-next');
		if (prev) { prev.addEventListener('click', function () { show(index - 1); restart(); }); }
		if (next) { next.addEventListener('click', function () { show(index + 1); restart(); }); }

		root.addEventListener('mouseenter', stop);
		root.addEventListener('mouseleave', start);
		root.addEventListener('focusin', stop);
		root.addEventListener('focusout', start);
		root.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft') { show(index - 1); restart(); }
			if (e.key === 'ArrowRight') { show(index + 1); restart(); }
		});
		document.addEventListener('visibilitychange', function () {
			if (document.hidden) { stop(); } else { start(); }
		});

		show(0);
		start();
	}

	function initCounter(field) {
		var counter = field.parentNode.querySelector('.gbss-counter');
		if (!counter) { return; }
		function update() { counter.textContent = ($max - field.value.length) + ' characters left'; }
		field.addEventListener('input', update);
		update();
	}

	function init() {
		document.querySelectorAll('.gbss-slideshow').forEach(initSlideshow);
		document.querySelectorAll('#gbss-message').forEach(initCounter);
	}

	if (document.readyState !== 'loading') { init(); } else {
		document.addEventListener('DOMContentLoaded', init);
	}
})();
JS;
}
```

## Using it

Put both shortcodes on a page — the slideshow first, the form below it:

```
[guestbook_slideshow]
[guestbook_form]
```

The slideshow accepts `count` (default 20), `interval` in milliseconds (default 6000), `autoplay` (`yes`/`no`), and `order` (`newest`, `oldest`, `random`), e.g. `[guestbook_slideshow count="10" interval="4000" order="random"]`.

New messages show up under **Guestbook** in the admin with status *Pending*; publish one and it joins the rotation. If you'd rather skip moderation, add `add_filter( 'gbss_auto_approve', '__return_true' );` to your theme's `functions.php` — though with a public form and no captcha, I'd leave moderation on.

## A couple of notes on the choices

Message bodies are stored raw and rendered with `esc_html()`, so no visitor HTML is ever executed. Website links go through `esc_url_raw()` restricted to `http`/`https` on the way in and `esc_url()` on the way out, and render with `rel="nofollow ugc noopener"` so the guestbook doesn't become an SEO target.

The spam defenses here are deliberately lightweight — a honeypot plus a per-IP throttle. That's enough for a personal site; if this goes on anything with traffic, pair it with Akismet or a captcha, since a determined bot will read the form markup and fill it in correctly.
