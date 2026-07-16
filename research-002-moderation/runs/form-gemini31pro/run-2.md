Ripgrep is not available. Falling back to GrepTool.
Here is the complete, single-file WordPress plugin. It creates a custom post type for testimonials, provides a shortcode to display the submission form, handles the form submission securely, and provides another shortcode to display the approved testimonials.

To use this, you can save the code below as `simple-testimonials.php`, place it in your `wp-content/plugins/` directory, and activate it from your WordPress admin dashboard.

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: Lets visitors leave a short text testimonial through a form, saves them, and shows them via shortcodes. Use [testimonial_form] to display the form and [testimonial_list] to show approved testimonials.
 * Version: 1.0
 * Author: Your Name
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

class Simple_Testimonials_Plugin {

    public function __construct() {
        // Register the Custom Post Type
        add_action( 'init', array( $this, 'register_testimonial_cpt' ) );
        
        // Register Shortcodes
        add_shortcode( 'testimonial_form', array( $this, 'render_form_shortcode' ) );
        add_shortcode( 'testimonial_list', array( $this, 'render_list_shortcode' ) );
        
        // Handle form submissions for logged-in and logged-out users
        add_action( 'admin_post_nopriv_submit_testimonial', array( $this, 'handle_form_submission' ) );
        add_action( 'admin_post_submit_testimonial', array( $this, 'handle_form_submission' ) );
    }

