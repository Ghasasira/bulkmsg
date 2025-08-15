<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomersImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Customer([
            'name'      => $row['cust_name'],
            'local_amt'  => str_replace(',', '', $row['local_amt']), // Remove commas
            'no_due_days' => $row['no_due_days'],
            'number1'    => $row['number_1'],
            'number2'    => $row['number_2'],
        ]);
    }
}
