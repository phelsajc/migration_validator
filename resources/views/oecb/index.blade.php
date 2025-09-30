@extends('layouts.app')

@section('title', 'OECB Dashboard')
<link rel="stylesheet" href="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.css') }}">

@section('content')
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <h4 class="mt-3">Loading OECB Results...</h4>
            <p class="text-muted">Please wait while we fetch your data</p>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card">
                         <div class="card-header">
                 <h3 class="card-title">
                     OECB - Patient Order Items
                     @if(($viewType ?? 'patient_list') === 'oecb_results' && !empty($visitId))
                        <small class="text-light">for {{ $patientName ?? 'Unknown Patient8' }} (Visit ID:{{ $vvisitId ?? null }})</small>
                     @endif
                 </h3>
                 @if(($viewType ?? 'patient_list') === 'oecb_results' && !empty($visitId))
                     <div class="card-tools">
                         <button onclick="printSOA()" class="btn btn-sm btn-outline-light me-2">
                             <i class="fas fa-print"></i> Print SOA
                         </button>
                         <form method="POST" action="{{ route('oecb.csf') }}" style="display: inline;">
                             @csrf
                             <input type="hidden" name="visitId" value="{{ $visitId }}">
                             <button type="submit" class="btn btn-sm btn-outline-light me-2">
                                 <i class="fas fa-print"></i> Print CSF
                             </button>
                         </form>
                         <a href="{{ route('oecb.show') }}" class="btn btn-sm btn-outline-light">
                             <i class="fas fa-arrow-left"></i> Back to Patient List
                         </a>
                     </div>
                 @endif
             </div>
            <div class="card-body">
                <!-- Filter Form -->
                <form method="POST" action="{{ route('oecb.index') }}" class="mb-4">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="dateRange" class="form-label">Date Range</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="far fa-calendar"></i></span>
                                <input type="text" class="form-control" id="dateRange" name="dateRange"
                                    value="{{ $dateRange }}" placeholder="Select date range">
                            </div>
                        </div>
                        <!-- <div class="col-md-3">
                            <label for="mrn" class="form-label">MRN</label>
                            <input
                                type="text"
                                class="form-control"
                                id="mrn"
                                name="mrn"
                                value="$mrn"
                                placeholder="MRN"
                            >
                        </div> -->
                        
                        <input type="hidden" name="vvvisitId" value="{{ $vvisitId ?? null }}">
                        <div class="d-none col-md-3">
                            <label for="mrn" class="form-label">Visit ID</label>
                            <input type="text" class="form-control" id="mrn" name="mrn" value="{{ $visitId }}"
                                placeholder="Visit ID">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </div>
                </form>

                <!-- Export Form -->
                <form method="POST" action="{{ route('oecb.export') }}" class="mb-4">
                    @csrf
                    <input type="hidden" name="dateRange" value="{{ $dateRange }}">
                    <input type="hidden" name="billingGroup" value="{{ $billingGroup }}">
                    <input type="hidden" name="orgId" value="{{ $orgId }}">
                    <!-- <input type="hidden" name="mrn" value="$mrn"> -->
                    <input type="hidden" name="mrn" value="{{ $visitId }}">
                    <!-- <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-download"></i> Export to CSV
                            </button>
                        </div>
                    </div> -->
                </form>
                                 <!-- Results Table -->
                 <!-- Printable SOA Section -->
                 @if(($viewType ?? 'patient_list') === 'oecb_results' && !empty($visitId) && count($results) > 0)
                     <div id="printableSOA" class="printable-soa" style="display: none;">
                         <!-- SOA Header -->
                         <div class="soa-header">
                             <div class="soa-title-section">
                                 <h1 class="soa-title">STATEMENT OF ACCOUNT</h1>
                                <div class="soa-ref-no">SOA Reference No.:
                                    {{ $patientVisitDetails['soano'] ?? date('Ymd') . substr($visitId, -4) }}</div>
                             </div>
                             
                             <div class="account-info">
                                 <div class="account-name">RIVERSIDE MEDICAL CENTER INC.</div>
                                 <div class="account-address">BS Aquino Dr, Bacolod, 6100 Negros Occidental</div>
                             </div>
                             
                             <div class="patient-info">
                                 <div class="patient-details">
                                     <div class="info-row">
                                         <span class="label">Patient Name:</span>
                                        <span class="value"
                                            style="width: 400px;">{{ $patientVisitDetails['name'] ?? $patientName ?? 'Unknown Patient7' }}</span>
                                     </div>
                                     <div class="info-row">
                                         <span class="label">Age:</span>
                                         <span class="value" style="width: 40px;">{{ $patientVisitDetails['age'] ?? '-' }}</span>
                                     </div>
                                     <div class="info-row">
                                         <span class="label">Address:</span>
                                         <span class="value">{{ $patientVisitDetails['address'] ?? '-' }}</span>
                                     </div>
                                     <div class="info-row">
                                         <span class="label">Date and Time Admitted:</span>
                                        <span
                                            class="value">{{ $patientVisitDetails['admittedat'] ? date_format(date_create($patientVisitDetails['admittedat']), 'F d, Y') : ''}}</span>
                                     </div>
                                     <div class="info-row">
                                         <span class="label">Date and Time Discharged:</span>
                                        <span
                                            class="value">{{ $patientVisitDetails['dischargeat'] ? date_format(date_create($patientVisitDetails['dischargeat']), 'F d, Y') : ''}}</span>
                                     </div>
                                     <div class="info-row diagnosis-row">
                                         <span class="label">Final Diagnosis (ICD-10/RVS):</span>
                                         <!-- <span class="value"style="width: 300px;">{{ $patientVisitDetails['finalDiagnosis'] ?? '-' }}</span> -->
                                        <span class="value" style="width: 300px;">OPER1</span>
                                         </div>
                                     <div class="info-row diagnosis-row">
                                         <span class="label">Discharge DX:</span>
                                        <span class="value"
                                            style="width: 400px;">{{ $patientVisitDetails['otherDiagnosis'] ?? '-' }}</span>
                                     </div>
                                    <div class="info-row diagnosis">
                                        <span class="label" style="width:10px;">Payors:</span>
                                        <span class="value"
                                            style="width: 150px;">{{ $patientVisitDetails['payors'] ?? '-' }}</span>
                                    </div>
                                    <div class="info-row diagnosis">
                                        <span class="label" style="width:10px;">Care Provider:</span>
                                        <span class="value"
                                            style="width: 250px;">{{ $patientVisitDetails['doctor'] ?? '-' }}</span>
                                    </div>
                                 </div>
                             </div>
                         </div>
                         
                         <!-- Summary of Fees -->
                         <div class="soa-section">
                             <h3 class="section-title">Summary of Fees</h3>
                             <table class="soa-table summary-table">
                                 <thead>
                                     <tr>
                                         <th>Fee Particulars</th>
                                         <th>Amount</th>
                                         <th>Mandatory Discount</th>
                                         <th>Philhealth</th>
                                         <th>Other Funding Sources</th>
                                         <th>Balance</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @php
                                         $totalPriceSum = 0;
                                         $totalOECBSum = 0;
                                         $totalFinalSum = 0;
                                         
                                         $examTotalPriceSum = 0;
                                         $examTotalOECBSum = 0;
                                         $examTotalFinalSum = 0;
                                         
                                         $diagnosticsTotalPriceSum = 0;
                                         $diagnosticsTotalOECBSum = 0;
                                         $diagnosticsTotalFinalSum = 0;
                                         
                                        $imagingTotalPriceSum = 0;
                                        $imagingTotalOECBSum = 0;
                                        $imagingTotalFinalSum = 0;

                                        $totalBalanceFinalSum = 0;
                                        
                                        // Group imaging items by billing group and subgroup
                                        $imagingGroups = [];
                                         
                                         $edTotalPriceSum = 0;
                                         $edTotalOECBSum = 0;
                                         $edTotalFinalSum = 0;
                                         
                                         // Define laboratory and diagnostic billing sub groups
                                         $labSubGroups = [                                            
                                             //'cardiovascular',
                                             'blood bank',
                                             'chemistry',
                                             'cytopathology',
                                             'drug assay',
                                             'drug testing procedures',
                                             'hematology and coagulation',
                                             'immunology',
                                             'microbiology',
                                             'molecular pathology',
                                             'organ or disease-oriented panels',
                                             'other panels',
                                             'other pathology and laboratory procedures',
                                             'reproductive medicine',
                                             'send-out',
                                             //'special area procedure',
                                             'surgical pathology procedures',
                                             'therapeutic drug assays',
                                             'transfusion medicine',
                                             'urinalysis'
                                         ];

                                         $diagnosticsSubGroups = [                                     
                                            'cardiovascular',                                     
                                            'pulmonology',
                                         ];
                                         
                                         // Define imaging billing sub groups
                                         $imagingSubGroups = [
                                             'angiography',
                                             'ct',
                                             'cta',
                                             'fluoroscopy',
                                             'mammography',
                                             'mra',
                                             'mri',
                                             'ultrasound',
                                             'x-ray',
                                             'x-ray - other charges'
                                         ];
                                         
                                         // Initialize professional fees totals
                                         $professionalTotalPriceSum = 0;
                                         $professionalTotalOECBSum = 0;
                                         $professionalTotalFinalSum = 0;
                                         $professionalItems = [];
                                         
                                         // Calculate totals for items with billing group "Medicines"
                                        foreach ($results as $item) {

                                        
                                             $billingGroup = $item['billinggroups'] ?? '';
                                             $billingSubGroup = strtolower($item['billingsubgroups'] ?? '');
                                             $quantity = $item['quantity'] ?? 0;
                                             $unitPrice = $item['unitprice'] ?? 0;
                                             $oecbCode = $item['orderItemCode']['code'] ?? 0;
                                             $payorDiscount = $item['payordiscount'] ?? 0;
                                             $specialDiscount = $item['specialdiscount'] ?? 0;
                                             $status = $item['statusDesc'] ?? '';
                                             $totalPrice = ($quantity * $unitPrice);// - $payorDiscount - $specialDiscount;
                                             $oecbTotal = $oecbCode * $quantity;
                                             $final = $totalPrice - $oecbTotal - $payorDiscount - $specialDiscount;

                                             // Professional Fees calculation
                                             // Check for various possible professional fees billing group names
                                             if (strtolower($billingGroup) === 'professional fees' && strtolower($status) !== 'cancelled') {
                                                 $professionalTotalPriceSum += $totalPrice;
                                                 $professionalTotalOECBSum += $oecbTotal;
                                                 $professionalTotalFinalSum += $final;
                                                 
                                                 // Add to professional items array for detailed display
                                                 $professionalItems[] = $item;
                                             }
                                             
                                             // Filter: Only include items where orderItemCode code is not 0 or not empty
                                             if (empty($oecbCode) || $oecbCode == 0) {
                                                 continue;
                                             }
                                             
                                             if (strtolower($billingGroup) === 'medicines' && strtolower($status) !== 'cancelled') {
                                                 $totalPriceSum += $totalPrice;
                                                 $totalOECBSum += $oecbTotal;
                                                 $totalFinalSum += $final;
                                             }
                                             
                                            // Separate Lab and Diagnostics
                                            if (in_array($billingSubGroup, $labSubGroups) && strtolower($status) !== 'cancelled') {
                                                 $examTotalPriceSum += $totalPrice;
                                                 $examTotalOECBSum += $oecbTotal;
                                                 $examTotalFinalSum += $final;
                                             }
                                             
                                             if (in_array($billingSubGroup, $diagnosticsSubGroups) && strtolower($status) !== 'cancelled') {
                                                 $diagnosticsTotalPriceSum += $totalPrice;
                                                 $diagnosticsTotalOECBSum += $oecbTotal;
                                                 $diagnosticsTotalFinalSum += $final;
                                             }
                                             
                                            // Include only Examinations with specific imaging subgroups OR any billing group with Others subgroup
                                            $isExaminationsWithImagingSubgroup = (
                                                strtolower($billingGroup) === 'examinations' && 
                                                in_array(strtolower($billingSubGroup), ['angiography', 'ct', 'cta', 'fluoroscopy', 'mammography', 'mra', 'mri', 'ultrasound', 'x-ray', 'x-ray - other charges'])
                                            );
                                            
                                            $isOthersSubgroup = (
                                                strtolower($billingSubGroup) === 'others'
                                            );
                                            
                                            //if ($isExaminationsWithImagingSubgroup || $isOthersSubgroup) {
                                            if ($isExaminationsWithImagingSubgroup && strtolower($status) !== 'cancelled') {
                                                $imagingTotalPriceSum += $totalPrice;
                                                //if($oecbCode > 0) {
                                                    $imagingTotalOECBSum += $oecbTotal;
                                                //}
                                                $imagingTotalFinalSum += $final;
                                                
                                                // Group by billing group and subgroup
                                                $groupKey = $billingGroup . ' - ' . $billingSubGroup;
                                                if (!isset($imagingGroups[$groupKey])) {
                                                    $imagingGroups[$groupKey] = [
                                                        'billingGroup' => $billingGroup,
                                                        'billingSubGroup' => $billingSubGroup,
                                                        'totalPrice' => 0,
                                                        'totalOECB' => 0,
                                                        'totalFinal' => 0
                                                    ];
                                                }
                                                $imagingGroups[$groupKey]['totalPrice'] += $totalPrice;
                                                $imagingGroups[$groupKey]['totalOECB'] += $oecbTotal;
                                                $imagingGroups[$groupKey]['totalFinal'] += $final;
                                            }
                                            
                                            if ((strtolower($billingGroup) === 'other charges' || strtolower($billingGroup) === 'special area procedure' || strtolower($billingGroup) === 'supplies' || strtolower($billingGroup) === 'medical procedures') && strtolower($status) !== 'cancelled') {
                                                 $edTotalPriceSum += $totalPrice;
                                                 //if($oecbCode > 0) {
                                                    $edTotalOECBSum += $oecbTotal;
                                                 //}
                                                 $edTotalFinalSum += $final;
                                             }

                                             
                                             
                                             
                                             
                                        }
                                     @endphp

                                     
                                     @php
                                         $hasSCD = false;
                                         $hasPWD = false;
                                         $payorsArr = $patientVisitDetails['payorsArr'] ?? [];
                                         
                                         // Convert MongoDB object to array if needed
                                         if (is_object($payorsArr)) {
                                             if ($payorsArr instanceof \MongoDB\Model\BSONArray || $payorsArr instanceof \MongoDB\Model\BSONDocument) {
                                                 $payorsArr = iterator_to_array($payorsArr);
                                             } else {
                                                 $payorsArr = (array) $payorsArr;
                                             }
                                         }
                                         if (is_array($payorsArr) && !empty($payorsArr)) {
                                             foreach ($payorsArr as $payor) {
                                                 if (stripos($payor, 'OECB SCD') !== false) {
                                                     $hasSCD = true;
                                                 }
                                                 if (stripos($payor, 'OECB PWD') !== false || stripos($payor, 'PWD EM') !== false) {
                                                     $hasPWD = true;
                                                 }
                                             }
                                         } else {
                                             echo '<div style="color: red;">payorsArr is empty or not an array after conversion</div>';
                                         }
                                     @endphp
                                     <tr>
                                         <td>Room and Board</td>
                                         <td>-</td>
                                         <td>-</td>
                                         <td>-</td>
                                         <td>-</td>
                                         <td>-</td>
                                     </tr>
                                     @php
                                         $therapeuticsMandatoryDiscount = ($hasSCD || $hasPWD) ? $totalPriceSum * 0.2 : 0;
                                         $therapeuticsBalance = ($totalPriceSum - $therapeuticsMandatoryDiscount )- $totalOECBSum - 0;
                                         $therapeuticsBalance = $therapeuticsBalance < 0 ? 0 : $therapeuticsBalance;
                                     @endphp
                                     <tr>
                                         <td>Therapeutics</td>
                                         <td>{{ number_format($totalPriceSum, 2) }}</td>
                                        <td>@if($hasSCD || $hasPWD)({{ number_format($therapeuticsMandatoryDiscount, 2) }})@else(0.00)@endif
                                        </td>
                                         <td>({{ number_format($totalOECBSum, 2) }})</td>
                                         <td>(0.00)</td>
                                         <td>{{ number_format($therapeuticsBalance, 2) }}</td>
                                     </tr>
                                     @php
                                         $labMandatoryDiscount = ($hasSCD || $hasPWD) ? $examTotalPriceSum * 0.2 : 0;
                                         $labBalance = ($examTotalPriceSum - $labMandatoryDiscount )- $examTotalOECBSum - 0;
                                         $labBalance = $labBalance < 0 ? 0 : $labBalance;
                                     @endphp
                                     <tr>
                                         <td>Laboratory</td>
                                         <td>{{ number_format($examTotalPriceSum, 2) }}</td>
                                        <td>@if($hasSCD || $hasPWD)({{ number_format($labMandatoryDiscount, 2) }})@else(0.00)@endif
                                        </td>
                                         <td>({{ number_format($examTotalOECBSum, 2) }})</td>
                                         <td>(0.00)</td>
                                         <td>{{ number_format($labBalance, 2) }}</td>
                                     </tr>
                                     
                                     @php
                                         $diagnosticsMandatoryDiscount = ($hasSCD || $hasPWD) ? $diagnosticsTotalPriceSum * 0.2 : 0;
                                         $diagnosticsBalance = ($diagnosticsTotalPriceSum - $diagnosticsMandatoryDiscount) - $diagnosticsTotalOECBSum - 0;
                                         $diagnosticsBalance = $diagnosticsBalance < 0 ? 0 : $diagnosticsBalance;
                                     @endphp
                                     <tr>
                                         <td>Diagnostics</td>
                                         <td>{{ number_format($diagnosticsTotalPriceSum, 2) }}</td>
                                         <td>@if($hasSCD || $hasPWD)({{ number_format($diagnosticsMandatoryDiscount, 2) }})@else(0.00)@endif</td>
                                         <td>({{ number_format($diagnosticsTotalOECBSum, 2) }})</td>
                                         <td>(0.00)</td>
                                         <td>{{ number_format($diagnosticsBalance, 2) }}</td>
                                     </tr>
                                    @php
                                        $imagingMandatoryDiscount = ($hasSCD || $hasPWD) ? $imagingTotalPriceSum * 0.2 : 0;
                                        $imagingBalance = ($imagingTotalPriceSum - $imagingMandatoryDiscount) - $imagingTotalOECBSum - 0;
                                        $imagingBalance = $imagingBalance < 0 ? 0 : $imagingBalance;
                                    @endphp
                                    <tr>
                                        <td>Imaging</td>
                                        <td>{{ number_format($imagingTotalPriceSum, 2) }}</td>
                                        <td>@if($hasSCD || $hasPWD)({{ number_format($imagingMandatoryDiscount, 2) }})@else(0.00)@endif
                                        </td>
                                        <td>({{ number_format($imagingTotalOECBSum, 2) }})</td>
                                        <td>(0.00)</td>
                                        <td>{{ number_format($imagingBalance, 2) }}</td>
                                    </tr>
                                     @php
                                         $edMandatoryDiscount = ($hasSCD || $hasPWD) ? $edTotalPriceSum * 0.2 : 0;
                                         $edBalance = ($edTotalPriceSum - $edMandatoryDiscount) - $edTotalOECBSum - 0;
                                         $edBalance = $edBalance < 0 ? 0 : $edBalance;
                                     @endphp
                                     <tr>
                                         <td>ED Services</td>
                                         <td>{{ number_format($edTotalPriceSum, 2) }}</td>
                                        <td>@if($hasSCD || $hasPWD)({{ number_format($edMandatoryDiscount, 2) }})@else(0.00)@endif
                                        </td>
                                         <td>({{ number_format($edTotalOECBSum, 2) }})</td>
                                         <td>(0.00)</td>
                                         <td>{{ number_format($edBalance, 2) }}</td>
                                     </tr>
                                     @php
                                         $grandTotal = $totalPriceSum + $examTotalPriceSum + $diagnosticsTotalPriceSum + $imagingTotalPriceSum + $edTotalPriceSum;// + $professionalTotalPriceSum;
                                         $mandatoryDiscount = ($hasSCD || $hasPWD) ? $grandTotal * 0.2 : 0;
                                         $totalOECB = $totalOECBSum + $examTotalOECBSum + $diagnosticsTotalOECBSum + $imagingTotalOECBSum + $edTotalOECBSum;// + $professionalTotalOECBSum;
                                         $totalBalance = $therapeuticsBalance + $labBalance + $diagnosticsBalance + $imagingBalance + $edBalance;
                                     @endphp
                                     <tr class="total-row">
                                         <td><strong>Total</strong></td>
                                         <td><strong>{{ number_format($grandTotal, 2) }}</strong></td>
                                        <td><strong>@if($hasSCD || $hasPWD)({{ number_format($mandatoryDiscount, 2) }})@else(0.00)@endif</strong>
                                        </td>
                                         <td><strong>({{ number_format($totalOECB, 2) }})</strong></td>
                                         <td><strong>(0.00)</strong></td>
                                         <td><strong>{{ number_format($totalBalance, 2) }}</strong></td>
                                     </tr>
                                 </tbody>
                             </table>
                         </div>
                         
                         <!-- Summary of Fees Non OECB -->
                         <div class="soa-section">
                             <h3 class="section-title">Summary of Fees Non OECB</h3>
                             <table class="soa-table summary-table">
                                 <thead>
                                     <tr>
                                         <th>Fee Particulars</th>
                                         <th>Amount</th>
                                         <th>Mandatory Discount</th>
                                         <th>Philhealth</th>
                                         <th>Other Funding Sources</th>
                                         <th>Balance</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @php
                                         // Calculate totals for items WITHOUT orderItemCode (Non OECB)
                                         $nonOECBTotalPriceSum = 0;
                                         $nonOECBTotalOECBSum = 0;
                                         $nonOECBTotalFinalSum = 0;
                                         
                                         $nonOECBExamTotalPriceSum = 0;
                                         $nonOECBExamTotalOECBSum = 0;
                                         $nonOECBExamTotalFinalSum = 0;
                                         
                                         $nonOECBDiagnosticsTotalPriceSum = 0;
                                         $nonOECBDiagnosticsTotalOECBSum = 0;
                                         $nonOECBDiagnosticsTotalFinalSum = 0;
                                         
                                         $nonOECBImagingTotalPriceSum = 0;
                                         $nonOECBImagingTotalOECBSum = 0;
                                         $nonOECBImagingTotalFinalSum = 0;

                                         $nonOECBTotalBalanceFinalSum = 0;
                                         
                                         $nonOECBEdTotalPriceSum = 0;
                                         $nonOECBEdTotalOECBSum = 0;
                                         $nonOECBEdTotalFinalSum = 0;
                                         
                                         // Calculate totals for items WITHOUT orderItemCode
                                         foreach ($results as $item) {
                                             $billingGroup = $item['billinggroups'] ?? '';
                                             $billingSubGroup = strtolower($item['billingsubgroups'] ?? '');
                                             $quantity = $item['quantity'] ?? 0;
                                             $unitPrice = $item['unitprice'] ?? 0;
                                             $oecbCode = $item['orderItemCode']['code'] ?? 0;
                                             $payorDiscount = $item['payordiscount'] ?? 0;
                                             $specialDiscount = $item['specialdiscount'] ?? 0;
                                             $status = $item['statusDesc'] ?? '';
                                             $totalPrice = ($quantity * $unitPrice);
                                             $oecbTotal = $oecbCode * $quantity;
                                             $final = $totalPrice - $oecbTotal - $payorDiscount - $specialDiscount;
                                             
                                             // Filter: Only include items WITHOUT orderItemCode (empty or 0)
                                             if (!empty($oecbCode) && $oecbCode != 0) {
                                                 continue;
                                             }
                                             
                                             if (strtolower($billingGroup) === 'medicines' && strtolower($status) !== 'cancelled') {
                                                 $nonOECBTotalPriceSum += $totalPrice;
                                                 $nonOECBTotalOECBSum += $oecbTotal;
                                                 $nonOECBTotalFinalSum += $final;
                                             }
                                             
                                             if (in_array($billingSubGroup, $labSubGroups) && strtolower($status) !== 'cancelled') {
                                                 $nonOECBExamTotalPriceSum += $totalPrice;
                                                 $nonOECBExamTotalOECBSum += $oecbTotal;
                                                 $nonOECBExamTotalFinalSum += $final;
                                             }
                                             
                                             if (in_array($billingSubGroup, $diagnosticsSubGroups) && strtolower($status) !== 'cancelled') {
                                                 $nonOECBDiagnosticsTotalPriceSum += $totalPrice;
                                                 $nonOECBDiagnosticsTotalOECBSum += $oecbTotal;
                                                 $nonOECBDiagnosticsTotalFinalSum += $final;
                                             }
                                             
                                             // Include only Examinations with specific imaging subgroups
                                             $isExaminationsWithImagingSubgroup = (
                                                 strtolower($billingGroup) === 'examinations' && 
                                                 in_array(strtolower($billingSubGroup), ['angiography', 'ct', 'cta', 'fluoroscopy', 'mammography', 'mra', 'mri', 'ultrasound', 'x-ray', 'x-ray - other charges'])
                                             );
                                             
                                             if ($isExaminationsWithImagingSubgroup && strtolower($status) !== 'cancelled') {
                                                 $nonOECBImagingTotalPriceSum += $totalPrice;
                                                 $nonOECBImagingTotalOECBSum += $oecbTotal;
                                                 $nonOECBImagingTotalFinalSum += $final;
                                             }
                                             
                                             if ((strtolower($billingGroup) === 'other charges' || strtolower($billingGroup) === 'special area procedure' || strtolower($billingGroup) === 'supplies' || strtolower($billingGroup) === 'medical procedures') && strtolower($status) !== 'cancelled') {
                                                 $nonOECBEdTotalPriceSum += $totalPrice;
                                                 $nonOECBEdTotalOECBSum += $oecbTotal;
                                                 $nonOECBEdTotalFinalSum += $final;
                                             }
                                         }
                                     @endphp
                                     
                                     @php
                                         $nonOECBTherapeuticsMandatoryDiscount = ($hasSCD || $hasPWD) ? $nonOECBTotalPriceSum * 0.2 : 0;
                                         $nonOECBTherapeuticsBalance = ($nonOECBTotalPriceSum - $nonOECBTherapeuticsMandatoryDiscount) - $nonOECBTotalOECBSum - 0;
                                         $nonOECBTherapeuticsBalance = $nonOECBTherapeuticsBalance < 0 ? 0 : $nonOECBTherapeuticsBalance;
                                     @endphp
                                     <tr>
                                         <td>Medicines</td>
                                         <td>{{ number_format($nonOECBTotalPriceSum, 2) }}</td>
                                         <td>@if($hasSCD || $hasPWD)({{ number_format($nonOECBTherapeuticsMandatoryDiscount, 2) }})@else(0.00)@endif</td>
                                         <td>({{ number_format($nonOECBTotalOECBSum, 2) }})</td>
                                         <td>(0.00)</td>
                                         <td>{{ number_format($nonOECBTherapeuticsBalance, 2) }}</td>
                                     </tr>
                                     
                                     @php
                                         $nonOECBLabMandatoryDiscount = ($hasSCD || $hasPWD) ? $nonOECBExamTotalPriceSum * 0.2 : 0;
                                         $nonOECBLabBalance = ($nonOECBExamTotalPriceSum - $nonOECBLabMandatoryDiscount) - $nonOECBExamTotalOECBSum - 0;
                                         $nonOECBLabBalance = $nonOECBLabBalance < 0 ? 0 : $nonOECBLabBalance;
                                     @endphp
                                     <tr>
                                         <td>Laboratory</td>
                                         <td>{{ number_format($nonOECBExamTotalPriceSum, 2) }}</td>
                                         <td>@if($hasSCD || $hasPWD)({{ number_format($nonOECBLabMandatoryDiscount, 2) }})@else(0.00)@endif</td>
                                         <td>({{ number_format($nonOECBExamTotalOECBSum, 2) }})</td>
                                         <td>(0.00)</td>
                                         <td>{{ number_format($nonOECBLabBalance, 2) }}</td>
                                     </tr>
                                     
                                     @php
                                         $nonOECBDiagnosticsMandatoryDiscount = ($hasSCD || $hasPWD) ? $nonOECBDiagnosticsTotalPriceSum * 0.2 : 0;
                                         $nonOECBDiagnosticsBalance = ($nonOECBDiagnosticsTotalPriceSum - $nonOECBDiagnosticsMandatoryDiscount) - $nonOECBDiagnosticsTotalOECBSum - 0;
                                         $nonOECBDiagnosticsBalance = $nonOECBDiagnosticsBalance < 0 ? 0 : $nonOECBDiagnosticsBalance;
                                     @endphp
                                     <tr>
                                         <td>Diagnostics</td>
                                         <td>{{ number_format($nonOECBDiagnosticsTotalPriceSum, 2) }}</td>
                                         <td>@if($hasSCD || $hasPWD)({{ number_format($nonOECBDiagnosticsMandatoryDiscount, 2) }})@else(0.00)@endif</td>
                                         <td>({{ number_format($nonOECBDiagnosticsTotalOECBSum, 2) }})</td>
                                         <td>(0.00)</td>
                                         <td>{{ number_format($nonOECBDiagnosticsBalance, 2) }}</td>
                                     </tr>
                                     
                                     @php
                                         $nonOECBImagingMandatoryDiscount = ($hasSCD || $hasPWD) ? $nonOECBImagingTotalPriceSum * 0.2 : 0;
                                         $nonOECBImagingBalance = ($nonOECBImagingTotalPriceSum - $nonOECBImagingMandatoryDiscount) - $nonOECBImagingTotalOECBSum - 0;
                                         $nonOECBImagingBalance = $nonOECBImagingBalance < 0 ? 0 : $nonOECBImagingBalance;
                                     @endphp
                                     <tr>
                                         <td>Imaging</td>
                                         <td>{{ number_format($nonOECBImagingTotalPriceSum, 2) }}</td>
                                         <td>@if($hasSCD || $hasPWD)({{ number_format($nonOECBImagingMandatoryDiscount, 2) }})@else(0.00)@endif</td>
                                         <td>({{ number_format($nonOECBImagingTotalOECBSum, 2) }})</td>
                                         <td>(0.00)</td>
                                         <td>{{ number_format($nonOECBImagingBalance, 2) }}</td>
                                     </tr>
                                     
                                     @php
                                         $nonOECBEdMandatoryDiscount = ($hasSCD || $hasPWD) ? $nonOECBEdTotalPriceSum * 0.2 : 0;
                                         $nonOECBEdBalance = ($nonOECBEdTotalPriceSum - $nonOECBEdMandatoryDiscount) - $nonOECBEdTotalOECBSum - 0;
                                         $nonOECBEdBalance = $nonOECBEdBalance < 0 ? 0 : $nonOECBEdBalance;
                                     @endphp
                                     <tr>
                                         <td>ED Services</td>
                                         <td>{{ number_format($nonOECBEdTotalPriceSum, 2) }}</td>
                                         <td>@if($hasSCD || $hasPWD)({{ number_format($nonOECBEdMandatoryDiscount, 2) }})@else(0.00)@endif</td>
                                         <td>({{ number_format($nonOECBEdTotalOECBSum, 2) }})</td>
                                         <td>(0.00)</td>
                                         <td>{{ number_format($nonOECBEdBalance, 2) }}</td>
                                     </tr>
                                     
                                     @php
                                         $nonOECBGrandTotal = $nonOECBTotalPriceSum + $nonOECBExamTotalPriceSum + $nonOECBDiagnosticsTotalPriceSum + $nonOECBImagingTotalPriceSum + $nonOECBEdTotalPriceSum;
                                         $nonOECBMandatoryDiscount = ($hasSCD || $hasPWD) ? $nonOECBGrandTotal * 0.2 : 0;
                                         $nonOECBTotalOECB = $nonOECBTotalOECBSum + $nonOECBExamTotalOECBSum + $nonOECBDiagnosticsTotalOECBSum + $nonOECBImagingTotalOECBSum + $nonOECBEdTotalOECBSum;
                                         $nonOECBTotalBalance = $nonOECBTherapeuticsBalance + $nonOECBLabBalance + $nonOECBDiagnosticsBalance + $nonOECBImagingBalance + $nonOECBEdBalance;
                                     @endphp
                                     <tr class="total-row">
                                         <td><strong>Total</strong></td>
                                         <td><strong>{{ number_format($nonOECBGrandTotal, 2) }}</strong></td>
                                         <td><strong>@if($hasSCD || $hasPWD)({{ number_format($nonOECBMandatoryDiscount, 2) }})@else(0.00)@endif</strong></td>
                                         <td><strong>({{ number_format($nonOECBTotalOECB, 2) }})</strong></td>
                                         <td><strong>(0.00)</strong></td>
                                         <td><strong>{{ number_format($nonOECBTotalBalance, 2) }}</strong></td>
                                     </tr>
                                 </tbody>
                             </table>
                         </div>
                         
                         <!-- Professional Fees -->
                         <div class="soa-section">
                             <h3 class="section-title">Professional Fees</h3>
                             <table class="soa-table professional-table">
                                 <thead>
                                     <tr>
                                         <th>Date</th>
                                         <th>Service Description</th>
                                         <th>Amount</th>
                                         <th>Mandatory Discount</th>
                                         <th>Philhealth</th>
                                         <th>Other Funding Sources</th>
                                         <th>Balance</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @if(!empty($professionalItems))
                                         @foreach($professionalItems as $item)
                                             @php
                                                 $quantity = $item['quantity'] ?? 0;
                                                 $unitPrice = $item['unitprice'] ?? 0;
                                                 $oecbCode = $item['orderItemCode']['code'] ?? 0;
                                                 $payorDiscount = $item['payordiscount'] ?? 0;
                                                 $specialDiscount = $item['specialdiscount'] ?? 0;
                                                 $totalPrice = ($quantity * $unitPrice) - $payorDiscount - $specialDiscount;
                                                 $oecbTotal = $oecbCode * $quantity;
                                                 
                                                 // Calculate mandatory discount (20% for SCD/PWD)
                                                 $mandatoryDiscount = ($hasSCD || $hasPWD) ? $totalPrice * 0.2 : 0;
                                                 $final = $totalPrice - $mandatoryDiscount - $oecbTotal;
                                             @endphp
                                             <tr>
                                                 <td>{{ date_format(date_create($item['orderdate']), 'm-d-Y') ?? 'N/A' }}</td>
                                                 <td>{{ $item['orderitemname'] ?? 'N/A' }}</td>
                                                 <td>{{ number_format($totalPrice, 2) }}</td>
                                                 <td>@if($hasSCD || $hasPWD)({{ number_format($mandatoryDiscount, 2) }})@else(0.00)@endif</td>
                                                 <td>({{ number_format($oecbTotal, 2) }})</td>
                                                 <td>(0.00)</td>
                                                 <td>{{ number_format($final < 0 ? 0 : $final, 2) }}</td>
                                             </tr>
                                         @endforeach
                                         @php
                                             $professionalMandatoryDiscount = ($hasSCD || $hasPWD) ? $professionalTotalPriceSum * 0.2 : 0;
                                             $professionalBalance = ($professionalTotalPriceSum - $professionalMandatoryDiscount) - $professionalTotalOECBSum - 0;
                                             $professionalBalance = $professionalBalance < 0 ? 0 : $professionalBalance;
                                         @endphp
                                         <tr class="total-row">
                                             <td colspan="2"><strong>Total Professional Fees</strong></td>
                                             <td><strong>{{ number_format($professionalTotalPriceSum, 2) }}</strong></td>
                                             <td><strong>@if($hasSCD || $hasPWD)({{ number_format($professionalMandatoryDiscount, 2) }})@else(0.00)@endif</strong></td>
                                             <td><strong>({{ number_format($professionalTotalOECBSum, 2) }})</strong></td>
                                             <td><strong>(0.00)</strong></td>
                                             <td><strong>{{ number_format($professionalBalance, 2) }}</strong></td>
                                         </tr>
                                     @else
                                         <tr>
                                             <td colspan="7" class="no-data">No professional fees data available</td>
                                         </tr>
                                     @endif
                                 </tbody>
                             </table>
                        </div>
                        
                        @if(($viewType ?? 'patient_list') === 'oecb_results')
                        <!-- Itemized Charges -->
                         <div class="soa-section">
                             <h3 class="section-title">Itemized Charges</h3>
                             <div class="itemized-controls mb-3 no-print">
                                 <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleItemizedView()">
                                     <i class="fas fa-list"></i> Show All Items
                                 </button>
                                 <button type="button" class="btn btn-sm btn-outline-secondary" onclick="showItemizedSummary()">
                                     <i class="fas fa-chart-bar"></i> Show Summary Only
                                 </button>
                             </div>
                             <div id="itemizedTable" class="itemized-table-container">
                             <table class="soa-table itemized-table">
                                 <thead>
                                     <tr>
                                         <th>Service Date</th>
                                         <th>Item No.</th>
                                         <th>Item Name</th>
                                         <th>Price</th>
                                         <th>Senior Discount / PWD</th>
                                         <th>Unit of Measurement</th>
                                         <th>PhilHealth</th>
                                         <th>Quantity</th>
                                         <th>Balance</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @php
                                         $itemizedTotalPrice = 0;
                                         $itemizedTotalOECB = 0;
                                         $itemizedTotalFinal = 0;
                                         $itemizedTotalSeniorDiscount = 0;
                                         
                                         // Check for SCD/PWD status for itemized charges
                                         $hasSCD = false;
                                         $hasPWD = false;
                                         $payorsArr = $patientVisitDetails['payorsArr'] ?? [];
                                         
                                         // Convert MongoDB object to array if needed
                                         if (is_object($payorsArr)) {
                                             if ($payorsArr instanceof \MongoDB\Model\BSONArray || $payorsArr instanceof \MongoDB\Model\BSONDocument) {
                                                 $payorsArr = iterator_to_array($payorsArr);
                                             } else {
                                                 $payorsArr = (array) $payorsArr;
                                             }
                                         }
                                         
                                         if (is_array($payorsArr) && !empty($payorsArr)) {
                                             foreach ($payorsArr as $payor) {
                                                 if (stripos($payor, 'OECB SCD') !== false) {
                                                     $hasSCD = true;
                                                 }
                                                 if (stripos($payor, 'OECB PWD') !== false || stripos($payor, 'PWD EM') !== false) {
                                                     $hasPWD = true;
                                                 }
                                             }
                                         }
                                     @endphp
                                     @foreach($results as $item)
                                         @php
                                             $quantity = $item['quantity'] ?? 0;
                                             $unitPrice = $item['unitprice'] ?? 0;
                                             $oecbCode = $item['orderItemCode']['code'] ?? 0;
                                             $oecbNo = $item['orderItemNo']['code'] ?? 0;
                                             $payorDiscount = $item['payordiscount'] ?? 0;
                                             $specialDiscount = $item['specialdiscount'] ?? 0;
                                             $status = $item['statusDesc'] ?? '';
                                             $totalPrice = ($quantity * $unitPrice);// - $payorDiscount - $specialDiscount;
                                             $oecbTotal = $oecbCode * $quantity;
                                             
                                             // Calculate senior discount (20% of total price)
                                             $seniorDiscount = ($hasSCD || $hasPWD) ? $totalPrice * 0.2 : 0;
                                             
                                             if (strtolower($status) !== 'cancelled') {
                                                 $itemizedTotalPrice += $totalPrice;
                                                 $itemizedTotalOECB += $oecbTotal;
                                                 $itemizedTotalSeniorDiscount += $seniorDiscount;
                                                 $final = $totalPrice - $oecbTotal - $seniorDiscount;
                                                        $final = $final > 0 ? $totalPrice - $oecbTotal - $seniorDiscount : 0;
                                                 $itemizedTotalFinal += $final;
                                             }
                                         @endphp
                                         @if (strtolower($status) != 'cancelled')
                                                <tr @if(strtolower($status) === 'cancelled') class="table-secondary" style="opacity: 0.6;"
                                                @endif>
                                             <!-- <td>{{ $dateRange ? explode(' - ', $dateRange)[0] : date('m-d-Y') }}</td> -->
                                             <td>{{ date_format(date_create($item['orderdate']), 'm-d-Y') ?? 'N/A' }}</td>
                                             <td>{{ $oecbNo }}</td>
                                                    <td>{{ $item['orderitemname'] ?? 'N/A' }} @if(strtolower($status) === 'cancelled')
                                                    <span class="badge badge-danger">Cancelled</span> @endif</td>
                                             <td>{{ number_format($totalPrice, 2) }}</td>
                                             <td>@if($hasSCD || $hasPWD){{ number_format($seniorDiscount, 2) }}@endif</td>
                                             <td>{{ $item['uom'] ?? 'N/A' }}</td>
                                             <td>{{ $oecbCode }}</td>
                                             <td>{{ $quantity }}</td>
                                             <td>{{ number_format($final, 2) }}</td>
                                         </tr>
                                         
                                         @endif
                                     @endforeach
                                     <tr class="total-row">
                                         <td colspan="3"><strong>Total</strong></td>
                                         <td><strong>{{ number_format($itemizedTotalPrice, 2) }}</strong></td>
                                                <td><strong>@if($hasSCD || $hasPWD){{ number_format($itemizedTotalSeniorDiscount, 2) }}@endif</strong>
                                                </td>
                                         <td></td>
                                         <td><strong>{{ number_format($itemizedTotalOECB, 2) }}</strong></td>
                                         <td></td>
                                         <td><strong>{{ $itemizedTotalFinal}}</strong></td>
                                     </tr>
                                 </tbody>
                            </table>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Footer with Signatures -->
                         <div class="soa-footer">
                             <div class="signature-section">
                                 <div class="signature-block">
                                     <h4>Prepared by:</h4>
                                     <div class="signature-line">Billing Clerk / Accountant</div>
                                     <div class="signature-space">{{ $patientVisitDetails['modifiedby'] ?? '-' }}</div>
                                     <div class="signature-line">Date Signed: _________________</div>
                                     <div class="signature-line">Contact No.: _________________</div>
                                 </div>
                                 
                                 <div class="signature-block">
                                     <h4>Conforme:</h4>
                                     <div class="signature-line">Patient / Representative</div>
                                     <div class="signature-space">{{ $patientVisitDetails['name'] ?? $patientName ?? '-' }}</div>
                                    <div class="signature-line">Relationship of representative to patient: _________________
                                    </div>
                                     <div class="signature-line">Date Signed: _________________</div>
                                     <div class="signature-line">Contact No.: _________________</div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 @endif

                 <!-- Printable CSF Section -->
                 @if(($viewType ?? 'patient_list') === 'oecb_results' && !empty($visitId) && count($results) > 0)
                     <div id="printableCSF" class="printable-csf" style="display: none;">
                         <!-- CSF Header -->
                         <div class="csf-header">
                             <div class="csf-logo-section">
                                 <div class="csf-logo">
                                     <strong>PhilHealth</strong><br>
                                     <small>Your Partner in Health.</small>
                                 </div>
                             </div>
                             
                             <div class="csf-title-section">
                                 <div class="csf-title">Republic of the Philippines</div>
                                 <div class="csf-subtitle">PHILIPPINE HEALTH INSURANCE CORPORATION</div>
                                 <div class="csf-address">
                                     Citystate Centre 709 Shaw Boulevard, Pasig City<br>
                                     Call Center (02) 441-7442 • Trunkline (02) 441-7444<br>
                                     www.philhealth.gov.ph<br>
                                     email: actioncenter@philhealth.gov.ph
                                 </div>
                             </div>
                             
                             <div class="csf-form-info">
                                 <div class="csf-notice">This form may be reproduced and is NOT FOR SALE</div>
                                 <div class="csf-form-title">CSF</div>
                                 <div class="csf-form-subtitle">(Claim Signature Form)</div>
                                 <div class="csf-revision">Revised September 2018</div>
                                 <div class="csf-series">
                                     <label>Series #:</label>
                                     <div class="series-boxes">
                                         <input type="text" maxlength="1" class="series-box">
                                         <input type="text" maxlength="1" class="series-box">
                                         <input type="text" maxlength="1" class="series-box">
                                         <input type="text" maxlength="1" class="series-box">
                                         <input type="text" maxlength="1" class="series-box">
                                         <input type="text" maxlength="1" class="series-box">
                                         <input type="text" maxlength="1" class="series-box">
                                         <input type="text" maxlength="1" class="series-box">
                                         <input type="text" maxlength="1" class="series-box">
                                         <input type="text" maxlength="1" class="series-box">
                                         <input type="text" maxlength="1" class="series-box">
                                         <input type="text" maxlength="1" class="series-box">
                                     </div>
                                 </div>
                             </div>
                         </div>

                         <!-- Important Reminders -->
                         <div class="csf-reminders">
                             <strong>IMPORTANT REMINDERS:</strong>
                             <ul>
                                 <li>PLEASE WRITE IN CAPITAL LETTERS AND CHECK THE APPROPRIATE BOXES.</li>
                                <li>All information required in this form are necessary. Claim forms with incomplete information
                                    shall not be processed.</li>
                                <li>FALSE/INCORRECT INFORMATION OR MISREPRESENTATION SHALL BE SUBJECT TO CRIMINAL, CIVIL OR
                                    ADMINISTRATIVE LIABILITIES.</li>
                             </ul>
                         </div>

                         <!-- PART I - MEMBER AND PATIENT INFORMATION -->
                         <div class="csf-section">
                             <h3>PART I - MEMBER AND PATIENT INFORMATION AND CERTIFICATION</h3>
                             
                             <div class="csf-field-group">
                                 <label>1. PhilHealth Identification Number (PIN) of Member:</label>
                                 <div class="pin-boxes">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                 </div>
                             </div>

                             <div class="csf-field-group">
                                 <label>2. Name of Member:</label>
                                 <div class="name-fields">
                                     <div class="name-field">
                                         <label>Last Name:</label>
                                         <input type="text" class="name-input">
                                     </div>
                                     <div class="name-field">
                                         <label>First Name:</label>
                                         <input type="text" class="name-input">
                                     </div>
                                     <div class="name-field">
                                         <label>Name Extension (JR/SR/II):</label>
                                         <input type="text" class="name-input">
                                     </div>
                                     <div class="name-field">
                                         <label>Middle Name:</label>
                                         <input type="text" class="name-input">
                                     </div>
                                 </div>
                                 <div class="name-example">ex: DELA CRUZ JUAN JR SIPAG</div>
                             </div>

                             <div class="csf-field-group">
                                 <label>3. Member Date of Birth:</label>
                                 <div class="date-fields">
                                     <div class="date-field">
                                         <label>month</label>
                                         <input type="text" class="date-input">
                                     </div>
                                     <div class="date-field">
                                         <label>day</label>
                                         <input type="text" class="date-input">
                                     </div>
                                     <div class="date-field">
                                         <label>year</label>
                                         <input type="text" class="date-input">
                                     </div>
                                 </div>
                             </div>

                             <div class="csf-field-group">
                                 <label>4. PhilHealth Identification Number (PIN) of Dependent:</label>
                                 <div class="pin-boxes">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                     <input type="text" maxlength="1" class="pin-box">
                                 </div>
                             </div>

                             <div class="csf-field-group">
                                 <label>5. Name of Patient:</label>
                                 <div class="name-fields">
                                     <div class="name-field">
                                         <label>Last Name:</label>
                                         <input type="text" class="name-input">
                                     </div>
                                     <div class="name-field">
                                         <label>First Name:</label>
                                         <input type="text" class="name-input">
                                     </div>
                                     <div class="name-field">
                                         <label>Name Extension (JR/SR/III):</label>
                                         <input type="text" class="name-input">
                                     </div>
                                     <div class="name-field">
                                         <label>Middle Name:</label>
                                         <input type="text" class="name-input">
                                     </div>
                                 </div>
                                 <div class="name-example">ex: DELA CRUZ JUAN JR SIPAG</div>
                             </div>

                             <div class="csf-field-group">
                                 <label>6. Relationship to Member:</label>
                                 <div class="checkbox-group">
                                     <label><input type="checkbox"> child</label>
                                     <label><input type="checkbox"> parent</label>
                                     <label><input type="checkbox"> spouse</label>
                                 </div>
                             </div>

                             <div class="csf-field-group">
                                 <label>7. Confinement Period:</label>
                                 <div class="confinement-period">
                                     <div class="confinement-field">
                                         <label>a. Date Admitted:</label>
                                         <div class="date-fields">
                                             <div class="date-field">
                                                 <label>month</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>day</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>year</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                         </div>
                                     </div>
                                     <div class="confinement-field">
                                         <label>b. Date Discharged:</label>
                                         <div class="date-fields">
                                             <div class="date-field">
                                                 <label>month</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>day</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>year</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>

                             <div class="csf-field-group">
                                 <label>8. Patient Date of Birth:</label>
                                 <div class="date-fields">
                                     <div class="date-field">
                                         <label>month</label>
                                         <input type="text" class="date-input">
                                     </div>
                                     <div class="date-field">
                                         <label>day</label>
                                         <input type="text" class="date-input">
                                     </div>
                                     <div class="date-field">
                                         <label>year</label>
                                         <input type="text" class="date-input">
                                     </div>
                                 </div>
                             </div>

                             <div class="csf-field-group">
                                 <label>9. CERTIFICATION OF MEMBER:</label>
                                 <div class="certification-text">
                                    Under the penalty of law, I attest that the information I provided in this Form are true and
                                    accurate to the best of my knowledge.
                                 </div>
                                 
                                 <div class="signature-fields">
                                     <div class="signature-field">
                                         <label>Signature Over Printed Name of Member:</label>
                                         <div class="signature-line"></div>
                                         <div class="printed-name-line"></div>
                                     </div>
                                     <div class="signature-field">
                                         <label>Date Signed:</label>
                                         <div class="date-fields">
                                             <div class="date-field">
                                                 <label>month</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>day</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>year</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                         </div>
                                     </div>
                                 </div>

                                 <div class="signature-fields">
                                     <div class="signature-field">
                                         <label>Signature Over Printed Name of Member's Representative:</label>
                                         <div class="signature-line"></div>
                                         <div class="printed-name-line"></div>
                                     </div>
                                     <div class="signature-field">
                                         <label>Date Signed:</label>
                                         <div class="date-fields">
                                             <div class="date-field">
                                                 <label>month</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>day</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>year</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                         </div>
                                     </div>
                                 </div>

                                 <div class="thumbmark-section">
                                    <div class="thumbmark-text">If member/representative is unable to write, put right
                                        thumbmark. Member/Representative should be assisted by an HCI representative. Check the
                                        appropriate box.</div>
                                     <div class="checkbox-group">
                                         <label><input type="checkbox"> Member</label>
                                         <label><input type="checkbox"> Representative</label>
                                     </div>
                                 </div>

                                 <div class="relationship-section">
                                     <label>Relationship of the representative to the member:</label>
                                     <div class="checkbox-group">
                                         <label><input type="checkbox"> Spouse</label>
                                         <label><input type="checkbox"> Child</label>
                                         <label><input type="checkbox"> Parent</label>
                                         <label><input type="checkbox"> Sibling</label>
                                         <label><input type="checkbox"> Others, Specify:</label>
                                         <input type="text" class="others-input">
                                     </div>
                                 </div>

                                 <div class="reason-section">
                                     <label>Reason for signing on behalf of the member:</label>
                                     <div class="checkbox-group">
                                         <label><input type="checkbox"> Member is incapacitated</label>
                                         <label><input type="checkbox"> Other reasons:</label>
                                         <input type="text" class="reason-input">
                                     </div>
                                 </div>
                             </div>
                         </div>

                         <!-- PART II - EMPLOYER'S CERTIFICATION -->
                         <div class="csf-section">
                             <h3>PART II - EMPLOYER'S CERTIFICATION (for employed members only)</h3>
                             
                             <div class="csf-field-group">
                                 <label>1. PhilHealth Employer Number (PEN):</label>
                                 <div class="pen-boxes">
                                     <input type="text" maxlength="1" class="pen-box">
                                     <input type="text" maxlength="1" class="pen-box">
                                     <input type="text" maxlength="1" class="pen-box">
                                     <input type="text" maxlength="1" class="pen-box">
                                     <input type="text" maxlength="1" class="pen-box">
                                     <input type="text" maxlength="1" class="pen-box">
                                     <input type="text" maxlength="1" class="pen-box">
                                     <input type="text" maxlength="1" class="pen-box">
                                     <input type="text" maxlength="1" class="pen-box">
                                     <input type="text" maxlength="1" class="pen-box">
                                     <input type="text" maxlength="1" class="pen-box">
                                     <input type="text" maxlength="1" class="pen-box">
                                 </div>
                             </div>

                             <div class="csf-field-group">
                                 <label>2. Contact No:</label>
                                 <input type="text" class="contact-input">
                             </div>

                             <div class="csf-field-group">
                                 <label>3. Business Name:</label>
                                 <input type="text" class="business-input">
                             </div>

                             <div class="csf-field-group">
                                 <label>4. CERTIFICATION OF EMPLOYER:</label>
                                 <div class="certification-text">
                                    This is to certify that the required 3/6 monthly premium contributions plus at least 6
                                    months contributions preceding the 3 months qualifying contributions within 12 month period
                                    prior to the first day of confinement (sufficient regularity) have been regularly remitted
                                    to PhilHealth. Moreover, the information supplied by the member or his/her representative on
                                    Part I are consistent with our available records.
                                 </div>
                                 
                                 <div class="signature-fields">
                                     <div class="signature-field">
                                         <label>Signature Over Printed Name of Employer/Authorized Representative:</label>
                                         <div class="signature-line"></div>
                                         <div class="printed-name-line"></div>
                                     </div>
                                     <div class="signature-field">
                                         <label>Official Capacity/Designation:</label>
                                         <input type="text" class="capacity-input">
                                     </div>
                                     <div class="signature-field">
                                         <label>Date Signed:</label>
                                         <div class="date-fields">
                                             <div class="date-field">
                                                 <label>month</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>day</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>year</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>

                         <!-- PART III - CONSENT TO ACCESS PATIENT RECORD/S -->
                         <div class="csf-section">
                             <h3>PART III - CONSENT TO ACCESS PATIENT RECORD/S</h3>
                             
                             <div class="consent-text">
                                <p>I hereby give my consent to the submission and examination of my medical records and other
                                    pertinent documents to PhilHealth for the purpose of processing my claim for health care
                                    benefits. I understand that the information contained therein will be treated with strict
                                    confidentiality and will be used solely for the purpose stated above.</p>
                                <p>I further understand that PhilHealth shall not be held liable for any legal action that may
                                    arise from the submission and examination of the said documents, and that I am responsible
                                    for the accuracy and completeness of the information provided.</p>
                             </div>

                             <div class="signature-fields">
                                 <div class="signature-field">
                                     <label>Signature Over Printed Name of Member/Patient/Authorized Representative:</label>
                                     <div class="signature-line"></div>
                                     <div class="printed-name-line"></div>
                                 </div>
                                 <div class="signature-field">
                                     <label>Date Signed:</label>
                                     <div class="date-fields">
                                         <div class="date-field">
                                             <label>month</label>
                                             <input type="text" class="date-input">
                                         </div>
                                         <div class="date-field">
                                             <label>day</label>
                                             <input type="text" class="date-input">
                                         </div>
                                         <div class="date-field">
                                             <label>year</label>
                                             <input type="text" class="date-input">
                                         </div>
                                     </div>
                                 </div>
                             </div>

                             <div class="thumbmark-section">
                                <div class="thumbmark-text">If member/representative is unable to write, put right thumbmark.
                                    Member/Representative should be assisted by an HCI representative. Check the appropriate
                                    box.</div>
                                 <div class="checkbox-group">
                                     <label><input type="checkbox"> Patient</label>
                                     <label><input type="checkbox"> Representative</label>
                                 </div>
                             </div>

                             <div class="relationship-section">
                                 <label>Relationship of the representative to the Patient:</label>
                                 <div class="checkbox-group">
                                     <label><input type="checkbox"> Spouse</label>
                                     <label><input type="checkbox"> Child</label>
                                     <label><input type="checkbox"> Parent</label>
                                     <label><input type="checkbox"> Sibling</label>
                                     <label><input type="checkbox"> Others, Specify:</label>
                                     <input type="text" class="others-input">
                                 </div>
                             </div>

                             <div class="reason-section">
                                 <label>Reason for signing on behalf of the patient:</label>
                                 <div class="checkbox-group">
                                     <label><input type="checkbox"> Patient is incapacitated</label>
                                     <label><input type="checkbox"> Other reasons:</label>
                                     <input type="text" class="reason-input">
                                 </div>
                             </div>
                         </div>

                         <!-- PART IV - HEALTH CARE PROFESSIONAL INFORMATION -->
                         <div class="csf-section">
                             <h3>PART IV - HEALTH CARE PROFESSIONAL INFORMATION</h3>
                             
                             <div class="professional-rows">
                                 <div class="professional-row">
                                     <div class="professional-field">
                                         <label>Accreditation No.:</label>
                                         <div class="accreditation-boxes">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                         </div>
                                     </div>
                                     <div class="professional-field">
                                         <label>Signature Over Printed Name:</label>
                                         <div class="signature-line"></div>
                                         <div class="printed-name-line"></div>
                                     </div>
                                     <div class="professional-field">
                                         <label>Date Signed:</label>
                                         <div class="date-fields">
                                             <div class="date-field">
                                                 <label>month</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>day</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>year</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                         </div>
                                     </div>
                                 </div>

                                 <div class="professional-row">
                                     <div class="professional-field">
                                         <label>Accreditation No.:</label>
                                         <div class="accreditation-boxes">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                         </div>
                                     </div>
                                     <div class="professional-field">
                                         <label>Signature Over Printed Name:</label>
                                         <div class="signature-line"></div>
                                         <div class="printed-name-line"></div>
                                     </div>
                                     <div class="professional-field">
                                         <label>Date Signed:</label>
                                         <div class="date-fields">
                                             <div class="date-field">
                                                 <label>month</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>day</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>year</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                         </div>
                                     </div>
                                 </div>

                                 <div class="professional-row">
                                     <div class="professional-field">
                                         <label>Accreditation No.:</label>
                                         <div class="accreditation-boxes">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                             <input type="text" maxlength="1" class="accreditation-box">
                                         </div>
                                     </div>
                                     <div class="professional-field">
                                         <label>Signature Over Printed Name:</label>
                                         <div class="signature-line"></div>
                                         <div class="printed-name-line"></div>
                                     </div>
                                     <div class="professional-field">
                                         <label>Date Signed:</label>
                                         <div class="date-fields">
                                             <div class="date-field">
                                                 <label>month</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>day</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>year</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>

                         <!-- PART V - PROVIDER INFORMATION AND CERTIFICATION -->
                         <div class="csf-section">
                             <h3>PART V - PROVIDER INFORMATION AND CERTIFICATION</h3>
                             
                             <div class="csf-field-group">
                                 <label>1. PhilHealth Benefits:</label>
                                 <div class="benefits-section">
                                     <div class="benefit-field">
                                         <label>ICD 10 or RVS Code:</label>
                                         <input type="text" class="code-input">
                                     </div>
                                     <div class="benefit-field">
                                         <label>1. First Case Rate:</label>
                                         <input type="text" class="rate-input">
                                     </div>
                                     <div class="benefit-field">
                                         <label>2. Second Case Rate:</label>
                                         <input type="text" class="rate-input">
                                     </div>
                                 </div>
                             </div>

                             <div class="csf-field-group">
                                 <label>2. Certification:</label>
                                 <div class="certification-text">
                                    I certify that services rendered were recorded in the patient's chart and health care
                                    institution records and that the herein information given are true and correct.
                                 </div>
                                 
                                 <div class="signature-fields">
                                     <div class="signature-field">
                                         <label>Signature Over Printed Name of Authorized HCI:</label>
                                         <div class="signature-line"></div>
                                         <div class="printed-name-line"></div>
                                     </div>
                                     <div class="signature-field">
                                         <label>Official Capacity/Designation:</label>
                                         <input type="text" class="capacity-input">
                                     </div>
                                     <div class="signature-field">
                                         <label>Date Signed:</label>
                                         <div class="date-fields">
                                             <div class="date-field">
                                                 <label>month</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>day</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                             <div class="date-field">
                                                 <label>year</label>
                                                 <input type="text" class="date-input">
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 @endif

                 @if(count($results) > 0)
                     @if(($viewType ?? 'patient_list') === 'patient_list')
                         <!-- Patient List Table -->
                         <div class="table-responsive">
                             <table class="table table-striped table-bordered">
                                 <thead class="table-dark">
                                     <tr>
                                         <th>Patient Name</th>
                                         <th>MRN</th>
                                         <th>Visit ID</th>
                                         <th>Order Date</th>
                                         <th>Entry Type</th>
                                         <th>Action</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @foreach($results as $patient)
                                         <tr>
                                             <td>{{ $patient['firstname'] ?? 'N/A' }} {{ $patient['lastname'] ?? 'N/A' }}</td>
                                             <td>{{ $patient['mrn'] ?? 'N/A' }}</td>
                                             <td>{{ $patient['visitId'] ?? 'N/A' }}</td>
                                             <td>
                                                 @if(isset($patient['lastOrderDate']))
                                                     @php
                                                         try {
                                                             // Handle MongoDB UTCDateTime or timestamp
                                                             if ($patient['lastOrderDate'] instanceof \MongoDB\BSON\UTCDateTime) {
                                                                 $date = $patient['lastOrderDate']->toDateTime();
                                                             } elseif (is_numeric($patient['lastOrderDate'])) {
                                                                 // Handle timestamp (milliseconds)
                                                                 $timestamp = $patient['lastOrderDate'] / 1000; // Convert to seconds
                                                                 $date = new \DateTime();
                                                                 $date->setTimestamp($timestamp);
                                                             } else {
                                                                 $date = \Carbon\Carbon::parse($patient['lastOrderDate']);
                                                             }
                                                             echo $date->format('M d, Y H:i');
                                                         } catch (\Exception $e) {
                                                             echo 'N/A';
                                                         }
                                                     @endphp
                                                 @else
                                                     N/A
                                                 @endif
                                             </td>
                                             <td>
                                                 <span class="badge badge-info">{{ $patient['entype'] ?? 'N/A' }}</span>
                                             </td>
                                             <td>
                                                 <form method="POST" action="{{ route('oecb.patient') }}" style="display: inline;">
                                                     @csrf
                                                     <input type="hidden" name="dateRange" value="{{ $dateRange }}">
                                                     <input type="hidden" name="billingGroup" value="{{ $billingGroup }}">
                                                     <input type="hidden" name="orgId" value="{{ $orgId }}">
                                                     <input type="hidden" name="mrn" value="{{ $patient['mrn'] }}">
                                                     <input type="hidden" name="vvisitId" value="{{ $patient['visitId'] }}">
                                                     <input type="hidden" name="visitId" value="{{ $patient['visitUid'] }}">
                                                     <button type="submit" class="btn btn-sm btn-success">
                                                         <i class="fas fa-eye"></i> View OECB
                                                     </button>
                                                 </form>
                                             </td>
                                         </tr>
                                     @endforeach
                                 </tbody>
                             </table>
                         </div>
                     @else
                         <!-- OECB Results Table -->
                         <div class="table-responsive">
                             <table class="table table-striped table-bordered">
                                 <thead class="table-dark">
                                     <tr>
                                         <th>Order Item Name</th>
                                         <th>Billing Group</th>
                                         <th>Billing Sub Group</th>
                                         <!-- <th>Department</th> -->
                                         <th>Quantity</th>
                                         <th>Unit Price</th>
                                         <th>Payor Discount</th>
                                         <th>Special Discount</th>
                                         <th>Total Price</th>
                                         <th>Senior / PWD Discount</th>
                                         <th>OECB</th>
                                         <th>Final</th>
                                         <th>Status</th>
                                         <th>Ordered Date</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     @php
                                         $totalPriceSum = 0;
                                         $totalOECBSum = 0;
                                         $totalFinalSum = 0;
                                         $totalSCDPWDBSum = 0;
                                         
                                        //dd($results)
                                     @endphp
                                     @foreach($results as $item)
                                         @php
                                             $quantity = $item['quantity'] ?? 0;
                                             $unitPrice = $item['unitprice'] ?? 0;
                                             $oecbCode = $item['orderItemCode']['code'] ?? 0;
                                             $payorDiscount = $item['payordiscount'] ?? 0;
                                             $specialDiscount = $item['specialdiscount'] ?? 0;
                                             $status = $item['statusDesc'] ?? '';
                                             $totalPrice = ($quantity * $unitPrice);// - $payorDiscount - $specialDiscount;
                                             $oecbTotal = $oecbCode * $quantity;
                                             $final = $totalPrice - $oecbTotal - $payorDiscount - $specialDiscount;                                             
                                             $scdpwdTotal = 0;

                                             if (strtolower($status) !== 'cancelled') {
                                                 $totalPriceSum += $totalPrice;
                                                 $totalOECBSum += $oecbTotal;
                                                 $totalFinalSum += ($final < 0 ? 0 : $final);
                                             }

                                                $hasSCD = false;
                                                $hasPWD = false;
                                                $payorsArr = $patientVisitDetails['payorsArr'] ?? [];
                                                
                                                // Convert MongoDB object to array if needed
                                                if (is_object($payorsArr)) {
                                                    if ($payorsArr instanceof \MongoDB\Model\BSONArray || $payorsArr instanceof \MongoDB\Model\BSONDocument) {
                                                        $payorsArr = iterator_to_array($payorsArr);
                                                    } else {
                                                        $payorsArr = (array) $payorsArr;
                                                    }
                                                }
                                            
                                                if (is_array($payorsArr) && !empty($payorsArr)) {
                                                    foreach ($payorsArr as $payor) {
                                                        if (stripos($payor, 'OECB SCD') !== false) {
                                                            $hasSCD = true;
                                                        }
                                                        if (stripos($payor, 'OECB PWD') !== false || stripos($payor, 'PWD EM') !== false) {
                                                            $hasPWD = true;
                                                        }
                                                    }
                                                }

                                            if ($hasSCD || $hasPWD) {
                                                    $scdpwdTotal = $totalPrice * 0.2;
                                                }
                                                if (strtolower($status) !== 'cancelled') {
                                                    $totalSCDPWDBSum += $scdpwdTotal;
                                                }

                                         @endphp
                                         <tr>
                                             <td>{{ $item['orderitemname'] ?? 'N/A' }}</td>
                                             <td>{{ $item['billinggroups'] ?? 'N/A' }}</td>
                                             <td>{{ $item['billingsubgroups'] ?? 'N/A' }}</td>
                                             <!-- <td>{{ $item['ordertodepartment_name'] ?? 'N/A' }}</td> -->
                                             <td>{{ $quantity }}</td>
                                             <td>{{ number_format($unitPrice, 2) }}</td>
                                             <td>{{ number_format($item['payordiscount'], 2) }}</td>
                                             <td>{{ number_format($item['specialdiscount'], 2) }}</td>
                                             <td>{{ number_format($totalPrice, 2) }}</td>
                                             <td>@if($hasSCD || $hasPWD)({{ number_format($totalPrice * 0.2, 2) }})@else(0.00)@endif</td>
                                             <td>{{ number_format($oecbTotal, 2) }}</td>
                                             <td>{{ number_format($final < 0 ? 0 : $final, 2) }}</td>
                                             <td>
                                                 <span class="badge badge-success">
                                                     {{ $item['statusDesc'] }}
                                                 </span>
                                             </td>
                                             <td>
                                                 @if(isset($item['orderdate']))
                                                     @php
                                                         try {
                                                             // Handle MongoDB UTCDateTime or timestamp
                                                             if ($item['orderdate'] instanceof \MongoDB\BSON\UTCDateTime) {
                                                                 $date = $item['orderdate']->toDateTime();
                                                             } elseif (is_numeric($item['orderdate'])) {
                                                                 // Handle timestamp (milliseconds)
                                                                 $timestamp = $item['orderdate'] / 1000; // Convert to seconds
                                                                 $date = new \DateTime();
                                                                 $date->setTimestamp($timestamp);
                                                             } else {
                                                                 $date = \Carbon\Carbon::parse($item['orderdate']);
                                                             }
                                                             echo $date->format('M d, Y H:i');
                                                         } catch (\Exception $e) {
                                                             echo 'N/A';
                                                         }
                                                     @endphp
                                                 @else
                                                     N/A
                                                 @endif
                                             </td>
                                         </tr>
                                     @endforeach
                                     <tr class="table-info font-weight-bold">
                                         <td colspan="7" class="text-right"><strong>TOTALS:</strong></td>
                                         <td><strong>{{ number_format($totalPriceSum, 2) }}</strong></td>
                                         <td><strong>{{ number_format($totalOECBSum, 2) }}</strong></td>
                                         <td><strong>{{ number_format($totalSCDPWDBSum, 2) }}</strong></td>
                                         <td><strong>{{ number_format($totalFinalSum, 2) }}</strong></td>
                                         <td colspan="3"></td>
                                     </tr>
                                 </tbody>
                             </table>
                         </div>
                     @endif

                     <!-- Pagination -->
                     <div class="d-flex justify-content-center mt-4">
                         {{ $results->links() }}
                     </div>
                 @else
                     <div class="alert alert-info">
                         <i class="fas fa-info-circle"></i> 
                         @if(($viewType ?? 'patient_list') === 'patient_list')
                             No patients found for the selected date range.
                         @else
                             No OECB results found for the selected criteria.
                         @endif
                     </div>
                 @endif
            </div>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .table th {
        background-color: #343a40;
        color: white;
        border-color: #454d55;
    }

    .badge {
        font-size: 0.8em;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
    }

    .card-header {
        background-color: #007bff;
        color: white;
    }

    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
    }

    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }
    
    /* Loading Overlay Styles */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .loading-content {
        text-align: center;
        padding: 30px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        max-width: 300px;
        width: 90%;
    }
    
    .loading-content .spinner-border {
        width: 3rem;
        height: 3rem;
        border-width: 0.3em;
    }
    
    .loading-content h4 {
        color: #495057;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .loading-content p {
        margin-bottom: 0;
        font-size: 14px;
    }
    
    /* Button loading state */
    .btn-loading {
        position: relative;
        pointer-events: none;
    }
    
    .btn-loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        margin: auto;
        border: 2px solid transparent;
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: button-loading-spinner 1s ease infinite;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
    }
    
         @keyframes button-loading-spinner {
         from {
             transform: rotate(0turn);
         }

         to {
             transform: rotate(1turn);
         }
     }
     
     /* Professional SOA Styles */
     .printable-soa {
         font-family: Arial, sans-serif;
        line-height: 1.5;
        color: #222;
         max-width: 800px;
         margin: 0 auto;
        font-size: 13px;
     }
     
     /* Print-specific styles for handling many rows */
    .itemized-table {
        page-break-inside: auto;
        width: 100%;
        margin: 0;
        border-collapse: collapse;
    }
    
    .itemized-table thead {
        display: table-header-group;
        page-break-after: avoid;
    }
    
    .itemized-table tbody tr {
        page-break-inside: avoid;
        page-break-after: auto;
        margin: 0;
        padding: 0;
        border-spacing: 0;
        height: auto;
    }
    
    /* Remove forced page breaks - let content flow naturally */
    
    .itemized-table tbody tr:last-child {
        page-break-after: avoid;
    }
    
    /* Remove extra spacing in table cells */
    .itemized-table th,
    .itemized-table td {
        margin: 0;
        padding: 1px 3px;
        border-collapse: collapse;
        vertical-align: top;
        border-spacing: 0;
        line-height: 1.1;
    }

        /* Ensure tables don't break awkwardly */
        .soa-table {
            page-break-inside: avoid;
        }

        /* Better spacing for print */
        .printable-soa {
            page-break-inside: auto;
        }

        /* Header sections should stay together */
        .soa-header {
            page-break-after: avoid;
        }

        /* Patient info section should stay together */
        .patient-info {
            page-break-after: avoid;
        }

    /* Print mode optimizations */
    .printable-soa.print-mode {
        font-size: 12px;
        line-height: 1.3;
        max-width: none;
        margin: 0;
        padding: 6px;
        color: #000;
    }
    
    .printable-soa.print-mode .soa-title {
        font-size: 22px;
        margin-bottom: 6px;
        font-weight: bold;
    }
    
    .printable-soa.print-mode .soa-table,
    .printable-soa.print-mode .itemized-table {
        font-size: 10px;
        margin: 0;
        border-collapse: collapse;
    }
    
    .printable-soa.print-mode .soa-table th,
    .printable-soa.print-mode .soa-table td,
    .printable-soa.print-mode .itemized-table th,
    .printable-soa.print-mode .itemized-table td {
        padding: 1px 2px;
        font-weight: 500;
        margin: 0;
        line-height: 1.1;
        border-spacing: 0;
    }
    
    /* Group headers and subtotals styling */
    .group-header {
        background-color: #f8f9fa !important;
        font-weight: bold;
        border-top: 2px solid #dee2e6;
    }
    
    .group-total {
        background-color: #e9ecef !important;
        font-style: italic;
        border-bottom: 1px solid #dee2e6;
    }
    
    .group-total td {
        padding: 8px 12px;
    }
    
    .soa-title-section {
        text-align: center;
        margin-bottom: 10px;
    }
     
     /* Itemized Charges Controls */
    .itemized-controls {
        text-align: center;
        margin-bottom: 8px;
    }
     
    .itemized-table-container {
        border: 1px solid #ddd;
        border-radius: 4px;
        margin: 0;
        padding: 0;
    }
     
     .itemized-table-container.collapsed {
         /* Removed max-height to display all items */
     }
     
     .itemized-summary {
         display: none;
         background: #f8f9fa;
         padding: 15px;
         border-radius: 4px;
         margin-bottom: 15px;
     }
     
     .itemized-summary.show {
         display: block;
     }
     
     .soa-title {
         font-size: 24px;
         font-weight: bold;
         margin: 0;
         color: #333;
     }
     
     .soa-ref-no {
         font-size: 12px;
         margin-top: 5px;
         text-align: right;
     }
     
    .account-info {
        margin-bottom: 10px;
        text-align: center;
    }
     
     .account-name {
         font-weight: bold;
         font-size: 14px;
     }
     
     .account-address {
         font-size: 12px;
         margin: 5px 0;
     }
     
     .account-id {
         font-size: 12px;
     }
     
    .patient-info {
        margin-bottom: 15px;
    }
     
     .patient-details {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 5px;
     }
     
     .info-row {
         display: flex;
         margin-bottom: 3px;
     }
     
     .info-row .label {
         font-weight: bold;
         min-width: 120px;
         font-size: 12px;
     }
     
    .info-row .value {
        font-size: 12px;
    }
    
    
    /* Alternative approach using specific classes */
    .diagnosis-row .label {
        min-width: 200px;
        max-width: 200px;
    }
    
    .diagnosis-row .value {
        flex: 1;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .diagnosis-row2 .value {
        flex: 1;
        word-wrap: break-word;
        overflow-wrap: break-word;
        width: 400px;
    }
    
     
    .soa-section {
        margin-bottom: 15px;
    }
     
     .section-title {
         font-size: 16px;
         font-weight: bold;
         margin-bottom: 10px;
         color: #333;
     }
     
    .soa-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
        font-size: 11px;
    }
     
    .soa-table th,
    .soa-table td {
        border: 1px solid #333;
        padding: 3px 5px;
        text-align: left;
        vertical-align: top;
        font-size: 11px;
        font-weight: 500;
    }
     
     .soa-table th {
         background-color: #f0f0f0;
         font-weight: bold;
         text-align: center;
         font-size: 10px;
     }
     
     .soa-table .total-row {
         background-color: #e8e8e8;
         font-weight: bold;
     }
     
    .soa-table .no-data {
        text-align: center;
        font-style: italic;
        color: #666;
    }
    
    /* Compact styling for Summary of Fees and Professional Fees */
    .summary-table,
    .professional-table {
        font-size: 10px;
    }
    
    .summary-table th,
    .summary-table td,
    .professional-table th,
    .professional-table td {
        padding: 3px 4px;
        font-size: 10px;
    }
    
    .summary-table th,
    .professional-table th {
        font-size: 9px;
        padding: 4px 3px;
    }
     
     .soa-footer {
         margin-top: 30px;
     }
     
     .signature-section {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 40px;
     }
     
     .signature-block h4 {
         font-size: 14px;
         font-weight: bold;
         margin-bottom: 10px;
     }
     
     .signature-line {
         font-size: 12px;
         margin-bottom: 8px;
     }
     
     .signature-space {
         height: 30px;
         border-bottom: 1px solid #333;
         margin: 10px 0;
     }
     
     /* Print Styles */
     @media print {
         body * {
             visibility: hidden;
         }
         
         .printable-soa,
         .printable-soa *,
         .printable-csf,
         .printable-csf * {
             visibility: visible;
         }
         
         .printable-soa,
         .printable-csf {
             position: absolute;
             left: 0;
             top: 0;
             width: 100%;
             margin: 0;
             padding: 20px;
         }
         
         .soa-header h2 {
             font-size: 28px;
         }
         
        .soa-table {
            font-size: 13px;
        }
        
        .soa-table th,
        .soa-table td {
            padding: 6px;
            font-weight: 500;
        }
         
        /* Print handling for many rows in Itemized Charges */
        .itemized-table {
            page-break-inside: auto;
            width: 100%;
            font-size: 11px;
            margin: 0;
            border-collapse: collapse;
        }
        
        .itemized-table thead {
            display: table-header-group;
            page-break-after: avoid;
        }
        
        .itemized-table tbody tr {
            page-break-inside: avoid;
            page-break-after: auto;
            margin: 0;
            padding: 0;
            border-spacing: 0;
            height: auto;
        }
        
        /* Let content flow naturally without forced breaks */
        
        .itemized-table tbody tr:last-child {
            page-break-after: avoid;
        }
        
        /* Optimize table cell spacing for print */
        .itemized-table th,
        .itemized-table td {
            padding: 1px 2px;
            margin: 0;
            border-collapse: collapse;
            vertical-align: top;
            line-height: 1.1;
            border-spacing: 0;
        }

            /* Ensure main SOA table doesn't break awkwardly */
            .soa-table {
                page-break-inside: avoid;
                font-size: 11px;
            }

        /* Better spacing and margins for print */
        .printable-soa {
            margin: 0;
            padding: 8px;
            font-size: 13px;
            line-height: 1.3;
            color: #000;
        }

            /* Keep header sections together */
            .soa-header {
                page-break-after: avoid;
                margin-bottom: 5px;
            }

            .patient-info {
                page-break-after: avoid;
                margin-bottom: 5px;
            }

        /* Optimize table cell padding for print - handled above */
        
        .soa-table th,
        .soa-table td {
            padding: 3px 5px;
            font-weight: 500;
        }

         /* Ensure totals row stays with content */
         .itemized-table .total-row {
             page-break-before: avoid;
         }
         
         /* Hide UI controls when printing */
         .no-print {
             display: none !important;
         }
     }
     
     /* CSF Form Styles */
     .printable-csf {
         font-family: Arial, sans-serif;
         line-height: 1.4;
         color: #333;
         max-width: 800px;
         margin: 0 auto;
         padding: 20px;
     }
     
     .csf-header {
         display: flex;
         justify-content: space-between;
         align-items: flex-start;
         margin-bottom: 20px;
         border-bottom: 2px solid #333;
         padding-bottom: 15px;
     }
     
     .csf-logo-section {
         flex: 0 0 150px;
     }
     
     .csf-logo {
         font-size: 14px;
         font-weight: bold;
     }
     
     .csf-title-section {
         flex: 1;
         text-align: center;
         margin: 0 20px;
     }
     
     .csf-title {
         font-size: 12px;
         margin-bottom: 5px;
     }
     
     .csf-subtitle {
         font-size: 14px;
         font-weight: bold;
         margin-bottom: 10px;
     }
     
     .csf-address {
         font-size: 10px;
         line-height: 1.2;
     }
     
     .csf-form-info {
         flex: 0 0 150px;
         text-align: right;
     }
     
     .csf-notice {
         font-size: 8px;
         margin-bottom: 10px;
     }
     
     .csf-form-title {
         font-size: 24px;
         font-weight: bold;
         margin-bottom: 5px;
     }
     
     .csf-form-subtitle {
         font-size: 12px;
         margin-bottom: 5px;
     }
     
     .csf-revision {
         font-size: 10px;
         margin-bottom: 10px;
     }
     
     .csf-series {
         font-size: 10px;
     }
     
        .series-boxes,
        .pin-boxes,
        .pen-boxes,
        .accreditation-boxes {
         display: flex;
         gap: 2px;
         margin-top: 5px;
     }
     
        .series-box,
        .pin-box,
        .pen-box,
        .accreditation-box {
         width: 15px;
         height: 20px;
         border: 1px solid #333;
         text-align: center;
         font-size: 12px;
         padding: 2px;
     }
     
     .csf-reminders {
         background-color: #f8f9fa;
         border: 1px solid #333;
         padding: 10px;
         margin-bottom: 20px;
     }
     
     .csf-reminders ul {
         margin: 5px 0;
         padding-left: 20px;
     }
     
     .csf-reminders li {
         font-size: 11px;
         margin-bottom: 3px;
     }
     
     .csf-section {
         margin-bottom: 25px;
         border: 1px solid #ddd;
         padding: 15px;
     }
     
     .csf-section h3 {
         font-size: 14px;
         font-weight: bold;
         margin-bottom: 15px;
         background-color: #f0f0f0;
         padding: 5px 10px;
         border-bottom: 1px solid #333;
     }
     
     .csf-field-group {
         margin-bottom: 15px;
     }
     
     .csf-field-group label {
         font-weight: bold;
         font-size: 11px;
         display: block;
         margin-bottom: 5px;
     }
     
     .name-fields {
         display: flex;
         gap: 10px;
         margin-bottom: 5px;
     }
     
     .name-field {
         flex: 1;
     }
     
     .name-field label {
         font-size: 10px;
         margin-bottom: 2px;
     }
     
     .name-input {
         width: 100%;
         border: none;
         border-bottom: 1px solid #333;
         padding: 2px;
         font-size: 11px;
     }
     
     .name-example {
         font-size: 9px;
         color: #666;
         font-style: italic;
     }
     
     .date-fields {
         display: flex;
         gap: 10px;
         align-items: center;
     }
     
     .date-field {
         display: flex;
         flex-direction: column;
         align-items: center;
     }
     
     .date-field label {
         font-size: 9px;
         margin-bottom: 2px;
     }
     
     .date-input {
         width: 30px;
         border: none;
         border-bottom: 1px solid #333;
         text-align: center;
         font-size: 11px;
     }
     
     .checkbox-group {
         display: flex;
         gap: 15px;
         flex-wrap: wrap;
         margin-top: 5px;
     }
     
     .checkbox-group label {
         font-size: 11px;
         font-weight: normal;
         display: flex;
         align-items: center;
         gap: 5px;
     }
     
     .confinement-period {
         display: flex;
         gap: 30px;
     }
     
     .confinement-field {
         flex: 1;
     }
     
     .certification-text {
         font-size: 10px;
         margin: 10px 0;
         padding: 10px;
         background-color: #f8f9fa;
         border: 1px solid #ddd;
     }
     
     .signature-fields {
         display: flex;
         gap: 20px;
         margin: 10px 0;
         align-items: flex-end;
     }
     
     .signature-field {
         flex: 1;
     }
     
     .signature-field label {
         font-size: 10px;
         margin-bottom: 5px;
     }
     
     .signature-line {
         height: 20px;
         border-bottom: 1px solid #333;
         margin-bottom: 5px;
     }
     
     .printed-name-line {
         height: 15px;
         border-bottom: 1px solid #333;
     }
     
        .thumbmark-section,
        .relationship-section,
        .reason-section {
         margin: 15px 0;
         padding: 10px;
         background-color: #f8f9fa;
         border: 1px solid #ddd;
     }
     
     .thumbmark-text {
         font-size: 10px;
         margin-bottom: 10px;
     }
     
        .others-input,
        .reason-input,
        .contact-input,
        .business-input,
        .capacity-input,
        .code-input,
        .rate-input {
         width: 150px;
         border: none;
         border-bottom: 1px solid #333;
         padding: 2px;
         font-size: 11px;
         margin-left: 10px;
     }
     
     .professional-rows {
         display: flex;
         flex-direction: column;
         gap: 15px;
     }
     
     .professional-row {
         display: flex;
         gap: 20px;
         align-items: flex-end;
         padding: 10px;
         border: 1px solid #ddd;
     }
     
     .professional-field {
         flex: 1;
     }
     
     .benefits-section {
         display: flex;
         gap: 20px;
         margin-top: 10px;
     }
     
     .benefit-field {
         flex: 1;
     }
     
     .benefit-field label {
         font-size: 10px;
         margin-bottom: 5px;
     }
     
     /* Print Styles for CSF */
     @media print {
         .printable-csf {
             font-size: 12px;
         }
         
         .csf-header {
             page-break-inside: avoid;
         }
         
         .csf-section {
             page-break-inside: avoid;
             margin-bottom: 15px;
         }
         
         .professional-row {
             page-break-inside: avoid;
         }
     }
 </style>
