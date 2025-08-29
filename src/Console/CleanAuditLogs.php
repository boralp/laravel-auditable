<?php

namespace Boralp\Auditable\Console;

use Boralp\Auditable\Models\AuditLog;
use Illuminate\Console\Command;

class CleanAuditLogs extends Command
{
    protected $signature = 'audit:clean {--days=90 : Delete logs older than X days}';

    protected $description = 'Clean old audit logs';

    public function handle()
    {
        $days = (int) $this->option('days') ?: config('auditable.retention_days');
        $cutoff = now()->subDays($days);

        $count = AuditLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$count} audit logs older than {$days} days.");
    }
}
