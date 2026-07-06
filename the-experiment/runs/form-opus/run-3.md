No problem — you asked to see the code, so here's the full plugin inline. Save it as `simple-testimonials.php` in `wp-content/plugins/` (or zip it and upload via **Plugins → Add New → Upload**), then activate it.

```php
<?php
/**
 * Plugin Name:       Simple Testimonials
 * Description:        Lets visitors submit a short text testimonial through a form, stores it, and displays approved testimonials. Use the [testimonial_form] and [testimonials] shortcodes.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * Text Domain:       simple-testimonials
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Simple_Testimonials {

	/** Custom post type key. */
	const CPT = 'simple_testimonial';

	/** Nonce action name for the submission form. */
	const NONCE_ACTION = 'simple_testimonials_submit';

	/** Max length (characters) for a testimonial body. */
	const MAX_LENGTH = 600;

	/** Bootstrap. */
	public static function init() {
		$plugin = new self();

		add_action( 'init', array( $plugin, 'register_cpt' ) );

		// Handle the form submission early, before output.
		add_action( 'init', array( $plugin, 'handle_submission' ) );

		// Shortcodes.
		add_shortcode( 'testimonial_form', array( $plugin, 'render_form' ) );
		add_shortcode( 'testimonials', array( $plugin, 'render_list' ) );

		// Admin: show a "Status" column on the list table.
		add_filter( 'manage_' . self::CPT . '_posts_columns', array( $plugin, 'admin_columns' ) );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( $plugin, 'admin_column_content' ), 10, 2 );
	}

	/**
	 * Register a private custom post type to store testimonials.
	 * Pending submissions are saved as 'pending'; admins set them to 'publish' to show them.
	 */
	public function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array(
					'name'          => __( 'Testimonials', 'simple-testimonials' ),
					'singular_name' => __( 'Testimonial', 'simple-testimonials' ),
					'menu_name'     => __( 'Testimonials', 'simple-testimonials' ),
					'add_new_item'  => __( 'Add New Testimonial', 'simple-testimonials' ),
					'edit_item'     => __( 'Edit Testimonial', 'simple-testimonials' ),
					'search_items'  => __( 'Search Testimonials', 'simple-testimonials' ),
					'not_found'     => __( 'No testimonials found.', 'simple-testimonials' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-format-quote',
				'supports'        => array( 'title', 'editor' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Process a submitted testimonial. Runs on every request but bails immediately
	 * unless our form was posted. Redirects back with a status flag (PRG pattern).
	 */
	public function handle_submission() {
		if ( empty( $_POST['simple_testimonials_form'] ) ) {
			return;
		}

		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = home_url( '/' );
		}

		// Verify nonce.
		if (
			empty( $_POST['simple_testimonials_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['simple_testimonials_nonce'] ) ), self::NONCE_ACTION )
		) {
			wp_safe_redirect( add_query_arg( 'testimonial', 'error', $redirect ) );
			exit;
		}

		// Honeypot: real users leave this hidden field empty.
		if ( ! empty( $_POST['website_url'] ) ) {
			wp_safe_redirect( add_query_arg( 'testimonial', 'error', $redirect ) );
			exit;
		}

		$author  = isset( $_POST['testimonial_author'] ) ? sanitize_text_field( wp_unslash( $_POST['testimonial_author'] ) ) : '';
		$content = isset( $_POST['testimonial_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['testimonial_content'] ) ) : '';

		$author  = trim( $author );
		$content = trim( $content );

		// Basic validation.
		if ( '' === $author || '' === $content ) {
			wp_safe_redirect( add_query_arg( 'testimonial', 'error', $redirect ) );
			exit;
		}

		// Enforce a sane maximum length.
		if ( function_exists( 'mb_substr' ) ) {
			$content = mb_substr( $content, 0, self::MAX_LENGTH );
			$author  = mb_substr( $author, 0, 100 );
		} else {
			$content = substr( $content, 0, self::MAX_LENGTH );
			$author  = substr( $author, 0, 100 );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::CPT,
				'post_title'   => $author,
				'post_content' => $content,
				'post_status'  => 'pending', // Hidden until an admin approves it.
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			wp_safe_redirect( add_query_arg( 'testimonial', 'error', $redirect ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'testimonial', 'success', $redirect ) );
		exit;
	}

	/**
	 * Shortcode: [testimonial_form]
	 * Renders the public submission form (plus a success/error notice after a POST).
	 */
	public function render_form() {
		ob_start();

		// Notice after a redirect.
		if ( isset( $_GET['testimonial'] ) ) {
			$flag = sanitize_key( wp_unslash( $_GET['testimonial'] ) );
			if ( 'success' === $flag ) {
				echo '<div class="simple-testimonials-notice simple-testimonials-notice--ok">'
					. esc_html__( 'Thank you! Your testimonial has been submitted and is awaiting review.', 'simple-testimonials' )
					. '</div>';
			} elseif ( 'error' === $flag ) {
				echo '<div class="simple-testimonials-notice simple-testimonials-notice--err">'
					. esc_html__( 'Sorry, something went wrong. Please fill in both fields and try again.', 'simple-testimonials' )
					. '</div>';
			}
		}
		?>
		<form class="simple-testimonials-form" method="post" action="">
			<p>
				<label for="testimonial_author"><?php esc_html_e( 'Your name', 'simple-testimonials' ); ?></label><br />
				<input type="text" id="testimonial_author" name="testimonial_author" maxlength="100" required />
			</p>
			<p>
				<label for="testimonial_content"><?php esc_html_e( 'Your testimonial', 'simple-testimonials' ); ?></label><br />
				<textarea id="testimonial_content" name="testimonial_content" rows="4" maxlength="<?php echo esc_attr( self::MAX_LENGTH ); ?>" required></textarea>
			</p>

			<?php // Honeypot field — hidden from humans, tempting to bots. ?>
			<p style="position:absolute;left:-9999px;" aria-hidden="true">
				<label for="website_url"><?php esc_html_e( 'Leave this field empty', 'simple-testimonials' ); ?></label>
				<input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off" />
			</p>

			<?php wp_nonce_field( self::NONCE_ACTION, 'simple_testimonials_nonce' ); ?>
			<input type="hidden" name="simple_testimonials_form" value="1" />

			<p>
				<button type="submit"><?php esc_html_e( 'Submit testimonial', 'simple-testimonials' ); ?></button>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode: [testimonials limit="10"]
	 * Lists approved (published) testimonials.
	 */
	public function render_list( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit' => 10,
			),
			$atts,
			'testimonials'
		);

		$query = new WP_Query(
			array(
				'post_type'              => self::CPT,
				'post_status'            => 'publish',
				'posts_per_page'         => (int) $atts['limit'],
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( ! $query->have_posts() ) {
			return '<p class="simple-testimonials-empty">' . esc_html__( 'No testimonials yet.', 'simple-testimonials' ) . '</p>';
		}

		ob_start();
		echo '<ul class="simple-testimonials-list">';
		while ( $query->have_posts() ) {
			$query->the_post();
			?>
			<li class="simple-testimonials-item">
				<blockquote class="simple-testimonials-quote"><?php echo esc_html( get_the_content() ); ?></blockquote>
				<cite class="simple-testimonials-author">&mdash; <?php echo esc_html( get_the_title() ); ?></cite>
			</li>
			<?php
		}
		echo '</ul>';
		wp_reset_postdata();

		return ob_get_clean();
	}

	/** Add a Status column to the admin list table. */
	public function admin_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['testimonial_status'] = __( 'Status', 'simple-testimonials' );
			}
		}
		return $new;
	}

	/** Render the Status column value. */
	public function admin_column_content( $column, $post_id ) {
		if ( 'testimonial_status' !== $column ) {
			return;
		}
		if ( 'publish' === get_post_status( $post_id ) ) {
			echo '<span style="color:#1a7f37;font-weight:600;">' . esc_html__( 'Approved', 'simple-testimonials' ) . '</span>';
		} else {
			echo '<span style="color:#bb6c00;font-weight:600;">' . esc_html__( 'Pending', 'simple-testimonials' ) . '</span>';
		}
	}
}

Simple_Testimonials::init();
```

