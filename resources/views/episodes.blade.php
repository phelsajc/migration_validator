@extends('layouts.app')

@section('title', 'Dashboard')
<link rel="stylesheet" href="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.css') }}">
<link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Episodes</h3>
            </div>
            <div class="card-body">
                {{-- <form method="POST" action="{{ route('episodes') }}" class="mb-4 d-flex flex-wrap align-items-end gap-2"> --}}
                <form id="exportForm" action="{{ route('export.excel') }}" method="GET" onsubmit="return validateExport()">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <label for="role">Department</label>
                            <select name="department" class="form-control" id="department" required>
                            {{-- <select name="department[]" class="form-control" id="department" required multiple> --}}
                                <option disabled value="0">Select Department</option>
                                <option selected disabled value="All">ALL</option>
                                @foreach ($departments as $item)
                                    @if ($item->ordertodepartmentname != null)
                                        <option value="{{ $item->ordertodepartmentname }}"
                                            {{ request('department') == $item->ordertodepartmentname ? 'selected' : '' }}>
                                            {{ $item->ordertodepartmentname }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label>Order Date</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                                <input type="text" required class="form-control float-right" id="visitdate"
                                    name="visitdate" value="{{ request('visitdate') }}">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label for="status">Type</label>
                            <select name="type" class="form-control" id="type">
                                <option value="All">All</option>
                                <option value="Emergency" {{ request('type') == 'Emergency' ? 'selected' : '' }}>
                                    Emergency</option>
                                <option value="Inpatient" {{ request('type') == 'Inpatient' ? 'selected' : '' }}>
                                    Inpatient</option>
                                <option value="Outpatient" {{ request('type') == 'Outpatient' ? 'selected' : '' }}>
                                    Outpatient</option>
                            </select>
                        </div>

                        {{-- <input type="hidden" name="page" id="page" value="{{ $currentPage }}"> --}}
                        <input type="hidden" name="dt_from" id="dt_from" value="{{ request('dt_from') }}">

                        <div class="col-md-3 d-flex align-items-end gap-2 flex-wrap">
                            {{-- <button type="submit" class="btn btn-primary mr-2 loadData">Filter</button> --}}
                    <button type="submit" class="btn btn-primary">Export to Excel</button>
                            {{-- <button type="button" onclick="exportWithXLSX()" class="btn btn-info mr-2">Export to
                                Excel</button> --}}
                        </div>

                    </div>
                </form>

                {{-- <form action="{{ route('export.excel') }}" method="GET">
                    <input type="hidden" name="visitdate" value="{{ request('visitdate') }}">
                    <input type="hidden" name="type" value="{{ request('type') }}">
                    <input type="hidden" name="department" value="{{ request('department') }}">
                    <button type="submit" class="btn btn-success">Export to Excel</button>
                </form> --}}

                {{-- <form id="exportForm" action="{{ route('export.excel') }}" method="GET" onsubmit="return validateExport()">
                    <input type="hidden" name="visitdate" value="{{ request('visitdate') }}" id="exportVisitdate">
                    <input type="hidden" name="type" value="{{ request('type') }}" id="exportType">
                    <input type="hidden" name="department" id="exportDepartment">
                    <button type="submit" class="btn btn-success">Export to Excel</button>
                </form> --}}

       
                <form method="GET" id="perPageForm" action="{{ route('episodes') }}">
                    <input type="hidden" name="visitdate" value="{{ request('visitdate') }}">
                    {{-- <input type="hidden" name="department[]" value="{{ request('department') }}"> --}}
                    <input type="hidden" name="department" value="{{ request('department') }}">
                    <input type="hidden" name="type" value="{{ request('type') }}">

                    {{-- <label>Show</label>
                    <select name="per_page" onchange="document.getElementById('perPageForm').submit();">
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ request('per_page') == $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                    <label>records per page</label> --}}
                </form>

                {{-- <div class="table-responsive">
                    <table class="table table-bordered" id="resultTbl">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>MRN</th>
                                <th>Visit Date</th>
                                <th>Order Date</th>
                                <th>Name</th>
                                <th>Encounter Type</th>
                                <th>order to Department</th>
                                <th>Order Item</th>
                                <th>Status</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $cnt=1;
                            @endphp
                            @foreach ($results as $index => $row)
                                @php
                                    $amount = $row->quantity * $row->unitprice;
                                @endphp
                                <tr>
                                    <td>{{$index+1}}</td>
                                    <td>{{ $row->mrn }}</td>
                                    <td>{{ date_format(date_create($row->startdate), ' F d, Y') }}</td>
                                    <td>{{ date_format(date_create($row->orderdate), ' F d, Y') }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ $row->entype }}</td>
                                    <td>{{ $row->ordertodepartmentname }}</td>
                                    <td>{{ $row->orderitemname }}</td>
                                    <td>{{ $row->statusdescription }}</td>
                                    <td>{{ number_format($amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($results)
                        {{ $results->appends(request()->except('page'))->links('pagination::bootstrap-4') }}
                    @endif
                </div> --}}
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>

        const isError = @json($error);
        if (isError) {
            toastr.error('Order Date more than a week. Click Export instead of Filter.')
        }else{
            toastr.success('Data loaded successfully.')
        }

        $('.loadData').click(function() {
            toastr.warning('Please wait while we load the data.')
        });

        $('#visitdate').daterangepicker();
        $('#visitdate').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
        });

        $('#visitdate').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        function formatCurrency(amount, currency = 'PHP', locale = 'en-PH') {
                return new Intl.NumberFormat(locale, {
                    style: 'decimal',
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

        const visitdate = document.getElementById('visitdate').value;
        if (!visitdate) {
            alert('Please select a date before exporting.');
            return false; // Prevent form submission
        }

        const type = document.getElementById('type').value;
        if (!type) {
            alert('Please select a type before exporting.');
            return false; // Prevent form submission
        }

        // Pass the value to the hidden input
        document.getElementById('exportDepartment').value = department;
        document.getElementById('exportVisitdate').value = visitdate;
        document.getElementById('exportType').value = type;
        return true;
    }
    </script>
@endsection
