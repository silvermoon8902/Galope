# Galope - imagen de la aplicacion (PHP 8.3 + servidor embebido).
#
# Usamos php:8.3-cli con el servidor embebido en lugar de php:8.3-apache
# porque en Railway la imagen apache mostraba "More than one MPM loaded" en
# tiempo de ejecucion incluso cuando la build dejaba solo mpm_prefork. Para
# un despliegue de demo el servidor embebido es suficiente y elimina toda
# la complejidad de configuracion de Apache.
FROM php:8.3-cli

# Extensiones de PHP: pdo_mysql para la base, mbstring para el manejo de nombres.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/
RUN chmod +x /var/www/html/docker/entrypoint.sh

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
