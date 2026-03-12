# Deploy agora (enquanto o DNS está em transição)

Use este roteiro para subir os arquivos na VPS e deixar tudo pronto. Quando o Registro.br terminar a transição (~2h), você só adiciona o registro **A** do subdomínio e roda o Certbot.

**Domínio:** `teste.nexxivo.com.br`  
**IP da VPS:** `161.97.66.3`

Troque `USUARIO` pelo seu usuário SSH (ex.: `root` ou `ubuntu`).

---

## 1. Na sua máquina: enviar os arquivos para a VPS

**Execute na pasta que contém as pastas `nexxivo`, `deploy` e `docker`** (raiz do projeto):

```bash
# Troque USUARIO por root ou ubuntu (ou o usuário que você usa no SSH)
rsync -avz --exclude 'node_modules' --exclude '.git' --exclude 'vendor' --exclude 'nexxivo/node_modules' --exclude 'nexxivo/storage/logs/*' --exclude 'nexxivo/storage/framework/cache/*' --exclude '.env' \
  ./ USUARIO@161.97.66.3:/var/www/nexxivo-app/
```

Se a VPS ainda não tiver `/var/www/nexxivo-app`, crie antes via SSH:

```bash
ssh USUARIO@161.97.66.3 "sudo mkdir -p /var/www/nexxivo-app && sudo chown \$USER:\$USER /var/www/nexxivo-app"
```

---

## 2. Na VPS: preparar ambiente (se ainda não fez)

```bash
sudo apt update && sudo apt install -y git curl unzip software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mbstring php8.2-xml php8.2-sqlite3 php8.2-curl php8.2-zip php8.2-bcmath nginx
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
```

(Depois do Docker, faça logout e login SSH para o grupo `docker` valer.)

---

## 3. Na VPS: instalar o projeto

```bash
cd /var/www/nexxivo-app/nexxivo
cp .env.example .env
nano .env   # ou vim
```

No `.env` deixe assim (troque o domínio e a chave):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://teste.nexxivo.com.br

DB_CONNECTION=sqlite

EVOLUTION_API_URL=http://127.0.0.1:8080
EVOLUTION_API_KEY=uma-chave-segura-gerada-por-voce
EVOLUTION_WEBHOOK_URL=https://teste.nexxivo.com.br
```

Depois:

```bash
php artisan key:generate
composer install --no-dev --optimize-autoloader
npm ci
npm run build
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 4. Na VPS: Nginx (já com o nome do domínio)

Quando o DNS apontar, o site já estará configurado para o domínio.

```bash
sudo cp /var/www/nexxivo-app/deploy/nginx-nexxivo.conf /etc/nginx/sites-available/nexxivo
sudo sed -i 's/DOMINIO/teste.nexxivo.com.br/g' /etc/nginx/sites-available/nexxivo
sudo sed -i 's|ROOT_PATH|/var/www/nexxivo-app/nexxivo/public|g' /etc/nginx/sites-available/nexxivo
sudo ln -sf /etc/nginx/sites-available/nexxivo /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

Para testar **por IP** antes do DNS: acesse `http://IP_DA_VPS`. Se não abrir, desabilite o site default ou use `default_server` no server (posso te passar o bloco se quiser).

---

## 5. Na VPS: Docker (Evolution API)

```bash
cd /var/www/nexxivo-app/docker
cp .env.example .env
nano .env
```

Deixe assim (mesma chave do Laravel e URL do domínio):

```env
EVOLUTION_API_KEY=uma-chave-segura-gerada-por-voce
WEBHOOK_GLOBAL_URL=https://teste.nexxivo.com.br/api/webhooks/evolution
POSTGRES_USER=evolution
POSTGRES_PASSWORD=senha-forte-postgres
POSTGRES_DB=evolution
```

```bash
docker compose up -d
docker compose ps
```

---

## 6. Na VPS: Supervisor (fila)

```bash
sudo apt install -y supervisor
sudo cp /var/www/nexxivo-app/nexxivo/deploy/supervisor/laravel-worker-vps.conf /etc/supervisor/conf.d/laravel-worker.conf
sudo sed -i 's|APP_ROOT|/var/www/nexxivo-app/nexxivo|g' /etc/supervisor/conf.d/laravel-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start "laravel-worker:*"
sudo supervisorctl status
```

---

## 7. Quando o DNS sair da transição (~2h)

1. No Registro.br: **Configurar zona DNS** → **Nova entrada**  
   - Tipo: **A**  
   - Nome: **teste**  
   - Dados: **161.97.66.3**  
   Salvar.

2. Aguardar propagação (alguns minutos).

3. Na VPS, gerar SSL:
   ```bash
   sudo apt install -y certbot python3-certbot-nginx
   sudo certbot --nginx -d teste.nexxivo.com.br
   ```

4. Acessar **https://teste.nexxivo.com.br** e fazer login (admin@nexxivo.com / admin123; troque a senha depois).

---

## Resumo do que fazer agora

| Onde      | Ação |
|----------|------|
| Sua máquina | Rodar o `rsync` (passo 1) |
| VPS      | Instalar dependências (2), instalar projeto (3), Nginx (4), Docker (5), Supervisor (6) |
| Depois do DNS | Registro A no Registro.br + `certbot --nginx -d teste.nexxivo.com.br` |

Assim os arquivos já ficam no servidor e o sistema pronto; quando a transição terminar, é só o DNS + Certbot.
