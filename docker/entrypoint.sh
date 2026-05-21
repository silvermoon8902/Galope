#!/usr/bin/env bash
# Entrypoint del contenedor de Galope:
#   1. Apache escucha en el puerto que asigna la plataforma ($PORT).
#   2. Espera a que la base de datos responda.
#   3. Crea el esquema y los datos de ejemplo en el primer arranque.
#   4. Arranca Apache.

PORT="${PORT:-80}"
sed -ri "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

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

exec apache2-foreground
