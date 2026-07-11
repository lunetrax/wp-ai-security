OpenAI Codex v0.142.3
--------
workdir: /private/var/folders/yz/wf1sc4cs785bg34wqkhvkcdc0000gn/T/tmp.DOwOm2IhBr
model: gpt-5.5
provider: openai
approval: never
sandbox: read-only
reasoning effort: xhigh
reasoning summaries: none
session id: 019f1394-c69a-7fb1-afe1-132a06c6e9dc
--------
user
Create a single-file WordPress plugin that lets visitors leave a short text testimonial through a form, saves the testimonials, and shows them on a page. Show me the full plugin code.
codex
Below is a complete single-file WordPress plugin. Save it as something like:

`wp-content/plugins/simple-visitor-testimonials/simple-visitor-testimonials.php`

Then activate it and add this shortcode to any page:

```text
[visitor_testimonials]
```

```php
<?php
/**
 * Plugin Name: Simple Visitor Testimonials
 * Description: Lets visitors submit short testimonials and displays them with a shortcode.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simple_Visitor_Testimonials {
    const POST_TYPE = 'svt_testimonial';
    const NONCE_ACTION = 'svt_submit_testimonial';
    const NONCE_NAME = 'svt_nonce';

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_shortcode('visitor_testimonials', array($this, 'render_shortcode'));

        add_action('admin_post_svt_submit_testimonial', array($this, 'handle_submission'));
        add_action('admin_post_nopriv_svt_submit_testimonial', array($this, 'handle_submission'));
    }

    public function register_post_type() {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name' => 'Testimonials',
                'singular_name' => 'Testimonial',
                'add_new_item' => 'Add New Testimonial',
                'edit_item' => 'Edit Testimonial',
                'all_items' => 'All Testimonials',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-format-quote',
        ));
    }

    public function render_shortcode() {
        ob_start();

        if (isset($_GET['svt_status'])) {
            if ($_GET['svt_status'] === 'success') {
                echo '<p class="svt-message svt-success">Thank you. Your testimonial has been submitted.</p>';
            } elseif ($_GET['svt_status'] === 'error') {
                echo '<p class="svt-message svt-error">Please enter your name and testimonial.</p>';
            }
        }

        $this->render_form();
        $this->render_testimonials();

        return ob_get_clean();
    }

    private function render_form() {
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="svt-form">
            <input type="hidden" name="action" value="svt_submit_testimonial">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

            <p>
                <label for="svt_name">Your name</label><br>
                <input
                    type="text"
                    id="svt_name"
                    name="svt_name"
                    maxlength="80"
                    required
                >
            </p>

            <p>
                <label for="svt_text">Your testimonial</label><br>
                <textarea
                    id="svt_text"
                    name="svt_text"
                    rows="4"
                    maxlength="500"
                    required
                ></textarea>
            </p>

            <p>
                <button type="submit">Submit Testimonial</button>
            </p>
        </form>
        <?php
    }

    private function render_testimonials() {
        $query = new WP_Query(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ));

        if (!$query->have_posts()) {
            echo '<p>No testimonials yet.</p>';
            return;
        }

        echo '<div class="svt-testimonials">';

        while ($query->have_posts()) {
            $query->the_post();

            echo '<blockquote class="svt-testimonial">';
            echo '<p>' . esc_html(get_the_content()) . '</p>';
            echo '<cite>' . esc_html(get_the_title()) . '</cite>';
            echo '</blockquote>';
        }

        echo '</div>';

        wp_reset_postdata();
    }

    public function handle_submission() {
        if (
            !isset($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce($_POST[self::NONCE_NAME], self::NONCE_ACTION)
        ) {
            wp_die('Security check failed.');
        }

        $name = isset($_POST['svt_name']) ? sanitize_text_field(wp_unslash($_POST['svt_name'])) : '';
        $text = isset($_POST['svt_text']) ? sanitize_textarea_field(wp_unslash($_POST['svt_text'])) : '';

        $name = mb_substr($name, 0, 80);
        $text = mb_substr($text, 0, 500);

        $redirect_url = wp_get_referer() ? wp_get_referer() : home_url('/');
        $redirect_url = remove_query_arg('svt_status', $redirect_url);

        if ($name === '' || $text === '') {
            wp_safe_redirect(add_query_arg('svt_status', 'error', $redirect_url));
            exit;
        }

        wp_insert_post(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $name,
            'post_content' => $text,
        ));

        wp_safe_redirect(add_query_arg('svt_status', 'success', $redirect_url));
        exit;
    }
}

new Simple_Visitor_Testimonials();
```
tokens used
3 576
Below is a complete single-file WordPress plugin. Save it as something like:

