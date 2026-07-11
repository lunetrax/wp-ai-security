Ripgrep is not available. Falling back to GrepTool.
I propose creating a single-file WordPress plugin (`simple-testimonials.php`) with the following approach:

1. **Custom Post Type:** Register a `testimonial` custom post type to natively store and manage the submissions in the WordPress backend.
2. **Submission Form Shortcode:** Create a `[testimonial_form]` shortcode that renders a simple frontend form for visitors to submit their testimonial.
3. **Form Handling:** Securely process form submissions (using nonces and sanitization) and save them as `pending` testimonials so you can review them before they appear on the site.
4. **Display Shortcode:** Create a `[testimonials_list]` shortcode to query and display only the `publish`ed testimonials.

Does this strategy sound good, or would you like to automatically publish them upon submission? Once we agree on the approach, I'll draft the plan and generate the full code.
