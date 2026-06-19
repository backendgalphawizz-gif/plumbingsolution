<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #0f172a; }
        .meta { font-size: 10px; color: #64748b; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; color: #334155; font-size: 10px; text-transform: uppercase; letter-spacing: .03em; padding: 8px 6px; border: 1px solid #e2e8f0; text-align: left; }
        td { padding: 7px 6px; border: 1px solid #e2e8f0; vertical-align: top; }
        tr:nth-child(even) td { background: #fafbfc; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">Generated on {{ $generatedAt }}</div>
    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($headers) }}">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
