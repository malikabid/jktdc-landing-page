// Tenders Manager - Fetch and filter tenders from API
class TendersManager {
  constructor() {
    this.tenders = [];
    this.apiUrl = '/admin/api/public/tenders';
  }

  // Fetch tenders from API
  async fetchTenders() {
    try {
      const response = await fetch(this.apiUrl);
      if (!response.ok) throw new Error('Failed to fetch tenders from API');
      this.tenders = await response.json();
      return this.tenders;
    } catch (error) {
      console.error('Error fetching tenders:', error);
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

  // Filter active tenders (status is 'active' from API)
  getActiveTenders() {
    return this.tenders.filter(tender => {
      return tender.status === 'active';
    }).sort((a, b) => {
      return this.parseDate(b.publishDate) - this.parseDate(a.publishDate);
    });
  }

  // Filter closed tenders (status is 'closed' from API)
  getClosedTenders() {
    return this.tenders.filter(tender => {
      return tender.status === 'closed';
    }).sort((a, b) => {
      return this.parseDate(b.publishDate) - this.parseDate(a.publishDate);
    });
  }

  // Calculate days remaining until closing
  getDaysRemaining(closingDate) {
    const today = this.getToday();
    const closing = this.parseDate(closingDate);
    const diffTime = closing - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
  }

  // Format date for display
  formatDate(dateString) {
    const date = this.parseDate(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
  }

  // Get urgency class based on days remaining
  getUrgencyClass(daysRemaining) {
    if (daysRemaining <= 3) return 'urgent';
    if (daysRemaining <= 7) return 'moderate';
    return 'normal';
  }

  // Render tender item HTML
  renderTenderItem(tender) {
    const daysRemaining = this.getDaysRemaining(tender.closingDate);
    const urgencyClass = this.getUrgencyClass(daysRemaining);
    const publishDate = this.formatDate(tender.publishDate);
    const closingDate = this.formatDate(tender.closingDate);
    
    let documentsHtml = '';
    if (tender.documents && tender.documents.length > 0) {
      documentsHtml = `
        <div class="tender-documents">
          <h4><i class="fas fa-file-pdf"></i> Documents:</h4>
          <div class="documents-list">
            ${tender.documents.map(doc => `
              <a href="${doc.url}" target="_blank" rel="noopener noreferrer" class="document-link">
                <i class="fas fa-download"></i> ${doc.name}
              </a>
            `).join('')}
          </div>
        </div>
      `;
    }

    let contactHtml = '';
    if (tender.contactPerson || tender.contactEmail || tender.contactPhone) {
      contactHtml = `
        <div class="tender-contact">
          <h4><i class="fas fa-address-card"></i> Contact Information:</h4>
          ${tender.contactPerson ? `<p><strong>Contact:</strong> ${tender.contactPerson}</p>` : ''}
          ${tender.contactEmail ? `<p><strong>Email:</strong> <a href="mailto:${tender.contactEmail}">${tender.contactEmail}</a></p>` : ''}
          ${tender.contactPhone ? `<p><strong>Phone:</strong> <a href="tel:${tender.contactPhone}">${tender.contactPhone}</a></p>` : ''}
        </div>
      `;
    }

    let statusBadge = '';
    if (tender.status === 'active') {
      statusBadge = `
        <div class="tender-status ${urgencyClass}">
          <i class="fas fa-clock"></i>
          ${daysRemaining > 0 ? `${daysRemaining} days remaining` : 'Closes today'}
        </div>
      `;
    } else {
      statusBadge = `<div class="tender-status closed">Closed</div>`;
    }

    return `
      <div class="tender-item" data-tender-id="${tender.id}">
        <div class="tender-header">
          <div class="tender-title-section">
            <h3>${tender.title}</h3>
            <p class="tender-number">${tender.tenderNumber}</p>
          </div>
          ${statusBadge}
        </div>
        
        <div class="tender-details">
          ${tender.description ? `<p class="tender-description">${tender.description}</p>` : ''}
          
          <div class="tender-meta">
            <div class="meta-item">
              <i class="fas fa-building"></i>
              <span><strong>Department:</strong> ${tender.department}</span>
            </div>
            <div class="meta-item">
              <i class="fas fa-tag"></i>
              <span><strong>Category:</strong> ${tender.category}</span>
            </div>
            ${tender.estimatedValue ? `
              <div class="meta-item">
                <i class="fas fa-rupee-sign"></i>
                <span><strong>Estimated Value:</strong> ${tender.estimatedValue}</span>
              </div>
            ` : ''}
            <div class="meta-item">
              <i class="fas fa-calendar-alt"></i>
              <span><strong>Published:</strong> ${publishDate}</span>
            </div>
            <div class="meta-item">
              <i class="fas fa-calendar-times"></i>
              <span><strong>Closing Date:</strong> ${closingDate}</span>
            </div>
          </div>

          <div class="tender-docs-contact-wrapper">
            ${documentsHtml}
            ${contactHtml}
          </div>
        </div>
      </div>
    `;
  }

  // Render tenders into a container
  renderTenders(tenders, containerId) {
    const container = document.getElementById(containerId);
    if (!container) {
      console.error(`Container ${containerId} not found`);
      return;
    }

    if (tenders.length === 0) {
      container.innerHTML = '<p class="no-tenders">No tenders available.</p>';
      return;
    }

    container.innerHTML = tenders.map(tender => this.renderTenderItem(tender)).join('');
  }

  // Initialize tenders page (all sections)
  async initializeTendersPage() {
    await this.fetchTenders();
    
    const activeTenders = this.getActiveTenders();
    const closedTenders = this.getClosedTenders();
    
    this.renderTenders(activeTenders, 'active-tenders');
    this.renderTenders(closedTenders, 'closed-tenders');
  }

  // Initialize homepage tenders (active only, limited count)
  async initializeHomepage(limit = 5) {
    await this.fetchTenders();
    const activeTenders = this.getActiveTenders().slice(0, limit);
    this.renderTenders(activeTenders, 'tenders-scroll');
  }
}

// Create global instance
window.tendersManager = new TendersManager();
