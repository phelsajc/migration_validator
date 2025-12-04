<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class MigrationValidationController extends Controller
{
    /**
     * Table configurations for migration validation
     */
    private $migrationTables = [
        'patients' => [
            'mongodb_collection' => 'patients',
            'mssql_table' => 'patients',
            'date_field_mongo' => 'createdat',
            'date_field_mssql' => 'createddate',
            'identifier_field' => 'mrn',
            'mongodb_identifier_field' => 'mrn',
            'pipeline_type' => 'complex',
        ],
        'careproviders' => [
            'mongodb_collection' => 'users',
            'mssql_table' => 'careprovider',
            'date_field_mongo' => 'createdat',
            'date_field_mssql' => 'createddate',
            'identifier_field' => 'careprovider_id',
            'mongodb_identifier_field' => '_id',
            'pipeline_type' => 'complex',
        ],
        'patientorderitems' => [
            'mongodb_collection' => 'patientorders',
            'mssql_table' => 'patientorderitems',
            'date_field_mongo' => 'createdat',
            'date_field_mssql' => 'createddate',
            'identifier_field' => 'patientorderitems_id',
            'mongodb_identifier_field' => 'patientorderitems._id',
            'pipeline_type' => 'complex'
        ],
        'patientorders' => [
            'mongodb_collection' => 'patientorders',
            'mssql_table' => 'patientorders',
            'date_field_mongo' => 'createdat',
            'date_field_mssql' => 'createddate',
            'identifier_field' => 'patientorders_id',
            'mongodb_identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'bedoccupancy' => [
            'mongodb_collection' => 'patientvisits',
            'mssql_table' => 'bedoccupancy',
            'date_field_mongo' => 'createdat',
            'date_field_mssql' => 'createddate',
            'identifier_field' => 'bedoccupancy_id',
            'mongodb_identifier_field' => 'bedoccupancy._id',
            'pipeline_type' => 'complex'
        ],
        'patientvisits' => [
            'mongodb_collection' => 'patientvisits',
            'mssql_table' => 'patientvisits',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'payors' => [
            'mongodb_collection' => 'payors',
            'mssql_table' => 'payors',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'dischargeprocesses' => [
            'mongodb_collection' => 'dischargeprocesses',
            'mssql_table' => 'dischargeprocesses',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'patientbills' => [
            'mongodb_collection' => 'patientbills',
            'mssql_table' => 'patientbills',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'patientbilleditems' => [
            'mongodb_collection' => 'patientbills',
            'mssql_table' => 'patientbilleditems',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'patientprocedures' => [
            'mongodb_collection' => 'patientprocedures',
            'mssql_table' => 'patientprocedures',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'patientpackages' => [
            'mongodb_collection' => 'patientpackages',
            'mssql_table' => 'patientpackages',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'patientpackages_orderitems' => [
            'mongodb_collection' => 'patientpackages',
            'mssql_table' => 'patientpackagesorderitems',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'insertedtimestamp',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'patientbilleditempayments' => [
            'mongodb_collection' => 'patientbilleditempayments',
            'mssql_table' => 'patientbilleditempayments',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'patientbilldeductions_careprovider' => [
            'mongodb_collection' => 'patientbilldeductions',
            'mssql_table' => 'patientbilldeductionscareprovider',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'patientbills_paymentdetails' => [
            'mongodb_collection' => 'patientbills',
            'mssql_table' => 'patientbills_paymentdetails',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'insertedtimestamp',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'deposits' => [
            'mongodb_collection' => 'deposits',
            'mssql_table' => 'deposits',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'visitpayors' => [
            'mongodb_collection' => 'patientvisits',
            'mssql_table' => 'visitpayors',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'goodsreceives' => [
            'mongodb_collection' => 'goodsreceives',
            'mssql_table' => 'goodsreceive',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'goodsreceives_itemdetails' => [
            'mongodb_collection' => 'goodsreceives',
            'mssql_table' => 'goodsreceives_items',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'stock_trans_iss' => [
            'mongodb_collection' => 'stocktransfers',
            'mssql_table' => 'stock_trans_iss',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'stock_trans_iss_items' => [
            'mongodb_collection' => 'stocktransfers',
            'mssql_table' => 'stock_trans_iss_items',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'stock_returns' => [
            'mongodb_collection' => 'stocktransferreturns',
            'mssql_table' => 'stock_returns',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'stock_returns_items' => [
            'mongodb_collection' => 'stocktransferreturns',
            'mssql_table' => 'stock_returns_items',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'orderitemcodes' => [
            'mongodb_collection' => 'orderitems',
            'mssql_table' => 'orderitemcodes',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'insertedtimestamp',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'departments' => [
            'mongodb_collection' => 'departments',
            'mssql_table' => 'department',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'insertedtimestamp',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'vendors' => [
            'mongodb_collection' => 'vendors',
            'mssql_table' => 'vendor',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'tpas' => [
            'mongodb_collection' => 'tpas',
            'mssql_table' => 'tpa',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'payortpaagreement' => [
            'mongodb_collection' => 'payors',
            'mssql_table' => 'payortpaagreement',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'itemmasters' => [
            'mongodb_collection' => 'itemmasters',
            'mssql_table' => 'itemmasters',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'patientbilleditempayments_paymentdetails' => [
            'mongodb_collection' => 'patientbilleditempayments',
            'mssql_table' => 'patientbilleditempayments_paymentdetails',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'itembillinggroup' => [
            'mongodb_collection' => 'tariffs',
            'mssql_table' => 'itembillinggroup',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'stockledgers' => [
            'mongodb_collection' => 'stockledgers',
            'mssql_table' => 'stockledgers',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'stockledgersdetails' => [
            'mongodb_collection' => 'stockledgers',
            'mssql_table' => 'stockledgersdetails',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'patientchargecodes' => [
            'mongodb_collection' => 'patientchargecodes',
            'mssql_table' => 'patientchargecodes',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifiedat',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'stockdispenses' => [
            'mongodb_collection' => 'stockdispenses',
            'mssql_table' => 'stockdispenses',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifiedat',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'patientbilldeductions' => [
            'mongodb_collection' => 'patientbilldeductions',
            'mssql_table' => 'patientbilldeductions',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'lab_exams' => [
            'mongodb_collection' => 'labresults',
            'mssql_table' => 'examresult',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'rad_exams' => [
            'mongodb_collection' => 'radiologyresults',
            'mssql_table' => 'examresult',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],//43
    ];
    /**
     * Display the migration validation dashboard
     */
    public function index()
    {
        return view('migration-validation.dashboard');
    }

    /**
     * Get pipeline for specific table based on configuration
     */
    private function getPipelineForTable($tableName, $startDate, $endDate)
    {
        if (!isset($this->migrationTables[$tableName])) {
            throw new Exception("Table configuration not found: {$tableName}");
        }

        $config = $this->migrationTables[$tableName];
        $startISODate = new \MongoDB\BSON\UTCDateTime(Carbon::parse($startDate)->timestamp * 1000);
        $endISODate = new \MongoDB\BSON\UTCDateTime(Carbon::parse($endDate)->timestamp * 1000);

        switch ($tableName) {
            case 'patients':
                return [
                    [
                        '$lookup' => [
                            'as' => "genderDetails",
                            'from' => "referencevalues",
                            'foreignField' => "_id",
                            'localField' => "genderuid"
                        ]
                    ],
                    [
                        '$lookup' => [
                            'as' => "maritalDetails",
                            'from' => "referencevalues",
                            'foreignField' => "_id",
                            'localField' => "maritalstatusuid"
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],

                    [
                        '$lookup' => [
                            'from' => 'patientadditionaldetails',
                            'localField' => '_id',
                            'foreignField' => 'patientuid',
                            'as' => 'additionalDetails'
                        ]
                    ],
                    [
                        '$match' => [
                            $config['date_field_mongo'] => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'careproviders':
                return [
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'genderuid',
                            'foreignField' => '_id',
                            'as' => 'genderDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$genderDetails', 'preserveNullAndEmptyArrays' => true]],

                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'specialtyuid',
                            'foreignField' => '_id',
                            'as' => 'specialtyDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$specialtyDetails', 'preserveNullAndEmptyArrays' => true]],

                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'useridentifiers.idtypeuid',
                            'foreignField' => '_id',
                            'as' => 'phicnoDetails'
                        ]
                    ],

                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$orgDetails', 'preserveNullAndEmptyArrays' => true]],

                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'subspecialtyuids',
                            'foreignField' => '_id',
                            'as' => 'subSpecialtyDetails'
                        ]
                    ],
                    [
                        '$match' => [
                            'iscareprovider' => true,
                            $config['date_field_mongo'] => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ],
                ];

            case 'patientorderitems':
                return [
                    ['$unwind' => ['path' => '$patientorderitems', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'billinggroups',
                            'localField' => 'patientorderitems.billinggroupuid',
                            'foreignField' => '_id',
                            'as' => 'billinggroupDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$billinggroupDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'billinggroups',
                            'localField' => 'patientorderitems.billingsubgroupuid',
                            'foreignField' => '_id',
                            'as' => 'billingsubgroupDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$billingsubgroupDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'billingsubgroupDetails.chargegroupcodeuid',
                            'foreignField' => '_id',
                            'as' => 'itemgroupDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$itemgroupDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'patientorderitems.ordertodepartmentuid',
                            'foreignField' => '_id',
                            'as' => 'departmentDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$departmentDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'tariffs',
                            'localField' => 'patientorderitems.tariffuid',
                            'foreignField' => '_id',
                            'as' => 'tariffDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$tariffDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'discountcodes',
                            'localField' => 'patientorderitems.specialdiscountcodeuids',
                            'foreignField' => '_id',
                            'as' => 'discountcodesDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$discountcodesDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'patientorderitems.agreementdiscounttypeuid',
                            'foreignField' => '_id',
                            'as' => 'payorDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$payorDetails', 'preserveNullAndEmptyArrays' => true]],

                    [
                        '$lookup' => [
                            'from' => 'wards',
                            'localField' => 'warduid',
                            'foreignField' => '_id',
                            'as' => 'wards'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$wards',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'patientorderitems.statusuid',
                            'foreignField' => '_id',
                            'as' => 'statusDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$statusDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'tariffs',
                            'localField' => 'patientorderitems.tariffuid',
                            'foreignField' => '_id',
                            'as' => 'tariffDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$tariffDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'users',
                            'localField' => 'patientorderitems.careprovideruid',
                            'foreignField' => '_id',
                            'as' => 'careproviderDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$careproviderDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'orderitems',
                            'localField' => 'patientorderitems.orderitemuid',
                            'foreignField' => '_id',
                            'as' => 'orderItemDetails'
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'frequencies',
                            'localField' => 'patientorderitems.frequencyuid',
                            'foreignField' => '_id',
                            'as' => 'frequencyDetails'
                        ]
                    ],
                    [
                        '$match' => [
                            $config['date_field_mongo'] => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ],
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ],
                ];
            case 'bedoccupancy':
                return [
                    [
                        '$unwind' => [
                            'path' => '$bedoccupancy',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'beds',
                            'localField' => 'bedoccupancy.beduid',
                            'foreignField' => '_id',
                            'as' => 'bedsDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$bedsDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$addFields' => [
                            'resolvedWardUid' => [
                                '$ifNull' => [
                                    '$bedoccupancy.warduid',
                                    '$bedsDetails.warduid'
                                ]
                            ]
                        ]
                    ],

                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'wards',
                            'localField' => 'resolvedWardUid',
                            'foreignField' => '_id',
                            'as' => 'stnDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$stnDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'locations',
                            'localField' => 'bedsDetails.roomuid',
                            'foreignField' => '_id',
                            'as' => 'locDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$locDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'bedoccupancy.bedcategoryuid',
                            'foreignField' => '_id',
                            'as' => 'refDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$refDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],

                    [
                        '$lookup' => [
                            'from' => 'beds',
                            'localField' => 'bedoccupancy.beduid',
                            'foreignField' => '_id',
                            'as' => 'bedParentDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$bedParentDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'bedParentDetails.owningdeptuid',
                            'foreignField' => '_id',
                            'as' => 'depOwningDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$depOwningDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'bedoccupancy' => [
                                //'$exists' => true,
                                '$ne' => null
                            ],
                            $config['date_field_mongo'] => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ],
                ];
            case 'patientvisits':
                return [
                    [
                        '$match' => [
                            $config['date_field_mongo'] => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$lookup' => [
                            'as' => 'orgDetails',
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'as' => 'refDetails',
                            'from' => 'referencevalues',
                            'localField' => 'entypeuid',
                            'foreignField' => '_id'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$refDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'as' => 'statusDetails',
                            'from' => 'referencevalues',
                            'localField' => 'visitstatusuid',
                            'foreignField' => '_id'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$statusDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],

                    [
                        '$lookup' => [
                            'as' => 'visitDetails',
                            'from' => 'referencevalues',
                            'localField' => 'visitcareproviders.visittypeuid',
                            'foreignField' => '_id'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$visitDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'payors':
                return [
                    [
                        '$match' => [
                            $config['date_field_mongo'] => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$lookup' => [
                            'as' => 'typeDetails',
                            'from' => 'referencevalues',
                            'foreignField' => '_id',
                            'localField' => 'payortypeuid',
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$typeDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ]
                    ],
                    [
                        '$lookup' => [
                            'as' => 'tpasDetails',
                            'from' => 'tpas',
                            'foreignField' => '_id',
                            'localField' => 'associatedpayoragreementsandtpa.tpauid',
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$tpasDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ]
                    ],
                    [
                        '$lookup' => [
                            'as' => 'tpasCreditDetails',
                            'from' => 'referencevalues',
                            'foreignField' => '_id',
                            'localField' => 'tpasDetails.credittermuid',
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$tpasCreditDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails',
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'dischargeprocesses':
                return [
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'dischargetypeuid',
                            'foreignField' => '_id',
                            'as' => 'dschDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$dschDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'medicaldischargetypeuid',
                            'foreignField' => '_id',
                            'as' => 'medDschDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$medDschDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'patientbills':
                return [
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$lookup' => [
                            'as' => "entypeDetails",
                            'from' => "referencevalues",
                            'foreignField' => "_id",
                            'localField' => "entypeuid"
                        ]
                    ],
                    ['$unwind' => ['path' => '$entypeDetails']],
                    [
                        '$lookup' => [
                            'as' => "patientvisitsDetails",
                            'from' => "patientvisits",
                            'foreignField' => "_id",
                            'localField' => "patientvisituid"
                        ]
                    ],
                    ['$unwind' => ['path' => '$patientvisitsDetails']],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'userdepartmentuid',
                            'foreignField' => '_id',
                            'as' => 'UserDeptDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$UserDeptDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'patientprocedures':
                return [
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$procedures',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'departmentuid',
                            'foreignField' => '_id',
                            'as' => 'departmentsDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$departmentsDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'users',
                            'localField' => 'careprovideruid',
                            'foreignField' => '_id',
                            'as' => 'careproviderDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$careproviderDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'procedures',
                            'localField' => 'procedures.procedureuid',
                            'foreignField' => '_id',
                            'as' => 'proceduresDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$proceduresDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'patientpackages':
                return [
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'users',
                            'localField' => 'careprovideruid',
                            'foreignField' => '_id',
                            'as' => 'usersDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$usersDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],

                    [
                        '$lookup' => [
                            'from' => 'patientvisits',
                            'localField' => 'patientvisituid',
                            'foreignField' => '_id',
                            'as' => 'pxvisitDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$pxvisitDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'ordersets',
                            'localField' => 'ordersetuid',
                            'foreignField' => '_id',
                            'as' => 'orderSetDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orderSetDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],

                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'pxvisitDetails.entypeuid',
                            'foreignField' => '_id',
                            'as' => 'refDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$refDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'tariffs',
                            'localField' => 'tariffuid',
                            'foreignField' => '_id',
                            'as' => 'tarrifDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$tarrifDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],

                    [
                        '$lookup' => [
                            'from' => 'billinggroups',
                            'localField' => 'tarrifDetails.billinggroupuid',
                            'foreignField' => '_id',
                            'as' => 'billingGrpDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$billingGrpDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],

                    [
                        '$lookup' => [
                            'from' => 'billinggroups',
                            'localField' => 'tarrifDetails.billingsubgroupuid',
                            'foreignField' => '_id',
                            'as' => 'billingSubGrpDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$billingSubGrpDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],

                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'orderSetDetails.ordertodepartmentuid',
                            'foreignField' => '_id',
                            'as' => 'departmentDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$departmentDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$addFields' => [
                            'deletedAudit' => [
                                '$filter' => [
                                    'input' => '$auditlogs',
                                    'as' => 'log',
                                    'cond' => [
                                        '$eq' => ['$$log.comments', 'DELETED']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'tariffs',
                            'localField' => 'ordersetuid',
                            'foreignField' => 'ordersetuid',
                            'as' => 'orderPckgSetDetails'
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'billinggroups',
                            'localField' => 'orderPckgSetDetails.billinggroupuid',
                            'foreignField' => '_id',
                            'as' => 'billingGrpSetDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$billingGrpSetDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'billinggroups',
                            'localField' => 'orderPckgSetDetails.billingsubgroupuid',
                            'foreignField' => '_id',
                            'as' => 'billingSubGrpSetDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$billingSubGrpSetDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'patientpackages_orderitems':
                return [
                    [
                        '$unwind' => [
                            'path' => '$orderitems',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'patientbilleditempayments':
                return [
                    [
                        '$lookup' => [
                            'from' => 'billinggroups',
                            'localField' => 'billinggroupuid',
                            'foreignField' => '_id',
                            'as' => 'billinggroupDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$billinggroupDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'billinggroups',
                            'localField' => 'billingsubgroupuid',
                            'foreignField' => '_id',
                            'as' => 'billingsubgroupDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$billingsubgroupDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'billingsubgroupDetails.billinggrouptypeuid',
                            'foreignField' => '_id',
                            'as' => 'groupDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$groupDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'accountreceivables',
                            'localField' => 'aruid',
                            'foreignField' => '_id',
                            'as' => 'arDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$arDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'patientbills',
                            'localField' => 'patientbilleditemuid',
                            'foreignField' => 'patientbilleditems._id',
                            'as' => 'patientBilledItemsDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$patientBilledItemsDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'patientbilldeductions',
                            'localField' => 'patientbilldeductionuid',
                            'foreignField' => '_id',
                            'as' => 'patientBillDeductionDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$patientBillDeductionDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'payors',
                            'localField' => 'patientBillDeductionDetails.payoruid',
                            'foreignField' => '_id',
                            'as' => 'payorDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$payorDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'payorDetails.payortypeuid',
                            'foreignField' => '_id',
                            'as' => 'refDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$refDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'patientbilldeductions_careprovider':
                return [
                    [
                        '$unwind' => [
                            'path' => '$careproviderdata',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'as' => 'orgDetails',
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'careproviderdata' => [
                                '$ne' => null,
                            ],
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'patientbills_paymentdetails':
                return [
                    [
                        '$unwind' => [
                            'path' => '$paymentdetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'paymentdetails.paymentmodeuid',
                            'foreignField' => '_id',
                            'as' => 'paymentModeDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$paymentModeDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'paymentdetails.carddetails.cardtypeuid',
                            'foreignField' => '_id',
                            'as' => 'cardDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$cardDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'deposits':
                return [
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'paymentmodeuid',
                            'foreignField' => '_id',
                            'as' => 'paymentDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$paymentDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'carddetails.cardtypeuid',
                            'foreignField' => '_id',
                            'as' => 'cardDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$cardDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'payors',
                            'localField' => 'tpauid',
                            'foreignField' => 'associatedpayoragreementsandtpa.tpauid',
                            'as' => 'tpaDetails'
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'userdepartmentuid',
                            'foreignField' => '_id',
                            'as' => 'departmentDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$departmentDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'visitpayors':
                return [
                    [
                        '$unwind' => [
                            'path' => '$visitpayors',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'payoragreements',
                            'localField' => 'visitpayors.payoragreementuid',
                            'foreignField' => '_id',
                            'as' => 'paDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$paDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'tpas',
                            'localField' => 'visitpayors.tpauid',
                            'foreignField' => '_id',
                            'as' => 'tpaDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$tpaDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'goodsreceives':
                return [
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'grntypeuid',
                            'foreignField' => '_id',
                            'as' => 'grnDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$grnDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'inventorystores',
                            'localField' => 'storeuid',
                            'foreignField' => '_id',
                            'as' => 'storeDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$storeDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'storeDetails.departmentuid',
                            'foreignField' => '_id',
                            'as' => 'deptDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$deptDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'statusuid',
                            'foreignField' => '_id',
                            'as' => 'statusDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$statusDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'goodsreceives_itemdetails':
                return [
                    [
                        '$unwind' => [
                            'path' => '$itemdetails',
                            'preserveNullAndEmptyArrays' => true,
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'itemmasters',
                            'localField' => 'itemdetails.itemmasteruid',
                            'foreignField' => '_id',
                            'as' => 'imDetails',
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$imDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'imDetails.itemcategoryuid',
                            'foreignField' => '_id',
                            'as' => 'imCatDetails',
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$imCatDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'itemdetails.receivetypeuid',
                            'foreignField' => '_id',
                            'as' => 'imRecDetails',
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$imRecDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'taxmasters',
                            'localField' => 'itemdetails.taxcodeuid',
                            'foreignField' => '_id',
                            'as' => 'taxDetails',
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$taxDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'statusuid',
                            'foreignField' => '_id',
                            'as' => 'statusDetails',
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$statusDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails',
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'stock_trans_iss':
                return [
                    [
                        '$lookup' => [
                            'from' => 'inventorystores',
                            'localField' => 'fromstoreuid',
                            'foreignField' => '_id',
                            'as' => 'invDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$invDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'invDetails.departmentuid',
                            'foreignField' => '_id',
                            'as' => 'deptDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$deptDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'inventorystores',
                            'localField' => 'tostoreuid',
                            'foreignField' => '_id',
                            'as' => 'invToDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$invToDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'invToDetails.departmentuid',
                            'foreignField' => '_id',
                            'as' => 'deptToDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$deptToDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'todeptuid',
                            'foreignField' => '_id',
                            'as' => 'toDeptToDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$toDeptToDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'stock_trans_iss_items':
                return [
                    [
                        '$unwind' => [
                            'path' => '$itemdetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'itemmasters',
                            'localField' => 'itemdetails.itemmasteruid',
                            'foreignField' => '_id',
                            'as' => 'imDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$imDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'imDetails.itemcategoryuid',
                            'foreignField' => '_id',
                            'as' => 'imCatDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$imCatDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'itemdetails.statusuid',
                            'foreignField' => '_id',
                            'as' => 'statusDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$statusDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'stock_returns':
                return [
                    [
                        '$lookup' => [
                            'from' => 'inventorystores',
                            'localField' => 'fromstoreuid',
                            'foreignField' => '_id',
                            'as' => 'invDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$invDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'invDetails.departmentuid',
                            'foreignField' => '_id',
                            'as' => 'deptDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$deptDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'inventorystores',
                            'localField' => 'tostoreuid',
                            'foreignField' => '_id',
                            'as' => 'invToDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$invToDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'invToDetails.departmentuid',
                            'foreignField' => '_id',
                            'as' => 'deptToDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$deptToDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'todeptuid',
                            'foreignField' => '_id',
                            'as' => 'toDeptToDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$toDeptToDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'itemdetails.statusuid',
                            'foreignField' => '_id',
                            'as' => 'statusDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$statusDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'stock_returns_items':
                return [
                    [
                        '$unwind' => [
                            'path' => '$itemdetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'itemmasters',
                            'localField' => 'itemdetails.itemmasteruid',
                            'foreignField' => '_id',
                            'as' => 'imDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$imDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'imDetails.itemcategoryuid',
                            'foreignField' => '_id',
                            'as' => 'imCatDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$imCatDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'itemdetails.statusuid',
                            'foreignField' => '_id',
                            'as' => 'statusDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$statusDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'orderitemcodes':
                return [
                    [
                        '$unwind' => [
                            'path' => '$orderitemcodes',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'orderitemcodes.orderitemcodetypeuid',
                            'foreignField' => '_id',
                            'as' => 'orderItemDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orderItemDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'departments':
                return [
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'vendors':
                return [
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'vendorclassuid',
                            'foreignField' => '_id',
                            'as' => 'classDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$classDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'credittermuid',
                            'foreignField' => '_id',
                            'as' => 'creditDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$creditDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'tpas':
                return [
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'arcategoryuid',
                            'foreignField' => '_id',
                            'as' => 'categoryDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$categoryDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'credittermuid',
                            'foreignField' => '_id',
                            'as' => 'creditDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$creditDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'payortpaagreement':
                return [
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'itemmasters':
                return [
                    [
                        '$lookup' => [
                            'from' => 'orderitems',
                            'localField' => 'orderitemuid',
                            'foreignField' => '_id',
                            'as' => 'orderItemsDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orderItemsDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'itemcategoryuid',
                            'foreignField' => '_id',
                            'as' => 'referenceDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$referenceDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'patientbilleditempayments_paymentdetails':
                return [
                    [
                        '$unwind' => [
                            'path' => '$paymentdetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'paymentdetails.paymentmodeuid',
                            'foreignField' => '_id',
                            'as' => 'paymentModeDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$paymentModeDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'itembillinggroup':
                return [
                    [
                        '$lookup' => [
                            'from' => 'orderitems',
                            'localField' => 'orderitemuid',
                            'foreignField' => '_id',
                            'as' => 'orderDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orderDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'billinggroups',
                            'localField' => 'billinggroupuid',
                            'foreignField' => '_id',
                            'as' => 'billingGrpDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$billingGrpDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'billinggroups',
                            'localField' => 'billingsubgroupuid',
                            'foreignField' => '_id',
                            'as' => 'billingSubGrpDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$billingSubGrpDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails',
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'billingSubGrpDetails.chargegroupcodeuid',
                            'foreignField' => '_id',
                            'as' => 'itemgroupDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$itemgroupDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'stockledgers':
                return [
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'itemmasters',
                            'localField' => 'itemmasteruid',
                            'foreignField' => '_id',
                            'as' => 'itemMasterDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$itemMasterDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'billingSubGrpDetails.chargegroupcodeuid',
                            'foreignField' => '_id',
                            'as' => 'itemgroupDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$itemgroupDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'inventorystores',
                            'localField' => 'storeuid',
                            'foreignField' => '_id',
                            'as' => 'invStoreDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$invStoreDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'quantityuom',
                            'foreignField' => '_id',
                            'as' => 'uomDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$uomDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'stockledgersdetails':
                return [
                    [
                        '$unwind' => [
                            'path' => '$ledgerdetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'itemmasters',
                            'localField' => 'itemmasteruid',
                            'foreignField' => '_id',
                            'as' => 'itemMasterDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$itemMasterDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'billingSubGrpDetails.chargegroupcodeuid',
                            'foreignField' => '_id',
                            'as' => 'itemgroupDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$itemgroupDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'inventorystores',
                            'localField' => 'storeuid',
                            'foreignField' => '_id',
                            'as' => 'invStoreDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$invStoreDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'quantityuom',
                            'foreignField' => '_id',
                            'as' => 'uomDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$uomDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'patientchargecodes':
                return [
                    [
                        '$match' => [
                            'chargecodes._id' => ['$exists' => true, '$ne' => null]
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$chargecodes',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'tariffs',
                            'localField' => 'chargecodes.tariffuid',
                            'foreignField' => '_id',
                            'as' => 'tariffDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$tariffDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'chargecodes.ordertodepartmentuid',
                            'foreignField' => '_id',
                            'as' => 'deptDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$deptDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'billinggroups',
                            'localField' => 'chargecodes.billinggroupuid',
                            'foreignField' => '_id',
                            'as' => 'billinggroupDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$billinggroupDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'billinggroups',
                            'localField' => 'chargecodes.billingsubgroupuid',
                            'foreignField' => '_id',
                            'as' => 'billingsubgroupDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$billingsubgroupDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'billingsubgroupDetails.chargegroupcodeuid',
                            'foreignField' => '_id',
                            'as' => 'refDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$refDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'chargecodes.bedcategoryuid',
                            'foreignField' => '_id',
                            'as' => 'bedCatDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$bedCatDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];

            case 'patientorders':
                return [
                    [
                        '$lookup' => [
                            'from' => 'inventorystores',
                            'localField' => 'invstoreuid',
                            'foreignField' => '_id',
                            'as' => 'invDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$invDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'ordertodepartmentuid',
                            'foreignField' => '_id',
                            'as' => 'departmentDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$departmentDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'orderdepartmentuid',
                            'foreignField' => '_id',
                            'as' => 'departmentFromDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$departmentFromDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'users',
                            'localField' => 'careprovideruid',
                            'foreignField' => '_id',
                            'as' => 'usertDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$usertDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'patientvisits',
                            'localField' => 'patientvisituid',
                            'foreignField' => '_id',
                            'as' => 'visitDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$visitDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$orgDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'invDetails.departmentuid',
                            'foreignField' => '_id',
                            'as' => 'departmentStoresDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$departmentStoresDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'invDetails.storetype',
                            'foreignField' => '_id',
                            'as' => 'referenceStoresDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$referenceStoresDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'entypeuid',
                            'foreignField' => '_id',
                            'as' => 'entypeDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$entypeDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'wards',
                            'localField' => 'warduid',
                            'foreignField' => '_id',
                            'as' => 'wardDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$wardDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'beds',
                            'localField' => 'beduid',
                            'foreignField' => '_id',
                            'as' => 'bedDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$bedDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'locations',
                            'localField' => 'bedDetails.roomuid',
                            'foreignField' => '_id',
                            'as' => 'locationDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$locationDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'bedcategoryuid',
                            'foreignField' => '_id',
                            'as' => 'bedRefDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$bedRefDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'bedDetails.owningdeptuid',
                            'foreignField' => '_id',
                            'as' => 'depOwningDetails'
                        ]
                    ],
                    ['$unwind' => ['path' => '$depOwningDetails', 'preserveNullAndEmptyArrays' => true]],
                    [
                        '$match' => [
                            $config['date_field_mongo'] => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ],
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'stockdispenses':
                return [
                    [
                        '$unwind' => [
                            'path' => '$itemdetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'itemdetails.statusuid',
                            'foreignField' => '_id',
                            'as' => 'dispStatusDetails',
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$dispStatusDetails',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'patientbilleditems':
                return [
                    [
                        '$unwind' => [
                            'path' => '$patientbilleditems',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'billinggroups',
                            'localField' => 'patientbilleditems.billinggroupuid',
                            'foreignField' => '_id',
                            'as' => 'billinggroupDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$billinggroupDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'billinggroups',
                            'localField' => 'patientbilleditems.billingsubgroupuid',
                            'foreignField' => '_id',
                            'as' => 'billingsubgroupDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$billingsubgroupDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'billingsubgroupDetails.chargegroupcodeuid',
                            'foreignField' => '_id',
                            'as' => 'itemgroupDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$itemgroupDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'patientbilleditems.ordertodepartmentuid',
                            'foreignField' => '_id',
                            'as' => 'departmentDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$departmentDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'tariffs',
                            'localField' => 'patientbilleditems.tariffuid',
                            'foreignField' => '_id',
                            'as' => 'tariffDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$tariffDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$set' => [
                            'discountLookupIds' => [
                                '$cond' => [
                                    'if' => [
                                        '$gt' => [
                                            [
                                                '$size' => [
                                                    '$ifNull' => ['$patientbilleditems.specialdiscountcodeuids', []]
                                                ]
                                            ],
                                            0
                                        ]
                                    ],
                                    'then' => '$patientbilleditems.specialdiscountcodeuids',
                                    'else' => '$patientbilleditems.specialdiscountinfo.specialdiscountcodeuid'
                                ]
                            ]
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'discountcodes',
                            'localField' => 'discountLookupIds',
                            'foreignField' => '_id',
                            'as' => 'discountcodesDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$discountcodesDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$unset' => 'discountLookupIds'
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'patientbilleditems.agreementdiscounttypeuid',
                            'foreignField' => '_id',
                            'as' => 'payorDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$payorDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],


                    [
                        '$lookup' => [
                            'from' => 'patientorders',
                            'localField' => 'patientbilleditems.patientorderuid',
                            'foreignField' => '_id',
                            'as' => 'pxOrder'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$pxOrder',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'wards',
                            'localField' => 'pxOrder.warduid',
                            'foreignField' => '_id',
                            'as' => 'wards'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$wards',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'departments',
                            'localField' => 'patientbilleditems.ordertodepartmentuid',
                            'foreignField' => '_id',
                            'as' => 'orderDepDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orderDepDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'orderitems',
                            'localField' => 'patientbilleditems.orderitemuid',
                            'foreignField' => '_id',
                            'as' => 'orderItemDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orderItemDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$patientbilleditems.specialdiscountinfo',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            case 'patientbilldeductions':
                return [
                    [
                        '$lookup' => [
                            'from' => 'payors',
                            'localField' => 'payoruid',
                            'foreignField' => '_id',
                            'as' => 'payorDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$payorDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'payorDetails.payortypeuid',
                            'foreignField' => '_id',
                            'as' => 'referenceDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$referenceDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'tpas',
                            'localField' => 'tpauid',
                            'foreignField' => '_id',
                            'as' => 'tpaDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$tpaDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'referencevalues',
                            'localField' => 'tpaDetails.credittermuid',
                            'foreignField' => '_id',
                            'as' => 'tpaReferenceDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$tpaReferenceDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    // Optimized eligibility lookup with better performance
                    [
                        '$lookup' => [
                            'from' => 'eligibilityrequests',
                            'let' => ['visitId' => '$patientvisituid'],
                            'pipeline' => [
                                [
                                    '$match' => [
                                        '$expr' => [
                                            '$eq' => ['$identifieruid', '$$visitId']
                                        ]
                                    ]
                                ],
                                [
                                    '$sort' => ['eligibilitydate' => -1]
                                ],
                                [
                                    '$limit' => 1
                                ]
                            ],
                            'as' => 'elegibilityDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$elegibilityDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$lookup' => [
                            'from' => 'organisations',
                            'localField' => 'orguid',
                            'foreignField' => '_id',
                            'as' => 'orgDetails'
                        ]
                    ],
                    [
                        '$unwind' => [
                            'path' => '$orgDetails',
                            'preserveNullAndEmptyArrays' => true
                        ]
                    ],
                    [
                        '$match' => [
                            'modifiedat' => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
            default:
                // Generic pipeline for simple tables
                return [
                    [
                        '$match' => [
                            $config['date_field_mongo'] => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
                    [
                        '$count' => 'Total'
                    ]
                ];
        }
    }

    /**
     * Generic validation method for any table
     */
    public function validateTable(Request $request)
    {
        try {
            $tableName = $request->input('table', 'patients');

            if (!isset($this->migrationTables[$tableName])) {
                return response()->json([
                    'success' => false,
                    'error' => "Table '{$tableName}' is not configured for validation"
                ], 400);
            }

            // Handle both GET and POST requests with proper date formatting
            $startDateInput = $request->input('start_date', $request->query('start_date', now()->format('Y-m-d')));
            $endDateInput = $request->input('end_date', $request->query('end_date', now()->format('Y-m-d')));

            // Format dates properly for MongoDB
            $startDate = Carbon::parse($startDateInput)->startOfDay()->toISOString();
            $endDate = Carbon::parse($endDateInput)->endOfDay()->toISOString();

            // Log the formatted dates for debugging
            Log::info('Date formatting', [
                'table' => $tableName,
                'start_date_input' => $startDateInput,
                'end_date_input' => $endDateInput,
                'start_date_formatted' => $startDate,
                'end_date_formatted' => $endDate
            ]);

            // Get MongoDB count using the provided pipeline
            $mongodbCount = $this->getMongoDBCount($startDate, $endDate, $tableName);

            // Get MSSQL count
            $mssqlCount = $this->getMSSQLCount($startDate, $endDate, $tableName);
            //dd($startDate, $endDate, $tableName, $mongodbCount, $mssqlCount, $mongodbCount - $mssqlCount);
            // Log the counts for debugging
            Log::info('Validation counts', [
                'table' => $tableName,
                'mongodb_count' => $mongodbCount,
                'mssql_count' => $mssqlCount,
                'difference' => $mongodbCount - $mssqlCount,
                'date_range' => $startDateInput . ' to ' . $endDateInput
            ]);


            // Get missing records analysis
            $missingRecordsAnalysis = $this->getMissingRecordsAnalysis($tableName, $startDate, $endDate, 50);

            // Calculate difference
            $foundMatches = $missingRecordsAnalysis['found_matches'] ?? 0;
            //$difference = $mongodbCount - $mssqlCount;
            $difference = $mongodbCount - $foundMatches;
            $isComplete = $difference === 0;
            $result = [
                'table' => $tableName,
                'mongodb_count' => $mongodbCount,
                'mssql_count' => $mssqlCount,
                'difference' => $difference,
                'is_complete' => $isComplete,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'validated_at' => now()->toISOString(),
                'status' => $isComplete ? 'COMPLETE' : 'INCOMPLETE',
                'missing_records_analysis' => $missingRecordsAnalysis
            ];

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (Exception $e) {
            Log::error('Migration validation error', [
                'table' => $request->input('table', 'patients'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getValidationHistory()
    {
        try {
            // This would typically come from a database table storing validation history
            // For now, return a sample response
            return response()->json([
                'success' => true,
                'data' => [
                    'validations' => [
                        [
                            'id' => 1,
                            'table' => 'patients',
                            'mongodb_count' => 1500,
                            'mssql_count' => 1500,
                            'difference' => 0,
                            'is_complete' => true,
                            'validated_at' => now()->subHours(1)->toISOString(),
                            'status' => 'COMPLETE'
                        ],
                        [
                            'id' => 2,
                            'table' => 'patients',
                            'mongodb_count' => 2000,
                            'mssql_count' => 1995,
                            'difference' => 5,
                            'is_complete' => false,
                            'validated_at' => now()->subHours(2)->toISOString(),
                            'status' => 'INCOMPLETE'
                        ]
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Get validation history error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to get validation history: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAvailableTables()
    {
        try {
            $tables = [];

            foreach ($this->migrationTables as $tableName => $config) {
                $tables[] = [
                    'name' => $tableName,
                    'mongodb_collection' => $config['mongodb_collection'],
                    'mssql_table' => $config['mssql_table'],
                    'pipeline_type' => $config['pipeline_type'],
                    'identifier_field' => $config['identifier_field']
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'tables' => $tables,
                    'total' => count($tables)
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get available tables: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate patients table migration completeness (legacy method)
     */
    public function validatePatients(Request $request)
    {
        try {
            // Handle both GET and POST requests with proper date formatting
            $startDateInput = $request->input('start_date', $request->query('start_date', now()->format('Y-m-d')));
            $endDateInput = $request->input('end_date', $request->query('end_date', now()->format('Y-m-d')));

            // Format dates properly for MongoDB
            $startDate = Carbon::parse($startDateInput)->startOfDay()->toISOString();
            $endDate = Carbon::parse($endDateInput)->endOfDay()->toISOString();

            // Log the formatted dates for debugging
            Log::info('Date formatting', [
                'start_date_input' => $startDateInput,
                'end_date_input' => $endDateInput,
                'start_date_formatted' => $startDate,
                'end_date_formatted' => $endDate,
                'start_date_sql' => Carbon::parse($startDateInput)->startOfDay()->format('Y-m-d H:i:s'),
                'end_date_sql' => Carbon::parse($endDateInput)->endOfDay()->format('Y-m-d H:i:s')
            ]);

            // Get MongoDB count using the provided pipeline
            $mongodbCount = $this->getMongoDBCount_Patients($startDate, $endDate);

            // Get MSSQL count
            $mssqlCount = $this->getMSSQLCount_Patients($startDate, $endDate);

            // Log the counts for debugging
            Log::info('Validation counts', [
                'mongodb_count' => $mongodbCount,
                'mssql_count' => $mssqlCount,
                'difference' => $mongodbCount - $mssqlCount,
                'date_range' => $startDateInput . ' to ' . $endDateInput
            ]);

            // Calculate difference
            $difference = $mongodbCount - $mssqlCount;
            $isComplete = $difference === 0;

            // Get missing records analysis
            //$missingRecordsAnalysis = $this->getMissingRecordsAnalysis('patients', $startDate, $endDate, 50);

            $result = [
                'table' => 'patients',
                'mongodb_count' => $mongodbCount,
                'mssql_count' => $mssqlCount,
                'difference' => $difference,
                'is_complete' => $isComplete,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'validated_at' => now()->toISOString(),
                'status' => $isComplete ? 'COMPLETE' : 'INCOMPLETE',
                //'missing_records_analysis' => $missingRecordsAnalysis
            ];

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (Exception $e) {
            Log::error('Migration validation error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get count from MongoDB using table-specific pipeline
     */
    private function getMongoDBCount($startDate, $endDate, $tableName = 'patients')
    {
        try {
            // Increase execution time limit
            set_time_limit(300);

            Log::info('Starting MongoDB count query', [
                'table' => $tableName,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            $config = $this->migrationTables[$tableName];
            $pipeline = $this->getPipelineForTable($tableName, $startDate, $endDate);

            // Method 1: Try simple count first for simple pipelines
            if ($config['pipeline_type'] === 'simple') {
                try {
                    $startISODate = new \MongoDB\BSON\UTCDateTime(Carbon::parse($startDate)->timestamp * 1000);
                    $endISODate = new \MongoDB\BSON\UTCDateTime(Carbon::parse($endDate)->timestamp * 1000);

                    $count = DB::connection('mongodb')
                        ->collection($config['mongodb_collection'])
                        ->where($config['date_field_mongo'], '>=', $startISODate)
                        ->where($config['date_field_mongo'], '<=', $endISODate)
                        ->count();

                    Log::info('MongoDB simple count completed', ['table' => $tableName, 'count' => $count]);
                    return $count;

                } catch (Exception $countException) {
                    Log::warning('Simple count failed, trying aggregation', [
                        'table' => $tableName,
                        'error' => $countException->getMessage()
                    ]);
                }
            }

            // Method 2: Use aggregation pipeline
            $result = DB::connection('mongodb')
                ->collection($config['mongodb_collection'])
                ->raw(function ($collection) use ($pipeline) {
                    return $collection->aggregate($pipeline, [
                        'allowDiskUse' => true,
                        'maxTimeMS' => 300000, // 5 minutes
                        'batchSize' => 1000
                    ]);
                })->toArray();

            $count = isset($result[0]['Total']) ? $result[0]['Total'] : 0;
            Log::info('MongoDB aggregation count completed', ['table' => $tableName, 'count' => $count]);
            return $count;

        } catch (Exception $e) {
            Log::error('MongoDB count error', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);
            throw new Exception("Failed to get MongoDB count for {$tableName}: " . $e->getMessage());
        }
    }

    /**
     * Get count from MSSQL using table-specific configuration
     */
    private function getMSSQLCount($startDate, $endDate, $tableName = 'patients')
    {
        try {
            $config = $this->migrationTables[$tableName];

            // Convert to UTC timezone to match migration filter
            $startDateTime = Carbon::parse($startDate)->utc()->format('Y-m-d H:i:s');
            $endDateTime = Carbon::parse($endDate)->utc()->format('Y-m-d H:i:s');

            // Execute the SQL query - match the migration filter exactly
            /* $result = DB::connection('sqlsrv')
                ->select("SELECT COUNT(*) as total FROM {$config['mssql_table']} WHERE {$config['date_field_mssql']} >= '$startDateTime' AND {$config['date_field_mssql']} <= '$endDateTime'");
             */

            $result1 = DB::connection('sqlsrv')
                ->select("SELECT COUNT(*) as total FROM {$config['mssql_table']} WHERE (TRY_CONVERT(datetimeoffset, {$config['date_field_mssql']}, 127) AT TIME ZONE 'Singapore Standard Time') BETWEEn '$startDateTime' AND '$endDateTime'");
            $result2 = DB::connection('sqlsrv')
                ->select("
                    SELECT COUNT(DISTINCT {$config['identifier_field']}) AS total
                    FROM {$config['mssql_table']}
                    WHERE 
                        (TRY_CONVERT(datetimeoffset, {$config['date_field_mssql']}, 127) 
                         AT TIME ZONE 'Singapore Standard Time')
                        BETWEEN '$startDateTime' AND '$endDateTime'
                ");

            $result = DB::connection('sqlsrv')
                ->select("
                    SELECT COUNT(*) AS total
                    FROM (
                        SELECT DISTINCT {$config['identifier_field']}
                        FROM {$config['mssql_table']}
                        WHERE 
                            (TRY_CONVERT(datetimeoffset, {$config['date_field_mssql']}, 127) 
                            AT TIME ZONE 'Singapore Standard Time')
                            BETWEEN '$startDateTime' AND '$endDateTime'
                    ) AS sub
                ");


            return $result[0]->total ?? 0;

        } catch (Exception $e) {
            Log::error('MSSQL count error', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);
            throw new Exception("Failed to get MSSQL count for {$tableName}: " . $e->getMessage());
        }
    }

    /**
     * Get count from MSSQL (legacy method for patients)
     */
    private function getMSSQLCount_Paatients($startDate, $endDate)
    {
        try {
            // Convert to UTC timezone to match migration filter
            $startDateTime = Carbon::parse($startDate)->utc()->format('Y-m-d H:i:s');
            $endDateTime = Carbon::parse($endDate)->utc()->format('Y-m-d H:i:s');

            // Execute the SQL query - match the migration filter exactly
            $result = DB::connection('sqlsrv')
                ->select("SELECT COUNT(*) as total FROM patients WHERE modifieddate >= '$startDateTime' AND modifieddate <= '$endDateTime'");

            return $result[0]->total ?? 0;

        } catch (Exception $e) {
            Log::error('MSSQL count error: ' . $e->getMessage());
            throw new Exception('Failed to get MSSQL count: ' . $e->getMessage());
        }
    }

    /**
     * Debug MongoDB query for specific table
     */
    public function debugMongoQuery(Request $request)
    {
        try {
            $tableName = $request->input('table', 'careproviders');
            $startDateInput = $request->input('start_date', now()->format('Y-m-d'));
            $endDateInput = $request->input('end_date', now()->format('Y-m-d'));

            if (!isset($this->migrationTables[$tableName])) {
                return response()->json([
                    'success' => false,
                    'error' => "Table '{$tableName}' not configured"
                ], 400);
            }

            $config = $this->migrationTables[$tableName];
            $startDate = Carbon::parse($startDateInput)->startOfDay()->toISOString();
            $endDate = Carbon::parse($endDateInput)->endOfDay()->toISOString();

            $startISODate = new \MongoDB\BSON\UTCDateTime(Carbon::parse($startDate)->timestamp * 1000);
            $endISODate = new \MongoDB\BSON\UTCDateTime(Carbon::parse($endDate)->timestamp * 1000);

            // Get sample records to see what's being matched
            $sampleRecords = DB::connection('mongodb')
                ->collection($config['mongodb_collection'])
                ->where($config['date_field_mongo'], '>=', $startISODate)
                ->where($config['date_field_mongo'], '<=', $endISODate)
                ->limit(5)
                ->get()
                ->toArray();

            // Get total count
            $totalCount = DB::connection('mongodb')
                ->collection($config['mongodb_collection'])
                ->where($config['date_field_mongo'], '>=', $startISODate)
                ->where($config['date_field_mongo'], '<=', $endISODate)
                ->count();

            // Get all records with their dates for debugging
            $allRecords = DB::connection('mongodb')
                ->collection($config['mongodb_collection'])
                ->limit(20)
                ->get()
                ->toArray();

            $formattedRecords = [];
            foreach ($allRecords as $record) {
                if (isset($record[$config['date_field_mongo']])) {
                    $formattedRecords[] = [
                        'id' => $record['_id'] ?? 'N/A',
                        'date_field' => $config['date_field_mongo'],
                        'date_value' => $record[$config['date_field_mongo']]->toDateTime()->format('Y-m-d H:i:s'),
                        'date_iso' => $record[$config['date_field_mongo']]->toDateTime()->toISOString(),
                        'identifier' => $record[$config['identifier_field']] ?? 'N/A'
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'debug_info' => [
                    'table' => $tableName,
                    'config' => $config,
                    'date_range' => [
                        'start_input' => $startDateInput,
                        'end_input' => $endDateInput,
                        'start_iso' => $startDate,
                        'end_iso' => $endDate,
                        'start_mongo' => $startISODate->toDateTime()->format('Y-m-d H:i:s'),
                        'end_mongo' => $endISODate->toDateTime()->format('Y-m-d H:i:s')
                    ],
                    'total_count' => $totalCount,
                    'sample_matched_records' => $sampleRecords,
                    'all_records_sample' => $formattedRecords
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Debug failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug method to investigate date differences
     */
    public function debugDateRange(Request $request)
    {
        try {
            $startDateInput = $request->input('start_date', now()->format('Y-m-d'));
            $endDateInput = $request->input('end_date', now()->format('Y-m-d'));

            $startDate = Carbon::parse($startDateInput)->startOfDay()->toISOString();
            $endDate = Carbon::parse($endDateInput)->endOfDay()->toISOString();

            $startDateTime = Carbon::parse($startDateInput)->startOfDay()->format('Y-m-d H:i:s');
            $endDateTime = Carbon::parse($endDateInput)->endOfDay()->format('Y-m-d H:i:s');

            // Get sample records from MongoDB
            $mongoSamples = DB::connection('mongodb')
                ->collection('patients')
                ->where('modifiedat', '>=', new \MongoDB\BSON\UTCDateTime(Carbon::parse($startDate)->timestamp * 1000))
                ->where('modifiedat', '<=', new \MongoDB\BSON\UTCDateTime(Carbon::parse($endDate)->timestamp * 1000))
                ->limit(5)
                ->get()
                ->toArray();

            // Get sample records from MSSQL
            $mssqlSamples = DB::connection('sqlsrv')
                ->select("SELECT TOP 5 modifieddate FROM patients WHERE CONVERT(datetime,modifieddate) AT TIME ZONE 'UTC' AT TIME ZONE 'Singapore Standard Time' BETWEEN '$startDateTime' AND '$endDateTime'");

            return response()->json([
                'success' => true,
                'debug_info' => [
                    'date_range' => [
                        'start_input' => $startDateInput,
                        'end_input' => $endDateInput,
                        'mongo_start' => $startDate,
                        'mongo_end' => $endDate,
                        'sql_start' => $startDateTime,
                        'sql_end' => $endDateTime
                    ],
                    'mongo_samples' => $mongoSamples,
                    'mssql_samples' => $mssqlSamples,
                    'mongo_count' => $this->getMongoDBCount_Patients($startDate, $endDate),
                    'mssql_count' => $this->getMSSQLCount_Patients($startDate, $endDate)
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Debug failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to get missing records analysis for any table
     */
    private function getMissingRecordsAnalysis($tableName, $startDate, $endDate, $limit = 50)
    {
        try {
            // Increase execution time limit for large datasets
            set_time_limit(600); // 10 minutes
            
            $config = $this->migrationTables[$tableName];
            $startDateInput = Carbon::parse($startDate)->format('Y-m-d');
            $endDateInput = Carbon::parse($endDate)->format('Y-m-d');

            $startDateTime = Carbon::parse($startDateInput)->startOfDay()->format('Y-m-d H:i:s');
            $endDateTime = Carbon::parse($endDateInput)->endOfDay()->format('Y-m-d H:i:s');

            // Get pipeline from getPipelineForTable and modify it to return records instead of count
            $pipeline = $this->getPipelineForTable($tableName, $startDate, $endDate);

            // Remove the $count stage and replace with $limit if needed
            $pipeline = array_filter($pipeline, function ($stage) {
                return !isset($stage['$count']);
            });

            // Optionally add limit
            /* if ($limit > 0) {
                $pipeline[] = ['$limit' => $limit];
            } */

            // Execute aggregation pipeline to get records using cursor (prevents memory exhaustion)
            // Don't use toArray() - process via cursor to avoid loading all records into memory
            $mongoCursor = DB::connection('mongodb')
                ->collection($config['mongodb_collection'])
                ->raw(function ($collection) use ($pipeline) {
                    return $collection->aggregate($pipeline, [
                        'allowDiskUse' => true,
                        'maxTimeMS' => 300000, // 5 minutes
                        'batchSize' => 1000,
                        'cursor' => ['batchSize' => 1000]
                    ]);
                });

            // Get all MSSQL records for the date range
            $mssqlRecords = DB::connection('sqlsrv')
                ->select("
                    SELECT DISTINCT {$config['identifier_field']}, {$config['date_field_mssql']}
                    FROM {$config['mssql_table']}
                    WHERE 
                        (TRY_CONVERT(datetimeoffset, {$config['date_field_mssql']}, 127) 
                         AT TIME ZONE 'Singapore Standard Time')
                        BETWEEN '$startDate' AND '$endDate'
                ");

            // Create a lookup map of MSSQL identifier values for efficient matching
            $identifierField = $config['identifier_field'];
            $mssqlIdentifierMap = [];
            foreach ($mssqlRecords as $mssqlRecord) {
                $mssqlId = $mssqlRecord->{$identifierField} ?? null;
                if ($mssqlId !== null) {
                    // Convert to string for consistent comparison
                    $mssqlIdentifierMap[(string) $mssqlId] = $mssqlRecord;
                }
            }

            // Find MongoDB records that don't have matching MSSQL records
            // Process via cursor to avoid loading all records into memory at once
            $missingRecords = [];
            $foundMatches = 0;
            $mongoTotal = 0;
            $firstRecord = null;
            // Process MongoDB records one at a time using cursor (prevents memory exhaustion)
            foreach ($mongoCursor as $mongoRecord) {
                $mongoTotal++;
                
                // Store first record for the test (same as original code)
                if ($mongoTotal === 1) {
                    $firstRecord = $mongoRecord;
                }
                
                // Get identifier field value (same logic as original)
                $mongoIdTest = $this->getMongoField($mongoRecord, $config['mongodb_identifier_field']);
                //dd($mongoIdTest);

                $mongoId = $mongoRecord['_id'] ?? null;
                $mongoDate = Carbon::parse($mongoRecord['createdat']->toDateTime())->format('Y-m-d H:i:s');

                // Check if this MongoDB record exists in MSSQL by ID
                // Use pre-loaded map for fast in-memory lookup (same results, much faster than database queries)
                $hasMatch = false;
                $mssqlRecord = null;

                if ($mongoId) {
                    try {
                        // Convert MongoDB ObjectId to string if needed
                        $mongoIdString = (string) $mongoIdTest;//$mongoRecord[$config['mongodb_identifier_field']];// (string) $mongoRecord[$config['mongodb_identifier_field']];//is_object($mongoId) ? (string)$mongoId : $mongoId;

                        // Use in-memory map lookup instead of database query (produces same results, much faster)
                        if (isset($mssqlIdentifierMap[$mongoIdString])) {
                            $mssqlRecord = $mssqlIdentifierMap[$mongoIdString];
                            $hasMatch = true;
                            $foundMatches++;
                        }
                    } catch (Exception $e) {
                        Log::warning('Error checking MSSQL record existence', [
                            'mongo_id' => $mongoIdString ?? 'N/A',
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                if (!$hasMatch) {
                    //dd($mongoRecord);
                    $missingRecords[] = [
                        'mongo_id' => $mongoRecord['_id'] ?? 'N/A',
                        'mongo_id2' => (string) $mongoRecord['_id'],
                        'mongo_createdat' => $mongoDate,
                        'universal_id' => $mongoIdString,
                        'modifiedat' => $mongoRecord['modifiedat']->toDateTime()->format('Y-m-d H:i:s'),
                        'mongo_createdat_original' => $mongoRecord['createdat']->toDateTime()->format('Y-m-d H:i:s.u'),
                        'mssql_check_result' => $mssqlRecord ? 'Found but no match' : 'Not found in MSSQL',
                        /* 'patient_data' => [
                            'mrn' => $mongoRecord['mrn'] ?? 'N/A',
                            'id' => $mongoRecord['patient_id'] ?? $mongoRecord['id'] ?? 'N/A',
                            '_id' => $mongoRecord['_id'] ?? $mongoRecord['id'] ?? 'N/A',
                            'bedoccupancy_id' => $mongoRecord['bedoccupancy']['_id'] ?? $mongoRecord['id'] ?? 'N/A',
                            'firstname' => $mongoRecord['firstname'] ?? 'N/A',
                            'lastname' => $mongoRecord['lastname'] ?? 'N/A',
                            'email' => $mongoRecord['email'] ?? 'N/A',
                            'phone' => $mongoRecord['phone'] ?? 'N/A'
                        ] */
                    ];
                }
            }

            return [
                'mongo_total' => $mongoTotal, // Count as we process instead of count($mongoRecords)
                'mssql_total' => count($mssqlRecords),
                'found_matches' => $foundMatches,
                'missing_from_mssql' => count($missingRecords),
                'missing_records' => $missingRecords,
                "sql" => "SELECT {$config['date_field_mssql']} FROM {$config['mssql_table']} WHERE (TRY_CONVERT(datetimeoffset, {$config['date_field_mssql']}, 127) AT TIME ZONE 'Singapore Standard Time') BETWEEN '$startDateTime' AND '$endDateTime'"
            ];

        } catch (Exception $e) {
            Log::error('Missing records analysis error', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    function getMongoField($record, $path)
    {
        $keys = explode('.', $path);

        foreach ($keys as $key) {
            if (!isset($record[$key]))
                return null;
            $record = $record[$key];
        }

        return $record;
    }

    /**
     * Find records that exist in MSSQL but not in MongoDB
     */
    public function findExtraRecords(Request $request)
    {
        try {
            $startDateInput = $request->input('start_date', now()->format('Y-m-d'));
            $endDateInput = $request->input('end_date', now()->format('Y-m-d'));
            $limit = $request->input('limit', 50);

            $startDate = Carbon::parse($startDateInput)->startOfDay()->toISOString();
            $endDate = Carbon::parse($endDateInput)->endOfDay()->toISOString();

            $startDateTime = Carbon::parse($startDateInput)->startOfDay()->format('Y-m-d H:i:s');
            $endDateTime = Carbon::parse($endDateInput)->endOfDay()->format('Y-m-d H:i:s');

            // Get all MongoDB records for the date range
            $mongoRecords = DB::connection('mongodb')
                ->collection('patients')
                ->where('modifiedat', '>=', new \MongoDB\BSON\UTCDateTime(Carbon::parse($startDate)->timestamp * 1000))
                ->where('modifiedat', '<=', new \MongoDB\BSON\UTCDateTime(Carbon::parse($endDate)->timestamp * 1000))
                ->get()
                ->toArray();

            // Get all MSSQL records for the date range
            $mssqlRecords = DB::connection('sqlsrv')
                ->select("SELECT TOP $limit modifieddate FROM patients WHERE CONVERT(datetime,modifieddate) AT TIME ZONE 'UTC' AT TIME ZONE 'Singapore Standard Time' BETWEEN '$startDateTime' AND '$endDateTime'");

            // Convert MongoDB dates to comparable format
            $mongoDates = array_map(function ($record) {
                return Carbon::parse($record['modifiedat']->toDateTime())->format('Y-m-d H:i:s');
            }, $mongoRecords);

            // Find MSSQL records that don't have matching MongoDB records
            $extraRecords = [];
            $foundMatches = 0;

            foreach ($mssqlRecords as $mssqlRecord) {
                $mssqlDate = Carbon::parse($mssqlRecord->modifieddate)->format('Y-m-d H:i:s');

                // Check if this MSSQL record has a corresponding MongoDB record
                $hasMatch = false;
                foreach ($mongoDates as $mongoDate) {
                    if ($mongoDate === $mssqlDate) {
                        $hasMatch = true;
                        $foundMatches++;
                        break;
                    }
                }

                if (!$hasMatch) {
                    $extraRecords[] = [
                        'mssql_date' => $mssqlDate,
                        'mssql_modifieddate' => $mssqlRecord->modifieddate
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'analysis' => [
                    'date_range' => $startDateInput . ' to ' . $endDateInput,
                    'mongo_total' => count($mongoRecords),
                    'mssql_total' => count($mssqlRecords),
                    'found_matches' => $foundMatches,
                    'extra_in_mssql' => count($extraRecords),
                    'extra_records' => $extraRecords
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Analysis failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
