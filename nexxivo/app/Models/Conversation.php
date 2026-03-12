<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class Conversation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
            'instance_name',
            'contact',
            'contact_name',
            'last_message_at',
            'is_archived',
            'is_blocked',
            'kanban_status',
            'status',
            'user_id',
            'tenant_queue_id',
            'tenant_id',
            'funnel_stage_id',
        ];

        protected $casts = [
            'last_message_at' => 'datetime',
            'is_archived' => 'boolean',
            'is_blocked' => 'boolean',
        ];

        public function messages(): HasMany
        {
            return $this->hasMany(Message::class);
        }



        public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * O Atendente (Humano) responsável por esta conversa/ticket
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A Fila (Setor de Vendas/Suporte) na qual esse cliente está aguardando
     */
    public function queue()
    {
        return $this->belongsTo(TenantQueue::class, 'tenant_queue_id');
    }

    public function funnelStage()
    {
        return $this->belongsTo(FunnelStage::class);
    }
}

