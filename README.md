# Agendamento Igreja

Sistema Laravel para reserva de horarios da vigilia do EJC SPSP, com confirmacoes via WhatsApp usando um servico Node/Baileys separado.

## Desenvolvimento Local

Instale as dependencias:

```bash
composer install
npm install
```

Configure o ambiente:

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Suba o Laravel:

```bash
php artisan serve
```

Em outro terminal, rode o servico de WhatsApp:

```bash
npm run whatsapp
```

No primeiro uso, escaneie o QR Code exibido no terminal com WhatsApp em **Aparelhos conectados > Conectar aparelho**.

## Publicacao no GitHub

Antes de publicar, confirme que estes arquivos/pastas nao entram no commit:

- `.env`
- `storage/whatsapp-auth`
- `storage/logs`
- `vendor`
- `node_modules`
- `database/database.sqlite`

O `.gitignore` ja protege esses caminhos.

## Deploy no Railway

Este projeto deve ser publicado como dois servicos no Railway usando o mesmo repositorio:

1. **App Laravel**: recebe o dominio publico e atende o site.
2. **WhatsApp Service**: roda o Node/Baileys e envia as mensagens.

Tambem crie um banco **Postgres** ou **MySQL** no Railway. Para producao, prefira Postgres.

### App Laravel

No servico Laravel:

- Build Command: `npm run build`
- Pre-Deploy Command: `sh ./railway/init-app.sh`
- Gere um dominio publico em **Settings > Networking > Generate Domain**.

Variaveis principais:

```env
APP_NAME="Agendamento Igreja"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://seu-dominio.up.railway.app
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR
LOG_CHANNEL=stderr
LOG_LEVEL=info
DB_CONNECTION=pgsql
DB_URL=${{Postgres.DATABASE_URL}}
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
WHATSAPP_SERVICE_URL=https://url-do-servico-whatsapp
WHATSAPP_SERVICE_TOKEN=um-token-forte
WHATSAPP_SERVICE_TIMEOUT=30
WHATSAPP_ADMIN_PHONE=5585999999999
```

Para gerar o `APP_KEY`, rode localmente:

```bash
php artisan key:generate --show
```

### WhatsApp Service

Crie outro servico no Railway apontando para o mesmo repositorio.

- Start Command: `sh ./railway/start-whatsapp.sh`
- Adicione um volume persistente e configure `WHATSAPP_AUTH_DIR` apontando para esse volume.
- Gere uma URL para esse servico se o Laravel for chamar por HTTP publico.

Variaveis principais:

```env
WHATSAPP_SERVICE_TOKEN=mesmo-token-do-laravel
WHATSAPP_AUTH_DIR=/data/whatsapp-auth
WHATSAPP_LOG_LEVEL=info
```

Depois do primeiro deploy, abra os logs do servico de WhatsApp e escaneie o QR Code.

## Comandos Uteis

```bash
php artisan test
npm run build
node --check whatsapp-service/server.js
```
