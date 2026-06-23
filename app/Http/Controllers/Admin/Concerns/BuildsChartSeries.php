<?php

namespace App\Http\Controllers\Admin\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Collection;

trait BuildsChartSeries
{
    protected function buildDateSeries(Carbon $start, Carbon $end, Collection $sales): array
    {
        $map = $sales->keyBy(fn ($row) => Carbon::parse($row->date)->format('Y-m-d'));
        $labels = [];
        $revenue = [];
        $orders = [];

        $current = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($current <= $endDay) {
            $key = $current->format('Y-m-d');
            $row = $map->get($key);
            $labels[] = $current->format('M d');
            $revenue[] = round((float) ($row->total ?? 0), 2);
            $orders[] = (int) ($row->count ?? 0);
            $current->addDay();
        }

        return compact('labels', 'revenue', 'orders');
    }
}
