Ripgrep is not available. Falling back to GrepTool.
I have designed a single-file WordPress plugin to meet your requirements. 

### Proposed Strategy
1. **Data Storage**: Uses a Custom Post Type (`testimonial`) to leverage WordPress's native, secure database tables rather than creating custom ones.
2. **Shortcodes**: 
   - `[testimonial_form]` renders the submission form.
   - `[testimonial_list]` displays the submitted testimonials.
3. **Security**: Implements WordPress nonces to protect against CSRF attacks, and sanitizes/escapes all inputs and outputs using `sanitize_text_field`, `sanitize_textarea_field`, and `wp_kses_post`.
4. **Flow**: Submissions are published immediately and the user is safely redirected using Post/Redirect/Get to prevent duplicate submissions on refresh.

### The Full Plugin Code
Here is the complete code for `simple-testimonials.php`:

```php
<?php
/**
 * Plugin Name: Simple Text Testimonials
 * Description: Lets visitors leave a short text testimonial through a form, saves them, and shows them on a page.
 * Version: 1.0
 * Author: Gemini
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Simple_Testimonials {

    public function __construct() {
        add_action( 'init', [ $this, 'register_cpt' ] );
        add_shortcode( 'testimonial_form', [ $this, 'render_form' ] );
        add_shortcode( 'testimonial_list', [ $this, 'render_list' ] );
        add_action( 'wp_loaded', [ $this, 'handle_submission' ] );
    }

    // Register the custom post type for testimonials
    public function register_cpt() {
        $args = [
            'public'              => true,
            'label'               => 'Testimonials',
            'supports'            => [ 'title', 'editor' ],
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'show_in_rest'        => true,
        ];
        register_post_type( 'testimonial', $args );
    }

    // Process form submissions
    public function handle_submission() {
        if ( isset( $_POST['st_submit'] ) && isset( $_POST['st_nonce'] ) ) {
            if ( wp_verify_nonce( $_POST['st_nonce'], 'st_submit_testimonial' ) ) {
                
                $name    = sanitize_text_field( wp_unslash( $_POST['st_name'] ) );
                $content = sanitize_textarea_field( wp_unslash( $_POST['st_content'] ) );

                if ( ! empty( $name ) && ! empty( $content ) ) {
                    $post_data = [
                        'post_title'   => $name,
                        'post_content' => $content,
                        'post_type'    => 'testimonial',
                        'post_status'  => 'publish', // Change to 'pending' if you want moderation
                    ];
                    wp_insert_post( $post_data );
                    
                    // Redirect to prevent form resubmission
                    wp_safe_redirect( add_query_arg( 'st_success', '1', wp_get_referer() ) );
                    exit;
                }
            }
        }
    }

    // Render the form via [testimonial_form]
    public function render_form() {
        ob_start();
        
        if ( isset( $_GET['st_success'] ) && $_GET['st_success'] === '1' ) {
            echo '<p style="color: green; font-weight: bold;">Thank you! Your testimonial has been submitted.</p>';
        }
        ?>
        <form method="post" action="" class="st-testimonial-form">
            <?php wp_nonce_field( 'st_submit_testimonial', 'st_nonce' ); ?>
            <p>
                <label for="st_name">Your Name:</label><br>
                <input type="text" name="st_name" id="st_name" required style="width: 100%; max-width: 400px;">
            </p>
            <p>
                <label for="st_content">Testimonial:</label><br>
                <textarea name="st_content" id="st_content" rows="4" required style="width: 100%; max-width: 400px;"></textarea>
            </p>
            <p>
                <input type="submit" name="st_submit" value="Submit Testimonial">
            </p>
        </form>
        <?php
        
        return ob_get_clean();
    }

    // Render the testimonials via [testimonial_list]
    public function render_list() {
        $query = new WP_Query( [
            'post_type'      => 'testimonial',
            'posts_per_page' => -1, // Fetch all for simplicity
            'post_status'    => 'publish',
        ] );

        if ( ! $query->have_posts() ) {
            return '<p>No testimonials yet.</p>';
        }

        ob_start();
        echo '<div class="st-testimonials-list">';
        
        while ( $query->have_posts() ) {
            $query->the_post();
            echo '<div class="st-testimonial" style="border-bottom: 1px solid #ccc; margin-bottom: 15px; padding-bottom: 15px;">';
            echo '<blockquote>' . wp_kses_post( get_the_content() ) . '</blockquote>';
            echo '<strong>- ' . esc_html( get_the_title() ) . '</strong>';
            echo '</div>';
        }
        
        echo '</div>';
        wp_reset_postdata();

        return ob_get_clean();
    }
}

new Simple_Testimonials();
```

Does this implementation look good to you? If you agree, I will draft this into a final plan and request approval to write it into your workspace.
