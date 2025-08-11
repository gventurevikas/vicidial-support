<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaigns - Vicidial Support System</title>
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
        .campaign-card {
            transition: transform 0.2s;
        }
        .campaign-card:hover {
            transform: translateY(-2px);
        }
        .status-active { border-left: 4px solid #28a745; }
        .status-paused { border-left: 4px solid #ffc107; }
        .status-stopped { border-left: 4px solid #dc3545; }
        .performance-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .performance-excellent { background-color: #28a745; }
        .performance-good { background-color: #17a2b8; }
        .performance-poor { background-color: #dc3545; }
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
                            <a class="nav-link active text-white" href="/campaigns">
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
                    <h1 class="h2">Campaigns</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshCampaigns()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-sm btn-success" onclick="syncFromVicidial()" id="syncButton">
                                <i class="fas fa-download"></i> Sync from Vicidial
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="showAddCampaignModal()">
                                <i class="fas fa-plus"></i> Add Campaign
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label for="instanceFilter" class="form-label">Instance</label>
                        <select class="form-select" id="instanceFilter" onchange="filterCampaigns()">
                            <option value="">All Instances</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select class="form-select" id="statusFilter" onchange="filterCampaigns()">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                            <option value="stopped">Stopped</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="typeFilter" class="form-label">Type</label>
                        <select class="form-select" id="typeFilter" onchange="filterCampaigns()">
                            <option value="">All Types</option>
                            <option value="healthcare">Healthcare</option>
                            <option value="financial">Financial</option>
                            <option value="retail">Retail</option>
                            <option value="nonprofit">Non-Profit</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="searchFilter" class="form-label">Search</label>
                        <input type="text" class="form-control" id="searchFilter" placeholder="Search campaigns..." onkeyup="filterCampaigns()">
                    </div>
                </div>

                <!-- Campaign Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Campaigns</h6>
                                        <h3 id="totalCampaigns">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-bullhorn fa-2x"></i>
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
                                        <h3 id="activeCampaigns">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-play-circle fa-2x"></i>
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
                                        <h6 class="card-title">Paused</h6>
                                        <h3 id="pausedCampaigns">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-pause-circle fa-2x"></i>
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
                                        <h6 class="card-title">Stopped</h6>
                                        <h3 id="stoppedCampaigns">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-stop-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Campaigns List -->
                <div class="row" id="campaignsList">
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

    <!-- Add Campaign Modal -->
    <div class="modal fade" id="addCampaignModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Campaign</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCampaignForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="campaignInstance" class="form-label">Instance *</label>
                                    <select class="form-select" id="campaignInstance" name="instance_id" required>
                                        <option value="">Select Instance</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="campaignStatus" class="form-label">Status</label>
                                    <select class="form-select" id="campaignStatus" name="status">
                                        <option value="active">Active</option>
                                        <option value="paused">Paused</option>
                                        <option value="stopped">Stopped</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="campaignName" class="form-label">Campaign Name *</label>
                                    <input type="text" class="form-control" id="campaignName" name="campaign_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="campaignType" class="form-label">Campaign Type</label>
                                    <select class="form-select" id="campaignType" name="campaign_type">
                                        <option value="general">General</option>
                                        <option value="healthcare">Healthcare</option>
                                        <option value="financial">Financial</option>
                                        <option value="retail">Retail</option>
                                        <option value="nonprofit">Non-Profit</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="targetAnswerRate" class="form-label">Target Answer Rate (%)</label>
                                    <input type="number" class="form-control" id="targetAnswerRate" name="target_answer_rate" value="15" min="0" max="100" step="0.1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="targetConversionRate" class="form-label">Target Conversion Rate (%)</label>
                                    <input type="number" class="form-control" id="targetConversionRate" name="target_conversion_rate" value="2" min="0" max="100" step="0.1">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rotationFrequency" class="form-label">Rotation Frequency (hours)</label>
                                    <input type="number" class="form-control" id="rotationFrequency" name="rotation_frequency_hours" value="48" min="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="autoRotation" class="form-label">Auto Rotation</label>
                                    <select class="form-select" id="autoRotation" name="auto_rotation_enabled">
                                        <option value="1">Enabled</option>
                                        <option value="0">Disabled</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addCampaign()">Add Campaign</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaign Details Modal -->
    <div class="modal fade" id="campaignDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Campaign Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="campaignDetailsContent">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let campaigns = [];
        let instances = [];
        let filteredCampaigns = [];
        let addCampaignModal;
        let campaignDetailsModal;

        document.addEventListener('DOMContentLoaded', function() {
            addCampaignModal = new bootstrap.Modal(document.getElementById('addCampaignModal'));
            campaignDetailsModal = new bootstrap.Modal(document.getElementById('campaignDetailsModal'));
            loadInstances();
            loadCampaigns();
        });

        function loadInstances() {
            fetch('/api/instances')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        instances = data.data;
                        populateInstanceFilter();
                        populateCampaignInstanceSelect();
                    }
                })
                .catch(error => console.error('Error loading instances:', error));
        }

        function loadCampaigns() {
            const instanceId = new URLSearchParams(window.location.search).get('instance_id');
            const url = instanceId ? `/api/campaigns?instance_id=${instanceId}` : '/api/campaigns';
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        campaigns = data.data;
                        filteredCampaigns = [...campaigns];
                        displayCampaigns();
                        updateStatistics();
                    }
                })
                .catch(error => console.error('Error loading campaigns:', error));
        }

        function populateInstanceFilter() {
            const select = document.getElementById('instanceFilter');
            select.innerHTML = '<option value="">All Instances</option>';
            
            instances.forEach(instance => {
                const option = document.createElement('option');
                option.value = instance.id;
                option.textContent = instance.instance_name;
                select.appendChild(option);
            });
        }

        function populateCampaignInstanceSelect() {
            const select = document.getElementById('campaignInstance');
            select.innerHTML = '<option value="">Select Instance</option>';
            
            instances.forEach(instance => {
                const option = document.createElement('option');
                option.value = instance.id;
                option.textContent = instance.instance_name;
                select.appendChild(option);
            });
        }

        function displayCampaigns() {
            const container = document.getElementById('campaignsList');
            
            if (filteredCampaigns.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                                <h5>No Campaigns Found</h5>
                                <p class="text-muted">Add your first campaign to get started.</p>
                                <button class="btn btn-primary" onclick="showAddCampaignModal()">
                                    <i class="fas fa-plus"></i> Add Campaign
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                return;
            }

            let html = '';
            filteredCampaigns.forEach(campaign => {
                const statusClass = `status-${campaign.status}`;
                const statusBadge = campaign.status === 'active' ? 'success' : 
                                   campaign.status === 'paused' ? 'warning' : 'danger';
                
                const performanceClass = getPerformanceClass(campaign.target_answer_rate);
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card campaign-card ${statusClass}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="card-title mb-1">${campaign.campaign_name}</h5>
                                        <p class="card-text text-muted small">${campaign.instance_name}</p>
                                    </div>
                                    <span class="badge bg-${statusBadge}">${campaign.status}</span>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted">Answer Rate</small><br>
                                            <span class="fw-bold">${campaign.target_answer_rate}%</span>
                                            <span class="performance-indicator ${performanceClass}"></span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Conversion Rate</small><br>
                                            <span class="fw-bold">${campaign.target_conversion_rate}%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-tag me-1"></i>${campaign.campaign_type}
                                    </small><br>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>Rotation: ${campaign.rotation_frequency_hours}h
                                    </small>
                                </div>
                                
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewCampaignDetails(${campaign.id})">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editCampaign(${campaign.id})">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="viewCallerIds(${campaign.id})">
                                        <i class="fas fa-phone"></i> Caller IDs
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
            const total = campaigns.length;
            const active = campaigns.filter(c => c.status === 'active').length;
            const paused = campaigns.filter(c => c.status === 'paused').length;
            const stopped = campaigns.filter(c => c.status === 'stopped').length;

            document.getElementById('totalCampaigns').textContent = total;
            document.getElementById('activeCampaigns').textContent = active;
            document.getElementById('pausedCampaigns').textContent = paused;
            document.getElementById('stoppedCampaigns').textContent = stopped;
        }

        function filterCampaigns() {
            const instanceFilter = document.getElementById('instanceFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const typeFilter = document.getElementById('typeFilter').value;
            const searchFilter = document.getElementById('searchFilter').value.toLowerCase();

            filteredCampaigns = campaigns.filter(campaign => {
                const instanceMatch = !instanceFilter || campaign.instance_id == instanceFilter;
                const statusMatch = !statusFilter || campaign.status === statusFilter;
                const typeMatch = !typeFilter || campaign.campaign_type === typeFilter;
                const searchMatch = !searchFilter || 
                    campaign.campaign_name.toLowerCase().includes(searchFilter) ||
                    campaign.instance_name.toLowerCase().includes(searchFilter);

                return instanceMatch && statusMatch && typeMatch && searchMatch;
            });

            displayCampaigns();
        }

        function showAddCampaignModal() {
            document.getElementById('addCampaignForm').reset();
            addCampaignModal.show();
        }

        function addCampaign() {
            const form = document.getElementById('addCampaignForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            fetch('/api/campaigns', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    addCampaignModal.hide();
                    loadCampaigns();
                    showAlert('Campaign added successfully!', 'success');
                } else {
                    showAlert('Failed to add campaign: ' + result.error, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to add campaign', 'danger');
            });
        }

        function viewCampaignDetails(campaignId) {
            campaignDetailsModal.show();
            
            fetch(`/api/campaigns/${campaignId}/details`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayCampaignDetails(data.data);
                    } else {
                        document.getElementById('campaignDetailsContent').innerHTML = `
                            <div class="text-center text-danger">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                <h5>Error Loading Details</h5>
                                <p>${data.error}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('campaignDetailsContent').innerHTML = `
                        <div class="text-center text-danger">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <h5>Error Loading Details</h5>
                            <p>Network error occurred.</p>
                        </div>
                    `;
                });
        }

        function displayCampaignDetails(campaign) {
            const content = document.getElementById('campaignDetailsContent');
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Campaign Information</h6>
                        <table class="table table-sm">
                            <tr><td>Name:</td><td>${campaign.campaign_name}</td></tr>
                            <tr><td>Instance:</td><td>${campaign.instance_name}</td></tr>
                            <tr><td>Type:</td><td>${campaign.campaign_type}</td></tr>
                            <tr><td>Status:</td><td><span class="badge bg-${campaign.status === 'active' ? 'success' : campaign.status === 'paused' ? 'warning' : 'danger'}">${campaign.status}</span></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Performance Targets</h6>
                        <table class="table table-sm">
                            <tr><td>Target Answer Rate:</td><td>${campaign.target_answer_rate || 0}%</td></tr>
                            <tr><td>Target Conversion Rate:</td><td>${campaign.target_conversion_rate || 0}%</td></tr>
                            <tr><td>Rotation Frequency:</td><td>${campaign.rotation_frequency_hours || 0} hours</td></tr>
                            <tr><td>Auto Rotation:</td><td>${campaign.auto_rotation_enabled ? 'Enabled' : 'Disabled'}</td></tr>
                        </table>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Recent Performance</h6>
                        <div id="campaignPerformanceChart" style="height: 300px;">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Load performance data
            loadCampaignPerformance(campaign.id);
        }

        function loadCampaignPerformance(campaignId) {
            fetch(`/api/campaigns/${campaignId}/performance`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayCampaignPerformance(data.data);
                    } else {
                        document.getElementById('campaignPerformanceChart').innerHTML = `
                            <div class="text-center text-muted">
                                <i class="fas fa-chart-line fa-2x mb-2"></i>
                                <p>No performance data available</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('campaignPerformanceChart').innerHTML = `
                        <div class="text-center text-muted">
                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                            <p>Failed to load performance data</p>
                        </div>
                    `;
                });
        }

        function displayCampaignPerformance(performance) {
            const chartContainer = document.getElementById('campaignPerformanceChart');
            
            // Create a simple performance summary
            const totalCalls = performance.total_caller_ids || 0;
            const activeCalls = performance.active_caller_ids || 0;
            const completedCalls = performance.completed_caller_ids || 0;
            const failedCalls = performance.failed_caller_ids || 0;
            
            const successRate = totalCalls > 0 ? ((completedCalls / totalCalls) * 100).toFixed(1) : 0;
            const failureRate = totalCalls > 0 ? ((failedCalls / totalCalls) * 100).toFixed(1) : 0;
            
            chartContainer.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h4 class="text-primary">${totalCalls}</h4>
                                <small class="text-muted">Total Caller IDs</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h4 class="text-success">${activeCalls}</h4>
                                <small class="text-muted">Active Caller IDs</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h4 class="text-info">${successRate}%</h4>
                                <small class="text-muted">Success Rate</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h4 class="text-warning">${failureRate}%</h4>
                                <small class="text-muted">Failure Rate</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6>Call Status Breakdown</h6>
                                <div class="row text-center">
                                    <div class="col-3">
                                        <small class="text-muted">Completed</small><br>
                                        <span class="fw-bold text-success">${completedCalls}</span>
                                    </div>
                                    <div class="col-3">
                                        <small class="text-muted">Failed</small><br>
                                        <span class="fw-bold text-danger">${failedCalls}</span>
                                    </div>
                                    <div class="col-3">
                                        <small class="text-muted">No Answer</small><br>
                                        <span class="fw-bold text-warning">${performance.no_answer_caller_ids || 0}</span>
                                    </div>
                                    <div class="col-3">
                                        <small class="text-muted">Busy</small><br>
                                        <span class="fw-bold text-secondary">${performance.busy_caller_ids || 0}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function editCampaign(campaignId) {
            // Implement edit functionality
            showAlert('Edit functionality coming soon', 'info');
        }

        function viewCallerIds(campaignId) {
            window.location.href = `/caller-ids?campaign_id=${campaignId}`;
        }

        function refreshCampaigns() {
            loadCampaigns();
        }

        function syncFromVicidial() {
            const syncButton = document.getElementById('syncButton');
            const originalText = syncButton.innerHTML;
            
            // Disable button and show loading state
            syncButton.disabled = true;
            syncButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
            
            // Get selected instance or sync all
            const selectedInstance = document.getElementById('instanceFilter').value;
            const syncUrl = selectedInstance ? `/api/sync-instance` : `/api/sync-all-instances`;
            const syncData = selectedInstance ? { instance_id: parseInt(selectedInstance) } : {};
            
            console.log('Sync URL:', syncUrl);
            console.log('Sync Data:', syncData);
            
            fetch(syncUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(syncData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                console.log('Sync result:', result);
                if (result.success) {
                    showAlert('Data synchronized successfully from Vicidial!', 'success');
                    loadCampaigns(); // Refresh the campaigns list
                } else {
                    showAlert('Sync failed: ' + (result.error || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Sync error:', error);
                showAlert('Sync failed: ' + error.message, 'danger');
            })
            .finally(() => {
                // Re-enable button and restore original text
                syncButton.disabled = false;
                syncButton.innerHTML = originalText;
            });
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