## How to use it

1. **Install & activate** — drop the file into `wp-content/plugins/simple-testimonials.php` and activate it under **Plugins**.
2. **Add the form** — put `[testimonial_form]` on any page/post where you want visitors to submit.
3. **Show testimonials** — put `[testimonials]` (optionally `[testimonials limit="5"]`) wherever you want them displayed.
4. **Approve submissions** — new entries arrive as **Pending** under the **Testimonials** menu in wp-admin. Open one and click **Publish** to make it appear publicly. (You can edit the text first if you like.)

## How it works & key design choices

- **Storage:** testimonials are saved as a custom post type (`simple_testimonial`) — no custom database tables, and you get the WP admin UI for moderation/editing for free.
- **Moderation by default:** submissions are stored as `pending`, so nothing shows publicly until you approve it. This is the single most important spam/abuse safeguard. If you'd rather auto-publish, change `'post_status' => 'pending'` to `'publish'` in `handle_submission()`.
- **Security:** nonce verification (CSRF), a honeypot field to deter bots, input sanitization on save (`sanitize_text_field` / `sanitize_textarea_field`), output escaping on display (`esc_html`), and a length cap.
- **Post/Redirect/Get:** after submit it redirects with a `?testimonial=success|error` flag so a page refresh won't resubmit.

A couple of optional upgrades if you want them later: store the submitter's email as post meta, add a simple CSS file, send the admin an email on each new submission, or rate-limit submissions per IP. Want any of those added?
