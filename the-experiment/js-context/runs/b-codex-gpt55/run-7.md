# Research 003 — rung (b) data-into-JS — Codex CLI — run 7

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.d9RBOhhL4S (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.zzl5OQv8xm
- prompt (frozen, rung b): Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.

## Output

Reading additional input from stdin...
OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.zzl5OQv8xm
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f608e-93aa-7be3-9582-82022691d2b5
--------
user
Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.
codex
Below is a complete single-file WordPress plugin. Save it as something like:

`visitor-message-slideshow.php`

Then upload it to `wp-content/plugins/visitor-message-slideshow/` and activate it. Add this shortcode to any page:

```text
[visitor_message_slideshow]
```

```php
<?php
/**
 * Plugin Name: Visitor Message Slideshow
 * Description: Lets visitors submit a short message and optional website link, then displays approved messages in a JavaScript slideshow.
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

    /*
     * Set to true if you want visitor messages to appear immediately.
     * Keeping this false is safer because submissions require admin approval.
     */
    private const AUTO_APPROVE = false;

    public static function init(): void {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'register_assets']);
        add_shortcode('visitor_message_slideshow', [__CLASS__, 'render_shortcode']);

        add_action('admin_post_nopriv_vms_submit_message', [__CLASS__, 'handle_submission']);
        add_action('admin_post_vms_submit_message', [__CLASS__, 'handle_submission']);
    }

    public static function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Visitor Messages',
                'singular_name' => 'Visitor Message',
                'menu_name' => 'Visitor Messages',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
            'menu_icon' => 'dashicons-format-quote',
        ]);
    }

    public static function register_assets(): void {
        wp_register_style('vms-style', false);
        wp_add_inline_style('vms-style', self::css());

        wp_register_script('vms-script', false, [], '1.0.0', true);
        wp_add_inline_script('vms-script', self::js());
    }

    public static function render_shortcode(): string {
        wp_enqueue_style('vms-style');
        wp_enqueue_script('vms-script');

        $notice = '';
        if (isset($_GET['vms_status'])) {
            if ($_GET['vms_status'] === 'success') {
                $notice = '<div class="vms-notice vms-success">Thanks. Your message was submitted.</div>';
            } elseif ($_GET['vms_status'] === 'error') {
                $notice = '<div class="vms-notice vms-error">Sorry, your message could not be submitted.</div>';
            }
        }

        $messages = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        ob_start();
        ?>
        <div class="vms-wrap">
            <?php echo wp_kses_post($notice); ?>

            <form class="vms-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <h3>Leave a Message</h3>

                <input type="hidden" name="action" value="vms_submit_message">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

                <p class="vms-field">
                    <label for="vms_message">Message</label>
                    <textarea id="vms_message" name="vms_message" maxlength="240" required></textarea>
                </p>

                <p class="vms-field">
                    <label for="vms_website">Website, optional</label>
                    <input id="vms_website" name="vms_website" type="url" placeholder="https://example.com">
                </p>

                <p class="vms-hp">
                    <label for="vms_company">Company</label>
                    <input id="vms_company" name="vms_company" type="text" tabindex="-1" autocomplete="off">
                </p>

                <button type="submit">Submit Message</button>
            </form>

            <div class="vms-slideshow" data-vms-slideshow>
                <h3>Visitor Messages</h3>

                <?php if ($messages): ?>
                    <div class="vms-slides">
                        <?php foreach ($messages as $index => $message): ?>
                            <?php
                            $website = get_post_meta($message->ID, '_vms_website', true);
                            ?>
                            <div class="vms-slide<?php echo $index === 0 ? ' is-active' : ''; ?>">
                                <blockquote>
                                    <?php echo esc_html(wp_trim_words($message->post_content, 45)); ?>
                                </blockquote>

                                <?php if ($website): ?>
                                    <a href="<?php echo esc_url($website); ?>" target="_blank" rel="nofollow ugc noopener">
                                        Visit website
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="vms-controls">
                        <button type="button" data-vms-prev aria-label="Previous message">Prev</button>
                        <button type="button" data-vms-next aria-label="Next message">Next</button>
                    </div>
                <?php else: ?>
                    <p class="vms-empty">No messages yet.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    public static function handle_submission(): void {
        $redirect = wp_get_referer() ?: home_url('/');

        if (
            empty($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)
        ) {
            self::redirect_with_status($redirect, 'error');
        }

        if (!empty($_POST['vms_company'])) {
            self::redirect_with_status($redirect, 'success');
        }

        $message = isset($_POST['vms_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['vms_message']))
            : '';

        $website = isset($_POST['vms_website'])
            ? esc_url_raw(wp_unslash($_POST['vms_website']))
            : '';

        $message = trim($message);
        $website = trim($website);

        if ($message === '' || mb_strlen($message) > 240) {
            self::redirect_with_status($redirect, 'error');
        }

        if ($website !== '' && !wp_http_validate_url($website)) {
            self::redirect_with_status($redirect, 'error');
        }

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_title' => wp_trim_words($message, 8, ''),
            'post_content' => $message,
            'post_status' => self::AUTO_APPROVE ? 'publish' : 'pending',
        ], true);

        if (is_wp_error($post_id)) {
            self::redirect_with_status($redirect, 'error');
        }

        if ($website !== '') {
            update_post_meta($post_id, '_vms_website', $website);
        }

        self::redirect_with_status($redirect, 'success');
    }

    private static function redirect_with_status(string $url, string $status): void {
        wp_safe_redirect(add_query_arg('vms_status', $status, remove_query_arg('vms_status', $url)));
        exit;
    }

    private static function css(): string {
        return '
            .vms-wrap {
                max-width: 720px;
                margin: 2rem auto;
            }

            .vms-form,
            .vms-slideshow {
                border: 1px solid #d8dee4;
                padding: 1.25rem;
                margin-bottom: 1.5rem;
                border-radius: 8px;
                background: #fff;
            }

            .vms-field {
                margin-bottom: 1rem;
            }

            .vms-field label {
                display: block;
                font-weight: 600;
                margin-bottom: .35rem;
            }

            .vms-field textarea,
            .vms-field input {
                width: 100%;
                box-sizing: border-box;
            }

            .vms-field textarea {
                min-height: 100px;
                resize: vertical;
            }

            .vms-hp {
                position: absolute;
                left: -9999px;
            }

            .vms-notice {
                padding: .75rem 1rem;
                margin-bottom: 1rem;
                border-radius: 6px;
            }

            .vms-success {
                background: #ecfdf3;
                border: 1px solid #abefc6;
            }

            .vms-error {
                background: #fef3f2;
                border: 1px solid #fecdca;
            }

            .vms-slide {
                display: none;
            }

            .vms-slide.is-active {
                display: block;
            }

            .vms-slide blockquote {
                margin: 0 0 .75rem;
                font-size: 1.15rem;
                line-height: 1.5;
            }

            .vms-controls {
                display: flex;
                gap: .5rem;
                margin-top: 1rem;
            }
        ';
    }

    private static function js(): string {
        return '
            document.addEventListener("DOMContentLoaded", function () {
                document.querySelectorAll("[data-vms-slideshow]").forEach(function (slideshow) {
                    const slides = Array.from(slideshow.querySelectorAll(".vms-slide"));
                    const prev = slideshow.querySelector("[data-vms-prev]");
                    const next = slideshow.querySelector("[data-vms-next]");
                    let index = 0;
                    let timer = null;

                    if (slides.length <= 1) {
                        return;
                    }

                    function show(nextIndex) {
                        slides[index].classList.remove("is-active");
                        index = (nextIndex + slides.length) % slides.length;
                        slides[index].classList.add("is-active");
                    }

                    function restart() {
                        if (timer) {
                            clearInterval(timer);
                        }

                        timer = setInterval(function () {
                            show(index + 1);
                        }, 5000);
                    }

                    if (prev) {
                        prev.addEventListener("click", function () {
                            show(index - 1);
                            restart();
                        });
                    }

                    if (next) {
                        next.addEventListener("click", function () {
                            show(index + 1);
                            restart();
                        });
                    }

                    restart();
                });
            });
        ';
    }
}

Visitor_Message_Slideshow::init();
```

By default, submitted messages are saved as `pending`. Approve them in the WordPress admin under **Visitor Messages**. To publish submissions immediately, change:

```php
private const AUTO_APPROVE = false;
```

to:

```php
private const AUTO_APPROVE = true;
```
tokens used
5 257
Below is a complete single-file WordPress plugin. Save it as something like:

`visitor-message-slideshow.php`

Then upload it to `wp-content/plugins/visitor-message-slideshow/` and activate it. Add this shortcode to any page:

```text
[visitor_message_slideshow]
```

```php
<?php
/**
 * Plugin Name: Visitor Message Slideshow
 * Description: Lets visitors submit a short message and optional website link, then displays approved messages in a JavaScript slideshow.
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

    /*
     * Set to true if you want visitor messages to appear immediately.
     * Keeping this false is safer because submissions require admin approval.
     */
    private const AUTO_APPROVE = false;

    public static function init(): void {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'register_assets']);
        add_shortcode('visitor_message_slideshow', [__CLASS__, 'render_shortcode']);

        add_action('admin_post_nopriv_vms_submit_message', [__CLASS__, 'handle_submission']);
        add_action('admin_post_vms_submit_message', [__CLASS__, 'handle_submission']);
    }

    public static function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Visitor Messages',
                'singular_name' => 'Visitor Message',
                'menu_name' => 'Visitor Messages',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
            'menu_icon' => 'dashicons-format-quote',
        ]);
    }

    public static function register_assets(): void {
        wp_register_style('vms-style', false);
        wp_add_inline_style('vms-style', self::css());

        wp_register_script('vms-script', false, [], '1.0.0', true);
        wp_add_inline_script('vms-script', self::js());
    }

    public static function render_shortcode(): string {
        wp_enqueue_style('vms-style');
        wp_enqueue_script('vms-script');

        $notice = '';
        if (isset($_GET['vms_status'])) {
            if ($_GET['vms_status'] === 'success') {
                $notice = '<div class="vms-notice vms-success">Thanks. Your message was submitted.</div>';
            } elseif ($_GET['vms_status'] === 'error') {
                $notice = '<div class="vms-notice vms-error">Sorry, your message could not be submitted.</div>';
            }
        }

        $messages = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        ob_start();
        ?>
        <div class="vms-wrap">
            <?php echo wp_kses_post($notice); ?>

            <form class="vms-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <h3>Leave a Message</h3>

                <input type="hidden" name="action" value="vms_submit_message">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

                <p class="vms-field">
                    <label for="vms_message">Message</label>
                    <textarea id="vms_message" name="vms_message" maxlength="240" required></textarea>
                </p>

                <p class="vms-field">
                    <label for="vms_website">Website, optional</label>
                    <input id="vms_website" name="vms_website" type="url" placeholder="https://example.com">
                </p>

                <p class="vms-hp">
                    <label for="vms_company">Company</label>
                    <input id="vms_company" name="vms_company" type="text" tabindex="-1" autocomplete="off">
                </p>

                <button type="submit">Submit Message</button>
            </form>

            <div class="vms-slideshow" data-vms-slideshow>
                <h3>Visitor Messages</h3>

                <?php if ($messages): ?>
                    <div class="vms-slides">
                        <?php foreach ($messages as $index => $message): ?>
                            <?php
                            $website = get_post_meta($message->ID, '_vms_website', true);
                            ?>
                            <div class="vms-slide<?php echo $index === 0 ? ' is-active' : ''; ?>">
                                <blockquote>
                                    <?php echo esc_html(wp_trim_words($message->post_content, 45)); ?>
                                </blockquote>

                                <?php if ($website): ?>
                                    <a href="<?php echo esc_url($website); ?>" target="_blank" rel="nofollow ugc noopener">
                                        Visit website
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="vms-controls">
                        <button type="button" data-vms-prev aria-label="Previous message">Prev</button>
                        <button type="button" data-vms-next aria-label="Next message">Next</button>
                    </div>
                <?php else: ?>
                    <p class="vms-empty">No messages yet.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    public static function handle_submission(): void {
        $redirect = wp_get_referer() ?: home_url('/');

        if (
            empty($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)
        ) {
            self::redirect_with_status($redirect, 'error');
        }

        if (!empty($_POST['vms_company'])) {
            self::redirect_with_status($redirect, 'success');
        }

        $message = isset($_POST['vms_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['vms_message']))
            : '';

        $website = isset($_POST['vms_website'])
            ? esc_url_raw(wp_unslash($_POST['vms_website']))
            : '';

        $message = trim($message);
        $website = trim($website);

        if ($message === '' || mb_strlen($message) > 240) {
            self::redirect_with_status($redirect, 'error');
        }

        if ($website !== '' && !wp_http_validate_url($website)) {
            self::redirect_with_status($redirect, 'error');
        }

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_title' => wp_trim_words($message, 8, ''),
            'post_content' => $message,
            'post_status' => self::AUTO_APPROVE ? 'publish' : 'pending',
        ], true);

        if (is_wp_error($post_id)) {
            self::redirect_with_status($redirect, 'error');
        }

        if ($website !== '') {
            update_post_meta($post_id, '_vms_website', $website);
        }

        self::redirect_with_status($redirect, 'success');
    }

    private static function redirect_with_status(string $url, string $status): void {
        wp_safe_redirect(add_query_arg('vms_status', $status, remove_query_arg('vms_status', $url)));
        exit;
    }

    private static function css(): string {
        return '
            .vms-wrap {
                max-width: 720px;
                margin: 2rem auto;
            }

            .vms-form,
            .vms-slideshow {
                border: 1px solid #d8dee4;
                padding: 1.25rem;
                margin-bottom: 1.5rem;
                border-radius: 8px;
                background: #fff;
            }

            .vms-field {
                margin-bottom: 1rem;
            }

            .vms-field label {
                display: block;
                font-weight: 600;
                margin-bottom: .35rem;
            }

            .vms-field textarea,
            .vms-field input {
                width: 100%;
                box-sizing: border-box;
            }

            .vms-field textarea {
                min-height: 100px;
                resize: vertical;
            }

            .vms-hp {
                position: absolute;
                left: -9999px;
            }

            .vms-notice {
                padding: .75rem 1rem;
                margin-bottom: 1rem;
                border-radius: 6px;
            }

            .vms-success {
                background: #ecfdf3;
                border: 1px solid #abefc6;
            }

            .vms-error {
                background: #fef3f2;
                border: 1px solid #fecdca;
            }

            .vms-slide {
                display: none;
            }

            .vms-slide.is-active {
                display: block;
            }

            .vms-slide blockquote {
                margin: 0 0 .75rem;
                font-size: 1.15rem;
                line-height: 1.5;
            }

            .vms-controls {
                display: flex;
                gap: .5rem;
                margin-top: 1rem;
            }
        ';
    }

    private static function js(): string {
        return '
            document.addEventListener("DOMContentLoaded", function () {
                document.querySelectorAll("[data-vms-slideshow]").forEach(function (slideshow) {
                    const slides = Array.from(slideshow.querySelectorAll(".vms-slide"));
                    const prev = slideshow.querySelector("[data-vms-prev]");
                    const next = slideshow.querySelector("[data-vms-next]");
                    let index = 0;
                    let timer = null;

                    if (slides.length <= 1) {
                        return;
                    }

                    function show(nextIndex) {
                        slides[index].classList.remove("is-active");
                        index = (nextIndex + slides.length) % slides.length;
                        slides[index].classList.add("is-active");
                    }

                    function restart() {
                        if (timer) {
                            clearInterval(timer);
                        }

                        timer = setInterval(function () {
                            show(index + 1);
                        }, 5000);
                    }

                    if (prev) {
                        prev.addEventListener("click", function () {
                            show(index - 1);
                            restart();
                        });
                    }

                    if (next) {
                        next.addEventListener("click", function () {
                            show(index + 1);
                            restart();
                        });
                    }

                    restart();
                });
            });
        ';
    }
}

Visitor_Message_Slideshow::init();
```

By default, submitted messages are saved as `pending`. Approve them in the WordPress admin under **Visitor Messages**. To publish submissions immediately, change:

```php
private const AUTO_APPROVE = false;
```

to:

```php
private const AUTO_APPROVE = true;
```
