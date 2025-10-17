# Imagen base de PHP con Apache
FROM php:8.2-apache

# Instalar extensiones necesarias (mysqli, pdo_mysql, etc.)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copiar todos los archivos del proyecto al contenedor
COPY . /var/www/html/

# Dar permisos
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto 80
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]
