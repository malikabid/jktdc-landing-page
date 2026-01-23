// Events Manager - Fetch and filter events from JSON
class EventsManager {
  constructor() {
    this.events = [];
    // Use public API endpoint for events (no auth required)
    this.dataUrl = '/admin/api/public/events';
  }

  // Fetch events from backend API
  async fetchEvents() {
    try {
      const response = await fetch(this.dataUrl);
      if (!response.ok) throw new Error('Failed to fetch events');
      const data = await response.json();
      // API returns { events: [...], ... }
      this.events = Array.isArray(data.events) ? data.events : [];
      return this.events;
    } catch (error) {
      console.error('Error fetching events:', error);
      this.events = [];
      return [];
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

  // Filter current events (today is between start and end date)
  getCurrentEvents() {
    const today = this.getToday();
    return this.events.filter(event => {
      const startDate = this.parseDate(event.startDate);
      const endDate = this.parseDate(event.endDate);
      return today >= startDate && today <= endDate;
    });
  }

  // Filter upcoming events (start date is in the future)
  getUpcomingEvents() {
    const today = this.getToday();
    return this.events.filter(event => {
      const startDate = this.parseDate(event.startDate);
      return startDate > today;
    }).sort((a, b) => {
      return this.parseDate(a.startDate) - this.parseDate(b.startDate);
    });
  }

  // Filter completed events (end date is in the past)
  getCompletedEvents() {
    const today = this.getToday();
    return this.events.filter(event => {
      const endDate = this.parseDate(event.endDate);
      return endDate < today;
    }).sort((a, b) => {
      return this.parseDate(b.endDate) - this.parseDate(a.endDate);
    });
  }

  // Get current + upcoming events (for homepage)
  getActiveEvents() {
    return [...this.getCurrentEvents(), ...this.getUpcomingEvents()];
  }

  // Get events marked for homepage display (sorted by start date descending - newest first)
  getHomepageEvents() {
    return this.events
      .filter(event => event.showOnHomepage === true)
      .sort((a, b) => this.parseDate(b.startDate) - this.parseDate(a.startDate));
  }

  // Format date for display
  formatDate(dateString) {
    const date = this.parseDate(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
  }

  // Get month and day from date string
  getDateParts(dateString) {
    const date = this.parseDate(dateString);
    return {
      month: date.toLocaleDateString('en-US', { month: 'short' }),
      day: date.getDate()
    };
  }

  // Render event item HTML
  renderEventItem(event) {
    const dateParts = this.getDateParts(event.startDate);

    let mediaHtml = '';

    // Video (YouTube or direct video file)
    if (event.videoUrl && event.thumbnail) {
      mediaHtml = `
        <div class="video-thumbnail" data-video-url="${event.videoUrl}">
          <img src="${event.thumbnail}" alt="${event.title} Video" />
          <div class="play-overlay">
            <i class="fas fa-play-circle"></i>
          </div>
          <div class="video-label">Watch Video</div>
        </div>
      `;
    } else if (event.file_path) {
      // Determine file type by extension
      const ext = event.file_path.split('.').pop().toLowerCase();
      if (["jpg","jpeg","png","gif","webp","bmp"].includes(ext)) {
        // Image file
        mediaHtml = `
          <div class="event-image">
            <img src="${event.file_path}" alt="${event.title}" />
          </div>
        `;
      } else if (["mp4","webm","ogg","mov","avi"].includes(ext)) {
        // Video file
        mediaHtml = `
          <div class="event-video">
            <video controls style="max-width:100%;border-radius:8px;">
              <source src="${event.file_path}" type="video/${ext === 'mp4' ? 'mp4' : ext}">
              Your browser does not support the video tag.
            </video>
          </div>
        `;
      } else if (["pdf","doc","docx"].includes(ext)) {
        // Downloadable file (PDF, DOC, DOCX)
        const icon = ext === 'pdf' ? 'fa-file-pdf' : 'fa-file-word';
        const label = ext === 'pdf' ? 'Download PDF' : 'Download Document';
        mediaHtml = `
          <div class="event-download">
            <a href="${event.file_path}" target="_blank" rel="noopener noreferrer" class="event-download-btn">
              <i class="fas ${icon}"></i> ${label}
            </a>
          </div>
        `;
      }
    }

    let registrationHtml = '';
    if (event.registrationUrl) {
      const deadlineText = event.registrationDeadline 
        ? ` (Closes: ${this.formatDate(event.registrationDeadline)})` 
        : '';
      registrationHtml = `
        <a href="${event.registrationUrl}" target="_blank" rel="noopener noreferrer" class="event-register-btn">
          <i class="fas fa-user-plus"></i> Register Now${deadlineText}
        </a>
      `;
    }

    return `
      <div class="event-item" data-event-id="${event.id}">
        <div class="event-date">
          <span class="month">${dateParts.month}</span>
          <span class="day">${dateParts.day}</span>
        </div>
        <div class="event-details">
          <h3>${event.title}</h3>
          <p>${event.description}</p>
          ${event.location ? `<p class="event-location"><i class="fas fa-map-marker-alt"></i> ${event.location}</p>` : ''}
          ${registrationHtml}
        </div>
        <div class="event-media">
          ${mediaHtml}
        </div>
      </div>
    `;
  }

  // Render events into a container
  renderEvents(events, containerId) {
    const container = document.getElementById(containerId);
    if (!container) {
      console.error(`Container ${containerId} not found`);
      return;
    }

    if (events.length === 0) {
      container.innerHTML = '<p class="no-events">No events available.</p>';
      return;
    }

    container.innerHTML = events.map(event => this.renderEventItem(event)).join('');

    // Reinitialize video modal for new thumbnails
    if (typeof initializeVideoModal === 'function') {
      setTimeout(() => initializeVideoModal(), 100);
    }
  }

  // Initialize homepage events (show events marked for homepage, or 3 most recent if none)
  async initializeHomepage() {
    await this.fetchEvents();
    let homepageEvents = this.getHomepageEvents();
    
    // If no homepage events, show 3 most recent active events
    if (homepageEvents.length === 0) {
      const activeEvents = this.getActiveEvents();
      homepageEvents = activeEvents.slice(0, 3);
    }
    
    // If still no events, show 3 most recent completed events
    if (homepageEvents.length === 0) {
      const completedEvents = this.getCompletedEvents();
      homepageEvents = completedEvents.slice(0, 3);
    }
    
    this.renderEvents(homepageEvents, 'events-scroll');
  }

  // Initialize events page (all sections)
  async initializeEventsPage() {
    await this.fetchEvents();
    
    // Combine current and upcoming events
    const activeEvents = this.getActiveEvents();
    const completedEvents = this.getCompletedEvents();

    this.renderEvents(activeEvents, 'upcoming-events');
    this.renderEvents(completedEvents, 'completed-events');
  }
}

// Create global instance
window.eventsManager = new EventsManager();
