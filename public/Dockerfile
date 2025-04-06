# Naudojame PHP su Apache
FROM php:8.1-apache

# Įdiegiam reikiamus paketus, įskaitant libpq-dev, kad galėtume sukurti PostgreSQL plėtinius
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Įjungiame Apache mod_rewrite
RUN a2enmod rewrite

# Nustatome darbo katalogą į /var/www/html
WORKDIR /var/www/html

# Kopijuojame failus iš vietinio projekto katalogo į Docker konteinerį
COPY . /var/www/html/

# Uždaryti Apache serverį
EXPOSE 80
