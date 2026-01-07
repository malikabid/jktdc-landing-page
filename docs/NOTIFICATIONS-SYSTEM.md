# Notifications System Documentation

## Overview
The notification section on the homepage is now dynamic, loading content from a JSON database file. This system is similar to the events management system and allows for easy updates without modifying HTML code.

## File Structure

### 1. Database File
**Location:** `pub/data/notifications.json`

This JSON file contains all notifications with the following structure:

```json
{
  "id": 1,
  "title": "Notification title",
  "description": "Full notification text displayed to users",
  "icon": "🔔",
  "showArrow": true,
  "priority": "high",
  "publishDate": "2024-12-01",
  "expiryDate": "2025-12-25",
  "category": "Event"
}
```

**Field Descriptions:**
- `id`: Unique identifier for the notification
- `title`: Short title (used for reference, not displayed on homepage)
- `description`: Full notification text displayed to users
- `icon`: Emoji icon shown before the notification (default: 🔔)
- `showArrow`: Boolean - whether to show arrow icon (👉) after bell icon
- `priority`: Notification importance level
  - `critical`: Red accent, urgent notices (e.g., safety advisories)
  - `high`: Orange accent, important updates (e.g., events, deadlines)
  - `medium`: Blue accent, general information
  - `low`: Default styling, minor updates
- `publishDate`: Date when notification becomes visible (format: YYYY-MM-DD)
- `expiryDate`: Date when notification stops showing (format: YYYY-MM-DD)
- `category`: Classification (e.g., "Event", "Advisory", "Service Update", "Tourism")

### 2. JavaScript Manager
**Location:** `pub/js/notifications-manager.js`

This script handles:
- Fetching notifications from JSON
- Filtering by date range (publish/expiry)
- Sorting by priority and date
- Rendering notifications in HTML
- Managing different display contexts (homepage vs. dedicated page)

### 3. HTML Integration
**Location:** `index.html`

The notifications are loaded into:
```html
<div class="scrollable-content" id="notifications-scroll">
  <!-- Notifications loaded dynamically -->
</div>
```

Initialization happens on page load:
```javascript
document.addEventListener('DOMContentLoaded', async function() {
  await window.notificationsManager.initializeHomepage();
});
```

## How It Works

### Homepage Display
The homepage shows all **active notifications** (current date is between publishDate and expiryDate), sorted by:
1. Priority (critical → high → medium → low)
2. Publish date (newest first within each priority level)

### Date Filtering
- Notifications are automatically shown/hidden based on current date
- `publishDate`: Notification starts appearing from this date
- `expiryDate`: Notification stops appearing after this date
- Both dates are inclusive

### Visual Styling
Notifications are styled based on priority:
- **Critical**: Red left border, light red background
- **High**: Orange left border, light orange background  
- **Medium**: Blue left border, light blue background
- **Default**: Orange left border, gray background

## Adding New Notifications

1. Open `pub/data/notifications.json`
2. Add a new object to the array with all required fields
3. Set appropriate publish and expiry dates
4. Choose priority level based on importance
5. Save the file - changes appear immediately (no cache busting needed for JSON)

**Example:**
```json
{
  "id": 9,
  "title": "New tourist package",
  "description": "Special summer packages now available for family tours. Book before March 31 for 20% early bird discount.",
  "icon": "🔔",
  "showArrow": true,
  "priority": "high",
  "publishDate": "2025-01-15",
  "expiryDate": "2025-03-31",
  "category": "Tourism"
}
```

## API Methods

The `NotificationsManager` class provides several methods:

### Basic Methods
- `fetchNotifications()`: Load notifications from JSON
- `getActiveNotifications()`: Get all current notifications
- `renderNotifications(notifications, containerId)`: Display notifications in a container

### Filtering Methods
- `getNotificationsByPriority(priority)`: Filter by priority level
- `getNotificationsByCategory(category)`: Filter by category
- `getImportantNotifications()`: Get critical + high priority only

### Page-Specific Methods
- `initializeHomepage()`: Load notifications for homepage
- `initializeNotificationsPage()`: Load notifications for dedicated notifications page (if created)

## Future Enhancements

Potential features to add:
1. **Dedicated Notifications Page**: Similar to events.html, showing all notifications grouped by priority
2. **Search/Filter**: Allow users to search notifications or filter by category
3. **Archive**: Show expired notifications for reference
4. **Read/Unread Status**: Track which notifications users have seen (requires backend)
5. **Push Notifications**: Alert users to critical notifications (requires service worker)

## Maintenance

### Regular Tasks
- Review and update expiry dates for time-sensitive notifications
- Remove very old notifications (expired 3+ months ago) to keep file manageable
- Update priority levels based on actual importance
- Ensure consistency in tone and formatting across notifications

### Troubleshooting
- **Notifications not appearing**: Check browser console for JSON fetch errors
- **Wrong display order**: Verify priority values are exactly "critical", "high", "medium", or "low"
- **Date issues**: Ensure dates are in YYYY-MM-DD format and are valid
- **Styling issues**: Check that CSS classes match priority values
