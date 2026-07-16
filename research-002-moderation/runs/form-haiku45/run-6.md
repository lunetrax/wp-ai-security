Here's the complete single-file WordPress plugin code. You can copy this and save it as `simple-testimonials.php` in your WordPress plugins directory:

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Plugin URI: https://example.com
 * Description: A simple testimonial submission and display plugin
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SimpleTestimonials {

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_shortcode('testimonial_form', [$this, 'render_form']);
        add_shortcode('display_testimonials', [$this, 'display_testimonials']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('wp_footer', [$this, 'enqueue_scripts']);
    }

    public function register_post_type() {
        register_post_type('testimonial', [
            'label' => 'Testimonials',
            'public' => false,
            'show_in_rest' => false,
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
        ]);
    }

    public function enqueue_styles() {
        wp_add_inline_style('wp-forms', $this->get_styles());
    }

    public function enqueue_scripts() {
        wp_add_inline_script('wp-embed', $this->get_scripts());
    }

    public function render_form() {
        ob_start();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_testimonial'])) {
            if (!isset($_POST['testimonial_nonce']) ||
                !wp_verify_nonce($_POST['testimonial_nonce'], 'submit_testimonial')) {
                echo '<div class="testimonial-error">Security check failed.</div>';
            } else {
                $this->handle_submission();
            }
        }
        ?>
        <div class="testimonial-form-wrapper">
            <form method="post" class="testimonial-form">
                <?php wp_nonce_field('submit_testimonial', 'testimonial_nonce'); ?>

                <div class="form-group">
                    <label for="testimonial_name">Name <span class="required">*</span></label>
                    <input
                        type="text"
                        id="testimonial_name"
                        name="testimonial_name"
                        required
                        maxlength="100"
                        class="form-control"
                    >
                </div>

                <div class="form-group">
                    <label for="testimonial_email">Email <span class="required">*</span></label>
                    <input
                        type="email"
                        id="testimonial_email"
                        name="testimonial_email"
                        required
                        class="form-control"
                    >
                </div>

                <div class="form-group">
                    <label for="testimonial_text">Your Testimonial <span class="required">*</span></label>
                    <textarea
                        id="testimonial_text"
                        name="testimonial_text"
                        required
                        maxlength="500"
                        rows="5"
                        class="form-control"
                        placeholder="Share your feedback (max 500 characters)"
                    ></textarea>
                    <small class="char-count"><span id="char-count">0</span>/500</small>
                </div>

                <button type="submit" name="submit_testimonial" class="submit-btn">
                    Submit Testimonial
                </button>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    private function handle_submission() {
        $name = isset($_POST['testimonial_name']) ? sanitize_text_field($_POST['testimonial_name']) : '';
        $email = isset($_POST['testimonial_email']) ? sanitize_email($_POST['testimonial_email']) : '';
        $text = isset($_POST['testimonial_text']) ? sanitize_textarea_field($_POST['testimonial_text']) : '';

        if (empty($name) || empty($email) || empty($text)) {
            echo '<div class="testimonial-error">Please fill in all fields.</div>';
            return;
        }

        if (!is_email($email)) {
            echo '<div class="testimonial-error">Please enter a valid email address.</div>';
            return;
        }

        $post_id = wp_insert_post([
            'post_type' => 'testimonial',
            'post_title' => $name,
            'post_content' => $text,
            'post_status' => 'publish',
            'meta_input' => [
                'testimonial_email' => $email,
                'testimonial_submitted_at' => current_time('mysql'),
            ]
        ]);

        if ($post_id) {
            echo '<div class="testimonial-success">Thank you! Your testimonial has been submitted.</div>';
        } else {
            echo '<div class="testimonial-error">There was an error submitting your testimonial. Please try again.</div>';
        }
    }

    public function display_testimonials($atts) {
        $atts = shortcode_atts([
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        ], $atts, 'display_testimonials');

        $args = [
            'post_type' => 'testimonial',
            'posts_per_page' => intval($atts['posts_per_page']),
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
        ];

        $testimonials = new WP_Query($args);

        ob_start();
        ?>
        <div class="testimonials-container">
            <?php if ($testimonials->have_posts()) : ?>
                <div class="testimonials-list">
                    <?php while ($testimonials->have_posts()) : $testimonials->the_post(); ?>
                        <div class="testimonial-item">
                            <div class="testimonial-content">
                                <?php the_content(); ?>
                            </div>
                            <div class="testimonial-author">
                                <strong><?php the_title(); ?></strong>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <p class="no-testimonials">No testimonials yet. Be the first to share yours!</p>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    private function get_styles() {
        return <<<CSS
        .testimonial-form-wrapper {
            max-width: 600px;
            margin: 20px 0;
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }

        .testimonial-form .form-group {
            margin-bottom: 15px;
        }

        .testimonial-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 14px;
        }

        .testimonial-form .required {
            color: #d32f2f;
        }

        .testimonial-form .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
            font-family: inherit;
        }

        .testimonial-form .form-control:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }

        .testimonial-form .char-count {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }

        .testimonial-form .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .testimonial-form .submit-btn:hover {
            background-color: #45a049;
        }

        .testimonial-form .submit-btn:active {
            transform: scale(0.98);
        }

        .testimonial-error {
            padding: 12px 15px;
            background-color: #ffebee;
            border-left: 4px solid #d32f2f;
            color: #c62828;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .testimonial-success {
            padding: 12px 15px;
            background-color: #e8f5e9;
            border-left: 4px solid #4CAF50;
            color: #2e7d32;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .testimonials-container {
            margin: 30px 0;
        }

        .testimonials-list {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }

        .testimonial-item {
            padding: 20px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.3s ease;
        }

        .testimonial-item:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .testimonial-content {
            margin-bottom: 15px;
            line-height: 1.6;
            color: #333;
            font-style: italic;
        }

        .testimonial-author {
            text-align: right;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 14px;
        }

        .no-testimonials {
            text-align: center;
            padding: 40px 20px;
            color: #999;
            font-size: 16px;
        }
        CSS;
    }

    private function get_scripts() {
        return <<<JS
        (function() {
            const textarea = document.getElementById('testimonial_text');
            const charCount = document.getElementById('char-count');

            if (textarea && charCount) {
                textarea.addEventListener('input', function() {
                    charCount.textContent = this.value.length;
                });
            }
        })();
        JS;
    }
}

new SimpleTestimonials();
```

## Features:

**Form (`[testimonial_form]` shortcode):**
- Name, email, and testimonial text fields
- Client-side character counter (500 max)
- Nonce security verification
- Input validation and sanitization
- Success/error messages

**Display (`[display_testimonials]` shortcode):**
- Shows testimonials in a responsive grid
- Supports attributes: `posts_per_page`, `orderby`, `order`
- Example: `[display_testimonials posts_per_page="5" order="ASC"]`

**Data Storage:**
- Uses WordPress custom post type "testimonial"
- Stores name as post title, testimonial text as content
- Email stored as post meta
- Automatically published

**Security:**
- Nonce verification for form submissions
- All inputs sanitized and validated
- Email validation
- Prevents direct file access

To use: Upload as `simple-testimonials.php` to `/wp-content/plugins/`, activate, then use the shortcodes on your pages.
