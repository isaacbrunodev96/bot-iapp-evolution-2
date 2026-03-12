<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FunnelStage extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = ['tenant_id', 'funnel_id', 'name', 'color', 'order'];

    public function funnel()
    {
        return $this->belongsTo(Funnel::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
