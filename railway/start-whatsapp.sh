#!/usr/bin/env sh
set -e

export WHATSAPP_SERVICE_PORT="${PORT:-3333}"
node whatsapp-service/server.js
