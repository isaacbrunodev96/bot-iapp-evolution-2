<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'conversation_id',
        'instance_name',
        'message_id',
        'from',
        'to',
        'message',
        'direction',
        'raw_data',
        'timestamp',
        'tenant_id',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'timestamp' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
