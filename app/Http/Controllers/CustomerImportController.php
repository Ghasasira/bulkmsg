<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\CustomersImport;
use Maatwebsite\Excel\Facades\Excel;

class CustomerImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        // dd("lets cook");

        Excel::import(new CustomersImport, $request->file('file'));

        return back()->with('success', 'Customers imported successfully!');
    }
}
