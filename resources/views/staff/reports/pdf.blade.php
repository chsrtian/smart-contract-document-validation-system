<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>LCRO Staff Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111827; }
        .header { width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 10px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .logo-cell { width: 75px; }
        .logo { width: 62px; height: 62px; object-fit: contain; }
        .office-line-1 { font-size: 12px; font-weight: 700; }
        .office-line-2 { font-size: 11px; font-weight: 700; }
        .office-line-3 { font-size: 9px; color: #374151; }
        .report-title { margin-top: 4px; font-size: 13px; font-weight: 800; }
        .meta { font-size: 9px; color: #374151; margin-top: 2px; }
        h3 { margin: 10px 0 5px 0; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
        .total-row { font-weight: 700; background: #eef2ff; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <img src="{{ $headerMeta['logo_pdf_path'] }}" class="logo" alt="LCRO Magallanes Logo">
                </td>
                <td>
                    <div class="office-line-1">{{ $headerMeta['office_line_1'] }}</div>
                    <div class="office-line-2">{{ $headerMeta['office_line_2'] }}</div>
                    <div class="office-line-3">{{ $headerMeta['office_line_3'] }}</div>
                    <div class="report-title">{{ $headerMeta['report_title'] }}</div>
                    <div class="meta">
                        Coverage: {{ $filters['date_from'] }} to {{ $filters['date_to'] }} | Generated: {{ $generatedAt->format('Y-m-d H:i:s') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <h3>Summary Totals</h3>
    <table>
        <thead>
            <tr>
                <th>Document Type</th>
                <th class="right">Pending</th>
                <th class="right">Validated</th>
                <th class="right">Rejected</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData['summary_table'] as $type => $row)
                <tr>
                    <td>{{ $typeLabels[$type] ?? ucwords(str_replace('_', ' ', $type)) }}</td>
                    <td class="right">{{ number_format($row['pending']) }}</td>
                    <td class="right">{{ number_format($row['completed']) }}</td>
                    <td class="right">{{ number_format($row['rejected']) }}</td>
                    <td class="right">{{ number_format($row['total']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No records in selected range.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td>Grand Total</td>
                <td class="right">{{ number_format($reportData['grand_total']['pending']) }}</td>
                <td class="right">{{ number_format($reportData['grand_total']['completed']) }}</td>
                <td class="right">{{ number_format($reportData['grand_total']['rejected']) }}</td>
                <td class="right">{{ number_format($reportData['grand_total']['total']) }}</td>
            </tr>
        </tbody>
    </table>

    <h3>Detailed Records</h3>
    <table>
        <thead>
            <tr>
                <th>Registry No.</th>
                <th>Document Type</th>
                <th>Name / Parties</th>
                <th>Date Received</th>
                <th>Date Processed</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData['records'] as $row)
                <tr>
                    <td>{{ $row['registry_no'] }}</td>
                    <td>{{ $typeLabels[$row['document_type']] ?? ucwords(str_replace('_', ' ', $row['document_type'])) }}</td>
                    <td>{{ $row['name_or_parties'] }}</td>
                    <td>{{ $row['date_received'] }}</td>
                    <td>{{ $row['date_processed'] }}</td>
                    <td>{{ $statusLabels[$row['status']] ?? ucfirst($row['status']) }}</td>
                    <td>{{ $row['remarks'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No records found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
