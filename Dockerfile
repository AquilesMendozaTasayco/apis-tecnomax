# Imagen base de PHP con Apache
FROM php:8.2-apache

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Instalar dependencias del sistema y extensiones PHP
RUN apt-get update && apt-get install -y unzip curl \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Copiar los archivos del proyecto al contenedor
COPY php.ini /usr/local/etc/php/php.ini

# Instalar Composer y dependencias del proyecto
RUN rm -rf vendor composer.lock \
    && curl -sS https://getcomposer.org/installer | php \
    && php composer.phar install --no-interaction --prefer-dist

# Dar permisos adecuados a los archivos
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto 80
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]
