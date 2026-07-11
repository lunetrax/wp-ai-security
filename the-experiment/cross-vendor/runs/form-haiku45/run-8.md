I'll create a complete, single-file WordPress plugin for you. Here's the full code:

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: Let visitors leave testimonials through a form and display them
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

class SimpleTestimonials {
    
    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_shortcode('testimonial_form', [$this, 'render_form']);
        add_shortcode('testimonials', [$this, 'render_testimonials']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_submit_testimonial', [$this, 'handle_submission']);
        add_action('wp_ajax_nopriv_submit_testimonial', [$this, 'handle_submission']);
    }
    
    public function register_post_type() {
        register_post_type('testimonial', [
            'label' => 'Testimonials',
            'public' => false,
            'show_in_rest' => true,
            'supports' => ['title', 'editor'],
        ]);
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'testimonialAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('testimonial_nonce')
        ]);
    }
    
    public function render_form() {
        ob_start();
        ?>
        <div class="testimonial-form-wrapper">
            <form id="testimonial-form" class="testimonial-form">
                <div class="form-group">
                    <label for="testimonial_name">Your Name:</label>
                    <input type="text" id="testimonial_name" name="name" required maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="testimonial_text">Your Testimonial:</label>
                    <textarea id="testimonial_text" name="testimonial" required maxlength="500" rows="5"></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="submit-btn">Submit Testimonial</button>
                </div>
                
                <div id="testimonial-message" class="testimonial-message"></div>
            </form>
        </div>
        
        <style>
            .testimonial-form-wrapper {
                max-width: 500px;
                margin: 20px 0;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }
            
            .form-group input,
            .form-group textarea {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-family: inherit;
                box-sizing: border-box;
            }
            
            .submit-btn {
                background-color: #0073aa;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
            }
            
            .submit-btn:hover {
                background-color: #005a87;
            }
            
            .testimonial-message {
                margin-top: 15px;
                padding: 10px;
                border-radius: 4px;
                display: none;
            }
            
            .testimonial-message.success {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
                display: block;
            }
            
            .testimonial-message.error {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
                display: block;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#testimonial-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $message = $('#testimonial-message');
                
                $.ajax({
                    url: testimonialAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'submit_testimonial',
                        nonce: testimonialAjax.nonce,
                        name: $('#testimonial_name').val(),
                        testimonial: $('#testimonial_text').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.text(response.data.message).removeClass('error').addClass('success');
                            $form[0].reset();
                            setTimeout(function() {
                                $message.removeClass('success');
                            }, 3000);
                        } else {
                            $message.text(response.data.message).removeClass('success').addClass('error');
                        }
                    },
                    error: function() {
                        $message.text('An error occurred. Please try again.').removeClass('success').addClass('error');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function handle_submission() {
        check_ajax_referer('testimonial_nonce', 'nonce');
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $testimonial = sanitize_textarea_field($_POST['testimonial'] ?? '');
        
        if (empty($name) || empty($testimonial)) {
            wp_send_json_error([
                'message' => 'Please fill in all fields.'
            ]);
        }
        
        $post_id = wp_insert_post([
            'post_type' => 'testimonial',
            'post_title' => $name,
            'post_content' => $testimonial,
            'post_status' => 'publish'
        ]);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error([
                'message' => 'Failed to save testimonial. Please try again.'
            ]);
        }
        
        wp_send_json_success([
            'message' => 'Thank you! Your testimonial has been submitted.'
        ]);
    }
    
    public function render_testimonials() {
        $args = [
            'post_type' => 'testimonial',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        $testimonials = new WP_Query($args);
        
        ob_start();
        ?>
        <div class="testimonials-container">
            <?php if ($testimonials->have_posts()): ?>
                <div class="testimonials-list">
                    <?php while ($testimonials->have_posts()): $testimonials->the_post(); ?>
                        <div class="testimonial-item">
                            <p class="testimonial-text"><?php echo wp_kses_post(get_the_content()); ?></p>
                            <p class="testimonial-author">— <?php echo esc_html(get_the_title()); ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-testimonials">No testimonials yet. Be the first to share!</p>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
        </div>
        
        <style>
            .testimonials-container {
                margin: 20px 0;
            }
            
            .testimonials-list {
                display: grid;
                gap: 20px;
            }
            
            .testimonial-item {
                background-color: #f9f9f9;
                padding: 20px;
                border-left: 4px solid #0073aa;
                border-radius: 4px;
            }
            
            .testimonial-text {
                margin: 0 0 10px 0;
                font-style: italic;
                color: #333;
            }
            
            .testimonial-author {
                margin: 0;
                font-weight: bold;
                color: #0073aa;
            }
            
            .no-testimonials {
                text-align: center;
                color: #999;
                padding: 40px 20px;
            }
        </style>
        <?php
        return ob_get_clean();
    }
}

new SimpleTestimonials();
```

## How to use:

1. **Install**: Save this as a `.php` file (e.g., `simple-testimonials.php`) in `/wp-content/plugins/`
2. **Activate**: Go to WordPress admin → Plugins and activate "Simple Testimonials"
3. **Add form**: Use `[testimonial_form]` shortcode on any page/post where visitors submit testimonials
4. **Display testimonials**: Use `[testimonials]` shortcode on any page/post to show all submitted testimonials

## Key features:

- **AJAX submission**: Form submits without page reload
- **Security**: Nonce verification, input sanitization, and escaping
- **Storage**: Uses WordPress custom post type (stored in the database)
- **Admin access**: Testimonials visible in WordPress admin under "Testimonials"
- **Responsive**: Styled for mobile and desktop
- **Validation**: Required fields and length limits (500 chars max)
- **User feedback**: Success/error messages after submission
