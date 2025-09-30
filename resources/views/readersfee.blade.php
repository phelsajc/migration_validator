@extends('layouts.app')

@section('title', 'Dashboard')
<link rel="stylesheet" href="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.css') }}">

@section('content')
<style>
    .table-condensed td,
.table-condensed th {
  padding: 0.3rem 0.5rem;
}
</style>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Readers Fee</h3>
            </div>
            <div class="card-body">
                
                {{-- Error Display Section --}}
                @if(isset($error))
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-ban"></i> Error!</h5>
                        {{ $error }}
                    </div>
                @endif
                
                <form method="POST" action="{{ route('readersfee') }}" class="mb-3">
                    @csrf
                    <div class="row">
                        {{-- <div class="col-md-6">
                            <div class="form-check">
                                <input type="radio" id="grpByDoctor" name="group_by" value="rad"
                                    class="form-check-input" {{ $group_by == 'rad' ? 'checked' : '' }}>
                                <label class="form-check-label" for="grpByDoctor">Radiology</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" id="grpByModality" name="group_by" value="lab"
                                    class="form-check-input" {{ $group_by  == 'lab' ? 'checked' : '' }}>
                                <label class="form-check-label" for="grpByModality">Lab</label>
                            </div>
                        </div> --}}
                        <div class="col-md-2">
                            <label for="role">Select Category</label>
                            <select name="group_by" class="form-control" id="group_by"  onchange="changeCat(this)">
                                <option value="rad" {{ $group_by  == 'rad' ? 'selected' : '' }}>Radiology</option>
                                <option value="lab" {{ $group_by  == 'lab' ? 'selected' : '' }}>Laboratory</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label for="role">Select Date</label>
                            <select name="group_by_date" class="form-control" id="group_by_date">
                                <option value="OrderDate" {{ $group_by_date  == 'OrderDate' ? 'selected' : '' }}>Order Date</option>
                                <option value="t3.reportauthorizeddate" {{ $group_by_date  == 't3.reportauthorizeddate' ? 'selected' : '' }}>Report Authorised</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Date</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                                <input type="text" required class="form-control float-right" id="orderdate"
                                    name="orderdate" value="{{ request('orderdate') }}">
                            </div>
                        </div>
                       
                        <div class="col-md-2  {{$group_by  == 'lab' ? 'd-none': ''}}" id="modalityDiv">
                            <label for="status">Modality</label>
                            <select name="modality" class="form-control" id="modality">
                                <option value="All">All</option>
                                <option value="X-RAY" {{ request('modality') == 'X-RAY' ? 'selected' : '' }}>X-RAY</option>
                                <option value="CT" {{ request('modality') == 'CT' ? 'selected' : '' }}>CT</option>
                                <option value="MRI" {{ request('modality') == 'MRI' ? 'selected' : '' }}>MRI</option>
                                <option value="ULTRASOUND" {{ request('modality') == 'ULTRASOUND' ? 'selected' : '' }}>
                                    ULTRASOUND</option>
                                <option value="NICIS"
                                    {{ request('modality') == 'NICIS' ? 'selected' : '' }}>
                                    NICIS</option>
                            </select>
                        </div>
                                                
                        <div class="col-md-2">
                            <label for="role">Doctor</label>
                            <input type="text" name="doctor" id="doctor" class="form-control" value="{{ request('doctor') }}">
                        </div>          
                        <div class="col-md-1">
                            <label for="role">MRN</label>
                            <input type="text" name="mrn" id="mrn" class="form-control" value="{{ request('mrn') }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary mr-2">Load</button>
                            <button type="button" onclick="filter()" class="btn btn-success mr-2">Group</button>
                            <button type="button" onclick="exportWithXLSX()" class="btn btn-info mr-2">Export to
                                Excel</button>
                        </div>
                    </div>
                </form>

                {{-- <form method="GET" action="" class="mb-3">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-check">
                                <input type="checkbox" id="grpByDoctor" name="rad" value="rad"
                                    class="form-check-input" id="activeCheck" {{ request('active') ? 'checked' : '' }}>
                                <label class="form-check-label" for="activeCheck">Group By Radiologist</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Order Date</label>
                            <div class="input-group">
                                <input type="date" name="date_from" id="date_from" class="form-control"
                                    value="{{ request('date_from') }}">
                                <span class="mx-1">to</span>
                                <input type="date" name="date_to" id="date_to" class="form-control"
                                    value="{{ request('date_to') }}">
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label for="role">Radiologist</label>
                            <select name="radiologist" class="form-control" id="radiologist">
                                <option value="All">All</option>
                                @foreach ($doctors as $item)
                                    <option value="{{ $item['prcno'] }}">{{ $item['firstname'] }} {{ $item['lastname'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="status">Modality</label>
                            <select name="role" class="form-control" id="modality">
                                <option value="All">All</option>
                                <option value="X-RAY" {{ request('modality') == 'X-RAY' ? 'selected' : '' }}>X-RAY</option>
                                <option value="CT" {{ request('modality') == 'CT' ? 'selected' : '' }}>CT</option>
                                <option value="MRI" {{ request('modality') == 'MRI' ? 'selected' : '' }}>MRI</option>
                                <option value="ULTRASOUND" {{ request('modality') == 'ULTRASOUND' ? 'selected' : '' }}>
                                    ULTRASOUND</option>
                                <option value="NON-INVASIVE CARDIOVASCULAR IMAGING SCIENCES (NICIS)"
                                    {{ request('modality') == 'NON-INVASIVE CARDIOVASCULAR IMAGING SCIENCES (NICIS)' ? 'selected' : '' }}>
                                    NICIS</option>
                            </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-end gap-2 flex-wrap">
                            <button type="button" onclick="filter()" class="btn btn-primary mr-2">Filter</button>
                            <button type="button" onclick="exportWithXLSX()" class="btn btn-info mr-2">Export to
                                Excel</button>
                        </div>

                    </div>
                </form> --}}

                <div class="table-responsive">
                    <table class="table table-bordered table-condensed" id="resultTbl">
                        <thead>
                            <tr>
                                <th>Order Status</th>
                                <th>Exam Status</th>
                                <th>Order Date</th>
                                <th>Authorised Date</th>
                                <th>Item Group</th>
                                <th>order to Department</th>
                                <th>Order Number</th>
                                <th>MRN</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Exam</th>
                                <th>Amount</th>
                                <th>Readers Fee</th>
                                <th>Payor Discount</th>
                                <th>Payor Discount Amount</th>
                                <th>Special Discount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($results as $index => $row)
                                <tr>
                                    <td>{{ $row->orderstatus }}</td>
                                    <td>{{ $row->examstatus }}</td>
                                    <td>{{ date_format(date_create($row->OrderDate),'F d, Y') }}</td>
                                    <td>{{ date_format(date_create($row->reportauthorizeddate),'F d, Y') }}</td>
                                    <td>{{ $row->itemgroup }}</td>
                                    <td>{{ $row->ordertodepartmentname }}</td>
                                    <td>{{ $row->ordernumber }}</td>
                                    <td>{{ $row->mrn }}</td>
                                    <td>{{ $row->PatientName }}</td>
                                    <td>{{ $row->DoctorName_Order }}</td>
                                    <td>{{ $row->orderitemname }}</td>
                                    <td>{{ number_format($row->ExamPrice, 2) }}</td>
                                    <td>{{ number_format($row->ReadersFee, 2) }}</td>
                                    <td>{{ number_format(calculatePayorDiscountPercent($row->ExamPrice, $row->payordiscount), 2) . '%' }}</td>
                                    <td>{{ number_format(calculatePayorDiscountAmount($row->ExamPrice, $row->payordiscount), 2) }}</td>
                                    <td>{{ number_format($row->specialdiscount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.js') }}"></script><!-- Load Moment Timezone -->
    <script src="https://cdn.jsdelivr.net/npm/moment-timezone@0.5.43/builds/moment-timezone-with-data.min.js"></script>

    <script>
        $('#orderdate').daterangepicker();
        $('#orderdate').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
        });

        $('#orderdate').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        function filter() {
            const grpByDoctor = document.getElementById("grpByDoctor");
            const modality = document.getElementById("modality");
            const radiologist = document.getElementById("radiologist");
            /* const fromDate = document.getElementById("date_from").value;
            const toDate = document.getElementById("date_to").value; */

            let filtered = @json($results);
            console.log(filtered)
            let doctorsList = @json($doctors);

            /* if (modality.value != "All") {
                filtered = filtered.filter(row =>
                    row.ordertodepartmentname === modality.value
                );
                console.log(1)
            }

            if (radiologist.value != "All") {
                filtered = filtered.filter(row =>
                    row.prcno === radiologist.value
                );
                console.log(2)
            }


            if (fromDate) {
                filtered = filtered.filter(row => getLocalDateOnly(row.OrderDate) >= moment(fromDate).format('YYYY-MM-DD'));
            }

            if (toDate) {
                filtered = filtered.filter(row => getLocalDateOnly(row.OrderDate) <= moment(toDate).format('YYYY-MM-DD'));
            } */


            const container = document.getElementById("resultTbl");
            container.innerHTML = ""; // clear if needed

            const grouped = {};

            filtered.forEach(row => {
                if (!grouped[row.DoctorName_Order]) {
                    grouped[row.DoctorName_Order] = [];
                }
                grouped[row.DoctorName_Order].push({
                    patient: row.PatientName,
                    exam: row.orderitemname,
                    OrderDate: row.OrderDate,
                    reportauthorizeddate: row.reportauthorizeddate,
                    status: row.examstatus,
                    amount: row.ExamPrice,
                    rf: row.ReadersFee,
                    prcno: row.prcno,
                    pd: row.payordiscount,
                    sd: row.specialdiscount,
                });
            });


            for (const doctor in grouped) {
                const groupRows = grouped[doctor];
                const prcno = groupRows[0].prcno; // `doctor` is actually the prcno key in your grouped object
                const totalAmount = groupRows.reduce((sum, entry) => sum + parseFloat(entry.amount), 0);

                //  const totalRf = groupRows.reduce((sum, entry) => sum + parseFloat(entry.rf), 0);
                const totalRf = groupRows.reduce((sum, entry) => {
                    return entry.rf != null ? sum + parseFloat(entry.rf) : sum;
                }, 0);

                const doctorPrcno = doctorsList.find(doc => doc.prcno === prcno);
                const rows = grouped[doctor].map(entry => `                    
                    <tr>
                <td>${entry.patient}</td>
                <td>${entry.exam}</td>
                <td>${moment(entry.OrderDate).tz("Asia/Manila").format('MMM DD, YYYY')}</td>
                <td>${moment(entry.reportauthorizeddate).tz("Asia/Manila").format('MMM DD, YYYY')}</td>
                <td>${entry.status}</td>
                <td>${formatCurrency(entry.amount)}</td>
                <td>${formatCurrency(entry.rf)}</td>
                <td>${doctorPrcno ? doctorPrcno.tax : ''}</td>
                <td>${calculatePayorDiscountPercent(entry.amount,entry.pd)}</td>
                <td>${calculatePayorDiscountAmount(entry.amount,entry.pd)}</td>
                <td>${formatCurrency(entry.sd)}</td>
            </tr>
        `).join('');

                const table = `
          
            <table border="1" cellpadding="6" cellspacing="0" style="margin-bottom: 20px; width: 100%;">
                <thead>
                    <tr style="font-weight: bold; background-color: #f0f0f0;">
                        <th colspan="11" style="text-align: left;">${doctor}</th>
                    </tr>
                    <tr>
                        <th>Patient</th>
                        <th>Exam</th>
                        <th>Order Date</th>
                        <th>Authorised Date</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Readers Fee</th>
                        <th>Tax</th>
                        <th>Payor Discount</th>
                        <th>Payor Discount Amount</th>
                        <th>Special Discount</th>
                    </tr>
                </thead>
                <tbody>
                    ${rows}
                    <tr style="font-weight: bold; background-color: #f0f0f0;">
                        <td colspan="5" style="text-align: right;">Total</td>
                        <td>${formatCurrency(totalAmount)}</td>
                        <td>${formatCurrency(totalRf)}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            <br>
        `;

                container.innerHTML += table;
            }
            //}
        }

        function formatCurrency(amount, currency = 'PHP', locale = 'en-PH') {
                return new Intl.NumberFormat(locale, {
                    style: 'decimal',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(amount);
        }

        function getLocalDateOnly(dateString) {
            // Step 1: Remove any space before the timezone
            const cleaned = dateString.replace(' ', 'T').replace(' +', '+');

            // Step 2: Create a Date object
            const date = new Date(cleaned);

            // Step 3: Validate
            if (isNaN(date.getTime())) throw new Error('Invalid date: ' + cleaned);

            // Step 4: Return YYYY-MM-DD in local time
            return date.getFullYear() + '-' +
                String(date.getMonth() + 1).padStart(2, '0') + '-' +
                String(date.getDate()).padStart(2, '0');
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
            let value = (pd / amount) * 100;
            return value != 0 ? value.toFixed(2) + "%" : "";
        }

        function calculatePayorDiscountAmount(amount, pd) {
            let value = (pd / amount) * 100;
            let total = (value/100) * amount ;
            return total != 0 ? total.toFixed(2) : "";
        }

        function changeCat(selectElement) {
        const selectedValue = selectElement.value;
            if(selectedValue=='rad'){
                $("#modalityDiv").removeClass("d-none")
            }else{
                $("#modalityDiv").addClass("d-none")
            }
        }
    </script>
@endsection
