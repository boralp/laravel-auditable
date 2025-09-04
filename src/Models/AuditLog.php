<?php

namespace Boralp\Auditable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'user_id',
        'action',
        'ip',
        'user_agent_id',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function userAgent(): BelongsTo
    {
        return $this->belongsTo(UserAgent::class, 'user_agent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Accessor to return human-readable IP
    public function getIpAttribute($value): ?string
    {
        return $value ? inet_ntop($value) : null;
    }

    // Mutator to set IP in binary
    public function setIpAttribute($value): void
    {
        $this->attributes['ip'] = $value ? inet_pton($value) : null;
    }
}
