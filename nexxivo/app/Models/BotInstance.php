<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotInstance extends Model
{
    use BelongsToTenant;

    /**
     * Mesma regra do InstanceController: só instâncias do utilizador e do tenant atual (evita Inbox/Canais divergentes).
     */
    public function scopeForPanelUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return $query->whereRaw('0 = 1');
        }
        $query->where('user_id', $user->id);
        if ($user->tenant_id !== null) {
            $query->where('tenant_id', $user->tenant_id);
        } else {
            $query->whereNull('tenant_id');
        }

        return $query;
    }

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

