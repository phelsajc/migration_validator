<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FilteredResultsExport implements FromCollection, WithHeadings
{
    protected $results;

    public function __construct($results)
    {
        $this->results = $results;
    }

    public function collection()
    {
        // Return the collection with all columns
        return collect($this->results)->map(function ($row) {
            return [
                'MRN' => $row->mrn ?? '',
                'Order Date' => $row->orderdate ?? '',
                'Patient Name' => $row->name ?? '',
                'Encounter Type' => $row->entype ?? '',
                'Order To Department' => $row->ordertodepartmentname ?? '',
                'Order Item Name' => $row->orderitemname ?? '',
                'Quantity' => $row->quantity ?? '',
                'Unit Price' => $row->unitprice ?? '',
                'Status Description' => $row->statusdescription ?? '',
                'Store' => $row->storename ?? '',
                'Order Number' => $row->ordernumber ?? '',
                'Item Group' => $row->itemgroup ?? '',
                'Payors' => $row->tpas_names ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'MRN',
            'Order Date',
            'Patient Name',
            'Encounter Type',
            'Order To Department',
            'Order Item Name',
            'Quantity',
            'Unit Price',
            'Status Description',
            'Store',
            'Order Number',
            'Item Group',
            'Payors',
        ];
    }

    public function chunkSize(): int
    {
        return 500; // Reduced from 1000 to 500 for better memory management
    }

    public function batchSize(): int
    {
        return 500; // Reduced from 1000 to 500 for better memory management
    }
}


