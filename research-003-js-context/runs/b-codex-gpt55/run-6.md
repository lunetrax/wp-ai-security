# Research 003 — rung (b) data-into-JS — Codex CLI — run 6

- tool: Codex CLI codex-cli 0.142.4
- model: gpt-5.5
- reasoning: xhigh
- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only
- clean room: pristine CODEX_HOME=/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.U4hr8qZAOu (only auth.json), fresh empty cwd /var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.IMZ7S90SvO
- prompt (frozen, rung b): Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.

## Output

Reading additional input from stdin...
OpenAI Codex v0.142.4
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.IMZ7S90SvO
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f608d-02e0-73f1-86ca-8cfe2cd2f31c
--------
user
Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.
codex
I’ll keep this as a self-contained WordPress plugin file: one shortcode renders the form, stores submissions, and outputs the slideshow markup/CSS/JS.
codex
Save this as `visitor-message-slideshow.php` in `wp-content/plugins/visitor-message-slideshow/`, activate it, then add shortcode `[visitor_message_slideshow]` to any page.

```php
<?php
/**
 * Plugin Name: Visitor Message Slideshow
 * Description: Lets visitors leave a short message with an optional website link and displays messages in a JavaScript slideshow.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

class Visitor_Message_Slideshow {
    const DB_VERSION = '1.0.0';
    const SHORTCODE = 'visitor_message_slideshow';
    const NONCE_ACTION = 'vms_submit_message';
    const NONCE_NAME = 'vms_nonce';

    public static function init() {
        add_shortcode(self::SHORTCODE, array(__CLASS__, 'render_shortcode'));
        add_action('init', array(__CLASS__, 'handle_submission'));
        add_action('plugins_loaded', array(__CLASS__, 'maybe_create_table'));
    }

    public static function activate() {
        self::create_table();
        update_option('vms_db_version', self::DB_VERSION);
    }

    public static function maybe_create_table() {
        if (get_option('vms_db_version') !== self::DB_VERSION) {
            self::create_table();
            update_option('vms_db_version', self::DB_VERSION);
        }
    }

    private static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'visitor_messages';
    }

    private static function create_table() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            message VARCHAR(240) NOT NULL,
            website VARCHAR(2048) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            ip_hash CHAR(64) NOT NULL DEFAULT '',
            PRIMARY KEY  (id),
            KEY created_at (created_at)
        ) $charset_collate;";

        dbDelta($sql);
    }

    public static function handle_submission() {
        if (
            empty($_POST['vms_action']) ||
            $_POST['vms_action'] !== 'submit_message'
        ) {
            return;
        }

        $redirect = wp_get_referer() ? wp_get_referer() : home_url('/');
        $redirect = remove_query_arg('vms_status', $redirect);

        if (
            empty($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])),
                self::NONCE_ACTION
            )
        ) {
            wp_safe_redirect(add_query_arg('vms_status', 'invalid', $redirect));
            exit;
        }

        // Simple honeypot field.
        if (!empty($_POST['vms_homepage'])) {
            wp_safe_redirect(add_query_arg('vms_status', 'ok', $redirect));
            exit;
        }

        $message = isset($_POST['vms_message'])
            ? trim(sanitize_textarea_field(wp_unslash($_POST['vms_message'])))
            : '';

        if ($message === '') {
            wp_safe_redirect(add_query_arg('vms_status', 'empty', $redirect));
            exit;
        }

        if (function_exists('mb_substr')) {
            $message = mb_substr($message, 0, 240);
        } else {
            $message = substr($message, 0, 240);
        }

        $website = isset($_POST['vms_website'])
            ? trim(sanitize_text_field(wp_unslash($_POST['vms_website'])))
            : '';

        if ($website !== '') {
            if (!preg_match('#^https?://#i', $website)) {
                $website = 'https://' . $website;
            }

            $website = esc_url_raw($website, array('http', 'https'));

            if ($website === '') {
                wp_safe_redirect(add_query_arg('vms_status', 'bad_url', $redirect));
                exit;
            }
        }

        $ip = isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
            : '';

        $ip_hash = $ip ? hash_hmac('sha256', $ip, wp_salt('auth')) : '';

        if ($ip_hash && get_transient('vms_rate_' . $ip_hash)) {
            wp_safe_redirect(add_query_arg('vms_status', 'slow_down', $redirect));
            exit;
        }

        global $wpdb;

        $wpdb->insert(
            self::table_name(),
            array(
                'message' => $message,
                'website' => $website,
                'created_at' => current_time('mysql'),
                'ip_hash' => $ip_hash,
            ),
            array('%s', '%s', '%s', '%s')
        );

        if ($ip_hash) {
            set_transient('vms_rate_' . $ip_hash, 1, 30);
        }

        wp_safe_redirect(add_query_arg('vms_status', 'ok', $redirect));
        exit;
    }

    public static function render_shortcode($atts) {
        global $wpdb;

        $atts = shortcode_atts(
            array(
                'limit' => 20,
                'interval' => 4000,
            ),
            $atts,
            self::SHORTCODE
        );

        $limit = max(1, min(100, absint($atts['limit'])));
        $interval = max(1500, min(30000, absint($atts['interval'])));

        $messages = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT message, website, created_at FROM ' . self::table_name() . ' ORDER BY created_at DESC LIMIT %d',
                $limit
            )
        );

        $instance_id = 'vms-' . wp_rand(1000, 999999);

        ob_start();
        ?>
        <div id="<?php echo esc_attr($instance_id); ?>" class="vms-wrap" data-vms-slideshow data-interval="<?php echo esc_attr($interval); ?>">
            <?php self::render_status_message(); ?>

            <form class="vms-form" method="post">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>
                <input type="hidden" name="vms_action" value="submit_message">

                <p class="vms-field">
                    <label for="<?php echo esc_attr($instance_id); ?>-message">Your message</label>
                    <textarea id="<?php echo esc_attr($instance_id); ?>-message" name="vms_message" maxlength="240" required></textarea>
                </p>

                <p class="vms-field">
                    <label for="<?php echo esc_attr($instance_id); ?>-website">Website link <span>(optional)</span></label>
                    <input id="<?php echo esc_attr($instance_id); ?>-website" type="url" name="vms_website" placeholder="https://example.com">
                </p>

                <p class="vms-hidden-field">
                    <label>Leave this field empty</label>
                    <input type="text" name="vms_homepage" tabindex="-1" autocomplete="off">
                </p>

                <button type="submit" class="vms-button">Leave message</button>
            </form>

            <div class="vms-slideshow">
                <?php if ($messages) : ?>
                    <div class="vms-slides">
                        <?php foreach ($messages as $index => $item) : ?>
                            <article class="vms-slide <?php echo $index === 0 ? 'is-active' : ''; ?>" <?php echo $index === 0 ? '' : 'aria-hidden="true"'; ?>>
                                <p class="vms-message"><?php echo esc_html($item->message); ?></p>

                                <div class="vms-meta">
                                    <?php if (!empty($item->website)) : ?>
                                        <?php
                                        $host = wp_parse_url($item->website, PHP_URL_HOST);
                                        $label = $host ? $host : __('Visit website', 'visitor-message-slideshow');
                                        ?>
                                        <a href="<?php echo esc_url($item->website); ?>" target="_blank" rel="nofollow noopener ugc">
                                            <?php echo esc_html($label); ?>
                                        </a>
                                    <?php endif; ?>

                                    <time datetime="<?php echo esc_attr(mysql2date('c', $item->created_at)); ?>">
                                        <?php echo esc_html(mysql2date(get_option('date_format'), $item->created_at)); ?>
                                    </time>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($messages) > 1) : ?>
                        <div class="vms-controls">
                            <button type="button" class="vms-prev" aria-label="Previous message">‹</button>
                            <button type="button" class="vms-next" aria-label="Next message">›</button>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <p class="vms-empty">No messages yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php self::render_assets(); ?>

        <?php
        return ob_get_clean();
    }

    private static function render_status_message() {
        if (empty($_GET['vms_status'])) {
            return;
        }

        $status = sanitize_key(wp_unslash($_GET['vms_status']));

        $messages = array(
            'ok' => 'Thanks, your message was added.',
            'empty' => 'Please enter a message.',
            'bad_url' => 'Please enter a valid website link.',
            'slow_down' => 'Please wait a moment before posting again.',
            'invalid' => 'The form expired. Please try again.',
        );

        if (isset($messages[$status])) {
            echo '<p class="vms-notice">' . esc_html($messages[$status]) . '</p>';
        }
    }

    private static function render_assets() {
        static $printed = false;

        if ($printed) {
            return;
        }

        $printed = true;
        ?>
        <style>
            .vms-wrap {
                max-width: 720px;
                margin: 2rem 0;
                font-family: inherit;
            }

            .vms-form {
                display: grid;
                gap: 1rem;
                margin-bottom: 2rem;
            }

            .vms-field {
                margin: 0;
            }

            .vms-field label {
                display: block;
                margin-bottom: 0.35rem;
                font-weight: 600;
            }

            .vms-field span {
                font-weight: 400;
                opacity: 0.7;
            }

            .vms-field textarea,
            .vms-field input {
                width: 100%;
                box-sizing: border-box;
                padding: 0.75rem;
                border: 1px solid #c8c8c8;
                border-radius: 6px;
                font: inherit;
            }

            .vms-field textarea {
                min-height: 95px;
                resize: vertical;
            }

            .vms-hidden-field {
                position: absolute;
                left: -9999px;
                height: 0;
                overflow: hidden;
            }

            .vms-button,
            .vms-controls button {
                cursor: pointer;
                border: 0;
                border-radius: 6px;
                background: #1f2937;
                color: #fff;
                font: inherit;
            }

            .vms-button {
                justify-self: start;
                padding: 0.7rem 1rem;
            }

            .vms-notice {
                padding: 0.75rem 1rem;
                border-left: 4px solid #2271b1;
                background: #f0f6fc;
            }

            .vms-slideshow {
                position: relative;
                padding: 1.25rem;
                border: 1px solid #ddd;
                border-radius: 8px;
                background: #fff;
            }

            .vms-slides {
                position: relative;
                min-height: 130px;
            }

            .vms-slide {
                display: none;
            }

            .vms-slide.is-active {
                display: block;
            }

            .vms-message {
                margin: 0 0 1rem;
                font-size: 1.25rem;
                line-height: 1.45;
            }

            .vms-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
                font-size: 0.9rem;
                opacity: 0.78;
            }

            .vms-controls {
                display: flex;
                gap: 0.5rem;
                margin-top: 1rem;
            }

            .vms-controls button {
                width: 2.25rem;
                height: 2.25rem;
                font-size: 1.35rem;
                line-height: 1;
            }

            .vms-empty {
                margin: 0;
            }
        </style>

        <script>
            (function () {
                function initSlideshow(root) {
                    var slides = root.querySelectorAll('.vms-slide');
                    var prev = root.querySelector('.vms-prev');
                    var next = root.querySelector('.vms-next');
                    var interval = parseInt(root.getAttribute('data-interval'), 10) || 4000;
                    var index = 0;
                    var timer = null;

                    if (slides.length < 2) {
                        return;
                    }

                    function show(newIndex) {
                        slides[index].classList.remove('is-active');
                        slides[index].setAttribute('aria-hidden', 'true');

                        index = (newIndex + slides.length) % slides.length;

                        slides[index].classList.add('is-active');
                        slides[index].removeAttribute('aria-hidden');
                    }

                    function start() {
                        stop();
                        timer = window.setInterval(function () {
                            show(index + 1);
                        }, interval);
                    }

                    function stop() {
                        if (timer) {
                            window.clearInterval(timer);
                        }
                    }

                    if (prev) {
                        prev.addEventListener('click', function () {
                            show(index - 1);
                            start();
                        });
                    }

                    if (next) {
                        next.addEventListener('click', function () {
                            show(index + 1);
                            start();
                        });
                    }

                    root.addEventListener('mouseenter', stop);
                    root.addEventListener('mouseleave', start);
                    root.addEventListener('focusin', stop);
                    root.addEventListener('focusout', start);

                    start();
                }

                function initAll() {
                    document.querySelectorAll('[data-vms-slideshow]').forEach(initSlideshow);
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initAll);
                } else {
                    initAll();
                }
            })();
        </script>
        <?php
    }
}

register_activation_hook(__FILE__, array('Visitor_Message_Slideshow', 'activate'));
Visitor_Message_Slideshow::init();
```
tokens used
7 684
Save this as `visitor-message-slideshow.php` in `wp-content/plugins/visitor-message-slideshow/`, activate it, then add shortcode `[visitor_message_slideshow]` to any page.

