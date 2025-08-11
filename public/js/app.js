/**
 * Vicidial Support System - Main JavaScript
 */

// Global application object
window.VicidialApp = {
    // Configuration
    config: {
        apiBase: '/api',
        refreshInterval: 30000, // 30 seconds
        chartColors: ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe']
    },
    
    // Initialize application
    init() {
        this.setupEventListeners();
        this.setupAutoRefresh();
        this.setupCharts();
        this.setupNotifications();
        console.log('Vicidial Support System initialized');
    },
    
    // Setup event listeners
    setupEventListeners() {
        // Navigation
        document.addEventListener('DOMContentLoaded', () => {
            this.setupNavigation();
            this.setupForms();
            this.setupModals();
            this.setupTables();
        });
        
        // API error handling
        this.setupApiErrorHandling();
    },
    
    // Setup navigation
    setupNavigation() {
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // Add loading state
                this.showLoading();
            });
        });
    },
    
    // Setup forms
    setupForms() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                this.handleFormSubmit(e);
            });
        });
    },
    
    // Setup modals
    setupModals() {
        const modalTriggers = document.querySelectorAll('[data-bs-toggle="modal"]');
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                this.handleModalTrigger(e);
            });
        });
    },
    
    // Setup tables
    setupTables() {
        const tables = document.querySelectorAll('.table');
        tables.forEach(table => {
            this.setupTableSorting(table);
            this.setupTableFiltering(table);
        });
    },
    
    // Setup auto refresh
    setupAutoRefresh() {
        if (window.location.pathname === '/dashboard') {
            setInterval(() => {
                this.refreshDashboard();
            }, this.config.refreshInterval);
        }
    },
    
    // Setup charts
    setupCharts() {
        const chartElements = document.querySelectorAll('[data-chart]');
        chartElements.forEach(element => {
            this.createChart(element);
        });
    },
    
    // Setup notifications
    setupNotifications() {
        // Check for browser notifications support
        if ('Notification' in window) {
            Notification.requestPermission();
        }
    },
    
    // API methods
    async apiCall(endpoint, options = {}) {
        const url = this.config.apiBase + endpoint;
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        };
        
        try {
            const response = await fetch(url, { ...defaultOptions, ...options });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API call failed:', error);
            this.showError('API request failed: ' + error.message);
            throw error;
        }
    },
    
    // Dashboard refresh
    async refreshDashboard() {
        try {
            const [instances, campaigns, alerts] = await Promise.all([
                this.apiCall('/instances'),
                this.apiCall('/campaigns'),
                this.apiCall('/alerts')
            ]);
            
            this.updateDashboardStats(instances, campaigns, alerts);
        } catch (error) {
            console.error('Dashboard refresh failed:', error);
        }
    },
    
    // Update dashboard statistics
    updateDashboardStats(instances, campaigns, alerts) {
        // Update instance count
        const instanceCount = document.getElementById('instanceCount');
        if (instanceCount && instances.success) {
            instanceCount.textContent = instances.data.length;
        }
        
        // Update campaign count
        const campaignCount = document.getElementById('campaignCount');
        if (campaignCount && campaigns.success) {
            campaignCount.textContent = campaigns.data.length;
        }
        
        // Update alert count
        const alertCount = document.getElementById('alertCount');
        if (alertCount && alerts.success) {
            const activeAlerts = alerts.data.filter(alert => !alert.is_resolved);
            alertCount.textContent = activeAlerts.length;
        }
    },
    
    // Form submission handler
    async handleFormSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        
        // Show loading state
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
        }
        
        try {
            const response = await fetch(form.action, {
                method: form.method,
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(result.message || 'Operation completed successfully');
                if (result.redirect) {
                    window.location.href = result.redirect;
                }
            } else {
                this.showError(result.error || 'Operation failed');
            }
        } catch (error) {
            this.showError('Form submission failed: ' + error.message);
        } finally {
            // Reset button state
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Submit';
            }
        }
    },
    
    // Modal trigger handler
    handleModalTrigger(event) {
        const target = event.target.getAttribute('data-bs-target');
        const modal = document.querySelector(target);
        
        if (modal) {
            // Load modal content if needed
            this.loadModalContent(modal, event.target);
        }
    },
    
    // Load modal content
    async loadModalContent(modal, trigger) {
        const dataUrl = trigger.getAttribute('data-url');
        if (dataUrl) {
            try {
                const response = await this.apiCall(dataUrl);
                const modalBody = modal.querySelector('.modal-body');
                if (modalBody && response.success) {
                    modalBody.innerHTML = this.renderModalContent(response.data);
                }
            } catch (error) {
                console.error('Failed to load modal content:', error);
            }
        }
    },
    
    // Render modal content
    renderModalContent(data) {
        // This would be customized based on the modal type
        return `<div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">${data.title || 'Details'}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre>${JSON.stringify(data, null, 2)}</pre>
            </div>
        </div>`;
    },
    
    // Table sorting
    setupTableSorting(table) {
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.addEventListener('click', () => {
                this.sortTable(table, header);
            });
        });
    },
    
    // Sort table
    sortTable(table, header) {
        const column = header.getAttribute('data-sort');
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const isAscending = header.classList.contains('sort-asc');
        
        rows.sort((a, b) => {
            const aValue = a.querySelector(`[data-${column}]`).textContent;
            const bValue = b.querySelector(`[data-${column}]`).textContent;
            
            if (isAscending) {
                return bValue.localeCompare(aValue);
            } else {
                return aValue.localeCompare(bValue);
            }
        });
        
        // Update table
        const tbody = table.querySelector('tbody');
        rows.forEach(row => tbody.appendChild(row));
        
        // Update header state
        table.querySelectorAll('th').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
        });
        header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
    },
    
    // Table filtering
    setupTableFiltering(table) {
        const filterInput = table.parentElement.querySelector('.table-filter');
        if (filterInput) {
            filterInput.addEventListener('input', (e) => {
                this.filterTable(table, e.target.value);
            });
        }
    },
    
    // Filter table
    filterTable(table, filterValue) {
        const rows = table.querySelectorAll('tbody tr');
        const filterLower = filterValue.toLowerCase();
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filterLower) ? '' : 'none';
        });
    },
    
    // Create chart
    createChart(element) {
        const chartType = element.getAttribute('data-chart');
        const chartData = JSON.parse(element.getAttribute('data-chart-data') || '{}');
        
        // This would integrate with Chart.js or similar library
        console.log(`Creating ${chartType} chart with data:`, chartData);
    },
    
    // Show loading
    showLoading() {
        const loading = document.createElement('div');
        loading.className = 'loading-overlay';
        loading.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(loading);
    },
    
    // Hide loading
    hideLoading() {
        const loading = document.querySelector('.loading-overlay');
        if (loading) {
            loading.remove();
        }
    },
    
    // Show success message
    showSuccess(message) {
        this.showNotification(message, 'success');
    },
    
    // Show error message
    showError(message) {
        this.showNotification(message, 'danger');
    },
    
    // Show notification
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.notifications-container') || document.body;
        container.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    },
    
    // Setup API error handling
    setupApiErrorHandling() {
        window.addEventListener('unhandledrejection', (event) => {
            console.error('Unhandled promise rejection:', event.reason);
            this.showError('An unexpected error occurred');
        });
    }
};

// Initialize application when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    VicidialApp.init();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VicidialApp;
} 