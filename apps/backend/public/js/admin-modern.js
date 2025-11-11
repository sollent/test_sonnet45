/**
 * Modern Admin Theme JavaScript
 * Handles mobile menu, search, and other interactions
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeMobileMenu();
        initializeSearch();
        initializeTooltips();
        initializeColorFields();
        adjustContentMargin();
        enhanceUserExperience();
    });

    /**
     * Initialize mobile menu functionality
     */
    function initializeMobileMenu() {
        // Create mobile menu toggle button
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'mobile-menu-toggle';
        toggleBtn.innerHTML = '<i class="fa fa-bars"></i>';
        toggleBtn.setAttribute('aria-label', 'Toggle navigation menu');
        document.body.appendChild(toggleBtn);

        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);

        // Get sidebar
        const sidebar = document.querySelector('.main-sidebar, .sidebar, aside.main-sidebar');

        if (!sidebar) return;

        // Toggle menu on button click
        toggleBtn.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-open');
        });

        // Close menu on overlay click
        overlay.addEventListener('click', function() {
            document.body.classList.remove('sidebar-open');
        });

        // Close menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
                document.body.classList.remove('sidebar-open');
            }
        });
    }

    /**
     * Initialize global search functionality
     */
    function initializeSearch() {
        // Create search container if it doesn't exist
        const header = document.querySelector('.content-header, .page-header, .header');
        if (!header) return;

        // Check if search already exists
        const existingSearch = header.querySelector('.global-search');
        if (existingSearch) return;

        // Create search HTML
        const searchContainer = document.createElement('div');
        searchContainer.className = 'global-search';
        searchContainer.innerHTML = `
            <input type="search"
                   placeholder="Search... (Cmd+K)"
                   class="global-search-input"
                   aria-label="Global search">
        `;

        // Try to find a good place to insert it
        const title = header.querySelector('h1');
        if (title && title.parentNode) {
            // Create flex container if needed
            if (!header.style.display || header.style.display !== 'flex') {
                header.style.display = 'flex';
                header.style.alignItems = 'center';
                header.style.justifyContent = 'space-between';
                header.style.gap = '1.5rem';
            }
            header.appendChild(searchContainer);
        }

        // Add keyboard shortcut (Cmd+K or Ctrl+K)
        document.addEventListener('keydown', function(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.querySelector('.global-search-input');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });

        // Add search functionality
        const searchInput = searchContainer.querySelector('.global-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', debounce(function(e) {
                const query = e.target.value.toLowerCase();
                performSearch(query);
            }, 300));
        }
    }

    /**
     * Perform search
     */
    function performSearch(query) {
        if (!query) {
            // Clear search results
            document.querySelectorAll('.search-highlight').forEach(el => {
                el.classList.remove('search-highlight');
            });
            return;
        }

        // Search in table rows
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(query)) {
                row.style.display = '';
                highlightText(row, query);
            } else {
                row.style.display = 'none';
            }
        });
    }

    /**
     * Highlight search text
     */
    function highlightText(element, query) {
        // Simple highlight implementation
        element.classList.add('search-highlight');
    }

    /**
     * Initialize tooltips
     */
    function initializeTooltips() {
        // Add tooltips to all elements with title attribute
        document.querySelectorAll('[title]').forEach(element => {
            const title = element.getAttribute('title');
            element.setAttribute('data-tooltip', title);
            element.removeAttribute('title'); // Remove to prevent browser tooltip

            // Add tooltip listeners
            element.addEventListener('mouseenter', showTooltip);
            element.addEventListener('mouseleave', hideTooltip);
        });
    }

    /**
     * Show tooltip
     */
    function showTooltip(e) {
        const text = e.target.getAttribute('data-tooltip');
        if (!text) return;

        const tooltip = document.createElement('div');
        tooltip.className = 'custom-tooltip';
        tooltip.textContent = text;
        tooltip.style.position = 'fixed';
        tooltip.style.background = '#1f2937';
        tooltip.style.color = '#fff';
        tooltip.style.padding = '4px 8px';
        tooltip.style.borderRadius = '4px';
        tooltip.style.fontSize = '12px';
        tooltip.style.zIndex = '9999';
        tooltip.style.pointerEvents = 'none';

        document.body.appendChild(tooltip);

        // Position tooltip
        const rect = e.target.getBoundingClientRect();
        tooltip.style.left = rect.left + 'px';
        tooltip.style.top = (rect.bottom + 5) + 'px';

        e.target._tooltip = tooltip;
    }

    /**
     * Hide tooltip
     */
    function hideTooltip(e) {
        if (e.target._tooltip) {
            e.target._tooltip.remove();
            delete e.target._tooltip;
        }
    }

    /**
     * Initialize color field formatting
     */
    function initializeColorFields() {
        // Find color fields and format them nicely
        document.querySelectorAll('.field-color .badge').forEach(badge => {
            const parent = badge.parentElement;
            if (!parent) return;

            // Extract color from badge style
            const style = badge.getAttribute('style');
            const colorMatch = style ? style.match(/background-color:\s*(#[0-9a-fA-F]{6})/) : null;
            const color = colorMatch ? colorMatch[1] : '#000000';

            // Create new color display
            const colorDisplay = document.createElement('div');
            colorDisplay.className = 'color-display';
            colorDisplay.innerHTML = `
                <span class="color-dot" style="
                    display: inline-block;
                    width: 24px;
                    height: 24px;
                    border-radius: 50%;
                    background-color: ${color};
                    border: 2px solid #e5e7eb;
                    margin-right: 8px;
                    vertical-align: middle;
                "></span>
                <code style="
                    font-size: 0.875rem;
                    color: #6b7280;
                ">${color.toUpperCase()}</code>
            `;

            // Replace badge with new display
            parent.replaceChild(colorDisplay, badge);
        });
    }

    /**
     * Adjust content margin based on sidebar
     */
    function adjustContentMargin() {
        const sidebar = document.querySelector('.main-sidebar, .sidebar, aside.main-sidebar');
        const content = document.querySelector('.main-content, .content-wrapper, #main');

        function updateLayout() {
            if (!content) return;

            if (window.innerWidth > 768) {
                // Desktop: sidebar visible, add margin
                if (sidebar) {
                    content.style.marginLeft = sidebar.offsetWidth + 'px';
                    content.style.paddingTop = '';
                }
            } else {
                // Mobile: sidebar hidden, no margin, add top padding
                content.style.marginLeft = '0';
                content.style.paddingTop = '60px';
                content.style.width = '100%';

                // Close sidebar if open
                document.body.classList.remove('sidebar-open');
            }
        }

        // Initial layout
        updateLayout();

        // Adjust on window resize
        window.addEventListener('resize', debounce(updateLayout, 250));
    }

    /**
     * Enhance user experience with smooth transitions
     */
    function enhanceUserExperience() {
        // Add smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') return;

                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Add loading states to forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = form.querySelector('[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    const originalText = submitBtn.textContent;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

                    // Reset after some time if form doesn't redirect
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }, 10000);
                }
            });
        });

        // Enhance tables with better hover effects
        document.querySelectorAll('tbody tr').forEach(row => {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function(e) {
                // Don't trigger on button/link clicks
                if (e.target.closest('a, button')) return;

                // Find the first link in the row (usually edit)
                const link = row.querySelector('a');
                if (link) {
                    window.location = link.href;
                }
            });
        });

        // Add confirmation dialogs for delete actions
        document.querySelectorAll('.action-delete, [title*="Delete"]').forEach(deleteBtn => {
            deleteBtn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
        });
    }

    /**
     * Debounce utility function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Export for potential use elsewhere
    window.AdminTheme = {
        initializeMobileMenu,
        initializeSearch,
        performSearch,
        showTooltip,
        hideTooltip
    };

})();