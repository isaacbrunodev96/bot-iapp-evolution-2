<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class BotInstance extends Model
{
    use BelongsToTenant;

    protected $fillable = [
            'user_id',
            'instance_name',
            'status',
            'qrcode',
            'qrcode_generated_at',
            'pairing_code',
            'tenant_id',
        ];

        protected $casts = [
            'qrcode_generated_at' => 'datetime',
        ];



        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }
    }

