<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lists - Vicidial Support System</title>
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
        .list-card {
            transition: transform 0.2s;
        }
        .list-card:hover {
            transform: translateY(-2px);
        }
        .status-active { border-left: 4px solid #28a745; }
        .status-exhausted { border-left: 4px solid #dc3545; }
        .status-rotated { border-left: 4px solid #ffc107; }
        .status-inactive { border-left: 4px solid #6c757d; }
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
                            <a class="nav-link active text-white" href="/lists">
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
                    <h1 class="h2">Lists</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshLists()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label for="campaignFilter" class="form-label">Campaign</label>
                        <select class="form-select" id="campaignFilter" onchange="filterLists()">
                            <option value="">All Campaigns</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select class="form-select" id="statusFilter" onchange="filterLists()">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="exhausted">Exhausted</option>
                            <option value="rotated">Rotated</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="searchFilter" class="form-label">Search</label>
                        <input type="text" class="form-control" id="searchFilter" placeholder="Search lists..." onkeyup="filterLists()">
                    </div>
                </div>

                <!-- List Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Lists</h6>
                                        <h3 id="totalLists">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-list fa-2x"></i>
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
                                        <h3 id="activeLists">0</h3>
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
                                        <h6 class="card-title">Exhausted</h6>
                                        <h3 id="exhaustedLists">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
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
                                        <h6 class="card-title">Total Records</h6>
                                        <h3 id="totalRecords">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-database fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lists -->
                <div class="row" id="listsContainer">
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
        let lists = [];
        let campaigns = [];
        let filteredLists = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadCampaigns();
            loadLists();
        });

        function loadCampaigns() {
            fetch('/api/campaigns')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        campaigns = data.data;
                        populateCampaignFilter();
                    }
                })
                .catch(error => console.error('Error loading campaigns:', error));
        }

        function loadLists() {
            fetch('/api/lists')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        lists = data.data;
                        filteredLists = [...lists];
                        displayLists();
                        updateStatistics();
                    }
                })
                .catch(error => console.error('Error loading lists:', error));
        }

        function populateCampaignFilter() {
            const select = document.getElementById('campaignFilter');
            select.innerHTML = '<option value="">All Campaigns</option>';
            
            campaigns.forEach(campaign => {
                const option = document.createElement('option');
                option.value = campaign.id;
                option.textContent = `${campaign.campaign_name} (${campaign.instance_name})`;
                select.appendChild(option);
            });
        }

        function displayLists() {
            const container = document.getElementById('listsContainer');
            
            if (filteredLists.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-list fa-3x text-muted mb-3"></i>
                                <h5>No Lists Found</h5>
                                <p class="text-muted">Lists will appear here when campaigns are synced.</p>
                            </div>
                        </div>
                    </div>
                `;
                return;
            }

            let html = '';
            filteredLists.forEach(list => {
                const statusClass = `status-${list.status}`;
                const statusBadge = list.status === 'active' ? 'success' : 
                                   list.status === 'exhausted' ? 'danger' : 
                                   list.status === 'rotated' ? 'warning' : 'secondary';
                
                const progressPercent = list.total_records > 0 ? (list.processed_records / list.total_records) * 100 : 0;
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card list-card ${statusClass}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="card-title mb-1">${list.list_name}</h5>
                                        <p class="card-text text-muted small">${list.campaign_name}</p>
                                    </div>
                                    <span class="badge bg-${statusBadge}">${list.status}</span>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted">Total Records</small><br>
                                            <span class="fw-bold">${list.total_records.toLocaleString()}</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Valid Records</small><br>
                                            <span class="fw-bold">${list.valid_records.toLocaleString()}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Progress</small>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" role="progressbar" style="width: ${progressPercent}%" 
                                             aria-valuenow="${progressPercent}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted">${list.processed_records.toLocaleString()} / ${list.total_records.toLocaleString()}</small>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <small class="text-muted">Answer Rate</small><br>
                                            <span class="fw-bold">${list.answer_rate}%</span>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Conversion</small><br>
                                            <span class="fw-bold">${list.conversion_rate}%</span>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Hangup</small><br>
                                            <span class="fw-bold">${list.hangup_rate}%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewListDetails(${list.id})">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="rotateList(${list.id})">
                                        <i class="fas fa-sync-alt"></i> Rotate
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
            const total = lists.length;
            const active = lists.filter(l => l.status === 'active').length;
            const exhausted = lists.filter(l => l.status === 'exhausted').length;
            const totalRecords = lists.reduce((sum, list) => sum + list.total_records, 0);

            document.getElementById('totalLists').textContent = total;
            document.getElementById('activeLists').textContent = active;
            document.getElementById('exhaustedLists').textContent = exhausted;
            document.getElementById('totalRecords').textContent = totalRecords.toLocaleString();
        }

        function filterLists() {
            const campaignFilter = document.getElementById('campaignFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const searchFilter = document.getElementById('searchFilter').value.toLowerCase();

            filteredLists = lists.filter(list => {
                const campaignMatch = !campaignFilter || list.campaign_id == campaignFilter;
                const statusMatch = !statusFilter || list.status === statusFilter;
                const searchMatch = !searchFilter || 
                    list.list_name.toLowerCase().includes(searchFilter) ||
                    list.campaign_name.toLowerCase().includes(searchFilter);

                return campaignMatch && statusMatch && searchMatch;
            });

            displayLists();
        }

        function viewListDetails(listId) {
            // Implement list details view
            showAlert('List details view coming soon', 'info');
        }

        function rotateList(listId) {
            // Implement list rotation
            showAlert('List rotation coming soon', 'info');
        }

        function refreshLists() {
            loadLists();
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