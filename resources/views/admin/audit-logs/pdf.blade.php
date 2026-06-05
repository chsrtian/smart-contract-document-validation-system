<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Audit Logs Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        h1 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .meta {
            font-size: 9px;
            color: #666;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 6px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
        }
        td {
            border: 1px solid #e5e7eb;
            padding: 5px;
            font-size: 8px;
        }
        .severity-info { color: #6b7280; }
        .severity-warning { color: #f59e0b; }
        .severity-critical { color: #ef4444; font-weight: bold; }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Audit Logs Export</h1>
        <div class="meta">
            <strong>Generated:</strong> {{ $exportDate->format('F d, Y H:i:s') }}<br>
            <strong>Total Records:</strong> {{ $auditLogs->count() }}<br>
            @if(!empty($filters))
                <strong>Filters Applied:</strong>
                @if(isset($filters['date_from'])) From {{ $filters['date_from'] }} @endif
                @if(isset($filters['date_to'])) To {{ $filters['date_to'] }} @endif
                @if(isset($filters['severity'])) | Severity: {{ ucfirst($filters['severity']) }} @endif
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Timestamp</th>
                <th style="width: 10%;">User</th>
                <th style="width: 15%;">Action</th>
                <th style="width: 10%;">Target</th>
                <th style="width: 8%;">Severity</th>
                <th style="width: 10%;">IP Address</th>
                <th style="width: 35%;">Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($auditLogs as $log)
                <tr>
                    <td>{{ $log->timestamp->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->user_name }}</td>
                    <td>{{ $log->action_type }}</td>
                    <td>
                        @if($log->target_entity_type)
                            {{ class_basename($log->target_entity_type) }} #{{ $log->target_entity_id }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="severity-{{ $log->severity }}">{{ ucfirst($log->severity) }}</td>
                    <td>{{ $log->ip_address ?? '-' }}</td>
                    <td>{{ Str::limit($log->notes ?? '-', 150) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 8px; color: #666; text-align: center;">
        Smart Contract Document Validation System - Audit Logs Export<br>
        Page generated on {{ $exportDate->format('F d, Y \a\t H:i:s') }}
    </div>
</body>
</html>