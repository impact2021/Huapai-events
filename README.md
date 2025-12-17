# Huapai Events

A WordPress plugin to manage and display events from Facebook on your WordPress website.

## Features

- Add Facebook event links with event details (title, content, date, featured image)
- Store events in a custom database table
- Display upcoming events using a shortcode
- Automatically hide past events
- Clean, responsive design
- Admin interface for managing events

## Installation

1. Download or clone this repository
2. Upload the `Huapai-events` folder to your WordPress `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. The plugin will automatically create the necessary database table

## Usage

### Adding Events

1. Go to **Huapai Events** in your WordPress admin menu
2. Fill in the event details:
   - **Facebook Event URL**: (Optional) Link to the Facebook event
   - **Event Title**: Required - The name of your event
   - **Event Description**: A description of the event
   - **Event Date**: Required - When the event will take place
   - **Featured Image URL**: Direct URL to an event image
3. Click "Add Event"

### Displaying Events

Use the shortcode `[huapai_events]` on any page or post to display upcoming events.

**Shortcode Options:**
- `limit`: Number of events to display (default: 10)
  - Example: `[huapai_events limit="5"]`
- `order`: Sort order by date - ASC or DESC (default: ASC)
  - Example: `[huapai_events order="DESC"]`

### Managing Events

- View all events in the admin panel
- Delete events you no longer need
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
