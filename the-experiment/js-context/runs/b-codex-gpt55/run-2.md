# Research 003 — rung (b) data-into-JS — Codex CLI — run 2

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.UHiWLnspfD (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.I41HMcOcqK
- prompt (frozen, rung b): Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.

## Output

Reading additional input from stdin...
OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.I41HMcOcqK
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f6085-bd58-72b0-a568-ee625baebb9d
--------
user
Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.
codex
I’ll keep this as a single self-contained plugin file: one shortcode renders the form and slideshow, and submissions are stored as a private custom post type so it uses WordPress’ existing storage/admin UI.
codex
Save this as `visitor-message-slideshow.php` in `wp-content/plugins/`, activate it, then place shortcode `[visitor_message_slideshow]` on any page.

```php
<?php
/**
 * Plugin Name: Visitor Message Slideshow
 * Description: Lets visitors leave a short message and optional website link, then displays messages in a JavaScript slideshow.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Visitor_Message_Slideshow {
    private const POST_TYPE = 'vms_message';
    private const NONCE_ACTION = 'vms_submit_message';
    private const NONCE_NAME = 'vms_nonce';
    private const MAX_MESSAGE_LENGTH = 220;

    public static function init(): void {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_shortcode('visitor_message_slideshow', [__CLASS__, 'render_shortcode']);

        add_action('admin_post_vms_submit_message', [__CLASS__, 'handle_submission']);
        add_action('admin_post_nopriv_vms_submit_message', [__CLASS__, 'handle_submission']);
    }

    public static function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Visitor Messages', 'visitor-message-slideshow'),
                'singular_name' => __('Visitor Message', 'visitor-message-slideshow'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
            'menu_icon' => 'dashicons-format-chat',
        ]);
    }

    public static function handle_submission(): void {
        $redirect_url = wp_get_referer() ?: home_url('/');

        if (
            empty($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)
        ) {
            self::redirect($redirect_url, 'error');
        }

        if (!empty($_POST['vms_company'])) {
            self::redirect($redirect_url, 'sent');
        }

        if (self::is_rate_limited()) {
            self::redirect($redirect_url, 'rate_limited');
        }

        $message = isset($_POST['vms_message'])
            ? sanitize_text_field(wp_unslash($_POST['vms_message']))
            : '';

        $message = trim(preg_replace('/\s+/', ' ', $message));

        if ($message === '') {
            self::redirect($redirect_url, 'empty');
        }

        if (function_exists('mb_substr')) {
            $message = mb_substr($message, 0, self::MAX_MESSAGE_LENGTH);
        } else {
            $message = substr($message, 0, self::MAX_MESSAGE_LENGTH);
        }

        $website = '';

        if (!empty($_POST['vms_website'])) {
            $raw_website = esc_url_raw(wp_unslash($_POST['vms_website']));
            $scheme = wp_parse_url($raw_website, PHP_URL_SCHEME);

            if (in_array($scheme, ['http', 'https'], true)) {
                $website = $raw_website;
            }
        }

        $title = wp_trim_words($message, 8, '...');

        wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $title ?: __('Visitor message', 'visitor-message-slideshow'),
            'post_content' => $message,
            'meta_input' => [
                '_vms_website' => $website,
            ],
        ]);

        self::mark_rate_limited();
        self::redirect($redirect_url, 'sent');
    }

    public static function render_shortcode(): string {
        $messages = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        ob_start();

        self::render_assets_once();

        $status = isset($_GET['vms_status'])
            ? sanitize_key(wp_unslash($_GET['vms_status']))
            : '';
        ?>

        <div class="vms-wrap">
            <?php if ($status === 'sent') : ?>
                <p class="vms-notice vms-success">Thanks, your message was added.</p>
            <?php elseif ($status === 'empty') : ?>
                <p class="vms-notice vms-error">Please enter a message.</p>
            <?php elseif ($status === 'rate_limited') : ?>
                <p class="vms-notice vms-error">Please wait a moment before sending another message.</p>
            <?php elseif ($status === 'error') : ?>
                <p class="vms-notice vms-error">Something went wrong. Please try again.</p>
            <?php endif; ?>

            <form class="vms-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="vms_submit_message">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

                <p>
                    <label for="vms-message">Message</label>
                    <textarea id="vms-message" name="vms_message" maxlength="<?php echo esc_attr(self::MAX_MESSAGE_LENGTH); ?>" required></textarea>
                </p>

                <p>
                    <label for="vms-website">Website link, optional</label>
                    <input id="vms-website" name="vms_website" type="url" placeholder="https://example.com">
                </p>

                <p class="vms-hp" aria-hidden="true">
                    <label for="vms-company">Company</label>
                    <input id="vms-company" name="vms_company" type="text" tabindex="-1" autocomplete="off">
                </p>

                <button type="submit">Leave message</button>
            </form>

            <div class="vms-slideshow" data-vms-slideshow>
                <?php if ($messages) : ?>
                    <div class="vms-slides">
                        <?php foreach ($messages as $index => $message_post) : ?>
                            <?php
                            $website = get_post_meta($message_post->ID, '_vms_website', true);
                            $host = $website ? wp_parse_url($website, PHP_URL_HOST) : '';
                            ?>
                            <article class="vms-slide <?php echo $index === 0 ? 'is-active' : ''; ?>">
                                <blockquote><?php echo esc_html($message_post->post_content); ?></blockquote>

                                <?php if ($website && $host) : ?>
                                    <a href="<?php echo esc_url($website); ?>" target="_blank" rel="nofollow ugc noopener">
                                        <?php echo esc_html($host); ?>
                                    </a>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($messages) > 1) : ?>
                        <div class="vms-controls">
                            <button type="button" data-vms-prev aria-label="Previous message">Prev</button>
                            <button type="button" data-vms-next aria-label="Next message">Next</button>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <p class="vms-empty">No messages yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    private static function render_assets_once(): void {
        static $rendered = false;

        if ($rendered) {
            return;
        }

        $rendered = true;
        ?>

        <style>
            .vms-wrap {
                max-width: 720px;
                margin: 2rem auto;
            }

            .vms-form {
                display: grid;
                gap: 1rem;
                margin-bottom: 2rem;
            }

            .vms-form p {
                margin: 0;
            }

            .vms-form label {
                display: block;
                font-weight: 600;
                margin-bottom: 0.35rem;
            }

            .vms-form textarea,
            .vms-form input[type="url"] {
                width: 100%;
                box-sizing: border-box;
                padding: 0.75rem;
            }

            .vms-form textarea {
                min-height: 100px;
                resize: vertical;
            }

            .vms-form button,
            .vms-controls button {
                cursor: pointer;
                padding: 0.65rem 1rem;
            }

            .vms-hp {
                position: absolute;
                left: -9999px;
            }

            .vms-notice {
                padding: 0.75rem 1rem;
                border-radius: 4px;
                margin-bottom: 1rem;
            }

            .vms-success {
                background: #ecfdf3;
                color: #166534;
            }

            .vms-error {
                background: #fef2f2;
                color: #991b1b;
            }

            .vms-slideshow {
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 1.5rem;
            }

            .vms-slides {
                position: relative;
                min-height: 120px;
            }

            .vms-slide {
                display: none;
            }

            .vms-slide.is-active {
                display: block;
            }

            .vms-slide blockquote {
                margin: 0 0 1rem;
                font-size: 1.2rem;
                line-height: 1.5;
            }

            .vms-controls {
                display: flex;
                gap: 0.5rem;
                margin-top: 1rem;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-vms-slideshow]').forEach(function (slideshow) {
                    var slides = Array.prototype.slice.call(slideshow.querySelectorAll('.vms-slide'));
                    var prev = slideshow.querySelector('[data-vms-prev]');
                    var next = slideshow.querySelector('[data-vms-next]');
                    var index = 0;
                    var timer = null;

                    if (slides.length < 2) {
                        return;
                    }

                    function show(newIndex) {
                        slides[index].classList.remove('is-active');
                        index = (newIndex + slides.length) % slides.length;
                        slides[index].classList.add('is-active');
                    }

                    function start() {
                        stop();
                        timer = window.setInterval(function () {
                            show(index + 1);
                        }, 5000);
                    }

                    function stop() {
                        if (timer) {
                            window.clearInterval(timer);
                        }
                    }

                    prev.addEventListener('click', function () {
                        show(index - 1);
                        start();
                    });

                    next.addEventListener('click', function () {
                        show(index + 1);
                        start();
                    });

                    slideshow.addEventListener('mouseenter', stop);
                    slideshow.addEventListener('mouseleave', start);

                    start();
                });
            });
        </script>

        <?php
    }

    private static function is_rate_limited(): bool {
        return (bool) get_transient(self::rate_limit_key());
    }

    private static function mark_rate_limited(): void {
        set_transient(self::rate_limit_key(), '1', MINUTE_IN_SECONDS);
    }

    private static function rate_limit_key(): string {
        $ip = isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
            : 'unknown';

        return 'vms_rate_' . md5($ip);
    }

    private static function redirect(string $url, string $status): void {
        $url = remove_query_arg('vms_status', $url);
        $url = add_query_arg('vms_status', $status, $url);

        wp_safe_redirect($url);
        exit;
    }
}

Visitor_Message_Slideshow::init();
```
tokens used
6 514
Save this as `visitor-message-slideshow.php` in `wp-content/plugins/`, activate it, then place shortcode `[visitor_message_slideshow]` on any page.

```php
<?php
/**
 * Plugin Name: Visitor Message Slideshow
 * Description: Lets visitors leave a short message and optional website link, then displays messages in a JavaScript slideshow.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Visitor_Message_Slideshow {
    private const POST_TYPE = 'vms_message';
    private const NONCE_ACTION = 'vms_submit_message';
    private const NONCE_NAME = 'vms_nonce';
    private const MAX_MESSAGE_LENGTH = 220;

    public static function init(): void {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_shortcode('visitor_message_slideshow', [__CLASS__, 'render_shortcode']);

        add_action('admin_post_vms_submit_message', [__CLASS__, 'handle_submission']);
        add_action('admin_post_nopriv_vms_submit_message', [__CLASS__, 'handle_submission']);
    }

    public static function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Visitor Messages', 'visitor-message-slideshow'),
                'singular_name' => __('Visitor Message', 'visitor-message-slideshow'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
            'menu_icon' => 'dashicons-format-chat',
        ]);
    }

    public static function handle_submission(): void {
        $redirect_url = wp_get_referer() ?: home_url('/');

        if (
            empty($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)
        ) {
            self::redirect($redirect_url, 'error');
        }

        if (!empty($_POST['vms_company'])) {
            self::redirect($redirect_url, 'sent');
        }

        if (self::is_rate_limited()) {
            self::redirect($redirect_url, 'rate_limited');
        }

        $message = isset($_POST['vms_message'])
            ? sanitize_text_field(wp_unslash($_POST['vms_message']))
            : '';

        $message = trim(preg_replace('/\s+/', ' ', $message));

        if ($message === '') {
            self::redirect($redirect_url, 'empty');
        }

        if (function_exists('mb_substr')) {
            $message = mb_substr($message, 0, self::MAX_MESSAGE_LENGTH);
        } else {
            $message = substr($message, 0, self::MAX_MESSAGE_LENGTH);
        }

        $website = '';

        if (!empty($_POST['vms_website'])) {
            $raw_website = esc_url_raw(wp_unslash($_POST['vms_website']));
            $scheme = wp_parse_url($raw_website, PHP_URL_SCHEME);

            if (in_array($scheme, ['http', 'https'], true)) {
                $website = $raw_website;
            }
        }

        $title = wp_trim_words($message, 8, '...');

        wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $title ?: __('Visitor message', 'visitor-message-slideshow'),
            'post_content' => $message,
            'meta_input' => [
                '_vms_website' => $website,
            ],
        ]);

        self::mark_rate_limited();
        self::redirect($redirect_url, 'sent');
    }

    public static function render_shortcode(): string {
        $messages = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        ob_start();

        self::render_assets_once();

        $status = isset($_GET['vms_status'])
            ? sanitize_key(wp_unslash($_GET['vms_status']))
            : '';
        ?>

        <div class="vms-wrap">
            <?php if ($status === 'sent') : ?>
                <p class="vms-notice vms-success">Thanks, your message was added.</p>
            <?php elseif ($status === 'empty') : ?>
                <p class="vms-notice vms-error">Please enter a message.</p>
            <?php elseif ($status === 'rate_limited') : ?>
                <p class="vms-notice vms-error">Please wait a moment before sending another message.</p>
            <?php elseif ($status === 'error') : ?>
                <p class="vms-notice vms-error">Something went wrong. Please try again.</p>
            <?php endif; ?>

            <form class="vms-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="vms_submit_message">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

                <p>
                    <label for="vms-message">Message</label>
                    <textarea id="vms-message" name="vms_message" maxlength="<?php echo esc_attr(self::MAX_MESSAGE_LENGTH); ?>" required></textarea>
                </p>

                <p>
                    <label for="vms-website">Website link, optional</label>
                    <input id="vms-website" name="vms_website" type="url" placeholder="https://example.com">
                </p>

                <p class="vms-hp" aria-hidden="true">
                    <label for="vms-company">Company</label>
                    <input id="vms-company" name="vms_company" type="text" tabindex="-1" autocomplete="off">
                </p>

                <button type="submit">Leave message</button>
            </form>

            <div class="vms-slideshow" data-vms-slideshow>
                <?php if ($messages) : ?>
                    <div class="vms-slides">
                        <?php foreach ($messages as $index => $message_post) : ?>
                            <?php
                            $website = get_post_meta($message_post->ID, '_vms_website', true);
                            $host = $website ? wp_parse_url($website, PHP_URL_HOST) : '';
                            ?>
                            <article class="vms-slide <?php echo $index === 0 ? 'is-active' : ''; ?>">
                                <blockquote><?php echo esc_html($message_post->post_content); ?></blockquote>

                                <?php if ($website && $host) : ?>
                                    <a href="<?php echo esc_url($website); ?>" target="_blank" rel="nofollow ugc noopener">
                                        <?php echo esc_html($host); ?>
                                    </a>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($messages) > 1) : ?>
                        <div class="vms-controls">
                            <button type="button" data-vms-prev aria-label="Previous message">Prev</button>
                            <button type="button" data-vms-next aria-label="Next message">Next</button>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <p class="vms-empty">No messages yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    private static function render_assets_once(): void {
        static $rendered = false;

        if ($rendered) {
            return;
        }

        $rendered = true;
        ?>

        <style>
            .vms-wrap {
                max-width: 720px;
                margin: 2rem auto;
            }

            .vms-form {
                display: grid;
                gap: 1rem;
                margin-bottom: 2rem;
            }

            .vms-form p {
                margin: 0;
            }

            .vms-form label {
                display: block;
                font-weight: 600;
                margin-bottom: 0.35rem;
            }

            .vms-form textarea,
            .vms-form input[type="url"] {
                width: 100%;
                box-sizing: border-box;
                padding: 0.75rem;
            }

            .vms-form textarea {
                min-height: 100px;
                resize: vertical;
            }

            .vms-form button,
            .vms-controls button {
                cursor: pointer;
                padding: 0.65rem 1rem;
            }

            .vms-hp {
                position: absolute;
                left: -9999px;
            }

            .vms-notice {
                padding: 0.75rem 1rem;
                border-radius: 4px;
                margin-bottom: 1rem;
            }

            .vms-success {
                background: #ecfdf3;
                color: #166534;
            }

            .vms-error {
                background: #fef2f2;
                color: #991b1b;
            }

            .vms-slideshow {
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 1.5rem;
            }

            .vms-slides {
                position: relative;
                min-height: 120px;
            }

            .vms-slide {
                display: none;
            }

            .vms-slide.is-active {
                display: block;
            }

            .vms-slide blockquote {
                margin: 0 0 1rem;
                font-size: 1.2rem;
                line-height: 1.5;
            }

            .vms-controls {
                display: flex;
                gap: 0.5rem;
                margin-top: 1rem;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-vms-slideshow]').forEach(function (slideshow) {
                    var slides = Array.prototype.slice.call(slideshow.querySelectorAll('.vms-slide'));
                    var prev = slideshow.querySelector('[data-vms-prev]');
                    var next = slideshow.querySelector('[data-vms-next]');
                    var index = 0;
                    var timer = null;

                    if (slides.length < 2) {
                        return;
                    }

                    function show(newIndex) {
                        slides[index].classList.remove('is-active');
                        index = (newIndex + slides.length) % slides.length;
                        slides[index].classList.add('is-active');
                    }

                    function start() {
                        stop();
                        timer = window.setInterval(function () {
                            show(index + 1);
                        }, 5000);
                    }

                    function stop() {
                        if (timer) {
                            window.clearInterval(timer);
                        }
                    }

                    prev.addEventListener('click', function () {
                        show(index - 1);
                        start();
                    });

                    next.addEventListener('click', function () {
                        show(index + 1);
                        start();
                    });

                    slideshow.addEventListener('mouseenter', stop);
                    slideshow.addEventListener('mouseleave', start);

                    start();
                });
            });
        </script>

        <?php
    }

    private static function is_rate_limited(): bool {
        return (bool) get_transient(self::rate_limit_key());
    }

    private static function mark_rate_limited(): void {
        set_transient(self::rate_limit_key(), '1', MINUTE_IN_SECONDS);
    }

    private static function rate_limit_key(): string {
        $ip = isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
            : 'unknown';

        return 'vms_rate_' . md5($ip);
    }

    private static function redirect(string $url, string $status): void {
        $url = remove_query_arg('vms_status', $url);
        $url = add_query_arg('vms_status', $status, $url);

        wp_safe_redirect($url);
        exit;
    }
}

Visitor_Message_Slideshow::init();
```
