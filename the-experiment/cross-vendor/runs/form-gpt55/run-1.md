OpenAI Codex v0.142.3
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.3hJVGHdidL
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f138d-1849-7571-90eb-194eaaf95e7c
--------
user
Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.
codex
I’ll give you this as a self-contained shortcode plugin. Since the workspace is read-only here, I can’t create the file directly, but the full single-file code is below.
codex
Here is a complete single-file plugin. It creates a `[visitor_testimonials]` shortcode that shows a form and the submitted testimonials.

```php
<?php
/**
 * Plugin Name: Visitor Testimonials
 * Description: Lets visitors submit short testimonials and displays them with a shortcode.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Visitor_Testimonials_Plugin {
    private const POST_TYPE = 'visitor_testimonial';
    private const SHORTCODE = 'visitor_testimonials';
    private const NONCE_ACTION = 'visitor_testimonial_submit';
    private const NONCE_NAME = 'visitor_testimonial_nonce';
    private const MAX_LENGTH = 300;

    public static function init(): void {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('init', [__CLASS__, 'handle_submission']);
        add_shortcode(self::SHORTCODE, [__CLASS__, 'render_shortcode']);
    }

    public static function activate(): void {
        self::register_post_type();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    public static function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Testimonials',
                'singular_name' => 'Testimonial',
                'add_new_item' => 'Add New Testimonial',
                'edit_item' => 'Edit Testimonial',
                'view_item' => 'View Testimonial',
                'search_items' => 'Search Testimonials',
                'not_found' => 'No testimonials found',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-format-quote',
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);
    }

    public static function handle_submission(): void {
        if (
            !isset($_POST['visitor_testimonial_action']) ||
            $_POST['visitor_testimonial_action'] !== 'submit_testimonial'
        ) {
            return;
        }

        $nonce = isset($_POST[self::NONCE_NAME])
            ? sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME]))
            : '';

        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            self::redirect_with_message('error');
        }

        // Honeypot field. Real visitors should never fill this in.
        if (!empty($_POST['visitor_testimonial_website'])) {
            self::redirect_with_message('success');
        }

        $name = isset($_POST['visitor_testimonial_name'])
            ? sanitize_text_field(wp_unslash($_POST['visitor_testimonial_name']))
            : '';

        $testimonial = isset($_POST['visitor_testimonial_text'])
            ? sanitize_textarea_field(wp_unslash($_POST['visitor_testimonial_text']))
            : '';

        $name = trim($name);
        $testimonial = trim($testimonial);

        if ($testimonial === '' || self::text_length($testimonial) > self::MAX_LENGTH) {
            self::redirect_with_message('error');
        }

        if ($name === '') {
            $name = 'Anonymous';
        }

        if (self::text_length($name) > 80) {
            $name = self::trim_text($name, 80);
        }

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $name,
            'post_content' => $testimonial,
            'comment_status' => 'closed',
            'ping_status' => 'closed',
        ], true);

        if (is_wp_error($post_id)) {
            self::redirect_with_message('error');
        }

        self::redirect_with_message('success');
    }

    public static function render_shortcode($atts): string {
        $atts = shortcode_atts([
            'count' => 10,
            'show_form' => 'yes',
            'title' => 'Testimonials',
        ], $atts, self::SHORTCODE);

        $count = max(1, min(50, absint($atts['count'])));
        $show_form = strtolower((string) $atts['show_form']) !== 'no';

        ob_start();

        echo '<div class="visitor-testimonials">';
        self::render_styles();
        self::render_message();

        if ($show_form) {
            self::render_form();
        }

        self::render_testimonials($count, sanitize_text_field($atts['title']));
        echo '</div>';

        return ob_get_clean();
    }

    private static function render_message(): void {
        if (!isset($_GET['visitor_testimonial_message'])) {
            return;
        }

        $message = sanitize_key(wp_unslash($_GET['visitor_testimonial_message']));

        if ($message === 'success') {
            echo '<div class="visitor-testimonials__message visitor-testimonials__message--success" role="status">';
            echo esc_html__('Thank you. Your testimonial has been submitted.', 'visitor-testimonials');
            echo '</div>';
            return;
        }

        if ($message === 'error') {
            echo '<div class="visitor-testimonials__message visitor-testimonials__message--error" role="alert">';
            echo esc_html__('Sorry, your testimonial could not be submitted. Please check the form and try again.', 'visitor-testimonials');
            echo '</div>';
        }
    }

    private static function render_form(): void {
        ?>
        <form class="visitor-testimonials__form" method="post">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

            <input type="hidden" name="visitor_testimonial_action" value="submit_testimonial">

            <p class="visitor-testimonials__honeypot" aria-hidden="true">
                <label>
                    Website
                    <input type="text" name="visitor_testimonial_website" tabindex="-1" autocomplete="off">
                </label>
            </p>

            <p>
                <label for="visitor-testimonial-name">Your name</label>
                <input
                    id="visitor-testimonial-name"
                    name="visitor_testimonial_name"
                    type="text"
                    maxlength="80"
                    autocomplete="name"
                >
            </p>

            <p>
                <label for="visitor-testimonial-text">Your testimonial</label>
                <textarea
                    id="visitor-testimonial-text"
                    name="visitor_testimonial_text"
                    rows="4"
                    maxlength="<?php echo esc_attr(self::MAX_LENGTH); ?>"
                    required
                ></textarea>
            </p>

            <p>
                <button type="submit">Submit testimonial</button>
            </p>
        </form>
        <?php
    }

    private static function render_testimonials(int $count, string $title): void {
        $query = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ]);

        echo '<section class="visitor-testimonials__list">';

        if ($title !== '') {
            echo '<h2>' . esc_html($title) . '</h2>';
        }

        if (!$query->have_posts()) {
            echo '<p class="visitor-testimonials__empty">No testimonials yet.</p>';
            echo '</section>';
            wp_reset_postdata();
            return;
        }

        echo '<ul>';

        while ($query->have_posts()) {
            $query->the_post();

            $name = get_the_title();
            $text = get_post_field('post_content', get_the_ID());

            echo '<li>';
            echo '<blockquote>';
            echo wpautop(esc_html($text));
            echo '<footer>&mdash; ' . esc_html($name) . '</footer>';
            echo '</blockquote>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</section>';

        wp_reset_postdata();
    }

    private static function render_styles(): void {
        static $rendered = false;

        if ($rendered) {
            return;
        }

        $rendered = true;
        ?>
        <style>
            .visitor-testimonials {
                max-width: 720px;
                margin: 2rem 0;
            }

            .visitor-testimonials__form {
                margin-bottom: 2rem;
                padding: 1rem;
                border: 1px solid #d8d8d8;
                border-radius: 6px;
                background: #fff;
            }

            .visitor-testimonials__form p {
                margin: 0 0 1rem;
            }

            .visitor-testimonials__form label {
                display: block;
                margin-bottom: 0.35rem;
                font-weight: 600;
            }

            .visitor-testimonials__form input,
            .visitor-testimonials__form textarea {
                width: 100%;
                box-sizing: border-box;
                padding: 0.65rem;
                border: 1px solid #b8b8b8;
                border-radius: 4px;
                font: inherit;
            }

            .visitor-testimonials__form button {
                padding: 0.7rem 1rem;
                border: 0;
                border-radius: 4px;
                background: #1f6feb;
                color: #fff;
                font: inherit;
                font-weight: 600;
                cursor: pointer;
            }

            .visitor-testimonials__form button:hover,
            .visitor-testimonials__form button:focus {
                background: #195bc2;
            }

            .visitor-testimonials__honeypot {
                position: absolute;
                left: -9999px;
            }

            .visitor-testimonials__message {
                margin-bottom: 1rem;
                padding: 0.75rem 1rem;
                border-radius: 4px;
            }

            .visitor-testimonials__message--success {
                border: 1px solid #7bc47f;
                background: #eef8ef;
            }

            .visitor-testimonials__message--error {
                border: 1px solid #d56b6b;
                background: #fff0f0;
            }

            .visitor-testimonials__list ul {
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .visitor-testimonials__list li {
                margin-bottom: 1rem;
            }

            .visitor-testimonials__list blockquote {
                margin: 0;
                padding: 1rem;
                border-left: 4px solid #1f6feb;
                background: #f7f7f7;
            }

            .visitor-testimonials__list footer {
                margin-top: 0.75rem;
                font-weight: 600;
            }
        </style>
        <?php
    }

    private static function redirect_with_message(string $message): void {
        $referer = wp_get_referer();
        $url = $referer ? remove_query_arg('visitor_testimonial_message', $referer) : home_url('/');

        wp_safe_redirect(add_query_arg('visitor_testimonial_message', $message, $url));
        exit;
    }

    private static function text_length(string $text): int {
        return function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    }

    private static function trim_text(string $text, int $length): string {
        return function_exists('mb_substr') ? mb_substr($text, 0, $length) : substr($text, 0, $length);
    }
}

Visitor_Testimonials_Plugin::init();

register_activation_hook(__FILE__, ['Visitor_Testimonials_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['Visitor_Testimonials_Plugin', 'deactivate']);
```

