// Notifications Manager - Fetch and filter notifications from database API with JSON fallback
class NotificationsManager {
  constructor() {
    this.notifications = [];
    this.apiUrl = '/admin/api/public/notifications';
    this.fallbackUrl = '/pub/data/notifications.json';
  }

  // Fetch notifications from database API, fallback to JSON
  async fetchNotifications() {
    try {
      // First try to fetch from database API
      console.log('Attempting to fetch notifications from database API...');
      const apiResponse = await fetch(this.apiUrl);

      if (apiResponse.ok) {
        const apiData = await apiResponse.json();
        if (apiData.notifications && Array.isArray(apiData.notifications)) {
          this.notifications = apiData.notifications;
          console.log(`Successfully loaded ${this.notifications.length} notifications from database API`);
          return this.notifications;
        } else {
          console.warn('Database API returned invalid data structure, falling back to JSON');
          throw new Error('Invalid API response structure');
        }
      } else {
        console.warn(`Database API failed with status ${apiResponse.status}, falling back to JSON`);
        throw new Error(`API failed: ${apiResponse.status}`);
      }
    } catch (apiError) {
      console.warn('Database API fetch failed:', apiError.message);
      console.log('Falling back to JSON file...');

      try {
        // Fallback to JSON file
        const jsonResponse = await fetch(this.fallbackUrl);
        if (!jsonResponse.ok) throw new Error('Failed to fetch JSON fallback');

        const jsonData = await jsonResponse.json();
        if (Array.isArray(jsonData)) {
          this.notifications = jsonData;
          console.log(`Successfully loaded ${this.notifications.length} notifications from JSON fallback`);
          return this.notifications;
        } else {
          throw new Error('Invalid JSON fallback structure');
        }
      } catch (jsonError) {
        console.error('Both database API and JSON fallback failed:', jsonError);
        console.error('API Error:', apiError.message);
        console.error('JSON Error:', jsonError.message);
        return [];
      }
    }
  }

