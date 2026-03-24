<?php

namespace App\Console\Commands;

use App\Models\BotInstance;
use App\Models\Conversation;
use App\Models\Flow;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;

class NexxivoSyncTenantIdsCommand extends Command
{
    protected $signature = 'nexxivo:sync-tenant-ids';

    protected $description = 'Preenche tenant_id (e user_id em conversas) a partir do dono da instância — útil após webhooks antigos sem tenant';

    public function handle(): int
    {
        $n = 0;
        BotInstance::query()->whereNull('tenant_id')->with('user:id,tenant_id')->chunkById(100, function ($rows) use (&$n) {
            foreach ($rows as $bi) {
                $tid = $bi->effectiveTenantId();
                if ($tid !== null) {
                    $bi->update(['tenant_id' => $tid]);
                    $n++;
                }
            }
        });
        $this->info("bot_instances atualizados: {$n}");

        $n = 0;
        Conversation::query()->where(function ($q) {
            $q->whereNull('tenant_id')->orWhereNull('user_id');
        })->chunkById(100, function ($rows) use (&$n) {
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
                    $n++;
                }
            }
        });
        $this->info("conversations atualizadas: {$n}");

        $n = 0;
        Message::query()->whereNull('tenant_id')->chunkById(100, function ($rows) use (&$n) {
            foreach ($rows as $m) {
                $tid = Conversation::query()->where('id', $m->conversation_id)->value('tenant_id');
                if ($tid) {
                    $m->update(['tenant_id' => $tid]);
                    $n++;
                }
            }
        });
        $this->info("messages atualizadas: {$n}");

        $n = 0;
        $tenantCount = Tenant::query()->count();
        $targetTenantId = null;
        if ($tenantCount === 1) {
            $targetTenantId = (int) Tenant::query()->value('id');
        } elseif ($tenantCount > 1) {
            $targetTenantId = User::query()->whereNotNull('tenant_id')->orderBy('id')->value('tenant_id');
            $targetTenantId = $targetTenantId !== null ? (int) $targetTenantId : null;
        } else {
            $targetTenantId = User::query()->whereKey(1)->value('tenant_id');
            $targetTenantId = $targetTenantId !== null ? (int) $targetTenantId : null;
        }

        if ($targetTenantId !== null) {
            Flow::query()
                ->where(function ($q) {
                    $q->whereNull('tenant_id')->orWhere('tenant_id', '');
                })
                ->chunkById(100, function ($rows) use (&$n, $targetTenantId) {
                    foreach ($rows as $f) {
                        $f->update(['tenant_id' => $targetTenantId]);
                        $n++;
                    }
                });
        }
        $this->info("flows atualizados: {$n}");

        return self::SUCCESS;
    }
}
