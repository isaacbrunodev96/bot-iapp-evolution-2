<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class OllamaWarmCommand extends Command
{
    protected $signature = 'ollama:warm';
    protected $description = 'Mantém o modelo Ollama carregado para respostas rápidas no fluxo com IA';

    public function handle(): int
    {
        $url = config('services.ai.ollama_url', env('OLLAMA_URL', 'http://127.0.0.1:11434'));
        $url = str_replace('http://localhost', 'http://127.0.0.1', $url);
        $model = config('services.ai.ollama_model', env('OLLAMA_MODEL', 'llama2'));

        try {
            $r = Http::timeout(90)->post(rtrim($url, '/') . '/api/generate', [
                'model' => $model,
                'prompt' => '.',
                'stream' => false,
            ]);
            if ($r->successful()) {
                $this->info('Ollama aquecido.');
                return 0;
            }
            $endpoint = rtrim($url, '/') . '/api/generate';
            $this->warn(sprintf(
                'Ollama respondeu com status %s (URL: %s, modelo: %s).',
                $r->status(),
                $endpoint,
                $model
            ));
            $body = $r->body();
            if ($body !== '') {
                $this->line('Resposta: ' . (strlen($body) > 500 ? substr($body, 0, 500) . '…' : $body));
            }
            if ($r->status() === 404) {
                $this->comment('Dica: 404 costuma ser modelo inexistente. Confira `ollama list` e alinhe OLLAMA_MODEL / Configurações de IA.');
            }
            return 1;
        } catch (\Throwable $e) {
            $this->warn('Ollama warm falhou: ' . $e->getMessage());
            return 1;
        }
    }
}
