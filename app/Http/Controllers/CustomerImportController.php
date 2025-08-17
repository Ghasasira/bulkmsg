<?php

namespace App\Http\Controllers;

use App\Imports\CustomersImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CustomerImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048' // 2MB max size
        ]);

        try {
            // Convert Excel to CSV as an intermediate step
            $tempPath = $this->convertExcelToTempCsv($request->file('file'));

            // Import the CSV data
            Excel::import(new CustomersImport, $tempPath, null, \Maatwebsite\Excel\Excel::CSV);

            // Clean up the temporary file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return back()->with('success', 'Customers imported successfully!');
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            return back()->with('error', 'Error reading Excel file: ' . $e->getMessage());
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];

            foreach ($failures as $failure) {
                $errors[] = "Row {$failure->row()}: {$failure->errors()[0]}";
            }

            return back()->with('error', implode('<br>', $errors));
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Convert Excel file to temporary CSV
     */
    protected function convertExcelToTempCsv(UploadedFile $file): string
    {
        $spreadsheet = IOFactory::load($file->getRealPath());

        // Create temporary file
        $tempPath = tempnam(sys_get_temp_dir(), 'laravel-excel') . '.csv';

        // Save as CSV
        $writer = IOFactory::createWriter($spreadsheet, 'Csv');
        // $writer->setDelimiter(',');
        // $writer->setEnclosure('"');
        // $writer->setLineEnding("\r\n");
        // $writer->setSheetIndex(0); // Only convert first sheet
        $writer->save($tempPath);

        return $tempPath;
    }
}
