Let me display the plugin code for you:

Here's a complete single-file WordPress plugin for testimonials:

```php
<?php
/**
 * Plugin Name: Simple Testimonials
 * Plugin URI: https://example.com
 * Description: Let visitors submit testimonials and display them on a page
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Simple_Testimonials {
	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'simple_testimonials';

		register_activation_hook( __FILE__, [ $this, 'create_table' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_shortcode( 'testimonial_form', [ $this, 'render_form' ] );
		add_shortcode( 'testimonials_display', [ $this, 'display_testimonials' ] );
		add_action( 'wp_ajax_nopriv_submit_testimonial', [ $this, 'handle_form_submission' ] );
		add_action( 'wp_ajax_submit_testimonial', [ $this, 'handle_form_submission' ] );
	}

	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			name VARCHAR(100) NOT NULL,
			email VARCHAR(100),
			message LONGTEXT NOT NULL,
			status VARCHAR(20) DEFAULT 'pending',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function enqueue_styles() {
		wp_add_inline_style( 'wp-forms', $this->get_css() );
	}

	private function get_css() {
		return "
			.testimonial-form-wrapper {
				max-width: 500px;
				margin: 20px 0;
				padding: 20px;
				border: 1px solid #ddd;
				border-radius: 5px;
				background-color: #f9f9f9;
			}

			.testimonial-form-wrapper form {
				display: flex;
				flex-direction: column;
			}

			.testimonial-form-wrapper label {
				margin-top: 15px;
				margin-bottom: 5px;
				font-weight: 600;
			}

			.testimonial-form-wrapper input[type='text'],
			.testimonial-form-wrapper input[type='email'],
			.testimonial-form-wrapper textarea {
				padding: 10px;
				border: 1px solid #ccc;
				border-radius: 3px;
				font-size: 14px;
				font-family: inherit;
			}

			.testimonial-form-wrapper textarea {
				min-height: 100px;
				resize: vertical;
			}

			.testimonial-form-wrapper button {
				margin-top: 15px;
				padding: 10px 20px;
				background-color: #0073aa;
				color: white;
				border: none;
				border-radius: 3px;
				cursor: pointer;
				font-size: 16px;
			}

			.testimonial-form-wrapper button:hover {
				background-color: #005a87;
			}

			.testimonial-message {
				padding: 10px;
				margin: 10px 0;
				border-radius: 3px;
			}

			.testimonial-message.success {
				background-color: #d4edda;
				color: #155724;
				border: 1px solid #c3e6cb;
			}

			.testimonial-message.error {
				background-color: #f8d7da;
				color: #721c24;
				border: 1px solid #f5c6cb;
			}

			.testimonials-list {
				max-width: 600px;
				margin: 20px 0;
			}

			.testimonial-item {
				padding: 15px;
				margin: 15px 0;
				border-left: 4px solid #0073aa;
				background-color: #f9f9f9;
				border-radius: 3px;
			}

			.testimonial-item .testimonial-name {
				font-weight: 600;
				font-size: 16px;
				margin-bottom: 5px;
			}

			.testimonial-item .testimonial-text {
				font-size: 14px;
				line-height: 1.6;
				color: #333;
				margin-bottom: 8px;
			}

			.testimonial-item .testimonial-date {
				font-size: 12px;
				color: #999;
			}

			.testimonials-empty {
				padding: 20px;
				text-align: center;
				color: #999;
				font-size: 14px;
			}
		";
	}

	public function render_form() {
		$nonce = wp_create_nonce( 'testimonial_form_nonce' );

		ob_start();
		?>
		<div class="testimonial-form-wrapper">
			<div id="testimonial-message"></div>
			<form id="testimonial-form">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">

				<label for="testimonial-name"><?php esc_html_e( 'Your Name', 'simple-testimonials' ); ?></label>
				<input type="text" id="testimonial-name" name="name" required>

				<label for="testimonial-email"><?php esc_html_e( 'Email (optional)', 'simple-testimonials' ); ?></label>
				<input type="email" id="testimonial-email" name="email">

				<label for="testimonial-message"><?php esc_html_e( 'Your Testimonial', 'simple-testimonials' ); ?></label>
				<textarea id="testimonial-message" name="message" required></textarea>

				<button type="submit"><?php esc_html_e( 'Submit Testimonial', 'simple-testimonials' ); ?></button>
			</form>
		</div>

		<script>
			document.getElementById( 'testimonial-form' ).addEventListener( 'submit', function( e ) {
				e.preventDefault();

				const formData = new FormData( this );
				const data = {
					action: 'submit_testimonial',
					nonce: formData.get( 'nonce' ),
					name: formData.get( 'name' ),
					email: formData.get( 'email' ),
					message: formData.get( 'message' )
				};

				fetch( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: new URLSearchParams( data )
				} )
				.then( response => response.json() )
				.then( data => {
					const messageDiv = document.getElementById( 'testimonial-message' );
					if ( data.success ) {
						messageDiv.className = 'testimonial-message success';
						messageDiv.textContent = data.data.message;
						document.getElementById( 'testimonial-form' ).reset();
					} else {
						messageDiv.className = 'testimonial-message error';
						messageDiv.textContent = data.data.message;
					}
				} );
			} );
		</script>
		<?php
		return ob_get_clean();
	}

	public function handle_form_submission() {
		check_ajax_referer( 'testimonial_form_nonce', 'nonce' );

		$name    = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
		$email   = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';

		if ( empty( $name ) || empty( $message ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Please fill in all required fields.', 'simple-testimonials' ),
				]
			);
		}

		if ( strlen( $message ) > 500 ) {
			wp_send_json_error(
				[
					'message' => __( 'Testimonial must be less than 500 characters.', 'simple-testimonials' ),
				]
			);
		}

		global $wpdb;
		$result = $wpdb->insert(
			$this->table_name,
			[
				'name'    => $name,
				'email'   => $email,
				'message' => $message,
				'status'  => 'pending',
			],
			[ '%s', '%s', '%s', '%s' ]
		);

		if ( $result ) {
			wp_send_json_success(
				[
					'message' => __( 'Thank you! Your testimonial has been submitted for review.', 'simple-testimonials' ),
				]
			);
		} else {
			wp_send_json_error(
				[
					'message' => __( 'An error occurred. Please try again.', 'simple-testimonials' ),
				]
			);
		}
	}

	public function display_testimonials() {
		global $wpdb;

		$testimonials = $wpdb->get_results(
			"SELECT * FROM $this->table_name WHERE status = 'approved' ORDER BY created_at DESC"
		);

		ob_start();
		?>
		<div class="testimonials-list">
			<?php
			if ( empty( $testimonials ) ) {
				echo '<div class="testimonials-empty">' . esc_html__( 'No testimonials yet. Be the first!', 'simple-testimonials' ) . '</div>';
			} else {
				foreach ( $testimonials as $testimonial ) {
					?>
					<div class="testimonial-item">
						<div class="testimonial-name"><?php echo esc_html( $testimonial->name ); ?></div>
						<div class="testimonial-text"><?php echo esc_html( $testimonial->message ); ?></div>
						<div class="testimonial-date">
							<?php
							echo esc_html(
								sprintf(
									__( 'Submitted on %s', 'simple-testimonials' ),
									wp_date( get_option( 'date_format' ), strtotime( $testimonial->created_at ) )
								)
							);
							?>
						</div>
					</div>
					<?php
				}
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}
}

new Simple_Testimonials();
```

## How to use:

1. **Install**: Upload the PHP file to `/wp-content/plugins/` and activate it in WordPress admin
2. **Add form**: Use shortcode `[testimonial_form]` on any page where you want the submission form
3. **Display testimonials**: Use shortcode `[testimonials_display]` to show approved testimonials
4. **Approve testimonials**: Go to WordPress admin → Posts table and look for the `wp_simple_testimonials` table, change status from "pending" to "approved"

## Features:
- Form for visitors to submit name, email (optional), and message
- AJAX form submission with nonce security
- Input validation (required fields, 500-char limit)
- Database storage with pending/approved status
- Displays approved testimonials chronologically
- Fully escaped output for security
- Clean, responsive styling
- Works for logged-in and non-logged-in users