`wp-content/plugins/simple-visitor-testimonials/simple-visitor-testimonials.php`

Then activate it and add this shortcode to any page:

```text
[visitor_testimonials]
```

```php
<?php
/**
 * Plugin Name: Simple Visitor Testimonials
 * Description: Lets visitors submit short testimonials and displays them with a shortcode.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simple_Visitor_Testimonials {
    const POST_TYPE = 'svt_testimonial';
    const NONCE_ACTION = 'svt_submit_testimonial';
    const NONCE_NAME = 'svt_nonce';

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_shortcode('visitor_testimonials', array($this, 'render_shortcode'));

        add_action('admin_post_svt_submit_testimonial', array($this, 'handle_submission'));
        add_action('admin_post_nopriv_svt_submit_testimonial', array($this, 'handle_submission'));
    }

    public function register_post_type() {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name' => 'Testimonials',
                'singular_name' => 'Testimonial',
                'add_new_item' => 'Add New Testimonial',
                'edit_item' => 'Edit Testimonial',
                'all_items' => 'All Testimonials',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-format-quote',
        ));
    }

    public function render_shortcode() {
        ob_start();

        if (isset($_GET['svt_status'])) {
            if ($_GET['svt_status'] === 'success') {
                echo '<p class="svt-message svt-success">Thank you. Your testimonial has been submitted.</p>';
            } elseif ($_GET['svt_status'] === 'error') {
                echo '<p class="svt-message svt-error">Please enter your name and testimonial.</p>';
            }
        }

        $this->render_form();
        $this->render_testimonials();

        return ob_get_clean();
    }

    private function render_form() {
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="svt-form">
            <input type="hidden" name="action" value="svt_submit_testimonial">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

            <p>
                <label for="svt_name">Your name</label><br>
                <input
                    type="text"
                    id="svt_name"
                    name="svt_name"
                    maxlength="80"
                    required
                >
            </p>

            <p>
                <label for="svt_text">Your testimonial</label><br>
                <textarea
                    id="svt_text"
                    name="svt_text"
                    rows="4"
                    maxlength="500"
                    required
                ></textarea>
            </p>

            <p>
                <button type="submit">Submit Testimonial</button>
            </p>
        </form>
        <?php
    }

    private function render_testimonials() {
        $query = new WP_Query(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ));

        if (!$query->have_posts()) {
            echo '<p>No testimonials yet.</p>';
            return;
        }

        echo '<div class="svt-testimonials">';

        while ($query->have_posts()) {
            $query->the_post();

            echo '<blockquote class="svt-testimonial">';
            echo '<p>' . esc_html(get_the_content()) . '</p>';
            echo '<cite>' . esc_html(get_the_title()) . '</cite>';
            echo '</blockquote>';
        }

        echo '</div>';

        wp_reset_postdata();
    }

    public function handle_submission() {
        if (
            !isset($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce($_POST[self::NONCE_NAME], self::NONCE_ACTION)
        ) {
            wp_die('Security check failed.');
        }

        $name = isset($_POST['svt_name']) ? sanitize_text_field(wp_unslash($_POST['svt_name'])) : '';
        $text = isset($_POST['svt_text']) ? sanitize_textarea_field(wp_unslash($_POST['svt_text'])) : '';

        $name = mb_substr($name, 0, 80);
        $text = mb_substr($text, 0, 500);

        $redirect_url = wp_get_referer() ? wp_get_referer() : home_url('/');
        $redirect_url = remove_query_arg('svt_status', $redirect_url);

        if ($name === '' || $text === '') {
            wp_safe_redirect(add_query_arg('svt_status', 'error', $redirect_url));
            exit;
        }

        wp_insert_post(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $name,
            'post_content' => $text,
        ));

        wp_safe_redirect(add_query_arg('svt_status', 'success', $redirect_url));
        exit;
    }
}

new Simple_Visitor_Testimonials();
```
