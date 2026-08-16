// Convert any YouTube URL (youtu.be, watch?v=, shorts) into an embeddable /embed/ URL
// with branding/related-video suppression parameters:
//   rel=0             - limit related "more videos" to the same channel
//   modestbranding=1  - reduce the YouTube logo in the control bar
//   iv_load_policy=3  - hide video annotations
//   playsinline=1     - play inline on mobile instead of fullscreen
// Non-YouTube URLs (e.g. direct video files) are returned unchanged.
function toEmbedUrl(url) {
  if (!url) return url;
  const match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/))([\w-]{11})/);
  if (!match) return url;
  return `https://www.youtube-nocookie.com/embed/${match[1]}?rel=0&modestbranding=1&iv_load_policy=3&playsinline=1&color=white`;
}

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
      const videoUrl = toEmbedUrl(this.getAttribute('data-video-url'));
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
