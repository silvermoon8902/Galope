#!/usr/bin/env bash
# Entrypoint del contenedor de Galope:
#   1. Espera a que la base de datos responda.
#   2. Crea el esquema y los datos de ejemplo en el primer arranque.
#   3. Arranca el servidor embebido de PHP en el puerto que asigna la plataforma.

PORT="${PORT:-8080}"

echo "Galope: esperando la base de datos..."
ready=0
for i in $(seq 1 30); do
  if php /var/www/html/bin/dbcheck.php 2>/dev/null; then
    ready=1
    echo "Galope: base de datos disponible."
    break
  fi
  sleep 2
done
[ "$ready" = 1 ] || echo "Galope: la base no respondio en 60s; se arranca igual."

# Crea esquema + datos de ejemplo en el primer arranque; no hace nada si ya existen.
php /var/www/html/bin/migrate.php || true

echo "Galope: iniciando servidor en 0.0.0.0:${PORT}"
exec php -S "0.0.0.0:${PORT}" -t /var/www/html/public /var/www/html/public/index.php
