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
        
        .table-info {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
        }
        
        .validation-result {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            transition: box-shadow 0.2s;
        }
        
        .validation-result:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .pipeline-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .result-breakdown {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
        }

        .result-breakdown h6 {
            font-size: 0.95rem;
            margin-bottom: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center mt-4 mb-4">
                <h1 class="mb-0">
                    <i class="fas fa-database"></i> Migration Validation Dashboard
                </h1>
                <a href="{{ route('migration-validation.report') }}" class="btn btn-outline-primary">
                    <i class="fas fa-file-alt"></i> Migration Report
                </a>
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
                                <div class="col-md-3">
                                    <label for="tableSelect" class="form-label">Table to Validate</label>
                                    <select class="form-select" id="tableSelect" onchange="loadTableInfo()">
                                        <option value="">Select a table...</option>
                                        <option value="patients">Patients</option>
                                        <option value="careproviders">Care Providers</option>
                                        <option value="patientorderitems">Patient Order Items</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="startDate" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="startDate">
                                </div>
                                <div class="col-md-3">
                                    <label for="endDate" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="endDate">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="button" class="btn btn-primary me-2" onclick="validateSelectedTable()" id="validateBtn" disabled>
                                        <i class="fas fa-search"></i> Validate
                                    </button>
                                    <!-- <button type="button" class="btn btn-success" onclick="validateAllTables()">
                                        <i class="fas fa-check-double"></i> Validate All
                                    </button> -->
                                </div>
                            </div>
                            <div class="row mt-2" id="tableInfo" style="display: none;">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <small id="tableInfoText"></small>
                                    </div>
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
                                <p>Select a table and date range, then click "Validate" to start validation.</p>
                                <p><small>Use "Validate All" to check all configured tables at once.</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Missing Records -->
        <div class="row mb-4" id="missingRecordsSection" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 id="analysisSectionTitle"><i class="fas fa-exclamation-triangle"></i> Missing Records Analysis</h5>
                        <button class="btn btn-outline-secondary btn-sm" onclick="toggleMissingRecords()">
                            <i class="fas fa-eye-slash"></i> Hide Details
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="missingRecordsContent">
                            <div class="text-center text-muted">
                                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                                <p>Loading missing records...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Validation History -->
        <!-- <div class="row">
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
        </div> -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showLoading() {
            document.getElementById('loadingSpinner').style.display = 'block';
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').style.display = 'none';
        }

        function loadAvailableTables() {
            fetch('/api/migration-validation/tables')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const tableSelect = document.getElementById('tableSelect');
                    tableSelect.innerHTML = '<option value="">Select a table...</option>';
                    
                    data.data.tables.forEach(table => {
                        const option = document.createElement('option');
                        option.value = table.name;
                        option.textContent = table.name.charAt(0).toUpperCase() + table.name.slice(1).replace(/([A-Z])/g, ' $1');
                        tableSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading tables:', error);
            });
        }

        function loadTableInfo() {
            const tableSelect = document.getElementById('tableSelect');
            const validateBtn = document.getElementById('validateBtn');
            const tableInfo = document.getElementById('tableInfo');
            const tableInfoText = document.getElementById('tableInfoText');
            
            if (tableSelect.value) {
                validateBtn.disabled = false;
                
                // Show table information
                const tableInfoMap = {
                    'patients': 'MongoDB Collection: patients | MSSQL Table: patients | Pipeline: Simple Count | Identifier: MRN',
                    'careproviders': 'MongoDB Collection: careproviders | MSSQL Table: careproviders | Pipeline: Simple Count | Identifier: Provider ID',
                    'patientorderitems': 'MongoDB Collection: patientorderitems | MSSQL Table: patientorderitems | Pipeline: Complex (with $lookup & $unwind) | Identifier: Order Item ID'
                };
                
                tableInfoText.textContent = tableInfoMap[tableSelect.value] || 'Table information not available';
                tableInfo.style.display = 'block';
            } else {
                validateBtn.disabled = true;
                tableInfo.style.display = 'none';
            }
        }

        function validateSelectedTable() {
            const tableSelect = document.getElementById('tableSelect');
            const selectedTable = tableSelect.value;
            
            if (!selectedTable) {
                displayError('Please select a table to validate');
                return;
            }
            
            showLoading();
            
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            // Properly format dates for MongoDB (avoid timezone issues)
            const startISODate = startDate ? startDate + 'T00:00:00.000Z' : new Date().toISOString().split('T')[0] + 'T00:00:00.000Z';
            const endISODate = endDate ? endDate + 'T23:59:59.999Z' : new Date().toISOString().split('T')[0] + 'T23:59:59.999Z';
            
            fetch('/api/migration-validation/validate/table', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    table: selectedTable,
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

        function validatePatients() {
            // Legacy function - redirect to new generic function
            document.getElementById('tableSelect').value = 'patients';
            loadTableInfo();
            validateSelectedTable();
        }

        function validateAllTables() {
            showLoading();
            
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            // Properly format dates for MongoDB (avoid timezone issues)
            const startISODate = startDate ? startDate + 'T00:00:00.000Z' : new Date().toISOString().split('T')[0] + 'T00:00:00.000Z';
            const endISODate = endDate ? endDate + 'T23:59:59.999Z' : new Date().toISOString().split('T')[0] + 'T23:59:59.999Z';
            
            fetch('/api/migration-validation/validate/all', {
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
            const historyDiv = document.getElementById('validationHistory');
            if (!historyDiv) {
                return;
            }

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

        function formatIdentifierLabel(field) {
            if (!field) {
                return 'identifier';
            }
            const value = String(field);
            if (value.toLowerCase() === 'mrn') {
                return 'MRN';
            }
            return value.replace(/_/g, ' ');
        }

        function formatSignedCount(value) {
            const number = Number(value) || 0;
            const formatted = Math.abs(number).toLocaleString();
            if (number > 0) {
                return '+' + formatted;
            }
            if (number < 0) {
                return '-' + formatted;
            }
            return formatted;
        }

        function buildValidationSummary(isComplete, difference, identifierLabel, mongodbCount, hasAnalysis) {
            if (!hasAnalysis) {
                return 'Match details are unavailable, so this status cannot be checked record by record.';
            }
            if (isComplete) {
                return `Complete because all ${Number(mongodbCount).toLocaleString()} MongoDB records in this date range were found in MSSQL by ${identifierLabel}. Unmatched MongoDB records: ${Number(difference).toLocaleString()}.`;
            }
            return `Incomplete because ${Number(difference).toLocaleString()} MongoDB record(s) have no matching ${identifierLabel} in MSSQL.`;
        }

        function buildCountGapNote(rowGap, extraCount, identifierLabel, mssqlCount, mssqlMatchRows) {
            const notes = [];
            if (rowGap === 0) {
                notes.push('MongoDB and MSSQL row counts are equal.');
            } else if (rowGap > 0) {
                notes.push(`MSSQL has ${rowGap.toLocaleString()} more rows than MongoDB.`);
            } else {
                notes.push(`MongoDB has ${Math.abs(rowGap).toLocaleString()} more records than MSSQL.`);
            }

            notes.push(`${Number(extraCount).toLocaleString()} MSSQL ${identifierLabel} value(s) in this date range have no matching MongoDB record.`);

            if (mssqlMatchRows !== null && Number(mssqlMatchRows) !== Number(mssqlCount)) {
                notes.push(`Matching compares distinct MSSQL rows (${Number(mssqlMatchRows).toLocaleString()}), while the MSSQL count is every row (${Number(mssqlCount).toLocaleString()}).`);
            }

            const repeatedRows = rowGap - extraCount;
            if (rowGap > extraCount && repeatedRows > 0) {
                notes.push(`The other ${repeatedRows.toLocaleString()} MSSQL rows repeat an ${identifierLabel} that was already matched, so they do not change the completion result.`);
            }

            return notes.join(' ');
        }

        function displayValidationResult(data) {
            const resultsDiv = document.getElementById('validationResults');
            
            if (data && data.success && data.data) {
                const result = data.data;
                
                const table = result.table || 'Unknown';
                const mongodbCount = Number(result.mongodb_count ?? 0);
                const mssqlCount = Number(result.mssql_count ?? 0);
                const difference = Number(result.difference ?? 0);
                const isComplete = result.is_complete || false;
                const status = result.status || 'UNKNOWN';
                const validatedAt = result.validated_at || new Date().toISOString();
                const missingRecordsAnalysis = result.missing_records_analysis || null;
                const identifierLabel = formatIdentifierLabel(result.identifier_field);
                const foundMatches = Number(missingRecordsAnalysis?.found_matches ?? 0);
                const missingCount = Number(missingRecordsAnalysis?.missing_from_mssql ?? (missingRecordsAnalysis?.missing_records?.length || 0));
                const extraCount = Number(missingRecordsAnalysis?.extra_in_mssql ?? (missingRecordsAnalysis?.extra_records?.length || 0));
                const mongoChecked = Number(missingRecordsAnalysis?.mongo_total ?? mongodbCount);
                const mssqlMatchRows = missingRecordsAnalysis && missingRecordsAnalysis.mssql_total !== undefined
                    ? Number(missingRecordsAnalysis.mssql_total)
                    : null;
                const rowGap = mssqlCount - mongodbCount;
                const summary = buildValidationSummary(isComplete, difference, identifierLabel, mongodbCount, !!missingRecordsAnalysis);
                const gapNote = missingRecordsAnalysis
                    ? buildCountGapNote(rowGap, extraCount, identifierLabel, mssqlCount, mssqlMatchRows)
                    : '';
                
                resultsDiv.innerHTML = `
                    <div class="validation-result p-3 mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Table:</strong> ${table}
                            </div>
                            <div class="col-md-3">
                                <strong>MongoDB Count:</strong> ${mongodbCount.toLocaleString()}
                            </div>
                            <div class="col-md-3">
                                <strong>MSSQL Count:</strong> ${mssqlCount.toLocaleString()}
                            </div>
                            <div class="col-md-3">
                                <strong>Unmatched MongoDB records:</strong>
                                <span class="${difference === 0 ? 'status-complete' : 'status-incomplete'}">
                                    ${difference.toLocaleString()}
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
                        <div class="alert ${isComplete ? 'alert-success' : 'alert-warning'} mt-3 mb-3">
                            ${summary}
                            ${gapNote ? `<div class="mt-1">${gapNote}</div>` : ''}
                        </div>
                        ${missingRecordsAnalysis ? `
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="result-breakdown p-3 h-100">
                                        <h6>Why this status</h6>
                                        <div><strong>Identifier:</strong> ${identifierLabel}</div>
                                        <div><strong>MongoDB records checked:</strong> ${mongoChecked.toLocaleString()}</div>
                                        <div><strong>Matched in MSSQL:</strong> ${foundMatches.toLocaleString()}</div>
                                        <div><strong>Missing from MSSQL:</strong> <span class="${missingCount === 0 ? 'status-complete' : 'status-incomplete'}">${missingCount.toLocaleString()}</span></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="result-breakdown p-3 h-100">
                                        <h6>Why the counts differ</h6>
                                        <div><strong>MongoDB records:</strong> ${mongodbCount.toLocaleString()}</div>
                                        <div><strong>MSSQL rows:</strong> ${mssqlCount.toLocaleString()}</div>
                                        <div><strong>Row count gap (MSSQL − MongoDB):</strong> <span class="${rowGap === 0 ? 'status-complete' : 'status-incomplete'}">${formatSignedCount(rowGap)}</span></div>
                                        <div><strong>Extra MSSQL ${identifierLabel} values:</strong> <span class="${extraCount === 0 ? 'status-complete' : 'status-incomplete'}">${extraCount.toLocaleString()}</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button class="btn btn-warning btn-sm me-2" onclick="showMissingRecords('${table}')">
                                        <i class="fas fa-exclamation-triangle"></i> View ${missingCount.toLocaleString()} missing from MSSQL
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="showExtraRecords('${table}')">
                                        <i class="fas fa-list"></i> View ${extraCount.toLocaleString()} extra in MSSQL
                                    </button>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                `;
                
                if (missingRecordsAnalysis) {
                    window.currentMissingRecords = missingRecordsAnalysis;
                }
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
                    const errorCount = data.data.summary.error_tables || 0;
                    html += `
                        <div class="alert alert-info">
                            <strong>Summary:</strong> 
                            ${data.data.summary.complete_tables} complete, 
                            ${data.data.summary.incomplete_tables} incomplete,
                            ${errorCount} errors out of 
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

        function setAnalysisTitle(title) {
            const titleEl = document.getElementById('analysisSectionTitle');
            if (titleEl) {
                titleEl.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${title}`;
            }
        }

        function showMissingRecords(tableName) {
            const missingRecordsSection = document.getElementById('missingRecordsSection');
            const missingRecordsContent = document.getElementById('missingRecordsContent');
            setAnalysisTitle('Missing from MSSQL');
            
            if (window.currentMissingRecords && window.currentMissingRecords.missing_records) {
                const missingRecords = window.currentMissingRecords.missing_records;
                const analysis = window.currentMissingRecords;
                
                let html = `
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>MongoDB Total:</strong> ${analysis.mongo_total || 0}
                        </div>
                        <div class="col-md-3">
                            <strong>MSSQL Total:</strong> ${analysis.mssql_total || 0}
                        </div>
                        <div class="col-md-3">
                            <strong>Found Matches:</strong> ${analysis.found_matches || 0}
                        </div>
                        <div class="col-md-3">
                            <strong>Missing from MSSQL:</strong> 
                            <span class="text-danger">${missingRecords.length}</span>
                        </div>
                    </div>
                    <hr>
                    <h6>MongoDB records not found in MSSQL:</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Identifier</th>
                                    <th>Created Date</th>
                                    <th>Modified Date</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                let a = 1;
                missingRecords.forEach(record => {
                    html += `
                        <tr>
                            <td>${a}</td>
                            <td><code>${record.universal_id || 'N/A'}</code></td>
                            <td>${record.mongo_createdat || 'N/A'}</td>
                            <td>${record.modifiedat || 'N/A'}</td>
                        </tr>
                    `;
                    a++;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                missingRecordsContent.innerHTML = html;
                missingRecordsSection.style.display = 'block';
            } else {
                missingRecordsContent.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No missing records data available.
                    </div>
                `;
                missingRecordsSection.style.display = 'block';
            }
        }

        function showExtraRecords(tableName) {
            const missingRecordsSection = document.getElementById('missingRecordsSection');
            const missingRecordsContent = document.getElementById('missingRecordsContent');
            setAnalysisTitle('Extra in MSSQL');
            const analysis = window.currentMissingRecords || {};
            const extraRecords = analysis.extra_records || [];

            if (extraRecords.length > 0) {
                let html = `
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>MongoDB Total:</strong> ${analysis.mongo_total || 0}
                        </div>
                        <div class="col-md-3">
                            <strong>MSSQL Total:</strong> ${analysis.mssql_total || 0}
                        </div>
                        <div class="col-md-3">
                            <strong>Found Matches:</strong> ${analysis.found_matches || 0}
                        </div>
                        <div class="col-md-3">
                            <strong>Extra in MSSQL:</strong>
                            <span class="text-danger">${extraRecords.length}</span>
                        </div>
                    </div>
                    <hr>
                    <h6>MSSQL records not found in MongoDB:</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Identifier</th>
                                    <th>MSSQL Date</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                extraRecords.forEach((record, index) => {
                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><code>${record.universal_id || 'N/A'}</code></td>
                            <td>${record.mssql_date || 'N/A'}</td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary btn-sm" id="checkExtraBtn" onclick="checkExtraInMongo('${tableName}')">
                            <i class="fas fa-search"></i> Check all ${extraRecords.length.toLocaleString()} in MongoDB
                        </button>
                        <small class="text-muted ms-2">One lookup for every identifier. This does not run validation again.</small>
                    </div>
                    <div id="extraMongoCheck" class="mt-3"></div>
                `;

                missingRecordsContent.innerHTML = html;
                missingRecordsSection.style.display = 'block';
            } else {
                missingRecordsContent.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No extra MSSQL identifiers in this date range. Every MSSQL identifier was found in MongoDB.
                    </div>
                `;
                missingRecordsSection.style.display = 'block';
            }
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function checkExtraInMongo(tableName) {
            const analysis = window.currentMissingRecords || {};
            const extraRecords = analysis.extra_records || [];
            const identifiers = extraRecords.map(record => record.universal_id).filter(Boolean);
            const resultBox = document.getElementById('extraMongoCheck');
            const button = document.getElementById('checkExtraBtn');
            const mssqlDateById = {};
            extraRecords.forEach(record => {
                mssqlDateById[record.universal_id] = record.mssql_date || 'N/A';
            });

            if (!resultBox || identifiers.length === 0) {
                return;
            }

            if (button) {
                button.disabled = true;
            }
            resultBox.innerHTML = `
                <div class="alert alert-info mb-0">
                    <i class="fas fa-spinner fa-spin"></i> Checking ${identifiers.length.toLocaleString()} identifiers in MongoDB...
                </div>
            `;

            fetch('/api/migration-validation/check-extra', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    table: tableName,
                    identifiers: identifiers,
                    start_date: document.getElementById('startDate').value,
                    end_date: document.getElementById('endDate').value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (button) {
                    button.disabled = false;
                }
                if (!data.success) {
                    resultBox.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(data.error || 'Check failed')}</div>`;
                    return;
                }
                renderExtraMongoCheck(data.data, mssqlDateById);
            })
            .catch(error => {
                if (button) {
                    button.disabled = false;
                }
                resultBox.innerHTML = `<div class="alert alert-danger mb-0">Check failed: ${escapeHtml(error.message)}</div>`;
            });
        }

        function renderExtraMongoCheck(result, mssqlDateById) {
            const resultBox = document.getElementById('extraMongoCheck');
            const records = result.records || [];
            let rows = '';

            records.forEach((record, index) => {
                const matches = record.matches || [];
                const mongoDate = matches.length
                    ? matches.map(match => escapeHtml(match.createdat_manila || '—')).join('<br>')
                    : '—';
                const modifiedDate = matches.length
                    ? matches.map(match => escapeHtml(match.modifiedat_manila || '—')).join('<br>')
                    : '—';
                const foundLabel = record.found ? 'Yes' : 'No';
                const foundClass = record.found ? 'status-complete' : 'status-incomplete';

                rows += `
                    <tr>
                        <td>${index + 1}</td>
                        <td><code>${escapeHtml(record.identifier)}</code></td>
                        <td>${escapeHtml(mssqlDateById[record.identifier] || 'N/A')}</td>
                        <td class="${foundClass}">${foundLabel}</td>
                        <td>${mongoDate}</td>
                        <td>${modifiedDate}</td>
                        <td>${escapeHtml(record.reason || '')}</td>
                    </tr>
                `;
            });

            resultBox.innerHTML = `
                <div class="alert alert-info">
                    Checked ${Number(result.checked || 0).toLocaleString()} identifiers in one query.
                    ${Number(result.found_in_mongodb || 0).toLocaleString()} exist in MongoDB
                    (${Number(result.outside_selected_date || 0).toLocaleString()} have ${escapeHtml(result.date_field || 'createdat')} outside the selected date).
                    ${Number(result.missing_from_mongodb || 0).toLocaleString()} are not in MongoDB.
                    MongoDB dates below are Manila time.
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Identifier</th>
                                <th>MSSQL createddate</th>
                                <th>In MongoDB</th>
                                <th>MongoDB createdat</th>
                                <th>MongoDB modifiedat</th>
                                <th>Why it is extra</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            `;
        }

        function toggleMissingRecords() {
            const missingRecordsSection = document.getElementById('missingRecordsSection');
            const toggleBtn = document.querySelector('#missingRecordsSection .btn');
            
            if (missingRecordsSection.style.display === 'none') {
                missingRecordsSection.style.display = 'block';
                toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Details';
            } else {
                missingRecordsSection.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-eye"></i> Show Details';
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Set default dates to yesterday; block today and future dates
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            const yesterdayStr = yesterday.toISOString().split('T')[0];
            
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            
            startDateInput.max = yesterdayStr;
            endDateInput.max = yesterdayStr;
            startDateInput.value = yesterdayStr;
            endDateInput.value = yesterdayStr;
            
            // Load available tables
            loadAvailableTables();
        });
    </script>
</body>
</html>
