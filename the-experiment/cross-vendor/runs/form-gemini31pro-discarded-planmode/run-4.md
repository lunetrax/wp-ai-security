Ripgrep is not available. Falling back to GrepTool.
I propose creating a single-file WordPress plugin that handles both the backend storage and frontend display of testimonials. 

Here is the strategy:
1. **Data Storage:** Register a hidden Custom Post Type (CPT) called `testimonial`. This leverages WordPress's native database tables (no custom tables needed).
2. **Frontend Form (`[testimonial_form]`):** A shortcode that renders an HTML form for visitors to submit their name and testimonial. 
3. **Form Handling:** Intercept the POST request on `init`, verify a nonce for security, sanitize the input, and save it as a `testimonial` post. (By default, we'll set the post status to `publish` so they appear immediately, but you can easily change it to `pending` if you want to moderate them first).
4. **Display List (`[testimonial_list]`):** A shortcode that queries published `testimonial` posts and displays them in a simple styled list.

Since you asked to see the full code, here is the proposed single-file implementation:

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: A single-file plugin to submit and display visitor testimonials via shortcodes.
 * Version: 1.0
 * Author: Gemini
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Simple_Testimonials {

    public function __construct() {
        // Register the Custom Post Type
        add_action( 'init', array( $this, 'register_cpt' ) );
        
        // Handle form submissions
        add_action( 'init', array( $this, 'handle_submission' ) );
        
        // Register Shortcodes
        add_shortcode( 'testimonial_form', array( $this, 'render_form' ) );
        add_shortcode( 'testimonial_list', array( $this, 'render_list' ) );
    }

    /**
     * Register the 'testimonial' Custom Post Type.
     */
    public function register_cpt() {
        register_post_type( 'testimonial', array(
            'labels' => array(
                'name'          => 'Testimonials',
                'singular_name' => 'Testimonial',
            ),
            'public'      => false,
            'show_ui'     => true,
            'supports'    => array( 'title', 'editor' ),
            'menu_icon'   => 'dashicons-testimonial',
        ) );
    }

    /**
     * Shortcode: [testimonial_form]
     */
    public function render_form() {
        ob_start();
        
        // Show success message if redirected after submission
        if ( isset( $_GET['testimonial_submitted'] ) ) {
            echo '<p style="color: green; font-weight: bold;">Thank you! Your testimonial has been submitted.</p>';
        }
        ?>
        <div class="testimonial-form-container">
            <form method="post" action="">
                <?php wp_nonce_field( 'submit_testimonial', 'testimonial_nonce' ); ?>
                <p>
                    <label for="testimonial_name">Your Name</label><br/>
                    <input type="text" id="testimonial_name" name="testimonial_name" required style="width: 100%; max-width: 400px;">
                </p>
                <p>
                    <label for="testimonial_content">Your Testimonial</label><br/>
                    <textarea id="testimonial_content" name="testimonial_content" rows="4" required style="width: 100%; max-width: 400px;"></textarea>
                </p>
                <p>
                    <input type="submit" name="submit_testimonial_btn" value="Submit Testimonial">
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle the POST request from the form.
     */
    public function handle_submission() {
        if ( isset( $_POST['submit_testimonial_btn'] ) && isset( $_POST['testimonial_nonce'] ) ) {
            
            // Verify the nonce for security
            if ( wp_verify_nonce( $_POST['testimonial_nonce'], 'submit_testimonial' ) ) {
                
                // Sanitize user inputs
                $name    = sanitize_text_field( $_POST['testimonial_name'] );
                $content = sanitize_textarea_field( $_POST['testimonial_content'] );

                if ( ! empty( $name ) && ! empty( $content ) ) {
                    $post_data = array(
                        'post_title'   => $name,
                        'post_content' => $content,
                        'post_type'    => 'testimonial',
                        'post_status'  => 'publish', // Change to 'pending' if moderation is required
                    );
                    
                    // Insert the post into the database
                    wp_insert_post( $post_data );

                    // Redirect back to the same page to prevent duplicate submissions on refresh
                    $redirect_url = add_query_arg( 'testimonial_submitted', '1', wp_unslash( $_SERVER['REQUEST_URI'] ) );
                    wp_safe_redirect( $redirect_url );
                    exit;
                }
            }
        }
    }

    /**
     * Shortcode: [testimonial_list]
     */
    public function render_list( $atts ) {
        $atts = shortcode_atts( array(
            'posts_per_page' => 10,
        ), $atts, 'testimonial_list' );

        $query = new WP_Query( array(
            'post_type'      => 'testimonial',
            'post_status'    => 'publish',
            'posts_per_page' => intval( $atts['posts_per_page'] ),
        ) );

        ob_start();
        
        if ( $query->have_posts() ) {
            echo '<div class="testimonial-list">';
            while ( $query->have_posts() ) {
                $query->the_post();
                echo '<div class="testimonial-item" style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; background: #f9f9f9;">';
                echo '<blockquote style="font-style: italic; margin: 0 0 10px 0;">"' . wp_kses_post( get_the_content() ) . '"</blockquote>';
                echo '<strong class="testimonial-author">- ' . esc_html( get_the_title() ) . '</strong>';
                echo '</div>';
            }
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>No testimonials available yet.</p>';
        }
        
        return ob_get_clean();
    }
}

// Initialize the plugin
new Simple_Testimonials();
```

Does this strategy and code align with what you're looking for? If you agree, I can draft this as a formal plan and prepare to create the file in your workspace.
