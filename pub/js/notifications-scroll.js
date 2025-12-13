// Vertical scrolling ticker for notifications and events
window.addEventListener('load', function() {
    // Function to create auto-scroll for a container
    function createAutoScroll(containerId) {
        const container = document.getElementById(containerId);
        
        if (!container) {
            console.log(containerId + ' container not found');
            return;
        }
        
        let isPaused = false;
        let scrollSpeed = 1; // pixels per frame
        
        // Clone all items to create seamless loop
        const originalItems = container.innerHTML;
        container.innerHTML = originalItems + originalItems;
        
        // Set initial scroll position to 0
        container.scrollTop = 0;
        
        function autoScroll() {
            if (!isPaused) {
                // Scroll up by scrollSpeed pixels
                container.scrollTop += scrollSpeed;
                
                // Get the height of half the content (original items)
                const maxScroll = container.scrollHeight / 2;
                
                // When we've scrolled through the original items, reset to top
                if (container.scrollTop >= maxScroll) {
                    container.scrollTop = 0;
                }
            }
            
            requestAnimationFrame(autoScroll);
        }
        
        // Start the animation
        requestAnimationFrame(autoScroll);
        
        // Pause scrolling on hover
        container.addEventListener('mouseenter', function() {
            isPaused = true;
        });
        
        container.addEventListener('mouseleave', function() {
            isPaused = false;
        });
    }
    
    // Initialize auto-scroll for both sections
    createAutoScroll('notifications-scroll');
    createAutoScroll('events-scroll');
});
