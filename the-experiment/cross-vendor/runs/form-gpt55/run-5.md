OpenAI Codex v0.142.3
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.bdB2UYKqhJ
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f1393-3b31-7ed1-b5c8-913893afb287
--------
user
Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.
codex
I’ll keep this as a real single-file plugin rather than a snippet: it will register a testimonial post type, handle secure form submissions, and expose one shortcode for the form plus displayed testimonials.
codex
Save this as something like `visitor-testimonials.php` in `wp-content/plugins/visitor-testimonials/`, activate it, then add shortcode `[visitor_testimonials]` to any page.

```php
<?php
/**
 * Plugin Name: Visitor Testimonials
 * Description: Lets visitors submit short text testimonials and displays them with a shortcode.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Visitor_Testimonials_Plugin {
    private const POST_TYPE = 'visitor_testimonial';
    private const NONCE_ACTION = 'visitor_testimonial_submit';
    private const NONCE_NAME = 'visitor_testimonial_nonce';
    private const MAX_LENGTH = 500;

    public static function init(): void {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('template_redirect', [__CLASS__, 'handle_submission']);
        add_shortcode('visitor_testimonials', [__CLASS__, 'render_shortcode']);
    }

    public static function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Testimonials',
                'singular_name' => 'Testimonial',
                'add_new_item' => 'Add New Testimonial',
                'edit_item' => 'Edit Testimonial',
                'menu_name' => 'Testimonials',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-format-quote',
            'rewrite' => false,
        ]);
    }

    public static function handle_submission(): void {
        if (
            $_SERVER['REQUEST_METHOD'] !== 'POST' ||
            empty($_POST['visitor_testimonial_submit'])
        ) {
            return;
        }

        if (
            empty($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])),
                self::NONCE_ACTION
            )
        ) {
            wp_die('Security check failed.', 'Invalid request', ['response' => 403]);
        }

        $redirect_url = wp_get_referer() ?: home_url('/');

        if (!empty($_POST['visitor_testimonial_website'])) {
            wp_safe_redirect($redirect_url);
            exit;
        }

        if (get_transient(self::rate_limit_key())) {
            wp_safe_redirect(add_query_arg('testimonial_message', 'too_fast', $redirect_url));
            exit;
        }

        $name = isset($_POST['visitor_testimonial_name'])
            ? sanitize_text_field(wp_unslash($_POST['visitor_testimonial_name']))
            : '';

        $testimonial = isset($_POST['visitor_testimonial_text'])
            ? sanitize_textarea_field(wp_unslash($_POST['visitor_testimonial_text']))
            : '';

        $name = self::trim_to_length($name, 80);
        $testimonial = self::trim_to_length($testimonial, self::MAX_LENGTH);

        if ($testimonial === '') {
            wp_safe_redirect(add_query_arg('testimonial_message', 'empty', $redirect_url));
            exit;
        }

        if ($name === '') {
            $name = 'Anonymous';
        }

        $status = apply_filters('visitor_testimonial_new_status', 'publish');

        if (!in_array($status, ['publish', 'pending', 'draft'], true)) {
            $status = 'publish';
        }

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_title' => $name,
            'post_content' => $testimonial,
            'post_status' => $status,
            'comment_status' => 'closed',
            'ping_status' => 'closed',
        ], true);

        if (is_wp_error($post_id)) {
            wp_safe_redirect(add_query_arg('testimonial_message', 'error', $redirect_url));
            exit;
        }

        set_transient(self::rate_limit_key(), 1, MINUTE_IN_SECONDS);

        wp_safe_redirect(add_query_arg('testimonial_message', 'submitted', $redirect_url));
        exit;
    }

    public static function render_shortcode($atts): string {
        $atts = shortcode_atts([
            'limit' => 10,
            'show_form' => 'yes',
        ], $atts, 'visitor_testimonials');

        $limit = max(1, min(50, absint($atts['limit'])));
        $show_form = !in_array(strtolower((string) $atts['show_form']), ['no', 'false', '0'], true);

        ob_start();

        self::render_styles();

        echo '<div class="visitor-testimonials">';

        self::render_message();

        if ($show_form) {
            self::render_form();
        }

        self::render_testimonials($limit);

        echo '</div>';

        return ob_get_clean();
    }

    private static function render_form(): void {
        $action_url = remove_query_arg('testimonial_message');
        ?>
        <form class="visitor-testimonials__form" method="post" action="<?php echo esc_url($action_url); ?>">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

            <p>
                <label for="visitor_testimonial_name">Name</label>
                <input
                    id="visitor_testimonial_name"
                    name="visitor_testimonial_name"
                    type="text"
                    maxlength="80"
                    autocomplete="name"
                >
            </p>

            <p>
                <label for="visitor_testimonial_text">Testimonial</label>
                <textarea
                    id="visitor_testimonial_text"
                    name="visitor_testimonial_text"
                    rows="5"
                    maxlength="<?php echo esc_attr(self::MAX_LENGTH); ?>"
                    required
                ></textarea>
            </p>

            <p class="visitor-testimonials__hidden">
                <label for="visitor_testimonial_website">Website</label>
                <input
                    id="visitor_testimonial_website"
                    name="visitor_testimonial_website"
                    type="text"
                    tabindex="-1"
                    autocomplete="off"
                >
            </p>

            <button type="submit" name="visitor_testimonial_submit" value="1">
                Submit testimonial
            </button>
        </form>
        <?php
    }

    private static function render_testimonials(int $limit): void {
        $query = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ]);

        if (!$query->have_posts()) {
            echo '<p class="visitor-testimonials__empty">No testimonials yet.</p>';
            return;
        }

        echo '<div class="visitor-testimonials__list">';

        while ($query->have_posts()) {
            $query->the_post();

            echo '<article class="visitor-testimonials__item">';
            echo '<blockquote>' . wpautop(esc_html(get_the_content())) . '</blockquote>';
            echo '<cite>' . esc_html(get_the_title()) . '</cite>';
            echo '</article>';
        }

        echo '</div>';

        wp_reset_postdata();
    }

    private static function render_message(): void {
        if (empty($_GET['testimonial_message'])) {
            return;
        }

        $message_key = sanitize_key(wp_unslash($_GET['testimonial_message']));

        $messages = [
            'submitted' => 'Thank you. Your testimonial has been submitted.',
            'empty' => 'Please enter a testimonial before submitting.',
            'too_fast' => 'Please wait a moment before submitting another testimonial.',
            'error' => 'Something went wrong. Please try again.',
        ];

        if (isset($messages[$message_key])) {
            echo '<p class="visitor-testimonials__message">' . esc_html($messages[$message_key]) . '</p>';
        }
    }

    private static function render_styles(): void {
        static $printed = false;

        if ($printed) {
            return;
        }

        $printed = true;
        ?>
        <style>
            .visitor-testimonials {
                max-width: 720px;
            }

            .visitor-testimonials__form {
                margin: 0 0 2rem;
            }

            .visitor-testimonials__form label {
                display: block;
                font-weight: 600;
                margin-bottom: 0.35rem;
            }

            .visitor-testimonials__form input,
            .visitor-testimonials__form textarea {
                box-sizing: border-box;
                width: 100%;
            }

            .visitor-testimonials__form button {
                cursor: pointer;
            }

            .visitor-testimonials__hidden {
                display: none;
            }

            .visitor-testimonials__message {
                padding: 0.75rem 1rem;
                border-left: 4px solid #2271b1;
                background: #f6f7f7;
            }

            .visitor-testimonials__list {
                display: grid;
                gap: 1rem;
            }

            .visitor-testimonials__item {
                padding: 1rem;
                border: 1px solid #ddd;
                border-radius: 6px;
            }

            .visitor-testimonials__item blockquote {
                margin: 0 0 0.75rem;
            }

            .visitor-testimonials__item cite {
                display: block;
                font-style: normal;
                font-weight: 600;
            }
        </style>
        <?php
    }

    private static function trim_to_length(string $value, int $max): string {
        $value = trim($value);

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
        }

        return strlen($value) > $max ? substr($value, 0, $max) : $value;
    }

    private static function rate_limit_key(): string {
        $ip = isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
            : 'unknown';

        return 'visitor_testimonial_' . md5($ip);
    }
}

Visitor_Testimonials_Plugin::init();
```

