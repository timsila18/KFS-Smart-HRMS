<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.3; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .header { text-align: center; }
        .logo { height: 68px; margin-bottom: 6px; }
        h1 { font-size: 17px; margin: 6px 0 0; text-transform: uppercase; }
        h2 { background: #e7f3ea; border: 1px solid #111827; font-size: 11px; margin: 14px 0 0; padding: 6px; text-transform: uppercase; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #111827; padding: 5px; vertical-align: top; }
        th { background: #f3f7f4; font-weight: bold; text-align: left; }
        .muted { color: #4b5563; }
        .box { border: 1px solid #111827; min-height: 44px; padding: 7px; }
        .sign td { height: 58px; }
        .center { text-align: center; }
    </style>
</head>
<body>
@php
    $periodStart = $appraisal->starts_on ? \Illuminate\Support\Carbon::parse($appraisal->starts_on)->format('j M Y') : '-';
    $periodEnd = $appraisal->ends_on ? \Illuminate\Support\Carbon::parse($appraisal->ends_on)->format('j M Y') : '-';
    $areas = [
        ['Punctuality', 'Attendance, timekeeping, and duty readiness', 'Reports on time, prepared for duty, and supports smooth handover at the station.'],
        ['Teamwork', 'Collaboration and communication', 'Works well with supervisors, rangers, foresters, support teams, and station administration.'],
        ['Forest Service / Quality of Work', 'Service delivery and quality standards', 'Delivers assigned KFS duties to the expected professional and public service standard.'],
        ['Discipline / Reliability', 'Conduct and consistency', 'Follows lawful instructions, upholds conduct standards, and can be relied on operationally.'],
        ['Role Delivery / Job Knowledge', 'Role competence and execution', 'Understands the role and delivers duties with confidence, accuracy, and accountability.'],
    ];
@endphp
<section class="page">
    <div class="header">
        <img class="logo" src="{{ public_path('images/kfs-logo.png') }}" alt="Kenya Forest Service logo">
        <div><strong>KENYA FOREST SERVICE</strong></div>
        <div>P.O. Box 30513 - 00100, Nairobi, Kenya</div>
        <h1>Staff Performance Appraisal Report</h1>
        <div>{{ $periodStart }} - {{ $periodEnd }}</div>
        <div class="muted">Employer ID: {{ $employee->employer ?? 'KFS' }} {{ strtoupper((string) $appraisal->status) }}</div>
    </div>

    <h2>Employee Details</h2>
    <table>
        <tr>
            <th>Name</th><td>{{ $employee->full_name }}</td>
            <th>Staff Number</th><td>{{ $employee->employee_number }}</td>
        </tr>
        <tr>
            <th>Department</th><td>{{ $employee->department?->name ?? 'Not captured' }}</td>
            <th>Designation</th><td>{{ $employee->jobPosition?->title ?? 'Not captured' }}</td>
        </tr>
        <tr>
            <th>Terms of Service</th><td>{{ str($employee->employment_status ?? 'active')->headline() }}</td>
            <th>Supervisor</th><td>{{ $appraisal->supervisor_name ?: 'Not captured' }}</td>
        </tr>
        <tr>
            <th>Appraisal Period</th><td>{{ $periodStart }} - {{ $periodEnd }}</td>
            <th>Status</th><td>{{ strtoupper((string) $appraisal->status) }}</td>
        </tr>
    </table>

    <h2>Employee Self-Review</h2>
    <p><strong>What went well during this period?</strong></p>
    <div class="box">-</div>
    <p><strong>What challenges did you face?</strong></p>
    <div class="box">-</div>
    <p><strong>What support or training would help you perform better?</strong></p>
    <div class="box">-</div>

    <h2>Supervisor Review</h2>
    <table>
        <tr>
            <th style="width: 15%;">Area</th>
            <th style="width: 20%;">Focus</th>
            <th>Expected Standard</th>
            <th class="center" style="width: 11%;">Self</th>
            <th class="center" style="width: 11%;">Supervisor</th>
            <th class="center" style="width: 11%;">Final</th>
        </tr>
        @foreach ($areas as $area)
            <tr>
                <td><strong>{{ $area[0] }}</strong></td>
                <td>{{ $area[1] }}</td>
                <td>{{ $area[2] }}</td>
                <td class="center">Pending</td>
                <td class="center">Pending</td>
                <td class="center">Pending</td>
            </tr>
        @endforeach
    </table>

    <h2>Supervisor Strengths Observed</h2>
    <div class="box">-</div>
    <h2>Areas to Improve</h2>
    <div class="box">-</div>
</section>

<section class="page">
    <div class="header">
        <img class="logo" src="{{ public_path('images/kfs-logo.png') }}" alt="Kenya Forest Service logo">
        <div><strong>KENYA FOREST SERVICE</strong></div>
        <h1>Staff Performance Appraisal Report</h1>
        <div>{{ $periodStart }} - {{ $periodEnd }}</div>
    </div>

    <h2>Supervisor Recommendation</h2>
    <div class="box">-</div>

    <h2>Score Summary</h2>
    <table>
        <tr>
            <th>Employee Share</th><td class="center">0.00/33</td>
            <th>Supervisor Share</th><td class="center">0.00/33</td>
            <th>Final Share</th><td class="center">{{ number_format((float) ($appraisal->overall_score ?? 0), 2) }}/34</td>
            <th>Total</th><td class="center">{{ number_format((float) ($appraisal->overall_score ?? 0), 2) }}/100</td>
        </tr>
    </table>

    <h2>Management Final Review</h2>
    <table>
        <tr><th style="width: 24%;">Management Remark</th><td>-</td></tr>
        <tr><th>Final Outcome</th><td>-</td></tr>
        <tr><th>Next Action</th><td>-</td></tr>
    </table>

    <h2>Employee Acknowledgement</h2>
    <p>I confirm that I have reviewed the contents of this appraisal and that the outcome and comments have been shared with me.</p>

    <h2>Auto-Generated Sign-Off</h2>
    <table class="sign">
        <tr>
            <th>Employee Acknowledgement</th>
            <th>Supervisor Sign-Off</th>
            <th>HR / Management Sign-Off</th>
        </tr>
        <tr>
            <td>{{ $employee->full_name }}<br>Employee<br>Date: -</td>
            <td>{{ $appraisal->supervisor_name ?: 'Supervisor' }}<br>Supervisor<br>Date: -</td>
            <td>Kenya Forest Service<br>HR / Management<br>Date: -</td>
        </tr>
    </table>

    <p class="muted">Generated {{ now()->format('d/m/Y, H:i:s') }} by KFS Smart HRMS</p>
</section>
</body>
</html>
