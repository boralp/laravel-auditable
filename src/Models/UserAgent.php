<?php

namespace Boralp\Auditable\Models;

use Illuminate\Database\Eloquent\Model;

class UserAgent extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'raw',
        'device_category',
        'browser_name',
        'os_name',
        'hash',
    ];
}
