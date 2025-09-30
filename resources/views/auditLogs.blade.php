@extends('layouts.app')

@section('title', 'Dashboard')
<link rel="stylesheet" href="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.css') }}">

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Logs</h3>
            </div>
            <div class="card-body">
                {{-- <form method="POST" action="{{ route('audit_logs') }}" class="mb-4 d-flex flex-wrap align-items-end gap-2">
                    @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <label>Date</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                                <input type="text" required class="form-control float-right" id="auditDate"
                                    name="auditDate" value="{{ request('auditDate') }}">
                            </div>
                        </div>

                        <div class="col-md-3 d-flex align-items-end gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary mr-2">Filter</button>
                        </div>
                    </div>
                </form> --}}

                
                <form method="POST" action="{{ route('export_audit_logs') }}" class="mb-4">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label for="auditDate" class="form-label">Date</label>
                            <div class="input-group w-100">
                                <span class="input-group-text"><i class="far fa-clock"></i></span>
                                <input
                                    type="text"
                                    required
                                    class="form-control"
                                    id="auditDate"
                                    name="auditDate"
                                    value="{{ request('auditDate') }}"
                                >
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="auditDate" class="form-label">MRN</label>
                            <div class="input-group w-100">
                                <input
                                    type="text"
                                    class="form-control"
                                    id="mrn"
                                    name="mrn"
                                    value="{{ request('mrn') }}"
                                >
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="auditDate" class="form-label">Role</label>
                            <div class="input-group w-100">
                                <input
                                    type="text"
                                    class="form-control"
                                    id="role"
                                    name="role"
                                    value="{{ request('role') }}"
                                >
                            </div>
                        </div>
                
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Export</button>
                        </div>
                    </div>
                </form>
                

                {{-- <form id="exportForm" action="{{ route('export.excel') }}" method="GET" onsubmit="return validateExport()">
                    <input type="hidden" name="visitdate" value="{{ request('visitdate') }}">
                    <input type="hidden" name="type" value="{{ request('type') }}">
                    <input type="hidden" name="department" id="exportDepartment">
                    <button type="submit" class="btn btn-success">Export to Excel</button>
                </form>

                <form method="GET" id="perPageForm" action="{{ route('episodes') }}">
                    <input type="hidden" name="visitdate" value="{{ request('visitdate') }}">
                    <input type="hidden" name="department" value="{{ request('department') }}">
                    <input type="hidden" name="type" value="{{ request('type') }}">

                    <label>Show</label>
                    <select name="per_page" onchange="document.getElementById('perPageForm').submit();">
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ request('per_page') == $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                    <label>records per page</label>
                </form> --}}

                {{-- <div class="table-responsive">
                    <table class="table table-bordered" id="resultTbl">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Dataset</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Patient</th>
                                <th>Visit no</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $cnt=1;
                            @endphp
                            @foreach ($results as $index => $row)
                                <tr>
                                    <td>{{$index+1}}</td>
                                    <td>{{ date_format(date_create($row->auditdate), ' F d, Y H:i:s') }}</td>
                                    <td>{{ $row->dataset }}</td>
                                    <td>{{ $row->printname ?? '' }}</td>
                                    <td>{{ $row->role ?? '' }}</td>
                                    <td>{{ $row->mrn ?? ''}}</td>
                                    <td>{{ $row->visitno ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div> --}}
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.js') }}"></script>

    <script>
        $('#auditDate').daterangepicker();
        $('#auditDate').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
        });

        $('#auditDate').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        function formatCurrency(amount, currency = 'PHP', locale = 'en-PH') {
            return new Intl.NumberFormat(locale, {
                style: 'currency',
                currency: currency,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);
        }

        function parseDateString(dateStr) {
            return new Date(dateStr.replace(' ', 'T').replace(' +', '+'));
        }

        function exportWithXLSX() {
            const table = document.getElementById("resultTbl");

            // Clone table to flatten multiple thead/tbody for proper export
            const clone = table.cloneNode(true);

            // Flatten multiple thead/tbody into one tbody
            const newTable = document.createElement("table");
            const tbody = document.createElement("tbody");

            const rows = clone.querySelectorAll("thead tr, tbody tr");
            rows.forEach(row => tbody.appendChild(row.cloneNode(true)));

            newTable.appendChild(tbody);

            const wb = XLSX.utils.table_to_book(newTable, {
                sheet: "Sheet1"
            });
            XLSX.writeFile(wb, "generated_report.xlsx");
        }

        function calculatePayorDiscountPercent(amount, pd) {
            let value = (pd / amount) * 100; //RONY GORECHO BAYABAN
            return value != 0 ? value.toFixed(2) + "%" : "";
        }

        
    function validateExport() {
        const department = document.getElementById('department').value;
        if (!department) {
            alert('Please select a department before exporting.');
            return false; // Prevent form submission
        }

        // Pass the value to the hidden input
        document.getElementById('exportDepartment').value = department;
        return true;
    }
    </script>
@endsection
