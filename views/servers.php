<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servers - Vicidial Support System</title>
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
        .server-card {
            transition: transform 0.2s;
        }
        .server-card:hover {
            transform: translateY(-2px);
        }
        .status-active { border-left: 4px solid #28a745; }
        .status-inactive { border-left: 4px solid #dc3545; }
        .status-maintenance { border-left: 4px solid #ffc107; }
        .metric-good { color: #28a745; }
        .metric-warning { color: #ffc107; }
        .metric-danger { color: #dc3545; }
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
                            <a class="nav-link active text-white" href="/servers">
                                <i class="fas fa-server me-2"></i>
                                Servers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/alerts">
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
                    <h1 class="h2">Server Monitoring</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshServers()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Server Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Servers</h6>
                                        <h3 id="totalServers">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-server fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Healthy</h6>
                                        <h3 id="healthyServers">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-check-circle fa-2x"></i>
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
                                        <h6 class="card-title">Warning</h6>
                                        <h3 id="warningServers">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
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
                                        <h3 id="criticalServers">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-times-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Servers -->
                <div class="row" id="serversContainer">
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
        let servers = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadServers();
        });

        function loadServers() {
            fetch('/api/server-metrics')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        servers = data.data;
                        displayServers();
                        updateStatistics();
                    }
                })
                .catch(error => console.error('Error loading servers:', error));
        }

        function displayServers() {
            const container = document.getElementById('serversContainer');
            
            if (servers.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-server fa-3x text-muted mb-3"></i>
                                <h5>No Servers Found</h5>
                                <p class="text-muted">Add servers to your instances to monitor them.</p>
                            </div>
                        </div>
                    </div>
                `;
                return;
            }

            let html = '';
            servers.forEach(server => {
                const statusClass = `status-${server.status}`;
                const statusBadge = server.status === 'active' ? 'success' : 
                                   server.status === 'maintenance' ? 'warning' : 'danger';
                
                const cpuClass = getMetricClass(server.cpu_usage, 80);
                const memoryClass = getMetricClass(server.memory_usage, 85);
                const diskClass = getMetricClass(server.disk_usage, 90);
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card server-card ${statusClass}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="card-title mb-1">${server.server_name}</h5>
                                        <p class="card-text text-muted small">${server.instance_name}</p>
                                    </div>
                                    <span class="badge bg-${statusBadge}">${server.status}</span>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-server me-1"></i>${server.server_type}
                                    </small><br>
                                    <small class="text-muted">
                                        <i class="fas fa-network-wired me-1"></i>${server.ip_address}
                                    </small>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <small class="text-muted">CPU</small><br>
                                            <span class="fw-bold ${cpuClass}">${server.cpu_usage}%</span>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Memory</small><br>
                                            <span class="fw-bold ${memoryClass}">${server.memory_usage}%</span>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Disk</small><br>
                                            <span class="fw-bold ${diskClass}">${server.disk_usage}%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-database me-1"></i>DB Connections: ${server.database_connections || 0}
                                    </small><br>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>Last Update: ${new Date(server.recorded_at).toLocaleTimeString()}
                                    </small>
                                </div>
                                
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewServerDetails(${server.id})">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="viewServerHistory(${server.id})">
                                        <i class="fas fa-chart-line"></i> History
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function getMetricClass(value, threshold) {
            if (value >= threshold) return 'metric-danger';
            if (value >= threshold * 0.8) return 'metric-warning';
            return 'metric-good';
        }

        function updateStatistics() {
            const total = servers.length;
            const healthy = servers.filter(s => 
                s.cpu_usage < 80 && s.memory_usage < 85 && s.disk_usage < 90
            ).length;
            const warning = servers.filter(s => 
                (s.cpu_usage >= 80 && s.cpu_usage < 90) ||
                (s.memory_usage >= 85 && s.memory_usage < 95) ||
                (s.disk_usage >= 90 && s.disk_usage < 95)
            ).length;
            const critical = servers.filter(s => 
                s.cpu_usage >= 90 || s.memory_usage >= 95 || s.disk_usage >= 95
            ).length;

            document.getElementById('totalServers').textContent = total;
            document.getElementById('healthyServers').textContent = healthy;
            document.getElementById('warningServers').textContent = warning;
            document.getElementById('criticalServers').textContent = critical;
        }

        function viewServerDetails(serverId) {
            // Implement server details view
            showAlert('Server details view coming soon', 'info');
        }

        function viewServerHistory(serverId) {
            // Implement server history view
            showAlert('Server history view coming soon', 'info');
        }

        function refreshServers() {
            loadServers();
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