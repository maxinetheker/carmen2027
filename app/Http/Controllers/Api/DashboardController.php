<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\TaskItemResource;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Property;
use App\Models\TaskItem;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $pipeline = Deal::query()->selectRaw('stage, count(*) as total, sum(value) as value')
            ->groupBy('stage')->get()->map(fn ($row) => [
                'stage' => $row->stage,
                'total' => (int) $row->total,
                'value' => (float) $row->value,
            ]);

        return response()->json([
            'metrics' => [
                'active_leads' => Lead::whereNotIn('status', ['won', 'lost'])->count(),
                'available_properties' => Property::where('status', 'available')->count(),
                'open_pipeline_value' => (float) Deal::whereNotIn('stage', ['won', 'lost'])->sum('value'),
                'conversion_rate' => $this->conversion(),
            ],
            'pipeline' => $pipeline,
            'tasks' => TaskItemResource::collection(
                TaskItem::where('status', '!=', 'done')->orderBy('due_at')->take(6)->get()
            ),
            'appointments' => AppointmentResource::collection(
                Appointment::where('starts_at', '>=', now())->orderBy('starts_at')->take(5)->get()
            ),
            'activities' => Activity::with('user')->latest('happened_at')->take(8)->get()->map(fn ($a) => [
                'id' => $a->id,
                'type' => $a->type,
                'description' => $a->description,
                'user_name' => $a->user?->name,
                'happened_at' => $a->happened_at?->toIso8601String(),
            ]),
        ]);
    }

    private function conversion(): int
    {
        $total = Lead::count();

        return $total ? (int) round(Lead::where('status', 'won')->count() * 100 / $total) : 0;
    }
}
