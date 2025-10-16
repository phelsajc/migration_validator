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
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => 'mrn',
            'pipeline_type' => 'simple'
        ],
        'careproviders' => [
            'mongodb_collection' => 'users',
            'mssql_table' => 'careprovider',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => 'code',
            'pipeline_type' => 'complex'
        ],
        'patientorderitems' => [
            'mongodb_collection' => 'patientorders',
            'mssql_table' => 'patientorderitems',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
            'pipeline_type' => 'complex'
        ],
        'bedoccupancy' => [
            'mongodb_collection' => 'patientvisits',
            'mssql_table' => 'bedoccupancy',
            'date_field_mongo' => 'modifiedat',
            'date_field_mssql' => 'modifieddate',
            'identifier_field' => '_id',
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
            'mssql_table' => 'patientbilldeductions',
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
                        '$match' => [
                            'iscareprovider' => true,
                            $config['date_field_mongo'] => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ]
                        ]
                    ],
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
                        '$count' => 'Total'
                    ],
                ];

            case 'patientorderitems':
                return [
                    [
                        '$match' => [
                            $config['date_field_mongo'] => [
                                '$gte' => $startISODate,
                                '$lte' => $endISODate
                            ],
                        ]
                    ],
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
                        '$count' => 'Total'
                    ],
                ];
            case 'bedoccupancy':
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
                        '$match' => [
                            'bedoccupancy' => [
                                '$exists' => true,
                                '$ne' => []
                            ]
                        ]
                    ],
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
                            'foreignField' =>  'associatedpayoragreementsandtpa.tpauid',
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

            // Log the counts for debugging
            Log::info('Validation counts', [
                'table' => $tableName,
                'mongodb_count' => $mongodbCount,
                'mssql_count' => $mssqlCount,
                'difference' => $mongodbCount - $mssqlCount,
                'date_range' => $startDateInput . ' to ' . $endDateInput
            ]);

            // Calculate difference
            $difference = $mongodbCount - $mssqlCount;
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
                'status' => $isComplete ? 'COMPLETE' : 'INCOMPLETE'
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

            $result = [
                'table' => 'patients',
                'mongodb_count' => $mongodbCount,
                'mssql_count' => $mssqlCount,
                'difference' => $difference,
                'is_complete' => $isComplete,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'validated_at' => now()->toISOString(),
                'status' => $isComplete ? 'COMPLETE' : 'INCOMPLETE'
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
            $result = DB::connection('sqlsrv')
                ->select("SELECT COUNT(*) as total FROM {$config['mssql_table']} WHERE {$config['date_field_mssql']} >= '$startDateTime' AND {$config['date_field_mssql']} <= '$endDateTime'");

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
    private function getMSSQLCount_Patients($startDate, $endDate)
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
     * Find records that exist in MongoDB but not in MSSQL
     */
    public function findMissingRecords(Request $request)
    {
        try {
            $startDateInput = $request->input('start_date', now()->format('Y-m-d'));
            $endDateInput = $request->input('end_date', now()->format('Y-m-d'));
            $limit = $request->input('limit', 50); // Limit results for performance

            $startDate = Carbon::parse($startDateInput)->startOfDay()->toISOString();
            $endDate = Carbon::parse($endDateInput)->endOfDay()->toISOString();

            $startDateTime = Carbon::parse($startDateInput)->startOfDay()->format('Y-m-d H:i:s');
            $endDateTime = Carbon::parse($endDateInput)->endOfDay()->format('Y-m-d H:i:s');

            // Get all MongoDB records for the date range
            $mongoRecords = DB::connection('mongodb')
                ->collection('patients')
                ->where('modifiedat', '>=', new \MongoDB\BSON\UTCDateTime(Carbon::parse($startDate)->timestamp * 1000))
                ->where('modifiedat', '<=', new \MongoDB\BSON\UTCDateTime(Carbon::parse($endDate)->timestamp * 1000))
                //->limit($limit)
                ->get()
                ->toArray();

            // Get all MSSQL records for the date range
            $mssqlRecords = DB::connection('sqlsrv')
                ->select("SELECT modifieddate FROM patients WHERE CONVERT(datetime,modifieddate) AT TIME ZONE 'UTC' AT TIME ZONE 'Singapore Standard Time' BETWEEN '$startDateTime' AND '$endDateTime'");

            // Convert MSSQL dates to comparable format
            $mssqlDates = array_map(function ($record) {
                return Carbon::parse($record->modifieddate)->format('Y-m-d H:i:s');
            }, $mssqlRecords);

            // Find MongoDB records that don't have matching MSSQL records
            $missingRecords = [];
            $foundMatches = 0;

            foreach ($mongoRecords as $mongoRecord) {
                $mongoDate = Carbon::parse($mongoRecord['modifiedat']->toDateTime())->format('Y-m-d H:i:s');

                // Check if this MongoDB record has a corresponding MSSQL record
                $hasMatch = false;
                foreach ($mssqlDates as $mssqlDate) {
                    // Allow for small time differences (within 1 second)
                    if (abs(Carbon::parse($mongoDate)->diffInSeconds(Carbon::parse($mssqlDate))) <= 1) {
                        $hasMatch = true;
                        $foundMatches++;
                        break;
                    }
                }

                if (!$hasMatch) {
                    $missingRecords[] = [
                        'mongo_id' => $mongoRecord['_id'] ?? 'N/A',
                        'mongo_modifiedat' => $mongoDate,
                        'mongo_modifiedat_original' => $mongoRecord['modifiedat']->toDateTime()->format('Y-m-d H:i:s.u'),
                        'patient_data' => [
                            'mrn' => $mongoRecord['mrn'] ?? 'N/A',
                            /* 'email' => $mongoRecord['email'] ?? 'N/A',
                            'phone' => $mongoRecord['phone'] ?? 'N/A',
                            'id' => $mongoRecord['patient_id'] ?? $mongoRecord['id'] ?? 'N/A' */
                        ]
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
                    'missing_from_mssql' => count($missingRecords),
                    'missing_records' => $missingRecords
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Analysis failed: ' . $e->getMessage()
            ], 500);
        }
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
                    // Allow for small time differences (within 1 second)
                    if (abs(Carbon::parse($mssqlDate)->diffInSeconds(Carbon::parse($mongoDate))) <= 1) {
                        $hasMatch = true;
                        $foundMatches++;
                        break;
                    }
                }

                if (!$hasMatch) {
                    $extraRecords[] = [
                        'mssql_modifieddate' => $mssqlDate,
                        'mssql_modifieddate_original' => $mssqlRecord->modifieddate
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

    /**
     * Get list of available tables for validation
     */
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
     * Get validation history
     */
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

    /**
     * Validate multiple tables at once
     */
    public function validateAllTables(Request $request)
    {
        try {
            // Handle both GET and POST requests with proper date formatting
            $startDateInput = $request->input('start_date', $request->query('start_date', now()->format('Y-m-d')));
            $endDateInput = $request->input('end_date', $request->query('end_date', now()->format('Y-m-d')));

            // Format dates properly for MongoDB
            $startDate = Carbon::parse($startDateInput)->startOfDay()->toISOString();
            $endDate = Carbon::parse($endDateInput)->endOfDay()->toISOString();

            // Get all configured tables
            $tables = array_keys($this->migrationTables);
            $results = [];

            foreach ($tables as $table) {
                try {
                    $mongodbCount = $this->getMongoDBCount($startDate, $endDate, $table);
                    $mssqlCount = $this->getMSSQLCount($startDate, $endDate, $table);
                    $difference = $mongodbCount - $mssqlCount;

                    $results[] = [
                        'table' => $table,
                        'mongodb_count' => $mongodbCount,
                        'mssql_count' => $mssqlCount,
                        'difference' => $difference,
                        'is_complete' => $difference === 0,
                        'status' => $difference === 0 ? 'COMPLETE' : 'INCOMPLETE'
                    ];
                } catch (Exception $tableException) {
                    Log::error('Table validation failed', [
                        'table' => $table,
                        'error' => $tableException->getMessage()
                    ]);

                    $results[] = [
                        'table' => $table,
                        'mongodb_count' => 0,
                        'mssql_count' => 0,
                        'difference' => 0,
                        'is_complete' => false,
                        'status' => 'ERROR',
                        'error' => $tableException->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'validations' => $results,
                    'summary' => [
                        'total_tables' => count($tables),
                        'complete_tables' => count(array_filter($results, function ($r) {
                            return $r['is_complete'];
                        })),
                        'incomplete_tables' => count(array_filter($results, function ($r) {
                            return !$r['is_complete'] && !isset($r['error']);
                        })),
                        'error_tables' => count(array_filter($results, function ($r) {
                            return isset($r['error']);
                        }))
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Validate all tables error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
