# Naudojame PHP su Apache
FROM php:8.1-apache

# Įdiegiam pdo_pgsql ir pgsql plėtinius PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Įjungiame Apache mod_rewrite
RUN a2enmod rewrite

# Nustatome darbo katalogą į /var/www/html
WORKDIR /var/www/html

# Kopijuojame failus iš vietinio projekto katalogo į Docker konteinerį
COPY . /var/www/html/

# Uždaryti Apache serverį
EXPOSE 80
