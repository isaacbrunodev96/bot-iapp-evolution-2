# Quando teste.nexxivo.com.br estiver resolvendo

Use este checklist quando o DNS propagar e **teste.nexxivo.com.br** apontar para **161.97.66.3**.

---

## 1. SSL (Certbot)

Na VPS:

```bash
certbot --nginx -d teste.nexxivo.com.br
```

---

## 2. Laravel (.env)

Editar `/var/www/nexxivo-app/nexxivo/.env`:

```env
APP_URL=https://teste.nexxivo.com.br
EVOLUTION_WEBHOOK_URL=https://teste.nexxivo.com.br
```

Depois:

```bash
cd /var/www/nexxivo-app/nexxivo
php artisan config:clear
php artisan config:cache
```

---

## 3. Docker – Evolution (.env)

Editar `/var/www/nexxivo-app/docker/.env`:

```env
WEBHOOK_GLOBAL_URL=https://teste.nexxivo.com.br/api/webhooks/evolution
```

Reiniciar a Evolution:

```bash
cd /var/www/nexxivo-app/docker
docker compose restart evolution-api
```

---

## 4. Atualizar webhook da instância no Laravel

Para a Evolution passar a enviar eventos para a URL HTTPS:

```bash
cd /var/www/nexxivo-app/nexxivo
php artisan tinker --execute="
app(\App\Services\EvolutionApiService::class)->setWebhook('user-1-teste', 'https://teste.nexxivo.com.br/api/webhooks/evolution', ['QRCODE_UPDATED', 'CONNECTION_UPDATE', 'MESSAGES_UPSERT']);
echo 'Webhook atualizado para teste.nexxivo.com.br';
"
```

(Se tiver outras instâncias, rode o `setWebhook` para cada uma ou use o nome correto no primeiro argumento.)

---

## 5. Conferir

- Acessar **https://teste.nexxivo.com.br** e fazer login.
- Enviar uma mensagem no WhatsApp e verificar se aparece em Conversas e se o fluxo dispara.

---

**IP da VPS:** 161.97.66.3  
**Domínio:** teste.nexxivo.com.br  
**Webhook atual (enquanto sem domínio):** http://172.17.0.1/api/webhooks/evolution (ou 161.97.66.3)
