// Events Manager - Fetch and filter events from JSON
class EventsManager {
  constructor() {
    this.events = [];
    this.dataUrl = '/pub/data/events.json';
  }

  // Fetch events from JSON
  async fetchEvents() {
    try {
      const response = await fetch(this.dataUrl);
      if (!response.ok) throw new Error('Failed to fetch events');
      this.events = await response.json();
      return this.events;
    } catch (error) {
      console.error('Error fetching events:', error);
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
    
    let videoHtml = '';
    if (event.videoUrl && event.thumbnail) {
      videoHtml = `
        <div class="video-thumbnail" data-video-url="${event.videoUrl}">
          <img src="${event.thumbnail}" alt="${event.title} Video" />
          <div class="play-overlay">
            <i class="fas fa-play-circle"></i>
          </div>
          <div class="video-label">Watch Video</div>
        </div>
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
          ${videoHtml}
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

  // Initialize homepage events (current + upcoming only)
  async initializeHomepage() {
    await this.fetchEvents();
    const activeEvents = this.getActiveEvents();
    this.renderEvents(activeEvents, 'events-scroll');
  }

  // Initialize events page (all sections)
  async initializeEventsPage() {
    await this.fetchEvents();
    
    const currentEvents = this.getCurrentEvents();
    const upcomingEvents = this.getUpcomingEvents();
    const completedEvents = this.getCompletedEvents();

    this.renderEvents(currentEvents, 'current-events');
    this.renderEvents(upcomingEvents, 'upcoming-events');
    this.renderEvents(completedEvents, 'completed-events');
  }
}

// Create global instance
window.eventsManager = new EventsManager();
