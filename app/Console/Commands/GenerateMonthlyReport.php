<?php

namespace App\Console\Commands;

use App\Services\ReportService;
use App\Services\AuditLogService;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class GenerateMonthlyReport extends Command
{
    protected $signature = 'report:monthly {--month= : Month (1-12)} {--year= : Year} {--email : Send report via email}';
    protected $description = 'Generate monthly performance report';

    public function handle()
    {
        $month = $this->option('month');
        $year = $this->option('year');
        $sendEmail = $this->option('email');

        $this->info('Generating monthly report...');

        // Generate report data
        $reportData = ReportService::generateMonthlyReport($month, $year);

        // Generate report files
        $this->generateTextReport($reportData);
        $this->generateJsonReport($reportData);

        $this->info('Report generated successfully!');
        $this->displayReportSummary($reportData);

        // Log report generation
        AuditLogService::log(
            'report.monthly_generated',
            null,
            null,
            [
                'period' => $reportData['period']['month'] . ' ' . $reportData['period']['year'],
                'documents' => $reportData['documents']['total_uploaded'],
            ],
            'info',
            "Monthly report generated for {$reportData['period']['month']} {$reportData['period']['year']}"
        );

        // Send email if requested
        if ($sendEmail) {
            $this->sendReportEmail($reportData);
        }

        return 0;
    }

    protected function generateTextReport($reportData)
    {
        $period = $reportData['period'];
        $filename = "monthly_report_{$period['year']}_{$period['month']}.txt";
        
        $content = $this->formatTextReport($reportData);
        
        Storage::put("reports/{$filename}", $content);
        $this->line("Text report saved: storage/app/reports/{$filename}");
    }

    protected function generateJsonReport($reportData)
    {
        $period = $reportData['period'];
        $filename = "monthly_report_{$period['year']}_{$period['month']}.json";
        
        Storage::put("reports/{$filename}", json_encode($reportData, JSON_PRETTY_PRINT));
        $this->line("JSON report saved: storage/app/reports/{$filename}");
    }

    protected function formatTextReport($data): string
    {
        $period = $data['period'];
        
        $report = "=====================================\n";
        $report .= "   MONTHLY PERFORMANCE REPORT\n";
        $report .= "=====================================\n\n";
        $report .= "Period: {$period['month']} {$period['year']}\n";
        $report .= "Generated: " . now()->format('M d, Y H:i:s') . "\n\n";
        
        // Documents Section
        $report .= "--- DOCUMENTS ---\n";
        $report .= "Total Uploaded: {$data['documents']['total_uploaded']}\n";
        $report .= "Daily Average: {$data['documents']['daily_average']}\n";
        $report .= "\nBy Type:\n";
        foreach ($data['documents']['by_type'] as $type => $count) {
            $report .= "  - " . ucwords(str_replace('_', ' ', $type)) . ": {$count}\n";
        }
        $report .= "\nBy Status:\n";
        foreach ($data['documents']['by_status'] as $status => $count) {
            $report .= "  - " . ucfirst($status) . ": {$count}\n";
        }
        $report .= "\nInterventions:\n";
        $report .= "  - Locked: {$data['documents']['interventions']['locked']}\n";
        $report .= "  - Archived: {$data['documents']['interventions']['archived']}\n";
        $report .= "  - Flagged: {$data['documents']['interventions']['flagged']}\n\n";
        
        // Corrections Section
        $report .= "--- CORRECTIONS ---\n";
        $report .= "Total Requests: {$data['corrections']['total_requests']}\n";
        $report .= "Approved: {$data['corrections']['approved']}\n";
        $report .= "Rejected: {$data['corrections']['rejected']}\n";
        $report .= "Pending: {$data['corrections']['pending']}\n";
        $report .= "Escalated: {$data['corrections']['escalated']}\n";
        $report .= "Overridden: {$data['corrections']['overridden']}\n";
        $report .= "Avg Resolution Time: {$data['corrections']['avg_resolution_time']}\n\n";
        
        // Blockchain Section
        $report .= "--- BLOCKCHAIN ---\n";
        $report .= "Total Transactions: {$data['blockchain']['total_transactions']}\n";
        $report .= "Confirmed: {$data['blockchain']['confirmed']}\n";
        $report .= "Failed: {$data['blockchain']['failed']}\n";
        $report .= "Pending: {$data['blockchain']['pending']}\n";
        $report .= "Success Rate: {$data['blockchain']['success_rate']}%\n\n";
        
        // System Section
        $report .= "--- SYSTEM ---\n";
        $report .= "Backups Created: {$data['system']['backups_created']}\n";
        $report .= "Backup Failures: {$data['system']['backup_failures']}\n";
        $report .= "Audit Logs: {$data['system']['audit_logs']}\n";
        $report .= "Critical Events: {$data['system']['critical_events']}\n";
        $report .= "Warnings: {$data['system']['warnings']}\n\n";
        
        // Performance Section
        $report .= "--- PERFORMANCE ---\n";
        $report .= "Document Completion Rate: {$data['performance']['document_completion_rate']}%\n";
        $report .= "Avg Documents/Day: {$data['performance']['avg_documents_per_day']}\n";
        $report .= "Peak Upload Day: {$data['performance']['peak_upload_day']}\n\n";
        
        // Top Performers
        $report .= "--- TOP PERFORMERS ---\n";
        foreach ($data['top_performers']['top_uploaders'] as $index => $performer) {
            $rank = $index + 1;
            $report .= "{$rank}. {$performer['user']} - {$performer['uploads']} uploads\n";
        }
        
        $report .= "\n=====================================\n";
        $report .= "End of Report\n";
        $report .= "=====================================\n";
        
        return $report;
    }

    protected function displayReportSummary($data)
    {
        $this->newLine();
        $this->info("=== REPORT SUMMARY ===");
        $this->line("Period: {$data['period']['month']} {$data['period']['year']}");
        $this->line("Documents: {$data['documents']['total_uploaded']}");
        $this->line("Corrections: {$data['corrections']['total_requests']}");
        $this->line("Blockchain Success Rate: {$data['blockchain']['success_rate']}%");
        $this->line("Completion Rate: {$data['performance']['document_completion_rate']}%");
    }

    protected function sendReportEmail($reportData)
    {
        $this->info('Sending report email to admins...');

        $admins = User::role('admin')->where('status', 'active')->get();

        foreach ($admins as $admin) {
            try {
                Mail::raw(
                    $this->formatTextReport($reportData),
                    function ($message) use ($admin, $reportData) {
                        $message->to($admin->email)
                            ->subject("Monthly Report - {$reportData['period']['month']} {$reportData['period']['year']}");
                    }
                );
                $this->line("Email sent to: {$admin->email}");
            } catch (\Exception $e) {
                $this->error("Failed to send email to {$admin->email}: {$e->getMessage()}");
            }
        }
    }
}