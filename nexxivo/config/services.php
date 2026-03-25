<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'bot' => [
        'url' => env('BOT_URL', 'http://localhost:3001'),
    ],

    'ai' => [
        /** Se true, URL/modelo Ollama vêm só do .env (ignora ai_settings). Útil em VPS single-tenant. */
        'ollama_from_env' => filter_var(env('OLLAMA_FROM_ENV', false), FILTER_VALIDATE_BOOLEAN),
        /** Se true, default_provider da IA passa a ser sempre ollama (ignora ai_settings). Útil se o painel ficou em Gemini sem key. */
        'ollama_force_provider' => filter_var(env('OLLAMA_FORCE_PROVIDER', false), FILTER_VALIDATE_BOOLEAN),
        'default_model' => env('AI_DEFAULT_MODEL', 'ollama'),
        'ollama_url' => env('OLLAMA_URL', 'http://localhost:11434'),
        'ollama_model' => env('OLLAMA_MODEL', 'llama2'),
        /** Se true, usa /api/chat com stream=true (curl). Default false por estabilidade (evita "resposta vazia"). */
        'ollama_use_stream' => filter_var(env('OLLAMA_USE_STREAM', false), FILTER_VALIDATE_BOOLEAN),
        'ollama_chat_temperature' => (float) env('OLLAMA_CHAT_TEMPERATURE', 0.32),
        'ollama_chat_top_p' => (float) env('OLLAMA_CHAT_TOP_P', 0.68),
        'gemini_key' => env('GEMINI_API_KEY', ''),
        'gemini_model' => env('GEMINI_MODEL', 'gemini-pro'),
        /** Tamanho máximo do texto do fluxo no system (modelos pequenos copiam menos se for mais curto). */
        'max_flow_context_chars' => max(400, (int) env('AI_MAX_FLOW_CONTEXT_CHARS', 900)),
    ],

    'elevenlabs' => [
        'api_key' => env('ELEVENLABS_API_KEY', ''),
        'voice_id' => env('ELEVENLABS_VOICE_ID', '21m00Tcm4TlvDq8ikWAM'),
        'model_id' => env('ELEVENLABS_MODEL_ID', 'eleven_multilingual_v2'),
    ],

    'evolution' => [
        'url' => rtrim(env('EVOLUTION_API_URL', 'http://localhost:8080'), '/'),
        'apikey' => env('EVOLUTION_API_KEY', ''),
        'timeout' => (int) env('EVOLUTION_API_TIMEOUT', 30),
        'webhook_url' => env('EVOLUTION_WEBHOOK_URL'), // URL que a Evolution (Docker) usa para chamar o Laravel. Se vazio, usa APP_URL.
        'dispatch_incoming_sync' => filter_var(
            env(
                'EVOLUTION_DISPATCH_INCOMING_SYNC',
                (env('DB_CONNECTION') === 'sqlite' && env('QUEUE_CONNECTION', 'database') === 'database') ? '1' : '0'
            ),
            FILTER_VALIDATE_BOOLEAN
        ),
        'no_flow_reply' => env('EVOLUTION_NO_FLOW_REPLY', 'Oi! No momento não há fluxo ativo para esta mensagem. No painel, crie um fluxo com gatilho "Qualquer mensagem" (catch_all).'),
    ],

];
