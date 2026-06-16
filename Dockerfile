# Galope - imagen de la aplicacion (PHP 8.3 + Apache).
FROM php:8.3-apache

# Extensiones de PHP: pdo_mysql para la base, mbstring para el manejo de nombres.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

# mod_php requiere mpm_prefork como UNICO MPM. Borramos fisicamente los
# archivos .so y .load/.conf de mpm_event y mpm_worker, no solo deshabilitamos
# los symlinks. Asi, aunque la plataforma haga overlays o reuse capas, no hay
# forma de cargar otro MPM. Validamos con "apache2 -t" en build-time: si la
# config queda rota por cualquier motivo, la build falla aca, no en runtime.
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load   /etc/apache2/mods-enabled/mpm_event.conf \
          /etc/apache2/mods-enabled/mpm_worker.load  /etc/apache2/mods-enabled/mpm_worker.conf \
          /etc/apache2/mods-available/mpm_event.load /etc/apache2/mods-available/mpm_event.conf \
          /etc/apache2/mods-available/mpm_worker.load /etc/apache2/mods-available/mpm_worker.conf \
          /usr/lib/apache2/modules/mod_mpm_event.so \
          /usr/lib/apache2/modules/mod_mpm_worker.so \
    && ln -sf ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf \
    && echo "=== MPM enabled (symlinks) ===" && ls /etc/apache2/mods-enabled/mpm_* \
    && echo "=== MPM .so files present ===" && ls /usr/lib/apache2/modules/mod_mpm* \
    && echo "=== apache2 -t (build-time config validation) ===" \
    && (. /etc/apache2/envvars && apache2 -t)

# Servir desde public/ y dejar que .htaccess maneje el ruteo.
RUN a2enmod rewrite \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        > /etc/apache2/conf-available/galope.conf \
    && a2enconf galope

COPY . /var/www/html/
RUN chmod +x /var/www/html/docker/entrypoint.sh

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
