OpenAI Codex v0.142.3
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.VFyyQ8lnme
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f1391-f590-7fb2-9d87-aaec92702a64
--------
user
Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.
codex
Save this as `wp-content/plugins/visitor-testimonials/visitor-testimonials.php`, activate it, then add shortcode `[visitor_testimonials]` to any page.

```php
<?php
/**
 * Plugin Name: Visitor Testimonials
 * Description: Lets visitors submit short testimonials and displays approved testimonials with a shortcode.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Visitor_Testimonials_Plugin {
    const POST_TYPE = 'visitor_testimonial';
    const NONCE_ACTION = 'visitor_testimonial_submit';
    const NONCE_NAME = 'visitor_testimonial_nonce';

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_shortcode('visitor_testimonials', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'register_styles'));
    }

    public function register_post_type() {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name' => 'Testimonials',
                'singular_name' => 'Testimonial',
                'add_new_item' => 'Add New Testimonial',
                'edit_item' => 'Edit Testimonial',
                'view_item' => 'View Testimonial',
                'search_items' => 'Search Testimonials',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-format-quote',
        ));
    }

    public function register_styles() {
        wp_register_style('visitor-testimonials-inline', false);
        wp_enqueue_style('visitor-testimonials-inline');

        wp_add_inline_style('visitor-testimonials-inline', '
            .visitor-testimonials {
                max-width: 720px;
                margin: 2rem 0;
            }
            .visitor-testimonials form {
                display: grid;
                gap: 0.75rem;
                margin-bottom: 2rem;
            }
            .visitor-testimonials label {
                font-weight: 600;
            }
            .visitor-testimonials input,
            .visitor-testimonials textarea {
                width: 100%;
                box-sizing: border-box;
            }
            .visitor-testimonials textarea {
                min-height: 110px;
            }
            .visitor-testimonials .notice {
                padding: 0.75rem 1rem;
                margin-bottom: 1rem;
                border-left: 4px solid #2271b1;
                background: #f6f7f7;
            }
            .visitor-testimonials .error {
                border-left-color: #d63638;
            }
            .visitor-testimonial-list {
                display: grid;
                gap: 1rem;
            }
            .visitor-testimonial {
                padding: 1rem;
                border: 1px solid #ddd;
                border-radius: 6px;
            }
            .visitor-testimonial blockquote {
                margin: 0;
            }
            .visitor-testimonial cite {
                display: block;
                margin-top: 0.75rem;
                font-style: normal;
                font-weight: 600;
            }
        ');
    }

    public function render_shortcode() {
        $message = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visitor_testimonial_submit'])) {
            $result = $this->handle_submission();

            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                $message = 'Thank you. Your testimonial has been submitted for review.';
            }
        }

        ob_start();
        ?>
        <div class="visitor-testimonials">
            <?php if ($message) : ?>
                <div class="notice"><?php echo esc_html($message); ?></div>
            <?php endif; ?>

            <?php if ($error) : ?>
                <div class="notice error"><?php echo esc_html($error); ?></div>
            <?php endif; ?>

            <?php echo $this->render_form(); ?>
            <?php echo $this->render_testimonials(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function handle_submission() {
        if (
            !isset($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)
        ) {
            return new WP_Error('invalid_nonce', 'Security check failed. Please try again.');
        }

        if (!empty($_POST['visitor_testimonial_website'])) {
            return new WP_Error('spam_detected', 'Submission could not be processed.');
        }

        $name = isset($_POST['visitor_testimonial_name'])
            ? sanitize_text_field(wp_unslash($_POST['visitor_testimonial_name']))
            : '';

        $text = isset($_POST['visitor_testimonial_text'])
            ? sanitize_textarea_field(wp_unslash($_POST['visitor_testimonial_text']))
            : '';

        $name = $this->limit_length(trim($name), 80);
        $text = $this->limit_length(trim($text), 500);

        if ($text === '') {
            return new WP_Error('missing_text', 'Please enter a testimonial.');
        }

        $post_id = wp_insert_post(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'pending',
            'post_title' => $name !== '' ? $name : 'Anonymous Testimonial',
            'post_content' => $text,
        ), true);

        if (is_wp_error($post_id)) {
            return new WP_Error('save_failed', 'Your testimonial could not be saved. Please try again.');
        }

        return $post_id;
    }

    private function render_form() {
        ob_start();
        ?>
        <form method="post">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

            <p style="display:none;">
                <label>
                    Website
                    <input type="text" name="visitor_testimonial_website" tabindex="-1" autocomplete="off">
                </label>
            </p>

            <p>
                <label for="visitor_testimonial_name">Name</label>
                <input
                    id="visitor_testimonial_name"
                    type="text"
                    name="visitor_testimonial_name"
                    maxlength="80"
                    autocomplete="name"
                >
            </p>

            <p>
                <label for="visitor_testimonial_text">Testimonial</label>
                <textarea
                    id="visitor_testimonial_text"
                    name="visitor_testimonial_text"
                    maxlength="500"
                    required
                ></textarea>
            </p>

            <p>
                <button type="submit" name="visitor_testimonial_submit" value="1">
                    Submit Testimonial
                </button>
            </p>
        </form>
        <?php
        return ob_get_clean();
    }

    private function render_testimonials() {
        $query = new WP_Query(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ));

        ob_start();

        if ($query->have_posts()) :
            ?>
            <div class="visitor-testimonial-list">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <article class="visitor-testimonial">
                        <blockquote>
                            <?php echo wpautop(esc_html(get_the_content())); ?>
                            <cite><?php echo esc_html(get_the_title()); ?></cite>
                        </blockquote>
                    </article>
                <?php endwhile; ?>
            </div>
            <?php
        else :
            ?>
            <p>No testimonials have been published yet.</p>
            <?php
        endif;

        wp_reset_postdata();

        return ob_get_clean();
    }

    private function limit_length($value, $max_length) {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max_length);
        }

        return substr($value, 0, $max_length);
    }
}

new Visitor_Testimonials_Plugin();
```

