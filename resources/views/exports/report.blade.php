<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #17251d; font-size: 11px; }
        .brand { display: table; width: 100%; margin-bottom: 12px; }
        .brand-logo { display: table-cell; width: 88px; vertical-align: middle; }
        .brand-logo img { width: 76px; height: auto; }
        .brand-copy { display: table-cell; vertical-align: middle; }
        h1 { color: #1f5f3f; font-size: 20px; margin-bottom: 4px; }
        .meta { color: #52665a; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1f5f3f; color: #fff; text-align: left; padding: 7px; }
        td { border-bottom: 1px solid #d9e4dc; padding: 6px; vertical-align: top; }
        tr:nth-child(even) td { background: #f4f8f5; }
    </style>
</head>
<body>
    <div class="brand">
        <div class="brand-logo"><img src="{{ public_path('images/kfs-logo.png') }}" alt="Kenya Forest Service logo"></div>
        <div class="brand-copy">
            <h1>{{ $report->name }}</h1>
            <div class="meta">
                KFS Smart HRMS | Generated {{ $generatedAt->format('Y-m-d H:i') }}
            </div>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ str($column)->headline() }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($columns as $column)
                        <td>{{ data_get($row, $column) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($columns), 1) }}">No records matched the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
