# Huapai Events - Feature Overview

## Core Features

### 1. Event Management System
- **Add Events**: Simple form interface to add new events
- **View Events**: Table view of all events with key information
- **Delete Events**: One-click event removal with confirmation
- **Edit Capability**: Database-backed system for reliable storage

### 2. Facebook Integration
- Direct input for Facebook event URLs
- Link to original Facebook event from frontend display
- Opens in new tab for better user experience
- Maintains connection to source event

### 3. Frontend Display
- **Shortcode**: `[huapai_events]` - Easy to implement anywhere
- **Automatic Filtering**: Only shows upcoming events
- **Responsive Design**: Works on all device sizes
- **Professional Styling**: Clean, modern event cards

### 4. Data Fields
Each event can include:
- Title (Required)
- Description/Content
- Event Date & Time (Required)
- Featured Image
- Facebook Event URL

## Admin Interface

### Add Event Form
Located at: **WordPress Admin → Huapai Events**

Form includes:
- Text input for Facebook Event URL
- Text input for Event Title (required field)
- Rich text editor for Event Description
- Date/time picker for Event Date (required field)
- URL input for Featured Image
- Submit button to save

### Events List
Shows all events in a table with columns:
- ID
- Title
- Event Date
- Featured Image (thumbnail preview)
- Facebook Event URL (clickable link)
- Actions (Delete button)

### User Interface Elements
- Success messages when events are added
- Error messages for validation issues
- Confirmation dialog before deletion
- Helpful instructions and shortcode display
- Clean WordPress admin styling

## Frontend Display

### Event Cards
Each event displays as a card with:
- Featured image at the top (if provided)
- Event title as heading
- Date and time in highlighted box
- Event description/content
- "View on Facebook" button (if URL provided)

### Styling Features
- Box shadow for depth
- Hover effects for interactivity
- Color-coded date section (blue accent)
- Rounded corners for modern look
- Proper spacing and typography
- Facebook brand color for button

### Responsive Behavior
- **Desktop**: Full-width event cards with large images
- **Tablet**: Adjusted padding and font sizes
- **Mobile**: Optimized for small screens, stacked layout

## Security Features

### Input Sanitization
- `sanitize_text_field()` for text inputs
- `esc_url_raw()` for URLs
- `wp_kses_post()` for rich content
- Protection against XSS attacks

### Output Escaping
- `esc_html()` for text output
- `esc_url()` for URL output
- `esc_attr()` for attributes
- Safe rendering of user content

### Nonce Verification
- Nonces on all forms
- Verification before processing
- Protection against CSRF attacks

### Database Security
- Prepared statements with `$wpdb->prepare()`
- Proper type casting for IDs
- SQL injection prevention
- Safe table name handling

## Technical Features

### Database
- Custom table: `wp_huapai_events`
- Indexed event_date for performance
- Automatic table creation on activation
- WordPress dbDelta for upgrades

### WordPress Integration
- Standard activation hook
- Admin menu with dashboard icon
- Shortcode API integration
- Enqueued styles (no inline CSS)
- WordPress coding standards

### Performance
- Only loads CSS when shortcode is present
- Indexed database queries
- Efficient date filtering
- Minimal database calls

## Shortcode Options

### Basic Usage
```
[huapai_events]
```
Shows up to 10 upcoming events in ascending date order.

### With Limit
```
[huapai_events limit="5"]
```
Shows only the next 5 upcoming events.

### With Custom Order
```
[huapai_events order="DESC"]
```
Shows events with newest dates first.

### Combined Options
```
[huapai_events limit="3" order="DESC"]
```
Shows 3 events with newest dates first.

## Browser Compatibility
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## WordPress Compatibility
- WordPress 5.0+
- PHP 7.0+
- MySQL 5.6+
- Classic Editor & Block Editor (Gutenberg)

## Future Enhancement Ideas
- Event categories/tags
- Search functionality
- Calendar view
- Export to iCal
- Email notifications
- Recurring events
- Event registration
- Google Maps integration
- Social sharing buttons
- Image upload instead of URL

## File Structure
```
Huapai-events/
├── huapai-events.php          # Main plugin file
├── assets/
│   └── css/
│       ├── huapai-events.css       # Frontend styles
│       └── huapai-events-admin.css # Admin styles
├── README.md                   # Project overview
├── USAGE.md                    # User guide
├── FEATURES.md                 # This file
└── .gitignore                 # Git ignore rules
```

## Best Practices Implemented
✓ WordPress coding standards
✓ Security best practices (sanitization, escaping, nonces)
✓ Responsive design
✓ Semantic HTML
✓ Clean separation of concerns
✓ Proper WordPress hooks and filters
✓ Database optimization
✓ User-friendly admin interface
✓ Clear documentation
✓ Version control ready
