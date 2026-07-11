Ripgrep is not available. Falling back to GrepTool.
Here is a complete, single-file WordPress plugin that fulfills your request. 

### Strategy
This plugin uses WordPress's native features to ensure it is lightweight and secure:
1. **Custom Post Type (`testimonial`)**: Saves testimonials using WordPress's built-in database layer, making them manageable from the admin dashboard.
2. **Shortcodes**: 
   - `[testimonial_form]`: Renders a form for visitors to submit their name and testimonial.
   - `[testimonial_list]`: Displays the approved (published) testimonials.
3. **Security**: Implements WordPress nonces to prevent cross-site request forgery (CSRF) and sanitizes all input data before saving it to the database.

### The Plugin Code

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: A single-file plugin to submit and display testimonials using shortcodes.
 * Version: 1.0
 * Author: Gemini
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Simple_Testimonials_Plugin {

    public function __construct() {
        // Hook into WordPress
        add_action( 'init', array( $this, 'register_testimonial_cpt' ) );
        add_shortcode( 'testimonial_form', array( $this, 'render_form_shortcode' ) );
        add_shortcode( 'testimonial_list', array( $this, 'render_list_shortcode' ) );
        add_action( 'template_redirect', array( $this, 'handle_form_submission' ) );
    }

    /**
     * 1. Register a Custom Post Type for Testimonials
     */
    public function register_testimonial_cpt() {
        register_post_type( 'testimonial', array(
            'labels' => array(
                'name'          => __( 'Testimonials' ),
                'singular_name' => __( 'Testimonial' ),
                'add_new_item'  => __( 'Add New Testimonial' )
            ),
            'public'              => false, // Keep false so they don't have individual public URL pages
            'show_ui'             => true,  // Show in admin dashboard
            'supports'            => array( 'title', 'editor' ),
            'exclude_from_search' => true,
            'show_in_rest'        => false,
        ) );
    }

    /**
     * 2. Render the Submission Form [testimonial_form]
     */
    public function render_form_shortcode() {
        ob_start();
        ?>
        <div class="testimonial-form-container">
            <?php if ( isset( $_GET['testimonial_submitted'] ) && $_GET['testimonial_submitted'] === '1' ) : ?>
                <div style="background: #e6ffe6; border: 1px solid #00cc00; padding: 10px; margin-bottom: 15px;">
                    Thank you! Your testimonial has been submitted.
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php wp_nonce_field( 'submit_testimonial_nonce', 'testimonial_nonce' ); ?>
                <input type="hidden" name="action" value="submit_testimonial">
                
                <p>
                    <label for="testimonial_name"><strong>Your Name:</strong></label><br>
                    <input type="text" id="testimonial_name" name="testimonial_name" required style="width: 100%; max-width: 400px;">
                </p>
                <p>
                    <label for="testimonial_text"><strong>Your Testimonial:</strong></label><br>
                    <textarea id="testimonial_text" name="testimonial_text" rows="4" required style="width: 100%; max-width: 400px;"></textarea>
                </p>
                <p>
                    <button type="submit">Submit Testimonial</button>
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 3. Handle the POST request when a user submits the form
     */
    public function handle_form_submission() {
        // Check if our form was submitted
        if ( isset( $_POST['action'] ) && $_POST['action'] === 'submit_testimonial' ) {
            
            // Verify security nonce
            if ( ! isset( $_POST['testimonial_nonce'] ) || ! wp_verify_nonce( $_POST['testimonial_nonce'], 'submit_testimonial_nonce' ) ) {
                wp_die( 'Security check failed. Please try again.' );
            }

            // Sanitize inputs
            $name = sanitize_text_field( $_POST['testimonial_name'] );
            $text = sanitize_textarea_field( $_POST['testimonial_text'] );

            if ( ! empty( $name ) && ! empty( $text ) ) {
                
                // Create the post array
                $post_data = array(
                    'post_title'   => $name,
                    'post_content' => $text,
                    'post_type'    => 'testimonial',
                    // Use 'pending' if you want admin approval first, or 'publish' to show immediately.
                    'post_status'  => 'publish', 
                );

                // Insert into database
                wp_insert_post( $post_data );

                // Redirect back to the same page with a success flag to prevent duplicate submissions on refresh
                $redirect_url = add_query_arg( 'testimonial_submitted', '1', remove_query_arg( 'testimonial_submitted' ) );
                wp_safe_redirect( $redirect_url );
                exit;
            }
        }
    }

    /**
     * 4. Render the Testimonials List [testimonial_list]
     */
    public function render_list_shortcode() {
        // Fetch the 10 most recent published testimonials
        $query = new WP_Query( array(
            'post_type'      => 'testimonial',
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            'orderby'        => 'date',
            'order'          => 'DESC'
        ) );

        ob_start();

        if ( $query->have_posts() ) :
            echo '<div class="testimonial-list">';
            while ( $query->have_posts() ) : $query->the_post();
                ?>
                <div class="testimonial-item" style="border-left: 4px solid #0073aa; padding: 10px 15px; margin-bottom: 20px; background: #f9f9f9;">
                    <blockquote style="margin: 0 0 10px 0; font-style: italic;">
                        "<?php echo wp_kses_post( get_the_content() ); ?>"
                    </blockquote>
                    <p style="margin: 0; font-weight: bold;">
                        &mdash; <?php echo esc_html( get_the_title() ); ?>
                    </p>
                </div>
                <?php
            endwhile;
            echo '</div>';
            wp_reset_postdata();
        else :
            echo '<p>No testimonials to display yet.</p>';
        endif;

        return ob_get_clean();
    }
}

// Initialize the plugin
new Simple_Testimonials_Plugin();
```

Since I am currently in **Plan Mode**, I am consulting with you on this approach before touching any files. 

Does this code meet your expectations, or would you like to tweak anything (such as setting the default status to `pending` so an admin has to approve them before they show up)? If it looks good, let me know and I can finalize a plan to save this as a `.php` file in your workspace!
