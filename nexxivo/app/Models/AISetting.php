<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class AISetting extends Model
{
    use BelongsToTenant;

    protected $table = 'ai_settings';
    
    protected $fillable = [
        'tenant_id',
        'key',
        'value',
    ];

    /**
     * Obter valor de uma configuração
     */
    public static function get(string $key, $default = null, $tenantId = null)
    {
        $tenantId = $tenantId ?? auth()->user()?->tenant_id;
        
        $query = self::where('key', $key);
        
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        } else {
             // Se não for auth nem fornecido, tenta buscar a global (tenant null) ou ignora
             return $default;
        }

        $setting = $query->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Definir valor de uma configuração
     */
    public static function set(string $key, $value, $tenantId = null)
    {
        $tenantId = $tenantId ?? auth()->user()?->tenant_id;
        if (!$tenantId) return null;

        return self::withoutGlobalScope('tenant')->updateOrCreate(
            ['key' => $key, 'tenant_id' => $tenantId],
            ['value' => $value, 'tenant_id' => $tenantId]
        );
    }
}
