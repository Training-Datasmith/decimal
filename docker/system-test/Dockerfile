FROM php:7.2-cli@sha256:5249d94b4b00db63a0ed53e6528114926110ab5dcb0d0f07cc30d1ea7dbe747e

RUN set -eux; \
    sed -i 's|deb.debian.org|archive.debian.org|g' /etc/apt/sources.list; \
    sed -i 's|security.debian.org|archive.debian.org|g' /etc/apt/sources.list; \
    apt-get -o Acquire::Check-Valid-Until=false update; \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        unzip \
    ; \
    docker-php-ext-install -j"$(nproc)" bcmath; \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app
