<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Vicidial Support System</title>
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
        .report-card {
            transition: transform 0.2s;
        }
        .report-card:hover {
            transform: translateY(-2px);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
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
                            <a class="nav-link text-white" href="/alerts">
                                <i class="fas fa-bell me-2"></i>
                                Alerts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="/reports">
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
                    <h1 class="h2">Reports & Analytics</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshReports()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="generateReport()">
                                <i class="fas fa-download"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Report Filters -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label for="reportType" class="form-label">Report Type</label>
                        <select class="form-select" id="reportType" onchange="loadReport()">
                            <option value="">Select Report Type</option>
                            <option value="performance">Performance Report</option>
                            <option value="rotation">Rotation Report</option>
                            <option value="server">Server Health Report</option>
                            <option value="campaign">Campaign Analytics</option>
                            <option value="caller_id">Caller ID Performance</option>
                            <option value="list">List Performance</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="dateRange" class="form-label">Date Range</label>
                        <select class="form-select" id="dateRange" onchange="loadReport()">
                            <option value="7">Last 7 Days</option>
                            <option value="30" selected>Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                            <option value="365">Last Year</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="instanceFilter" class="form-label">Instance</label>
                        <select class="form-select" id="instanceFilter" onchange="loadReport()">
                            <option value="">All Instances</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="campaignFilter" class="form-label">Campaign</label>
                        <select class="form-select" id="campaignFilter" onchange="loadReport()">
                            <option value="">All Campaigns</option>
                        </select>
                    </div>
                </div>

                <!-- Report Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Calls</h6>
                                        <h3 id="totalCalls">0</h3>
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
                                        <h6 class="card-title">Answer Rate</h6>
                                        <h3 id="answerRate">0%</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-percentage fa-2x"></i>
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
                                        <h6 class="card-title">Conversion Rate</h6>
                                        <h3 id="conversionRate">0%</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-chart-line fa-2x"></i>
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
                                        <h6 class="card-title">Rotations</h6>
                                        <h3 id="totalRotations">0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-sync-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Performance Trends</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="performanceChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Call Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="callDistributionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Content -->
                <div class="row" id="reportContent">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <h5>Select a Report Type</h5>
                                <p class="text-muted">Choose a report type from the dropdown above to view detailed analytics.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Generate Report Modal -->
    <div class="modal fade" id="generateReportModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="generateReportForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reportName" class="form-label">Report Name</label>
                                    <input type="text" class="form-control" id="reportName" name="report_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reportFormat" class="form-label">Format</label>
                                    <select class="form-select" id="reportFormat" name="format">
                                        <option value="pdf">PDF</option>
                                        <option value="excel">Excel</option>
                                        <option value="csv">CSV</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="startDate" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="startDate" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="endDate" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="endDate" name="end_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reportDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="reportDescription" name="description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitReportGeneration()">Generate Report</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let instances = [];
        let campaigns = [];
        let performanceChart;
        let callDistributionChart;
        let generateReportModal;

        document.addEventListener('DOMContentLoaded', function() {
            generateReportModal = new bootstrap.Modal(document.getElementById('generateReportModal'));
            loadInstances();
            loadCampaigns();
            initializeCharts();
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

        function initializeCharts() {
            // Performance Chart
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            performanceChart = new Chart(performanceCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Answer Rate',
                        data: [],
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Conversion Rate',
                        data: [],
                        borderColor: '#17a2b8',
                        backgroundColor: 'rgba(23, 162, 184, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });

            // Call Distribution Chart
            const distributionCtx = document.getElementById('callDistributionChart').getContext('2d');
            callDistributionChart = new Chart(distributionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Answered', 'No Answer', 'Busy', 'Failed'],
                    datasets: [{
                        data: [0, 0, 0, 0],
                        backgroundColor: [
                            '#28a745',
                            '#ffc107',
                            '#dc3545',
                            '#6c757d'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }

        function loadReport() {
            const reportType = document.getElementById('reportType').value;
            const dateRange = document.getElementById('dateRange').value;
            const instanceId = document.getElementById('instanceFilter').value;
            const campaignId = document.getElementById('campaignFilter').value;

            if (!reportType) {
                showDefaultContent();
                return;
            }

            fetch(`/api/reports/${reportType}?days=${dateRange}&instance_id=${instanceId}&campaign_id=${campaignId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayReport(data.data);
                        updateCharts(data.data);
                        updateStatistics(data.data);
                    }
                })
                .catch(error => {
                    console.error('Error loading report:', error);
                    showErrorContent();
                });
        }

        function displayReport(data) {
            const container = document.getElementById('reportContent');
            
            let html = `
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">${data.title}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Summary</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Period:</strong> ${data.period}</li>
                                        <li><strong>Total Calls:</strong> ${data.total_calls.toLocaleString()}</li>
                                        <li><strong>Average Answer Rate:</strong> ${data.avg_answer_rate}%</li>
                                        <li><strong>Average Conversion Rate:</strong> ${data.avg_conversion_rate}%</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Key Metrics</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Successful Calls:</strong> ${data.successful_calls.toLocaleString()}</li>
                                        <li><strong>Failed Calls:</strong> ${data.failed_calls.toLocaleString()}</li>
                                        <li><strong>Total Duration:</strong> ${data.total_duration}</li>
                                        <li><strong>Rotations:</strong> ${data.rotations}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            if (data.details && data.details.length > 0) {
                html += `
                    <div class="col-12 mt-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Detailed Breakdown</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Calls</th>
                                                <th>Answered</th>
                                                <th>Answer Rate</th>
                                                <th>Conversions</th>
                                                <th>Conversion Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                `;

                data.details.forEach(detail => {
                    html += `
                        <tr>
                            <td>${detail.date}</td>
                            <td>${detail.calls.toLocaleString()}</td>
                            <td>${detail.answered.toLocaleString()}</td>
                            <td>${detail.answer_rate}%</td>
                            <td>${detail.conversions.toLocaleString()}</td>
                            <td>${detail.conversion_rate}%</td>
                        </tr>
                    `;
                });

                html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            container.innerHTML = html;
        }

        function updateCharts(data) {
            // Update Performance Chart
            if (data.chart_data) {
                performanceChart.data.labels = data.chart_data.labels;
                performanceChart.data.datasets[0].data = data.chart_data.answer_rates;
                performanceChart.data.datasets[1].data = data.chart_data.conversion_rates;
                performanceChart.update();
            }

            // Update Call Distribution Chart
            if (data.distribution_data) {
                callDistributionChart.data.datasets[0].data = [
                    data.distribution_data.answered,
                    data.distribution_data.no_answer,
                    data.distribution_data.busy,
                    data.distribution_data.failed
                ];
                callDistributionChart.update();
            }
        }

        function updateStatistics(data) {
            document.getElementById('totalCalls').textContent = data.total_calls.toLocaleString();
            document.getElementById('answerRate').textContent = data.avg_answer_rate + '%';
            document.getElementById('conversionRate').textContent = data.avg_conversion_rate + '%';
            document.getElementById('totalRotations').textContent = data.rotations.toLocaleString();
        }

        function showDefaultContent() {
            const container = document.getElementById('reportContent');
            container.innerHTML = `
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                            <h5>Select a Report Type</h5>
                            <p class="text-muted">Choose a report type from the dropdown above to view detailed analytics.</p>
                        </div>
                    </div>
                </div>
            `;
        }

        function showErrorContent() {
            const container = document.getElementById('reportContent');
            container.innerHTML = `
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                            <h5>Error Loading Report</h5>
                            <p class="text-muted">Failed to load the selected report. Please try again.</p>
                        </div>
                    </div>
                </div>
            `;
        }

        function generateReport() {
            document.getElementById('generateReportForm').reset();
            generateReportModal.show();
        }

        function submitReportGeneration() {
            const form = document.getElementById('generateReportForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            fetch('/api/reports/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    generateReportModal.hide();
                    showAlert('Report generated successfully!', 'success');
                } else {
                    showAlert('Failed to generate report: ' + result.error, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to generate report', 'danger');
            });
        }

        function refreshReports() {
            loadReport();
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