@endsection

@section('scripts')
<script src="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script>
        $(document).ready(function () {
    // Initialize date range picker
    $('#dateRange').daterangepicker({
        locale: {
            format: 'MM/DD/YYYY',
            separator: ' - ',
            applyLabel: 'Apply',
            cancelLabel: 'Cancel',
            fromLabel: 'From',
            toLabel: 'To',
            customRangeLabel: 'Custom',
            weekLabel: 'W',
            daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            firstDay: 1
        },
        startDate: moment().subtract(7, 'days'),
        endDate: moment(),
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    // Show loading overlay function
    function showLoading() {
        $('#loadingOverlay').fadeIn(300);
    }

    // Hide loading overlay function
    function hideLoading() {
        $('#loadingOverlay').fadeOut(300);
    }

    // Handle form submission with loading
            $('form[action="{{ route('oecb.index') }}"]').on('submit', function (e) {
        const submitBtn = $(this).find('button[type="submit"]');
        
        // Show loading overlay
        showLoading();
        
        // Add loading class to button
        submitBtn.addClass('btn-loading');
        submitBtn.prop('disabled', true);
        
        // Store original button text
        const originalText = submitBtn.text();
        submitBtn.text('Filtering...');
        
        // If there's an error or the page doesn't redirect, hide loading after 30 seconds
                setTimeout(function () {
            hideLoading();
            submitBtn.removeClass('btn-loading');
            submitBtn.prop('disabled', false);
            submitBtn.text(originalText);
        }, 30000);
    });

    // Handle page load to ensure loading is hidden
            $(window).on('load', function () {
        hideLoading();
    });

    // Also hide loading when DOM is ready (backup)
    hideLoading();

    // Handle export form submission with loading
            $('form[action="{{ route('oecb.export') }}"]').on('submit', function (e) {
        const submitBtn = $(this).find('button[type="submit"]');
        
        // Show loading for export
        showLoading();
        
        // Add loading class to button
        submitBtn.addClass('btn-loading');
        submitBtn.prop('disabled', true);
        
        // Store original button text
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Exporting...');
        
        // Hide loading after a short delay for export (since it's a download)
                setTimeout(function () {
            hideLoading();
            submitBtn.removeClass('btn-loading');
            submitBtn.prop('disabled', false);
            submitBtn.html(originalText);
        }, 3000);
    });

    // Handle patient OECB view form submission with loading
            $('form[action="{{ route('oecb.patient') }}"]').on('submit', function (e) {
        const submitBtn = $(this).find('button[type="submit"]');
        
        // Show loading overlay
        showLoading();
        
        // Add loading class to button
        submitBtn.addClass('btn-loading');
        submitBtn.prop('disabled', true);
        
        // Store original button text
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        
        // If there's an error or the page doesn't redirect, hide loading after 30 seconds
                setTimeout(function () {
            hideLoading();
            submitBtn.removeClass('btn-loading');
            submitBtn.prop('disabled', false);
            submitBtn.html(originalText);
        }, 30000);
    });

    // Removed auto-submit - users must click Filter button manually
    // $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
    //     $(this).closest('form').submit();
    // });

    // Hide loading if page is already loaded with results
    @if(count($results) > 0 || request()->isMethod('post'))
        // Page loaded with results, make sure loading is hidden
        hideLoading();
    @endif

         // Additional safety: hide loading after a short delay to ensure it's gone
            setTimeout(function () {
         hideLoading();
     }, 1000);
 });

 // Print SOA function
 function printSOA() {
     // Show the printable SOA section
     document.getElementById('printableSOA').style.display = 'block';
     
            // Add print-specific class for better page fitting
            document.getElementById('printableSOA').classList.add('print-mode');

            // Wait a moment for styles to apply, then print
            setTimeout(function () {
     // Print the page
     window.print();
     
                // Clean up after printing
                setTimeout(function () {
         document.getElementById('printableSOA').style.display = 'none';
                    document.getElementById('printableSOA').classList.remove('print-mode');
     }, 1000);
            }, 100);
 }
 
 
 // Itemized Charges Controls
 function toggleItemizedView() {
     const container = document.getElementById('itemizedTable');
     const summary = document.querySelector('.itemized-summary');
     
     if (container.classList.contains('collapsed')) {
         container.classList.remove('collapsed');
         summary.classList.remove('show');
     } else {
         container.classList.add('collapsed');
         summary.classList.add('show');
     }
 }
 
 function showItemizedSummary() {
     const container = document.getElementById('itemizedTable');
     const summary = document.querySelector('.itemized-summary');
     
     container.style.display = 'none';
     summary.classList.add('show');
 }
 
 function showAllItemized() {
     const container = document.getElementById('itemizedTable');
     const summary = document.querySelector('.itemized-summary');
     
     container.style.display = 'block';
     container.classList.remove('collapsed');
     summary.classList.remove('show');
 }
 </script>
@endsection