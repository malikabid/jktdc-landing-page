async function loadComponent(url, placeholderId) {
    const response = await fetch(url);
    const text = await response.text();
    document.getElementById(placeholderId).innerHTML = text;
}

function handleFlexibleMenu() {
    const navbar = document.querySelector('.navbar');
    const navbarList = document.querySelector('.navbar-list');
    
    if (!navbar || !navbarList) return;

    function calculateVisibleItems() {
        // Only run flexible menu on desktop (screens above 768px)
        if (window.innerWidth <= 768) {
            // Remove hidden-item class on mobile to show all items
            const items = Array.from(navbarList.querySelectorAll(':scope > li.hidden-item'));
            items.forEach(item => item.classList.remove('hidden-item'));
            
            // Remove more-menu on mobile
            const moreMenu = navbarList.querySelector('li.more-menu');
            if (moreMenu) {
                moreMenu.remove();
            }
            return;
        }

        const navbarWidth = navbar.offsetWidth;
        // Select ONLY direct children of navbar-list (top-level items only)
        const items = Array.from(navbarList.querySelectorAll(':scope > li:not(.more-menu)'));
        let moreMenu = navbarList.querySelector('li.more-menu');
        
        // Remove hidden-item class temporarily to calculate
        items.forEach(item => item.classList.remove('hidden-item'));
        
        let totalWidth = 0;
        const moreMenuWidth = 80; // Approximate width of ">>" button
        let visibleCount = 0;

        for (let item of items) {
            const itemWidth = item.offsetWidth;
            totalWidth += itemWidth;
            
            if (totalWidth + moreMenuWidth < navbarWidth) {
                visibleCount++;
            } else {
                break;
            }
        }

        // Hide items that don't fit
        items.forEach((item, index) => {
            if (index >= visibleCount) {
                item.classList.add('hidden-item');
            }
        });

        // Show/hide more menu button
        const hiddenItems = items.filter((_, idx) => idx >= visibleCount);
        if (hiddenItems.length > 0) {
            if (!moreMenu) {
                moreMenu = document.createElement('li');
                moreMenu.className = 'more-menu';
                moreMenu.innerHTML = `
                    <a href="#" onclick="event.preventDefault()">»</a>
                    <ul class="more-menu-dropdown"></ul>
                `;
                navbarList.appendChild(moreMenu);
                
                // Add mouseenter listener to adjust more-menu positioning
                moreMenu.addEventListener('mouseenter', function() {
                    const dropdown = this.querySelector('.more-menu-dropdown');
                    if (dropdown) {
                        dropdown.classList.remove('align-left', 'align-right');
                        dropdown.style.visibility = 'hidden';
                        dropdown.style.display = 'block';
                        const rect = dropdown.getBoundingClientRect();
                        dropdown.style.visibility = '';
                        dropdown.style.display = '';
                        
                        if (rect.right > window.innerWidth) {
                            dropdown.classList.add('align-left');
                        } else {
                            dropdown.classList.add('align-right');
                        }
                    }
                });
            }
            
            const dropdown = moreMenu.querySelector('.more-menu-dropdown');
            dropdown.innerHTML = hiddenItems.map(item => {
                const link = item.querySelector(':scope > a');
                return `<li><a href="${link.href}">${link.textContent}</a></li>`;
            }).join('');
        } else if (moreMenu) {
            moreMenu.remove();
        }
    }

    // Calculate on resize
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(calculateVisibleItems, 250);
    });

    // Calculate initial state after DOM is fully rendered
    requestAnimationFrame(() => {
        calculateVisibleItems();
    });
}

async function initialize() {
    await loadComponent('/header.html', 'header-placeholder');
    await loadComponent('/footer.html', 'footer-placeholder');

    // Wait for DOM to be ready
    setTimeout(() => {
        handleFlexibleMenu();

        // Function to adjust submenu position based on viewport
        function adjustSubmenuPosition() {
            const menuItems = document.querySelectorAll('.navbar-list > li');
            
            menuItems.forEach(item => {
                const submenu = item.querySelector(':scope > ul');
                if (submenu) {
                    // Remove existing position class
                    submenu.classList.remove('align-left', 'align-right');
                    
                    // Force a reflow to get accurate position while keeping it invisible
                    submenu.style.visibility = 'hidden';
                    submenu.style.display = 'block';
                    const rect = submenu.getBoundingClientRect();
                    submenu.style.visibility = '';
                    submenu.style.display = '';
                    
                    // Check if submenu goes beyond right edge of viewport
                    if (rect.right > window.innerWidth) {
                        // Align to the left to keep it on screen
                        submenu.classList.add('align-left');
                    } else {
                        // Default alignment to the right
                        submenu.classList.add('align-right');
                    }
                }
            });
        }
        
        // Adjust on hover for desktop
        document.querySelectorAll('.navbar-list > li').forEach(item => {
            item.addEventListener('mouseenter', adjustSubmenuPosition);
        });
        
        // Adjust on window resize
        window.addEventListener('resize', adjustSubmenuPosition);

        // Menu toggle functionality
        const menuButton = document.querySelector('.menu-button');
        const navbar = document.querySelector('.navbar');
        
        if (menuButton) {
            menuButton.addEventListener('click', function(e) {
                e.stopPropagation();
                navbar.classList.toggle('active');
                document.body.style.overflow = navbar.classList.contains('active') ? 'hidden' : '';
            });
        }

        // Handle submenu clicks on mobile using event delegation
        document.addEventListener('click', function(e) {
            // Check if click is on a menu link with a submenu
            const navbarList = document.querySelector('.navbar-list');
            if (!navbarList || !navbarList.contains(e.target)) return;
            
            // Find the closest <a> tag that is a direct child of an <li>
            let link = e.target.closest('a');
            if (!link) return;
            
            let menuItem = link.closest('.navbar-list > li');
            if (!menuItem) return;
            
            const submenu = menuItem.querySelector(':scope > ul');
            if (!submenu) return;
            
            // Only handle on mobile
            if (window.innerWidth <= 768) {
                e.preventDefault();
                e.stopPropagation();
                
                // Close all other submenus
                document.querySelectorAll('.navbar-list > li.active').forEach(item => {
                    if (item !== menuItem) {
                        item.classList.remove('active');
                    }
                });
                
                // Toggle current submenu
                menuItem.classList.toggle('active');
            }
        });

        // Set active class based on current URL (for page navigation)
        const currentPath = window.location.pathname;
        const currentPathWithoutSlash = currentPath.endsWith('/') ? currentPath.slice(0, -1) : currentPath;
        
        // Don't set active state on coming-soon page
        if (!currentPathWithoutSlash.includes('coming-soon')) {
            document.querySelectorAll('.navbar-list li a').forEach(link => {
                const href = link.getAttribute('href');
                const hrefWithoutSlash = href.endsWith('/') ? href.slice(0, -1) : href;
                
                if (href !== '#' && (hrefWithoutSlash === currentPathWithoutSlash || hrefWithoutSlash === '/' && currentPathWithoutSlash === '')) {
                    link.parentElement.classList.add('active');

                    // Find parent menu item and add active class
                    const parentLi = link.parentElement.closest('ul')?.closest('li');
                    if (parentLi) {
                        parentLi.classList.add('active');
                    }
                }
            });
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            const navbar = document.querySelector('.navbar');
            if (navbar && !navbar.contains(e.target)) {
                navbar.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Close menu on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const navbar = document.querySelector('.navbar');
                if (navbar) {
                    navbar.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });
    }, 100);
}

initialize();