FROM php:8.3-cli-alpine

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN install-php-extensions apcu intl opcache xsl zip

WORKDIR /var/www

# Base php.ini
COPY --link <<EOF /usr/local/etc/php/conf.d/retumador.ini
date.timezone=Europe/London
memory_limit=-1
EOF

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Chromium and ChromeDriver
ENV PANTHER_NO_SANDBOX=1
# Not mandatory, but recommended
ENV PANTHER_CHROME_ARGUMENTS='--disable-dev-shm-usage'
RUN apk add --no-cache chromium chromium-chromedriver

# Firefox and GeckoDriver (optional)
ARG GECKODRIVER_VERSION=0.28.0
RUN apk add --no-cache firefox libzip-dev;
RUN wget -q https://github.com/mozilla/geckodriver/releases/download/v$GECKODRIVER_VERSION/geckodriver-v$GECKODRIVER_VERSION-linux64.tar.gz; \
    tar -zxf geckodriver-v$GECKODRIVER_VERSION-linux64.tar.gz -C /usr/bin; \
    rm geckodriver-v$GECKODRIVER_VERSION-linux64.tar.gz
