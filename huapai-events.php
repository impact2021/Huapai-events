<?php
/**
 * Plugin Name: Huapai Events
 * Plugin URI: https://github.com/impact2021/Huapai-events
 * Description: A WordPress plugin to manage Facebook events with a shortcode to display upcoming events
 * Version: 2.0
 * Author: Impact Websites
 * License: GPL v2 or later
 * Text Domain: huapai-events
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HUAPAI_EVENTS_VERSION', '2.0');
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
 * Fetch metadata from a given URL
 */
function huapai_events_fetch_url_metadata($url) {
    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return array('error' => 'Invalid URL');
    }
    
    // Fetch the URL content
    $response = wp_remote_get($url, array(
        'timeout' => 15,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ));
    
    if (is_wp_error($response)) {
        return array('error' => 'Failed to fetch URL: ' . $response->get_error_message());
    }
    
    $html = wp_remote_retrieve_body($response);
    
    if (empty($html)) {
        return array('error' => 'No content retrieved from URL');
    }
    
    // Parse Open Graph and meta tags
    $metadata = array(
        'title' => '',
        'description' => '',
        'image' => '',
        'date' => ''
    );
    
    // Use DOMDocument to parse HTML
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    // Handle UTF-8 encoding properly
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    
    $xpath = new DOMXPath($dom);
    
    // Try to get Open Graph tags first (commonly used by Facebook)
    $og_title = $xpath->query('//meta[@property="og:title"]/@content');
    if ($og_title->length > 0) {
        $metadata['title'] = $og_title->item(0)->nodeValue;
    }
    
    $og_description = $xpath->query('//meta[@property="og:description"]/@content');
    if ($og_description->length > 0) {
        $metadata['description'] = $og_description->item(0)->nodeValue;
    }
    
    $og_image = $xpath->query('//meta[@property="og:image"]/@content');
    if ($og_image->length > 0) {
        $metadata['image'] = $og_image->item(0)->nodeValue;
    }
    
    // Try to get event-specific data
    $event_start_time = $xpath->query('//meta[@property="event:start_time"]/@content');
    if ($event_start_time->length > 0) {
        $metadata['date'] = $event_start_time->item(0)->nodeValue;
    }
    
    // Fallback to standard meta tags if Open Graph tags are not available
    if (empty($metadata['title'])) {
        $title = $xpath->query('//meta[@name="title"]/@content');
        if ($title->length > 0) {
            $metadata['title'] = $title->item(0)->nodeValue;
        } else {
            $title_tag = $xpath->query('//title');
            if ($title_tag->length > 0) {
                $metadata['title'] = $title_tag->item(0)->nodeValue;
            }
        }
    }
    
    if (empty($metadata['description'])) {
        $description = $xpath->query('//meta[@name="description"]/@content');
        if ($description->length > 0) {
            $metadata['description'] = $description->item(0)->nodeValue;
        }
    }
    
    // Check if we got at least some data
    if (empty($metadata['title']) && empty($metadata['description'])) {
        return array('error' => 'Could not extract event information from URL');
    }
    
    // Sanitize extracted metadata for security
    $metadata['title'] = sanitize_text_field($metadata['title']);
    // Use sanitize_textarea_field to preserve newlines in description
    $metadata['description'] = sanitize_textarea_field($metadata['description']);
    $metadata['image'] = esc_url_raw($metadata['image']);
    $metadata['date'] = sanitize_text_field($metadata['date']);
    
    return $metadata;
}

/**
 * AJAX handler to fetch event data from URL
 */
