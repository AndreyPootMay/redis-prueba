<?php


namespace App\Http\Controllers;

use App\Models\CorporateWeekStats;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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

        // Create a new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set the column headers
        $columnXNumber = 1;
        foreach ($weeksArray as $weekRange => $weekItem) {
            $sheet->setCellValueByColumnAndRow($columnXNumber, 2, 'Account');
            $columnXNumber++;
            $sheet->setCellValueByColumnAndRow($columnXNumber, 2, 'Participants');
            $columnXNumber++;
            $sheet->setCellValueByColumnAndRow($columnXNumber, 2, 'Activities');
            $columnXNumber++;
            $sheet->setCellValueByColumnAndRow($columnXNumber, 2, 'Deliveries');
            $columnXNumber++;
            $sheet->setCellValueByColumnAndRow($columnXNumber, 2, 'Engagement');
            $columnXNumber++;
        }

        // Ytd-participants|Ytd-deliveries|YtdEngagement

        // Only put the account names for now!
        $columnXNumber = 1;
        foreach ($weeksArray as $weekRange => $weekItem) {
            $rowNumber = 3;
            foreach ($weekItem as $accountName => $accountItem) {
                if ($rowNumber > 106) {
                    $columnXNumber = $columnXNumber + 5;
                    $rowNumber = 3;
                }
                $sheet->setCellValueByColumnAndRow($columnXNumber, $rowNumber, $accountName);
                $rowNumber = $rowNumber + 1;
            }
            $columnXNumber = $columnXNumber + 5;
        }

        // Only put the additional data for now!
        $columnXNumber = 2;
        foreach ($weeksArray as $weekRange => $weekItem) {
            $rowNumber = 3;
            foreach ($weekItem as $accountName => $accountItem) {
                if ($rowNumber > 106) {
                    $columnXNumber = $columnXNumber + 5;
                    $rowNumber = 3;
                }
                $sheet->setCellValueByColumnAndRow($columnXNumber, $rowNumber, $accountItem[0]['participants']);
                $rowNumber = $rowNumber + 1;
            }
            $columnXNumber = $columnXNumber + 5;
        }

        // TODO: Activities
        $columnXNumber = 3;
        $arrayIndex = 0;
        $ytdActivities = [];
        foreach ($weeksArray as $weekRange => $weekItem) {
            $rowNumber = 3;
            foreach ($weekItem as $accountName => $accountItem) {
                if ($rowNumber > 106) {
                    $columnXNumber = $columnXNumber + 5;
                    $rowNumber = 3;
                    $arrayIndex = 0;
                }
                $sheet->setCellValueByColumnAndRow($columnXNumber, $rowNumber, $accountItem[0]['activities']);
                $rowNumber = $rowNumber + 1;
                $ytdActivities[$accountName][$arrayIndex] = $accountItem[0]['activities'];

                $arrayIndex++;
            }
            $columnXNumber = $columnXNumber + 5;
        }

        $columnXNumber = 4;
        $ytdDeliveries = [];
        foreach ($weeksArray as $weekRange => $weekItem) {
            $rowNumber = 3;
            foreach ($weekItem as $accountName => $accountItem) {
                if ($rowNumber > 106) {
                    $columnXNumber = $columnXNumber + 5;
                    $rowNumber = 3;
                }
                $sheet->setCellValueByColumnAndRow($columnXNumber, $rowNumber, $accountItem[0]['deliveries']);
                $rowNumber = $rowNumber + 1;
                $ytdDeliveries[$accountName][$arrayIndex] = $accountItem[0]['deliveries'];
                $arrayIndex++;
            }
            $columnXNumber = $columnXNumber + 5;
        }

        $columnXNumber = 5;
        foreach ($weeksArray as $weekRange => $weekItem) {
            $rowNumber = 3;
            foreach ($weekItem as $accountName => $accountItem) {
                if ($rowNumber > 106) {
                    $columnXNumber = $columnXNumber + 5;
                    $rowNumber = 3;
                }
                $sheet->setCellValueByColumnAndRow($columnXNumber, $rowNumber, number_format($accountItem[0]['engagement'] * 100, 2));
                $rowNumber = $rowNumber + 1;
            }
            $columnXNumber = $columnXNumber + 5;
        }

        $rowNumber = 1;
        $columnNumber = 2;
        foreach ($weeksArray as $weekName => $weekItem) {
            $sheet->setCellValueByColumnAndRow($columnNumber, $rowNumber, $weekName);
            $columnNumber = $columnNumber + 5;
        }

        // Acumulado
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnNumber = Coordinate::columnIndexFromString($highestColumn) + 2;

        $sheet->setCellValueByColumnAndRow($highestColumnNumber, 2, 'YTD Activities');
        $sheet->setCellValueByColumnAndRow($highestColumnNumber + 1, 2, 'YTD Deliveries');
        $sheet->setCellValueByColumnAndRow($highestColumnNumber + 2, 2, 'YTD Engagement rate');

        // YtdActivities
        $rowNumber = 3;
        foreach ($ytdActivities as $accountName => $item) {
            $sheet->setCellValueByColumnAndRow($highestColumnNumber, $rowNumber, collect($item)->sum());
            $rowNumber++;
        }

        $rowNumber = 3;
        foreach ($ytdDeliveries as $accountName => $item) {
            $sheet->setCellValueByColumnAndRow($highestColumnNumber + 1, $rowNumber, collect($item)->sum());
            $rowNumber++;
        }

        // Set AutoSize for all columns
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        // Set the response headers
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="report_ytd_' . time() . '.xlsx"');
        header('Cache-Control: max-age=0');

        // Save the spreadsheet to PHP output
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }
}
