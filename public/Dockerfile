# Naudojame PHP su Apache
FROM php:8.1-apache

# Įdiegiam mysqli plėtinį
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Įjungiame Apache mod_rewrite
RUN a2enmod rewrite

# Nustatome darbo katalogą į public
WORKDIR /var/www/html

# Kopijuojame failus iš vietinio public katalogo į Docker konteinerį
COPY . /var/www/html/

# Uždaryti Apache serverį
EXPOSE 80
