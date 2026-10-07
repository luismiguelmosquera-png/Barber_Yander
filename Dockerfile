FROM php:8.2-apache

# Habilitar extensiones necesarias para MySQL y módulos de Apache
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite headers

# Copiar el código fuente web
COPY barber_yander/web/ /var/www/html/

# Permisos seguros para Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Script de inicio para soporte dinámico del puerto de Render ($PORT)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Render escucha en el puerto asignado por la variable de entorno PORT
EXPOSE 80 10000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
