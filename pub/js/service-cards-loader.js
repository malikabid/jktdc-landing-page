// Load service cards dynamically based on URL parameter
document.addEventListener('DOMContentLoaded', function() {
  // Get URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  const showServiceCards = urlParams.get('showServiceCards') === 'true';
  
  const serviceCardsContainer = document.querySelector('.cards-container');
  const serviceCardsOverlay = document.querySelector('.service-cards-overlay');
  const mainContent = document.querySelector('.main-content');
  
  if (showServiceCards && serviceCardsContainer) {
    // Load and display service cards
    fetch('pub/html/service-cards.html')
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
      })
      .then(html => {
        serviceCardsContainer.innerHTML = html;
        // Add class to main-content for -110px margin
        if (mainContent) {
          mainContent.classList.add('service-cards');
        }
      })
      .catch(error => {
        console.error('Error loading service cards:', error);
        // Hide overlay on error
        if (serviceCardsOverlay) {
          serviceCardsOverlay.style.display = 'none';
        }
      });
  } else {
    // Hide service cards overlay if parameter is false or not set
    if (serviceCardsOverlay) {
      serviceCardsOverlay.style.display = 'none';
    }
  }
});
