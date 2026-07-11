Here's the complete single-file WordPress plugin code:

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Description: Collect and display visitor testimonials with a simple form
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

register_activation_hook( __FILE__, 'testimonials_create_table' );
add_shortcode( 'testimonials_form', 'testimonials_form_shortcode' );
add_shortcode( 'testimonials_display', 'testimonials_display_shortcode' );
add_action( 'wp_ajax_submit_testimonial', 'testimonials_handle_submission' );
add_action( 'wp_ajax_nopriv_submit_testimonial', 'testimonials_handle_submission' );

function testimonials_create_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'testimonials';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(100) NOT NULL,
		email varchar(100) NOT NULL,
		message longtext NOT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		approved tinyint(1) DEFAULT 0,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

function testimonials_form_shortcode() {
	ob_start();
	?>
	<div class="testimonials-form-wrapper" style="max-width: 500px; margin: 20px 0;">
		<form id="testimonials-form" class="testimonials-form">
			<div class="form-group" style="margin-bottom: 15px;">
				<label for="testimonial-name" style="display: block; margin-bottom: 5px; font-weight: bold;">Name:</label>
				<input type="text" id="testimonial-name" name="name" required maxlength="100" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
			</div>
			<div class="form-group" style="margin-bottom: 15px;">
				<label for="testimonial-email" style="display: block; margin-bottom: 5px; font-weight: bold;">Email:</label>
				<input type="email" id="testimonial-email" name="email" required maxlength="100" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
			</div>
			<div class="form-group" style="margin-bottom: 15px;">
				<label for="testimonial-message" style="display: block; margin-bottom: 5px; font-weight: bold;">Your Testimonial:</label>
				<textarea id="testimonial-message" name="message" required maxlength="500" rows="5" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;"></textarea>
			</div>
			<button type="submit" class="submit-btn" style="background-color: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Submit Testimonial</button>
			<div id="form-message" class="form-message" style="margin-top: 10px;"></div>
		</form>
	</div>

	<script>
	jQuery(document).ready(function($) {
		$('#testimonials-form').on('submit', function(e) {
			e.preventDefault();
			var name = $('#testimonial-name').val();
			var email = $('#testimonial-email').val();
			var message = $('#testimonial-message').val();
			var messageDiv = $('#form-message');

			$.ajax({
				url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
				type: 'POST',
				data: {
					action: 'submit_testimonial',
					name: name,
					email: email,
					message: message,
					nonce: '<?php echo wp_create_nonce( "testimonials_nonce" ); ?>'
				},
				success: function(response) {
					if (response.success) {
						messageDiv.html('<p style="color: green; font-weight: bold;">Thank you! Your testimonial has been submitted.</p>');
						$('#testimonials-form')[0].reset();
						setTimeout(function() {
							messageDiv.html('');
						}, 3000);
					} else {
						messageDiv.html('<p style="color: red;">Error: ' + response.data + '</p>');
					}
				},
				error: function() {
					messageDiv.html('<p style="color: red;">An error occurred. Please try again.</p>');
				}
			});
		});
	});
	</script>
	<?php
	return ob_get_clean();
}

function testimonials_handle_submission() {
	check_ajax_referer( 'testimonials_nonce', 'nonce' );

	global $wpdb;
	$table_name = $wpdb->prefix . 'testimonials';

	$name = sanitize_text_field( $_POST['name'] ?? '' );
	$email = sanitize_email( $_POST['email'] ?? '' );
	$message = sanitize_textarea_field( $_POST['message'] ?? '' );

	if ( empty( $name ) || empty( $email ) || empty( $message ) ) {
		wp_send_json_error( 'All fields are required.' );
	}

	if ( ! is_email( $email ) ) {
		wp_send_json_error( 'Invalid email address.' );
	}

	$result = $wpdb->insert(
		$table_name,
		array(
			'name' => $name,
			'email' => $email,
			'message' => $message,
		),
		array( '%s', '%s', '%s' )
	);

	if ( $result ) {
		wp_send_json_success( 'Testimonial submitted successfully.' );
	} else {
		wp_send_json_error( 'Failed to save testimonial.' );
	}
}

function testimonials_display_shortcode() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'testimonials';

	$testimonials = $wpdb->get_results( "SELECT * FROM $table_name WHERE approved = 1 ORDER BY created_at DESC" );

	if ( empty( $testimonials ) ) {
		return '<p style="text-align: center; color: #666;">No testimonials yet.</p>';
	}

	$html = '<div class="testimonials-list" style="max-width: 800px; margin: 20px auto;">';
	foreach ( $testimonials as $testimonial ) {
		$html .= '<div class="testimonial-item" style="background: #f9f9f9; padding: 20px; margin-bottom: 15px; border-left: 4px solid #0073aa; border-radius: 4px;">';
		$html .= '<p style="margin: 0 0 10px 0; font-style: italic; color: #333;">"' . esc_html( $testimonial->message ) . '"</p>';
		$html .= '<p style="margin: 0; font-weight: bold; color: #0073aa;">— ' . esc_html( $testimonial->name ) . '</p>';
		$html .= '</div>';
	}
	$html .= '</div>';

	return $html;
}
```

**How to use:**

1. Save as `testimonials.php` in your WordPress plugins directory (`wp-content/plugins/`)
2. Activate the plugin from WordPress admin
3. Add the form shortcode to a page: `[testimonials_form]`
4. Add the display shortcode to another page: `[testimonials_display]`

**Features:**
- Simple form that collects name, email, and testimonial text
- Data is saved to a custom database table
- Testimonials require admin approval before displaying
- AJAX form submission with success/error messages
- Displays approved testimonials in reverse chronological order
- Security: uses nonces to prevent CSRF attacks and sanitizes all input

**Note:** To approve testimonials, you'll need to add an admin interface or manually update the database, setting `approved = 1` for testimonials you want to display.