  // Get today's date at midnight (for consistent comparison)
  getToday() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return today;
  }

  // Parse date string to Date object
  parseDate(dateString) {
    return new Date(dateString + 'T00:00:00');
  }

  // Filter active notifications (today is between publish and expiry date)
  getActiveNotifications() {
    const today = this.getToday();
    console.log('Today:', today.toISOString());
    const active = this.notifications.filter(notification => {
      const publishDate = this.parseDate(notification.publishDate);
      const expiryDate = this.parseDate(notification.expiryDate);
      const isActive = today >= publishDate && today <= expiryDate;
      console.log(`Notification ${notification.id}: publish=${publishDate.toISOString()}, expiry=${expiryDate.toISOString()}, active=${isActive}`);
      return isActive && notification.isActive;
    }).sort((a, b) => {
      // Sort by priority first, then by publish date (newest first)
      const priorityOrder = { critical: 0, high: 1, medium: 2, low: 3 };
      const priorityDiff = priorityOrder[a.priority] - priorityOrder[b.priority];
      if (priorityDiff !== 0) return priorityDiff;
      return this.parseDate(b.publishDate) - this.parseDate(a.publishDate);
    });
    console.log('Total active notifications:', active.length);
    return active;
  }

  // Filter notifications by priority
  getNotificationsByPriority(priority) {
    return this.getActiveNotifications().filter(notification => 
      notification.priority === priority
    );
  }

  // Filter notifications by category
  getNotificationsByCategory(category) {
    return this.getActiveNotifications().filter(notification => 
      notification.category === category
    );
  }

  // Get critical and high priority notifications only (for homepage)
  getImportantNotifications() {
    return this.getActiveNotifications().filter(notification => 
      notification.priority === 'critical' || notification.priority === 'high'
    );
  }

  // Render notification item HTML
  renderNotificationItem(notification) {
    const arrowIcon = notification.showArrow ? '<span class="icon">👉</span>' : '';
    const priorityClass = notification.priority ? `priority-${notification.priority}` : '';
    const hasFile = notification.fileUrl ? 'data-has-file="true"' : '';
    
    return `
      <div class="notification-item ${priorityClass}" data-notification-id="${notification.id}" ${hasFile} style="cursor: pointer;" onclick="window.location.href='/notifications.html'">
        <span class="icon">${notification.icon || '🔔'}</span>
        ${arrowIcon}
        <p>${notification.description}</p>
      </div>
    `;
  }

  // Render notification item HTML for notifications page (with metadata)
  renderNotificationItemFull(notification) {
    const arrowIcon = notification.showArrow ? '<span class="icon">👉</span>' : '';
    const priorityClass = notification.priority ? `priority-${notification.priority}` : '';
    const notificationTitle = notification.title ? `<h3 style="font-size: 1.2rem; color: var(--accent-color); margin-bottom: 8px; font-weight: 600;">${notification.title}</h3>` : '';
    const notificationNo = notification.notificationNo ? `<div style="font-size: 0.9rem; color: #666; margin-bottom: 8px;"><strong>Notification No:</strong> ${notification.notificationNo}</div>` : '';
    
    let fileDownload = '';
    if (notification.fileUrl) {
      fileDownload = `<a href="${notification.fileUrl}" target="_blank" class="download-btn" style="display: inline-flex; margin-top: 12px;">
          <i class="fas fa-download"></i> Download ${notification.fileName || 'File'}
        </a>`;
    }
    
    return `
      <div class="notification-item ${priorityClass}" data-notification-id="${notification.id}">
        <div style="display: flex; align-items: flex-start; gap: 10px;">
          <span class="icon">${notification.icon || '🔔'}</span>
          ${arrowIcon}
          <div style="flex: 1;">
            ${notificationTitle}
            ${notificationNo}
            <p style="margin-bottom: 10px;">${notification.description}</p>
            <div class="notification-meta">
              <span><i class="fas fa-tag"></i> ${notification.category}</span>
              <span><i class="fas fa-calendar"></i> Published: ${this.formatDate(notification.publishDate)}</span>
            </div>
            ${fileDownload}
          </div>
        </div>
      </div>
    `;
  }

  // Format date for display
  formatDate(dateString) {
    const date = this.parseDate(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
  }

  // Render notifications into a container
  renderNotifications(notifications, containerId, fullDisplay = false) {
    const container = document.getElementById(containerId);
    if (!container) {
      console.error(`Container ${containerId} not found`);
      return;
    }

    if (notifications.length === 0) {
      container.innerHTML = '<p class="no-notifications">No notifications available.</p>';
      return;
    }

    const renderFunc = fullDisplay ? this.renderNotificationItemFull.bind(this) : this.renderNotificationItem.bind(this);
    container.innerHTML = notifications.map(notification => 
      renderFunc(notification)
    ).join('');
  }

  // Initialize homepage notifications (important notifications only)
  async initializeHomepage() {
    console.log('NotificationsManager: Starting homepage initialization');
    try {
      await this.fetchNotifications();
      console.log('NotificationsManager: Fetch completed, notifications:', this.notifications.length);
      const activeNotifications = this.getActiveNotifications();
      console.log('NotificationsManager: Active notifications:', activeNotifications.length);
      this.renderNotifications(activeNotifications, 'notifications-scroll', false);
      console.log('NotificationsManager: Homepage initialization completed');
    } catch (error) {
      console.error('NotificationsManager: Error during homepage initialization:', error);
    }
  }

  // Initialize notifications page (all categories)
  async initializeNotificationsPage() {
    await this.fetchNotifications();
    
    const allNotifications = this.getActiveNotifications();
    console.log('Active notifications:', allNotifications.length, allNotifications);
    this.renderNotifications(allNotifications, 'all-notifications', true);
  }
}

// Create global instance
window.notificationsManager = new NotificationsManager();
