// Theme switcher based on URL parameter
document.addEventListener('DOMContentLoaded', function() {
  // Get URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  const theme = urlParams.get('theme');
  
  // Default theme is 'blue', can be changed to 'eggplant' via URL parameter
  const validThemes = ['blue', 'eggplant'];
  const selectedTheme = validThemes.includes(theme) ? theme : 'blue';
  
  // Apply theme to document
  if (selectedTheme === 'eggplant') {
    document.documentElement.setAttribute('data-theme', 'eggplant');
  } else {
    document.documentElement.removeAttribute('data-theme');
  }
  
  // Store theme preference in sessionStorage
  sessionStorage.setItem('theme', selectedTheme);
  
  // Log theme for debugging
  console.log('Active theme:', selectedTheme);
});

// Function to switch theme programmatically (can be called from other scripts)
function switchTheme(themeName) {
  if (themeName === 'eggplant') {
    document.documentElement.setAttribute('data-theme', 'eggplant');
  } else {
    document.documentElement.removeAttribute('data-theme');
  }
  sessionStorage.setItem('theme', themeName);
}

// Make function available globally
window.switchTheme = switchTheme;
