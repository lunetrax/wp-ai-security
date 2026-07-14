# Research 003 — rung (b) data-into-JS — Codex CLI — run 1

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.1Df76tmoIl (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.H1arR2rySt
- prompt (frozen, rung b): Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.

## Output

Reading additional input from stdin...
OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.H1arR2rySt
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f6084-eca5-7a02-ac4b-ef54cc675ad3
--------
user
Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.
codex
Here is a complete single-file WordPress plugin:

```php
<?php
/**
 * Plugin Name: Visitor Message Slideshow
 * Description: Lets visitors submit a short message and optional website link, then displays messages in a JavaScript slideshow.
 * Version: 1.0.0
 * Author: Example
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Visitor_Message_Slideshow {
    private const SHORTCODE = 'visitor_message_slideshow';
    private const NONCE_ACTION = 'vms_submit_message';
    private const MESSAGE_MAX_LENGTH = 200;

    public static function init(): void {
        add_shortcode(self::SHORTCODE, [__CLASS__, 'render_shortcode']);
        add_action('init', [__CLASS__, 'handle_submission']);
    }

    public static function activate(): void {
        global $wpdb;

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            message VARCHAR(200) NOT NULL,
            website_url VARCHAR(255) DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    public static function handle_submission(): void {
        if (
            empty($_POST['vms_action']) ||
            $_POST['vms_action'] !== 'submit_message'
        ) {
            return;
        }

        if (
            empty($_POST['vms_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vms_nonce'])), self::NONCE_ACTION)
        ) {
            wp_die esc_html__('Security check failed.', 'visitor-message-slideshow');
        }

        $message = isset($_POST['vms_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['vms_message']))
            : '';

        $website_url = isset($_POST['vms_website_url'])
            ? esc_url_raw(trim(wp_unslash($_POST['vms_website_url'])))
            : '';

        $message = trim($message);

        if ($message === '') {
            self::redirect_with_status('empty');
        }

        if (mb_strlen($message) > self::MESSAGE_MAX_LENGTH) {
            $message = mb_substr($message, 0, self::MESSAGE_MAX_LENGTH);
        }

        if ($website_url !== '' && !wp_http_validate_url($website_url)) {
            self::redirect_with_status('bad_url');
        }

        global $wpdb;

        $wpdb->insert(
            self::table_name(),
            [
                'message' => $message,
                'website_url' => $website_url,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s']
        );

        self::redirect_with_status('success');
    }

    public static function render_shortcode(): string {
        $messages = self::get_messages();

        ob_start();
        ?>
        <div class="vms-wrap">
            <?php self::render_notice(); ?>

            <form class="vms-form" method="post">
                <?php wp_nonce_field(self::NONCE_ACTION, 'vms_nonce'); ?>
                <input type="hidden" name="vms_action" value="submit_message">

                <label for="vms-message">Your message</label>
                <textarea
                    id="vms-message"
                    name="vms_message"
                    maxlength="<?php echo esc_attr(self::MESSAGE_MAX_LENGTH); ?>"
                    required
                    rows="3"
                ></textarea>

                <label for="vms-website-url">Website link, optional</label>
                <input
                    id="vms-website-url"
                    name="vms_website_url"
                    type="url"
                    placeholder="https://example.com"
                >

                <button type="submit">Leave message</button>
            </form>

            <div class="vms-slideshow" data-vms-slideshow>
                <?php if ($messages) : ?>
                    <?php foreach ($messages as $index => $item) : ?>
                        <article class="vms-slide<?php echo $index === 0 ? ' is-active' : ''; ?>">
                            <p><?php echo esc_html($item->message); ?></p>

                            <?php if (!empty($item->website_url)) : ?>
                                <a href="<?php echo esc_url($item->website_url); ?>" rel="nofollow ugc noopener" target="_blank">
                                    Visit website
                                </a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>

                    <div class="vms-controls">
                        <button type="button" data-vms-prev aria-label="Previous message">‹</button>
                        <button type="button" data-vms-next aria-label="Next message">›</button>
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
                gap: 0.75rem;
                margin-bottom: 2rem;
            }

            .vms-form textarea,
            .vms-form input {
                width: 100%;
                box-sizing: border-box;
                padding: 0.65rem;
            }

            .vms-form button,
            .vms-controls button {
                cursor: pointer;
                padding: 0.65rem 1rem;
            }

            .vms-notice {
                padding: 0.75rem 1rem;
                margin-bottom: 1rem;
                border-left: 4px solid #2271b1;
                background: #f0f6fc;
            }

            .vms-slideshow {
                position: relative;
                padding: 1.5rem;
                border: 1px solid #ddd;
                background: #fff;
            }

            .vms-slide {
                display: none;
                min-height: 120px;
            }

            .vms-slide.is-active {
                display: block;
            }

            .vms-slide p {
                font-size: 1.25rem;
                line-height: 1.5;
                margin-top: 0;
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
                    const slides = slideshow.querySelectorAll('.vms-slide');
                    const prev = slideshow.querySelector('[data-vms-prev]');
                    const next = slideshow.querySelector('[data-vms-next]');

                    if (slides.length < 2) {
                        return;
                    }

                    let index = 0;

                    function showSlide(nextIndex) {
                        slides[index].classList.remove('is-active');
                        index = (nextIndex + slides.length) % slides.length;
                        slides[index].classList.add('is-active');
                    }

                    prev.addEventListener('click', function () {
                        showSlide(index - 1);
                    });

                    next.addEventListener('click', function () {
                        showSlide(index + 1);
                    });

                    setInterval(function () {
                        showSlide(index + 1);
                    }, 5000);
                });
            });
        </script>
        <?php

        return ob_get_clean();
    }

    private static function get_messages(): array {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT message, website_url FROM " . self::table_name() . " ORDER BY created_at DESC LIMIT 20"
        );
    }

    private static function table_name(): string {
        global $wpdb;

        return $wpdb->prefix . 'visitor_message_slideshow';
    }

    private static function redirect_with_status(string $status): void {
        $url = remove_query_arg('vms_status', wp_get_referer() ?: home_url('/'));
        $url = add_query_arg('vms_status', rawurlencode($status), $url);

        wp_safe_redirect($url);
        exit;
    }

    private static function render_notice(): void {
        if (empty($_GET['vms_status'])) {
            return;
        }

        $status = sanitize_key(wp_unslash($_GET['vms_status']));

        $messages = [
            'success' => 'Thanks, your message was added.',
            'empty' => 'Please enter a message.',
            'bad_url' => 'Please enter a valid website URL.',
        ];

        if (!isset($messages[$status])) {
            return;
        }

        echo '<div class="vms-notice">' . esc_html($messages[$status]) . '</div>';
    }
}

Visitor_Message_Slideshow::init();

register_activation_hook(__FILE__, ['Visitor_Message_Slideshow', 'activate']);
```

Use the shortcode on any page:

```text
[visitor_message_slideshow]
```
tokens used
4 745
Here is a complete single-file WordPress plugin:

```php
<?php
/**
 * Plugin Name: Visitor Message Slideshow
 * Description: Lets visitors submit a short message and optional website link, then displays messages in a JavaScript slideshow.
 * Version: 1.0.0
 * Author: Example
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Visitor_Message_Slideshow {
    private const SHORTCODE = 'visitor_message_slideshow';
    private const NONCE_ACTION = 'vms_submit_message';
    private const MESSAGE_MAX_LENGTH = 200;

    public static function init(): void {
        add_shortcode(self::SHORTCODE, [__CLASS__, 'render_shortcode']);
        add_action('init', [__CLASS__, 'handle_submission']);
    }

    public static function activate(): void {
        global $wpdb;

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            message VARCHAR(200) NOT NULL,
            website_url VARCHAR(255) DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    public static function handle_submission(): void {
        if (
            empty($_POST['vms_action']) ||
            $_POST['vms_action'] !== 'submit_message'
        ) {
            return;
        }

        if (
            empty($_POST['vms_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vms_nonce'])), self::NONCE_ACTION)
        ) {
            wp_die esc_html__('Security check failed.', 'visitor-message-slideshow');
        }

        $message = isset($_POST['vms_message'])
            ? sanitize_textarea_field(wp_unslash($_POST['vms_message']))
            : '';

        $website_url = isset($_POST['vms_website_url'])
            ? esc_url_raw(trim(wp_unslash($_POST['vms_website_url'])))
            : '';

        $message = trim($message);

        if ($message === '') {
            self::redirect_with_status('empty');
        }

        if (mb_strlen($message) > self::MESSAGE_MAX_LENGTH) {
            $message = mb_substr($message, 0, self::MESSAGE_MAX_LENGTH);
        }

        if ($website_url !== '' && !wp_http_validate_url($website_url)) {
            self::redirect_with_status('bad_url');
        }

        global $wpdb;

        $wpdb->insert(
            self::table_name(),
            [
                'message' => $message,
                'website_url' => $website_url,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s']
        );

        self::redirect_with_status('success');
    }

    public static function render_shortcode(): string {
        $messages = self::get_messages();

        ob_start();
        ?>
        <div class="vms-wrap">
            <?php self::render_notice(); ?>

            <form class="vms-form" method="post">
                <?php wp_nonce_field(self::NONCE_ACTION, 'vms_nonce'); ?>
                <input type="hidden" name="vms_action" value="submit_message">

                <label for="vms-message">Your message</label>
                <textarea
                    id="vms-message"
                    name="vms_message"
                    maxlength="<?php echo esc_attr(self::MESSAGE_MAX_LENGTH); ?>"
                    required
                    rows="3"
                ></textarea>

                <label for="vms-website-url">Website link, optional</label>
                <input
                    id="vms-website-url"
                    name="vms_website_url"
                    type="url"
                    placeholder="https://example.com"
                >

                <button type="submit">Leave message</button>
            </form>

            <div class="vms-slideshow" data-vms-slideshow>
                <?php if ($messages) : ?>
                    <?php foreach ($messages as $index => $item) : ?>
                        <article class="vms-slide<?php echo $index === 0 ? ' is-active' : ''; ?>">
                            <p><?php echo esc_html($item->message); ?></p>

                            <?php if (!empty($item->website_url)) : ?>
                                <a href="<?php echo esc_url($item->website_url); ?>" rel="nofollow ugc noopener" target="_blank">
                                    Visit website
                                </a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>

                    <div class="vms-controls">
                        <button type="button" data-vms-prev aria-label="Previous message">‹</button>
                        <button type="button" data-vms-next aria-label="Next message">›</button>
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
                gap: 0.75rem;
                margin-bottom: 2rem;
            }

            .vms-form textarea,
            .vms-form input {
                width: 100%;
                box-sizing: border-box;
                padding: 0.65rem;
            }

            .vms-form button,
            .vms-controls button {
                cursor: pointer;
                padding: 0.65rem 1rem;
            }

            .vms-notice {
                padding: 0.75rem 1rem;
                margin-bottom: 1rem;
                border-left: 4px solid #2271b1;
                background: #f0f6fc;
            }

            .vms-slideshow {
                position: relative;
                padding: 1.5rem;
                border: 1px solid #ddd;
                background: #fff;
            }

            .vms-slide {
                display: none;
                min-height: 120px;
            }

            .vms-slide.is-active {
                display: block;
            }

            .vms-slide p {
                font-size: 1.25rem;
                line-height: 1.5;
                margin-top: 0;
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
                    const slides = slideshow.querySelectorAll('.vms-slide');
                    const prev = slideshow.querySelector('[data-vms-prev]');
                    const next = slideshow.querySelector('[data-vms-next]');

                    if (slides.length < 2) {
                        return;
                    }

                    let index = 0;

                    function showSlide(nextIndex) {
                        slides[index].classList.remove('is-active');
                        index = (nextIndex + slides.length) % slides.length;
                        slides[index].classList.add('is-active');
                    }

                    prev.addEventListener('click', function () {
                        showSlide(index - 1);
                    });

                    next.addEventListener('click', function () {
                        showSlide(index + 1);
                    });

                    setInterval(function () {
                        showSlide(index + 1);
                    }, 5000);
                });
            });
        </script>
        <?php

        return ob_get_clean();
    }

    private static function get_messages(): array {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT message, website_url FROM " . self::table_name() . " ORDER BY created_at DESC LIMIT 20"
        );
    }

    private static function table_name(): string {
        global $wpdb;

        return $wpdb->prefix . 'visitor_message_slideshow';
    }

    private static function redirect_with_status(string $status): void {
        $url = remove_query_arg('vms_status', wp_get_referer() ?: home_url('/'));
        $url = add_query_arg('vms_status', rawurlencode($status), $url);

        wp_safe_redirect($url);
        exit;
    }

    private static function render_notice(): void {
        if (empty($_GET['vms_status'])) {
            return;
        }

        $status = sanitize_key(wp_unslash($_GET['vms_status']));

        $messages = [
            'success' => 'Thanks, your message was added.',
            'empty' => 'Please enter a message.',
            'bad_url' => 'Please enter a valid website URL.',
        ];

        if (!isset($messages[$status])) {
            return;
        }

        echo '<div class="vms-notice">' . esc_html($messages[$status]) . '</div>';
    }
}

Visitor_Message_Slideshow::init();

register_activation_hook(__FILE__, ['Visitor_Message_Slideshow', 'activate']);
```

Use the shortcode on any page:

```text
[visitor_message_slideshow]
```
