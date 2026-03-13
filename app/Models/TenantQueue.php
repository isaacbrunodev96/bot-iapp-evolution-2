<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class TenantQueue extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'description',
        'color',
        'is_active',
        'tenant_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Retorna os Usuários/Atendentes alocados nesta Fila
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_queue_user');
    }

    /**
     * Retorna todas os tickets/conversas que estão em aberto aguardando nesta fila
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
