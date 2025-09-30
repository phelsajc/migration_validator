<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration Validation Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-complete { color: #28a745; }
        .status-incomplete { color: #dc3545; }
        .card-header { background-color: #f8f9fa; }
        .validation-result { border-left: 4px solid #007bff; }
        .loading { display: none; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1 class="mt-4 mb-4">
                    <i class="fas fa-database"></i> Migration Validation Dashboard
                </h1>
            </div>
        </div>

        <!-- Date Range Selection -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-alt"></i> Date Range Selection</h5>
                    </div>
                    <div class="card-body">
                        <form id="validationForm">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="startDate" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="startDate">
                                </div>
                                <div class="col-md-4">
                                    <label for="endDate" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="endDate">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="button" class="btn btn-primary me-2" onclick="validatePatients()">
                                        <i class="fas fa-search"></i> Validate Patients
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="validateAllTables()">
                                        <i class="fas fa-check-double"></i> Validate All
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Validation Results -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-chart-bar"></i> Validation Results</h5>
                        <div class="loading" id="loadingSpinner">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="validationResults">
                            <div class="text-center text-muted">
                                <i class="fas fa-info-circle fa-2x mb-3"></i>
                                <p>Select a date range and click "Validate Patients" to start validation.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Validation History -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-history"></i> Validation History</h5>
                        <button class="btn btn-outline-primary btn-sm" onclick="loadHistory()">
                            <i class="fas fa-refresh"></i> Refresh
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="validationHistory">
                            <div class="text-center text-muted">
                                <i class="fas fa-clock fa-2x mb-3"></i>
                                <p>Click "Refresh" to load validation history.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showLoading() {
            document.getElementById('loadingSpinner').style.display = 'block';
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').style.display = 'none';
        }

        function validatePatients() {
            showLoading();
            
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            // Properly format dates for MongoDB (avoid timezone issues)
            const startISODate = startDate ? startDate + 'T00:00:00.000Z' : new Date().toISOString().split('T')[0] + 'T00:00:00.000Z';
            const endISODate = endDate ? endDate + 'T23:59:59.999Z' : new Date().toISOString().split('T')[0] + 'T23:59:59.999Z';
            
            fetch('/migration-validation/validate/patients', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    start_date: startISODate,
                    end_date: endISODate
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                displayValidationResult(data);
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                displayError('Validation failed: ' + error.message);
            });
        }

        function validateAllTables() {
            showLoading();
            
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            // Properly format dates for MongoDB (avoid timezone issues)
            const startISODate = startDate ? startDate + 'T00:00:00.000Z' : new Date().toISOString().split('T')[0] + 'T00:00:00.000Z';
            const endISODate = endDate ? endDate + 'T23:59:59.999Z' : new Date().toISOString().split('T')[0] + 'T23:59:59.999Z';
            
            fetch('/migration-validation/validate/all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    start_date: startISODate,
                    end_date: endISODate
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                displayValidationResults(data);
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                displayError('Validation failed: ' + error.message);
            });
        }

        function loadHistory() {
            fetch('/migration-validation/history')
            .then(response => response.json())
            .then(data => {
                displayHistory(data);
            })
            .catch(error => {
                console.error('Error:', error);
                displayError('Failed to load history: ' + error.message);
            });
        }

        function displayValidationResult(data) {
            const resultsDiv = document.getElementById('validationResults');
            
            if (data && data.success && data.data) {
                const result = data.data;
                
                // Safe property access with fallbacks
                const table = result.table || 'Unknown';
                const mongodbCount = result.mongodb_count || 0;
                const mssqlCount = result.mssql_count || 0;
                const difference = result.difference || 0;
                const isComplete = result.is_complete || false;
                const status = result.status || 'UNKNOWN';
                const validatedAt = result.validated_at || new Date().toISOString();
                
                resultsDiv.innerHTML = `
                    <div class="validation-result p-3 mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Table:</strong> ${table}
                            </div>
                            <div class="col-md-3">
                                <strong>MongoDB Count:</strong> ${Number(mongodbCount).toLocaleString()}
                            </div>
                            <div class="col-md-3">
                                <strong>MSSQL Count:</strong> ${Number(mssqlCount).toLocaleString()}
                            </div>
                            <div class="col-md-3">
                                <strong>Difference:</strong> 
                                <span class="${difference === 0 ? 'status-complete' : 'status-incomplete'}">
                                    ${Number(difference).toLocaleString()}
                                </span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <strong>Status:</strong> 
                                <span class="badge ${isComplete ? 'bg-success' : 'bg-danger'}">
                                    ${status}
                                </span>
                            </div>
                            <div class="col-md-6">
                                <strong>Validated At:</strong> ${new Date(validatedAt).toLocaleString()}
                            </div>
                        </div>
                    </div>
                `;
            } else {
                console.error('Invalid response data:', data);
                displayError(data?.error || 'Validation failed - Invalid response format');
            }
        }

        function displayValidationResults(data) {
            const resultsDiv = document.getElementById('validationResults');
            
            if (data && data.success && data.data && data.data.validations) {
                let html = '<div class="row">';
                
                data.data.validations.forEach(result => {
                    // Safe property access with fallbacks
                    const table = result.table || 'Unknown';
                    const mongodbCount = result.mongodb_count || 0;
                    const mssqlCount = result.mssql_count || 0;
                    const difference = result.difference || 0;
                    const isComplete = result.is_complete || false;
                    const status = result.status || 'UNKNOWN';
                    
                    html += `
                        <div class="col-md-6 mb-3">
                            <div class="validation-result p-3">
                                <h6>${table}</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <small>MongoDB: ${Number(mongodbCount).toLocaleString()}</small><br>
                                        <small>MSSQL: ${Number(mssqlCount).toLocaleString()}</small>
                                    </div>
                                    <div class="col-6 text-end">
                                        <small>Difference: 
                                            <span class="${difference === 0 ? 'status-complete' : 'status-incomplete'}">
                                                ${Number(difference).toLocaleString()}
                                            </span>
                                        </small><br>
                                        <span class="badge ${isComplete ? 'bg-success' : 'bg-danger'}">
                                            ${status}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                
                if (data.data.summary) {
                    html += `
                        <div class="alert alert-info">
                            <strong>Summary:</strong> 
                            ${data.data.summary.complete_tables} complete, 
                            ${data.data.summary.incomplete_tables} incomplete out of 
                            ${data.data.summary.total_tables} total tables
                        </div>
                    `;
                }
                
                resultsDiv.innerHTML = html;
            } else {
                console.error('Invalid response data:', data);
                displayError(data?.error || 'Validation failed - Invalid response format');
            }
        }

        function displayHistory(data) {
            const historyDiv = document.getElementById('validationHistory');
            
            if (data.success && data.data.validations.length > 0) {
                let html = '<div class="table-responsive"><table class="table table-striped">';
                html += '<thead><tr><th>Table</th><th>MongoDB</th><th>MSSQL</th><th>Difference</th><th>Status</th><th>Validated At</th></tr></thead><tbody>';
                
                data.data.validations.forEach(validation => {
                    html += `
                        <tr>
                            <td>${validation.table}</td>
                            <td>${validation.mongodb_count.toLocaleString()}</td>
                            <td>${validation.mssql_count.toLocaleString()}</td>
                            <td class="${validation.difference === 0 ? 'status-complete' : 'status-incomplete'}">
                                ${validation.difference.toLocaleString()}
                            </td>
                            <td>
                                <span class="badge ${validation.is_complete ? 'bg-success' : 'bg-danger'}">
                                    ${validation.status}
                                </span>
                            </td>
                            <td>${new Date(validation.validated_at).toLocaleString()}</td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table></div>';
                historyDiv.innerHTML = html;
            } else {
                historyDiv.innerHTML = '<div class="text-center text-muted"><p>No validation history found.</p></div>';
            }
        }

        function displayError(message) {
            const resultsDiv = document.getElementById('validationResults');
            resultsDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> ${message}
                </div>
            `;
        }

        // Load history on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadHistory();
        });
    </script>
</body>
</html>
