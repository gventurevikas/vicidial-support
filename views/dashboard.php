<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vicidial Support Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .alert-card {
            border-left: 4px solid #dc3545;
        }
        .warning-card {
            border-left: 4px solid #ffc107;
        }
        .info-card {
            border-left: 4px solid #17a2b8;
        }
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .metric-label {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        .instance-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
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
                            <a class="nav-link active text-white" href="/dashboard">
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
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshData()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="metric-label">Active Instances</div>
                                        <div class="metric-value" id="activeInstances">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-server fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="metric-label">Active Campaigns</div>
                                        <div class="metric-value" id="activeCampaigns">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bullhorn fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="metric-label">Active Caller IDs</div>
                                        <div class="metric-value" id="activeCallerIds">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-phone fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="metric-label">Active Alerts</div>
                                        <div class="metric-value" id="activeAlerts">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bell fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Campaign Performance</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="campaignPerformanceChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Server Health</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="serverHealthChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Alerts -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Alerts</h5>
                            </div>
                            <div class="card-body">
                                <div id="recentAlerts">
                                    <div class="text-center">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instance Status -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Instance Status</h5>
                            </div>
                            <div class="card-body">
                                <div id="instanceStatus">
                                    <div class="text-center">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let campaignPerformanceChart;
        let serverHealthChart;

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            loadRecentAlerts();
            loadInstanceStatus();
            
            // Auto-refresh every 30 seconds
            setInterval(function() {
                loadDashboardData();
                loadRecentAlerts();
                loadInstanceStatus();
            }, 30000);
        });

        // Load dashboard statistics
        function loadDashboardData() {
            // Load instances
            fetch('/api/instances')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('activeInstances').textContent = data.data.length;
                    }
                })
                .catch(error => console.error('Error loading instances:', error));

            // Load campaigns
            fetch('/api/campaigns')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const activeCampaigns = data.data.filter(c => c.status === 'active').length;
                        document.getElementById('activeCampaigns').textContent = activeCampaigns;
                        updateCampaignPerformanceChart(data.data);
                    }
                })
                .catch(error => console.error('Error loading campaigns:', error));

            // Load caller IDs
            fetch('/api/caller-ids')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const activeCallerIds = data.data.filter(c => c.status === 'active').length;
                        document.getElementById('activeCallerIds').textContent = activeCallerIds;
                    }
                })
                .catch(error => console.error('Error loading caller IDs:', error));

            // Load alerts
            fetch('/api/alerts')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('activeAlerts').textContent = data.data.length;
                    }
                })
                .catch(error => console.error('Error loading alerts:', error));

            // Load server metrics
            fetch('/api/server-metrics')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateServerHealthChart(data.data);
                    }
                })
                .catch(error => console.error('Error loading server metrics:', error));
        }

        // Update campaign performance chart
        function updateCampaignPerformanceChart(campaigns) {
            const ctx = document.getElementById('campaignPerformanceChart').getContext('2d');
            
            if (campaignPerformanceChart) {
                campaignPerformanceChart.destroy();
            }

            const activeCampaigns = campaigns.filter(c => c.status === 'active');
            const labels = activeCampaigns.map(c => c.campaign_name);
            const answerRates = activeCampaigns.map(c => c.target_answer_rate || 0);
            const conversionRates = activeCampaigns.map(c => c.target_conversion_rate || 0);

            campaignPerformanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Answer Rate (%)',
                        data: answerRates,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Conversion Rate (%)',
                        data: conversionRates,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        // Update server health chart
        function updateServerHealthChart(servers) {
            const ctx = document.getElementById('serverHealthChart').getContext('2d');
            
            if (serverHealthChart) {
                serverHealthChart.destroy();
            }

            const labels = servers.map(s => s.server_name);
            const cpuUsage = servers.map(s => s.cpu_usage || 0);
            const memoryUsage = servers.map(s => s.memory_usage || 0);

            serverHealthChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: cpuUsage,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 205, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Load recent alerts
        function loadRecentAlerts() {
            fetch('/api/alerts')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayRecentAlerts(data.data);
                    }
                })
                .catch(error => console.error('Error loading alerts:', error));
        }

        // Display recent alerts
        function displayRecentAlerts(alerts) {
            const container = document.getElementById('recentAlerts');
            
            if (alerts.length === 0) {
                container.innerHTML = '<p class="text-muted text-center">No active alerts</p>';
                return;
            }

            const recentAlerts = alerts.slice(0, 5);
            let html = '';

            recentAlerts.forEach(alert => {
                const alertClass = alert.alert_type === 'critical' ? 'alert-danger' : 
                                 alert.alert_type === 'warning' ? 'alert-warning' : 'alert-info';
                
                html += `
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        <strong>${alert.title}</strong><br>
                        <small class="text-muted">${alert.instance_name} - ${new Date(alert.created_at).toLocaleString()}</small><br>
                        ${alert.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // Load instance status
        function loadInstanceStatus() {
            fetch('/api/instances')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayInstanceStatus(data.data);
                    }
                })
                .catch(error => console.error('Error loading instance status:', error));
        }

        // Display instance status
        function displayInstanceStatus(instances) {
            const container = document.getElementById('instanceStatus');
            let html = '<div class="row">';

            instances.forEach(instance => {
                const statusClass = instance.status === 'active' ? 'success' : 
                                  instance.status === 'maintenance' ? 'warning' : 'danger';
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title mb-1">${instance.instance_name}</h6>
                                        <p class="card-text text-muted small">${instance.instance_description || 'No description'}</p>
                                    </div>
                                    <span class="badge bg-${statusClass}">${instance.status}</span>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-server me-1"></i>${instance.vicidial_db_host}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            container.innerHTML = html;
        }

        // Refresh all data
        function refreshData() {
            loadDashboardData();
            loadRecentAlerts();
            loadInstanceStatus();
        }
    </script>
</body>
</html> 