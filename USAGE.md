# Huapai Events - Usage Guide

## Quick Start

### 1. Installation
1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate through WordPress admin panel
3. Look for "Huapai Events" in the admin menu

### 2. Adding Your First Event

Navigate to **Huapai Events** in your WordPress admin menu:

#### Method 1: Auto-Extract from URL (Recommended)
1. **Event URL**: Paste a Facebook event URL (or other event page URL)
2. Click **"Fetch Event Data"** button
3. Wait while the plugin automatically extracts:
   - Event title
   - Event description
   - Event date and time
   - Featured image
4. Review the auto-filled fields and make any adjustments
5. Click **Add Event**

#### Method 2: Manual Entry
1. **Event Title** (required): Enter the event name
2. **Event Description**: Add details about the event (supports formatting)
3. **Event Date** (required): Select date and time
4. **Featured Image URL**: Paste the direct URL to an image
5. **Event URL** (optional): Add the link to your Facebook event
6. Click **Add Event**

### 3. Displaying Events

Add the shortcode to any page or post:

```
[huapai_events]
```

#### Shortcode Options

**Limit number of events:**
```
[huapai_events limit="5"]
```

**Change sort order (newest first):**
```
[huapai_events order="DESC"]
```

**Combine options:**
```
[huapai_events limit="3" order="DESC"]
```

## Features

### Automatic Past Event Removal
- Events are automatically hidden from the frontend display after their date has passed
- Past events remain in the admin panel for your records
- Only upcoming events are shown to visitors

### Responsive Design
- Events display beautifully on desktop, tablet, and mobile devices
- Images scale appropriately for different screen sizes
- Touch-friendly buttons and links

### Event Information Display
Each event card shows:
- Featured image (if provided)
- Event title
- Event date and time (formatted according to your WordPress settings)
- Event description
- Link to Facebook event (if provided)

## Managing Events

### Viewing All Events
Go to **Huapai Events** in the admin menu to see:
- All events (past and upcoming)
- Event details in a table format
- Thumbnail of featured images
- Quick access to Facebook event links

### Deleting Events
1. Go to **Huapai Events** in the admin menu
2. Find the event you want to delete
3. Click the **Delete** button
4. Confirm the deletion

## Tips & Best Practices

### Using Auto-Extract Feature
- Works best with Facebook events and pages that use Open Graph metadata
- If auto-extraction doesn't work perfectly, you can manually edit any field
- Some private or restricted events may not allow data extraction
- The feature extracts publicly available metadata from the URL

### Images
- Use high-quality images (recommended minimum: 800x600px)
- Images should be hosted on a reliable server
- Auto-extraction will pull the event's featured image automatically
- You can change the auto-filled image URL if needed

### Event Descriptions
- Auto-extracted descriptions can be edited and formatted using the WordPress editor
- Keep descriptions concise but informative
- Include key details like location, time, and what to bring

### Facebook Events
- Always test Facebook event URLs before adding them
- Make sure the Facebook event is public so visitors can view it
- The URL should look like: `https://www.facebook.com/events/123456789/`
- Public events work best with the auto-extraction feature

### Shortcode Placement
- Add the shortcode to dedicated "Events" page
- Can be used in multiple pages if needed
- Works in posts, pages, and text widgets

## Troubleshooting

### Events Not Showing
- Check that the event date is in the future
- Verify the shortcode is spelled correctly: `[huapai_events]`
- Make sure you've added at least one upcoming event

### Images Not Displaying
- Verify the image URL is correct and accessible
- Check that the image is hosted on a server (not local file)
- Test the image URL in a browser

### Styling Issues
- Clear your browser cache
- Clear WordPress cache if using a caching plugin
- Check for CSS conflicts with your theme

## Database Information

The plugin creates a table `wp_huapai_events` with these fields:
- `id`: Unique identifier
- `title`: Event name
- `content`: Event description
- `event_date`: When the event occurs
- `featured_image`: Image URL
- `fb_event_url`: Facebook event link
- `created_at`: When the event was added

**Note:** The table is automatically created when you activate the plugin.

## Support

For issues, questions, or feature requests:
- Visit: [GitHub Repository](https://github.com/impact2021/Huapai-events)
- Create an issue with details about your problem
- Include WordPress version and PHP version if reporting bugs
