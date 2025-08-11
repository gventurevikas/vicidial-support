<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts - Vicidial Support System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .main-content {
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .alert-critical { border-left: 4px solid #dc3545; }
        .alert-warning { border-left: 4px solid #ffc107; }
        .alert-info { border-left: 4px solid #17a2b8; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">Vicidial Support</h4>
                        <p class="text-white-50">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?></p>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/dashboard">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/instances">
                                <i class="fas fa-server me-2"></i>
                                Instances
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/campaigns">
                                <i class="fas fa-bullhorn me-2"></i>
                                Campaigns
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/caller-ids">
                                <i class="fas fa-phone me-2"></i>
                                Caller IDs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/lists">
                                <i class="fas fa-list me-2"></i>
                                Lists
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/servers">
                                <i class="fas fa-server me-2"></i>
                                Servers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="/alerts">
                                <i class="fas fa-bell me-2"></i>
                                Alerts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/reports">
                                <i class="fas fa-chart-bar me-2"></i>
                                Reports
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link text-white" href="/logout">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">System Alerts</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshAlerts()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="resolveAllAlerts()">
                                <i class="fas fa-check-double"></i> Resolve All
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label for="typeFilter" class="form-label">Alert Type</label>
                        <select class="form-select" id="typeFilter" onchange="filterAlerts()">
                            <option value="">All Types</option>
                            <option value="critical">Critical</option>
                            <option value="warning">Warning</option>
                            <option value="info">Info</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="categoryFilter" class="form-label">Category</label>
                        <select class="form-select" id="categoryFilter" onchange="filterAlerts()">
                            <option value="">All Categories</option>
                            <option value="server">Server</option>
                            <option value="caller_id">Caller ID</option>
                            <option value="list">List</option>
                            <option value="campaign">Campaign</option>
                            <option value="system">System</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select class="form-select" id="statusFilter" onchange="filterAlerts()">
                            <option value="">All Status</option>
                            <option value="unresolved">Unresolved</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="searchFilter" class="form-label">Search</label>
                        <input type="text" class="form-control" id="searchFilter" placeholder="Search alerts..." onkeyup="filterAlerts()">
                    </div>
                </div>

                <!-- Alert Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Alerts</h6>
                                        <h3 id="totalAlerts">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-bell fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Critical</h6>
                                        <h3 id="criticalAlerts">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Warnings</h6>
                                        <h3 id="warningAlerts">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-exclamation-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Info</h6>
                                        <h3 id="infoAlerts">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-info-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts List -->
                <div class="row" id="alertsContainer">
                    <div class="col-12">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let alerts = [];
        let filteredAlerts = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadAlerts();
        });

        function loadAlerts() {
            fetch('/api/alerts')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alerts = data.data;
                        filteredAlerts = [...alerts];
                        displayAlerts();
                        updateStatistics();
                    }
                })
                .catch(error => console.error('Error loading alerts:', error));
        }

        function displayAlerts() {
            const container = document.getElementById('alertsContainer');
            
            if (filteredAlerts.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-bell fa-3x text-muted mb-3"></i>
                                <h5>No Alerts Found</h5>
                                <p class="text-muted">All systems are running smoothly!</p>
                            </div>
                        </div>
                    </div>
                `;
                return;
            }

            let html = '';
            filteredAlerts.forEach(alert => {
                const alertClass = `alert-${alert.alert_type}`;
                const typeBadge = alert.alert_type === 'critical' ? 'danger' : 
                                 alert.alert_type === 'warning' ? 'warning' : 'info';
                
                html += `
                    <div class="col-12 mb-3">
                        <div class="card ${alertClass}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <h6 class="card-title mb-0 me-2">${alert.title}</h6>
                                            <span class="badge bg-${typeBadge}">${alert.alert_type}</span>
                                            <span class="badge bg-secondary ms-2">${alert.alert_category}</span>
                                        </div>
                                        <p class="card-text">${alert.message}</p>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-server me-1"></i>${alert.instance_name}
                                                </small>
                                            </div>
                                            <div class="col-md-6 text-end">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>${new Date(alert.created_at).toLocaleString()}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        ${!alert.is_resolved ? `
                                            <button type="button" class="btn btn-sm btn-success" onclick="resolveAlert(${alert.id})">
                                                <i class="fas fa-check"></i> Resolve
                                            </button>
                                        ` : `
                                            <span class="badge bg-success">Resolved</span>
                                        `}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function updateStatistics() {
            const total = alerts.length;
            const critical = alerts.filter(a => a.alert_type === 'critical').length;
            const warning = alerts.filter(a => a.alert_type === 'warning').length;
            const info = alerts.filter(a => a.alert_type === 'info').length;

            document.getElementById('totalAlerts').textContent = total;
            document.getElementById('criticalAlerts').textContent = critical;
            document.getElementById('warningAlerts').textContent = warning;
            document.getElementById('infoAlerts').textContent = info;
        }

        function filterAlerts() {
            const typeFilter = document.getElementById('typeFilter').value;
            const categoryFilter = document.getElementById('categoryFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const searchFilter = document.getElementById('searchFilter').value.toLowerCase();

            filteredAlerts = alerts.filter(alert => {
                const typeMatch = !typeFilter || alert.alert_type === typeFilter;
                const categoryMatch = !categoryFilter || alert.alert_category === categoryFilter;
                const statusMatch = !statusFilter || 
                    (statusFilter === 'unresolved' && !alert.is_resolved) ||
                    (statusFilter === 'resolved' && alert.is_resolved);
                const searchMatch = !searchFilter || 
                    alert.title.toLowerCase().includes(searchFilter) ||
                    alert.message.toLowerCase().includes(searchFilter) ||
                    alert.instance_name.toLowerCase().includes(searchFilter);

                return typeMatch && categoryMatch && statusMatch && searchMatch;
            });

            displayAlerts();
        }

        function resolveAlert(alertId) {
            fetch(`/api/alerts/${alertId}/resolve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    loadAlerts();
                    showAlert('Alert resolved successfully!', 'success');
                } else {
                    showAlert('Failed to resolve alert', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to resolve alert', 'danger');
            });
        }

        function resolveAllAlerts() {
            if (confirm('Are you sure you want to resolve all alerts?')) {
                fetch('/api/alerts/resolve-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        loadAlerts();
                        showAlert('All alerts resolved successfully!', 'success');
                    } else {
                        showAlert('Failed to resolve alerts', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Failed to resolve alerts', 'danger');
                });
            }
        }

        function refreshAlerts() {
            loadAlerts();
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.top = '20px';
            alertDiv.style.right = '20px';
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html> 