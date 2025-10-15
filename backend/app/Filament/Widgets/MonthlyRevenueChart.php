<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MonthlyRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Évolution des Revenus Mensuels';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = null;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = Payment::where('status', 'completed')
            ->where('completed_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(completed_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Revenus (FCFA)',
                    'data' => $data->pluck('total')->toArray(),
                    'borderColor' => '#1E3A8A',
                    'backgroundColor' => 'rgba(30, 58, 138, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $data->pluck('month')->map(function ($m) {
                return \Carbon\Carbon::parse($m)->translatedFormat('M Y');
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return new Intl.NumberFormat("fr-FR").format(value) + " FCFA"; }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return new Intl.NumberFormat("fr-FR").format(context.parsed.y) + " FCFA"; }',
                    ],
                ],
            ],
        ];
    }
}
