<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 6px 0;
        }
        h2 {
            font-size: 12px;
            margin: 14px 0 8px 0;
            padding-bottom: 4px;
            border-bottom: 1px solid #d1d5db;
        }
        .meta {
            margin-bottom: 14px;
            font-size: 9px;
            color: #4b5563;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        th,
        td {
            border: 1px solid #d1d5db;
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            width: 35%;
        }
        .footer {
            margin-top: 16px;
            padding-top: 6px;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Monthly Performance Report</h1>
    <div class="meta">
        <strong>Period:</strong> {{ $reportData['period']['month'] }} {{ $reportData['period']['year'] }}<br>
        <strong>Coverage:</strong> {{ $reportData['period']['start_date'] }} to {{ $reportData['period']['end_date'] }}<br>
        <strong>Generated:</strong> {{ $generatedAt->format('F d, Y H:i:s') }}
    </div>

    <h2>Documents</h2>
    <table>
        <tbody>
            <tr><th>Total Uploaded</th><td>{{ $reportData['documents']['total_uploaded'] }}</td></tr>
            <tr><th>Daily Average</th><td>{{ $reportData['documents']['daily_average'] }}</td></tr>
            <tr><th>Birth Certificates</th><td>{{ $reportData['documents']['by_type']['birth_certificate'] }}</td></tr>
            <tr><th>Death Certificates</th><td>{{ $reportData['documents']['by_type']['death_certificate'] }}</td></tr>
            <tr><th>Marriage Certificates</th><td>{{ $reportData['documents']['by_type']['marriage_certificate'] }}</td></tr>
            <tr><th>CENOMAR</th><td>{{ $reportData['documents']['by_type']['cenomar'] }}</td></tr>
            <tr><th>Draft</th><td>{{ $reportData['documents']['by_status']['draft'] }}</td></tr>
            <tr><th>Pending</th><td>{{ $reportData['documents']['by_status']['pending'] }}</td></tr>
            <tr><th>Completed</th><td>{{ $reportData['documents']['by_status']['completed'] }}</td></tr>
            <tr><th>Rejected</th><td>{{ $reportData['documents']['by_status']['rejected'] }}</td></tr>
            <tr><th>Locked</th><td>{{ $reportData['documents']['interventions']['locked'] }}</td></tr>
            <tr><th>Archived</th><td>{{ $reportData['documents']['interventions']['archived'] }}</td></tr>
            <tr><th>Flagged</th><td>{{ $reportData['documents']['interventions']['flagged'] }}</td></tr>
        </tbody>
    </table>

    <h2>Corrections</h2>
    <table>
        <tbody>
            <tr><th>Total Requests</th><td>{{ $reportData['corrections']['total_requests'] }}</td></tr>
            <tr><th>Approved</th><td>{{ $reportData['corrections']['approved'] }}</td></tr>
            <tr><th>Rejected</th><td>{{ $reportData['corrections']['rejected'] }}</td></tr>
            <tr><th>Pending</th><td>{{ $reportData['corrections']['pending'] }}</td></tr>
            <tr><th>Escalated</th><td>{{ $reportData['corrections']['escalated'] }}</td></tr>
            <tr><th>Overridden</th><td>{{ $reportData['corrections']['overridden'] }}</td></tr>
            <tr><th>Avg Resolution Time</th><td>{{ $reportData['corrections']['avg_resolution_time'] }}</td></tr>
        </tbody>
    </table>

    <h2>Users</h2>
    <table>
        <tbody>
            <tr><th>New Users</th><td>{{ $reportData['users']['new_users'] }}</td></tr>
            <tr><th>Active Users</th><td>{{ $reportData['users']['active_users'] }}</td></tr>
            <tr><th>Deactivated</th><td>{{ $reportData['users']['deactivated'] }}</td></tr>
            <tr><th>Staff Added</th><td>{{ $reportData['users']['by_role']['staff'] }}</td></tr>
            <tr><th>Supervisors Added</th><td>{{ $reportData['users']['by_role']['supervisor'] }}</td></tr>
            <tr><th>Admins Added</th><td>{{ $reportData['users']['by_role']['admin'] }}</td></tr>
        </tbody>
    </table>

    <h2>Blockchain</h2>
    <table>
        <tbody>
            <tr><th>Total Transactions</th><td>{{ $reportData['blockchain']['total_transactions'] }}</td></tr>
            <tr><th>Confirmed</th><td>{{ $reportData['blockchain']['confirmed'] }}</td></tr>
            <tr><th>Failed</th><td>{{ $reportData['blockchain']['failed'] }}</td></tr>
            <tr><th>Pending</th><td>{{ $reportData['blockchain']['pending'] }}</td></tr>
            <tr><th>Success Rate</th><td>{{ $reportData['blockchain']['success_rate'] }}%</td></tr>
        </tbody>
    </table>

    <h2>System and Performance</h2>
    <table>
        <tbody>
            <tr><th>Backups Created</th><td>{{ $reportData['system']['backups_created'] }}</td></tr>
            <tr><th>Backup Failures</th><td>{{ $reportData['system']['backup_failures'] }}</td></tr>
            <tr><th>Audit Logs</th><td>{{ $reportData['system']['audit_logs'] }}</td></tr>
            <tr><th>Critical Events</th><td>{{ $reportData['system']['critical_events'] }}</td></tr>
            <tr><th>Warnings</th><td>{{ $reportData['system']['warnings'] }}</td></tr>
            <tr><th>Completion Rate</th><td>{{ $reportData['performance']['document_completion_rate'] }}%</td></tr>
            <tr><th>Avg Documents/Day</th><td>{{ $reportData['performance']['avg_documents_per_day'] }}</td></tr>
            <tr><th>Peak Upload Day</th><td>{{ $reportData['performance']['peak_upload_day'] }}</td></tr>
            <tr><th>System Uptime</th><td>{{ $reportData['performance']['system_uptime'] }}</td></tr>
        </tbody>
    </table>

    <h2>Top Performers</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Rank</th>
                <th>User</th>
                <th style="width: 25%;">Uploads</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData['top_performers']['top_uploaders'] as $index => $performer)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $performer['user'] }}</td>
                    <td>{{ $performer['uploads'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No uploader data available for this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Smart Contract Document Validation System<br>
        Monthly report generated on {{ $generatedAt->format('F d, Y \a\t H:i:s') }}
    </div>
</body>
</html>