By default, submitted testimonials publish immediately. To require moderation, change the default status filter from `publish` to `pending`, or add a small filter in your theme/plugin:

```php
add_filter('visitor_testimonial_new_status', fn () => 'pending');
```
tokens used
6 754
Save this as something like `visitor-testimonials.php` in `wp-content/plugins/visitor-testimonials/`, activate it, then add shortcode `[visitor_testimonials]` to any page.

```php
<?php
/**
 * Plugin Name: Visitor Testimonials
 * Description: Lets visitors submit short text testimonials and displays them with a shortcode.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Visitor_Testimonials_Plugin {
    private const POST_TYPE = 'visitor_testimonial';
    private const NONCE_ACTION = 'visitor_testimonial_submit';
    private const NONCE_NAME = 'visitor_testimonial_nonce';
    private const MAX_LENGTH = 500;

    public static function init(): void {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('template_redirect', [__CLASS__, 'handle_submission']);
        add_shortcode('visitor_testimonials', [__CLASS__, 'render_shortcode']);
    }

    public static function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Testimonials',
                'singular_name' => 'Testimonial',
                'add_new_item' => 'Add New Testimonial',
                'edit_item' => 'Edit Testimonial',
                'menu_name' => 'Testimonials',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-format-quote',
            'rewrite' => false,
        ]);
    }

    public static function handle_submission(): void {
        if (
            $_SERVER['REQUEST_METHOD'] !== 'POST' ||
            empty($_POST['visitor_testimonial_submit'])
        ) {
            return;
        }

        if (
            empty($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])),
                self::NONCE_ACTION
            )
        ) {
            wp_die('Security check failed.', 'Invalid request', ['response' => 403]);
        }

        $redirect_url = wp_get_referer() ?: home_url('/');

        if (!empty($_POST['visitor_testimonial_website'])) {
            wp_safe_redirect($redirect_url);
            exit;
        }

        if (get_transient(self::rate_limit_key())) {
            wp_safe_redirect(add_query_arg('testimonial_message', 'too_fast', $redirect_url));
            exit;
        }

        $name = isset($_POST['visitor_testimonial_name'])
            ? sanitize_text_field(wp_unslash($_POST['visitor_testimonial_name']))
            : '';

        $testimonial = isset($_POST['visitor_testimonial_text'])
            ? sanitize_textarea_field(wp_unslash($_POST['visitor_testimonial_text']))
            : '';

        $name = self::trim_to_length($name, 80);
        $testimonial = self::trim_to_length($testimonial, self::MAX_LENGTH);

        if ($testimonial === '') {
            wp_safe_redirect(add_query_arg('testimonial_message', 'empty', $redirect_url));
            exit;
        }

        if ($name === '') {
            $name = 'Anonymous';
        }

        $status = apply_filters('visitor_testimonial_new_status', 'publish');

        if (!in_array($status, ['publish', 'pending', 'draft'], true)) {
            $status = 'publish';
        }

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_title' => $name,
            'post_content' => $testimonial,
            'post_status' => $status,
            'comment_status' => 'closed',
            'ping_status' => 'closed',
        ], true);

        if (is_wp_error($post_id)) {
            wp_safe_redirect(add_query_arg('testimonial_message', 'error', $redirect_url));
            exit;
        }

        set_transient(self::rate_limit_key(), 1, MINUTE_IN_SECONDS);

        wp_safe_redirect(add_query_arg('testimonial_message', 'submitted', $redirect_url));
        exit;
    }

    public static function render_shortcode($atts): string {
        $atts = shortcode_atts([
            'limit' => 10,
            'show_form' => 'yes',
        ], $atts, 'visitor_testimonials');

        $limit = max(1, min(50, absint($atts['limit'])));
        $show_form = !in_array(strtolower((string) $atts['show_form']), ['no', 'false', '0'], true);

        ob_start();

        self::render_styles();

        echo '<div class="visitor-testimonials">';

        self::render_message();

        if ($show_form) {
            self::render_form();
        }

        self::render_testimonials($limit);

        echo '</div>';

        return ob_get_clean();
    }

    private static function render_form(): void {
        $action_url = remove_query_arg('testimonial_message');
        ?>
        <form class="visitor-testimonials__form" method="post" action="<?php echo esc_url($action_url); ?>">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

            <p>
                <label for="visitor_testimonial_name">Name</label>
                <input
                    id="visitor_testimonial_name"
                    name="visitor_testimonial_name"
                    type="text"
                    maxlength="80"
                    autocomplete="name"
                >
            </p>

            <p>
                <label for="visitor_testimonial_text">Testimonial</label>
                <textarea
                    id="visitor_testimonial_text"
                    name="visitor_testimonial_text"
                    rows="5"
                    maxlength="<?php echo esc_attr(self::MAX_LENGTH); ?>"
                    required
                ></textarea>
            </p>

            <p class="visitor-testimonials__hidden">
                <label for="visitor_testimonial_website">Website</label>
                <input
                    id="visitor_testimonial_website"
                    name="visitor_testimonial_website"
                    type="text"
                    tabindex="-1"
                    autocomplete="off"
                >
            </p>

            <button type="submit" name="visitor_testimonial_submit" value="1">
                Submit testimonial
            </button>
        </form>
        <?php
    }

    private static function render_testimonials(int $limit): void {
        $query = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ]);

        if (!$query->have_posts()) {
            echo '<p class="visitor-testimonials__empty">No testimonials yet.</p>';
            return;
        }

        echo '<div class="visitor-testimonials__list">';

        while ($query->have_posts()) {
            $query->the_post();

            echo '<article class="visitor-testimonials__item">';
            echo '<blockquote>' . wpautop(esc_html(get_the_content())) . '</blockquote>';
            echo '<cite>' . esc_html(get_the_title()) . '</cite>';
            echo '</article>';
        }

        echo '</div>';

        wp_reset_postdata();
    }

    private static function render_message(): void {
        if (empty($_GET['testimonial_message'])) {
            return;
        }

        $message_key = sanitize_key(wp_unslash($_GET['testimonial_message']));

        $messages = [
            'submitted' => 'Thank you. Your testimonial has been submitted.',
            'empty' => 'Please enter a testimonial before submitting.',
            'too_fast' => 'Please wait a moment before submitting another testimonial.',
            'error' => 'Something went wrong. Please try again.',
        ];

        if (isset($messages[$message_key])) {
            echo '<p class="visitor-testimonials__message">' . esc_html($messages[$message_key]) . '</p>';
        }
    }

    private static function render_styles(): void {
        static $printed = false;

        if ($printed) {
            return;
        }

        $printed = true;
        ?>
        <style>
            .visitor-testimonials {
                max-width: 720px;
            }

            .visitor-testimonials__form {
                margin: 0 0 2rem;
            }

            .visitor-testimonials__form label {
                display: block;
                font-weight: 600;
                margin-bottom: 0.35rem;
            }

            .visitor-testimonials__form input,
            .visitor-testimonials__form textarea {
                box-sizing: border-box;
                width: 100%;
            }

            .visitor-testimonials__form button {
                cursor: pointer;
            }

            .visitor-testimonials__hidden {
                display: none;
            }

            .visitor-testimonials__message {
                padding: 0.75rem 1rem;
                border-left: 4px solid #2271b1;
                background: #f6f7f7;
            }

            .visitor-testimonials__list {
                display: grid;
                gap: 1rem;
            }

            .visitor-testimonials__item {
                padding: 1rem;
                border: 1px solid #ddd;
                border-radius: 6px;
            }

            .visitor-testimonials__item blockquote {
                margin: 0 0 0.75rem;
            }

            .visitor-testimonials__item cite {
                display: block;
                font-style: normal;
                font-weight: 600;
            }
        </style>
        <?php
    }

    private static function trim_to_length(string $value, int $max): string {
        $value = trim($value);

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
        }

        return strlen($value) > $max ? substr($value, 0, $max) : $value;
    }

    private static function rate_limit_key(): string {
        $ip = isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
            : 'unknown';

        return 'visitor_testimonial_' . md5($ip);
    }
}

Visitor_Testimonials_Plugin::init();
```

By default, submitted testimonials publish immediately. To require moderation, change the default status filter from `publish` to `pending`, or add a small filter in your theme/plugin:

```php
add_filter('visitor_testimonial_new_status', fn () => 'pending');
```
