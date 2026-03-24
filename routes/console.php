<?php

use App\Models\BotInstance;
use App\Models\Conversation;
use App\Models\Flow;
use App\Models\Message;
use App\Models\Tenant;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('nexxivo:sync-tenant-ids', function () {
    // Backfill para conversas/mensagens antigas criadas via webhook sem auth.
    $this->info('Sincronizando tenant_id (e user_id em conversas) ...');

    $updated = 0;
    BotInstance::query()
        ->whereNull('tenant_id')
        ->with('user:id,tenant_id')
        ->chunkById(100, function ($rows) use (&$updated) {
            foreach ($rows as $bi) {
                $tid = $bi->effectiveTenantId();
                if ($tid !== null) {
                    $bi->update(['tenant_id' => $tid]);
                    $updated++;
                }
            }
        });
    $this->info("bot_instances atualizados: {$updated}");

    $updated = 0;
    Conversation::query()
        ->where(function ($q) {
            $q->whereNull('tenant_id')
                ->orWhereNull('user_id');
        })
        ->chunkById(100, function ($rows) use (&$updated) {
            foreach ($rows as $c) {
                $bi = BotInstance::query()
                    ->where('instance_name', $c->instance_name)
                    ->with('user:id,tenant_id')
                    ->first();

                $tid = $bi?->effectiveTenantId();
                $uid = $bi?->user_id;

                $updates = [];
                if ($c->tenant_id === null && $tid) {
                    $updates['tenant_id'] = $tid;
                }
                if ($c->user_id === null && $uid) {
                    $updates['user_id'] = $uid;
                }

                if ($updates !== []) {
                    $c->update($updates);
                    $updated++;
                }
            }
        });
    $this->info("conversations atualizadas: {$updated}");

    $updated = 0;
    Message::query()
        ->whereNull('tenant_id')
        ->chunkById(100, function ($rows) use (&$updated) {
            foreach ($rows as $m) {
                $tid = Conversation::query()
                    ->where('id', $m->conversation_id)
                    ->value('tenant_id');

                if ($tid) {
                    $m->update(['tenant_id' => $tid]);
                    $updated++;
                }
            }
        });
    $this->info("messages atualizadas: {$updated}");

    $updated = 0;
    if (Tenant::query()->count() === 1) {
        $onlyTid = (int) Tenant::query()->value('id');
        Flow::query()->whereNull('tenant_id')->chunkById(100, function ($rows) use (&$updated, $onlyTid) {
            foreach ($rows as $f) {
                $f->update(['tenant_id' => $onlyTid]);
                $updated++;
            }
        });
    }
    $this->info("flows atualizados: {$updated}");

    return 0;
})->purpose('Preenche tenant_id em dados antigos criados pelo webhook');
