Ripgrep is not available. Falling back to GrepTool.
Here is the complete code for a single-file WordPress plugin that handles both collecting and displaying testimonials. 

It registers a Custom Post Type (so you can view/manage them in the WordPress admin dashboard), uses a shortcode to display the submission form, and another shortcode to list the approved testimonials.

### `simple-testimonials.php`

Create a new file named `simple-testimonials.php`, paste this code into it, and upload it to your `/wp-content/plugins/` directory (or zip it and upload it via the WordPress plugin installer).

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: A single-file WordPress plugin to collect and display visitor testimonials.
 * Version: 1.0.0
 * Author: Your Name
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

class Simple_Testimonials_Plugin {

    public function __construct() {
        // Hook into WordPress
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'template_redirect', array( $this, 'handle_form_submission' ) );
        
        // Register Shortcodes
        add_shortcode( 'testimonial_form', array( $this, 'render_form_shortcode' ) );
        add_shortcode( 'testimonial_list', array( $this, 'render_list_shortcode' ) );
    }

    /**
     * Registers a Custom Post Type to store testimonials safely in the database.
     */
    public function register_cpt() {
        $args = array(
            'public'              => false, // Keep false so they don't have individual public URL pages
            'show_ui'             => true,  // Show in WordPress Admin
            'label'               => 'Testimonials',
            'menu_icon'           => 'dashicons-testimonial',
            'supports'            => array( 'title', 'editor' ),
            'exclude_from_search' => true,
        );
        register_post_type( 'visitor_testimonial', $args );
    }

    /**
     * Renders the submission form for visitors.
     * Usage: [testimonial_form]
     */
    public function render_form_shortcode() {
        $html = '<div class="simple-testimonial-form-wrapper" style="max-width: 500px; margin: 0 auto 30px auto;">';
        
        // Show success message if redirected after submission
        if ( isset( $_GET['testimonial_submitted'] ) && $_GET['testimonial_submitted'] == '1' ) {
            $html .= '<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;">';
            $html .= 'Thank you! Your testimonial has been submitted.';
            $html .= '</div>';
        }

        $html .= '<form action="" method="POST" class="simple-testimonial-form">';
        
        // Security Nonce
        $html .= wp_nonce_field( 'submit_testimonial_action', 'testimonial_nonce', true, false );
        
        $html .= '<div style="margin-bottom: 15px;">';
        $html .= '<label for="test_name" style="display: block; margin-bottom: 5px; font-weight: bold;">Your Name</label>';
        $html .= '<input type="text" id="test_name" name="test_name" required style="width: 100%; padding: 8px; box-sizing: border-box;">';
        $html .= '</div>';
        
        $html .= '<div style="margin-bottom: 15px;">';
        $html .= '<label for="test_content" style="display: block; margin-bottom: 5px; font-weight: bold;">Your Testimonial</label>';
        $html .= '<textarea id="test_content" name="test_content" required rows="4" style="width: 100%; padding: 8px; box-sizing: border-box;"></textarea>';
        $html .= '</div>';
        
        $html .= '<button type="submit" name="submit_testimonial" style="padding: 10px 20px; background: #0073aa; color: #fff; border: none; border-radius: 4px; cursor: pointer;">Submit Testimonial</button>';
        
        $html .= '</form></div>';

        return $html;
    }

    /**
     * Intercepts the page load to handle the POST request from the form.
     */
    public function handle_form_submission() {
        // Check if our form was submitted and nonce is valid
        if ( isset( $_POST['submit_testimonial'] ) && isset( $_POST['testimonial_nonce'] ) ) {
            if ( wp_verify_nonce( $_POST['testimonial_nonce'], 'submit_testimonial_action' ) ) {
                
                // Sanitize user inputs
                $name    = sanitize_text_field( $_POST['test_name'] );
                $content = sanitize_textarea_field( $_POST['test_content'] );

                if ( ! empty( $name ) && ! empty( $content ) ) {
                    // Save as a new post
                    $post_data = array(
                        'post_title'   => $name,
                        'post_content' => $content,
                        'post_type'    => 'visitor_testimonial',
                        // Set to 'pending' if you want to approve them in the admin dashboard first.
                        // Set to 'publish' to show them on the site immediately.
                        'post_status'  => 'publish', 
                    );
                    
                    wp_insert_post( $post_data );

                    // Post-Redirect-Get pattern to prevent duplicate submissions on page refresh
                    $redirect_url = add_query_arg( 'testimonial_submitted', '1', (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
                    wp_safe_redirect( $redirect_url );
                    exit;
                }
            }
        }
    }

    /**
     * Renders the list of approved testimonials.
     * Usage: [testimonial_list posts_per_page="5"]
     */
    public function render_list_shortcode( $atts ) {
        // Parse optional shortcode arguments
        $atts = shortcode_atts( array(
            'posts_per_page' => 10,
        ), $atts );

        $query_args = array(
            'post_type'      => 'visitor_testimonial',
            'post_status'    => 'publish', // Only show published testimonials
            'posts_per_page' => intval( $atts['posts_per_page'] ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $testimonial_query = new WP_Query( $query_args );

        $html = '<div class="simple-testimonial-list">';
        
        if ( $testimonial_query->have_posts() ) {
            while ( $testimonial_query->have_posts() ) {
                $testimonial_query->the_post();
                
                $html .= '<div class="testimonial-item" style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px; background: #fafafa;">';
                $html .= '<blockquote style="margin: 0 0 15px 0; font-style: italic; font-size: 1.1em; border-left: 4px solid #0073aa; padding-left: 15px;">';
                $html .= esc_html( get_the_content() );
                $html .= '</blockquote>';
                $html .= '<div class="testimonial-author" style="text-align: right; font-weight: bold; color: #555;">';
                $html .= '&mdash; ' . esc_html( get_the_title() );
                $html .= '</div>';
                $html .= '</div>';
            }
            wp_reset_postdata(); // Restore original Post Data
        } else {
            $html .= '<p>No testimonials yet. Be the first to leave one!</p>';
        }

        $html .= '</div>';

        return $html;
    }
}

// Initialize the plugin
new Simple_Testimonials_Plugin();
```

### How to Use It:
1. **Activate the Plugin**: Go to your Plugins page in WordPress and activate "Simple Testimonials".
2. **Collect Testimonials**: Add the `[testimonial_form]` shortcode to a Page or Post to display the submission form to your visitors.
3. **Display Testimonials**: Add the `[testimonial_list]` shortcode on any Page or Post where you want the testimonials to be visible.
4. **Manage Testimonials**: Look in your WordPress Admin sidebar menu for a new tab called **Testimonials**. You can edit, delete, or manually add testimonials from there. 

*(Note: In the code, I set `'post_status' => 'publish'`. This means testimonials will appear on your site instantly. If you want to review and approve them manually to prevent spam, change that line to `'post_status' => 'pending'` in the `handle_form_submission` method).*
