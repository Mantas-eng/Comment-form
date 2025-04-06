FROM php:8.1-apache

# Įdiegiam mysqli
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Perkeliame failus
COPY . /var/www/html/

# Įjungiame mod_rewrite (jeigu reikia .htaccess)
RUN a2enmod rewrite

