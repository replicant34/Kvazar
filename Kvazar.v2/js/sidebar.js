document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    if (!sidebar || !content || !sidebarToggle) {
        console.error('Sidebar elements not found');
        return;
    }
    
    // Mobile detection
    const isMobile = window.innerWidth <= 768;
    
    // Initialize sidebar state based on screen size
    if (isMobile) {
        // On mobile, start with sidebar hidden and not collapsed
        sidebar.classList.remove('collapsed');
        content.classList.remove('collapsed');
        document.body.classList.remove('sidebar-active');
    } else {
        // On desktop, start collapsed
        sidebar.classList.add('collapsed');
        content.classList.add('collapsed');
    }
    
    // Simple toggle function
    function toggleSidebar() {
        if (isMobile) {
            document.body.classList.toggle('sidebar-active');
            // On mobile, always ensure sidebar is not collapsed when active
            if (document.body.classList.contains('sidebar-active')) {
                sidebar.classList.remove('collapsed');
                content.classList.remove('collapsed');
            }
        } else {
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('collapsed');
        }
    }
    
    // Custom collapse function
    function toggleCollapse(element) {
        if (element.classList.contains('show')) {
            element.classList.remove('show');
            return false; // collapsed
        } else {
            element.classList.add('show');
            return true; // expanded
        }
    }
    
    // Close all other submenus
    function closeOtherSubmenus(currentSubmenu) {
        document.querySelectorAll('.collapse.show').forEach(submenu => {
            if (submenu !== currentSubmenu) {
                submenu.classList.remove('show');
                const toggle = document.querySelector(`[href="#${submenu.id}"]`);
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
    
    // Toggle button click
    sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleSidebar();
    });
    
    // Handle main navigation links (non-dropdown)
    const mainNavLinks = sidebar.querySelectorAll('a:not(.dropdown-toggle)');
    mainNavLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!isMobile && sidebar.classList.contains('collapsed')) {
                console.log('Main nav link clicked in collapsed sidebar:', this.href);
                e.preventDefault();
                e.stopPropagation();
                
                // Open sidebar first
                toggleSidebar();
                
                // Navigate after delay
                if (this.href && !this.href.includes('#')) {
                    setTimeout(() => {
                        window.location.href = this.href;
                    }, 150);
                }
            }
        });
    });
    
    // Main sidebar click handler - improved
    sidebar.addEventListener('click', function(e) {
        if (isMobile) return;

        const link = e.target.closest('a');
        const isCollapsed = sidebar.classList.contains('collapsed');

        console.log('Sidebar click:', {
            target: e.target.tagName,
            className: e.target.className,
            isCollapsed: isCollapsed,
            isLink: !!link
        });

        if (link) {
            // If it's a dropdown toggle, let the dropdown handler manage it
            if (link.classList.contains('dropdown-toggle')) return;

            if (isCollapsed) {
                // Open sidebar, then navigate
                console.log('Link clicked in collapsed sidebar, opening sidebar first');
                e.preventDefault();
                e.stopPropagation();
                    toggleSidebar();
                if (link.href && !link.href.includes('#')) {
                        setTimeout(() => {
                        window.location.href = link.href;
                    }, 150);
                    }
                }
            // If expanded, let the link work normally (no preventDefault)
            return;
        }

        // If not clicking a link or button, toggle sidebar (collapse/expand)
        if (!e.target.closest('button')) {
            console.log('Empty space clicked, toggling sidebar');
            toggleSidebar();
        }
    });
    
    // Handle dropdown toggles - custom collapse
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isCollapsed = sidebar.classList.contains('collapsed');
            
            // If sidebar is collapsed, open it first and rotate arrow
            if (isCollapsed && !isMobile) {
                console.log('Dropdown toggle clicked in collapsed sidebar, opening sidebar first');
                toggleSidebar();
                
                // Rotate the arrow immediately
                this.setAttribute('aria-expanded', 'true');
                
                // Show the submenu after sidebar opens
                const submenuId = this.getAttribute('href');
                const submenu = document.querySelector(submenuId);
                if (submenu) {
                    setTimeout(() => {
                        submenu.classList.add('show');
                    }, 150);
                }
                return;
            }
            
            // Normal dropdown toggle behavior for expanded sidebar
            const submenuId = this.getAttribute('href');
            const submenu = document.querySelector(submenuId);
            
            if (submenu) {
                // Close other open submenus
                closeOtherSubmenus(submenu);
                
                // Toggle current submenu
                const isExpanded = toggleCollapse(submenu);
                this.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            }
        });
    });
    
    // Handle navigation links in submenus
    const submenuLinks = sidebar.querySelectorAll('.collapse a');
    submenuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!isMobile && sidebar.classList.contains('collapsed')) {
                console.log('Submenu link clicked in collapsed sidebar');
                e.preventDefault();
                e.stopPropagation();
                
                // Open sidebar first
                toggleSidebar();
                
                // Navigate after delay
                if (this.href && !this.href.includes('#')) {
                    setTimeout(() => {
                        window.location.href = this.href;
                    }, 150);
                }
            }
        });
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (isMobile && 
            document.body.classList.contains('sidebar-active') && 
            !sidebar.contains(e.target) && 
            e.target !== sidebarToggle) {
            document.body.classList.remove('sidebar-active');
            // Ensure sidebar is not collapsed when closing on mobile
            sidebar.classList.remove('collapsed');
            content.classList.remove('collapsed');
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        const wasMobile = isMobile;
        const isNowMobile = window.innerWidth <= 768;
        
        if (wasMobile !== isNowMobile) {
            location.reload();
        }
    });
}); 