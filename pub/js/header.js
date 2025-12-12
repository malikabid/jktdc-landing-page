async function loadComponent(url, placeholderId) {
    const response = await fetch(url);
    const text = await response.text();
    document.getElementById(placeholderId).innerHTML = text;
}

async function initialize() {
    await loadComponent('/header.html', 'header-placeholder');
    await loadComponent('/footer.html', 'footer-placeholder');

    // Menu toggle functionality
    const menuButton = document.querySelector('.menu-button');
    const navbar = document.querySelector('.navbar');
    
    menuButton.addEventListener('click', function(e) {
        e.stopPropagation();
        navbar.classList.toggle('active');
        document.body.style.overflow = navbar.classList.contains('active') ? 'hidden' : '';
    });

    // Handle submenu clicks on mobile
    document.querySelectorAll('.navbar-list > li').forEach(menuItem => {
        const menuLink = menuItem.querySelector(':scope > a');
        const submenu = menuItem.querySelector(':scope > ul');
        
        if (menuLink && submenu) {
            menuLink.addEventListener('click', function(e) {
                // Only handle dropdown behavior on mobile
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Toggle active class
                    const wasActive = menuItem.classList.contains('active');
                    
                    // Close all other submenus
                    document.querySelectorAll('.navbar-list > li').forEach(item => {
                        if (item !== menuItem) {
                            item.classList.remove('active');
                        }
                    });
                    
                    // Toggle current submenu
                    menuItem.classList.toggle('active');
                }
            });
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
        if (!navbar.contains(e.target)) {
            navbar.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // Close menu on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            navbar.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
}

initialize();