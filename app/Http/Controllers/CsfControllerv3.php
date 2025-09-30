<?php

namespace App\Http\Controllers;
use FPDF;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CsfControllerv3 extends FPDF
{
    private $data;
    private $widths;
    private $aligns;

    public function __construct($data = [])
    {
        $this->data = $data;

        // Set the size to 4 by 6 inches (converted to mm)
        $width = 80; // 6 inches to mm (landscape width)
        $height = 200; // 4 inches to mm (landscape height)

        parent::__construct('P', 'mm', array(216, 330)); // Portrait, mm, long bond paper
        $this->SetTitle("Patient Intake Form", true);
        $this->SetAuthor("TJGazel", true);
        $this->SetMargins(8, 8, 8);
        $this->AddPage();
        $this->drawPageBorder(); // Add border to the page
        $this->Body();

    }

    public static function generateCSF(Request $request)
    {
        $visitId = $request->input('visitId');

        if (empty($visitId)) {
            return redirect()->route('oecb.show')->with('error', 'Visit ID is required.');
        }

        // Get patient details using the MongoDB pipeline
        $patientVisitDetails = self::getPatientVisitDetails($visitId);
        // Create CsfControllerv3 instance
        $pdf = new self($patientVisitDetails);

        // Output PDF and return response
        $pdf->Output('D', 'CSF_' . $visitId . '_' . date('Ymd') . '.pdf');

        // This line will never be reached due to Output() above, but satisfies return type
        return response()->make('', 200);
    }

    /**
     * Get patient visit details using the provided pipeline
     *
     * @param string $visitId
     * @return array
     */
    private static function getPatientVisitDetails($visitId)
    {
        try {
            // Your comprehensive pipeline
            $pipeline = [
                [
                    '$unwind' => [
                        'path' => '$visitpayors',
                        'preserveNullAndEmptyArrays' => true
                    ]
                ],
                [
                    '$lookup' => [
                        'from' => 'patients',
                        'localField' => 'patientuid',
                        'foreignField' => '_id',
                        'as' => 'pxDetails'
                    ]
                ],
                [
                    '$unwind' => [
                        'path' => '$pxDetails',
                        'preserveNullAndEmptyArrays' => true
                    ]
                ],
                [
                    '$lookup' => [
                        'from' => "users",
                        'let' => ['careProviders' => '$visitcareproviders'],
                        'pipeline' => [
                            [
                                '$match' => [
                                    '$expr' => [
                                        '$in' => [
                                            '$_id',
                                            [
                                                '$map' => [
                                                    'input' => [
                                                        '$filter' => [
                                                            'input' => '$$careProviders',
                                                            'as' => 'cp',
                                                            'cond' => ['$eq' => ['$$cp.isprimarycareprovider', true]]
                                                        ]
                                                    ],
                                                    'as' => 'fcp',
                                                    'in' => '$$fcp.careprovideruid'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'as' => "primaryCareProviderDetails"
                    ]
                ],
                [
                    '$lookup' => [
                        'from' => 'patientadditionaldetails',
                        'localField' => 'patientuid',
                        'foreignField' => 'patientuid',
                        'as' => 'pxAddDetails'
                    ]
                ],
                [
                    '$lookup' => [
                        'from' => 'referencevalues',
                        'localField' => 'visitpayors.memberpolicydetail.reltypeuid',
                        'foreignField' => '_id',
                        'as' => 'relDetails'
                    ]
                ],
                [
                    '$match' => [
                        '_id' => new \MongoDB\BSON\ObjectId($visitId),
                        //'visitid' => "EP25008526",
                        'visitpayors.payoruid' => new \MongoDB\BSON\ObjectId('6787bb3a0cef1a1315d6e043')
                        //'visitpayors.payoruid' => new \MongoDB\BSON\ObjectId('679971070cef1a1315a99303')
                        
                    ]
                ],
                [
                    '$project' => [
                        '_id' => 1,
                        'memberfname' => '$visitpayors.memberpolicydetail.firstname',
                        'membermname' => '$visitpayors.memberpolicydetail.middlename',
                        'memberlname' => '$visitpayors.memberpolicydetail.lastname',
                        'membersuffix' => '$visitpayors.memberpolicydetail.suffix',
                        'memberpin' => '$visitpayors.memberpolicydetail.memberpin',
                        'patientuid' => 1,
                        'memberdateofbirth' => [
                            '$ifNull' => [
                                [
                                    '$dateToString' => [
                                        'format' => "%Y-%m-%d",
                                        'date' => '$visitpayors.memberpolicydetail.dateofbirth'
                                    ]
                                ],
                                ""
                            ]
                        ],
                        'dependentfname' => '$pxDetails.firstname',
                        'dependentmname' => '$pxDetails.middlename',
                        'dependentlname' => '$pxDetails.lastname',
                        'dependentsuffix' => '$pxDetails.thirdname',
                        'dependentdateofbirth' => [
                            '$ifNull' => [
                                [
                                    '$dateToString' => [
                                        'format' => "%Y-%m-%d",
                                        'date' => '$pxDetails.dateofbirth'
                                    ]
                                ],
                                ""
                            ]
                        ],
                        'doctor' => ['$arrayElemAt' => ['$primaryCareProviderDetails.printname', 0]],
                        'relationshipToMember' => ['$arrayElemAt' => ['$relDetails.valuedescription', 0]],
                        'accreditation' => [
                            '$getField' => [
                                'field' => "iddetail",
                                'input' => [
                                    '$arrayElemAt' => [
                                        [
                                            '$filter' => [
                                                'input' => [
                                                    '$reduce' => [
                                                        'input' => '$primaryCareProviderDetails',
                                                        'initialValue' => [],
                                                        'in' => ['$concatArrays' => ['$$value', '$$this.useridentifiers']]
                                                    ]
                                                ],
                                                'as' => "pcp",
                                                'cond' => ['$eq' => ['$$pcp.idtypeuid', new \MongoDB\BSON\ObjectId("5f7beab391e9464d27fae7e6")]]
                                            ]
                                        ],
                                        0
                                    ]
                                ]
                            ]
                        ],
                        'admissiondt' => [
                            '$ifNull' => [
                                [
                                    '$dateToString' => [
                                        'format' => "%Y-%m-%d",
                                        'date' => '$startdate'
                                    ]
                                ],
                                ""
                            ]
                        ],
                        'dischdt' => [
                            '$ifNull' => [
                                [
                                    '$dateToString' => [
                                        'format' => "%Y-%m-%d",
                                        'date' => '$medicaldischargedate'
                                    ]
                                ],
                                ""
                            ]
                        ],
                        'dependentpin' => [
                            '$ifNull' => [
                                [
                                    '$getField' => [
                                        'field' => "iddetail",
                                        'input' => [
                                            '$arrayElemAt' => [
                                                [
                                                    '$filter' => [
                                                        'input' => [
                                                            '$reduce' => [
                                                                'input' => '$pxAddDetails',
                                                                'initialValue' => [],
                                                                'in' => ['$concatArrays' => ['$$value', '$$this.addlnidentifiers']]
                                                            ]
                                                        ],
                                                        'as' => "pcp",
                                                        'cond' => ['$eq' => ['$$pcp.idtypeuid', new \MongoDB\BSON\ObjectId("67fc5e8002ae6de42f7d4799")]]
                                                    ]
                                                ],
                                                0
                                            ]
                                        ]
                                    ]

                                ],
                                '000000000000' // default if null or not found
                            ]
                        ]
                    ]
                ]
            ];

            $result = DB::connection('mongodb')
                ->collection('patientvisits')
                ->raw(function ($collection) use ($pipeline) {
                    return $collection->aggregate($pipeline, ['allowDiskUse' => true]);
                })->toArray();

            if (!empty($result)) {
                $data = $result[0];

                // Convert BSONDocument to array if needed
                if ($data instanceof \MongoDB\Model\BSONDocument) {
                    $data = iterator_to_array($data);
                }

                return $data;
            }

            return [];
        } catch (\Exception $e) {
            \Log::error('Error in getPatientVisitDetails: ' . $e->getMessage());
            return [];
        }
    }

    function getDataValue($key, $default = '')
    {
        return $this->data[$key] ?? $default;
    }

    function formatDateForDisplay($dateString)
    {
        if (empty($dateString)) {
            return '';
        }

        try {
            $date = new \DateTime($dateString);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return '';
        }
    }

    function getMemberPIN()
    {
        return $this->getDataValue('memberpin');
    }

    function getDependentPIN()
    {
        return $this->getDataValue('dependentpin');
    }

    function getMemberDateOfBirth()
    {
        return $this->getDataValue('memberdateofbirth');
    }

    function getPatientDateOfBirth()
    {
        return $this->getDataValue('dependentdateofbirth');
    }

    function getAccreditation()
    {
        return $this->getDataValue('accreditation');
    }

    function DrawAccreditationBoxes($accreditation, $x, $y)
    {
        $this->SetXY($x, $y);

        if (empty($accreditation)) {
            // Draw empty boxes
            for ($i = 0; $i < 4; $i++) {
                $this->Cell(4.0, 4.0, '', 1, 0);
            }
            $this->Cell(4, 4.5, '-', 0, 0, 'C');
            for ($i = 0; $i < 7; $i++) {
                $this->Cell(4.0, 4.0, '', 1, 0);
            }
            $this->Cell(4, 4.5, '-', 0, 0, 'C');
            $this->Cell(4.0, 4.0, '', 1, 0);
            return;
        }

        // Remove any non-numeric characters
        $accreditation = preg_replace('/[^0-9]/', '', $accreditation);

        // Format: XXXX-XXXXXXX-X
        $accreditationArray = str_split($accreditation);

        // Draw first 4 digits
        for ($i = 0; $i < 4; $i++) {
            $this->Cell(4.0, 4.0, $accreditationArray[$i] ?? '', 1, 0, 'C');
        }
        $this->Cell(4, 4.5, '-', 0, 0, 'C');

        // Draw next 7 digits
        for ($i = 4; $i < 11; $i++) {
            $this->Cell(4.0, 4.0, $accreditationArray[$i] ?? '', 1, 0, 'C');
        }
        $this->Cell(4, 4.5, '-', 0, 0, 'C');

        // Draw last digit
        $this->Cell(4.0, 4.0, $accreditationArray[11] ?? '', 1, 0, 'C');
    }

    function DrawPINBoxes($pin, $x, $y)
    {
        $this->SetXY($x, $y);

        if (empty($pin)) {
            // Draw empty boxes
            for ($i = 0; $i < 2; $i++) {
                $this->Cell(4.0, 4.0, '', 1, 0);
            }
            $this->Cell(4, 4.5, '-', 0, 0, 'C');
            for ($i = 0; $i < 9; $i++) {
                $this->Cell(4.0, 4.0, '', 1, 0);
            }
            $this->Cell(4, 4.5, '-', 0, 0, 'C');
            for ($i = 0; $i < 1; $i++) {
                $this->Cell(4.0, 4.0, '', 1, 0);
            }
            return;
        }

        // Remove any non-numeric characters
        $pin = preg_replace('/[^0-9]/', '', $pin);

        // Format: XX-XXXXXXXX-X
        $pinArray = str_split($pin);

        // Draw first 2 digits
        for ($i = 0; $i < 2; $i++) {
            $this->Cell(4.0, 4.0, $pinArray[$i] ?? '', 1, 0, 'C');
        }
        $this->Cell(4, 4.5, '-', 0, 0, 'C');

        // Draw next 9 digits
        for ($i = 2; $i < 11; $i++) {
            $this->Cell(4.0, 4.0, $pinArray[$i] ?? '', 1, 0, 'C');
        }
        $this->Cell(4, 4.5, '-', 0, 0, 'C');

        // Draw last digit
        $this->Cell(4.0, 4.0, $pinArray[11] ?? '', 1, 0, 'C');
    }

    function DrawDateBoxes($dateString, $x, $y, $format = 'Y-m-d')
    {
        $this->SetXY($x, $y);

        if (empty($dateString)) {
            // Draw empty boxes
            for ($i = 0; $i < 2; $i++) {
                $this->Cell(4.0, 4.0, '', 1, 0);
            }
            $this->Cell(3, 5, '-', 0, 0, 'C');
            for ($i = 0; $i < 2; $i++) {
                $this->Cell(4.0, 4.0, '', 1, 0);
            }
            $this->Cell(3, 5, '-', 0, 0, 'C');
            for ($i = 0; $i < 4; $i++) {
                $this->Cell(4.0, 4.0, '', 1, 0);
            }
            return;
        }

        try {
            $date = new \DateTime($dateString);
            $month = $date->format('m');
            $day = $date->format('d');
            $year = $date->format('Y');

            // Draw month boxes
            for ($i = 0; $i < 2; $i++) {
                $this->Cell(4.0, 4.0, $month[$i], 1, 0, 'C');
            }
            $this->Cell(3, 5, '-', 0, 0, 'C');

            // Draw day boxes
            for ($i = 0; $i < 2; $i++) {
                $this->Cell(4.0, 4.0, $day[$i], 1, 0, 'C');
            }
            $this->Cell(3, 5, '-', 0, 0, 'C');

            // Draw year boxes
            for ($i = 0; $i < 4; $i++) {
                $this->Cell(4.0, 4.0, $year[$i], 1, 0, 'C');
            }
        } catch (\Exception $e) {
            // If date parsing fails, draw empty boxes
            for ($i = 0; $i < 2; $i++) {
                $this->Cell(4.0, 4.0, '', 1, 0);
            }
            $this->Cell(3, 5, '-', 0, 0, 'C');
            for ($i = 0; $i < 2; $i++) {
                $this->Cell(4.0, 4.0, '', 1, 0);
            }
            $this->Cell(3, 5, '-', 0, 0, 'C');
            for ($i = 0; $i < 4; $i++) {
                $this->Cell(4.0, 4.0, '', 1, 0);
            }
        }
    }

    public function Header()
    {
        $this->Image(public_path() . '\images\csfheader.png', 24, 5, 173, 15);
        $this->Ln(13.5);
        // Set font for the header
        $this->SetFont('Arial', 'B', 6);
        $this->Cell(5, 5, 'IMPORTANT REMINDERS:', 0, 0);
        $this->SetFont('Arial', '', 6);
        $this->Cell(105, 5, '', 0, 0, 'L');

        $this->Cell(10, 5, '', 0, 0, 'L');
        $this->Cell(10, 5, 'Series #', 0, 0, 'L');

        for ($i = 0; $i < 13; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(0, 5, '', 0, 1);

        $this->SetFont('Arial', '', 6);
        $this->Cell(5, 3.5, 'PLEASE WRITE IN CAPITAL LETTERS AND CHECK THE APPROPRIATE BOXES.', 0, 1, 'L');
        $this->Cell(5, 3.5, 'All information required in this form are necessary. Claim forms with incomplete information shall not be processed.', 0, 1, 'L');
        $this->SetFont('Arial', 'B', 6);
        $this->Cell(5, 3.5, 'FALSE/INCORRECT INFORMATION OR MISREPRESENTATION SHALL BE SUBJECT TO CRIMINAL, CIVIL OR ADMINISTRATIVE LIABILITIES.', 0, 1, 'L');
        $this->Ln(2);
    }

    function DrawBoxedText($label, $boxes, $x, $y, $w = 6, $h = 6)
    {
        $this->SetXY($x, $y);
        $this->SetFont('Arial', '', 6);
        $this->Cell(0, 5, $label, 0, 1);

        $this->SetX($x);
        for ($i = 0; $i < $boxes; $i++) {
            $this->Cell($w, $h, '', 1, 0);
        }
    }
    public function AssessForm()
    {
        $this->SetFont('Arial', 'B', 8);

        // Title
        // Set black background
        $this->SetFillColor(0, 0, 0);     // RGB: black
        $this->SetTextColor(255, 255, 255); // white text

        $this->Cell(
            0,
            4,
            'PART I - MEMBER AND PATIENT INFORMATION AND CERTIFICATION',
            1,   // border
            1,   // line break
            'C', // center
            true // fill with background color
        );

        // Reset colors back to default (black text, no fill)
        $this->SetTextColor(0, 0, 0);
        $this->SetFillColor(255, 255, 255);


        // Row 1
        $this->SetFont('Arial', 'B', 6);
        $this->Ln(2);
        // 1. PhilHealth Identification Number (PIN) of Member
        $this->Cell(55, 6, '1. PhilHealth Identification Number (PIN) of Member:', 0, 0);
        $this->SetFont('Arial', 'B', 6);

        // Get member PIN from data
        $memberPIN = $this->getMemberPIN();
        $this->DrawPINBoxes($memberPIN, $this->GetX(), $this->GetY() + 2);


        $this->Ln();

        // Row 2 + 3
        // Row header
        $this->Cell(148, 4, '2. Name of Member:', 0, 0);
        $this->Cell(60, 4, '3. Member Date of Birth:', 0, 1);

        // --- Name underlines row ---

        // Extract member name data from pipeline
        $memberFirstName = strtoupper($this->getDataValue('memberfname'));
        $memberMiddleName = strtoupper($this->getDataValue('membermname'));
        $memberLastName = strtoupper($this->getDataValue('memberlname'));
        $extension = strtoupper($this->getDataValue('membersuffix')); // Not available in current data structure

        $nameFields = [
            ['w' => 35, 'label' => 'Last Name', 'value' => $memberLastName],
            ['w' => 40, 'label' => 'First Name', 'value' => $memberFirstName],
            ['w' => 15, 'label' => 'Extension (JR/SR/III)', 'value' => $extension],
            ['w' => 35, 'label' => 'Middle Name', 'value' => $memberMiddleName],
        ];

        $this->SetFont('Arial', '', 6);

        // --- Draw underline row with values ---
        foreach ($nameFields as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($nameFields) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between fields
            }
        }

        // --- DOB boxes row ---
        $this->Cell(8); // spacing between name fields and DOB

        // Get member date of birth from data
        $memberDOB = $this->getMemberDateOfBirth();
        $this->DrawDateBoxes($memberDOB, $this->GetX(), $this->GetY() + 2);
        $this->Ln();

        // --- Labels row ---
        foreach ($nameFields as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C');
            if ($i < count($nameFields) - 1) {
                $this->Cell(5, 3.5, '', 0, 0);
            }
        }

        // DOB labels under the boxes
        $this->Cell(7.5);
        $this->Cell(10, 6, 'month', 0, 0, 'C');   // under 2 boxes
        $this->Cell(3.5); // under dash
        $this->Cell(10, 6, 'day', 0, 0, 'C');   // under 2 boxes
        $this->Cell(2.5); // under dash
        $this->Cell(20, 6, 'year', 0, 1, 'C'); // under 4 boxes
        $this->Cell(20, 1, '', 0, 1, 'C'); // under 4 boxes
        $this->SetFont('Arial', 'B', 6);

        $this->Cell(60, 6, '4. PhilHealth Identification Number (PIN) of Dependent:', 0, 0);

        // Get dependent PIN from data
        $dependentPIN = $this->getDependentPIN();
        $this->DrawPINBoxes($dependentPIN, $this->GetX(), $this->GetY() + 2);
        $this->Ln(5);

        $this->Cell(148, 4, '5. Name of Patient:', 0, 0);
        $this->Cell(60, 4, '6. Relationship To Member:', 0, 1);



        // Extract patient (dependent) name data from pipeline
        $dependentFirstName = strtoupper($this->getDataValue('dependentfname'));
        $dependentMiddleName = strtoupper($this->getDataValue('dependentmname'));
        $dependentLastName = strtoupper($this->getDataValue('dependentlname'));
        $dependentextension = strtoupper($this->getDataValue('dependentsuffix'));// Not available in current data structure
        $relationshipToMember = strtoupper($this->getDataValue('relationshipToMember'));
        $nameFields = [
            ['w' => 35, 'label' => 'Last Name', 'value' => $dependentLastName],
            ['w' => 40, 'label' => 'First Name', 'value' => $dependentFirstName],
            ['w' => 15, 'label' => 'Extension (JR/SR/III)', 'value' => $dependentextension],
            ['w' => 35, 'label' => 'Middle Name', 'value' => $dependentMiddleName],
        ];

        $this->SetFont('Arial', '', 6);

        // --- Draw underline row with values ---
        foreach ($nameFields as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($nameFields) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between fields
            }
        }

        $this->Cell(8); // spacing between name fields and DOB

        // MM boxes
        for ($i = 0; $i < 1; $i++) {
            if ($relationshipToMember == 'SON' || $relationshipToMember == 'DAUGHTER') {
                $this->Cell(4.0, 4.0, 'x', 1, 0);
            } else {
                $this->Cell(4.0, 4.0, '', 1, 0);
            }
        }
        $this->Cell(7, 5, 'child', 0, 0, 'L');
        $this->Cell(3, 5, '', 0, 0);

        // DD boxes
        for ($i = 0; $i < 1; $i++) {
            if ($relationshipToMember == 'FATHER' || $relationshipToMember == 'MOTHER') {
                $this->Cell(4.5, 4, 'x', 1, 0);
            } else {
                $this->Cell(4.5, 4, '', 1, 0);
            }
        }
        $this->Cell(9, 5, 'parent', 0, 0, 'L');
        $this->Cell(3, 5, '', 0, 0);
        // YYYY boxes
        for ($i = 0; $i < 1; $i++) {
            if ($relationshipToMember == 'SPOUSE') {
                $this->Cell(4.5, 4, 'x', 1, 0);
            } else {
                $this->Cell(4.5, 4, '', 1, 0);
            }
            $this->Cell(7, 5, 'spouse', 0, 0, 'L');
        }
        $this->Ln();

        // --- Labels row ---


        foreach ($nameFields as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($nameFields) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between labels
            }
        }
        $this->Cell(4.5, 3.5, '', 0, 1);
        $this->SetFont('Arial', 'B', 6);
        $this->Cell(148, 4, '7. Confinement Period:', 0, 0);

        // 8. Patient Date of Birth
        $this->Cell(28, 4, '8. Patient Date of Birth:', 0, 1);

        $datefields1 = [
            ['w' => 9, 'label' => 'Month'],
            ['w' => 6, 'label' => 'Day'],
            ['w' => 16, 'label' => 'Year'],

        ];

        $datefields2 = [
            ['w' => 9, 'label' => 'Month'],
            ['w' => 6, 'label' => 'Day'],
            ['w' => 16, 'label' => 'Year'],

        ];


        $datefields3 = [
            ['w' => 9, 'label' => 'Month'],
            ['w' => 6, 'label' => 'Day'],
            ['w' => 16, 'label' => 'Year'],

        ];


        $this->SetFont('Arial', '', 6);
        $this->Cell(25, 6, 'a. Date Admitted:', 0, 0);

        // Get admission date from data
        $admissionDate = $this->formatDateForDisplay($this->getDataValue('admissiondt'));
        $this->DrawDateBoxes($admissionDate, $this->GetX(), $this->GetY());

        $this->Cell(5, 6, '', 0, 0);
        $this->Cell(28, 6, 'b. Date Discharged:', 0, 0);

        // Get discharge date from data
        $dischargeDate = $this->formatDateForDisplay($this->getDataValue('dischdt'));
        $this->DrawDateBoxes($dischargeDate, $this->GetX(), $this->GetY());

        $this->Cell(8, 5, '', 0, 0);

        // Get patient date of birth from data
        $patientDOB = $this->getPatientDateOfBirth();
        $this->DrawDateBoxes($patientDOB, $this->GetX(), $this->GetY());
        $this->ln();
        $this->Cell(25, 4.5, '', 0, 0);
        foreach ($datefields1 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($datefields1) - 1) {
                $this->Cell(3.5, 3.5, '', 0, 0); // gap between labels
            }
        }

        $this->Cell(33, 4.5, '', 0, 0);
        foreach ($datefields2 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($datefields2) - 1) {
                $this->Cell(3.5, 3.5, '', 0, 0); // gap between labels
            }
        }

        $this->Cell(8, 4.5, '', 0, 0);
        foreach ($datefields3 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($datefields3) - 1) {
                $this->Cell(3.5, 3.5, '', 0, 0); // gap between labels
            }
        }

        $this->Cell(5, 5, '', 0, 1); // gap between labels

        // 9. Certification
        $this->SetFont('Arial', 'B', 6);
        $this->Cell(5, 1, '9. CERTIFICATION OF MEMBER:', 0, 1);
        $this->SetFont('Arial', 'B', 6);
        $this->Cell(5, 2, '', 0, 0);
        $this->Cell(190, 5, 'Under the penalty of law, I attest that the information I provided in this Form are true and accurate to the best of my knowledge.', 0, 1, 'C');

        $this->Cell(18, 5, '', 0, 0);
        $signfield = [
            ['w' => 80, 'label' => 'Signature Over Printed Name of Member', 'value' => ''],
            ['w' => 80, 'label' => 'Signature Over Printed Name of Members Representative', 'value' => ''],

        ];
        $this->SetFont('Arial', '', 6);

        // --- Draw underline row with values ---
        foreach ($signfield as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($signfield) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between fields
            }
        }
        $this->Cell(5, 3.5, '', 0, 1); // gap between fields
        $this->Cell(17.5, 6, '', 0, 0);
        foreach ($signfield as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($signfield) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between labels
            }
        }
        $this->ln();
        $this->Cell(30, 3, '', 0, 0, 'L');
        $this->Cell(5, 4, 'Date Signed ', 0, 0, 'C');

        $this->Cell(8, 5, '', 0, 0);

        // Use admission date for date signed in certification of member
        $this->DrawDateBoxes($admissionDate, $this->GetX(), $this->GetY());
        $this->Cell(30, 3, '', 0, 0, 'L');
        $this->Cell(5, 4, 'Date Signed ', 0, 0, 'C');
        $this->Cell(8, 5, '', 0, 0);

        // Use admission date for date signed of Members Representative
        $this->DrawDateBoxes($admissionDate, $this->GetX(), $this->GetY());

        $this->ln();
        $this->Cell(43, 4.5, '', 0, 0);
        foreach ($datefields1 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($datefields1) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between labels
            }
        }

        $this->Cell(44, 4.5, '', 0, 0);
        foreach ($datefields2 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($datefields2) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between labels
            }
        }




        $this->Cell(4.0, 4.0, '', 0, 1);
        $this->Ln(1.5);


        $this->Cell(5, 3.5, 'If member/representative is unable to write,', 0, 0);

        $this->Cell(60, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(25, 20, '', 1, 0);
        }
        $this->Cell(40, 3.5, 'Relationship of the', 0, 0);


        $this->Cell(5, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(5, 5, 'Spouse', 0, 0, 'L');
        $this->Cell(5, 5, '', 0, 0);

        $this->Cell(5, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(5, 5, 'Child', 0, 0, 'L');
        $this->Cell(7, 5, '', 0, 0);

        // DD boxes
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(5, 5, 'Parent', 0, 0, 'L');
        $this->Cell(3, 5, '', 0, 0);



        $this->Cell(5, 5, '', 0, 1);

        $this->Cell(90, 4.5, 'put right thumbmark. Member/Representative', 0, 0);
        $this->Cell(40, 4.5, 'representative to the member', 0, 0);

        $this->Cell(5, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(5, 5, 'Sibling', 0, 0, 'L');
        $this->Cell(5, 5, '', 0, 0);

        $this->Cell(5, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(5, 5, 'Others, Specify', 0, 0, 'L');
        $this->Cell(12, 5, '', 0, 0);

        // DD boxes
        $others1 = [
            ['w' => 15, 'label' => 'Signature Over Printed Name of Member', 'value' => ''],

        ];
        $this->SetFont('Arial', '', 6);

        // --- Draw underline row with values ---
        foreach ($others1 as $i => $f) {
            $this->Cell($f['w'], 4.5, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($others1) - 1) {
                $this->Cell(5, 6, '', 0, 0); // gap between fields
            }
        }
        $this->Cell(5, 6, '', 0, 1); // gap between fields


        $this->Cell(90, 4.5, 'should be assisted by an HCI representative.', 0, 0);
        $this->Cell(40, 4.5, 'Reason for signing on', 0, 0);

        $this->Cell(5, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(4.5, 4.5, 'Member is incapacitated', 0, 0, 'L');
        $this->Cell(4.5, 4.5, '', 0, 1);



        $this->Cell(90, 4.5, 'Check the appropriate box.', 0, 0);
        $this->Cell(40, 4.5, 'behalf of the member', 0, 0);

        $this->Cell(5, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(17, 4.5, 'Other reasons:', 0, 0, 'L');
        $others2 = [
            ['w' => 38, 'label' => 'Signature Over Printed Name of Member', 'value' => ''],

        ];
        $this->SetFont('Arial', '', 6);

        // --- Draw underline row with values ---
        foreach ($others2 as $i => $f) {
            $this->Cell($f['w'], 4.5, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($others2) - 1) {
                $this->Cell(5, 4.5, '', 0, 0); // gap between fields
            }
        }
        $this->Cell(5, 5, '', 0, 1); // gap between fields

        $this->Cell(5, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(7, 3.5, 'Member', 0, 0, 'L');
        $this->Cell(10, 3.5, '', 0, 0);

        // DD boxes
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(9, 3.5, 'Representative', 0, 0, 'L');
        $this->Cell(3, 3.5, '', 0, 0);


        $this->ln(5.5);
        $this->SetFont('Arial', 'B', 8);

        // Title
        // Set black background
        $this->SetFillColor(0, 0, 0);     // RGB: black
        $this->SetTextColor(255, 255, 255); // white text

        $this->Cell(
            0,
            4,
            'PART II - EMPLOYERS CERTIFICATION (for employed members only)',
            1,   // border
            1,   // line break
            'C', // center
            true // fill with background color
        );

        // Reset colors back to default (black text, no fill)
        $this->SetTextColor(0, 0, 0);
        $this->SetFillColor(255, 255, 255);







        $this->Ln(2);
        $this->SetFont('Arial', 'B', 6);
        // 1. PhilHealth Identification Number (PIN) of Member
        $this->Cell(40, 4, '1.PhilHealth Employer Number (PEN):', 0, 0);
        $this->SetFont('Arial', 'B', 6);
        // 2 digits
        for ($i = 0; $i < 2; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        // dash
        $this->Cell(4, 4.5, '-', 0, 0, 'C');

        // 9 digits
        for ($i = 0; $i < 9; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        // dash
        $this->Cell(4, 4.5, '-', 0, 0, 'C');

        // 1 digit
        $this->Cell(4.0, 4.0, '', 1, 0);

        $this->Cell(35, 4.5, '', 0, 0);
        $this->Cell(20, 4, '2.Contact No:', 0, 0, 'L');
        $contact1 = [
            ['w' => 40, 'label' => 'contactnumber', 'value' => ''],

        ];
        $this->SetFont('Arial', '', 6);

        // --- Draw underline row with values ---
        foreach ($contact1 as $i => $f) {
            $this->Cell($f['w'], 4.5, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($contact1) - 1) {
                $this->Cell(5, 6, '', 0, 0); // gap between fields
            }
        }
        $this->Cell(5, 6, '', 0, 1); // gap between fields

        $this->SetFont('Arial', 'B', 6);
        $this->Cell(35, 4, '3.Business Name:', 0, 0, 'L');
        $contact1 = [
            ['w' => 155, 'label' => 'Business Name of Employer', 'value' => ''],

        ];
        $this->SetFont('Arial', '', 6);

        // --- Draw underline row with values ---
        foreach ($contact1 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($contact1) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between fields
            }
        }

        $this->Ln();
        $this->SetFont('Arial', '', 6);
        $this->Cell(35, 6, '', 0, 0); // gap between fields
        // --- Labels row ---
        foreach ($contact1 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C');
            if ($i < count($contact1) - 1) {
                $this->Cell(5, 3.5, '', 0, 0);
            }
        }
        $this->SetFont('Arial', '', 6);
        $this->Cell(5, 3.5, '', 0, 1); // gap between fields

        $this->SetFont('Arial', 'B', 7);
        $this->Cell(35, 4, '4.CERTIFICATION OF EMPLOYER:', 0, 1, 'L');
        $this->SetFont('Arial', 'B', 6);
        $this->Cell(5, 6, '', 0, 0); // gap between fields
        $this->Cell(35, 2.5, '"This is to certify that the required 3/6 monthly premium contributions plus at least 6 months contributions preceding the 3 months qualifying contributions within 12', 0, 1, 'L');
        $this->Cell(35, 2.5, 'month period prior to the first day of confinement (sufficient regularity) have been regularly remitted to PhilHealth. Moreover, the information supplied by the member or', 0, 1, 'L');
        $this->Cell(35, 2.5, 'his/her representative on Part I are consistent with our available records."', 0, 1, 'L');



        $this->Cell(5, 5, '', 0, 0);
        $signfield3 = [
            ['w' => 75, 'label' => 'Signature Over Printed Name of Employer/Authorized Representative', 'value' => ''],
            ['w' => 40, 'label' => 'Official Capacity/Designation', 'value' => ''],

        ];
        $this->SetFont('Arial', '', 6);

        // --- Draw underline row with values ---
        foreach ($signfield3 as $i => $f) {
            $this->Cell($f['w'], 6, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($signfield3) - 1) {
                $this->Cell(5, 6, '', 0, 0); // gap between fields
            }
        }
        $this->Cell(5, 6, '', 0, 1); // gap between fields
        $this->Cell(5, 6, '', 0, 0);
        $this->SetFont('Arial', '', 6);
        foreach ($signfield3 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($signfield3) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between labels
            }
        }
        $this->SetFont('Arial', '', 6);

        $this->Cell(13, 5, '', 0, 0, 'L');
        $this->Cell(5, -2, 'Date Signed ', 0, 0, 'C');
        $this->Cell(8, 5, '', 0, 0);
        $this->ln(-2.5);
        $this->Cell(150, 5, '', 0, 0);
        for ($i = 0; $i < 2; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(3, 5, '-', 0, 0);
        for ($i = 0; $i < 2; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);

        }
        $this->Cell(3, 5, '-', 0, 0);
        for ($i = 0; $i < 4; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);

        }

        $this->ln();
        $this->Cell(150, 4.5, '', 0, 0);
        foreach ($datefields1 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($datefields1) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between labels
            }
        }







        $this->ln(4);
        $this->SetFont('Arial', 'B', 8);

        // Title
        // Set black background
        $this->SetFillColor(0, 0, 0);     // RGB: black
        $this->SetTextColor(255, 255, 255); // white text

        $this->Cell(
            0,
            4,
            'PART III - CONSENT TO ACCESS PATIENT RECORD/S',
            1,   // border
            1,   // line break
            'C', // center
            true // fill with background color
        );

        // Reset colors back to default (black text, no fill)
        $this->SetTextColor(0, 0, 0);
        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 5.5);
        $this->Cell(5, 1, '', 0, 1);
        $this->Cell(35, 2.5, 'I hereby consent to the submission and examination of the patient’s pertinent medical records for the purpose of verifying the veracity of this claim to effect efficient', 0, 1, 'L');
        $this->Cell(35, 2.5, 'processing of benefit payment.', 0, 1, 'L');
        $this->Cell(35, 2.5, 'I hereby hold PhilHealth or any of its officers, employees and/or representatives free from any legal liabilities relative to the herein-mentioned consent which I have', 0, 1, 'L');
        $this->Cell(35, 2.5, 'voluntarily and willingly given in connection with this claim for reimbursement before PhilHealth.', 0, 1, 'L');

        $this->ln(0.5);



        $this->Cell(5, 5, '', 0, 0);
        $signfield4 = [
            ['w' => 100, 'label' => 'Signature Over Printed Name of Member/Patient/Authorized Representative', 'value' => ''],

        ];
        $this->SetFont('Arial', '', 6);

        // --- Draw underline row with values ---
        foreach ($signfield4 as $i => $f) {
            $this->Cell($f['w'], 4, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($signfield4) - 1) {
                $this->Cell(5, 4, '', 0, 0); // gap between fields
            }
        }
        $this->Cell(5, 4, '', 0, 1); // gap between fields
        $this->Cell(5, 6, '', 0, 0);
        $this->SetFont('Arial', '', 6);
        foreach ($signfield4 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($signfield4) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between labels
            }
        }
        $this->SetFont('Arial', '', 6);

        $this->Cell(20, 5, '', 0, 0, 'L');
        $this->Cell(5, -2, 'Date Signed ', 0, 0, 'C');
        $this->Cell(8, 5, '', 0, 0);
        $this->ln(-2.5);
        $this->Cell(140, 5, '', 0, 0);
        for ($i = 0; $i < 2; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(3, 5, '-', 0, 0);
        for ($i = 0; $i < 2; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);

        }
        $this->Cell(3, 5, '-', 0, 0);
        for ($i = 0; $i < 4; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);

        }

        $this->ln();
        $this->Cell(140, 4.5, '', 0, 0);
        foreach ($datefields1 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($datefields1) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between labels
            }
        }



        $this->Cell(4.0, 5, '', 0, 1);



        $this->Cell(5, 4.5, 'If member/representative is unable to write,', 0, 0);

        $this->Cell(60, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(25, 20, '', 1, 0);
        }
        $this->Cell(40, 4.5, 'Relationship of the', 0, 0);


        $this->Cell(5, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(5, 5, 'Spouse', 0, 0, 'L');
        $this->Cell(5, 5, '', 0, 0);

        $this->Cell(5, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(5, 5, 'Child', 0, 0, 'L');
        $this->Cell(7, 5, '', 0, 0);

        // DD boxes
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(5, 5, 'Parent', 0, 0, 'L');
        $this->Cell(3, 5, '', 0, 0);



        $this->Cell(5, 4.5, '', 0, 1);

        $this->Cell(90, 4.5, 'put right thumbmark. Member/Representative', 0, 0);
        $this->Cell(40, 4.5, 'representative to the member', 0, 0);

        $this->Cell(5, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(5, 4.5, 'Sibling', 0, 0, 'L');
        $this->Cell(5, 4.5, '', 0, 0);

        $this->Cell(5, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(5, 4.5, 'Others, Specify', 0, 0, 'L');
        $this->Cell(12, 4.5, '', 0, 0);

        // DD boxes
        $others1 = [
            ['w' => 15, 'label' => 'Signature Over Printed Name of Member', 'value' => ''],

        ];
        $this->SetFont('Arial', '', 6);

        // --- Draw underline row with values ---
        foreach ($others1 as $i => $f) {
            $this->Cell($f['w'], 4.5, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($others1) - 1) {
                $this->Cell(5, 4.5, '', 0, 0); // gap between fields
            }
        }
        $this->Cell(5, 4.5, '', 0, 1); // gap between fields


        $this->Cell(90, 4.5, 'should be assisted by an HCI representative.', 0, 0);
        $this->Cell(40, 4.5, 'Reason for signing on', 0, 0);

        $this->Cell(5, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(5, 4.5, 'Patient is incapacitated', 0, 0, 'L');
        $this->Cell(5, 4.5, '', 0, 1);



        $this->Cell(90, 4.5, 'Check the appropriate box.', 0, 0);
        $this->Cell(40, 4.5, 'behalf of the Patient', 0, 0);

        $this->Cell(5, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(17, 5, 'Other reasons:', 0, 0, 'L');
        $others3 = [
            ['w' => 38, 'label' => 'Signature Over Printed Name of Patient', 'value' => ''],

        ];
        $this->SetFont('Arial', '', 6);

        // --- Draw underline row with values ---
        foreach ($others3 as $i => $f) {
            $this->Cell($f['w'], 4.5, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($others3) - 1) {
                $this->Cell(5, 6, '', 0, 0); // gap between fields
            }
        }
        $this->Cell(5, 4.5, '', 0, 1); // gap between fields

        $this->Cell(5, 4.5, '', 0, 0);
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(7, 4.5, 'Patient', 0, 0, 'L');
        $this->Cell(10, 4.5, '', 0, 0);

        // DD boxes
        for ($i = 0; $i < 1; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(9, 4.5, 'Representative', 0, 0, 'L');
        $this->Cell(3, 4.5, '', 0, 0);


        $this->ln(6);
        $this->SetFont('Arial', 'B', 8);

        // Title
        // Set black background
        $this->SetFillColor(0, 0, 0);     // RGB: black
        $this->SetTextColor(255, 255, 255); // white text

        $this->Cell(
            0,
            4,
            'PART IV - HEALTH CARE PROFESSIONAL INFORMATION',
            1,   // border
            1,   // line break
            'C', // center
            true // fill with background color
        );

        // Reset colors back to default (black text, no fill)
        $this->SetTextColor(0, 0, 0);
        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 5.5);
        $this->Cell(5, 1, '', 0, 1);
        $this->Cell(20, 6, 'Accreditation No.', 0, 0);
        $this->SetFont('Arial', 'B', 6);

        // Get accreditation from data
        $accreditation = $this->getAccreditation();
        $this->DrawAccreditationBoxes($accreditation, $this->GetX(), $this->GetY() + 2);

        $this->Cell(5, 4.5, '', 0, 0);
        $contact1 = [
            ['w' => 59.5, 'label' => 'Signature Over Printed Name', 'value' => $this->getDataValue('doctor')],

        ];
        $this->SetFont('Arial', '', 6);

        // --- Draw underline row with values ---
        foreach ($contact1 as $i => $f) {
            $this->Cell($f['w'], 4.5, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($contact1) - 1) {
                $this->Cell(5, 6, '', 0, 0); // gap between fields
            }
        }



        $this->SetFont('Arial', '', 6);

        $this->Cell(5, 5, '', 0, 0, 'L');
        $this->Cell(5, 5, 'Date Signed ', 0, 0, 'C');

        $this->Cell(5, 5, '', 0, 0);

        // Use admission date for date signed in Part IV
        $this->DrawDateBoxes($admissionDate, $this->GetX(), $this->GetY());
        $datefields4 = [
            ['w' => 9, 'label' => 'Month'],
            ['w' => 5, 'label' => 'Day'],
            ['w' => 14, 'label' => 'Year'],

        ];
        $this->ln();
        $this->Cell(155, 4.5, '', 0, 0);
        foreach ($datefields4 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($datefields4) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between labels
            }
        }

        $this->Cell(5, 5, '', 0, 1);

        $this->Cell(20, 6, 'Accreditation No.', 0, 0);
        $this->SetFont('Arial', 'B', 6);

        // Get accreditation from data
        $accreditation = $this->getAccreditation();
        $this->DrawAccreditationBoxes('', $this->GetX(), $this->GetY() + 2);

        $this->Cell(5, 4.5, '', 0, 0);
        $contact1 = [
            ['w' => 59.5, 'label' => 'Signature Over Printed Name', 'value' => ''],

        ];
        $this->SetFont('Arial', '', 6);

        // --- Draw underline row with values ---
        foreach ($contact1 as $i => $f) {
            $this->Cell($f['w'], 4.5, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($contact1) - 1) {
                $this->Cell(5, 6, '', 0, 0); // gap between fields
            }
        }



        $this->SetFont('Arial', '', 6);

        $this->Cell(5, 5, '', 0, 0, 'L');
        $this->Cell(5, 5, 'Date Signed ', 0, 0, 'C');


        $this->Cell(5, 5, '', 0, 0);
        for ($i = 0; $i < 2; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(3, 5, '-', 0, 0);
        for ($i = 0; $i < 2; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);

        }
        $this->Cell(3, 5, '-', 0, 0);
        for ($i = 0; $i < 4; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);

        }
        $datefields4 = [
            ['w' => 9, 'label' => 'Month'],
            ['w' => 5, 'label' => 'Day'],
            ['w' => 14, 'label' => 'Year'],

        ];
        $this->ln();
        $this->Cell(155, 4.5, '', 0, 0);
        foreach ($datefields4 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($datefields4) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between labels
            }
        }

        $this->Cell(5, 5, '', 0, 1);

        $this->Cell(20, 6, 'Accreditation No.', 0, 0);
        $this->SetFont('Arial', 'B', 6);

        // Get accreditation from data
        $accreditation = $this->getAccreditation();
        $this->DrawAccreditationBoxes('', $this->GetX(), $this->GetY() + 2);

        $this->Cell(5, 4.5, '', 0, 0);
        $contact1 = [
            ['w' => 59.5, 'label' => 'Signature Over Printed Name', 'value' => ''],

        ];
        $this->SetFont('Arial', '', 6);

        // --- Draw underline row with values ---
        foreach ($contact1 as $i => $f) {
            $this->Cell($f['w'], 4.5, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($contact1) - 1) {
                $this->Cell(5, 6, '', 0, 0); // gap between fields
            }
        }



        $this->SetFont('Arial', '', 6);

        $this->Cell(5, 5, '', 0, 0, 'L');
        $this->Cell(5, 5, 'Date Signed ', 0, 0, 'C');


        $this->Cell(5, 5, '', 0, 0);
        for ($i = 0; $i < 2; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);
        }
        $this->Cell(3, 5, '-', 0, 0);
        for ($i = 0; $i < 2; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);

        }
        $this->Cell(3, 5, '-', 0, 0);
        for ($i = 0; $i < 4; $i++) {
            $this->Cell(4.0, 4.0, '', 1, 0);

        }
        $datefields4 = [
            ['w' => 9, 'label' => 'Month'],
            ['w' => 5, 'label' => 'Day'],
            ['w' => 14, 'label' => 'Year'],

        ];
        $this->ln();
        $this->Cell(155, 4.5, '', 0, 0);
        foreach ($datefields4 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($datefields4) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between labels
            }
        }

        $this->Cell(5, 4, '', 0, 1);
        $this->SetFont('Arial', 'B', 8);

        // Title
        // Set black background
        $this->SetFillColor(0, 0, 0);     // RGB: black
        $this->SetTextColor(255, 255, 255); // white text

        $this->Cell(
            0,
            4,
            'PART V - PROVIDER INFORMATION AND CERTIFICATION',
            1,   // border
            1,   // line break
            'C', // center
            true // fill with background color
        );

        // Reset colors back to default (black text, no fill)
        $this->SetTextColor(0, 0, 0);
        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 6);
        $this->Cell(5, 1, '', 0, 1);

        $this->Cell(25, 5, '1.PhilHealth Benefits:', 0, 0, 'L');
        $this->Cell(5, 5, 'ICD 10 or RVS Code:', 0, 0, 'L');
        $this->Cell(25, 5, '', 0, 0);

        // DD boxes
        $firstcase = [
            ['w' => 50, 'label' => 'firstcase rate', 'value' => ' OPER1'],

        ];

        $secondcase = [
            ['w' => 50, 'label' => 'second case rate', 'value' => ''],

        ];


        $this->SetFont('Arial', '', 6);
        $this->Cell(20, 5, '1. First Case Rate', 0, 0, 'L');
        // --- Draw underline row with values ---
        foreach ($firstcase as $i => $f) {
            $this->Cell($f['w'], 4.5, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($firstcase) - 1) {
                $this->Cell(5, 6, '', 0, 0); // gap between fields
            }
        }
        $this->SetFont('Arial', '', 6);
        $this->Cell(20, 5, '1. Second Case Rate', 0, 0, 'L');
        // --- Draw underline row with values ---
        foreach ($secondcase as $i => $f) {
            $this->Cell($f['w'], 4.5, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($secondcase) - 1) {
                $this->Cell(5, 6, '', 0, 0); // gap between fields
            }
        }

        $this->Cell(5, 5, '', 0, 1);
        $this->Cell(195, 5, 'I certify that services rendered were recorded in the patient’s chart and health care institution records and that the herein information given are true and correct.
', 0, 1, 'C');


        $this->Cell(5, 5, '', 0, 0);
        $signfield3 = [
            ['w' => 75, 'label' => 'Signature Over Printed Name of Authorized HCI', 'value' => 'ANDREW L. LO'],
            ['w' => 40, 'label' => 'Official Capacity/Designation', 'value' => 'CLAIMS PROCESSING SUPERVISOR'],

        ];
        $this->SetFont('Arial', '', 6);

        // --- Draw underline row with values ---
        foreach ($signfield3 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['value'], 'B', 0, 'C'); // show dynamic value, centered
            if ($i < count($signfield3) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between fields
            }
        }
        $this->Cell(5, 3.5, '', 0, 1); // gap between fields
        $this->Cell(5, 3.5, '', 0, 0);
        $this->SetFont('Arial', '', 6);
        foreach ($signfield3 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($signfield3) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between labels
            }
        }
        $this->SetFont('Arial', '', 6);

        $this->Cell(13, 5, '', 0, 0, 'L');
        $this->Cell(5, -2, 'Date Signed ', 0, 0, 'C');
        $this->Cell(8, 5, '', 0, 0);
        $this->ln(-2);
        $this->Cell(150, 5, '', 0, 0);

        // Use admission date for date signed in Part V
        $this->DrawDateBoxes($admissionDate, $this->GetX(), $this->GetY());

        $this->ln();
        $this->Cell(150, 4.5, '', 0, 0);
        foreach ($datefields1 as $i => $f) {
            $this->Cell($f['w'], 3.5, $f['label'], 0, 0, 'C'); // label centered
            if ($i < count($datefields1) - 1) {
                $this->Cell(5, 3.5, '', 0, 0); // gap between labels
            }
        }


    }

    public function Body()
    {
        $this->AssessForm();
    }

    public function Footer()
    {
        $this->Ln(6);

        $this->SetAutoPageBreak(true, 25);
    }

    function SetWidths($w)
    {
        $this->widths = $w;
    }

    function SetAligns($a)
    {
        $this->aligns = $a;
    }

    function Row($data)
    {
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        $h = 5 * $nb;
        $this->CheckPageBreak($h);
        for ($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : "C";
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x, $y, $w, $h);
            $this->MultiCell($w, 5, $data[$i], 0, $a);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }

    function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont["cw"];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = (($w - 2 * $this->cMargin) * 1000) / $this->FontSize;
        $s = str_replace("\r", "", $txt);
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb - 1] == "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == " ") {
                $sep = $i;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }

    function FancyRow($data, $border = [], $align = [], $style = [], $maxline = [])
    {
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        if (count($maxline)) {
            $_maxline = max($maxline);
            if ($nb > $_maxline) {
                $nb = $_maxline;
            }
        }
        $h = 5 * $nb;
        $this->CheckPageBreak($h);
        for ($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($align[$i]) ? $align[$i] : "L";
            $m = isset($maxline[$i]) ? $maxline[$i] : false;
            $x = $this->GetX();
            $y = $this->GetY();
            if ($border[$i] == 1) {
                $this->Rect($x, $y, $w, $h);
            } else {
                $_border = strtoupper($border[$i]);
                if (strstr($_border, "L") !== false) {
                    $this->Line($x, $y, $x, $y + $h);
                }
                if (strstr($_border, "R") !== false) {
                    $this->Line($x + $w, $y, $x + $w, $y + $h);
                }
                if (strstr($_border, "T") !== false) {
                    $this->Line($x, $y, $x + $w, $y);
                }
                if (strstr($_border, "B") !== false) {
                    $this->Line($x, $y + $h, $x + $w, $y + $h);
                }
            }
            if (isset($style[$i])) {
                $this->SetFont("", $style[$i]);
            }
            $this->MultiCell($w, 5, $data[$i], 0, $a, 0, $m);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    /**
     * Draw a border around the entire page
     */
    public function drawPageBorder()
    {
        // Get current position
        $currentX = $this->GetX();
        $currentY = $this->GetY();

        // Set border properties
        $this->SetDrawColor(0, 0, 0); // Black border
        $this->SetLineWidth(0.5); // 0.5mm line width

        // Get page dimensions
        $pageWidth = $this->GetPageWidth();
        $pageHeight = $this->GetPageHeight();
        $margin = 5; // 5mm margin from page edges

        // Draw border rectangle
        $this->Rect(
            $margin,
            $margin,
            $pageWidth - (2 * $margin),
            $pageHeight - (2 * $margin)
        );

        // Restore position
        $this->SetXY($currentX, $currentY);
    }
}
?>