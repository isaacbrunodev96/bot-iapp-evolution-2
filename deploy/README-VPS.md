# Deploy Nexxivo na VPS com domínio de teste

Guia para subir o projeto em uma VPS e expor em um domínio (ex.: `app.seudominio.com.br`) para o cliente testar.

## Visão geral

- **Laravel (nexxivo)** roda no servidor com Nginx + PHP-FPM.
- **Evolution API** roda em Docker (porta 8080 interna); webhook chama o Laravel via domínio público.
- **SSL** com Let's Encrypt (Certbot).
- **Fila** com Supervisor para processar mensagens do WhatsApp.

## Pré-requisitos na VPS

- Ubuntu 22.04 ou 24.04 (ou Debian equivalente)
- Acesso SSH (root ou usuário com sudo)
- Domínio apontando para o IP da VPS (registro A ou CNAME)

---

## 1. Preparar o servidor

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y git curl unzip software-properties-common
```

### PHP 8.2+, Composer, Node (build)

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mbstring php8.2-xml php8.2-mysql php8.2-sqlite3 php8.2-curl php8.2-zip php8.2-bcmath
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node 20 LTS (para build do front)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### Nginx

```bash
sudo apt install -y nginx
```

### Docker (para Evolution API)

```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
# Fazer logout e login de novo para o grupo docker valer
```

---

## 2. Clonar o projeto e instalar dependências

Defina o diretório da aplicação (ex.: `/var/www/nexxivo`):

```bash
sudo mkdir -p /var/www
sudo chown $USER:$USER /var/www
cd /var/www
git clone <URL_DO_SEU_REPOSITORIO> nexxivo-app
cd nexxivo-app
```

Ou fazer upload via rsync/scp da sua máquina:

```bash
# Na sua máquina (ajuste usuário e IP)
rsync -avz --exclude node_modules --exclude .git --exclude vendor sites/ usuario@IP_DA_VPS:/var/www/nexxivo-app/
```

Instalar dependências:

```bash
cd /var/www/nexxivo-app/nexxivo
cp .env.example .env
# Editar .env (ver seção 4)
php artisan key:generate
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Banco (SQLite para teste; ou configurar MySQL/PostgreSQL no .env)
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Permissões:

```bash
sudo chown -R www-data:www-data /var/www/nexxivo-app/nexxivo/storage /var/www/nexxivo-app/nexxivo/bootstrap/cache
sudo chmod -R 775 /var/www/nexxivo-app/nexxivo/storage /var/www/nexxivo-app/nexxivo/bootstrap/cache
```

---

## 3. Nginx + SSL (domínio de teste)

Substitua `app.seudominio.com.br` pelo domínio real.

### 3.1 Copiar configuração

```bash
sudo cp /var/www/nexxivo-app/deploy/nginx-nexxivo.conf /etc/nginx/sites-available/nexxivo
sudo ln -s /etc/nginx/sites-available/nexxivo /etc/nginx/sites-enabled/
# Trocar DOMINIO no arquivo pelo seu domínio
sudo sed -i 's/DOMINIO/app.seudominio.com.br/g' /etc/nginx/sites-available/nexxivo
sudo sed -i 's|ROOT_PATH|/var/www/nexxivo-app/nexxivo/public|g' /etc/nginx/sites-available/nexxivo
```

### 3.2 Certificado SSL (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d app.seudominio.com.br
```

O Certbot ajusta o Nginx para HTTPS. Renovação automática já fica no cron.

### 3.3 Testar e recarregar Nginx

```bash
sudo nginx -t && sudo systemctl reload nginx
```

---

## 4. Arquivo .env em produção (nexxivo)

No servidor, edite `/var/www/nexxivo-app/nexxivo/.env`:

```env
APP_NAME=Nexxivo
APP_ENV=production
APP_KEY=base64:...   # gerado com php artisan key:generate
APP_DEBUG=false
APP_URL=https://app.seudominio.com.br

# Banco (exemplo SQLite para teste)
DB_CONNECTION=sqlite
# Ou MySQL: DB_CONNECTION=mysql, DB_HOST=127.0.0.1, DB_DATABASE=nexxivo, DB_USERNAME=..., DB_PASSWORD=...

SESSION_SECURE_COOKIE=true

# Evolution API (Docker na mesma VPS)
EVOLUTION_API_URL=http://127.0.0.1:8080
EVOLUTION_API_KEY=sua-chave-segura-aqui
EVOLUTION_API_TIMEOUT=30
# URL pública para a Evolution chamar o webhook (obrigatório HTTPS em produção)
EVOLUTION_WEBHOOK_URL=https://app.seudominio.com.br
```

Depois:

```bash
cd /var/www/nexxivo-app/nexxivo
php artisan config:cache
```

---

## 5. Evolution API no Docker

Na mesma VPS, a Evolution precisa chamar o Laravel pela **URL pública** (domínio):

```bash
cd /var/www/nexxivo-app/docker
cp .env.example .env
```

Edite `docker/.env`:

```env
EVOLUTION_API_KEY=sua-chave-segura-aqui
# URL que a Evolution usa para enviar webhooks ao Laravel (domínio público)
WEBHOOK_GLOBAL_URL=https://app.seudominio.com.br/api/webhooks/evolution
POSTGRES_USER=evolution
POSTGRES_PASSWORD=senha-forte-postgres
POSTGRES_DB=evolution
```

Subir os containers:

```bash
docker compose up -d
docker compose ps
```

A Evolution fica em `http://127.0.0.1:8080` (só local). O Laravel se comunica com ela por essa URL; a Evolution envia eventos para `WEBHOOK_GLOBAL_URL`.

---

## 6. Supervisor (fila Laravel)

Sem o worker, as mensagens recebidas não são processadas (fluxos, IA, etc.).

```bash
sudo apt install -y supervisor
# Use o template para VPS (path e usuário www-data)
sudo cp /var/www/nexxivo-app/nexxivo/deploy/supervisor/laravel-worker-vps.conf /etc/supervisor/conf.d/laravel-worker.conf
sudo sed -i 's|APP_ROOT|/var/www/nexxivo-app/nexxivo|g' /etc/supervisor/conf.d/laravel-worker.conf
```

Ativar:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start "laravel-worker:*"
sudo supervisorctl status
```

---

## 7. Checklist final

| Item | Comando / Verificação |
|------|------------------------|
| Site no ar | Abrir `https://app.seudominio.com.br` |
| Login | Rodar `php artisan db:seed --class=AdminUserSeeder` (cria admin@nexxivo.com / admin123 — altere a senha depois) |
| Evolution acessível (local) | `curl -H "apikey: SUA_CHAVE" http://127.0.0.1:8080/instance/fetchInstances` |
| Worker rodando | `sudo supervisorctl status` → laravel-worker RUNNING |
| Criar instância no painel | Instâncias → Nova → escanear QR no WhatsApp |
| Webhook | Enviar mensagem no WhatsApp e ver se o bot responde; ver logs em `storage/logs/laravel.log` |

---

## 8. Deploy futuro (atualizar código)

```bash
cd /var/www/nexxivo-app
git pull
cd nexxivo
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart "laravel-worker:*"
```

---

## Resumo de portas

- **80/443**: Nginx (Laravel) – domínio público.
- **8080**: Evolution API – apenas localhost (não expor no firewall para internet).

Se precisar de Ollama (IA local), use o mesmo `docker-compose` que já inclui o serviço Ollama e exponha apenas se quiser (geralmente só localhost).
