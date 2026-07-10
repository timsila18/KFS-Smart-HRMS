<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#173526}.brand{display:table;width:100%;margin-bottom:12px}.brand-logo{display:table-cell;width:88px;vertical-align:middle}.brand-logo img{width:76px;height:auto}.brand-copy{display:table-cell;vertical-align:middle}table{width:100%;border-collapse:collapse}td,th{border:1px solid #d9e2dc;padding:6px}th{background:#26734d;color:white;text-align:left}</style></head>
<body>
<div class="brand"><div class="brand-logo"><img src="{{ public_path('images/kfs-logo.png') }}" alt="Kenya Forest Service logo"></div><div class="brand-copy"><h2>KFS Smart HRMS Payslip</h2><p>{{ $run->run_number }} - {{ $employee->full_name }} ({{ $employee->employee_number }})</p></div></div>
<table><thead><tr><th>Code</th><th>Name</th><th>Amount</th></tr></thead><tbody>
@foreach($items as $item)<tr><td>{{ $item->payCode?->code }}</td><td>{{ $item->payCode?->name }}</td><td>{{ number_format($item->amount, 2) }}</td></tr>@endforeach
</tbody></table>
</body></html>
