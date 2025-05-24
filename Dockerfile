FROM php:8.3-fpm

# Define o diretório de trabalho
WORKDIR /var/www

# Instala extensões PHP, dependências e adiciona Node.js/npm em uma única camada
RUN apt-get update && apt-get install -y \
    libxml2-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libmemcached-dev \
    zlib1g-dev \
    libzip-dev \
    curl \
    git \
    unzip \
    nginx \
    supervisor \
    sudo \
    default-mysql-client \
    nodejs \
    npm \
    && curl -sSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o /usr/local/bin/install-php-extensions \
    && chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions soap bcmath mbstring pdo_mysql zip exif pcntl gd memcached intl redis imagick \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Adiciona usuário e grupo para segurança
RUN groupadd -g 1000 www && useradd -u 1000 -ms /bin/bash -g www www \
    && mkdir -p /var/www/storage \
    && chown -R www:www-data /var/www \
    && chmod -R ug+w /var/www/storage

# Copia apenas os arquivos necessários
COPY --chown=www:www-data . /var/www
COPY --chown=www:www-data .env /var/www/.env
COPY --chown=www:www-data docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY --chown=www:www-data docker/php.ini /usr/local/etc/php/conf.d/app.ini
COPY --chown=www:www-data docker/nginx.conf /etc/nginx/sites-enabled/default
COPY --chown=www:www-data docker/run.sh /var/www/docker/run.sh

# # Instala dependências do Composer e npm (se houver package.json)
# RUN composer install --no-dev --optimize-autoloader \
#     && npm install && npm run build

# # Adiciona aliases ao bashrc
RUN echo "alias pms='php artisan migrate:fresh --seed'" >> ~/.bashrc \
    && echo "alias pm='php artisan migrate'" >> ~/.bashrc \
    && echo "alias pa='php artisan'" >> ~/.bashrc \
    && echo "alias pmr='php artisan migrate:rollback'" >> ~/.bashrc \
    && echo "alias pmo='php artisan optimize'" >> ~/.bashrc \
    && echo 'alias cft="cloudflared tunnel --url http://localhost:80"' >> ~/.bashrc

# Volta para root para configurações finais
USER root
RUN chmod +x /var/www/docker/run.sh

# Expõe a porta
EXPOSE 80

# Define o entrypoint
ENTRYPOINT ["/var/www/docker/run.sh"]