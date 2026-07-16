Ripgrep is not available. Falling back to GrepTool.
Here is the complete code for a single-file WordPress plugin that adds a simple testimonial system. 

It registers a Custom Post Type (so you can view/manage them from the WordPress Admin area), provides a shortcode for the submission form, and provides another shortcode to display the submitted testimonials.

### **How to use this plugin:**
1. Create a new file in your WordPress `wp-content/plugins/` directory named `simple-testimonials.php`.
2. Paste the code below into that file and save it.
3. Go to **Plugins** in your WordPress dashboard and activate **Simple Visitor Testimonials**.
4. Create or edit a page, and place the `[testimonial_form]` shortcode where you want the submission form to appear, and `[testimonial_list]` where you want the testimonials to be displayed.

### **The Plugin Code:**

```php
<?php
/**
 * Plugin Name: Simple Visitor Testimonials
 * Description: Lets visitors submit testimonials via a form and displays them using shortcodes.
 * Version: 1.0
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly to prevent unauthorized access
}

/**
 * 1. Register the Custom Post Type to store testimonials
 */
add_action( 'init', 'svt_register_post_type' );
function svt_register_post_type() {
    $args = array(
        'public'             => false,  // Prevents single testimonial pages from being accessed directly
        'show_ui'            => true,   // Shows the menu in the WP Admin
        'publicly_queryable' => false,
        'label'              => 'Testimonials',
        'menu_icon'          => 'dashicons-format-quote', // Quote icon in the admin sidebar
        'supports'           => array( 'title', 'editor' ), // Title = Author Name, Editor = Testimonial text
    );
    register_post_type( 'svt_testimonial', $args );
}

/**
 * 2. Shortcode for the Submission Form: [testimonial_form]
 */
add_shortcode( 'testimonial_form', 'svt_form_shortcode' );
function svt_form_shortcode() {
    ob_start();
    
    // Display Success/Error messages based on URL parameters
    if ( isset( $_GET['svt_success'] ) ) {
        echo '<p style="color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px;">Thank you! Your testimonial has been submitted.</p>';
    }

    if ( isset( $_GET['svt_error'] ) ) {
        echo '<p style="color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px;">There was an error submitting your testimonial. Please ensure all fields are filled.</p>';
    }

    ?>
    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" class="svt-testimonial-form">
        <!-- Hidden field to tell WordPress which action hook to trigger -->
        <input type="hidden" name="action" value="svt_submit_testimonial">
        
        <!-- Security Nonce -->
        <?php wp_nonce_field( 'svt_testimonial_nonce', 'svt_nonce' ); ?>
        
        <div style="margin-bottom: 15px;">
            <label for="svt_name" style="display:block; font-weight: bold; margin-bottom: 5px;">Your Name:</label>
            <input type="text" name="svt_name" id="svt_name" required style="width: 100%; max-width: 500px; padding: 8px;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label for="svt_text" style="display:block; font-weight: bold; margin-bottom: 5px;">Your Testimonial:</label>
            <textarea name="svt_text" id="svt_text" required rows="5" style="width: 100%; max-width: 500px; padding: 8px;"></textarea>
        </div>
        
        <div>
            <button type="submit" style="padding: 10px 20px; cursor: pointer;">Submit Testimonial</button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * 3. Handle the form submission
 */
// Hook for logged-in users
add_action( 'admin_post_svt_submit_testimonial', 'svt_handle_submission' );
// Hook for guests
add_action( 'admin_post_nopriv_svt_submit_testimonial', 'svt_handle_submission' );

function svt_handle_submission() {
    // 1. Check Security Nonce
    if ( ! isset( $_POST['svt_nonce'] ) || ! wp_verify_nonce( $_POST['svt_nonce'], 'svt_testimonial_nonce' ) ) {
        wp_die( 'Security check failed. Please go back and try again.' );
    }

    // 2. Sanitize user inputs
    $name = isset( $_POST['svt_name'] ) ? sanitize_text_field( $_POST['svt_name'] ) : '';
    $text = isset( $_POST['svt_text'] ) ? sanitize_textarea_field( $_POST['svt_text'] ) : '';

    // Set up redirect URL back to the page the form was submitted from
    $redirect_url = wp_get_referer();
    $redirect_url = remove_query_arg( array('svt_success', 'svt_error'), $redirect_url );

    if ( empty( $name ) || empty( $text ) ) {
        wp_redirect( add_query_arg( 'svt_error', '1', $redirect_url ) );
        exit;
    }

    // 3. Save as a custom post type
    $post_data = array(
        'post_title'   => $name,
        'post_content' => $text,
        'post_status'  => 'publish', // Change this to 'pending' if you want to moderate them manually before they appear
        'post_type'    => 'svt_testimonial',
    );

    $post_id = wp_insert_post( $post_data );

    // 4. Redirect with success or error parameter
    if ( ! is_wp_error( $post_id ) && $post_id > 0 ) {
        wp_redirect( add_query_arg( 'svt_success', '1', $redirect_url ) );
    } else {
        wp_redirect( add_query_arg( 'svt_error', '1', $redirect_url ) );
    }
    exit;
}

/**
 * 4. Shortcode to display testimonials: [testimonial_list]
 */
add_shortcode( 'testimonial_list', 'svt_list_shortcode' );
function svt_list_shortcode( $atts ) {
    // Allow users to set limit via [testimonial_list posts_per_page="5"]
    $atts = shortcode_atts( array(
        'posts_per_page' => 10,
    ), $atts );

    $args = array(
        'post_type'      => 'svt_testimonial',
        'post_status'    => 'publish',
        'posts_per_page' => intval( $atts['posts_per_page'] ),
        'orderby'        => 'date',
        'order'          => 'DESC', // Shows newest first
    );

    $query = new WP_Query( $args );

    ob_start();

    if ( $query->have_posts() ) {
        echo '<div class="svt-testimonials-list">';
        while ( $query->have_posts() ) {
            $query->the_post();
            ?>
            <div class="svt-testimonial-card" style="border-left: 4px solid #0073aa; padding: 15px 20px; margin-bottom: 25px; background: #f9f9f9; border-radius: 0 4px 4px 0;">
                <blockquote style="margin: 0 0 10px 0; padding: 0; border: none; font-style: italic; font-size: 1.1em;">
                    "<?php echo nl2br( esc_html( get_the_content() ) ); ?>"
                </blockquote>
                <div class="svt-testimonial-author" style="font-weight: bold; color: #555;">
                    &mdash; <?php echo esc_html( get_the_title() ); ?>
                </div>
            </div>
            <?php
        }
        echo '</div>';
        wp_reset_postdata(); // Clean up query variables
    } else {
        echo '<p>No testimonials found yet. Be the first to leave one!</p>';
    }

    return ob_get_clean();
}
```

### Security & Functional Features included:
* **Nonces:** Uses `wp_nonce_field` and `wp_verify_nonce` to prevent Cross-Site Request Forgery (CSRF).
* **Sanitization:** Uses `sanitize_text_field` and `sanitize_textarea_field` to strip any malicious scripts before saving to the database.
* **Escaping:** Uses `esc_html` upon rendering the testimonials to prevent Cross-Site Scripting (XSS).
* **Post Status:** Hardcoded to `publish` so they show up immediately. If you wish to vet submissions first, change `'post_status' => 'publish'` to `'post_status' => 'pending'` on line 97. They will then sit in your WP Admin for you to approve.
* **Redirection State cleanup:** Safely uses `remove_query_arg` so refreshing the page after a successful submission doesn't result in duplicate form submissions.
