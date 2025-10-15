<?php

namespace App\Filament\Widgets;

use App\Models\Property;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopDistrictsChart extends ChartWidget
{
    protected static ?string $heading = 'Top Quartiers Recherchés';
    protected static ?int $sort = 4;
    protected static ?string $maxHeight = '300px';
    
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $districts = Property::select('district', DB::raw('SUM(views_count) as total_views'))
            ->groupBy('district')
            ->orderByDesc('total_views')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Vues',
                    'data' => $districts->pluck('total_views')->toArray(),
                    'backgroundColor' => '#3B82F6',
                ],
            ],
            'labels' => $districts->pluck('district')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
