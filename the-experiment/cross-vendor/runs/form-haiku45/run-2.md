I've created a complete single-file WordPress plugin. Here's what it does:

## Features

**Form & Collection:**
- AJAX form for visitors to submit name and testimonial (up to 500 characters)
- Built-in validation and security (nonce verification, sanitization)
- Success/error messaging with auto-dismiss

**Storage:**
- Saves testimonials as custom post type (non-public, hidden from frontend)
- Stores author name as post title, testimonial text as content

**Display:**
- `[testimonials_display]` shortcode shows all testimonials in a responsive grid
- Customizable via shortcode attributes: `limit`, `orderby`, `order`
- Example: `[testimonials_display limit="20" order="ASC"]`

**Styling:**
- Responsive grid layout (auto-fills based on screen width)
- Styled form with focus states and validation feedback
- Testimonial cards with clean design

## How to Use

1. Copy the code to `wp-content/plugins/testimonials-plugin.php`
2. Activate in WordPress admin
3. Add `[testimonial_form]` shortcode to a page where you want the submission form
4. Add `[testimonials_display]` shortcode to show submitted testimonials
5. Testimonials can be managed (deleted, edited) in WordPress admin under a new "Testimonials" menu item

The plugin uses WordPress best practices: nonce verification, input sanitization, proper escaping, and AJAX for smooth UX.
