## WICHTIG: Wenn Änderungen an dieser Datei gemacht werden, muss danach docker-compose build aufgerufen
## werden, um das Image neu zu bauen!

FROM php:fpm

### Installing PHP Extensions
# RUN <Linux Befehl der im PHP Container ausgeführt werden soll>
RUN docker-php-ext-install pdo pdo_mysql
RUN docker-php-ext-install mysqli

# Für besseres debugging
RUN pecl install xdebug && docker-php-ext-enable xdebug 