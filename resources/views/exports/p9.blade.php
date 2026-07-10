<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#173526}.brand{display:table;width:100%;margin-bottom:12px}.brand-logo{display:table-cell;width:88px;vertical-align:middle}.brand-logo img{width:76px;height:auto}.brand-copy{display:table-cell;vertical-align:middle}table{width:100%;border-collapse:collapse}td,th{border:1px solid #d9e2dc;padding:6px}th{background:#26734d;color:white;text-align:left}</style></head>
<body>
<div class="brand"><div class="brand-logo"><img src="{{ public_path('images/kfs-logo.png') }}" alt="Kenya Forest Service logo"></div><div class="brand-copy"><h2>KFS Smart HRMS P9 Summary</h2><p>{{ $run->run_number }}</p></div></div>
<table><thead><tr><th>Employee</th><th>Taxable Earnings</th><th>PAYE</th></tr></thead><tbody>
@foreach($run->items->groupBy('employee_id') as $items)
<tr>
<td>{{ $items->first()->employee->full_name }}</td>
<td>{{ number_format($items->filter(fn($i) => $i->amount > 0 && $i->payCode?->is_taxable)->sum('amount'), 2) }}</td>
<td>{{ number_format(abs($items->filter(fn($i) => $i->payCode?->component_subtype === 'paye')->sum('amount')), 2) }}</td>
</tr>
@endforeach
</tbody></table>
</body></html>
