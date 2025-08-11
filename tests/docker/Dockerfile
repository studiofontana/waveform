FROM php:7.1-alpine

# system deps Composer often needs
RUN apk add --no-cache \
    lame \
    git \
    unzip \
    bash \
    curl \
    ca-certificates \
    openssl

# Get Composer 2.2 (compatible with PHP 7.1) from the official image
COPY --from=composer:2.2 /usr/bin/composer /usr/local/bin/composer

# Make sure it’s executable and available
RUN chmod +x /usr/local/bin/composer && \
    /usr/local/bin/composer --version