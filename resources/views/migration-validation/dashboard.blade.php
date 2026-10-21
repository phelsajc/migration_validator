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
                                <div class="col-md-3 d-flex align-items-end flex-wrap gap-2">
                                    <button type="button" class="btn btn-primary" onclick="validateSelectedTable()" id="validateBtn" disabled>
                                        <i class="fas fa-search"></i> Validate
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="validateAllTables()" id="validateAllBtn">
                                        <i class="fas fa-check-double"></i> Validate All
                                    </button>
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
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const validateAllBtn = document.getElementById('validateAllBtn');
            const resultsDiv = document.getElementById('validationResults');

            if (!startDate || !endDate) {
                displayError('Please select both start and end dates.');
                return;
            }

            showLoading();
            if (validateAllBtn) {
                validateAllBtn.disabled = true;
            }
            resultsDiv.innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                    <p>Counting MongoDB and MSSQL for every table.</p>
                    <p><small>This is the count-only check. It can take a few minutes. Record matching stays on the single-table Validate button.</small></p>
                </div>
            `;

            fetch('/api/migration-validation/validate/all', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    start_date: startDate,
                    end_date: endDate
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (validateAllBtn) {
                    validateAllBtn.disabled = false;
                }
                displayValidationResults(data);
            })
            .catch(error => {
                hideLoading();
                if (validateAllBtn) {
                    validateAllBtn.disabled = false;
                }
                console.error('Error:', error);
                displayError('Validation failed: ' + error.message);
            });
        }

        function validateOneFromAll(tableName) {
            const tableSelect = document.getElementById('tableSelect');
            tableSelect.value = tableName;
            loadTableInfo();
            validateSelectedTable();
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

        function buildValidationSummary(isComplete, missingCount, identifierLabel, mongodbCount, hasAnalysis) {
            if (!hasAnalysis) {
                return 'Match details are unavailable, so this status cannot be checked record by record.';
            }

            if (isComplete) {
                return `Complete because all ${Number(mongodbCount).toLocaleString()} MongoDB records in this date range were found in MSSQL by ${identifierLabel}. Incomplete only means MongoDB IDs are missing from MSSQL.`;
            }

            return `Incomplete because ${Number(missingCount).toLocaleString()} MongoDB record(s) have no matching ${identifierLabel} in MSSQL.`;
        }

        function buildCountGapNote(rowGap, mssqlCount, mssqlMatchRows) {
            const notes = [];
            if (rowGap === 0) {
                notes.push('MongoDB and MSSQL row counts are equal.');
            } else if (rowGap > 0) {
                notes.push(`MSSQL has ${rowGap.toLocaleString()} more rows than MongoDB.`);
            } else {
                notes.push(`MongoDB has ${Math.abs(rowGap).toLocaleString()} more records than MSSQL.`);
            }

            if (mssqlMatchRows !== null && Number(mssqlMatchRows) !== Number(mssqlCount)) {
                notes.push(`Matching compares distinct MSSQL rows (${Number(mssqlMatchRows).toLocaleString()}), while the MSSQL count is every row (${Number(mssqlCount).toLocaleString()}).`);
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
                const isComplete = result.is_complete || false;
                const status = result.status || 'UNKNOWN';
                const validatedAt = result.validated_at || new Date().toISOString();
                const missingRecordsAnalysis = result.missing_records_analysis || null;
                const identifierLabel = formatIdentifierLabel(result.identifier_field);
                const missingCount = Number(
                    missingRecordsAnalysis?.missing_from_mssql
                    ?? result.difference
                    ?? (missingRecordsAnalysis?.missing_records?.length || 0)
                );
                const mssqlMatchRows = missingRecordsAnalysis && missingRecordsAnalysis.mssql_total !== undefined
                    ? Number(missingRecordsAnalysis.mssql_total)
                    : null;
                const rowGap = mssqlCount - mongodbCount;
                const rowGapAbs = Math.abs(rowGap);
                const summary = buildValidationSummary(
                    isComplete,
                    missingCount,
                    identifierLabel,
                    mongodbCount,
                    !!missingRecordsAnalysis
                );
                const gapNote = missingRecordsAnalysis
                    ? buildCountGapNote(rowGap, mssqlCount, mssqlMatchRows)
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
                                <span class="${missingCount === 0 ? 'status-complete' : 'status-incomplete'}">
                                    ${missingCount.toLocaleString()}
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
                                <div class="col-md-12">
                                    <div class="result-breakdown p-3 h-100">
                                        <h6>Why the counts differ</h6>
                                        <div><strong>MongoDB records:</strong> ${mongodbCount.toLocaleString()}</div>
                                        <div><strong>MSSQL rows:</strong> ${mssqlCount.toLocaleString()}</div>
                                        <div><strong>Row count gap (MSSQL − MongoDB):</strong> <span class="${rowGap === 0 ? 'status-complete' : 'status-incomplete'}">${formatSignedCount(rowGap)}</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button class="btn btn-warning btn-sm me-2" onclick="showMissingRecords('${table}')">
                                        <i class="fas fa-exclamation-triangle"></i> View ${missingCount.toLocaleString()} missing from MSSQL
                                    </button>
                                    ${rowGap > 0 ? `
                                    <button class="btn btn-outline-secondary btn-sm" onclick="showExtraRecords('${table}')">
                                        <i class="fas fa-list"></i> View ${rowGapAbs.toLocaleString()} in MSSQL but not in MongoDB
                                    </button>
                                    ` : ''}
                                </div>
                            </div>
                        ` : ''}
                    </div>
                `;
                
                if (missingRecordsAnalysis) {
                    window.currentMissingRecords = missingRecordsAnalysis;
                    window.currentValidationMeta = {
                        table,
                        mongodbCount,
                        mssqlCount,
                        rowGap,
                        rowGapAbs
                    };
                }
            } else {
                console.error('Invalid response data:', data);
                displayError(data?.error || 'Validation failed - Invalid response format');
            }
        }

        function displayValidationResults(data) {
            const resultsDiv = document.getElementById('validationResults');
            
            if (data && data.success && data.data && data.data.validations) {
                const summary = data.data.summary || {};
                const validations = [...data.data.validations].sort((a, b) => {
                    const rank = (row) => row.status === 'ERROR' ? 0 : (row.is_complete ? 2 : 1);
                    const rankDiff = rank(a) - rank(b);
                    if (rankDiff !== 0) {
                        return rankDiff;
                    }
                    return Math.abs(Number(b.difference) || 0) - Math.abs(Number(a.difference) || 0);
                });

                let rows = '';
                validations.forEach(result => {
                    const table = result.table || 'Unknown';
                    const mongodbCount = result.mongodb_count;
                    const mssqlCount = result.mssql_count;
                    const difference = result.difference;
                    const isComplete = result.is_complete || false;
                    const status = result.status || 'UNKNOWN';
                    const badgeClass = status === 'ERROR' ? 'bg-secondary' : (isComplete ? 'bg-success' : 'bg-danger');
                    const countText = (value) => value === null || value === undefined ? '—' : Number(value).toLocaleString();
                    const gapText = difference === null || difference === undefined ? '—' : formatSignedCount(difference);

                    rows += `
                        <tr>
                            <td>${escapeHtml(table)}</td>
                            <td class="text-end">${countText(mongodbCount)}</td>
                            <td class="text-end">${countText(mssqlCount)}</td>
                            <td class="text-end ${difference === 0 ? 'status-complete' : 'status-incomplete'}">${gapText}</td>
                            <td><span class="badge ${badgeClass}">${escapeHtml(status)}</span></td>
                            <td>
                                ${status === 'ERROR'
                                    ? `<small class="text-danger">${escapeHtml(result.error || 'Count failed')}</small>`
                                    : `<button class="btn btn-outline-primary btn-sm" onclick="validateOneFromAll('${escapeHtml(table)}')">Record check</button>`
                                }
                            </td>
                        </tr>
                    `;
                });

                resultsDiv.innerHTML = `
                    <div class="alert alert-info">
                        <strong>Count check for every table.</strong>
                        ${Number(summary.complete_tables || 0).toLocaleString()} counts match,
                        ${Number(summary.incomplete_tables || 0).toLocaleString()} counts differ,
                        ${Number(summary.error_tables || 0).toLocaleString()} errors
                        out of ${Number(summary.total_tables || validations.length).toLocaleString()} tables.
                        <div class="mt-1"><small>Complete here means the MongoDB count equals the MSSQL count. Use Record check on one table for the identifier match.</small></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>Table</th>
                                    <th class="text-end">MongoDB</th>
                                    <th class="text-end">MSSQL</th>
                                    <th class="text-end">Count gap</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                `;
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
            setAnalysisTitle('In MSSQL but not in MongoDB');
            const analysis = window.currentMissingRecords || {};
            const meta = window.currentValidationMeta || {};
            const rowGapAbs = Number(meta.rowGapAbs ?? Math.max(0, (meta.mssqlCount || 0) - (meta.mongodbCount || 0)));
            const allExtraRecords = analysis.extra_records || [];
            // Show only up to the row count gap so the list matches the gap number
            const extraRecords = rowGapAbs > 0
                ? allExtraRecords.slice(0, rowGapAbs)
                : allExtraRecords;

            if (extraRecords.length > 0) {
                let html = `
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>MongoDB Count:</strong> ${Number(meta.mongodbCount || 0).toLocaleString()}
                        </div>
                        <div class="col-md-3">
                            <strong>MSSQL Count:</strong> ${Number(meta.mssqlCount || 0).toLocaleString()}
                        </div>
                        <div class="col-md-3">
                            <strong>Row count gap:</strong>
                            <span class="text-danger">${rowGapAbs.toLocaleString()}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Showing:</strong> ${extraRecords.length.toLocaleString()}
                        </div>
                    </div>
                    <hr>
                    <h6>MSSQL identifiers with no MongoDB match (row count gap):</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Identifier</th>
                                    <th>Created Date</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                extraRecords.forEach((record, index) => {
                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><code>${escapeHtml(record.universal_id || 'N/A')}</code></td>
                            <td>${escapeHtml(record.mssql_date || 'N/A')}</td>
                        </tr>
                    `;
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
                        <i class="fas fa-info-circle"></i> No MSSQL-only identifiers available for this row count gap.
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
