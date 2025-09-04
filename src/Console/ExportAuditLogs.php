<?php

namespace Boralp\Auditable\Console;

use Boralp\Auditable\Models\AuditLog;
use Illuminate\Console\Command;

class ExportAuditLogs extends Command
{
    protected $signature = 'audit:export
        {--days=90 : Export logs older than X days}
        {--format=json : Export format: json or csv}
        {--path= : Export path (default: storage/app/audit_exports)}';

    protected $description = 'Export old audit logs to JSON or CSV before cleanup';

    public function handle()
    {
        $days = (int) $this->option('days') ?: config('auditable.retention_days');
        $cutoff = now()->subDays($days);
        $format = strtolower($this->option('format'));
        $path = $this->option('path') ?? storage_path('app/audit_exports');

        if (! in_array($format, ['json', 'csv'])) {
            $this->error('Invalid format. Use json or csv.');

            return;
        }

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $logs = AuditLog::where('created_at', '<', $cutoff)->get();

        if ($logs->isEmpty()) {
            $this->info("No logs older than {$days} days found.");

            return;
        }

        $filename = "audit_export_{$days}d_".now()->format('Ymd_His').".{$format}";
        $filePath = "{$path}/{$filename}";

        if ($format === 'json') {
            file_put_contents($filePath, $logs->toJson(JSON_PRETTY_PRINT));
        } else {
            $handle = fopen($filePath, 'w');
            fputcsv($handle, array_keys($logs->first()->toArray()));
            foreach ($logs as $log) {
                fputcsv($handle, $log->toArray());
            }
            fclose($handle);
        }

        $this->info("Exported {$logs->count()} logs to {$filePath}");
    }
}
