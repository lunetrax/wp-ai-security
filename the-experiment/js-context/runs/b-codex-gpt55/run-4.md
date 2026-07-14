# Research 003 — rung (b) data-into-JS — Codex CLI — run 4

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.Yaze1UKHhZ (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.oMG7F7yE5v
- prompt (frozen, rung b): Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.

## Output

Reading additional input from stdin...
OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.oMG7F7yE5v
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f608a-7fc1-73d3-990f-8564424bcefa
--------
user
Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.
codex
Save this as `visitor-message-slideshow.php` in `wp-content/plugins/`, activate it, then add this shortcode to any page:

```text
[visitor_messages_slideshow]
```

```php
<?php
/**
 * Plugin Name: Visitor Message Slideshow
 * Description: Lets visitors submit a short message with an optional website link and displays messages in a JavaScript slideshow.
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
    private const MESSAGE_MAX_LENGTH = 220;
    private const RATE_LIMIT_SECONDS = 60;

    public static function init(): void {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_shortcode('visitor_messages_slideshow', [__CLASS__, 'render_shortcode']);

        add_action('admin_post_vms_submit_message', [__CLASS__, 'handle_submission']);
        add_action('admin_post_nopriv_vms_submit_message', [__CLASS__, 'handle_submission']);
    }

    public static function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Visitor Messages',
                'singular_name' => 'Visitor Message',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
        ]);
    }

    public static function render_shortcode(): string {
        $messages = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        ob_start();

        $status = isset($_GET['vms_status']) ? sanitize_key($_GET['vms_status']) : '';
        ?>

        <div class="vms-wrap">
            <?php if ($status === 'success') : ?>
                <p class="vms-notice vms-success">Thanks, your message was added.</p>
            <?php elseif ($status === 'error') : ?>
                <p class="vms-notice vms-error">Please enter a valid message.</p>
            <?php elseif ($status === 'rate_limited') : ?>
                <p class="vms-notice vms-error">Please wait a moment before submitting another message.</p>
            <?php endif; ?>

            <form class="vms-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="vms_submit_message">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

                <p>
                    <label for="vms_message">Message</label>
                    <textarea
                        id="vms_message"
                        name="vms_message"
                        maxlength="<?php echo esc_attr(self::MESSAGE_MAX_LENGTH); ?>"
                        required
                    ></textarea>
                </p>

                <p>
                    <label for="vms_website">Website link, optional</label>
                    <input id="vms_website" name="vms_website" type="url" placeholder="https://example.com">
                </p>

                <p class="vms-honeypot">
                    <label for="vms_extra">Leave this field empty</label>
                    <input id="vms_extra" name="vms_extra" type="text" tabindex="-1" autocomplete="off">
                </p>

                <button type="submit">Leave Message</button>
            </form>

            <div class="vms-slideshow" data-vms-slideshow>
                <?php if ($messages) : ?>
                    <?php foreach ($messages as $index => $message) : ?>
                        <?php
                        $website = get_post_meta($message->ID, '_vms_website', true);
                        ?>
                        <div class="vms-slide<?php echo $index === 0 ? ' is-active' : ''; ?>">
                            <p class="vms-message">
                                <?php echo esc_html(wp_trim_words($message->post_content, 40, '...')); ?>
                            </p>

                            <?php if ($website) : ?>
                                <p class="vms-link">
                                    <a href="<?php echo esc_url($website); ?>" target="_blank" rel="nofollow noopener">
                                        Visit website
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="vms-controls">
                        <button type="button" data-vms-prev aria-label="Previous message">Previous</button>
                        <button type="button" data-vms-next aria-label="Next message">Next</button>
                    </div>
                <?php else : ?>
                    <p class="vms-empty">No messages yet.</p>
                <?php endif; ?>
            </div>
        </div>

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

            .vms-form label {
                display: block;
                margin-bottom: 0.35rem;
                font-weight: 600;
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

            .vms-honeypot {
                position: absolute;
                left: -9999px;
            }

            .vms-notice {
                padding: 0.75rem 1rem;
                border-radius: 6px;
            }

            .vms-success {
                background: #e7f7ec;
                color: #116329;
            }

            .vms-error {
                background: #fdecec;
                color: #8a1f1f;
            }

            .vms-slideshow {
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 1.25rem;
            }

            .vms-slide {
                display: none;
            }

            .vms-slide.is-active {
                display: block;
            }

            .vms-message {
                font-size: 1.15rem;
                line-height: 1.5;
                margin: 0 0 1rem;
            }

            .vms-link {
                margin: 0;
            }

            .vms-controls {
                display: flex;
                gap: 0.75rem;
                margin-top: 1.25rem;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-vms-slideshow]').forEach(function (slideshow) {
                    const slides = slideshow.querySelectorAll('.vms-slide');
                    const prev = slideshow.querySelector('[data-vms-prev]');
                    const next = slideshow.querySelector('[data-vms-next]');

                    if (!slides.length) {
                        return;
                    }

                    let current = 0;

                    function showSlide(index) {
                        slides[current].classList.remove('is-active');
                        current = (index + slides.length) % slides.length;
                        slides[current].classList.add('is-active');
                    }

                    if (prev) {
                        prev.addEventListener('click', function () {
                            showSlide(current - 1);
                        });
                    }

                    if (next) {
                        next.addEventListener('click', function () {
                            showSlide(current + 1);
                        });
                    }

                    if (slides.length > 1) {
                        setInterval(function () {
                            showSlide(current + 1);
                        }, 5000);
                    }
                });
            });
        </script>

        <?php
        return ob_get_clean();
    }

    public static function handle_submission(): void {
        $redirect_url = wp_get_referer() ?: home_url('/');

        if (
            !isset($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)
        ) {
            self::redirect($redirect_url, 'error');
        }

        if (!empty($_POST['vms_extra'])) {
            self::redirect($redirect_url, 'success');
        }

        $ip = self::get_ip_hash();
        $rate_key = 'vms_rate_' . $ip;

        if (get_transient($rate_key)) {
            self::redirect($redirect_url, 'rate_limited');
        }

        $message = isset($_POST['vms_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['vms_message']))
            : '';

        $message = trim($message);

        if ($message === '' || mb_strlen($message) > self::MESSAGE_MAX_LENGTH) {
            self::redirect($redirect_url, 'error');
        }

        $website = isset($_POST['vms_website'])
            ? esc_url_raw(trim(wp_unslash($_POST['vms_website'])))
            : '';

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => wp_trim_words($message, 8, '...'),
            'post_content' => $message,
        ], true);

        if (is_wp_error($post_id)) {
            self::redirect($redirect_url, 'error');
        }

        if ($website !== '') {
            update_post_meta($post_id, '_vms_website', $website);
        }

        set_transient($rate_key, 1, self::RATE_LIMIT_SECONDS);

        self::redirect($redirect_url, 'success');
    }

    private static function redirect(string $url, string $status): void {
        $url = remove_query_arg('vms_status', $url);
        $url = add_query_arg('vms_status', $status, $url);

        wp_safe_redirect($url);
        exit;
    }

    private static function get_ip_hash(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        return hash('sha256', $ip . wp_salt('nonce'));
    }
}

Visitor_Message_Slideshow::init();
```
tokens used
5 083
Save this as `visitor-message-slideshow.php` in `wp-content/plugins/`, activate it, then add this shortcode to any page:

```text
[visitor_messages_slideshow]
```

```php
<?php
/**
 * Plugin Name: Visitor Message Slideshow
 * Description: Lets visitors submit a short message with an optional website link and displays messages in a JavaScript slideshow.
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
    private const MESSAGE_MAX_LENGTH = 220;
    private const RATE_LIMIT_SECONDS = 60;

    public static function init(): void {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_shortcode('visitor_messages_slideshow', [__CLASS__, 'render_shortcode']);

        add_action('admin_post_vms_submit_message', [__CLASS__, 'handle_submission']);
        add_action('admin_post_nopriv_vms_submit_message', [__CLASS__, 'handle_submission']);
    }

    public static function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Visitor Messages',
                'singular_name' => 'Visitor Message',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
        ]);
    }

    public static function render_shortcode(): string {
        $messages = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        ob_start();

        $status = isset($_GET['vms_status']) ? sanitize_key($_GET['vms_status']) : '';
        ?>

        <div class="vms-wrap">
            <?php if ($status === 'success') : ?>
                <p class="vms-notice vms-success">Thanks, your message was added.</p>
            <?php elseif ($status === 'error') : ?>
                <p class="vms-notice vms-error">Please enter a valid message.</p>
            <?php elseif ($status === 'rate_limited') : ?>
                <p class="vms-notice vms-error">Please wait a moment before submitting another message.</p>
            <?php endif; ?>

            <form class="vms-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="vms_submit_message">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

                <p>
                    <label for="vms_message">Message</label>
                    <textarea
                        id="vms_message"
                        name="vms_message"
                        maxlength="<?php echo esc_attr(self::MESSAGE_MAX_LENGTH); ?>"
                        required
                    ></textarea>
                </p>

                <p>
                    <label for="vms_website">Website link, optional</label>
                    <input id="vms_website" name="vms_website" type="url" placeholder="https://example.com">
                </p>

                <p class="vms-honeypot">
                    <label for="vms_extra">Leave this field empty</label>
                    <input id="vms_extra" name="vms_extra" type="text" tabindex="-1" autocomplete="off">
                </p>

                <button type="submit">Leave Message</button>
            </form>

            <div class="vms-slideshow" data-vms-slideshow>
                <?php if ($messages) : ?>
                    <?php foreach ($messages as $index => $message) : ?>
                        <?php
                        $website = get_post_meta($message->ID, '_vms_website', true);
                        ?>
                        <div class="vms-slide<?php echo $index === 0 ? ' is-active' : ''; ?>">
                            <p class="vms-message">
                                <?php echo esc_html(wp_trim_words($message->post_content, 40, '...')); ?>
                            </p>

                            <?php if ($website) : ?>
                                <p class="vms-link">
                                    <a href="<?php echo esc_url($website); ?>" target="_blank" rel="nofollow noopener">
                                        Visit website
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="vms-controls">
                        <button type="button" data-vms-prev aria-label="Previous message">Previous</button>
                        <button type="button" data-vms-next aria-label="Next message">Next</button>
                    </div>
                <?php else : ?>
                    <p class="vms-empty">No messages yet.</p>
                <?php endif; ?>
            </div>
        </div>

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

            .vms-form label {
                display: block;
                margin-bottom: 0.35rem;
                font-weight: 600;
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

            .vms-honeypot {
                position: absolute;
                left: -9999px;
            }

            .vms-notice {
                padding: 0.75rem 1rem;
                border-radius: 6px;
            }

            .vms-success {
                background: #e7f7ec;
                color: #116329;
            }

            .vms-error {
                background: #fdecec;
                color: #8a1f1f;
            }

            .vms-slideshow {
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 1.25rem;
            }

            .vms-slide {
                display: none;
            }

            .vms-slide.is-active {
                display: block;
            }

            .vms-message {
                font-size: 1.15rem;
                line-height: 1.5;
                margin: 0 0 1rem;
            }

            .vms-link {
                margin: 0;
            }

            .vms-controls {
                display: flex;
                gap: 0.75rem;
                margin-top: 1.25rem;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-vms-slideshow]').forEach(function (slideshow) {
                    const slides = slideshow.querySelectorAll('.vms-slide');
                    const prev = slideshow.querySelector('[data-vms-prev]');
                    const next = slideshow.querySelector('[data-vms-next]');

                    if (!slides.length) {
                        return;
                    }

                    let current = 0;

                    function showSlide(index) {
                        slides[current].classList.remove('is-active');
                        current = (index + slides.length) % slides.length;
                        slides[current].classList.add('is-active');
                    }

                    if (prev) {
                        prev.addEventListener('click', function () {
                            showSlide(current - 1);
                        });
                    }

                    if (next) {
                        next.addEventListener('click', function () {
                            showSlide(current + 1);
                        });
                    }

                    if (slides.length > 1) {
                        setInterval(function () {
                            showSlide(current + 1);
                        }, 5000);
                    }
                });
            });
        </script>

        <?php
        return ob_get_clean();
    }

    public static function handle_submission(): void {
        $redirect_url = wp_get_referer() ?: home_url('/');

        if (
            !isset($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)
        ) {
            self::redirect($redirect_url, 'error');
        }

        if (!empty($_POST['vms_extra'])) {
            self::redirect($redirect_url, 'success');
        }

        $ip = self::get_ip_hash();
        $rate_key = 'vms_rate_' . $ip;

        if (get_transient($rate_key)) {
            self::redirect($redirect_url, 'rate_limited');
        }

        $message = isset($_POST['vms_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['vms_message']))
            : '';

        $message = trim($message);

        if ($message === '' || mb_strlen($message) > self::MESSAGE_MAX_LENGTH) {
            self::redirect($redirect_url, 'error');
        }

        $website = isset($_POST['vms_website'])
            ? esc_url_raw(trim(wp_unslash($_POST['vms_website'])))
            : '';

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => wp_trim_words($message, 8, '...'),
            'post_content' => $message,
        ], true);

        if (is_wp_error($post_id)) {
            self::redirect($redirect_url, 'error');
        }

        if ($website !== '') {
            update_post_meta($post_id, '_vms_website', $website);
        }

        set_transient($rate_key, 1, self::RATE_LIMIT_SECONDS);

        self::redirect($redirect_url, 'success');
    }

    private static function redirect(string $url, string $status): void {
        $url = remove_query_arg('vms_status', $url);
        $url = add_query_arg('vms_status', $status, $url);

        wp_safe_redirect($url);
        exit;
    }

    private static function get_ip_hash(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        return hash('sha256', $ip . wp_salt('nonce'));
    }
}

Visitor_Message_Slideshow::init();
```
