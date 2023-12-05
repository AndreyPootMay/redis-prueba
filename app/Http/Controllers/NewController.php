<?php


namespace App\Http\Controllers;

use App\Models\CorporateWeekStats;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class NewController extends Controller
{
    public function example(Request $request): JsonResponse
    {
        $cacheKey = 'mi_ruta_cache_key';

        // Intenta obtener la respuesta de la caché
        $cachedResponse = Cache::get($cacheKey);

        // Si la respuesta está en caché, responde con ella
        if ($cachedResponse) {
            return response()->json($cachedResponse);
        }

        // Creo una fecha al azar con un número para saber si lo que quise hacer se cacheó
        $startDate = Carbon::now()->subDays(30); // 30 days ago
        $endDate = Carbon::now(); // Current date

        $randomDate = Carbon::createFromTimestamp(mt_rand($startDate->timestamp, $endDate->timestamp));

        // Format the random date as per your requirements
        $formattedRandomDate = $randomDate->toDateString();


        // Si la respuesta no está en caché, realiza la lógica de tu solicitud
        $datos = [
            'num1' => rand(10, 50),
            'fecha' => $formattedRandomDate,
        ]; // lógica para obtener los datos de tu API

        // Almacena la respuesta en caché durante un tiempo específico (por ejemplo, 60 minutos)
        Cache::put($cacheKey, $datos, now()->addMinutes(60));

        // Responde con los datos obtenidos
        return response()->json($datos);
    }

    public function reportYtd(Request $request)
    {
        $weekStats = CorporateWeekStats::loadModels();
        dd($weekStats[1]);

        $outPutArray = [];

        foreach ($weekStats as $entry) {
            $weekRange = explode('.', $entry['PK2']);
            $weekFrom = date('Y-m-d H:i:s', $weekRange[1]);
            $weekTo = date('Y-m-d H:i:s', $weekRange[2]);

            foreach ($entry['stats_table'] as $stats) {
                $outPutArray[] = [
                    'account_name' => $stats['account_name'],
                    'week_range' => "From {$weekFrom} to {$weekTo}",
                    'week_from' => $weekFrom,
                    'week_to' => $weekTo,
                    'activities' => $stats['activities'],
                    'deliveries' => $stats['deliveries'],
                    'participants' => $stats['participants'],
                    'engagement' => $stats['engagement'],
                ];
            }
        }

        $weeksArray = collect($outPutArray)->groupBy(['week_range', 'account_name']);
        dd($weeksArray);

        // Create a new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set the column headers
        $sheet->setCellValue('A2', 'Account');
        $sheet->setCellValue('B2', 'Participants');
        $sheet->setCellValue('C2', 'Activities');
        $sheet->setCellValue('D2', 'Deliveries');
        $sheet->setCellValue('E2', 'Engagement');

        // Loop through the array and populate the spreadsheet
        $row = 2;
        $columnXNumber = 1;
        foreach ($weeksArray as $weekRange => $accounts) {
            $lastColumnIndex = $columnXNumber + 5;
            // Set the value in the current column
            $sheet->setCellValueByColumnAndRow($columnXNumber, 1, $weekRange);

            if ($lastColumnIndex === 6) { // only the first time
                $sheet->setCellValueByColumnAndRow($lastColumnIndex + 1, 2, 'Account');
            } else {
                $sheet->setCellValueByColumnAndRow($lastColumnIndex + 1, 2, '');
            }

            $sheet->setCellValueByColumnAndRow($lastColumnIndex + 1, 2, 'Participants');
            $sheet->setCellValueByColumnAndRow($lastColumnIndex + 2, 2, 'Activities');
            $sheet->setCellValueByColumnAndRow($lastColumnIndex + 3, 2, 'Deliveries');
            $sheet->setCellValueByColumnAndRow($lastColumnIndex + 4, 2, 'Engagement');

            // Calculate the last column index for merging
            $lastColumnIndex = $columnXNumber + 4;

            // Merge the current column to the next 3 columns
            $sheet->mergeCellsByColumnAndRow($columnXNumber, 1, $lastColumnIndex, 1);

            // Center the content within the merged cells
            $sheet->getStyleByColumnAndRow($columnXNumber, 1, $lastColumnIndex, 1)->getAlignment()->setHorizontal('center');

            // Move to the next starting column for the next iteration
            $columnXNumber = $lastColumnIndex + 1;

            $row++;
        }

        $accountRow = 3;
        foreach ($weeksArray as $weekRange => $accounts) {
            foreach ($accounts as $accountName => $value) {
                $sheet->setCellValue("A{$accountRow}", $accountName);
                $accountRow++;
            }
            break;
        }

        $row = 2;
        $statsRow = 3;
        $columnXNumber = 1;
        foreach ($weeksArray as $weekRange => $accounts) {
            foreach ($accounts as $accountName => $value) {
                if ($statsRow >= sizeof($accounts)) {
                    $columnXNumber = $columnXNumber + 5; // Salto 5 columnas en horizontal
                    $statsRow = 3;
                }
                // Set the value in the current column
                $sheet->setCellValueByColumnAndRow($columnXNumber + 1, $statsRow, $value[0]['participants']);
                $sheet->setCellValueByColumnAndRow($columnXNumber + 2, $statsRow, $value[0]['activities']);
                $sheet->setCellValueByColumnAndRow($columnXNumber + 3, $statsRow, $value[0]['deliveries']);
                $sheet->setCellValueByColumnAndRow($columnXNumber + 4, $statsRow, $value[0]['engagement']);

                $statsRow++;
            }
        }


        // Set AutoSize for all columns
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        // Set the response headers
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="report_ytd_'. time() .'.xlsx"');
        header('Cache-Control: max-age=0');

        // Save the spreadsheet to PHP output
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }
}
