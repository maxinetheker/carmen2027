<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\TaskItem;
use App\Services\Export\CrmDataExport;
use App\Services\Export\WeeklySummaryExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    public function index()
    {
        $week = [now()->startOfWeek(), now()->endOfWeek()];

        return view('admin.exports', [
            'from' => $week[0]->toDateString(),
            'to' => $week[1]->toDateString(),
            'sections' => CrmDataExport::SECTIONS,
            'counts' => [
                'buyers' => $this->countByParty(['buyer', 'both']),
                'sellers' => $this->countByParty(['seller', 'both']),
                'visits' => Appointment::whereBetween('starts_at', $week)->count(),
                'tasks' => TaskItem::where('status', '!=', 'done')->count(),
            ],
        ]);
    }

    public function weekly(Request $request, WeeklySummaryExport $export)
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);
        $from = Carbon::parse($data['from'])->startOfDay();
        $to = Carbon::parse($data['to'])->endOfDay();

        return $this->download(
            $export->build($from, $to),
            'resumen-semanal-'.$from->format('Y-m-d').'.xlsx'
        );
    }

    public function data(Request $request, CrmDataExport $export)
    {
        $data = $request->validate([
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['string', 'in:'.implode(',', array_keys(CrmDataExport::SECTIONS))],
        ]);

        return $this->download(
            $export->build($data['sections']),
            'crm-carmen-mestanza-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    /**
     * El libro se arma en un archivo temporal y se envía con `deleteFileAfterSend`:
     * mantenerlo en memoria haría reventar el límite de PHP con muchos registros.
     */
    private function download($workbook, string $name)
    {
        $path = storage_path('app/'.Str::uuid().'.xlsx');
        $workbook->save($path);

        return response()->download($path, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    private function countByParty(array $types): int
    {
        return Lead::whereIn('party_type', $types)->count()
            + Contact::whereIn('party_type', $types)->count();
    }
}
