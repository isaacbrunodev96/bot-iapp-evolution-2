<?php

namespace App\Console\Commands;

use App\Models\AISetting;
use App\Models\Tenant;
use Illuminate\Console\Command;

class NexxivoSyncAiOllamaFromEnvCommand extends Command
{
    protected $signature = 'nexxivo:sync-ai-ollama-from-env {--tenant= : Apenas este tenant (id)}';

    protected $description = 'Copia OLLAMA_URL e OLLAMA_MODEL do .env/config para ai_settings de cada tenant (alinha painel com o servidor)';

    public function handle(): int
    {
        $url = str_replace(
            'http://localhost',
            'http://127.0.0.1',
            (string) config('services.ai.ollama_url', 'http://127.0.0.1:11434')
        );
        $model = (string) config('services.ai.ollama_model', 'llama2');

        $tenantOption = $this->option('tenant');
        $tenantIds = $tenantOption !== null && $tenantOption !== ''
            ? [(int) $tenantOption]
            : Tenant::query()->orderBy('id')->pluck('id')->all();

        if ($tenantIds === []) {
            $this->error('Nenhum tenant na tabela tenants. Crie um tenant ou use --tenant=ID.');

            return self::FAILURE;
        }

        foreach ($tenantIds as $tid) {
            foreach (['ollama_url' => $url, 'ollama_model' => $model] as $key => $value) {
                AISetting::withoutGlobalScopes()->updateOrCreate(
                    ['tenant_id' => $tid, 'key' => $key],
                    ['tenant_id' => $tid, 'value' => $value]
                );
            }
            $this->info("Tenant #{$tid}: ai_settings ollama_url + ollama_model atualizados ({$model}).");
        }

        return self::SUCCESS;
    }
}
