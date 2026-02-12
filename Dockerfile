# Use a imagem oficial do PHP com Apache
FROM php:8.1-apache

# Instalar extensões necessárias do PHP e Composer
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Configurar o DocumentRoot do Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Copiar arquivos da aplicação
COPY . /var/www/html/

# Instalar dependências do Composer
WORKDIR /var/www/html
RUN if [ -f composer.json ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

# Definir permissões corretas
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Criar diretório para includes se não existir
RUN mkdir -p /var/www/html/includes

# Expor a porta 80
EXPOSE 80

# Comando para iniciar o Apache
CMD ["apache2-foreground"]