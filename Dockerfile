# Galope - imagen de la aplicacion (PHP 8.3 + Apache).
FROM php:8.3-apache

# Extensiones de PHP: pdo_mysql para la base, mbstring para el manejo de nombres.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

# mod_php requiere mpm_prefork como UNICO MPM. Manipulamos los symlinks
# directamente para no depender del estado previo de a2dismod/a2enmod
# (que en builds cacheados puede dejar mas de un MPM cargado).
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load   /etc/apache2/mods-enabled/mpm_event.conf \
          /etc/apache2/mods-enabled/mpm_worker.load  /etc/apache2/mods-enabled/mpm_worker.conf \
    && ln -sf ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf \
    && echo "MPM enabled:" && ls /etc/apache2/mods-enabled/mpm_*

# Servir desde public/ y dejar que .htaccess maneje el ruteo.
RUN a2enmod rewrite \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        > /etc/apache2/conf-available/galope.conf \
    && a2enconf galope

COPY . /var/www/html/
RUN chmod +x /var/www/html/docker/entrypoint.sh

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
