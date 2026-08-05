<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Property;

class ReportController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'deals' => Deal::selectRaw('stage, count(*) as total, sum(value) as value')
                ->groupBy('stage')->get()->map(fn ($r) => [
                    'stage' => $r->stage, 'total' => (int) $r->total, 'value' => (float) $r->value,
                ]),
            'leads' => Lead::selectRaw('source, count(*) as total')
                ->groupBy('source')->orderByDesc('total')->get()->map(fn ($r) => [
                    'source' => $r->source, 'total' => (int) $r->total,
                ]),
            'inventory' => Property::selectRaw('status, count(*) as total')
                ->groupBy('status')->get()->map(fn ($r) => [
                    'status' => $r->status, 'total' => (int) $r->total,
                ]),
            'won_revenue' => (float) Deal::where('stage', 'won')->sum('value'),
            'forecast' => (float) (Deal::whereNotIn('stage', ['won', 'lost'])
                ->selectRaw('sum(value * probability / 100) as total')->value('total') ?? 0),
        ]);
    }
}
