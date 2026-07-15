<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.45; }
        .memo { page-break-after: always; }
        .memo:last-child { page-break-after: auto; }
        .logo { margin-bottom: 12px; text-align: center; }
        .logo img { height: 72px; width: auto; }
        h1 { font-size: 15px; margin: 4px 0 24px; text-align: center; text-decoration: underline; }
        .line { margin: 9px 0; }
        .label { display: inline-block; font-weight: bold; width: 82px; }
        .subject { font-weight: bold; margin-top: 14px; }
        p { margin: 14px 0; text-align: justify; }
        table { border-collapse: collapse; margin: 18px 0; width: 100%; }
        th, td { border: 1px solid #111827; padding: 7px; }
        th { font-weight: bold; text-align: left; }
        td.amount, th.amount { text-align: right; }
        .total td { font-weight: bold; }
        .sign { margin-top: 38px; }
        .initials { margin-top: 22px; }
    </style>
</head>
<body>
@foreach ($memos as $memo)
    <section class="memo">
        <div class="logo"><img src="{{ public_path('images/kfs-logo.png') }}" alt="Kenya Forest Service logo"></div>
        <h1>INTERNAL MEMO</h1>

        <div class="line"><span class="label">TO</span>: CHIEF CONSERVATOR OF FORESTS</div>
        <div class="line"><span class="label">FROM</span>: {{ $memo['from'] }}</div>
        @if ($memo['through'])
            <div class="line"><span class="label">THRO'</span>: {{ $memo['through'] }}</div>
        @endif
        <div class="line"><span class="label">REF NO</span>: {{ $memo['ref_no'] }}</div>
        <div class="line"><span class="label">DATE</span>: {{ $memo['date'] }}</div>

        <div class="subject">
            SUBJECT: PAYMENT OF {{ $memo['period_subject'] }} SALARY FOR STAFF ON TEMPORARY<br>
            {{ $memo['subject_suffix'] }}
        </div>

        <p>
            The attached is the Contract Muster Roll for the month of {{ $memo['period_sentence'] }}
            for {{ $memo['staff_words'] }} ({{ $memo['staff_count'] }}) {{ $memo['description'] }}.
        </p>

        <p>
            The purpose of this memo is therefore to request your approval of the payment of their salary,
            Employer's NSSF, Employer's Affordable Housing Levy, Employer's NITA Levy and Gross Pay
            amounting to Kshs. {{ $memo['total_formatted'] }} as summarized below;
        </p>

        <table>
            <thead>
            <tr>
                <th style="width: 12%;">S/No.</th>
                <th>Employer's Payments</th>
                <th class="amount" style="width: 28%;">Amount (Kshs.)</th>
            </tr>
            </thead>
            <tbody>
            <tr><td>1.</td><td>Employer's NSSF</td><td class="amount">{{ $memo['employer_nssf_formatted'] }}</td></tr>
            <tr><td>2.</td><td>Employer's Affordable Housing Levy (1.5%)</td><td class="amount">{{ $memo['employer_housing_levy_formatted'] }}</td></tr>
            <tr><td>3.</td><td>Employer's NITA Levy (Kshs. 50/-per staff)</td><td class="amount">{{ $memo['employer_nita_formatted'] }}</td></tr>
            <tr><td>4.</td><td>Gross Pay</td><td class="amount">{{ $memo['gross_pay_formatted'] }}</td></tr>
            <tr class="total"><td></td><td>Total</td><td class="amount">{{ $memo['total_formatted'] }}</td></tr>
            </tbody>
        </table>

        <p>Kindly Approve.</p>

        <div class="sign">
            <strong>{{ $memo['prepared_by'] }}</strong><br>
            {{ $memo['from'] }}
        </div>

        @if ($memo['enclosure'])
            <div class="initials">Encl: {{ $memo['enclosure'] }}</div>
        @endif
        <div class="initials">{{ $memo['initials'] }}</div>
    </section>
@endforeach
</body>
</html>
