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
        } elseif (Tenant::query()->count() === 1) {
            $only = (int) Tenant::query()->value('id');
            $query->where(function (Builder $q) use ($only) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $only);
            });
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

    /**
     * tenant_id para webhook/Inbox: instância, utilizador dono, ou único tenant na base (deploy single-tenant).
     */
    public function effectiveTenantId(): ?int
    {
        if ($this->tenant_id !== null) {
            return (int) $this->tenant_id;
        }

        $this->loadMissing('user:id,tenant_id');
        if ($this->user?->tenant_id !== null) {
            return (int) $this->user->tenant_id;
        }

        if ($this->user_id) {
            $tid = User::query()->whereKey($this->user_id)->value('tenant_id');
            if ($tid !== null) {
                return (int) $tid;
            }
        }

        if (Tenant::query()->count() === 1) {
            $v = Tenant::query()->value('id');

            return $v !== null ? (int) $v : null;
        }

        return null;
    }
    }

