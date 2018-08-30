FROM php:7.1

ARG DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_PROCESS_TIMEOUT 3600

COPY composer-install.sh /tmp/composer-install.sh

RUN apt-get update -q \
  && apt-get install unzip git wget -y --no-install-recommends \
  && rm -rf /var/lib/apt/lists/* \
  && /tmp/composer-install.sh \
  && rm /tmp/composer-install.sh \
  && mv composer.phar /usr/local/bin/composer

CMD bash
