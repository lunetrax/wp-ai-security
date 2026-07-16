Ripgrep is not available. Falling back to GrepTool.
I am currently in Plan Mode, which means I need to create a plan and get your approval before I can generate or write the source code. 

Here is my proposed strategy for the single-file WordPress plugin:

1. **Data Storage:** Register a minimal Custom Post Type (CPT) called `testimonial`. This is the WordPress-standard way to store text entries and allows you to easily manage/delete them from the WP Admin dashboard.
2. **Shortcodes:** We'll include two shortcodes:
   - `[testimonial_form]`: Renders a simple HTML form (Name and Message) for visitors to submit their testimonial. 
   - `[testimonial_list]`: Loops through and displays the published testimonials.
3. **Security & Moderation:** Form submissions will be secured with nonces and sanitized. By default, submitted testimonials will be saved as "Pending" so an admin can review and publish them to prevent spam from appearing live (I can change this to publish immediately if you prefer).

Does this approach sound good to you? Once you confirm, I will draft the formal implementation plan and we can proceed to generate the full plugin code.
