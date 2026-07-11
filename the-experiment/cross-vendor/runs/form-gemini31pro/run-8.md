Ripgrep is not available. Falling back to GrepTool.
Error executing tool run_shell_command: Tool "run_shell_command" not found. Did you mean one of: "update_topic", "grep_search", "invoke_agent"?
Here is the complete code for a single-file WordPress plugin that handles both the submission form and the display of testimonials.

It registers a Custom Post Type (CPT) to safely save the data inside your WordPress database and provides two shortcodes:
- `[testimonial_form]` to display the submission form.
- `[testimonial_list]` to display the submitted testimonials.

You can save this code as `simple-testimonials.php` and upload it to your `wp-content/plugins/` directory, or zip the file and upload it directly through the WordPress admin area.

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: A single-file plugin to submit and display testimonials using shortcodes.
 * Version: 1.0
 * Author: Gemini
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Simple_Testimonials {

    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_shortcode( 'testimonial_form', array( $this, 'render_form' ) );
        add_shortcode( 'testimonial_list', array( $this, 'render_list' ) );
        add_action( 'template_redirect', array( $this, 'handle_submission' ) );
    }

    /**
     * Registers the Testimonial Custom Post Type
     * This allows testimonials to be saved and managed in the WP Admin Dashboard.
     */
    public function register_cpt() {
        $args = array(
            'public'              => false, // Keeps them out of standard frontend queries
            'show_ui'             => true,  // Shows the "Testimonials" menu in wp-admin
            'label'               => 'Testimonials',
            'supports'            => array( 'title', 'editor' ),
            'menu_icon'           => 'dashicons-format-quote',
            'exclude_from_search' => true,
        );
        register_post_type( 'testimonial', $args );
    }

    /**
     * Renders the testimonial submission form
     * Shortcode: [testimonial_form]
     */
    public function render_form() {
        ob_start();
        ?>
        <div class="testimonial-form-container" style="max-width: 500px; margin-bottom: 30px;">
            <?php
            // Check for success message in URL
            if ( isset( $_GET['testimonial_submitted'] ) && $_GET['testimonial_submitted'] == '1' ) {
                echo '<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">Thank you! Your testimonial has been submitted.</div>';
            }
            ?>
            <form action="" method="post" class="testimonial-form">
                <?php wp_nonce_field( 'submit_testimonial', 'testimonial_nonce' ); ?>
                
                <div style="margin-bottom: 15px;">
                    <label for="testimonial_name" style="display: block; font-weight: bold; margin-bottom: 5px;">Your Name:</label>
                    <input type="text" name="testimonial_name" id="testimonial_name" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="testimonial_content" style="display: block; font-weight: bold; margin-bottom: 5px;">Your Testimonial:</label>
                    <textarea name="testimonial_content" id="testimonial_content" rows="4" required style="width: 100%; padding: 8px; box-sizing: border-box;"></textarea>
                </div>
                
                <div>
                    <input type="submit" name="submit_testimonial_action" value="Submit Testimonial" style="padding: 10px 20px; cursor: pointer; background: #0073aa; color: #fff; border: none; border-radius: 3px;">
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renders the list of published testimonials
     * Shortcode: [testimonial_list posts_per_page="10"]
     */
    public function render_list( $atts ) {
        $atts = shortcode_atts( array(
            'posts_per_page' => 10,
        ), $atts );

        $query = new WP_Query( array(
            'post_type'      => 'testimonial',
            'post_status'    => 'publish', // Only display published testimonials
            'posts_per_page' => intval( $atts['posts_per_page'] ),
            'orderby'        => 'date',
            'order'          => 'DESC'
        ) );

        ob_start();

        if ( $query->have_posts() ) {
            echo '<div class="testimonial-list">';
            while ( $query->have_posts() ) {
                $query->the_post();
                ?>
                <div class="testimonial-item" style="border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px; border-radius: 8px; background: #f8fafc;">
                    <blockquote style="margin: 0 0 15px 0; font-style: italic; font-size: 1.1em; color: #333;">
                        "<?php echo wp_kses_post( get_the_content() ); ?>"
                    </blockquote>
                    <p style="margin: 0; font-weight: bold; color: #555;">&mdash; <?php echo esc_html( get_the_title() ); ?></p>
                </div>
                <?php
            }
            echo '</div>';
            wp_reset_postdata(); // Important: Restore original Post Data
        } else {
            echo '<p>No testimonials have been submitted yet.</p>';
        }

        return ob_get_clean();
    }

    /**
     * Intercepts POST requests on the front end to save the submission
     */
    public function handle_submission() {
        if ( isset( $_POST['submit_testimonial_action'] ) && isset( $_POST['testimonial_nonce'] ) ) {
            
            // 1. Verify nonce to prevent Cross-Site Request Forgery (CSRF)
            if ( wp_verify_nonce( $_POST['testimonial_nonce'], 'submit_testimonial' ) ) {
                
                // 2. Sanitize user inputs safely
                $name = sanitize_text_field( wp_unslash( $_POST['testimonial_name'] ) );
                $content = sanitize_textarea_field( wp_unslash( $_POST['testimonial_content'] ) );

                if ( ! empty( $name ) && ! empty(Here is the complete, single-file WordPress plugin. It uses a Custom Post Type to store testimonials safely in the WordPress database and provides shortcodes for both the submission form and the display list.

You can save this code as `simple-testimonials.php`, zip it, and upload it via your WordPress admin, or drop it directly into your `wp-content/plugins/` directory.

### Plugin Code (`simple-testimonials.php`)

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: A single-file plugin to submit and display testimonials using shortcodes.
 * Version: 1.0
 * Author: Gemini
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Simple_Testimonials {

    public function __construct() {
        // Hook into WordPress
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_shortcode( 'testimonial_form', array( $this, 'render_form' ) );
        add_shortcode( 'testimonial_list', array( $this, 'render_list' ) );
        add_action( 'template_redirect', array( $this, 'handle_submission' ) );
    }

    /**
     * Registers the Testimonial Custom Post Type (CPT) to store the submissions.
     */
    public function register_cpt() {
        $args = array(
            'public'              => false, // Not publicly queryable via frontend URLs
            'show_ui'             => true,  // Show in the admin dashboard
            'label'               => 'Testimonials',
            'supports'            => array( 'title', 'editor' ),
            'menu_icon'           => 'dashicons-format-quote',
            'exclude_from_search' => true,
        );
        register_post_type( 'testimonial', $args );
    }

    /**
     * Renders the testimonial submission form.
     * Usage: [testimonial_form]
     */
    public function render_form() {
        ob_start();
        ?>
        <div class="testimonial-form-container" style="max-width: 500px; margin-bottom: 30px;">
            <?php
            // Show a success message if the user was just redirected here after submitting
            if ( isset( $_GET['testimonial_submitted'] ) && $_GET['testimonial_submitted'] == '1' ) {
                echo '<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">Thank you! Your testimonial has been submitted.</div>';
            }
            ?>
            <form action="" method="post" class="testimonial-form">
                <?php wp_nonce_field( 'submit_testimonial', 'testimonial_nonce' ); ?>
                
                <div style="margin-bottom: 15px;">
                    <label for="testimonial_name" style="display: block; font-weight: bold; margin-bottom: 5px;">Your Name:</label>
                    <input type="text" name="testimonial_name" id="testimonial_name" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="testimonial_content" style="display: block; font-weight: bold; margin-bottom: 5px;">Your Testimonial:</label>
                    <textarea name="testimonial_content" id="testimonial_content" rows="4" required style="width: 100%; padding: 8px; box-sizing: border-box;"></textarea>
                </div>
                
                <div>
                    <input type="submit" name="submit_testimonial_action" value="Submit Testimonial" style="padding: 10px 20px; cursor: pointer;">
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renders the list of published testimonials.
     * Usage: [testimonial_list posts_per_page="10"]
     */
    public function render_list( $atts ) {
        $atts = shortcode_atts( array(
            'posts_per_page' => 10,
        ), $atts );

        $query = new WP_Query( array(
            'post_type'      => 'testimonial',
            'post_status'    => 'publish', // Only fetch published testimonials
            'posts_per_page' => intval( $atts['posts_per_page'] ),
            'orderby'        => 'date',
            'order'          => 'DESC'
        ) );

        ob_start();

        if ( $query->have_posts() ) {
            echo '<div class="testimonial-list">';
            while ( $query->have_posts() ) {
                $query->the_post();
                ?>
                <div class="testimonial-item" style="border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px; border-radius: 8px; background: #f8fafc;">
                    <blockquote style="margin: 0 0 15px 0; font-style: italic; font-size: 1.1em; color: #333;">
                        "<?php echo wp_kses_post( get_the_content() ); ?>"
                    </blockquote>
                    <p style="margin: 0; font-weight: bold; color: #555;">&mdash; <?php echo esc_html( get_the_title() ); ?></p>
                </div>
                <?php
            }
            echo '</div>';
            wp_reset_postdata(); // Important: Reset the global $post object
        } else {
            echo '<p>No testimonials have been submitted yet.</p>';
        }

        return ob_get_clean();
    }

    /**
     * Handles the POST request to save a new testimonial safely.
     */
    public function handle_submission() {
        if ( isset( $_POST['submit_testimonial_action'] ) && isset( $_POST['testimonial_nonce'] ) ) {
            
            // 1. Verify nonce for security against CSRF attacks
            if ( wp_verify_nonce( $_POST['testimonial_nonce'], 'submit_testimonial' ) ) {
                
                // 2. Sanitize user inputs safely
                $name    = sanitize_text_field( wp_unslash( $_POST['testimonial_name'] ) );
                $content = sanitize_textarea_field( wp_unslash( $_POST['testimonial_content'] ) );

                if ( ! empty( $name ) && ! empty( $content ) ) {
                    $post_data = array(
                        'post_title'   => $name,
                        'post_content' => $content,
                        'post_type'    => 'testimonial',
                        // Set this to 'publish' to show immediately. 
                        // Set it to 'draft' if you want administrators to approve them first.
                        'post_status'  => 'publish', 
                    );

                    // 3. Insert the post into the database
                    $post_id = wp_insert_post( $post_data );

                    if ( ! is_wp_error( $post_id ) ) {
                        // 4. Redirect to the same page with a success parameter (prevents duplicate form resubmission)
                        $redirect_url = add_query_arg( 'testimonial_submitted', '1', wp_get_referer() ?: home_url() );
                        wp_safe_redirect( $redirect_url );
                        exit;
                    }
                }
            }
        }
    }
}

// Initialize the plugin
new Simple_Testimonials();
```

### How to use it:

1. **Activate the plugin** in your WordPress Admin dashboard. You will see a new "Testimonials" item appear in your left-hand menu.
2. **Add the form**: Create a new Page (e.g., "Leave a Review") and add the shortcode `[testimonial_form]` to the content.
3. **Display the testimonials**: Create a Page (e.g., "Testimonials") or place it below the form using the shortcode `[testimonial_list]`.
   - *Optional:* You can limit the number of testimonials it shows like this: `[testimonial_list posts_per_page="5"]`

### Important Features Included:
- **Security**: It uses WordPress Nonces to prevent CSRF attacks, and utilizes `sanitize_text_field` and `sanitize_textarea_field` to clean database inputs.
- **Output Escaping**: Uses `wp_kses_post()` and `esc_html()` when outputting variables to protect against XSS injections.
- **PRG Pattern**: Implements the Post/Redirect/Get pattern (`wp_safe_redirect`) upon successful form submission so that refreshing the page won't accidentally re-submit the testimonial.
