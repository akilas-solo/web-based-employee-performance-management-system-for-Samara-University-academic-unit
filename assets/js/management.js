/**
 * Samara University Academic Performance Evaluation System
 * Management UI JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize sidebar toggle
    initSidebar();
    
    // Initialize notifications
    initNotifications();
    
    // Initialize search functionality
    initSearch();
    
    // Initialize data tables if available
    initDataTables();
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize custom file inputs
    initCustomFileInputs();
    
    // Initialize tooltips and popovers
    initTooltipsAndPopovers();
    
    // Initialize sidebar toggle functionality
    function initSidebar() {
        const sidebarDesktopToggler = document.getElementById('sidebarDesktopToggler');
        const sidebarMobileToggler = document.getElementById('sidebarMobileToggler');
        const sidebarToggler = document.getElementById('sidebarToggler');
        const sidebar = document.querySelector('.sidebar');
        const dashboardContent = document.querySelector('.dashboard-content');
        
        // Create backdrop for mobile sidebar if it doesn't exist
        let backdrop = document.querySelector('.sidebar-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.classList.add('sidebar-backdrop');
            document.body.appendChild(backdrop);
        }
        
        // Desktop sidebar toggle
        if (sidebarDesktopToggler && sidebar && dashboardContent) {
            sidebarDesktopToggler.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('collapsed');
                dashboardContent.classList.toggle('expanded');
                
                // Save state to localStorage
                const newState = sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded';
                localStorage.setItem('sidebarState', newState);
            });
        }
        
        // Mobile sidebar toggle
        if (sidebarMobileToggler && sidebar) {
            sidebarMobileToggler.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.add('show');
                backdrop.classList.add('show');
                document.body.classList.add('sidebar-open');
            });
        }
        
        // Close sidebar when clicking the X button
        if (sidebarToggler && sidebar && backdrop) {
            sidebarToggler.addEventListener('click', function() {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
                document.body.classList.remove('sidebar-open');
            });
        }
        
        // Close sidebar when clicking outside of it
        if (backdrop && sidebar) {
            backdrop.addEventListener('click', function() {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
                document.body.classList.remove('sidebar-open');
            });
        }
        
        // Check for saved sidebar state on page load
        const sidebarState = localStorage.getItem('sidebarState');
        if (sidebarState === 'collapsed' && sidebar && dashboardContent) {
            sidebar.classList.add('collapsed');
            dashboardContent.classList.add('expanded');
        }
    }
    
    // Initialize notifications functionality
    function initNotifications() {
        const notificationDropdown = document.querySelector('.notification-dropdown');
        
        if (notificationDropdown) {
            const notificationToggle = notificationDropdown.querySelector('.notification-dropdown-toggle');
            const notificationMenu = notificationDropdown.querySelector('.dropdown-menu');
            
            // Mark all notifications as read
            const markAllReadBtn = document.querySelector('.mark-all-read');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Send AJAX request to mark all notifications as read
                    fetch('includes/ajax_handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=mark_all_notifications_read'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update UI
                            const notificationBadge = document.querySelector('.notification-badge');
                            if (notificationBadge) {
                                notificationBadge.style.display = 'none';
                            }
                            
                            const notificationItems = document.querySelectorAll('.notification-item.unread');
                            notificationItems.forEach(item => {
                                item.classList.remove('unread');
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error marking notifications as read:', error);
                    });
                });
            }
        }
    }
    
    // Initialize search functionality
    function initSearch() {
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        
        if (searchForm && searchInput) {
            searchForm.addEventListener('submit', function(e) {
                if (searchInput.value.trim() === '') {
                    e.preventDefault();
                }
            });
        }
    }
    
    // Initialize DataTables if available
    function initDataTables() {
        if (typeof $.fn.DataTable !== 'undefined') {
            $('.datatable').DataTable({
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    paginate: {
                        first: '<i class="fas fa-angle-double-left"></i>',
                        previous: '<i class="fas fa-angle-left"></i>',
                        next: '<i class="fas fa-angle-right"></i>',
                        last: '<i class="fas fa-angle-double-right"></i>'
                    }
                }
            });
        }
    }
    
    // Initialize form validation
    function initFormValidation() {
        const forms = document.querySelectorAll('.needs-validation');
        
        if (forms.length > 0) {
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        }
    }
    
    // Initialize custom file inputs
    function initCustomFileInputs() {
        const customFileInputs = document.querySelectorAll('.custom-file-input');
        
        if (customFileInputs.length > 0) {
            Array.from(customFileInputs).forEach(input => {
                input.addEventListener('change', function() {
                    const fileName = this.files[0].name;
                    const label = this.nextElementSibling;
                    
                    if (label) {
                        label.textContent = fileName;
                    }
                });
            });
        }
    }
    
    // Initialize tooltips and popovers
    function initTooltipsAndPopovers() {
        // Initialize tooltips if Bootstrap is available
        if (typeof $ !== 'undefined' && typeof $.fn.tooltip !== 'undefined') {
            $('[data-toggle="tooltip"]').tooltip();
        }
        
        // Initialize popovers if Bootstrap is available
        if (typeof $ !== 'undefined' && typeof $.fn.popover !== 'undefined') {
            $('[data-toggle="popover"]').popover();
        }
    }
});
