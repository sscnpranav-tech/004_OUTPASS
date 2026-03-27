<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Outpass;

class OutpassDocumentController extends Controller
{
    public function generate($schedule_id, $document_type, $house = null)
    {
        $schedule = Schedule::findOrFail($schedule_id);

        $query = Outpass::where('type', $schedule->type)
            ->where('from_date', $schedule->from_date)
            ->where('status', 'active');

        switch ($document_type) {
            case 'notesheet':
                $outpasses = $query->get();
                $matrix = $this->buildNotesheetMatrix($outpasses);
                return view('admin.reports.print-notesheet', [
                    'schedule' => $schedule,
                    'total' => $outpasses->count(),
                    'matrix' => $matrix['table'],
                    'final_grand' => $matrix['grand_total'],
                    'houses' => $matrix['houses']
                ]);

            case 'slips':
                // Grouped by house for the individual cards
                $outpasses = $query->orderBy('house')->orderBy('class')->get()->groupBy('house');
                return view('admin.reports.print-slips', [
                    'schedule' => $schedule,
                    'groupedOutpasses' => $outpasses
                ]);

            case 'master':
                $outpasses = $query->orderBy('house')->orderBy('class')->get()->groupBy('house');
                $title = "LIST OF CADETS APPLIED FOR OUTPASS ON " . \Carbon\Carbon::parse($schedule->from_date)->format('d-M-Y');
                return view('admin.reports.print-list', compact('schedule', 'outpasses', 'title'));

            case 'girls':
                $outpasses = $query->where('gender', 'female')->orderBy('house')->orderBy('class')->get()->groupBy('house');
                $title = "LIST OF GIRLS APPLIED FOR OUTPASS ON " . \Carbon\Carbon::parse($schedule->from_date)->format('d-M-Y');
                return view('admin.reports.print-list', compact('schedule', 'outpasses', 'title'));

            case 'house-boys':
                $outpasses = $query->where('gender', 'male')->where('house', $house)->orderBy('class')->get()->groupBy('house');
                $title = strtoupper($house) . " HOUSE BOYS OUTPASS LIST - " . \Carbon\Carbon::parse($schedule->from_date)->format('d-M-Y');
                return view('admin.reports.print-list', compact('schedule', 'outpasses', 'title'));
        }
    }

    /**
     * Replicates your exact Class/House cross-tab matrix logic
     */
    private function buildNotesheetMatrix($outpasses)
{
    // Failsafe: Handle empty data gracefully
    if ($outpasses->isEmpty()) {
        return ['table' => [], 'grand_total' => 0, 'houses' => []];
    }

    // 1. Dynamically extract exact houses directly from the database
    $houses = $outpasses->pluck('house')->map(fn($h) => strtoupper(trim($h)))->unique()->sort()->values()->toArray();

    // 2. Dynamically extract exact classes directly from the database
    $classes = $outpasses->pluck('class')->map(fn($c) => strtoupper(trim($c)))->unique()->values()->toArray();

    // 3. Mathematical Roman Numeral Sorting (Zero Hardcoded Classes)
    usort($classes, function($a, $b) {
        $romanToInt = function($roman) {
            $values = ['M'=>1000, 'D'=>500, 'C'=>100, 'L'=>50, 'X'=>10, 'V'=>5, 'I'=>1];
            $result = 0;
            $length = strlen($roman);

            for ($i = 0; $i < $length; $i++) {
                $current = $values[$roman[$i]] ?? 0;
                $next = $values[$roman[$i + 1] ?? ''] ?? 0;
                $result += ($current < $next) ? -$current : $current;
            }

            // If the conversion results in 0 (e.g., standard text like "Nursery"), assign a high weight to push it to the end.
            return $result > 0 ? $result : 999;
        };

        return $romanToInt($a) <=> $romanToInt($b);
    });

    // 4. Tally the counts strictly based on the dynamic data
    $counts = [];
    foreach ($outpasses as $row) {
        $key = strtoupper(trim($row->class)) . '|' . strtoupper(trim($row->house));
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }

    // 5. Build the dynamic rows and columns
    $result = [];
    $grandTotals = array_fill_keys($houses, 0);
    $grandTotal  = 0;

    foreach ($classes as $class) {
        $row = ['class' => $class];
        $rowTotal = 0;
        foreach ($houses as $house) {
            $count = $counts[$class . '|' . $house] ?? 0;
            $row[$house] = $count > 0 ? $count : '-';
            $rowTotal += $count;
            $grandTotals[$house] += $count;
        }
        $row['Total'] = $rowTotal > 0 ? $rowTotal : '-';
        $grandTotal += $rowTotal;
        $result[] = $row;
    }

    // 6. Build the Grand Total footer row
    $grandRow = ['class' => 'Total'];
    foreach ($houses as $house) {
        $grandRow[$house] = $grandTotals[$house] > 0 ? $grandTotals[$house] : '-';
    }
    $grandRow['Total'] = $grandTotal > 0 ? $grandTotal : '-';
    $result[] = $grandRow;

    return [
        'table' => $result,
        'grand_total' => $grandTotal,
        'houses' => $houses
    ];
}
}
