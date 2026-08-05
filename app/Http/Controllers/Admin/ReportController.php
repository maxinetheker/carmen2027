<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Property;

class ReportController extends Controller
{
    public function __invoke()
    {
        $deals = Deal::selectRaw('stage, count(*) as total, sum(value) as value')
            ->groupBy('stage')->get();
        $leads = Lead::selectRaw('source, count(*) as total')
            ->groupBy('source')->orderByDesc('total')->get();

        return view('admin.reports', [
            'deals' => $deals,
            'leads' => $leads,
            'inventory' => Property::selectRaw('status, count(*) as total')
                ->groupBy('status')->get(),
            'wonRevenue' => Deal::where('stage', 'won')->sum('value'),
            'forecast' => Deal::whereNotIn('stage', ['won', 'lost'])
                ->selectRaw('sum(value * probability / 100) as total')->value('total') ?? 0,
        ]);
    }
}
