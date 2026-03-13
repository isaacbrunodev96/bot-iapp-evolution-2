<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            // Previne Loop Infinito na autenticacao: so acessa auth()->user() se ele ja estiver em memoria
            if (auth()->hasUser()) {
                $user = auth()->user();
                $tenantId = session('tenant_id') ?? $user->tenant_id;
                if ($tenantId && !($user->is_super_admin ?? false)) {
                    $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
                }
            }
        });

        static::creating(function (Model $model) {
            if (auth()->hasUser()) {
                $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
                if ($tenantId && empty($model->tenant_id)) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
