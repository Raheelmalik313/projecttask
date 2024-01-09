<?php
/*
Plugin Name: Website Management Plugin
Description: Custom plugin for managing websites.
Version: 1.0
*/

function website_form_shortcode() {
    ob_start();
    ?>
    <form action="<?php echo esc_url(home_url('/submit-website/')); ?>" method="post">
        <label for="visitor_name">Name:</label>
        <input type="text" name="visitor_name" required>
        
        <label for="website_url">Website URL:</label>
        <input type="url" name="website_url" required>

        <input type="submit" value="Submit">
    </form>
    <?php
    return ob_get_clean();
}

add_shortcode('website_form', 'website_form_shortcode');





// Register custom post type WEBSITES
function register_websites_post_type() {
    $labels = array(
        'name'               => _x('WEBSITES', 'post type general name'),
        'singular_name'      => _x('WEBSITE', 'post type singular name'),
        'menu_name'          => _x('WEBSITES', 'admin menu'),
        'add_new'            => _x('Add New', 'WEBSITE'),
        'add_new_item'       => __('Add New WEBSITE'),
        'new_item'           => __('New WEBSITE'),
        'edit_item'          => __('Edit WEBSITE'),
        'view_item'          => __('View WEBSITE'),
        'all_items'          => __('All WEBSITES'),
        'search_items'       => __('Search WEBSITES'),
        'not_found'          => __('No WEBSITES found'),
        'not_found_in_trash' => __('No WEBSITES found in the Trash'),
        'parent_item_colon'  => '',
        'menu_icon'          => 'dashicons-admin-site',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'websites'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title'),
    );

    register_post_type('websites', $args);
}

add_action('init', 'register_websites_post_type');


function handle_website_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['visitor_name']) && isset($_POST['website_url'])) {
        $visitor_name = sanitize_text_field($_POST['visitor_name']);
        $website_url = esc_url($_POST['website_url']);

        // Create a new post of custom type WEBSITES
        $post_id = wp_insert_post(array(
            'post_title'   => $visitor_name,
            'post_type'    => 'websites',
            'post_status'  => 'publish',
        ));

        // Save the website URL as post meta
        update_post_meta($post_id, '_website_url', $website_url);
    }
}

add_action('init', 'handle_website_form_submission');