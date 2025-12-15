// Theme configuration
const THEMES = {
  blue: null,
  eggplant: 'eggplant',
  purple: 'purple'
};

const DEFAULT_THEME = 'blue';

// Apply theme to document
function applyTheme(themeName) {
  const themeValue = THEMES[themeName];
  
  if (themeValue) {
    document.documentElement.setAttribute('data-theme', themeValue);
  } else {
    document.documentElement.removeAttribute('data-theme');
  }
  
  sessionStorage.setItem('theme', themeName);
}

// Initialize theme from URL parameter
document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const theme = urlParams.get('theme');
  const selectedTheme = THEMES.hasOwnProperty(theme) ? theme : DEFAULT_THEME;
  
  applyTheme(selectedTheme);
});

// Make function available globally
window.switchTheme = applyTheme;
