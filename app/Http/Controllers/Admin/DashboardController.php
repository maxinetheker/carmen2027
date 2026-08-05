<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $pipeline = Deal::latest()->get()->groupBy('stage');

        return view('admin.dashboard', [
            'metrics' => [
                ['label' => 'Prospectos activos', 'value' => Lead::whereNotIn('status', ['won', 'lost'])->count(), 'trend' => '+12%'],
                ['label' => 'Propiedades', 'value' => Property::where('status', 'available')->count(), 'trend' => 'disponibles'],
                ['label' => 'Oportunidades', 'value' => '$'.number_format(Deal::whereNotIn('stage', ['won', 'lost'])->sum('value') / 1000).'K', 'trend' => 'proyectado'],
                ['label' => 'Conversión', 'value' => $this->conversion().'%', 'trend' => 'últimos prospectos'],
            ],
            'pipeline' => $pipeline,
            'tasks' => TaskItem::where('status', '!=', 'done')->orderBy('due_at')->take(6)->get(),
            'appointments' => Appointment::where('starts_at', '>=', now())
                ->orderBy('starts_at')->take(5)->get(),
            'activities' => Activity::with('user')->latest('happened_at')->take(8)->get(),
        ]);
    }

    private function conversion(): int
    {
        $total = Lead::count();
        return $total ? (int) round(Lead::where('status', 'won')->count() * 100 / $total) : 0;
    }
}
