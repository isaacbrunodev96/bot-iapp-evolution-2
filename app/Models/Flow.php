<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class Flow extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'description',
        'instance_name',
        'triggers',
        'actions',
        'is_active',
        'priority',
        'tenant_id',
    ];

    protected $casts = [
        'triggers' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
    ];

    public function executions(): HasMany
    {
        return $this->hasMany(FlowExecution::class);
    }
}
