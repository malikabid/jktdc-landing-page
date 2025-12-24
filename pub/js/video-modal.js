// Video Modal Functionality
function initializeVideoModal() {
  const modal = document.getElementById('videoModal');
  const videoPlayer = document.getElementById('videoPlayer');
  const closeBtn = document.querySelector('.video-modal-close');
  const videoThumbnails = document.querySelectorAll('.video-thumbnail, .watch-video-btn');

  console.log('Video modal initialized');
  console.log('Found', videoThumbnails.length, 'video thumbnails');
  console.log('Modal element:', modal);
  console.log('Video player element:', videoPlayer);
  console.log('Close button:', closeBtn);

  if (!modal || !videoPlayer || !closeBtn) {
    console.error('Video modal elements not found');
    return;
  }

  // Open modal and load video
  videoThumbnails.forEach((thumbnail, index) => {
    console.log('Attaching click handler to thumbnail', index);
    thumbnail.addEventListener('click', function(e) {
      e.preventDefault();
      console.log('Video thumbnail clicked!');
      const videoUrl = this.getAttribute('data-video-url');
      console.log('Video URL:', videoUrl);
      
      // Add autoplay parameter to YouTube URL
      const autoplayUrl = videoUrl + (videoUrl.includes('?') ? '&' : '?') + 'autoplay=1';
      
      videoPlayer.src = autoplayUrl;
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden'; // Prevent background scrolling
      console.log('Modal opened');
    });
  });

  // Close modal function
  function closeModal() {
    console.log('Closing modal');
    modal.style.display = 'none';
    videoPlayer.src = ''; // Stop video playback
    document.body.style.overflow = ''; // Restore scrolling
  }

  // Close modal on X button click
  if (closeBtn) {
    closeBtn.addEventListener('click', closeModal);
  }

  // Close modal when clicking outside the video
  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      closeModal();
    }
  });

  // Close modal on Escape key press
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal.style.display === 'flex') {
      closeModal();
    }
  });
}

// Try to initialize immediately
document.addEventListener('DOMContentLoaded', function() {
  console.log('DOM Content Loaded');
  initializeVideoModal();
});

// Also try after a short delay to ensure dynamic content is loaded
window.addEventListener('load', function() {
  console.log('Window Loaded');
  setTimeout(function() {
    console.log('Delayed initialization');
    initializeVideoModal();
  }, 500);
});
