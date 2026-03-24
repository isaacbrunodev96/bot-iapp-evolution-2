<?php

namespace App\Console\Commands;

use App\Models\BotInstance;
use App\Services\AIService;
use Illuminate\Console\Command;

class NexxivoTestAiOllamaCommand extends Command
{
    protected $signature = 'nexxivo:test-ai-ollama {--tenant= : tenant_id (omite = primeiro BotInstance)}';

    protected $description = 'Testa Ollama com a mesma config do worker (AIService + tenant)';

    public function handle(): int
    {
        $tenantOpt = $this->option('tenant');
        if ($tenantOpt !== null && $tenantOpt !== '') {
            $tenantId = (int) $tenantOpt;
        } else {
            $bi = BotInstance::query()->with('user:id,tenant_id')->orderBy('id')->first();
            if (! $bi) {
                $this->error('Nenhuma BotInstance na base. Crie uma ou use --tenant=ID.');

                return self::FAILURE;
            }
            $tenantId = $bi->effectiveTenantId();
            $this->line('Usando tenant_id da primeira instância: ' . ($tenantId ?? '(null)'));
        }

        $this->line('OLLAMA_FROM_ENV (config): ' . (config('services.ai.ollama_from_env') ? 'true' : 'false'));
        $this->line('OLLAMA_MODEL (config): ' . config('services.ai.ollama_model'));
        $this->line('OLLAMA_URL (config): ' . config('services.ai.ollama_url'));

        try {
            $ai = new AIService($tenantId);
            $text = $ai->generateResponse(
                'Responda apenas com a palavra OK e mais nada.',
                'teste',
                'ollama',
                null,
                []
            );
            $this->info('Resposta da IA: ' . trim($text));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Falhou: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
