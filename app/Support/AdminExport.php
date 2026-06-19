<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminExport
{
    public static function download(string $format, string $basename, string $title, array $headers, iterable $rows)
    {
        $filename = $basename.'-'.now()->format('Y-m-d');

        return match ($format) {
            'pdf' => self::pdf($filename, $title, $headers, $rows),
            default => self::csv($filename, $headers, $rows),
        };
    }

    public static function csv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, is_array($row) ? $row : (array) $row);
            }

            fclose($handle);
        }, $filename.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function pdf(string $filename, string $title, array $headers, iterable $rows)
    {
        $rows = $rows instanceof Collection ? $rows : collect($rows);

        return Pdf::loadView('admin.exports.table-pdf', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'generatedAt' => now()->format('M d, Y g:i A'),
        ])->download($filename.'.pdf');
    }
}
