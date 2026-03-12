<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'tenant_id',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function botInstances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BotInstance::class);
    }

    /**
     * As Filas de atendimento que o atendente faz parte
     */
    public function queues()
    {
        return $this->belongsToMany(TenantQueue::class, 'tenant_queue_user');
    }

    /**
     * As Conversas que o Atendente está gerindo agora.
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'user_id');
    }
}
