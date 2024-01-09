<?php
/*
Plugin Name: Website Management Plugin
Description: Custom plugin for managing websites.
Version: 1.0
*/


function website_form_shortcode() {
    ob_start();
    ?>
     <form id="testform"  action="<?php echo esc_url(home_url('/your-website/')); ?>" method="post">
   
       <p> <label for="visitor_name">Name: </label><br>
        <input type="text" name="visitor_name" required>
       
</p>
   <p>     <label for="website_url">Website URL:</label><br>
        <input type="url" name="website_url" required>
</p>
<p>
        <input type="submit" value="Submit">
  </p>
  </form>
    <?php
    return ob_get_clean();
}

add_shortcode('website_form', 'website_form_shortcode');





// Handle form submissions and create post
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

        // Check if there's a redirect URL
        if (isset($_POST['website_url']) && !empty($_POST['website_url'])) {
            // Redirect to the specified URL
            wp_redirect($_POST['website_url']);
            exit;
        }
    }
}

add_action('init', 'handle_website_form_submission');








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
        'register_meta_box_cb' => 'customize_website_edit_screen',
    );

    register_post_type('websites', $args);
}

add_action('init', 'register_websites_post_type');


// Display the source code metabox
function display_website_source_code_metabox($post) {
    $website_source_code = get_post_meta($post->ID, '_website_source_code', true);
    $website_url = get_post_meta($post->ID, '_website_url', true);
    ?>
  <!--  <label for="website_url">Website URL:</label>
     <input type="text" id="website_url" name="website_url" value="<?php echo esc_url($website_url); ?>" readonly> -->

    <label for="website_source_code">Website Source Code:</label>
    <textarea id="website_source_code" name="website_source_code" rows="8" style="width: 100%;"><?php echo esc_textarea($website_url); ?></textarea>
    <?php
}

// Save the source code when the post is saved
function save_website_source_code($post_id) {
    if (isset($_POST['website_source_code'])) {
        $website_source_code = sanitize_textarea_field($_POST['website_source_code']);
        update_post_meta($post_id, '_website_source_code', $website_source_code);
    }
}

// Hook into the 'add_meta_boxes' action
add_action('add_meta_boxes', 'customize_website_edit_screen');

// Hook into the 'save_post' action
add_action('save_post', 'save_website_source_code');



// Modify columns in admin post list view for custom post type WEBSITES
function customize_website_columns($columns) {
    $columns['title'] = 'Name';
    $columns['website_url'] = 'Website URL';
    $columns['date'] = 'Date';

    // Reorder the columns, moving 'date' to the last position
    $date_column = $columns['date'];
    unset($columns['date']);
    $columns['date'] = $date_column;

    return $columns;
}

add_filter('manage_websites_posts_columns', 'customize_website_columns');

// Display data in custom columns for admin post list view
function display_website_columns($column, $post_id) {
    switch ($column) {
        case 'website_url':
            echo esc_url(get_post_meta($post_id, '_website_url', true));
            break;

        // Add more cases for additional columns if needed

        default:
            break;
    }
}

add_action('manage_websites_posts_custom_column', 'display_website_columns', 10, 2);



// Remove "Add New" submenu for WEBSITES post type
function remove_websites_add_new_submenu() {
    remove_submenu_page('edit.php?post_type=websites', 'post-new.php?post_type=websites');
}

add_action('admin_menu', 'remove_websites_add_new_submenu');


// Modify the edit screen for a WEBSITE
function customize_website_edit_screen() {
    // Remove all standard metaboxes
    remove_post_type_support('websites', 'editor');
    remove_post_type_support('websites', 'thumbnail');

    // Remove "Publish" metabox
    remove_meta_box('submitdiv', 'websites', 'side');

    // Add your custom metabox
    add_meta_box('website_source_code', 'Website Source Code', 'display_website_source_code_metabox', 'websites', 'normal', 'high');
}




// Register the REST API endpoint
function register_websites_rest_route() {
    register_rest_route('custom/v1', '/websites/', array(
        'methods' => 'GET',
        'callback' => 'get_websites_data',
    ));
}

add_action('rest_api_init', 'register_websites_rest_route');



// Restrict access to source code based on user role
function restrict_source_code_access($post_id) {
    $post_type = 'websites';

    if (!current_user_can('administrator') && (current_user_can('editor') && get_post_type($post_id) === $post_type)) {
        // Editors can only see the name, not the source code
        remove_meta_box('website_source_code', $post_type, 'normal');
    }
}

add_action('add_meta_boxes', 'restrict_source_code_access');

// Remove textarea for Editors in the edit screen
function remove_textarea_for_editors() {
    $post_type = 'websites';

    if (!current_user_can('administrator') && current_user_can('editor')) {
        echo '<style>
            #website_source_code { display: none !important; }
        </style>';
    }
}

add_action('admin_head', 'remove_textarea_for_editors');












// Callback function to get WEBSITES data
function get_websites_data($data) {
    $args = array(
        'post_type' => 'websites',
        'posts_per_page' => -1,
    );

    $websites_query = new WP_Query($args);
    $websites = array();

    if ($websites_query->have_posts()) {
        while ($websites_query->have_posts()) {
            $websites_query->the_post();
            $website_id = get_the_ID();
            $website_title = get_the_title();
            $website_url = get_post_meta($website_id, '_website_url', true);

            $websites[] = array(
                'id' => $website_id,
                'title' => $website_title,
                'url' => $website_url,
            );
        }
        wp_reset_postdata();
    }

    return rest_ensure_response($websites);
}