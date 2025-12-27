<?php
/**
 * Plugin Name: Huapai Events
 * Plugin URI: https://github.com/impact2021/Huapai-events
 * Description: A WordPress plugin to manage Facebook events with a shortcode to display upcoming events
 * Version: 3.0
 * Author: Impact Websites
 * License: GPL v2 or later
 * Text Domain: huapai-events
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HUAPAI_EVENTS_VERSION', '3.0');
define('HUAPAI_EVENTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HUAPAI_EVENTS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin activation hook
 */
function huapai_events_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'huapai_events';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        content text NOT NULL,
        event_date datetime NOT NULL,
        featured_image varchar(500) DEFAULT NULL,
        fb_event_url varchar(500) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY event_date (event_date)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'huapai_events_activate');



/**
 * Add admin menu
 */
function huapai_events_admin_menu() {
    add_menu_page(
        'Huapai Events',
        'Huapai Events',
        'manage_options',
        'huapai-events',
        'huapai_events_admin_page',
        'dashicons-calendar-alt',
        30
    );
}
add_action('admin_menu', 'huapai_events_admin_menu');

/**
 * Admin page content
 */
function huapai_events_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'huapai_events';
    
    // Show update success message if redirected after update
    if (isset($_GET['updated']) && $_GET['updated'] === '1') {
        echo '<div class="notice notice-success"><p>Event updated successfully!</p></div>';
    }
    
    // Get edit event ID if present
    $edit_event_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $edit_event = null;
    
    if ($edit_event_id && isset($_GET['action']) && $_GET['action'] === 'edit' && check_admin_referer('huapai_edit_event_' . $edit_event_id)) {
        // Note: $table_name is safe - constructed from $wpdb->prefix
        $edit_event = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_event_id));
        if (!$edit_event) {
            echo '<div class="notice notice-error"><p>Event not found!</p></div>';
            $edit_event_id = 0;
        }
    }
    
    // Handle event duplication
    if (isset($_GET['action']) && $_GET['action'] === 'duplicate' && isset($_GET['event_id']) && check_admin_referer('huapai_duplicate_event_' . intval($_GET['event_id']))) {
        $event_id = intval($_GET['event_id']);
        // Note: $table_name is safe - constructed from $wpdb->prefix
        $event = $wpdb->get_row($wpdb->prepare("SELECT title, content, featured_image, fb_event_url FROM $table_name WHERE id = %d", $event_id));
        
        if ($event) {
            // Store the event data in a transient for the form to pick up
            set_transient('huapai_duplicate_event_' . get_current_user_id(), array(
                'title' => $event->title . ' (Copy)',
                'content' => $event->content,
                'featured_image' => $event->featured_image,
                'fb_event_url' => $event->fb_event_url
            ), 300); // 5 minutes
            echo '<div class="notice notice-success"><p>Event ready to duplicate! Update the date and click Add Event.</p></div>';
        }
    }
    
    // Handle form submission for add/edit
    if (isset($_POST['huapai_save_event']) && check_admin_referer('huapai_save_event_action', 'huapai_save_event_nonce')) {
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $title = sanitize_text_field($_POST['event_title']);
        $content = wp_kses_post($_POST['event_content']);
        $event_date_only = sanitize_text_field($_POST['event_date']);
        $event_time = sanitize_text_field($_POST['event_time']);
        $featured_image = esc_url_raw($_POST['featured_image']);
        $fb_event_url = esc_url_raw($_POST['fb_event_url']);
        
        // Validate time format (HH:MM)
        if (!empty($event_time) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $event_time)) {
            echo '<div class="notice notice-error"><p>Invalid time format. Please use HH:MM format.</p></div>';
            $event_time = ''; // Reset to trigger validation error below
        }
        
        // Combine date and time into datetime
        $event_datetime = $event_date_only . ' ' . $event_time . ':00';
        
        if (!empty($title) && !empty($event_date_only) && !empty($event_time)) {
            if ($event_id > 0) {
                // Update existing event
                $wpdb->update(
                    $table_name,
                    array(
                        'title' => $title,
                        'content' => $content,
                        'event_date' => $event_datetime,
                        'featured_image' => $featured_image,
                        'fb_event_url' => $fb_event_url
                    ),
                    array('id' => $event_id),
                    array('%s', '%s', '%s', '%s', '%s'),
                    array('%d')
                );
                // Redirect to clear the edit parameters
                wp_safe_redirect(admin_url('admin.php?page=huapai-events&updated=1'));
                exit;
            } else {
                // Insert new event
                $wpdb->insert(
                    $table_name,
                    array(
                        'title' => $title,
                        'content' => $content,
                        'event_date' => $event_datetime,
                        'featured_image' => $featured_image,
                        'fb_event_url' => $fb_event_url
                    ),
                    array('%s', '%s', '%s', '%s', '%s')
                );
                echo '<div class="notice notice-success"><p>Event added successfully!</p></div>';
                // Clear any duplicate transient
                delete_transient('huapai_duplicate_event_' . get_current_user_id());
            }
        } else {
            echo '<div class="notice notice-error"><p>Title, Event Date, and Event Time are required!</p></div>';
        }
    }
    
    // Handle event deletion
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['event_id']) && check_admin_referer('huapai_delete_event_' . intval($_GET['event_id']))) {
        $event_id = intval($_GET['event_id']);
        $wpdb->delete($table_name, array('id' => $event_id), array('%d'));
        echo '<div class="notice notice-success"><p>Event deleted successfully!</p></div>';
    }
    
    // Get duplicate event data if available
    $duplicate_data = get_transient('huapai_duplicate_event_' . get_current_user_id());
    
    // Handle search
    $search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    
    // Get all events with optional search filter
    // Note: $table_name is safe - constructed from $wpdb->prefix
    if (!empty($search_query)) {
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE title LIKE %s OR content LIKE %s ORDER BY event_date DESC",
            '%' . $wpdb->esc_like($search_query) . '%',
            '%' . $wpdb->esc_like($search_query) . '%'
        ));
    } else {
        $events = $wpdb->get_results("SELECT * FROM $table_name ORDER BY event_date DESC");
    }
    
    // Separate into upcoming and past events
    $current_time = current_time('mysql');
    $upcoming_events = array();
    $past_events = array();
    
    foreach ($events as $event) {
        if ($event->event_date >= $current_time) {
            $upcoming_events[] = $event;
        } else {
            $past_events[] = $event;
        }
    }
    
    // Prepare form values
    $form_title = '';
    $form_content = '';
    $form_date = '';
    $form_time = '15:00';
    $form_image = '';
    $form_fb_url = '';
    $form_id = 0;
    $form_heading = 'Add New Event';
    $form_button_text = 'Add Event';
    
    if ($edit_event) {
        $form_title = $edit_event->title;
        $form_content = $edit_event->content;
        $datetime_parts = explode(' ', $edit_event->event_date);
        $form_date = isset($datetime_parts[0]) ? $datetime_parts[0] : '';
        $form_time = isset($datetime_parts[1]) ? substr($datetime_parts[1], 0, 5) : '15:00'; // Get HH:MM or default
        $form_image = $edit_event->featured_image;
        $form_fb_url = $edit_event->fb_event_url;
        $form_id = $edit_event->id;
        $form_heading = 'Edit Event';
        $form_button_text = 'Update Event';
    } elseif ($duplicate_data) {
        $form_title = $duplicate_data['title'];
        $form_content = $duplicate_data['content'];
        $form_image = $duplicate_data['featured_image'];
        $form_fb_url = $duplicate_data['fb_event_url'];
    }
    
    ?>
    <div class="wrap huapai-admin-wrap">
        <h1>Huapai Events Manager</h1>
        
        <div class="huapai-admin-container">
            <div class="huapai-admin-main">
                <h2><?php echo esc_html($form_heading); ?></h2>
                <form method="post" action="" id="huapai-event-form">
                    <?php wp_nonce_field('huapai_save_event_action', 'huapai_save_event_nonce'); ?>
                    <input type="hidden" name="event_id" value="<?php echo esc_attr($form_id); ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="event_title">Event Title *</label></th>
                            <td><input type="text" name="event_title" id="event_title" class="regular-text" 
                                       value="<?php echo esc_attr($form_title); ?>" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="event_content">Event Description</label></th>
                            <td>
                                <?php 
                                wp_editor($form_content, 'event_content', array(
                                    'textarea_name' => 'event_content',
                                    'media_buttons' => false,
                                    'textarea_rows' => 5,
                                    'teeny' => true
                                )); 
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="event_date">Event Date *</label></th>
                            <td><input type="date" name="event_date" id="event_date" value="<?php echo esc_attr($form_date); ?>" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="event_time">Event Time *</label></th>
                            <td>
                                <input type="time" name="event_time" id="event_time" value="<?php echo esc_attr($form_time); ?>" required>
                                <p class="description">Defaults to 3:00 PM</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="featured_image">Featured Image</label></th>
                            <td>
                                <input type="url" name="featured_image" id="featured_image" class="regular-text" 
                                       value="<?php echo esc_attr($form_image); ?>" placeholder="https://...">
                                <button type="button" class="button" id="upload_image_button">Select from Media Library</button>
                                <p class="description">Choose an image from the media library or enter a URL</p>
                                <div id="image_preview_container" style="margin-top: 10px;">
                                    <?php if ($form_image): ?>
                                        <img src="<?php echo esc_url($form_image); ?>" style="max-width: 200px; height: auto; display: block;">
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="fb_event_url">Facebook Event URL</label></th>
                            <td>
                                <input type="url" name="fb_event_url" id="fb_event_url" class="regular-text" 
                                       value="<?php echo esc_attr($form_fb_url); ?>" placeholder="https://facebook.com/events/...">
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="huapai_save_event" id="submit" class="button button-primary" value="<?php echo esc_attr($form_button_text); ?>">
                        <?php if ($edit_event): ?>
                            <a href="<?php echo admin_url('admin.php?page=huapai-events'); ?>" class="button">Cancel</a>
                        <?php endif; ?>
                    </p>
                </form>
                
                <h2>All Events</h2>
                <div style="margin-bottom: 15px;">
                    <form method="get" action="">
                        <input type="hidden" name="page" value="huapai-events">
                        <input type="text" name="search" id="event_search" value="<?php echo esc_attr($search_query); ?>" placeholder="Search events..." style="width: 300px;">
                        <input type="submit" class="button" value="Search">
                        <?php if (!empty($search_query)): ?>
                            <a href="<?php echo admin_url('admin.php?page=huapai-events'); ?>" class="button">Clear Search</a>
                        <?php endif; ?>
                    </form>
                </div>
                <p><strong>Shortcode:</strong> Use <code>[huapai_events]</code> to display upcoming events on any page or post.</p>
                
                <?php if ($events): ?>
                    <?php if (!empty($search_query)): ?>
                        <p><em>Found <?php echo count($events); ?> event(s) matching "<?php echo esc_html($search_query); ?>"</em></p>
                    <?php endif; ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Event Date</th>
                                <th>Featured Image</th>
                                <th>FB Event URL</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?php echo esc_html($event->id); ?></td>
                                    <td><?php echo esc_html($event->title); ?></td>
                                    <td><?php echo esc_html($event->event_date); ?></td>
                                    <td>
                                        <?php if ($event->featured_image): ?>
                                            <img src="<?php echo esc_url($event->featured_image); ?>" style="max-width: 50px; height: auto;">
                                        <?php else: ?>
                                            No image
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($event->fb_event_url): ?>
                                            <a href="<?php echo esc_url($event->fb_event_url); ?>" target="_blank">View</a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=huapai-events&action=edit&edit_id=' . $event->id), 'huapai_edit_event_' . $event->id); ?>" 
                                           class="button button-small">Edit</a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=huapai-events&action=duplicate&event_id=' . $event->id), 'huapai_duplicate_event_' . $event->id); ?>" 
                                           class="button button-small">Duplicate</a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=huapai-events&action=delete&event_id=' . $event->id), 'huapai_delete_event_' . $event->id); ?>" 
                                           onclick="return confirm('Are you sure you want to delete this event?');" 
                                           class="button button-small">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No events found. <?php echo !empty($search_query) ? 'Try a different search term.' : 'Add your first event above!'; ?></p>
                <?php endif; ?>
            </div>
            
            <div class="huapai-admin-sidebar">
                <div class="huapai-sidebar-widget">
                    <h3>Quick View</h3>
                    
                    <div class="huapai-tabs">
                        <button class="huapai-tab-button active" data-tab="upcoming">Upcoming Events (<?php echo count($upcoming_events); ?>)</button>
                        <button class="huapai-tab-button" data-tab="past">Past Events (<?php echo count($past_events); ?>)</button>
                    </div>
                    
                    <div id="huapai-tab-upcoming" class="huapai-tab-content active">
                        <?php if ($upcoming_events): ?>
                            <div class="huapai-event-list">
                                <?php foreach ($upcoming_events as $event): ?>
                                    <div class="huapai-sidebar-event">
                                        <div class="huapai-sidebar-event-title"><?php echo esc_html($event->title); ?></div>
                                        <div class="huapai-sidebar-event-date">
                                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event->event_date))); ?>
                                        </div>
                                        <div class="huapai-sidebar-event-actions">
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=huapai-events&action=edit&edit_id=' . $event->id), 'huapai_edit_event_' . $event->id); ?>" 
                                               class="button button-small">Edit</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="huapai-sidebar-empty">No upcoming events</p>
                        <?php endif; ?>
                    </div>
                    
                    <div id="huapai-tab-past" class="huapai-tab-content">
                        <?php if ($past_events): ?>
                            <div class="huapai-event-list">
                                <?php foreach ($past_events as $event): ?>
                                    <div class="huapai-sidebar-event">
                                        <div class="huapai-sidebar-event-title"><?php echo esc_html($event->title); ?></div>
                                        <div class="huapai-sidebar-event-date">
                                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event->event_date))); ?>
                                        </div>
                                        <div class="huapai-sidebar-event-actions">
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=huapai-events&action=edit&edit_id=' . $event->id), 'huapai_edit_event_' . $event->id); ?>" 
                                               class="button button-small">Edit</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="huapai-sidebar-empty">No past events</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Shortcode to display upcoming events
 */
