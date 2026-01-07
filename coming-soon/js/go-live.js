// Go Live Functionality - Website Inauguration with Password Protection
(function() {
  'use strict';

  // Check URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  const hasGoLiveParam = urlParams.has('golive');

  // Configuration
  // SHA-256 hash of 'dotk2025' - To generate hash for new password:
  // Open browser console and run: crypto.subtle.digest('SHA-256', new TextEncoder().encode('your-password')).then(h => console.log(Array.from(new Uint8Array(h)).map(b => b.toString(16).padStart(2, '0')).join('')))
  const PASSWORD_HASH = 'c5996bdccefa26f1bda51d908c042316e1042d8b412557ec88f0f68c2d0bdc6f';
  const LIVE_FLAG = 'siteIsLive';

  // Hash function using SHA-256
  async function hashPassword(password) {
    const encoder = new TextEncoder();
    const data = encoder.encode(password);
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    return hashHex;
  }

  // Check if site is already live
  const isLive = localStorage.getItem(LIVE_FLAG);
  if (isLive === 'true') {
    // If already live, redirect to main site immediately
    window.location.href = '/';
    return;
  }

  // Create password modal HTML
  const passwordModalHTML = `
    <div id="passwordModal" class="modal-overlay">
      <div class="modal-content">
        <h2><i class="fas fa-lock"></i> Admin Access</h2>
        <p>Enter the admin password to proceed:</p>
        <input 
          type="password" 
          id="adminPassword" 
          class="password-input" 
          placeholder="Enter password..."
          autocomplete="off"
        />
        <div id="errorMessage" class="error-message">
          <i class="fas fa-exclamation-circle"></i> Incorrect password. Please try again.
        </div>
        <div class="modal-buttons">
          <button id="verifyPassword" class="modal-btn confirm">
            <i class="fas fa-check"></i> Verify
          </button>
          <button id="cancelPassword" class="modal-btn cancel">
            <i class="fas fa-times"></i> Cancel
          </button>
        </div>
      </div>
    </div>
  `;

  // Create thank you modal HTML
  const thankYouModalHTML = `
    <div id="goLiveModal" class="modal-overlay">
      <div class="modal-content inauguration-modal">
        <div style="font-size: 4rem; color: var(--primary-color); margin-bottom: 20px;">
          <i class="fas fa-ribbon"></i>
        </div>
        <h2 style="color: var(--accent-color); margin-bottom: 15px; font-size: 2rem;">
          Thank You, Honourable Chief Minister
        </h2>
        <p style="color: #555; font-size: 1.1rem; line-height: 1.8; margin-bottom: 25px;">
          We express our sincere gratitude to the <strong>Honourable Chief Minister of Jammu & Kashmir</strong> 
          for inaugurating the official website of the <strong>Directorate of Tourism Kashmir</strong>.
        </p>
        <p style="color: #666; font-size: 1rem; margin-bottom: 30px;">
          Your vision and leadership continue to promote Kashmir's rich heritage and natural beauty to the world.
        </p>
        <div class="modal-buttons">
          <button id="confirmGoLive" class="modal-btn confirm" style="font-size: 1.1rem; padding: 14px 35px;">
            <i class="fas fa-rocket"></i> Launch Website
          </button>
        </div>
      </div>
    </div>
  `;

  // Insert modals into page
  document.addEventListener('DOMContentLoaded', function() {
    // Add both modals to body first
    document.body.insertAdjacentHTML('beforeend', passwordModalHTML);
    document.body.insertAdjacentHTML('beforeend', thankYouModalHTML);

    // Show/hide Go Live button based on URL parameter
    const goLiveSection = document.getElementById('goLiveSection');
    if (hasGoLiveParam && goLiveSection) {
      goLiveSection.style.display = 'block';
    }

    // Get elements
    const goLiveBtn = document.getElementById('goLiveBtn');
    const passwordModal = document.getElementById('passwordModal');
    const thankYouModal = document.getElementById('goLiveModal');
    const passwordInput = document.getElementById('adminPassword');
    const verifyBtn = document.getElementById('verifyPassword');
    const cancelBtn = document.getElementById('cancelPassword');
    const errorMessage = document.getElementById('errorMessage');
    const confirmBtn = document.getElementById('confirmGoLive');

    // Show password modal when Go Live button is clicked
    if (goLiveBtn) {
      goLiveBtn.addEventListener('click', function() {
        passwordModal.classList.add('active');
        passwordInput.value = '';
        passwordInput.focus();
        errorMessage.classList.remove('show');
        passwordInput.classList.remove('error');
      });
    }

    // Close password modal on cancel
    cancelBtn.addEventListener('click', function() {
      passwordModal.classList.remove('active');
    });

    // Close modals on outside click
    passwordModal.addEventListener('click', function(e) {
      if (e.target === passwordModal) {
        passwordModal.classList.remove('active');
      }
    });

    thankYouModal.addEventListener('click', function(e) {
      if (e.target === thankYouModal) {
        thankYouModal.classList.remove('active');
      }
    });

    // Close modals on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        if (passwordModal.classList.contains('active')) {
          passwordModal.classList.remove('active');
        }
        if (thankYouModal.classList.contains('active')) {
          thankYouModal.classList.remove('active');
        }
      }
    });

    // Verify password
    async function verifyPassword() {
      const enteredPassword = passwordInput.value.trim();
      
      // Disable button while verifying
      verifyBtn.disabled = true;
      verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';

      try {
        // Hash the entered password and compare
        const enteredHash = await hashPassword(enteredPassword);

        if (enteredHash === PASSWORD_HASH) {
          // Password correct - close password modal and show thank you modal
          passwordModal.classList.remove('active');
          thankYouModal.classList.add('active');
          
          // Reset button
          verifyBtn.disabled = false;
          verifyBtn.innerHTML = '<i class="fas fa-check"></i> Verify';
        } else {
          // Password incorrect - show error
          errorMessage.classList.add('show');
          passwordInput.classList.add('error');
          passwordInput.value = '';
          passwordInput.focus();

          // Reset button
          verifyBtn.disabled = false;
          verifyBtn.innerHTML = '<i class="fas fa-check"></i> Verify';

          // Remove error styling after animation
          setTimeout(function() {
            passwordInput.classList.remove('error');
          }, 500);
        }
      } catch (error) {
        console.error('Password verification error:', error);
        errorMessage.classList.add('show');
        verifyBtn.disabled = false;
        verifyBtn.innerHTML = '<i class="fas fa-check"></i> Verify';
      }
    }

    // Handle verify button click
    verifyBtn.addEventListener('click', verifyPassword);

    // Handle Enter key in password input
    passwordInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        verifyPassword();
      }
    });

    // Launch website
    function launchWebsite() {
      // Set live flag
      localStorage.setItem(LIVE_FLAG, 'true');
      
      // Show success message
      thankYouModal.innerHTML = `
        <div class="modal-content" style="text-align: center;">
          <div style="font-size: 4rem; color: #10b981; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i>
          </div>
          <h2 style="color: #10b981; margin-bottom: 10px;">Website is Now Live!</h2>
          <p style="color: #666; font-size: 1.1rem;">Welcome to the official Tourism Kashmir portal...</p>
          <div style="margin-top: 20px;">
            <div class="spinner" style="
              border: 3px solid #f3f3f3;
              border-top: 3px solid #10b981;
              border-radius: 50%;
              width: 40px;
              height: 40px;
              animation: spin 1s linear infinite;
              margin: 0 auto;
            "></div>
          </div>
        </div>
      `;

      // Add spinner animation
      if (!document.getElementById('spinnerStyle')) {
        const style = document.createElement('style');
        style.id = 'spinnerStyle';
        style.textContent = `
          @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
          }
        `;
        document.head.appendChild(style);
      }

      // Redirect to main site after 2.5 seconds
      setTimeout(function() {
        window.location.href = '/';
      }, 2500);
    }

    // Handle confirm button click
    confirmBtn.addEventListener('click', launchWebsite);
  });

  // Optional: Add a way to disable live mode (for testing)
  // Uncomment the next line and run in console to go back to coming-soon mode:
  // localStorage.removeItem('siteIsLive');
})();
