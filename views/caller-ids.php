<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caller IDs - Vicidial Support System</title>
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
        .caller-id-card {
            transition: transform 0.2s;
        }
        .caller-id-card:hover {
            transform: translateY(-2px);
        }
        .status-active { border-left: 4px solid #28a745; }
        .status-blocked { border-left: 4px solid #dc3545; }
        .status-rotated { border-left: 4px solid #ffc107; }
        .status-testing { border-left: 4px solid #17a2b8; }
        .status-inactive { border-left: 4px solid #6c757d; }
        .performance-excellent { color: #28a745; }
        .performance-good { color: #17a2b8; }
        .performance-poor { color: #dc3545; }
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
                            <a class="nav-link active text-white" href="/caller-ids">
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
                    <h1 class="h2">Caller IDs</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshCallerIds()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="showAddCallerIdModal()">
                                <i class="fas fa-plus"></i> Add Caller ID
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <label for="instanceFilter" class="form-label">Instance</label>
                        <select class="form-select" id="instanceFilter" onchange="filterCallerIds()">
                            <option value="">All Instances</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="campaignFilter" class="form-label">Campaign</label>
                        <select class="form-select" id="campaignFilter" onchange="filterCallerIds()">
                            <option value="">All Campaigns</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select class="form-select" id="statusFilter" onchange="filterCallerIds()">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="blocked">Blocked</option>
                            <option value="rotated">Rotated</option>
                            <option value="testing">Testing</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="performanceFilter" class="form-label">Performance</label>
                        <select class="form-select" id="performanceFilter" onchange="filterCallerIds()">
                            <option value="">All Performance</option>
                            <option value="excellent">Excellent (>20%)</option>
                            <option value="good">Good (15-20%)</option>
                            <option value="poor">Poor (<15%)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="searchFilter" class="form-label">Search</label>
                        <input type="text" class="form-control" id="searchFilter" placeholder="Search caller IDs..." onkeyup="filterCallerIds()">
                    </div>
                </div>

                <!-- Caller ID Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Caller IDs</h6>
                                        <h3 id="totalCallerIds">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-phone fa-2x"></i>
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
                                        <h3 id="activeCallerIds">0</h3>
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
                                        <h6 class="card-title">Needs Rotation</h6>
                                        <h3 id="needsRotation">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-sync-alt fa-2x"></i>
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
                                        <h6 class="card-title">Blocked</h6>
                                        <h3 id="blockedCallerIds">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-ban fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Caller IDs List -->
                <div class="row" id="callerIdsList">
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

    <!-- Add Caller ID Modal -->
    <div class="modal fade" id="addCallerIdModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Caller ID</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCallerIdForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="callerIdCampaign" class="form-label">Campaign *</label>
                                    <select class="form-select" id="callerIdCampaign" name="campaign_id" required>
                                        <option value="">Select Campaign</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="callerIdStatus" class="form-label">Status</label>
                                    <select class="form-select" id="callerIdStatus" name="status">
                                        <option value="active">Active</option>
                                        <option value="testing">Testing</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phoneNumber" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phoneNumber" name="phone_number" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="callerIdName" class="form-label">Caller ID Name</label>
                                    <input type="text" class="form-control" id="callerIdName" name="caller_id_name">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addCallerId()">Add Caller ID</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rotate Caller ID Modal -->
    <div class="modal fade" id="rotateCallerIdModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rotate Caller ID</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="rotateCallerIdForm">
                        <input type="hidden" id="rotateCallerIdId" name="caller_id">
                        <div class="mb-3">
                            <label for="rotationReason" class="form-label">Rotation Reason *</label>
                            <select class="form-select" id="rotationReason" name="reason" required>
                                <option value="">Select Reason</option>
                                <option value="Low answer rate">Low answer rate</option>
                                <option value="High block rate">High block rate</option>
                                <option value="Customer complaints">Customer complaints</option>
                                <option value="Scheduled rotation">Scheduled rotation</option>
                                <option value="Performance issues">Performance issues</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="customReason" class="form-label">Custom Reason</label>
                            <textarea class="form-control" id="customReason" name="custom_reason" rows="3" placeholder="Enter custom reason if 'Other' is selected"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="rotateCallerId()">Rotate Caller ID</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let callerIds = [];
        let campaigns = [];
        let instances = [];
        let filteredCallerIds = [];
        let addCallerIdModal;
        let rotateCallerIdModal;

        document.addEventListener('DOMContentLoaded', function() {
            addCallerIdModal = new bootstrap.Modal(document.getElementById('addCallerIdModal'));
            rotateCallerIdModal = new bootstrap.Modal(document.getElementById('rotateCallerIdModal'));
            loadCampaigns();
            loadInstances();
            loadCallerIds();
        });

        function loadInstances() {
            fetch('/api/instances')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        instances = data.data;
                        populateInstanceFilter();
                    }
                })
                .catch(error => console.error('Error loading instances:', error));
        }

        function loadCampaigns() {
            fetch('/api/campaigns')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        campaigns = data.data;
                        populateCampaignFilter();
                        populateCallerIdCampaignSelect();
                    }
                })
                .catch(error => console.error('Error loading campaigns:', error));
        }

        function loadCallerIds() {
            const campaignId = new URLSearchParams(window.location.search).get('campaign_id');
            const instanceId = new URLSearchParams(window.location.search).get('instance_id');
            
            let url = '/api/caller-ids';
            const params = new URLSearchParams();
            
            if (campaignId) params.append('campaign_id', campaignId);
            if (instanceId) params.append('instance_id', instanceId);
            
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        callerIds = data.data;
                        filteredCallerIds = [...callerIds];
                        displayCallerIds();
                        updateStatistics();
                    }
                })
                .catch(error => console.error('Error loading caller IDs:', error));
        }

        function populateInstanceFilter() {
            const select = document.getElementById('instanceFilter');
            select.innerHTML = '<option value="">All Instances</option>';
            
            instances.forEach(instance => {
                const option = document.createElement('option');
                option.value = instance.id;
                option.textContent = `${instance.instance_name}`;
                select.appendChild(option);
            });
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

        function populateCallerIdCampaignSelect() {
            const select = document.getElementById('callerIdCampaign');
            select.innerHTML = '<option value="">Select Campaign</option>';
            
            campaigns.forEach(campaign => {
                const option = document.createElement('option');
                option.value = campaign.id;
                option.textContent = `${campaign.campaign_name} (${campaign.instance_name})`;
                select.appendChild(option);
            });
        }

        function displayCallerIds() {
            const container = document.getElementById('callerIdsList');
            
            if (filteredCallerIds.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-phone fa-3x text-muted mb-3"></i>
                                <h5>No Caller IDs Found</h5>
                                <p class="text-muted">Add your first caller ID to get started.</p>
                                <button class="btn btn-primary" onclick="showAddCallerIdModal()">
                                    <i class="fas fa-plus"></i> Add Caller ID
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                return;
            }

            let html = '';
            filteredCallerIds.forEach(callerId => {
                const statusClass = `status-${callerId.status}`;
                const statusBadge = callerId.status === 'active' ? 'success' : 
                                   callerId.status === 'blocked' ? 'danger' : 
                                   callerId.status === 'rotated' ? 'warning' : 
                                   callerId.status === 'testing' ? 'info' : 'secondary';
                
                const performanceClass = getPerformanceClass(callerId.answer_rate);
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card caller-id-card ${statusClass}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="card-title mb-1">${callerId.phone_number}</h5>
                                        <p class="card-text text-muted small">${callerId.campaign_name}</p>
                                    </div>
                                    <span class="badge bg-${statusBadge}">${callerId.status}</span>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted">Answer Rate</small><br>
                                            <span class="fw-bold ${performanceClass}">${callerId.answer_rate}%</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Block Rate</small><br>
                                            <span class="fw-bold">${callerId.block_rate}%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-phone me-1"></i>Total Calls: ${callerId.total_calls}
                                    </small><br>
                                    <small class="text-muted">
                                        <i class="fas fa-check me-1"></i>Successful: ${callerId.successful_calls}
                                    </small>
                                </div>
                                
                                <div class="btn-group w-100" role="group">
                                    ${callerId.status === 'active' ? `
                                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="showRotateModal(${callerId.id})">
                                            <i class="fas fa-sync-alt"></i> Rotate
                                        </button>
                                    ` : ''}
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewCallerIdDetails(${callerId.id})">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editCallerId(${callerId.id})">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function getPerformanceClass(answerRate) {
            if (answerRate >= 20) return 'performance-excellent';
            if (answerRate >= 15) return 'performance-good';
            return 'performance-poor';
        }

        function updateStatistics() {
            // Update statistics based on filtered results
            const total = filteredCallerIds.length;
            const active = filteredCallerIds.filter(c => c.status === 'active').length;
            const blocked = filteredCallerIds.filter(c => c.status === 'blocked').length;
            const needsRotation = filteredCallerIds.filter(c => c.status === 'active' && c.answer_rate < 15).length;

            document.getElementById('totalCallerIds').textContent = total;
            document.getElementById('activeCallerIds').textContent = active;
            document.getElementById('blockedCallerIds').textContent = blocked;
            document.getElementById('needsRotation').textContent = needsRotation;
        }

        function filterCallerIds() {
            const instanceFilter = document.getElementById('instanceFilter').value;
            const campaignFilter = document.getElementById('campaignFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const performanceFilter = document.getElementById('performanceFilter').value;
            const searchFilter = document.getElementById('searchFilter').value.toLowerCase();

            filteredCallerIds = callerIds.filter(callerId => {
                const instanceMatch = !instanceFilter || callerId.instance_id == instanceFilter;
                const campaignMatch = !campaignFilter || callerId.campaign_id == campaignFilter;
                const statusMatch = !statusFilter || callerId.status === statusFilter;
                const searchMatch = !searchFilter || 
                    callerId.phone_number.toLowerCase().includes(searchFilter) ||
                    callerId.campaign_name.toLowerCase().includes(searchFilter);

                let performanceMatch = true;
                if (performanceFilter) {
                    if (performanceFilter === 'excellent') {
                        performanceMatch = callerId.answer_rate >= 20;
                    } else if (performanceFilter === 'good') {
                        performanceMatch = callerId.answer_rate >= 15 && callerId.answer_rate < 20;
                    } else if (performanceFilter === 'poor') {
                        performanceMatch = callerId.answer_rate < 15;
                    }
                }

                return instanceMatch && campaignMatch && statusMatch && performanceMatch && searchMatch;
            });

            displayCallerIds();
            updateStatistics();
        }

        function showAddCallerIdModal() {
            document.getElementById('addCallerIdForm').reset();
            addCallerIdModal.show();
        }

        function addCallerId() {
            const form = document.getElementById('addCallerIdForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            fetch('/api/caller-ids', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    addCallerIdModal.hide();
                    loadCallerIds();
                    showAlert('Caller ID added successfully!', 'success');
                } else {
                    showAlert('Failed to add caller ID: ' + result.error, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to add caller ID', 'danger');
            });
        }

        function showRotateModal(callerId) {
            document.getElementById('rotateCallerIdId').value = callerId;
            document.getElementById('rotateCallerIdForm').reset();
            rotateCallerIdModal.show();
        }

        function rotateCallerId() {
            const form = document.getElementById('rotateCallerIdForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            const reason = data.reason === 'Other' ? data.custom_reason : data.reason;

            fetch('/api/rotate-caller-id', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    caller_id: data.caller_id,
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    rotateCallerIdModal.hide();
                    loadCallerIds();
                    showAlert('Caller ID rotated successfully!', 'success');
                } else {
                    showAlert('Failed to rotate caller ID: ' + result.error, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to rotate caller ID', 'danger');
            });
        }

        function viewCallerIdDetails(callerId) {
            // Implement caller ID details view
            showAlert('Details view coming soon', 'info');
        }

        function editCallerId(callerId) {
            // Implement edit functionality
            showAlert('Edit functionality coming soon', 'info');
        }

        function refreshCallerIds() {
            loadCallerIds();
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