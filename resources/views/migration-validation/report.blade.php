<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-complete { color: #28a745; }
        .status-incomplete { color: #dc3545; }
        .status-error { color: #6c757d; }
        .card-header { background-color: #f8f9fa; }
        .loading { display: none; }
        .sortable { cursor: pointer; user-select: none; }
        .sortable:hover { background-color: #e9ecef; }
        .sort-icon { font-size: 0.75rem; margin-left: 4px; }
        tr.table-row-incomplete { background-color: #fff8f8; }
        tr.table-row-error { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center mt-4 mb-4">
                <h1 class="mb-0">
                    <i class="fas fa-file-alt"></i> Migration Report
                </h1>
                <a href="{{ route('migration-validation.dashboard') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-alt"></i> Report Parameters</h5>
                    </div>
                    <div class="card-body">
                        <form id="reportForm" onsubmit="return false;">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="startDate" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="startDate">
                                </div>
                                <div class="col-md-3">
                                    <label for="endDate" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="endDate">
                                </div>
                                <div class="col-md-6 d-flex align-items-end gap-2">
                                    <button type="button" class="btn btn-primary" onclick="generateReport()" id="generateBtn">
                                        <i class="fas fa-play"></i> Generate Report
                                    </button>
                                    <button type="button" class="btn btn-outline-success" onclick="downloadCsv()" id="exportBtn" disabled>
                                        <i class="fas fa-download"></i> Download CSV
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-table"></i> Migration Summary</h5>
                        <div class="loading" id="loadingSpinner">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="reportSummary" class="mb-3" style="display: none;"></div>
                        <div id="reportResults">
                            <div class="text-center text-muted">
                                <i class="fas fa-info-circle fa-2x mb-3"></i>
                                <p>Select a date range and click "Generate Report" to compare MongoDB vs IQVIA_Staging counts for all tables.</p>
                                <p><small>This may take several minutes for all configured tables.</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4" id="detailSection" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-search"></i> Table Detail: <span id="detailTableName"></span></h5>
                        <button class="btn btn-outline-secondary btn-sm" onclick="hideDetailSection()">
                            <i class="fas fa-times"></i> Close
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="detailContent">
                            <div class="text-center text-muted">
                                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                                <p>Loading table details...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let reportData = null;
        let sortColumn = 'table';
        let sortDirection = 'asc';

        function showLoading() {
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('generateBtn').disabled = true;
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('generateBtn').disabled = false;
        }

        function getDateRange() {
            return {
                start_date: document.getElementById('startDate').value,
                end_date: document.getElementById('endDate').value,
            };
        }

        function generateReport() {
            const dates = getDateRange();
            if (!dates.start_date || !dates.end_date) {
                displayError('Please select both start and end dates.');
                return;
            }

            showLoading();
            hideDetailSection();
            document.getElementById('reportResults').innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                    <p>Generating report for all configured tables...</p>
                    <p><small>This may take several minutes.</small></p>
                </div>
            `;
            document.getElementById('reportSummary').style.display = 'none';
            document.getElementById('exportBtn').disabled = true;

            fetch('/api/migration-validation/validate/all', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dates),
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    reportData = data.data;
                    displayReport(data.data);
                    document.getElementById('exportBtn').disabled = false;
                } else {
                    displayError(data.error || 'Report generation failed.');
                }
            })
            .catch(error => {
                hideLoading();
                displayError('Report generation failed: ' + error.message);
            });
        }

        function displayReport(data) {
            const summaryDiv = document.getElementById('reportSummary');
            const summary = data.summary || {};
            summaryDiv.innerHTML = `
                <div class="alert alert-info mb-0">
                    <strong>Summary:</strong>
                    ${summary.complete_tables || 0} complete,
                    ${summary.incomplete_tables || 0} incomplete,
                    ${summary.error_tables || 0} errors
                    out of ${summary.total_tables || 0} tables
                    <span class="ms-3 text-muted">
                        (${formatDate(data.start_date)} to ${formatDate(data.end_date)})
                    </span>
                </div>
            `;
            summaryDiv.style.display = 'block';

            renderReportTable(data.validations || []);
        }

        function renderReportTable(validations) {
            const sorted = sortValidations([...validations]);
            let html = `
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="reportTable">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="sortTable('table')">Table <span class="sort-icon">${getSortIcon('table')}</span></th>
                                <th class="sortable" onclick="sortTable('mongodb_collection')">MongoDB Collection <span class="sort-icon">${getSortIcon('mongodb_collection')}</span></th>
                                <th class="sortable" onclick="sortTable('mssql_table')">IQVIA_Staging Table <span class="sort-icon">${getSortIcon('mssql_table')}</span></th>
                                <th class="sortable text-end" onclick="sortTable('mongodb_count')">MongoDB Count <span class="sort-icon">${getSortIcon('mongodb_count')}</span></th>
                                <th class="sortable text-end" onclick="sortTable('mssql_count')">MSSQL Count <span class="sort-icon">${getSortIcon('mssql_count')}</span></th>
                                <th class="sortable text-end" onclick="sortTable('difference')">Difference <span class="sort-icon">${getSortIcon('difference')}</span></th>
                                <th class="sortable" onclick="sortTable('status')">Status <span class="sort-icon">${getSortIcon('status')}</span></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            sorted.forEach(row => {
                const rowClass = row.status === 'ERROR' ? 'table-row-error' : (row.is_complete ? '' : 'table-row-incomplete');
                const mongoCount = row.mongodb_count !== null ? Number(row.mongodb_count).toLocaleString() : '—';
                const mssqlCount = row.mssql_count !== null ? Number(row.mssql_count).toLocaleString() : '—';
                const difference = row.difference !== null ? Number(row.difference).toLocaleString() : '—';
                const diffClass = row.difference === 0 ? 'status-complete' : (row.difference !== null ? 'status-incomplete' : 'status-error');
                const badgeClass = row.status === 'COMPLETE' ? 'bg-success' : (row.status === 'ERROR' ? 'bg-secondary' : 'bg-danger');

                html += `
                    <tr class="${rowClass}">
                        <td><strong>${escapeHtml(row.table)}</strong></td>
                        <td>${escapeHtml(row.mongodb_collection || '')}</td>
                        <td>${escapeHtml(row.mssql_table || '')}</td>
                        <td class="text-end">${mongoCount}</td>
                        <td class="text-end">${mssqlCount}</td>
                        <td class="text-end ${diffClass}">${difference}</td>
                        <td>
                            <span class="badge ${badgeClass}">${row.status}</span>
                            ${row.error ? `<br><small class="text-muted">${escapeHtml(row.error)}</small>` : ''}
                        </td>
                        <td>
                            ${row.status !== 'ERROR' ? `
                                <button class="btn btn-sm btn-outline-primary" onclick="viewTableDetails('${escapeHtml(row.table)}')">
                                    <i class="fas fa-search"></i> View Details
                                </button>
                            ` : ''}
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            document.getElementById('reportResults').innerHTML = html;
        }

        function sortTable(column) {
            if (sortColumn === column) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = column;
                sortDirection = 'asc';
            }
            if (reportData && reportData.validations) {
                renderReportTable(reportData.validations);
            }
        }

        function sortValidations(validations) {
            return validations.sort((a, b) => {
                let valA = a[sortColumn];
                let valB = b[sortColumn];

                if (valA === null) valA = sortDirection === 'asc' ? Infinity : -Infinity;
                if (valB === null) valB = sortDirection === 'asc' ? Infinity : -Infinity;

                if (typeof valA === 'string') valA = valA.toLowerCase();
                if (typeof valB === 'string') valB = valB.toLowerCase();

                if (valA < valB) return sortDirection === 'asc' ? -1 : 1;
                if (valA > valB) return sortDirection === 'asc' ? 1 : -1;
                return 0;
            });
        }

        function getSortIcon(column) {
            if (sortColumn !== column) return '';
            return sortDirection === 'asc' ? '▲' : '▼';
        }

        function viewTableDetails(tableName) {
            const dates = getDateRange();
            const detailSection = document.getElementById('detailSection');
            const detailContent = document.getElementById('detailContent');

            document.getElementById('detailTableName').textContent = tableName;
            detailSection.style.display = 'block';
            detailContent.innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                    <p>Loading details for ${escapeHtml(tableName)}...</p>
                </div>
            `;
            detailSection.scrollIntoView({ behavior: 'smooth' });

            fetch('/api/migration-validation/validate/table', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    table: tableName,
                    start_date: dates.start_date,
                    end_date: dates.end_date,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayTableDetail(data.data);
                } else {
                    detailContent.innerHTML = `<div class="alert alert-danger">${escapeHtml(data.error || 'Failed to load details.')}</div>`;
                }
            })
            .catch(error => {
                detailContent.innerHTML = `<div class="alert alert-danger">Failed to load details: ${escapeHtml(error.message)}</div>`;
            });
        }

        function displayTableDetail(result) {
            const analysis = result.missing_records_analysis || {};
            const missingRecords = analysis.missing_records || [];
            const mongodbCount = result.mongodb_count || 0;
            const mssqlCount = result.mssql_count || 0;
            const difference = result.difference || 0;

            let html = `
                <div class="row mb-3">
                    <div class="col-md-3"><strong>MongoDB Count:</strong> ${Number(mongodbCount).toLocaleString()}</div>
                    <div class="col-md-3"><strong>MSSQL Count:</strong> ${Number(mssqlCount).toLocaleString()}</div>
                    <div class="col-md-3"><strong>Difference:</strong> <span class="${difference === 0 ? 'status-complete' : 'status-incomplete'}">${Number(difference).toLocaleString()}</span></div>
                    <div class="col-md-3">
                        <strong>Status:</strong>
                        <span class="badge ${result.is_complete ? 'bg-success' : 'bg-danger'}">${result.status}</span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3"><strong>Found Matches:</strong> ${Number(analysis.found_matches || 0).toLocaleString()}</div>
                    <div class="col-md-3"><strong>Missing from MSSQL:</strong> ${Number(analysis.missing_from_mssql || 0).toLocaleString()}</div>
                    <div class="col-md-6"><strong>Validated At:</strong> ${new Date(result.validated_at).toLocaleString()}</div>
                </div>
            `;

            if (missingRecords.length > 0) {
                html += `
                    <hr>
                    <h6>Missing Records (${missingRecords.length} shown)</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
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

                missingRecords.forEach((record, index) => {
                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><code>${escapeHtml(record.universal_id || record.mongo_id || 'N/A')}</code></td>
                            <td>${escapeHtml(record.mongo_createdat || 'N/A')}</td>
                            <td>${escapeHtml(record.modifiedat || 'N/A')}</td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
            } else if (!result.is_complete) {
                html += '<div class="alert alert-warning mt-3">Count mismatch detected but no missing record details were returned.</div>';
            } else {
                html += '<div class="alert alert-success mt-3">Migration is complete for this table in the selected date range.</div>';
            }

            document.getElementById('detailContent').innerHTML = html;
        }

        function hideDetailSection() {
            document.getElementById('detailSection').style.display = 'none';
        }

        function downloadCsv() {
            if (!reportData || !reportData.validations) {
                return;
            }

            const headers = ['Table', 'MongoDB Collection', 'IQVIA_Staging Table', 'MongoDB Count', 'MSSQL Count', 'Difference', 'Status', 'Error'];
            const rows = reportData.validations.map(row => [
                row.table,
                row.mongodb_collection,
                row.mssql_table,
                row.mongodb_count !== null ? row.mongodb_count : '',
                row.mssql_count !== null ? row.mssql_count : '',
                row.difference !== null ? row.difference : '',
                row.status,
                row.error || '',
            ]);

            const csvContent = [headers, ...rows]
                .map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))
                .join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            link.href = URL.createObjectURL(blob);
            link.download = `migration-report_${startDate}_to_${endDate}.csv`;
            link.click();
            URL.revokeObjectURL(link.href);
        }

        function displayError(message) {
            document.getElementById('reportResults').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> ${escapeHtml(message)}
                </div>
            `;
        }

        function formatDate(isoString) {
            if (!isoString) return '';
            return new Date(isoString).toLocaleDateString();
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(text);
            return div.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);

            document.getElementById('startDate').value = yesterday.toISOString().split('T')[0];
            document.getElementById('endDate').value = yesterday.toISOString().split('T')[0];
        });
    </script>
</body>
</html>
