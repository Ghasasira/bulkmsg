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
            'name'        => $row['name'], // Lowercase
            'local_amt'   => str_replace(',', '', $row['amount']), // Remove commas
            'no_due_days' => $row['pastdue'], // Lowercase
            'number1'     => $row['contact'], // Lowercase
        ]);
    }
}
