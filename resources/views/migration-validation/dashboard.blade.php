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

        .summary-chip {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            margin-right: 0.5rem;
            margin-bottom: 0.35rem;
        }

        .summary-chip-complete { background-color: #d1e7dd; color: #0f5132; }
        .summary-chip-incomplete { background-color: #f8d7da; color: #842029; }
        .summary-chip-error { background-color: #e2e3e5; color: #41464b; }
        .summary-chip-total { background-color: #cff4fc; color: #055160; }

        .incomplete-callout-item {
            display: inline-block;
            margin: 0.15rem 0.35rem 0.15rem 0;
            padding: 0.2rem 0.55rem;
            background: #fff;
            border: 1px solid #f5c2c7;
            border-radius: 4px;
            font-size: 0.875rem;
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
                                    <label for="startDate" class="form-label">Start Date (UTC)</label>
                                    <input type="date" class="form-control" id="startDate" title="Calendar day in UTC">
                                </div>
                                <div class="col-md-3">
                                    <label for="endDate" class="form-label">End Date (UTC)</label>
                                    <input type="date" class="form-control" id="endDate" title="Calendar day in UTC">
                                </div>
                                <div class="col-md-3 d-flex align-items-end flex-wrap gap-2">
                                    <button type="button" class="btn btn-primary" onclick="validateSelectedTable()" id="validateBtn" disabled>
                                        <i class="fas fa-search"></i> Validate
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="validateAllTables(true)" id="validateAllBtn">
                                        <i class="fas fa-check-double"></i> Validate All
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="validateAllTables(true)" id="refreshCountsBtn" title="Re-run live counts and update cache">
                                        <i class="fas fa-sync-alt"></i> Refresh counts
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
                                <p>Loading previous-day results…</p>
                                <p><small>Uses cached counts when available (instant). Otherwise runs a live count check table by table.</small></p>
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

        function setCountButtonsDisabled(disabled) {
            const validateAllBtn = document.getElementById('validateAllBtn');
            const refreshCountsBtn = document.getElementById('refreshCountsBtn');
            if (validateAllBtn) {
                validateAllBtn.disabled = disabled;
            }
            if (refreshCountsBtn) {
                refreshCountsBtn.disabled = disabled;
            }
        }

        function buildSummaryFromValidations(validations) {
            let complete = 0;
            let incomplete = 0;
            let errors = 0;
            validations.forEach(row => {
                if (row.status === 'ERROR') {
                    errors++;
                } else if (row.is_complete) {
                    complete++;
                } else {
                    incomplete++;
                }
            });
            return {
                total_tables: validations.length,
                complete_tables: complete,
                incomplete_tables: incomplete,
                error_tables: errors,
            };
        }

        function loadCachedOrLiveResults() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            if (!startDate || !endDate) {
                return;
            }

            showLoading();
            const resultsDiv = document.getElementById('validationResults');
            resultsDiv.innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                    <p>Checking for cached previous-day results…</p>
                </div>
            `;

            const params = new URLSearchParams({ start_date: startDate, end_date: endDate });
            fetch('/api/migration-validation/results?' + params.toString())
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    hideLoading();
                    if (ok && data.success && data.data && data.data.validations) {
                        displayValidationResults({ success: true, data: data.data, cached: true });
                        return;
                    }
                    validateAllTables(false);
                })
                .catch(error => {
                    console.error('Cache lookup failed:', error);
                    hideLoading();
                    validateAllTables(false);
                });
        }

        async function validateAllTables(forceLive) {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const resultsDiv = document.getElementById('validationResults');

            if (!startDate || !endDate) {
                displayError('Please select both start and end dates.');
                return;
            }

            showLoading();
            setCountButtonsDisabled(true);

            let tables = [];
            try {
                const tablesResponse = await fetch('/api/migration-validation/tables');
                const tablesData = await tablesResponse.json();
                tables = (tablesData.data && tablesData.data.tables) ? tablesData.data.tables : [];
            } catch (error) {
                hideLoading();
                setCountButtonsDisabled(false);
                displayError('Failed to load tables: ' + error.message);
                return;
            }

            if (!tables.length) {
                hideLoading();
                setCountButtonsDisabled(false);
                displayError('No configured tables found.');
                return;
            }

            const validations = tables.map(table => ({
                table: table.name,
                mongodb_collection: table.mongodb_collection,
                mssql_table: table.mssql_table,
                mongodb_count: null,
                mssql_count: null,
                difference: null,
                is_complete: false,
                status: 'PENDING',
                error: null,
            }));

            const livePayload = {
                validations,
                summary: buildSummaryFromValidations(validations),
                start_date: startDate,
                end_date: endDate,
                validated_at: new Date().toISOString(),
            };
            displayValidationResults({ success: true, data: livePayload, cached: false, inProgress: true });

            for (let i = 0; i < tables.length; i++) {
                const tableName = tables[i].name;
                try {
                    const response = await fetch('/api/migration-validation/validate/table/counts', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            table: tableName,
                            start_date: startDate,
                            end_date: endDate,
                        }),
                    });
                    const data = await response.json();
                    if (data.success && data.data) {
                        validations[i] = data.data;
                    } else {
                        validations[i] = {
                            ...validations[i],
                            status: 'ERROR',
                            error: data.error || 'Count failed',
                        };
                    }
                } catch (error) {
                    validations[i] = {
                        ...validations[i],
                        status: 'ERROR',
                        error: error.message || 'Count failed',
                    };
                }

                livePayload.validations = validations;
                livePayload.summary = buildSummaryFromValidations(validations);
                livePayload.validated_at = new Date().toISOString();
                displayValidationResults({
                    success: true,
                    data: livePayload,
                    cached: false,
                    inProgress: i < tables.length - 1,
                    progress: { done: i + 1, total: tables.length },
                });
            }

            try {
                await fetch('/api/migration-validation/results', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        start_date: startDate,
                        end_date: endDate,
                        validations,
                        summary: livePayload.summary,
                        validated_at: livePayload.validated_at,
                    }),
                });
            } catch (error) {
                console.error('Failed to save cached results:', error);
            }

            hideLoading();
            setCountButtonsDisabled(false);
            displayValidationResults({ success: true, data: livePayload, cached: false, inProgress: false });
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

        function formatDisplayDate(value) {
            if (!value) {
                return document.getElementById('startDate')?.value || '—';
            }
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return String(value).split('T')[0];
            }
            return date.toISOString().split('T')[0];
        }

        function displayValidationResults(data) {
            const resultsDiv = document.getElementById('validationResults');
            
            if (data && data.success && data.data && data.data.validations) {
                const summary = data.data.summary || {};
                const fromCache = !!data.cached;
                const inProgress = !!data.inProgress;
                const progress = data.progress || null;
                const validations = [...data.data.validations].sort((a, b) => {
                    const rank = (row) => {
                        if (row.status === 'PENDING') return 3;
                        if (row.status === 'ERROR') return 0;
                        return row.is_complete ? 2 : 1;
                    };
                    const rankDiff = rank(a) - rank(b);
                    if (rankDiff !== 0) {
                        return rankDiff;
                    }
                    return Math.abs(Number(b.difference) || 0) - Math.abs(Number(a.difference) || 0);
                });

                const completeCount = Number(summary.complete_tables || 0);
                const incompleteCount = Number(summary.incomplete_tables || 0);
                const errorCount = Number(summary.error_tables || 0);
                const totalCount = Number(summary.total_tables || validations.length);
                const dayLabel = formatDisplayDate(data.data.start_date || data.data.end_date);
                const validatedAt = data.data.validated_at
                    ? new Date(data.data.validated_at).toLocaleString()
                    : new Date().toLocaleString();

                const incompleteTables = validations.filter(row => row.status === 'INCOMPLETE' || (row.status !== 'ERROR' && row.status !== 'PENDING' && !row.is_complete));
                const errorTables = validations.filter(row => row.status === 'ERROR');

                let incompleteCallout = '';
                if (!inProgress) {
                    if (incompleteTables.length > 0 || errorTables.length > 0) {
                        const mismatchItems = incompleteTables.map(row => {
                            const gap = row.difference === null || row.difference === undefined
                                ? '—'
                                : formatSignedCount(row.difference);
                            return `<span class="incomplete-callout-item"><strong>${escapeHtml(row.table || 'Unknown')}</strong> gap ${gap}</span>`;
                        }).join('');
                        const errorItems = errorTables.map(row =>
                            `<span class="incomplete-callout-item"><strong>${escapeHtml(row.table || 'Unknown')}</strong> error</span>`
                        ).join('');

                        incompleteCallout = `
                            <div class="alert alert-warning">
                                <strong>Needs attention</strong>
                                <div class="mt-2">${mismatchItems}${errorItems}</div>
                            </div>
                        `;
                    } else {
                        incompleteCallout = `
                            <div class="alert alert-success">
                                <strong>All tables match</strong> for ${escapeHtml(dayLabel)}. MongoDB and MSSQL counts are equal.
                            </div>
                        `;
                    }
                } else if (progress) {
                    incompleteCallout = `
                        <div class="alert alert-info">
                            <i class="fas fa-spinner fa-spin"></i>
                            Counting tables… ${progress.done} / ${progress.total}
                        </div>
                    `;
                }

                let rows = '';
                validations.forEach(result => {
                    const table = result.table || 'Unknown';
                    const mongodbCount = result.mongodb_count;
                    const mssqlCount = result.mssql_count;
                    const difference = result.difference;
                    const isComplete = result.is_complete || false;
                    const status = result.status || 'UNKNOWN';
                    let badgeClass = 'bg-danger';
                    if (status === 'ERROR') {
                        badgeClass = 'bg-secondary';
                    } else if (status === 'PENDING') {
                        badgeClass = 'bg-warning text-dark';
                    } else if (isComplete) {
                        badgeClass = 'bg-success';
                    }
                    const countText = (value) => value === null || value === undefined ? '—' : Number(value).toLocaleString();
                    const gapText = difference === null || difference === undefined ? '—' : formatSignedCount(difference);
                    const gapClass = difference === 0 ? 'status-complete' : (difference === null || difference === undefined ? '' : 'status-incomplete');

                    let actionCell = '';
                    if (status === 'PENDING') {
                        actionCell = '<small class="text-muted">Waiting…</small>';
                    } else if (status === 'ERROR') {
                        actionCell = `<small class="text-danger">${escapeHtml(result.error || 'Count failed')}</small>`;
                    } else {
                        actionCell = `<button class="btn btn-outline-primary btn-sm" onclick="validateOneFromAll('${escapeHtml(table)}')">Record check</button>`;
                    }

                    rows += `
                        <tr>
                            <td>${escapeHtml(table)}</td>
                            <td class="text-end">${countText(mongodbCount)}</td>
                            <td class="text-end">${countText(mssqlCount)}</td>
                            <td class="text-end ${gapClass}">${gapText}</td>
                            <td><span class="badge ${badgeClass}">${escapeHtml(status)}</span></td>
                            <td>${actionCell}</td>
                        </tr>
                    `;
                });

                const sourceBadge = fromCache
                    ? '<span class="badge bg-info ms-2">Cached</span>'
                    : (inProgress ? '<span class="badge bg-warning text-dark ms-2">Live…</span>' : '<span class="badge bg-success ms-2">Live</span>');

                resultsDiv.innerHTML = `
                    <div class="alert alert-primary mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <strong>Previous day:</strong> ${escapeHtml(dayLabel)}
                                <span class="text-muted ms-2">(UTC calendar day)</span>
                                ${sourceBadge}
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted">Validated at ${escapeHtml(validatedAt)}</small>
                                ${!inProgress ? `<button type="button" class="btn btn-sm btn-outline-primary" onclick="validateAllTables(true)"><i class="fas fa-sync-alt"></i> Refresh counts</button>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <span class="summary-chip summary-chip-complete">${completeCount.toLocaleString()} complete</span>
                        <span class="summary-chip summary-chip-incomplete">${incompleteCount.toLocaleString()} incomplete</span>
                        <span class="summary-chip summary-chip-error">${errorCount.toLocaleString()} errors</span>
                        <span class="summary-chip summary-chip-total">${totalCount.toLocaleString()} total</span>
                    </div>
                    ${incompleteCallout}
                    <p class="text-muted small mb-2">Complete means MongoDB count equals MSSQL count. Use Record check for identifier match details.${fromCache ? ' Cached snapshot — use Refresh counts for a live re-run.' : ''}</p>
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

            // Prefer cached previous-day counts on login; fall back to live progressive counts
            loadCachedOrLiveResults();
        });
    </script>
</body>
</html>
