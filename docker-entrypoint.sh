#!/bin/sh
set -e

# Configuración dinámica del puerto asignado por Render ($PORT, default 80 o 10000)
PORT="${PORT:-80}"

sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Iniciar Apache en primer plano
exec apache2-foreground
