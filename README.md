# Huapai Events 2.0

A WordPress plugin to manage and display events from Facebook on your WordPress website.

## Version 2.0 Features

### Frontend Display
- **Modern Two-Column Layout**: Events display with images on the left (300px) and content on the right
- **Smart Description Truncation**: Long descriptions automatically truncate to 200 characters with a "Read More" button
- **Expandable Content**: Click "Read More" to see full descriptions, "Read Less" to collapse
- **Clean Date Display**: Event dates shown in a styled badge format
- **Default Event Time**: Events automatically default to 3:00 PM unless specified otherwise
- **Responsive Design**: Seamlessly adapts to mobile devices with vertical stacking

### Admin Features
- **Automatic event data extraction** from URLs (Facebook events and other event pages)
- **Upcoming/Past Events Tabs**: Easily view and manage events by status
- **Clone Events**: Duplicate past events to quickly create similar future events
- **Default 3pm Time**: Date picker automatically sets time to 3:00 PM
- Store events in a custom database table
- Automatically hide past events from frontend display
- Admin interface for managing events

## Installation

1. Download or clone this repository
2. Upload the `Huapai-events` folder to your WordPress `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. The plugin will automatically create the necessary database table

## Usage

### Adding Events

1. Go to **Huapai Events** in your WordPress admin menu
2. Enter the event URL and auto-fill details:
   - **Event URL**: Paste a Facebook event URL (or other event page URL)
   - Click **"Fetch Event Data"** to automatically extract event information
   - The plugin will auto-fill: title, description, date, and featured image
   - If no time is provided, it defaults to 3:00 PM
3. Review and adjust the auto-filled information if needed
4. Click "Add Event"

**Manual Entry:** You can also manually fill in all fields if preferred:
   - **Event Title**: Required - The name of your event
   - **Event Description**: A description of the event
   - **Event Date**: Required - When the event will take place
   - **Featured Image URL**: Direct URL to an event image

### Displaying Events

Use the shortcode `[huapai_events]` on any page or post to display upcoming events.

**What You'll See:**
- Events displayed in a modern two-column layout with image on the left
- Event title prominently displayed at the top
- Date and time in a styled badge (defaults to 3:00 PM if not specified)
- Description truncated to 200 characters with "Read More" button for longer content
- Link to view the event on Facebook

**Shortcode Options:**
- `limit`: Number of events to display (default: 10)
  - Example: `[huapai_events limit="5"]`
- `order`: Sort order by date - ASC or DESC (default: ASC)
  - Example: `[huapai_events order="DESC"]`

### Managing Events

- **View Upcoming Events**: See all future events in the main tab
- **View Past Events**: Switch to the Past Events tab to see historical events
- **Duplicate Events**: Clone any event (especially useful for recurring events) and modify details
- **Delete Events**: Remove events you no longer need
- Past events automatically disappear from the frontend display

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## Database Table

The plugin creates a table `wp_huapai_events` (prefix may vary) with the following structure:
- `id`: Unique event identifier
- `title`: Event title
- `content`: Event description
- `event_date`: Date and time of the event
- `featured_image`: URL to the featured image
- `fb_event_url`: Facebook event URL
- `created_at`: Timestamp when the event was added

## Support

For issues or questions, please visit the [GitHub repository](https://github.com/impact2021/Huapai-events)
