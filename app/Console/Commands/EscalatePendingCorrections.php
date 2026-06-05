<?php

namespace App\Console\Commands;

use App\Models\CorrectionRequest;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class EscalatePendingCorrections extends Command
{
    protected $signature = 'corrections:escalate';
    protected $description = 'Escalate correction requests that have been pending for more than 48 hours';

    public function handle()
    {
        $cutoffTime = Carbon::now()->subHours(48);
        
        $pendingCorrections = CorrectionRequest::where('status', CorrectionRequest::STATUS_PENDING)
            ->where('requested_at', '<', $cutoffTime)
            ->whereNull('escalated_at')
            ->get();

        $escalatedCount = 0;

        foreach ($pendingCorrections as $correction) {
            $correction->update([
                'escalated_at' => now(),
                'escalated_reason' => 'Auto-escalated: Pending for more than 48 hours without supervisor action',
                'escalation_status' => 'pending',
            ]);

            // Log audit entry
            AuditLogService::log(
                'correction.escalated',
                $correction,
                ['escalated_at' => null],
                ['escalated_at' => now(), 'escalation_status' => 'pending'],
                'warning',
                "Correction request auto-escalated after 48 hours pending"
            );

            // ADDED: Send notification
            NotificationService::notifyEscalation($correction);

            $escalatedCount++;
        }

        $this->info("Escalated {$escalatedCount} correction request(s).");
        
        return 0;
    }
}