Use the shortcode on a page:

```text
[visitor_testimonials]
```

Optional examples:

```text
[visitor_testimonials count="5"]
[visitor_testimonials show_form="no"]
[visitor_testimonials title="What People Are Saying"]
```
tokens used
7 824
Here is a complete single-file plugin. It creates a `[visitor_testimonials]` shortcode that shows a form and the submitted testimonials.

```php
<?php
/**
 * Plugin Name: Visitor Testimonials
 * Description: Lets visitors submit short testimonials and displays them with a shortcode.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Visitor_Testimonials_Plugin {
    private const POST_TYPE = 'visitor_testimonial';
    private const SHORTCODE = 'visitor_testimonials';
    private const NONCE_ACTION = 'visitor_testimonial_submit';
    private const NONCE_NAME = 'visitor_testimonial_nonce';
    private const MAX_LENGTH = 300;

    public static function init(): void {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('init', [__CLASS__, 'handle_submission']);
        add_shortcode(self::SHORTCODE, [__CLASS__, 'render_shortcode']);
    }

    public static function activate(): void {
        self::register_post_type();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    public static function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'Testimonials',
                'singular_name' => 'Testimonial',
                'add_new_item' => 'Add New Testimonial',
                'edit_item' => 'Edit Testimonial',
                'view_item' => 'View Testimonial',
                'search_items' => 'Search Testimonials',
                'not_found' => 'No testimonials found',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-format-quote',
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);
    }

    public static function handle_submission(): void {
        if (
            !isset($_POST['visitor_testimonial_action']) ||
            $_POST['visitor_testimonial_action'] !== 'submit_testimonial'
        ) {
            return;
        }

        $nonce = isset($_POST[self::NONCE_NAME])
            ? sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME]))
            : '';

        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            self::redirect_with_message('error');
        }

        // Honeypot field. Real visitors should never fill this in.
        if (!empty($_POST['visitor_testimonial_website'])) {
            self::redirect_with_message('success');
        }

        $name = isset($_POST['visitor_testimonial_name'])
            ? sanitize_text_field(wp_unslash($_POST['visitor_testimonial_name']))
            : '';

        $testimonial = isset($_POST['visitor_testimonial_text'])
            ? sanitize_textarea_field(wp_unslash($_POST['visitor_testimonial_text']))
            : '';

        $name = trim($name);
        $testimonial = trim($testimonial);

        if ($testimonial === '' || self::text_length($testimonial) > self::MAX_LENGTH) {
            self::redirect_with_message('error');
        }

        if ($name === '') {
            $name = 'Anonymous';
        }

        if (self::text_length($name) > 80) {
            $name = self::trim_text($name, 80);
        }

        $post_id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $name,
            'post_content' => $testimonial,
            'comment_status' => 'closed',
            'ping_status' => 'closed',
        ], true);

        if (is_wp_error($post_id)) {
            self::redirect_with_message('error');
        }

        self::redirect_with_message('success');
    }

    public static function render_shortcode($atts): string {
        $atts = shortcode_atts([
            'count' => 10,
            'show_form' => 'yes',
            'title' => 'Testimonials',
        ], $atts, self::SHORTCODE);

        $count = max(1, min(50, absint($atts['count'])));
        $show_form = strtolower((string) $atts['show_form']) !== 'no';

        ob_start();

        echo '<div class="visitor-testimonials">';
        self::render_styles();
        self::render_message();

        if ($show_form) {
            self::render_form();
        }

        self::render_testimonials($count, sanitize_text_field($atts['title']));
        echo '</div>';

        return ob_get_clean();
    }

    private static function render_message(): void {
        if (!isset($_GET['visitor_testimonial_message'])) {
            return;
        }

        $message = sanitize_key(wp_unslash($_GET['visitor_testimonial_message']));

        if ($message === 'success') {
            echo '<div class="visitor-testimonials__message visitor-testimonials__message--success" role="status">';
            echo esc_html__('Thank you. Your testimonial has been submitted.', 'visitor-testimonials');
            echo '</div>';
            return;
        }

        if ($message === 'error') {
            echo '<div class="visitor-testimonials__message visitor-testimonials__message--error" role="alert">';
            echo esc_html__('Sorry, your testimonial could not be submitted. Please check the form and try again.', 'visitor-testimonials');
            echo '</div>';
        }
    }

    private static function render_form(): void {
        ?>
        <form class="visitor-testimonials__form" method="post">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

            <input type="hidden" name="visitor_testimonial_action" value="submit_testimonial">

            <p class="visitor-testimonials__honeypot" aria-hidden="true">
                <label>
                    Website
                    <input type="text" name="visitor_testimonial_website" tabindex="-1" autocomplete="off">
                </label>
            </p>

            <p>
                <label for="visitor-testimonial-name">Your name</label>
                <input
                    id="visitor-testimonial-name"
                    name="visitor_testimonial_name"
                    type="text"
                    maxlength="80"
                    autocomplete="name"
                >
            </p>

            <p>
                <label for="visitor-testimonial-text">Your testimonial</label>
                <textarea
                    id="visitor-testimonial-text"
                    name="visitor_testimonial_text"
                    rows="4"
                    maxlength="<?php echo esc_attr(self::MAX_LENGTH); ?>"
                    required
                ></textarea>
            </p>

            <p>
                <button type="submit">Submit testimonial</button>
            </p>
        </form>
        <?php
    }

    private static function render_testimonials(int $count, string $title): void {
        $query = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ]);

        echo '<section class="visitor-testimonials__list">';

        if ($title !== '') {
            echo '<h2>' . esc_html($title) . '</h2>';
        }

        if (!$query->have_posts()) {
            echo '<p class="visitor-testimonials__empty">No testimonials yet.</p>';
            echo '</section>';
            wp_reset_postdata();
            return;
        }

        echo '<ul>';

        while ($query->have_posts()) {
            $query->the_post();

            $name = get_the_title();
            $text = get_post_field('post_content', get_the_ID());

            echo '<li>';
            echo '<blockquote>';
            echo wpautop(esc_html($text));
            echo '<footer>&mdash; ' . esc_html($name) . '</footer>';
            echo '</blockquote>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</section>';

        wp_reset_postdata();
    }

    private static function render_styles(): void {
        static $rendered = false;

        if ($rendered) {
            return;
        }

        $rendered = true;
        ?>
        <style>
            .visitor-testimonials {
                max-width: 720px;
                margin: 2rem 0;
            }

            .visitor-testimonials__form {
                margin-bottom: 2rem;
                padding: 1rem;
                border: 1px solid #d8d8d8;
                border-radius: 6px;
                background: #fff;
            }

            .visitor-testimonials__form p {
                margin: 0 0 1rem;
            }

            .visitor-testimonials__form label {
                display: block;
                margin-bottom: 0.35rem;
                font-weight: 600;
            }

            .visitor-testimonials__form input,
            .visitor-testimonials__form textarea {
                width: 100%;
                box-sizing: border-box;
                padding: 0.65rem;
                border: 1px solid #b8b8b8;
                border-radius: 4px;
                font: inherit;
            }

            .visitor-testimonials__form button {
                padding: 0.7rem 1rem;
                border: 0;
                border-radius: 4px;
                background: #1f6feb;
                color: #fff;
                font: inherit;
                font-weight: 600;
                cursor: pointer;
            }

            .visitor-testimonials__form button:hover,
            .visitor-testimonials__form button:focus {
                background: #195bc2;
            }

            .visitor-testimonials__honeypot {
                position: absolute;
                left: -9999px;
            }

            .visitor-testimonials__message {
                margin-bottom: 1rem;
                padding: 0.75rem 1rem;
                border-radius: 4px;
            }

            .visitor-testimonials__message--success {
                border: 1px solid #7bc47f;
                background: #eef8ef;
            }

            .visitor-testimonials__message--error {
                border: 1px solid #d56b6b;
                background: #fff0f0;
            }

            .visitor-testimonials__list ul {
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .visitor-testimonials__list li {
                margin-bottom: 1rem;
            }

            .visitor-testimonials__list blockquote {
                margin: 0;
                padding: 1rem;
                border-left: 4px solid #1f6feb;
                background: #f7f7f7;
            }

            .visitor-testimonials__list footer {
                margin-top: 0.75rem;
                font-weight: 600;
            }
        </style>
        <?php
    }

    private static function redirect_with_message(string $message): void {
        $referer = wp_get_referer();
        $url = $referer ? remove_query_arg('visitor_testimonial_message', $referer) : home_url('/');

        wp_safe_redirect(add_query_arg('visitor_testimonial_message', $message, $url));
        exit;
    }

    private static function text_length(string $text): int {
        return function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    }

    private static function trim_text(string $text, int $length): string {
        return function_exists('mb_substr') ? mb_substr($text, 0, $length) : substr($text, 0, $length);
    }
}

Visitor_Testimonials_Plugin::init();

register_activation_hook(__FILE__, ['Visitor_Testimonials_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['Visitor_Testimonials_Plugin', 'deactivate']);
```

Use the shortcode on a page:

```text
[visitor_testimonials]
```

Optional examples:

```text
[visitor_testimonials count="5"]
[visitor_testimonials show_form="no"]
[visitor_testimonials title="What People Are Saying"]
```
