<?php

namespace App\Filament\Widgets;

use App\Models\Property;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PropertyStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Statut des Maisons';
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $statuses = Property::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $statusLabels = [
            'available' => 'Disponible',
            'rented' => 'Loué',
            'maintenance' => 'Maintenance',
            'unavailable' => 'Indisponible',
        ];

        $labels = $statuses->map(fn ($s) => $statusLabels[$s->status] ?? ucfirst($s->status))->toArray();

        $total = $statuses->sum('count');
        $percentages = $statuses->map(fn ($s) => $total > 0 ? round(($s->count / $total) * 100) : 0);

        return [
            'datasets' => [
                [
                    'data' => $statuses->pluck('count')->toArray(),
                    'backgroundColor' => [
                        '#10B981', // available - green
                        '#3B82F6', // rented - blue
                        '#F59E0B', // maintenance - orange
                        '#6B7280', // unavailable - gray
                    ],
                ],
            ],
            'labels' => array_map(function ($label, $percentage) {
                return "{$label} ({$percentage}%)";
            }, $labels, $percentages->toArray()),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
