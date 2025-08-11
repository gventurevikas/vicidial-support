<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instances - Vicidial Support System</title>
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
        .instance-card {
            transition: transform 0.2s;
        }
        .instance-card:hover {
            transform: translateY(-2px);
        }
        .status-active { border-left: 4px solid #28a745; }
        .status-inactive { border-left: 4px solid #dc3545; }
        .status-maintenance { border-left: 4px solid #ffc107; }
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
                            <a class="nav-link active text-white" href="/instances">
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
                    <h1 class="h2">Vicidial Instances</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshInstances()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="showAddInstanceModal()">
                                <i class="fas fa-plus"></i> Add Instance
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Instance Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Instances</h6>
                                        <h3 id="totalInstances">0</h3>
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
                                        <h6 class="card-title">Active</h6>
                                        <h3 id="activeInstances">0</h3>
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
                                        <h6 class="card-title">Maintenance</h6>
                                        <h3 id="maintenanceInstances">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-tools fa-2x"></i>
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
                                        <h6 class="card-title">Inactive</h6>
                                        <h3 id="inactiveInstances">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-times-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instances List -->
                <div class="row" id="instancesList">
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

    <!-- Add Instance Modal -->
    <div class="modal fade" id="addInstanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Vicidial Instance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addInstanceForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="instanceName" class="form-label">Instance Name *</label>
                                    <input type="text" class="form-control" id="instanceName" name="instance_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="instanceStatus" class="form-label">Status</label>
                                    <select class="form-select" id="instanceStatus" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="maintenance">Maintenance</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="instanceDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="instanceDescription" name="instance_description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dbHost" class="form-label">Database Host *</label>
                                    <input type="text" class="form-control" id="dbHost" name="vicidial_db_host" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dbPort" class="form-label">Database Port</label>
                                    <input type="number" class="form-control" id="dbPort" name="vicidial_db_port" value="3306">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dbName" class="form-label">Database Name *</label>
                                    <input type="text" class="form-control" id="dbName" name="vicidial_db_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dbUser" class="form-label">Database User *</label>
                                    <input type="text" class="form-control" id="dbUser" name="vicidial_db_user" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="dbPassword" class="form-label">Database Password *</label>
                            <input type="password" class="form-control" id="dbPassword" name="vicidial_db_password" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="webServerUrl" class="form-label">Web Server URL</label>
                                    <input type="url" class="form-control" id="webServerUrl" name="web_server_url">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dialerServerIp" class="form-label">Dialer Server IP</label>
                                    <input type="text" class="form-control" id="dialerServerIp" name="dialer_server_ip">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addInstance()">Add Instance</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Connection Modal -->
    <div class="modal fade" id="testConnectionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Test Connection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="testConnectionResult">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Testing...</span>
                            </div>
                            <p class="mt-2">Testing connection...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let instances = [];
        let addInstanceModal;
        let testConnectionModal;

        document.addEventListener('DOMContentLoaded', function() {
            addInstanceModal = new bootstrap.Modal(document.getElementById('addInstanceModal'));
            testConnectionModal = new bootstrap.Modal(document.getElementById('testConnectionModal'));
            loadInstances();
        });

        function loadInstances() {
            fetch('/api/instances')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        instances = data.data;
                        displayInstances();
                        updateStatistics();
                    }
                })
                .catch(error => console.error('Error loading instances:', error));
        }

        function displayInstances() {
            const container = document.getElementById('instancesList');
            
            if (instances.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-server fa-3x text-muted mb-3"></i>
                                <h5>No Instances Found</h5>
                                <p class="text-muted">Add your first Vicidial instance to get started.</p>
                                <button class="btn btn-primary" onclick="showAddInstanceModal()">
                                    <i class="fas fa-plus"></i> Add Instance
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                return;
            }

            let html = '';
            instances.forEach(instance => {
                const statusClass = `status-${instance.status}`;
                const statusBadge = instance.status === 'active' ? 'success' : 
                                   instance.status === 'maintenance' ? 'warning' : 'danger';
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card instance-card ${statusClass}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="card-title mb-1">${instance.instance_name}</h5>
                                        <p class="card-text text-muted small">${instance.instance_description || 'No description'}</p>
                                    </div>
                                    <span class="badge bg-${statusBadge}">${instance.status}</span>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-database me-1"></i>${instance.vicidial_db_host}:${instance.vicidial_db_port}
                                    </small><br>
                                    <small class="text-muted">
                                        <i class="fas fa-database me-1"></i>${instance.vicidial_db_name}
                                    </small>
                                </div>
                                
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="testConnection(${instance.id})">
                                        <i class="fas fa-plug"></i> Test
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="syncInstance(${instance.id})">
                                        <i class="fas fa-sync-alt"></i> Sync
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="viewInstance(${instance.id})">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function updateStatistics() {
            const total = instances.length;
            const active = instances.filter(i => i.status === 'active').length;
            const maintenance = instances.filter(i => i.status === 'maintenance').length;
            const inactive = instances.filter(i => i.status === 'inactive').length;

            document.getElementById('totalInstances').textContent = total;
            document.getElementById('activeInstances').textContent = active;
            document.getElementById('maintenanceInstances').textContent = maintenance;
            document.getElementById('inactiveInstances').textContent = inactive;
        }

        function showAddInstanceModal() {
            document.getElementById('addInstanceForm').reset();
            addInstanceModal.show();
        }

        function addInstance() {
            const form = document.getElementById('addInstanceForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            console.log('Adding instance with data:', data);

            fetch('/api/instances', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                console.log('Add instance result:', result);
                if (result.success) {
                    addInstanceModal.hide();
                    loadInstances();
                    showAlert('Instance added successfully!', 'success');
                } else {
                    showAlert('Failed to add instance: ' + (result.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to add instance: ' + error.message, 'danger');
            });
        }

        function testConnection(instanceId) {
            testConnectionModal.show();
            
            fetch('/api/test-connection', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ instance_id: instanceId })
            })
            .then(response => response.json())
            .then(result => {
                const resultDiv = document.getElementById('testConnectionResult');
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="text-center text-success">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <h5>Connection Successful!</h5>
                            <p>Successfully connected to the Vicidial database.</p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="text-center text-danger">
                            <i class="fas fa-times-circle fa-2x mb-2"></i>
                            <h5>Connection Failed!</h5>
                            <p>${result.error}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                const resultDiv = document.getElementById('testConnectionResult');
                resultDiv.innerHTML = `
                    <div class="text-center text-danger">
                        <i class="fas fa-times-circle fa-2x mb-2"></i>
                        <h5>Connection Failed!</h5>
                        <p>Network error occurred.</p>
                    </div>
                `;
            });
        }

        function syncInstance(instanceId) {
            fetch('/api/sync-instance', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ instance_id: instanceId })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showAlert('Instance synchronized successfully!', 'success');
                } else {
                    showAlert('Failed to sync instance', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to sync instance', 'danger');
            });
        }

        function viewInstance(instanceId) {
            window.location.href = `/campaigns?instance_id=${instanceId}`;
        }

        function refreshInstances() {
            loadInstances();
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