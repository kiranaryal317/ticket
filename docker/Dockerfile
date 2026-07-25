FROM ubuntu:24.04
  
ENV DEBIAN_FRONTEND=noninteractive

# System deps
RUN apt update && apt install -y \
    software-properties-common \
    curl \
    git \
    unzip \
    zip \
    nginx \
    supervisor \
    sqlite3 \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && add-apt-repository ppa:ondrej/php -y \
    && apt update

# PHP 8.3
RUN apt install -y \
    php8.3 \
    php8.3-fpm \
    php8.3-cli \
    php8.3-mysql \
    php8.3-pgsql \
    php8.3-sqlite3 \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-gd \
    php8.3-intl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt install -y nodejs

# PHP config
COPY docker/php.ini /etc/php/8.3/fpm/php.ini

# Nginx config
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/src

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

 