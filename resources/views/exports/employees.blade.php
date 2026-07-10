<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #173526; }
        .brand { display: table; width: 100%; margin-bottom: 12px; }
        .brand-logo { display: table-cell; width: 88px; vertical-align: middle; }
        .brand-logo img { width: 76px; height: auto; }
        .brand-copy { display: table-cell; vertical-align: middle; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #26734d; color: #fff; text-align: left; }
        th, td { border: 1px solid #d9e2dc; padding: 6px; }
    </style>
</head>
<body>
    <div class="brand">
        <div class="brand-logo"><img src="{{ public_path('images/kfs-logo.png') }}" alt="Kenya Forest Service logo"></div>
        <div class="brand-copy">
            <h1>Kenya Forest Service Employee Register</h1>
            <p>KFS Smart HRMS | Generated {{ now()->format('d M Y H:i') }}</p>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Employee No</th>
                <th>Name</th>
                <th>Status</th>
                <th>Station</th>
                <th>Department</th>
                <th>Position</th>
                <th>Hire Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($employees as $employee)
                <tr>
                    <td>{{ $employee->employee_number }}</td>
                    <td>{{ $employee->full_name }}</td>
                    <td>{{ $employee->employment_status }}</td>
                    <td>{{ $employee->station?->name }}</td>
                    <td>{{ $employee->department?->name }}</td>
                    <td>{{ $employee->jobPosition?->title }}</td>
                    <td>{{ $employee->hire_date?->format('d M Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
