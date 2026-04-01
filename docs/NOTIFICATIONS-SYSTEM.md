# Notifications System Documentation

## Overview
The notification system is now database-driven with automatic fallback to JSON files. Notifications are primarily loaded from the admin database API, with seamless fallback to the legacy JSON file if the database is unavailable. This ensures continuous operation even during maintenance or database issues.

## Data Sources (Priority Order)

### 1. Primary: Database API
**Endpoint:** `/admin/api/public/notifications`

The system first attempts to fetch notifications from the database via the admin API. This provides:
- Real-time updates from admin panel
- Dynamic content management
- User-based access controls
- Audit trails and versioning

### 2. Fallback: JSON File
**Location:** `pub/data/notifications.json`

If the database API is unavailable, the system automatically falls back to the JSON file, ensuring the website remains functional.

## File Structure

### Database Schema
The notifications table includes:

```sql
CREATE TABLE notifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(500),
  description TEXT,
  notification_no VARCHAR(100),
  icon VARCHAR(10) DEFAULT '📄',
  show_arrow BOOLEAN DEFAULT TRUE,
  priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
  publish_date DATE,
  expiry_date DATE,
  category VARCHAR(100) DEFAULT 'General',
  file_url VARCHAR(500),
  file_name VARCHAR(300),
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### JSON Fallback Structure
**Location:** `pub/data/notifications.json`

```json
{
  "id": 1,
  "title": "Notification title",
  "description": "Full notification text displayed to users",
  "notificationNo": "REF/123/2024",
  "icon": "📄",
  "showArrow": true,
  "priority": "high",
  "publishDate": "2024-12-01",
  "expiryDate": "2025-12-25",
  "category": "Official",
  "fileUrl": "/pub/pdf/document.pdf",
  "fileName": "Document Name",
  "isActive": true
}
```

**Field Descriptions:**
- `id`: Unique identifier
- `title`: Notification title
- `description`: Full notification text
- `notificationNo`: Official reference number
- `icon`: Emoji icon (default: 📄)
- `showArrow`: Show arrow indicator (👉)
- `priority`: Importance level (low/medium/high/critical)
- `publishDate`: Start date (YYYY-MM-DD)
- `expiryDate`: End date (YYYY-MM-DD)
- `category`: Classification
- `fileUrl`: Download link
- `fileName`: Display name for downloads
- `isActive`: Visibility status

## JavaScript Manager
**Location:** `pub/js/notifications-manager.js`

### Key Features:
- **Dual Source Loading**: Database API → JSON fallback
- **Error Handling**: Graceful degradation
- **Caching**: Stores loaded notifications in memory
- **Filtering**: Date range, priority, category filters
- **Sorting**: Priority-based, then chronological
- **Rendering**: Multiple display formats

### Loading Logic:
```javascript
async fetchNotifications() {
  try {
    // Try database API first
    const apiResponse = await fetch('/admin/api/public/notifications');
    if (apiResponse.ok) {
      const data = await apiResponse.json();
      return data.notifications; // API returns {notifications: [...]}
    }
  } catch (error) {
    console.warn('Database API failed, using JSON fallback');
  }
  
  // Fallback to JSON
  const jsonResponse = await fetch('/pub/data/notifications.json');
  return await jsonResponse.json(); // JSON is direct array
}
```

### 2. JavaScript Manager
**Location:** `pub/js/notifications-manager.js`

This script handles:
- **Primary**: Fetching notifications from database API
- **Fallback**: Loading from JSON file if API fails
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

### Data Loading Priority

The notifications system uses a **database-first approach with JSON fallback**:

1. **Primary**: Loads from database via `/admin/api/public/notifications` API
   - Real-time data from admin panel
   - Supports all features (attachments, categories, priorities)
   - Automatic cache busting via versioned API

2. **Fallback**: If API fails, loads from `pub/data/notifications.json`
   - Static JSON file for emergency situations
   - Limited features (no attachments, basic categories)
   - No cache busting needed

**Benefits:**
- Database provides full functionality and real-time updates
- JSON ensures website stays functional during database outages
- Automatic fallback prevents broken notifications display
- Console logs indicate which source is being used

**Monitoring:**
Check browser console for loading status:
- `"Loading notifications from API"` = Database working
- `"API failed, loading from JSON fallback"` = Database issue, using backup

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

## Managing Notifications

### Admin Panel (Primary Method)
1. Log into the admin panel at `/admin`
2. Navigate to **Notifications** section
3. Click **"Add New Notification"**
4. Fill in the form with:
   - Title and description
   - Priority level (low/medium/high/critical)
   - Publish and expiry dates
   - Category and optional file attachments
5. Save - notifications appear immediately on the website

### JSON Fallback (Emergency Only)
If the admin panel is unavailable, you can temporarily update `pub/data/notifications.json`:

1. Open `pub/data/notifications.json`
2. Add a new object to the array with all required fields
3. Set appropriate publish and expiry dates
4. Choose priority level based on importance
5. Save the file - changes appear immediately

**Example:**
```json
{
  "id": 9,
  "title": "New tourist package",
  "description": "Special summer packages now available for family tours. Book before March 31 for 20% early bird discount.",
  "notificationNo": "PKG/2025/001",
  "icon": "🔔",
  "showArrow": true,
  "priority": "high",
  "publishDate": "2025-01-15",
  "expiryDate": "2025-03-31",
  "category": "Tourism",
  "fileUrl": "/pub/pdf/packages.pdf",
  "fileName": "Summer Packages 2025",
  "isActive": true
}
```

**⚠️ Note:** JSON edits are temporary. Always use the admin panel for permanent changes.

## API Methods

The `NotificationsManager` class provides several methods:

### Basic Methods
- `fetchNotifications()`: Load notifications from database API with JSON fallback
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
