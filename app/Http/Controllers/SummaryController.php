<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Area;
use App\Region;
use App\Branch;
use App\Transaction;
use DB;
use Carbon\Carbon;

class SummaryController extends Controller
{
    public function index()
    {
        $regions = Region::where('status', 1)
            ->orderBy('region_name')
            ->get();
        $areas = Area::where('status', 1)
            ->orderBy('area_name')
            ->get();
        $branches = Branch::where('status', 1)
            ->orderBy('branch_name')
            ->get();
        return view('reports.summary.index', compact('regions', 'areas', 'branches'));
    }

    public function create() { }

    public function store(Request $request)
    {
        $request->validate([
            'date_from' => 'required|before_or_equal:date_to',
            'date_to' => 'required|after_or_equal:date_from',
        ]);

        CommonController::fixUnpostedTransactions($request->date_from, $request->date_to);

        $row = 6;
        $file_name = 'Summary_Report_'.auth()->user()->id.'_'.Carbon::now()->format('ymdHis').'.xlsx';
        $style_data_1 = [
            'font' => [
                'bold' => true,
            ],
            'borders' => [
                'left' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        $style_data_2 = [
            'borders' => [
                'left' => [
                    'borderStyle' => Border::BORDER_THICK,
                ],
                'top' => [
                    'borderStyle' => Border::BORDER_THICK,
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_THICK,
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_THICK,
                ],
            ],
        ];
        $spreadsheet = new Spreadsheet();
        $with_transaction = false;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Summary Report for '.Carbon::parse($request->date_from)->format('m/d/Y').' - '.Carbon::parse($request->date_to)->format('m/d/Y'));
        $sheet->setCellValue('A2', 'Date and Time Generated: '.Carbon::now()->format('m/d/Y H:i:s'));
        $sheet->setCellValue('A3', 'Generated By: '.auth()->user()->full_name);
        $sheet->setCellValue('B4', 'FAMILY PROTECT');
        $sheet->setCellValue('F4', 'FAMILY PROTECT PLUS');
        $sheet->setCellValue('J4', 'KWARTA PADALA');
        $sheet->setCellValue('N4', 'PINOY PROTECT');
        $sheet->setCellValue('R4', 'PINOY PROTECT PLUS');
        $sheet->setCellValue('A5', 'Row Labels');
        $sheet->setCellValue('B5', '1-POSTED');
        $sheet->setCellValue('C5', '2-UNPOSTED');
        $sheet->setCellValue('D5', '3-DELETED');
        $sheet->setCellValue('E5', '4-CANCELLED');
        $sheet->setCellValue('F5', '1-POSTED');
        $sheet->setCellValue('G5', '2-UNPOSTED');
        $sheet->setCellValue('H5', '3-DELETED');
        $sheet->setCellValue('I5', '4-CANCELLED');
        $sheet->setCellValue('J5', '1-POSTED');
        $sheet->setCellValue('K5', '2-UNPOSTED');
        $sheet->setCellValue('L5', '3-DELETED');
        $sheet->setCellValue('M5', '4-CANCELLED');
        $sheet->setCellValue('N5', '1-POSTED');
        $sheet->setCellValue('O5', '2-UNPOSTED');
        $sheet->setCellValue('P5', '3-DELETED');
        $sheet->setCellValue('Q5', '4-CANCELLED');
        $sheet->setCellValue('R5', '1-POSTED');
        $sheet->setCellValue('S5', '2-UNPOSTED');
        $sheet->setCellValue('T5', '3-DELETED');
        $sheet->setCellValue('U5', '4-CANCELLED');
        $sheet->getStyle('A1:U5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('D9E1F2');
        // $sheet->getStyle('A1:A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('000000');
        $sheet->getStyle('B4:U5')->getAlignment()->setHorizontal('center');
        $spreadsheet->getActiveSheet()->mergeCells('B4:E4');
        $spreadsheet->getActiveSheet()->mergeCells('F4:I4');
        $spreadsheet->getActiveSheet()->mergeCells('J4:M4');
        $spreadsheet->getActiveSheet()->mergeCells('N4:Q4');
        $spreadsheet->getActiveSheet()->mergeCells('R4:U4');
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $query = DB::table('transactions AS t')
            ->leftJoin('users as u', 't.userid_created', '=', 'u.id')
            ->leftJoin('branches as b', 'b.id', '=', 'u.branch_id')
            // ->where('t.status', '!=', 'deleted')
            ->whereBetween('t.date_issued', [$request->date_from, $request->date_to])
            // ->groupBy('u.id', 'u.full_name')
            // ->groupBy('t.userbranch')
            ->groupBy('b.branch_name')
            // ->orderBy('u.full_name')
            // ->orderBy('t.userbranch')
            ->orderBy('b.branch_name')
            // ->select('u.full_name',
            // ->select('t.userbranch',
            ->select('b.branch_name',
                DB::raw("SUM(IF(t.type = 'A' AND t.posted = 1 AND t.status IN ('active', 'edited'), t.units, NULL)) AS a_count_1"),
                DB::raw("SUM(IF(t.type = 'AO' AND t.posted = 1 AND t.status IN ('active', 'edited'), t.units, NULL)) AS ao_count_1"),
                DB::raw("SUM(IF(t.type = 'B' AND t.posted = 1 AND t.status IN ('active', 'edited'), t.units, NULL)) AS b_count_1"),
                DB::raw("SUM(IF(t.type = 'D' AND t.posted = 1 AND t.status IN ('active', 'edited'), t.units, NULL)) AS d_count_1"),
                DB::raw("SUM(IF(t.type = 'R' AND t.posted = 1 AND t.status IN ('active', 'edited'), t.units, NULL)) AS r_count_1"),
                DB::raw("SUM(IF(t.type = 'A' AND (t.posted = 0 OR t.posted IS NULL) AND t.status IN ('active', 'edited'), t.units, NULL)) AS a_count_2"),
                DB::raw("SUM(IF(t.type = 'AO' AND (t.posted = 0 OR t.posted IS NULL) AND t.status IN ('active', 'edited'), t.units, NULL)) AS ao_count_2"),
                DB::raw("SUM(IF(t.type = 'B' AND (t.posted = 0 OR t.posted IS NULL) AND t.status IN ('active', 'edited'), t.units, NULL)) AS b_count_2"),
                DB::raw("SUM(IF(t.type = 'D' AND (t.posted = 0 OR t.posted IS NULL) AND t.status IN ('active', 'edited'), t.units, NULL)) AS d_count_2"),
                DB::raw("SUM(IF(t.type = 'R' AND (t.posted = 0 OR t.posted IS NULL) AND t.status IN ('active', 'edited'), t.units, NULL)) AS r_count_2"),
                DB::raw("SUM(IF(t.type = 'A' AND t.status = 'deleted', t.units, NULL)) AS a_count_3"),
                DB::raw("SUM(IF(t.type = 'AO' AND t.status = 'deleted', t.units, NULL)) AS ao_count_3"),
                DB::raw("SUM(IF(t.type = 'B' AND t.status = 'deleted', t.units, NULL)) AS b_count_3"),
                DB::raw("SUM(IF(t.type = 'D' AND t.status = 'deleted', t.units, NULL)) AS d_count_3"),
                DB::raw("SUM(IF(t.type = 'R' AND t.status = 'deleted', t.units, NULL)) AS r_count_3"),
                DB::raw("SUM(IF(t.type = 'A' AND t.status = 'cancelled', t.units, NULL)) AS a_count_4"),
                DB::raw("SUM(IF(t.type = 'AO' AND t.status = 'cancelled', t.units, NULL)) AS ao_count_4"),
                DB::raw("SUM(IF(t.type = 'B' AND t.status = 'cancelled', t.units, NULL)) AS b_count_4"),
                DB::raw("SUM(IF(t.type = 'D' AND t.status = 'cancelled', t.units, NULL)) AS d_count_4"),
                DB::raw("SUM(IF(t.type = 'R' AND t.status = 'cancelled', t.units, NULL)) AS r_count_4")
            )
            ->when($request->input('product'), function($query, $products) {
                    return $query->whereIn('type', $products);
                })
            ->when($request->input('branch'), function($query, $branches) {
                    return $query->whereIn('b.branch_name', $branches);
                })
            ->when($request->input('area'), function($query, $areas) {
                    $branches = Branch::whereIn('area_id', $areas)->pluck('branch_name');
                    return $query->whereIn('b.branch_name', $branches);
                })
            ->when($request->input('region'), function($query, $regions) {
                    $areas = Area::whereIn('region_id', $regions)->pluck('id');
                    $branches = Branch::whereIn('area_id', $areas)->pluck('branch_name');
                    return $query->whereIn('b.branch_name', $branches);
                })
            ;

        // if($request->product) {
        //     $query = $query->whereIn('t.type', $request->product);
        // }
        // if($request->branch) {
        //     // $query = $query->whereIn('t.userbranch', $request->branch);
        //     $query = $query->whereIn('b.branch_name', $request->branch);
        // }
        // if($request->area) {
        //     $branches = Branch::whereIn('area_id', $request->area)->pluck('branch_name');
        //     // $query = $query->whereIn('t.userbranch', $branches);
        //     $query = $query->whereIn('b.branch_name', $branches);
        // }
        // if($request->region) {
        //     $areas = Area::whereIn('region_id', $request->region)->pluck('id');
        //     $branches = Branch::whereIn('area_id', $areas)->pluck('branch_name');
        //     // $query = $query->whereIn('t.userbranch', $branches);
        //     $query = $query->whereIn('b.branch_name', $branches);
        // }
        
        $query->chunk(100, function($transactions) use ($spreadsheet, &$row, &$with_transaction, $style_data_1) { // READ AND WRITE $row
            $sheet = $spreadsheet->getActiveSheet();
            foreach($transactions as $transaction) {
                $with_transaction = true;
                // $sheet->setCellValue('A'.$row, $transaction->full_name);
                // $sheet->setCellValue('A'.$row, $transaction->userbranch);
                $sheet->setCellValue('A'.$row, $transaction->branch_name);
                $sheet->setCellValue('B'.$row, $transaction->d_count_1);
                $sheet->setCellValue('C'.$row, $transaction->d_count_2);
                $sheet->setCellValue('D'.$row, $transaction->d_count_3);
                $sheet->setCellValue('E'.$row, $transaction->d_count_4);
                $sheet->setCellValue('F'.$row, $transaction->a_count_1);
                $sheet->setCellValue('G'.$row, $transaction->a_count_2);
                $sheet->setCellValue('H'.$row, $transaction->a_count_3);
                $sheet->setCellValue('I'.$row, $transaction->a_count_4);
                $sheet->setCellValue('J'.$row, $transaction->ao_count_1);
                $sheet->setCellValue('K'.$row, $transaction->ao_count_2);
                $sheet->setCellValue('L'.$row, $transaction->ao_count_3);
                $sheet->setCellValue('M'.$row, $transaction->ao_count_4);
                $sheet->setCellValue('N'.$row, $transaction->r_count_1);
                $sheet->setCellValue('O'.$row, $transaction->r_count_2);
                $sheet->setCellValue('P'.$row, $transaction->r_count_3);
                $sheet->setCellValue('Q'.$row, $transaction->r_count_4);
                $sheet->setCellValue('R'.$row, $transaction->b_count_1);
                $sheet->setCellValue('S'.$row, $transaction->b_count_2);
                $sheet->setCellValue('T'.$row, $transaction->b_count_3);
                $sheet->setCellValue('U'.$row, $transaction->b_count_4);

                foreach(range('A', 'U') as $column) {
                    $sheet->getStyle($column.$row)->applyFromArray($style_data_1);
                }

                $row++;
            }
        });

        // TOTALS
        $sheet->setCellValue('A'.$row, 'Grand Total');
        $sheet->setCellValue('B'.$row, '=SUM(B6:B'.($row - 1).')');
        $sheet->setCellValue('C'.$row, '=SUM(C6:C'.($row - 1).')');
        $sheet->setCellValue('D'.$row, '=SUM(D6:D'.($row - 1).')');
        $sheet->setCellValue('E'.$row, '=SUM(E6:E'.($row - 1).')');
        $sheet->setCellValue('F'.$row, '=SUM(F6:F'.($row - 1).')');
        $sheet->setCellValue('G'.$row, '=SUM(G6:G'.($row - 1).')');
        $sheet->setCellValue('H'.$row, '=SUM(H6:H'.($row - 1).')');
        $sheet->setCellValue('I'.$row, '=SUM(I6:I'.($row - 1).')');
        $sheet->setCellValue('J'.$row, '=SUM(J6:J'.($row - 1).')');
        $sheet->setCellValue('K'.$row, '=SUM(K6:K'.($row - 1).')');
        $sheet->setCellValue('L'.$row, '=SUM(L6:L'.($row - 1).')');
        $sheet->setCellValue('M'.$row, '=SUM(M6:M'.($row - 1).')');
        $sheet->setCellValue('N'.$row, '=SUM(N6:N'.($row - 1).')');
        $sheet->setCellValue('O'.$row, '=SUM(O6:O'.($row - 1).')');
        $sheet->setCellValue('P'.$row, '=SUM(P6:P'.($row - 1).')');
        $sheet->setCellValue('Q'.$row, '=SUM(Q6:Q'.($row - 1).')');
        $sheet->setCellValue('R'.$row, '=SUM(R6:R'.($row - 1).')');
        $sheet->setCellValue('S'.$row, '=SUM(S6:S'.($row - 1).')');
        $sheet->setCellValue('T'.$row, '=SUM(T6:T'.($row - 1).')');
        $sheet->setCellValue('U'.$row, '=SUM(U6:U'.($row - 1).')');

        // FINAL TOUCH
        $sheet->getStyle('A'.$row.':U'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('D9E1F2');
        $sheet->getStyle('B6:U'.($row + 1))->getNumberFormat()->setFormatCode('#,##0_-');
        foreach(range('A', 'U') as $column) {
            $sheet->getStyle($column.'4')->applyFromArray($style_data_1);
            $sheet->getStyle($column.'5')->applyFromArray($style_data_1);
            $sheet->getStyle($column.$row)->applyFromArray($style_data_1);
        }
        $sheet->getStyle('B4:E'.$row)->applyFromArray($style_data_2);
        $sheet->getStyle('F4:I'.$row)->applyFromArray($style_data_2);
        $sheet->getStyle('J4:M'.$row)->applyFromArray($style_data_2);
        $sheet->getStyle('N4:Q'.$row)->applyFromArray($style_data_2);
        $sheet->getStyle('R4:U'.$row)->applyFromArray($style_data_2);

        /* AUTOSIZE START */
        $cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);
        foreach ($cellIterator as $cell) {
            $sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
        }
        /* AUTOSIZE END */

        if($with_transaction) {
            $writer = new Xlsx($spreadsheet);
            // if(strpos(url()->current(), 'public') !== false) $public = '../';
            // else $public = '';
            // $result = $writer->save($public.'public/storage/downloads/'.$file_name);
            // $result = $writer->save('public/storage/downloads/'.$file_name);
            $result = $writer->save(public_path('storage/').$file_name);
            session([
                'download' => $file_name,
                'success' => 'File Created Successfully'
            ]);
        } else {
            session(['error' => 'No records found']);
        }
        return redirect()->route('summary.index');
    }

    public function show($id) { }
    public function edit($id) { }
    public function update(Request $request, $id) { }
    public function destroy($id) { }
}