function huapai_events_fetch_url_data() {
    check_ajax_referer('huapai_fetch_url_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
        return;
    }
    
    $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
    
    if (empty($url)) {
        wp_send_json_error(array('message' => 'No URL provided'));
        return;
    }
    
    $metadata = huapai_events_fetch_url_metadata($url);
    
    if (isset($metadata['error'])) {
        wp_send_json_error(array('message' => $metadata['error']));
        return;
    }
    
    wp_send_json_success($metadata);
}
add_action('wp_ajax_huapai_fetch_url_data', 'huapai_events_fetch_url_data');

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
    
    // Handle form submission
    if (isset($_POST['huapai_add_event']) && check_admin_referer('huapai_add_event_action', 'huapai_add_event_nonce')) {
        $title = sanitize_text_field($_POST['event_title']);
        $content = wp_kses_post($_POST['event_content']);
        $event_date = sanitize_text_field($_POST['event_date']);
        $featured_image = esc_url_raw($_POST['featured_image']);
        $fb_event_url = esc_url_raw($_POST['fb_event_url']);
        
        if (!empty($title) && !empty($event_date)) {
            $wpdb->insert(
                $table_name,
                array(
                    'title' => $title,
                    'content' => $content,
                    'event_date' => $event_date,
                    'featured_image' => $featured_image,
                    'fb_event_url' => $fb_event_url
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );
            echo '<div class="notice notice-success"><p>Event added successfully!</p></div>';
            // Clear any duplicate transient
            delete_transient('huapai_duplicate_event_' . get_current_user_id());
        } else {
            echo '<div class="notice notice-error"><p>Title and Event Date are required!</p></div>';
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
    
    // Get all events
    // Note: $table_name is safe - constructed from $wpdb->prefix
    $all_events = $wpdb->get_results("SELECT id, title, content, event_date, featured_image, fb_event_url FROM $table_name ORDER BY event_date DESC");
    
    // Separate into upcoming and past events
    $current_time = current_time('mysql');
    $upcoming_events = array();
    $past_events = array();
    
    foreach ($all_events as $event) {
        if ($event->event_date >= $current_time) {
            $upcoming_events[] = $event;
        } else {
            $past_events[] = $event;
        }
    }
    
    ?>
    <div class="wrap huapai-admin-wrap">
        <h1>Huapai Events Manager</h1>
        
        <div class="huapai-admin-container">
            <div class="huapai-admin-main">
                <h2>Add New Event</h2>
                <form method="post" action="" id="huapai-event-form">
                    <?php wp_nonce_field('huapai_add_event_action', 'huapai_add_event_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="fb_event_url">Event URL</label></th>
                            <td>
                                <input type="url" name="fb_event_url" id="fb_event_url" class="regular-text" 
                                       value="<?php echo $duplicate_data ? esc_attr($duplicate_data['fb_event_url']) : ''; ?>"
                                       placeholder="https://facebook.com/events/..." style="margin-bottom: 10px;">
                                <br>
                                <button type="button" id="huapai_fetch_event_data" class="button button-secondary">Fetch Event Data</button>
                                <p class="description">Enter a Facebook event URL (or other event page URL) and click "Fetch Event Data" to automatically fill in the details below.</p>
                                <div id="huapai_fetch_status"></div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="event_title">Event Title *</label></th>
                            <td><input type="text" name="event_title" id="event_title" class="regular-text" 
                                       value="<?php echo $duplicate_data ? esc_attr($duplicate_data['title']) : ''; ?>" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="event_content">Event Description</label></th>
                            <td>
                                <?php 
                                wp_editor($duplicate_data ? $duplicate_data['content'] : '', 'event_content', array(
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
                            <td><input type="datetime-local" name="event_date" id="event_date" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="featured_image">Featured Image URL</label></th>
                            <td>
                                <input type="url" name="featured_image" id="featured_image" class="regular-text" 
                                       value="<?php echo $duplicate_data ? esc_attr($duplicate_data['featured_image']) : ''; ?>"
                                       placeholder="https://...">
                                <p class="description">Direct URL to the event image</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="huapai_add_event" id="submit" class="button button-primary" value="Add Event">
                    </p>
                </form>
                
                <h2>All Events</h2>
                <p><strong>Shortcode:</strong> Use <code>[huapai_events]</code> to display upcoming events on any page or post.</p>
                
                <?php if ($all_events): ?>
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
                            <?php foreach ($all_events as $event): ?>
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
                    <p>No events found. Add your first event above!</p>
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
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=huapai-events&action=duplicate&event_id=' . $event->id), 'huapai_duplicate_event_' . $event->id); ?>" 
                                               class="button button-small">Duplicate</a>
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
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=huapai-events&action=duplicate&event_id=' . $event->id), 'huapai_duplicate_event_' . $event->id); ?>" 
                                               class="button button-small">Duplicate</a>
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
                            <a href="<?php echo esc_url($event->fb_event_url); ?>" target="_blank" rel="noopener noreferrer" class="huapai-event-button">View Event Details</a>
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
        wp_enqueue_style('huapai-events-admin-style', HUAPAI_EVENTS_PLUGIN_URL . 'assets/css/huapai-events-admin.css', array(), HUAPAI_EVENTS_VERSION);
        wp_enqueue_script('huapai-events-admin-script', HUAPAI_EVENTS_PLUGIN_URL . 'assets/js/huapai-events-admin.js', array('jquery'), HUAPAI_EVENTS_VERSION, true);
        
        // Pass AJAX URL and nonce to JavaScript
        wp_localize_script('huapai-events-admin-script', 'huapaiEventsAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('huapai_fetch_url_nonce')
        ));
    }
}
add_action('admin_enqueue_scripts', 'huapai_events_admin_styles');
