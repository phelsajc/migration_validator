<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EpisodesExport implements FromCollection, WithHeadings
{
    protected $results;

    public function __construct($results)
    {
        $this->results = $results;
    }

    public function collection()
    {
        // Return the collection with all columns mapped from episodes data
        return collect($this->results)->map(function ($row) {
            return [
                'MRN' => $row->mrn ?? '',
                'Start Date' => $row->startdate ?? '',
                'Order Date' => $row->orderdate ?? '',
                'Patient Name' => $row->name ?? '',
                'Encounter Type' => $row->entype ?? '',
                'Order To Department' => $row->ordertodepartmentname ?? '',
                'Order Item Name' => $row->orderitemname ?? '',
                'Quantity' => $row->quantity ?? '',
                'Unit Price' => $row->unitprice ?? '',
                'Total Amount' => ($row->quantity ?? 0) * ($row->unitprice ?? 0),
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
            'Start Date',
            'Order Date',
            'Patient Name',
            'Encounter Type',
            'Order To Department',
            'Order Item Name',
            'Quantity',
            'Unit Price',
            'Total Amount',
            'Status Description',
            'Store',
            'Order Number',
            'Item Group',
            'Payors',
        ];
    }
}