function huapai_events_shortcode($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'huapai_events';
    
    // Get attributes
    $atts = shortcode_atts(array(
        'limit' => 10,
        'order' => 'ASC'
    ), $atts);
    
    // Get upcoming events only (events in the future)
    // Note: $table_name is safe - constructed from $wpdb->prefix (WordPress internal)
    // Table names cannot be parameterized in SQL prepared statements
    $current_time = current_time('mysql');
    $events = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE event_date >= %s ORDER BY event_date " . sanitize_sql_orderby($atts['order']) . " LIMIT %d",
        $current_time,
        intval($atts['limit'])
    ));
    
    if (!$events) {
        return '<p class="huapai-no-events">No upcoming events at this time.</p>';
    }
    
    ob_start();
    ?>
    <div class="huapai-events-container">
        <?php foreach ($events as $event): 
            // Strip HTML tags from content for character counting
            $plain_content = strip_tags($event->content);
            $is_long_description = strlen($plain_content) > 200;
            $short_description = $is_long_description ? substr($plain_content, 0, 200) . '...' : $plain_content;
        ?>
            <div class="huapai-event-item">
                <?php if ($event->featured_image): ?>
                    <div class="huapai-event-image">
                        <img src="<?php echo esc_url($event->featured_image); ?>" alt="<?php echo esc_attr($event->title); ?>">
                    </div>
                <?php endif; ?>
                
                <div class="huapai-event-content">
                    <h3 class="huapai-event-title"><?php echo esc_html($event->title); ?></h3>
                    
                    <div class="huapai-event-date">
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event->event_date))); ?>
                    </div>
                    
                    <?php if ($event->content): ?>
                        <div class="huapai-event-description">
                            <?php if ($is_long_description): ?>
                                <div class="huapai-description-short"><?php echo esc_html($short_description); ?></div>
                                <div class="huapai-description-full" style="display: none;"><?php echo wp_kses_post($event->content); ?></div>
                                <button class="huapai-read-more-btn" aria-expanded="false">Read More</button>
                            <?php else: ?>
                                <?php echo wp_kses_post($event->content); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($event->fb_event_url): ?>
                        <div class="huapai-event-link">
                            <a href="<?php echo esc_url($event->fb_event_url); ?>" target="_blank" rel="noopener noreferrer" class="huapai-event-button">View on Facebook</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('huapai_events', 'huapai_events_shortcode');