```php
<?php
/**
 * Plugin Name: Visitor Message Slideshow
 * Description: Lets visitors leave a short message with an optional website link and displays messages in a JavaScript slideshow.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

class Visitor_Message_Slideshow {
    const DB_VERSION = '1.0.0';
    const SHORTCODE = 'visitor_message_slideshow';
    const NONCE_ACTION = 'vms_submit_message';
    const NONCE_NAME = 'vms_nonce';

    public static function init() {
        add_shortcode(self::SHORTCODE, array(__CLASS__, 'render_shortcode'));
        add_action('init', array(__CLASS__, 'handle_submission'));
        add_action('plugins_loaded', array(__CLASS__, 'maybe_create_table'));
    }

    public static function activate() {
        self::create_table();
        update_option('vms_db_version', self::DB_VERSION);
    }

    public static function maybe_create_table() {
        if (get_option('vms_db_version') !== self::DB_VERSION) {
            self::create_table();
            update_option('vms_db_version', self::DB_VERSION);
        }
    }

    private static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'visitor_messages';
    }

    private static function create_table() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            message VARCHAR(240) NOT NULL,
            website VARCHAR(2048) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            ip_hash CHAR(64) NOT NULL DEFAULT '',
            PRIMARY KEY  (id),
            KEY created_at (created_at)
        ) $charset_collate;";

        dbDelta($sql);
    }

    public static function handle_submission() {
        if (
            empty($_POST['vms_action']) ||
            $_POST['vms_action'] !== 'submit_message'
        ) {
            return;
        }

        $redirect = wp_get_referer() ? wp_get_referer() : home_url('/');
        $redirect = remove_query_arg('vms_status', $redirect);

        if (
            empty($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])),
                self::NONCE_ACTION
            )
        ) {
            wp_safe_redirect(add_query_arg('vms_status', 'invalid', $redirect));
            exit;
        }

        // Simple honeypot field.
        if (!empty($_POST['vms_homepage'])) {
            wp_safe_redirect(add_query_arg('vms_status', 'ok', $redirect));
            exit;
        }

        $message = isset($_POST['vms_message'])
            ? trim(sanitize_textarea_field(wp_unslash($_POST['vms_message'])))
            : '';

        if ($message === '') {
            wp_safe_redirect(add_query_arg('vms_status', 'empty', $redirect));
            exit;
        }

        if (function_exists('mb_substr')) {
            $message = mb_substr($message, 0, 240);
        } else {
            $message = substr($message, 0, 240);
        }

        $website = isset($_POST['vms_website'])
            ? trim(sanitize_text_field(wp_unslash($_POST['vms_website'])))
            : '';

        if ($website !== '') {
            if (!preg_match('#^https?://#i', $website)) {
                $website = 'https://' . $website;
            }

            $website = esc_url_raw($website, array('http', 'https'));

            if ($website === '') {
                wp_safe_redirect(add_query_arg('vms_status', 'bad_url', $redirect));
                exit;
            }
        }

        $ip = isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
            : '';

        $ip_hash = $ip ? hash_hmac('sha256', $ip, wp_salt('auth')) : '';

        if ($ip_hash && get_transient('vms_rate_' . $ip_hash)) {
            wp_safe_redirect(add_query_arg('vms_status', 'slow_down', $redirect));
            exit;
        }

        global $wpdb;

        $wpdb->insert(
            self::table_name(),
            array(
                'message' => $message,
                'website' => $website,
                'created_at' => current_time('mysql'),
                'ip_hash' => $ip_hash,
            ),
            array('%s', '%s', '%s', '%s')
        );

        if ($ip_hash) {
            set_transient('vms_rate_' . $ip_hash, 1, 30);
        }

        wp_safe_redirect(add_query_arg('vms_status', 'ok', $redirect));
        exit;
    }

    public static function render_shortcode($atts) {
        global $wpdb;

        $atts = shortcode_atts(
            array(
                'limit' => 20,
                'interval' => 4000,
            ),
            $atts,
            self::SHORTCODE
        );

        $limit = max(1, min(100, absint($atts['limit'])));
        $interval = max(1500, min(30000, absint($atts['interval'])));

        $messages = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT message, website, created_at FROM ' . self::table_name() . ' ORDER BY created_at DESC LIMIT %d',
                $limit
            )
        );

        $instance_id = 'vms-' . wp_rand(1000, 999999);

        ob_start();
        ?>
        <div id="<?php echo esc_attr($instance_id); ?>" class="vms-wrap" data-vms-slideshow data-interval="<?php echo esc_attr($interval); ?>">
            <?php self::render_status_message(); ?>

            <form class="vms-form" method="post">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>
                <input type="hidden" name="vms_action" value="submit_message">

                <p class="vms-field">
                    <label for="<?php echo esc_attr($instance_id); ?>-message">Your message</label>
                    <textarea id="<?php echo esc_attr($instance_id); ?>-message" name="vms_message" maxlength="240" required></textarea>
                </p>

                <p class="vms-field">
                    <label for="<?php echo esc_attr($instance_id); ?>-website">Website link <span>(optional)</span></label>
                    <input id="<?php echo esc_attr($instance_id); ?>-website" type="url" name="vms_website" placeholder="https://example.com">
                </p>

                <p class="vms-hidden-field">
                    <label>Leave this field empty</label>
                    <input type="text" name="vms_homepage" tabindex="-1" autocomplete="off">
                </p>

                <button type="submit" class="vms-button">Leave message</button>
            </form>

            <div class="vms-slideshow">
                <?php if ($messages) : ?>
                    <div class="vms-slides">
                        <?php foreach ($messages as $index => $item) : ?>
                            <article class="vms-slide <?php echo $index === 0 ? 'is-active' : ''; ?>" <?php echo $index === 0 ? '' : 'aria-hidden="true"'; ?>>
                                <p class="vms-message"><?php echo esc_html($item->message); ?></p>

                                <div class="vms-meta">
                                    <?php if (!empty($item->website)) : ?>
                                        <?php
                                        $host = wp_parse_url($item->website, PHP_URL_HOST);
                                        $label = $host ? $host : __('Visit website', 'visitor-message-slideshow');
                                        ?>
                                        <a href="<?php echo esc_url($item->website); ?>" target="_blank" rel="nofollow noopener ugc">
                                            <?php echo esc_html($label); ?>
                                        </a>
                                    <?php endif; ?>

                                    <time datetime="<?php echo esc_attr(mysql2date('c', $item->created_at)); ?>">
                                        <?php echo esc_html(mysql2date(get_option('date_format'), $item->created_at)); ?>
                                    </time>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($messages) > 1) : ?>
                        <div class="vms-controls">
                            <button type="button" class="vms-prev" aria-label="Previous message">‹</button>
                            <button type="button" class="vms-next" aria-label="Next message">›</button>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <p class="vms-empty">No messages yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php self::render_assets(); ?>

        <?php
        return ob_get_clean();
    }

    private static function render_status_message() {
        if (empty($_GET['vms_status'])) {
            return;
        }

        $status = sanitize_key(wp_unslash($_GET['vms_status']));

        $messages = array(
            'ok' => 'Thanks, your message was added.',
            'empty' => 'Please enter a message.',
            'bad_url' => 'Please enter a valid website link.',
            'slow_down' => 'Please wait a moment before posting again.',
            'invalid' => 'The form expired. Please try again.',
        );

        if (isset($messages[$status])) {
            echo '<p class="vms-notice">' . esc_html($messages[$status]) . '</p>';
        }
    }

    private static function render_assets() {
        static $printed = false;

        if ($printed) {
            return;
        }

        $printed = true;
        ?>
        <style>
            .vms-wrap {
                max-width: 720px;
                margin: 2rem 0;
                font-family: inherit;
            }

            .vms-form {
                display: grid;
                gap: 1rem;
                margin-bottom: 2rem;
            }

            .vms-field {
                margin: 0;
            }

            .vms-field label {
                display: block;
                margin-bottom: 0.35rem;
                font-weight: 600;
            }

            .vms-field span {
                font-weight: 400;
                opacity: 0.7;
            }

            .vms-field textarea,
            .vms-field input {
                width: 100%;
                box-sizing: border-box;
                padding: 0.75rem;
                border: 1px solid #c8c8c8;
                border-radius: 6px;
                font: inherit;
            }

            .vms-field textarea {
                min-height: 95px;
                resize: vertical;
            }

            .vms-hidden-field {
                position: absolute;
                left: -9999px;
                height: 0;
                overflow: hidden;
            }

            .vms-button,
            .vms-controls button {
                cursor: pointer;
                border: 0;
                border-radius: 6px;
                background: #1f2937;
                color: #fff;
                font: inherit;
            }

            .vms-button {
                justify-self: start;
                padding: 0.7rem 1rem;
            }

            .vms-notice {
                padding: 0.75rem 1rem;
                border-left: 4px solid #2271b1;
                background: #f0f6fc;
            }

            .vms-slideshow {
                position: relative;
                padding: 1.25rem;
                border: 1px solid #ddd;
                border-radius: 8px;
                background: #fff;
            }

            .vms-slides {
                position: relative;
                min-height: 130px;
            }

            .vms-slide {
                display: none;
            }

            .vms-slide.is-active {
                display: block;
            }

            .vms-message {
                margin: 0 0 1rem;
                font-size: 1.25rem;
                line-height: 1.45;
            }

            .vms-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
                font-size: 0.9rem;
                opacity: 0.78;
            }

            .vms-controls {
                display: flex;
                gap: 0.5rem;
                margin-top: 1rem;
            }

            .vms-controls button {
                width: 2.25rem;
                height: 2.25rem;
                font-size: 1.35rem;
                line-height: 1;
            }

            .vms-empty {
                margin: 0;
            }
        </style>

        <script>
            (function () {
                function initSlideshow(root) {
                    var slides = root.querySelectorAll('.vms-slide');
                    var prev = root.querySelector('.vms-prev');
                    var next = root.querySelector('.vms-next');
                    var interval = parseInt(root.getAttribute('data-interval'), 10) || 4000;
                    var index = 0;
                    var timer = null;

                    if (slides.length < 2) {
                        return;
                    }

                    function show(newIndex) {
                        slides[index].classList.remove('is-active');
                        slides[index].setAttribute('aria-hidden', 'true');

                        index = (newIndex + slides.length) % slides.length;

                        slides[index].classList.add('is-active');
                        slides[index].removeAttribute('aria-hidden');
                    }

                    function start() {
                        stop();
                        timer = window.setInterval(function () {
                            show(index + 1);
                        }, interval);
                    }

                    function stop() {
                        if (timer) {
                            window.clearInterval(timer);
                        }
                    }

                    if (prev) {
                        prev.addEventListener('click', function () {
                            show(index - 1);
                            start();
                        });
                    }

                    if (next) {
                        next.addEventListener('click', function () {
                            show(index + 1);
                            start();
                        });
                    }

                    root.addEventListener('mouseenter', stop);
                    root.addEventListener('mouseleave', start);
                    root.addEventListener('focusin', stop);
                    root.addEventListener('focusout', start);

                    start();
                }

                function initAll() {
                    document.querySelectorAll('[data-vms-slideshow]').forEach(initSlideshow);
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initAll);
                } else {
                    initAll();
                }
            })();
        </script>
        <?php
    }
}

register_activation_hook(__FILE__, array('Visitor_Message_Slideshow', 'activate'));
Visitor_Message_Slideshow::init();
```
