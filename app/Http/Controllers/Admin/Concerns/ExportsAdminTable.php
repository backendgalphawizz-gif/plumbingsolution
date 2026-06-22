<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Support\AdminValidation as V;
use App\Support\AdminExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait ExportsAdminTable
{
    protected function applyDateRange(Builder $query, Request $request, string $column = 'created_at'): Builder
    {
        $request->validate(V::dateRangeRules());

        return $query
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate($column, '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate($column, '<=', $request->date_to));
    }

    protected function exportResponse(Request $request, string $basename, string $title, array $headers, iterable $rows)
    {
        $request->validate(['format' => ['required', 'in:csv,pdf']]);

        return AdminExport::download($request->format, $basename, $title, $headers, $rows);
    }
}