/**
 * Enqueue frontend styles
 */
function huapai_events_enqueue_styles() {
    if (!is_admin()) {
        $post = get_post();
        if ($post && has_shortcode($post->post_content, 'huapai_events')) {
            wp_enqueue_style('huapai-events-style', HUAPAI_EVENTS_PLUGIN_URL . 'assets/css/huapai-events.css', array(), HUAPAI_EVENTS_VERSION);
            wp_enqueue_script('huapai-events-script', HUAPAI_EVENTS_PLUGIN_URL . 'assets/js/huapai-events.js', array('jquery'), HUAPAI_EVENTS_VERSION, true);
        }
    }
}
add_action('wp_enqueue_scripts', 'huapai_events_enqueue_styles');

/**
 * Enqueue admin styles and scripts
 */
function huapai_events_admin_styles($hook) {
    if ($hook === 'toplevel_page_huapai-events') {
        wp_enqueue_media(); // Enqueue WordPress media library
        wp_enqueue_style('huapai-events-admin-style', HUAPAI_EVENTS_PLUGIN_URL . 'assets/css/huapai-events-admin.css', array(), HUAPAI_EVENTS_VERSION);
        wp_enqueue_script('huapai-events-admin-script', HUAPAI_EVENTS_PLUGIN_URL . 'assets/js/huapai-events-admin.js', array('jquery'), HUAPAI_EVENTS_VERSION, true);
    }
}
add_action('admin_enqueue_scripts', 'huapai_events_admin_styles');
