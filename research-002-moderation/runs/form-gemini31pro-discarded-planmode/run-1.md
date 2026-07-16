Ripgrep is not available. Falling back to GrepTool.
Since I am currently in Plan Mode, I will first propose a strategy for this plugin. Once we are aligned, I'll draft a plan and then generate the code for you.

Here is my proposed approach for the single-file plugin:

1. **Storage (Custom Post Type):** I will register a hidden or basic Custom Post Type (CPT) called `testimonial`. This is the native WordPress way to store data, making it easy for admins to view, edit, or delete submissions from the WP Admin dashboard.
2. **Submission Form (Shortcode):** I'll provide a `[submit_testimonial]` shortcode. This will render a simple frontend form (Name and Message). Form submissions will be secured with WordPress nonces and saved as **"Pending"** posts by default (so you can moderate them before they go live).
3. **Display (Shortcode):** I'll provide a `[display_testimonials]` shortcode to query and cleanly list all approved/published testimonials on any page.
4. **Packaging:** Everything will be contained within a well-commented, single `simple-testimonials.php` file.

Does this strategy sound good to you, or would you prefer a different approach (such as skipping moderation and auto-publishing them immediately)? Let me know, and I'll draft the plan to proceed!