Submitted testimonials are saved as `Pending`. In the WordPress admin, go to **Testimonials**, review each one, and publish it to make it appear on the page.
tokens used
4 176
Save this as `wp-content/plugins/visitor-testimonials/visitor-testimonials.php`, activate it, then add shortcode `[visitor_testimonials]` to any page.

```php
<?php
/**
 * Plugin Name: Visitor Testimonials
 * Description: Lets visitors submit short testimonials and displays approved testimonials with a shortcode.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Visitor_Testimonials_Plugin {
    const POST_TYPE = 'visitor_testimonial';
    const NONCE_ACTION = 'visitor_testimonial_submit';
    const NONCE_NAME = 'visitor_testimonial_nonce';

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_shortcode('visitor_testimonials', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'register_styles'));
    }

    public function register_post_type() {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name' => 'Testimonials',
                'singular_name' => 'Testimonial',
                'add_new_item' => 'Add New Testimonial',
                'edit_item' => 'Edit Testimonial',
                'view_item' => 'View Testimonial',
                'search_items' => 'Search Testimonials',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-format-quote',
        ));
    }

    public function register_styles() {
        wp_register_style('visitor-testimonials-inline', false);
        wp_enqueue_style('visitor-testimonials-inline');

        wp_add_inline_style('visitor-testimonials-inline', '
            .visitor-testimonials {
                max-width: 720px;
                margin: 2rem 0;
            }
            .visitor-testimonials form {
                display: grid;
                gap: 0.75rem;
                margin-bottom: 2rem;
            }
            .visitor-testimonials label {
                font-weight: 600;
            }
            .visitor-testimonials input,
            .visitor-testimonials textarea {
                width: 100%;
                box-sizing: border-box;
            }
            .visitor-testimonials textarea {
                min-height: 110px;
            }
            .visitor-testimonials .notice {
                padding: 0.75rem 1rem;
                margin-bottom: 1rem;
                border-left: 4px solid #2271b1;
                background: #f6f7f7;
            }
            .visitor-testimonials .error {
                border-left-color: #d63638;
            }
            .visitor-testimonial-list {
                display: grid;
                gap: 1rem;
            }
            .visitor-testimonial {
                padding: 1rem;
                border: 1px solid #ddd;
                border-radius: 6px;
            }
            .visitor-testimonial blockquote {
                margin: 0;
            }
            .visitor-testimonial cite {
                display: block;
                margin-top: 0.75rem;
                font-style: normal;
                font-weight: 600;
            }
        ');
    }

    public function render_shortcode() {
        $message = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visitor_testimonial_submit'])) {
            $result = $this->handle_submission();

            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                $message = 'Thank you. Your testimonial has been submitted for review.';
            }
        }

        ob_start();
        ?>
        <div class="visitor-testimonials">
            <?php if ($message) : ?>
                <div class="notice"><?php echo esc_html($message); ?></div>
            <?php endif; ?>

            <?php if ($error) : ?>
                <div class="notice error"><?php echo esc_html($error); ?></div>
            <?php endif; ?>

            <?php echo $this->render_form(); ?>
            <?php echo $this->render_testimonials(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function handle_submission() {
        if (
            !isset($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)
        ) {
            return new WP_Error('invalid_nonce', 'Security check failed. Please try again.');
        }

        if (!empty($_POST['visitor_testimonial_website'])) {
            return new WP_Error('spam_detected', 'Submission could not be processed.');
        }

        $name = isset($_POST['visitor_testimonial_name'])
            ? sanitize_text_field(wp_unslash($_POST['visitor_testimonial_name']))
            : '';

        $text = isset($_POST['visitor_testimonial_text'])
            ? sanitize_textarea_field(wp_unslash($_POST['visitor_testimonial_text']))
            : '';

        $name = $this->limit_length(trim($name), 80);
        $text = $this->limit_length(trim($text), 500);

        if ($text === '') {
            return new WP_Error('missing_text', 'Please enter a testimonial.');
        }

        $post_id = wp_insert_post(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'pending',
            'post_title' => $name !== '' ? $name : 'Anonymous Testimonial',
            'post_content' => $text,
        ), true);

        if (is_wp_error($post_id)) {
            return new WP_Error('save_failed', 'Your testimonial could not be saved. Please try again.');
        }

        return $post_id;
    }

    private function render_form() {
        ob_start();
        ?>
        <form method="post">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

            <p style="display:none;">
                <label>
                    Website
                    <input type="text" name="visitor_testimonial_website" tabindex="-1" autocomplete="off">
                </label>
            </p>

            <p>
                <label for="visitor_testimonial_name">Name</label>
                <input
                    id="visitor_testimonial_name"
                    type="text"
                    name="visitor_testimonial_name"
                    maxlength="80"
                    autocomplete="name"
                >
            </p>

            <p>
                <label for="visitor_testimonial_text">Testimonial</label>
                <textarea
                    id="visitor_testimonial_text"
                    name="visitor_testimonial_text"
                    maxlength="500"
                    required
                ></textarea>
            </p>

            <p>
                <button type="submit" name="visitor_testimonial_submit" value="1">
                    Submit Testimonial
                </button>
            </p>
        </form>
        <?php
        return ob_get_clean();
    }

    private function render_testimonials() {
        $query = new WP_Query(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ));

        ob_start();

        if ($query->have_posts()) :
            ?>
            <div class="visitor-testimonial-list">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <article class="visitor-testimonial">
                        <blockquote>
                            <?php echo wpautop(esc_html(get_the_content())); ?>
                            <cite><?php echo esc_html(get_the_title()); ?></cite>
                        </blockquote>
                    </article>
                <?php endwhile; ?>
            </div>
            <?php
        else :
            ?>
            <p>No testimonials have been published yet.</p>
            <?php
        endif;

        wp_reset_postdata();

        return ob_get_clean();
    }

    private function limit_length($value, $max_length) {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max_length);
        }

        return substr($value, 0, $max_length);
    }
}

new Visitor_Testimonials_Plugin();
```

Submitted testimonials are saved as `Pending`. In the WordPress admin, go to **Testimonials**, review each one, and publish it to make it appear on the page.