    /**
     * 1. Register Custom Post Type to store testimonials
     */
    public function register_testimonial_cpt() {
        $labels = array(
            'name'                  => _x( 'Testimonials', 'Post type general name', 'textdomain' ),
            'singular_name'         => _x( 'Testimonial', 'Post type singular name', 'textdomain' ),
            'menu_name'             => _x( 'Testimonials', 'Admin Menu text', 'textdomain' ),
            'name_admin_bar'        => _x( 'Testimonial', 'Add New on Toolbar', 'textdomain' ),
            'add_new'               => __( 'Add New', 'textdomain' ),
            'add_new_item'          => __( 'Add New Testimonial', 'textdomain' ),
            'new_item'              => __( 'New Testimonial', 'textdomain' ),
            'edit_item'             => __( 'Edit Testimonial', 'textdomain' ),
            'view_item'             => __( 'View Testimonial', 'textdomain' ),
            'all_items'             => __( 'All Testimonials', 'textdomain' ),
            'search_items'          => __( 'Search Testimonials', 'textdomain' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => false, // We only want to show them via shortcode
            'show_ui'            => true,
            'show_in_menu'       => true,
            'capability_type'    => 'post',
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-testimonial',
            'supports'           => array( 'title', 'editor' ),
        );

        register_post_type( 'testimonial', $args );
    }

    /**
     * 2. Render Form Shortcode: [testimonial_form]
     */
    public function render_form_shortcode() {
        ob_start();
        $message = '';
        
        // Check for success or error parameters in the URL
        if ( isset( $_GET['testimonial_submitted'] ) && $_GET['testimonial_submitted'] === 'true' ) {
            $message = '<p style="color: green; font-weight: bold;">Thank you! Your testimonial has been submitted and is awaiting approval.</p>';
        }
        if ( isset( $_GET['testimonial_error'] ) && $_GET['testimonial_error'] === 'true' ) {
            $message = '<p style="color: red; font-weight: bold;">There was an error submitting your testimonial. Please ensure all fields are filled out.</p>';
        }
        ?>
        <div class="testimonial-form-container">
            <?php echo $message; ?>
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
                <!-- Hidden action field tells admin-post.php which hook to fire -->
                <input type="hidden" name="action" value="submit_testimonial">
                
                <!-- Security nonce -->
                <?php wp_nonce_field( 'submit_testimonial_nonce', 'testimonial_nonce' ); ?>
                
                <p>
                    <label for="testimonial_author">Your Name:</label><br>
                    <input type="text" id="testimonial_author" name="testimonial_author" required style="width: 100%; max-width: 400px; box-sizing: border-box;">
                </p>
                <p>
                    <label for="testimonial_content">Your Testimonial:</label><br>
                    <textarea id="testimonial_content" name="testimonial_content" rows="4" required style="width: 100%; max-width: 400px; box-sizing: border-box;"></textarea>
                </p>
                <p>
                    <input type="submit" value="Submit Testimonial">
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 3. Handle Form Submission
     */
    public function handle_form_submission() {
        // Verify nonce for security
        if ( ! isset( $_POST['testimonial_nonce'] ) || ! wp_verify_nonce( $_POST['testimonial_nonce'], 'submit_testimonial_nonce' ) ) {
            wp_die( 'Security check failed. Please go back and try again.' );
        }

        // Sanitize inputs
        $author  = isset( $_POST['testimonial_author'] ) ? sanitize_text_field( $_POST['testimonial_author'] ) : '';
        $content = isset( $_POST['testimonial_content'] ) ? sanitize_textarea_field( $_POST['testimonial_content'] ) : '';

        // Validate
        if ( empty( $author ) || empty( $content ) ) {
            $redirect_url = add_query_arg( 'testimonial_error', 'true', wp_get_referer() );
            wp_safe_redirect( $redirect_url );
            exit;
        }

        // Prepare post data
        $post_data = array(
            'post_title'   => wp_strip_all_tags( $author ),
            'post_content' => $content,
            'post_type'    => 'testimonial',
            'post_status'  => 'draft', // Saves as draft so an admin must approve/publish it
        );

        // Insert the post into the database
        $post_id = wp_insert_post( $post_data );

        // Redirect back with success or error parameter
        if ( ! is_wp_error( $post_id ) ) {
            $redirect_url = add_query_arg( 'testimonial_submitted', 'true', wp_get_referer() );
        } else {
            $redirect_url = add_query_arg( 'testimonial_error', 'true', wp_get_referer() );
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * 4. Render List Shortcode: [testimonial_list]
     */
    public function render_list_shortcode( $atts ) {
        // Allow user to set the number of testimonials via shortcode, e.g., [testimonial_list posts_per_page="5"]
        $atts = shortcode_atts( array(
            'posts_per_page' => 10,
        ), $atts );

        $args = array(
            'post_type'      => 'testimonial',
            'post_status'    => 'publish', // Only show approved (published) testimonials
            'posts_per_page' => intval( $atts['posts_per_page'] ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $query = new WP_Query( $args );

        ob_start();

        if ( $query->have_posts() ) {
            echo '<div class="testimonial-list">';
            while ( $query->have_posts() ) {
                $query->the_post();
                ?>
                <div class="testimonial-item" style="border: 1px solid #e0e0e0; padding: 20px; margin-bottom: 20px; border-radius: 8px; background-color: #fafafa;">
                    <blockquote style="margin: 0 0 15px 0; font-style: italic; font-size: 1.1em; color: #555;">
                        "<?php echo esc_html( get_the_content() ); ?>"
                    </blockquote>
                    <p style="margin: 0; font-weight: bold; text-align: right; color: #333;">
                        &mdash; <?php echo esc_html( get_the_title() ); ?>
                    </p>
                </div>
                <?php
            }
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>No testimonials have been published yet.</p>';
        }

        return ob_get_clean();
    }
}

// Initialize the plugin
new Simple_Testimonials_Plugin();
```

### How to use it:
1. **Activation:** After activating the plugin, you will see a new **"Testimonials"** menu appear in your WordPress dashboard side-menu. 
2. **Showing the form:** Create a new page and add the shortcode `[testimonial_form]` where you want visitors to leave a review.
3. **Approving testimonials:** When a visitor submits the form, a new Testimonial post is created in your backend under "Testimonials" with a status of **Draft**. An admin must go in and click **Publish** to approve it. This protects your site from spam.
4. **Displaying testimonials:** Create a page (or use the same one) and add the shortcode `[testimonial_list]` where you want the approved reviews to appear. You can also limit the amount shown by doing `[testimonial_list posts_per_page="5"]`.
