<?php

namespace Boralp\Auditable\Traits;

use Boralp\Auditable\Models\AuditLog;
use Boralp\Auditable\Models\UserAgent;

trait Auditable
{
    public static function bootAuditable()
    {
        static::creating(function ($model) {
            if ($model->isFillable('created_by') && auth()->id()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if ($model->isFillable('updated_by') && auth()->id()) {
                $model->updated_by = auth()->id();
            }
        });

        static::created(function ($model) {
            $model->storeAuditLog('created');
        });

        static::updated(function ($model) {
            $model->storeAuditLog('updated', [
                'before' => array_intersect_key($model->getOriginal(), $model->getChanges()),
                'after' => $model->getChanges(),
            ]);
        });
    }

    protected function storeAuditLog($action, $changes = null)
    {
        try {
            $uaId = null;
            if (config('auditable.track_user_agent')) {
                $uaString = request()->userAgent();
                $uaHash = hash('sha256', $uaString);

                $uaId = UserAgent::firstOrCreate(
                    ['hash' => $uaHash],
                    ['user_agent' => $uaString]
                )->id;
            }

            AuditLog::create([
                'auditable_type' => get_class($this),
                'auditable_id' => $this->id,
                'user_id' => config('auditable.track_user_id') ? auth()->id() : null,
                'action' => $action,
                'ip_address' => config('auditable.track_ip') ? request()->ip() : null,
                'user_agent_id' => $uaId,
                'changes' => config('auditable.track_changes') ? $changes : null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Audit log failed: '.$e->getMessage());
        }
    }

    public function audits()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
