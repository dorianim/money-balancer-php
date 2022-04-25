FROM alpine as builder

COPY . /src
RUN apk add git && \
  echo "**** adding version ****" && \
  cd /src && \
  export VERSION=$(git describe --exact-match --tags $(git log -n1 --pretty='%h') || echo "dev - $(git rev-parse --short HEAD)") && \
  printf "<?php\n\$VERSION = \"$VERSION\";\n" > ./version.php

FROM ghcr.io/linuxserver/baseimage-alpine-nginx:2021.11.04

ARG gitcommithash
LABEL maintainer="Dorian Zedler <mail@dorian.im>"

ENV MUSL_LOCPATH="/usr/share/i18n/locales/musl"
RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/v3.13/community musl-locales musl-locales-lang \
    && cd "$MUSL_LOCPATH" \
    && for i in *.UTF-8; do \
     i1=${i%%.UTF-8}; \
     i2=${i1/_/-}; \
     i3=${i/_/-}; \
     cp -a "$i" "$i1"; \
     cp -a "$i" "$i2"; \
     cp -a "$i" "$i3"; \
     done

RUN \
  echo "**** install packages ****" && \
  apk update && \
  apk add --no-cache  \
    curl \
    mysql-client \
    php7-ctype \
    php7-curl \
    php7-dom \
    php7-gd \
    php7-ldap \
    php7-mbstring \
    php7-memcached \
    php7-mysqlnd \
    php7-openssl \
    php7-pdo_mysql \
    php7-phar \
    php7-simplexml \
    php7-tokenizer \
    php7-intl \
    tar && \
  echo "**** configure php-fpm ****" && \
  sed -i 's/;clear_env = no/clear_env = no/g' /etc/php7/php-fpm.d/www.conf && \
  echo "env[PATH] = /usr/local/bin:/usr/bin:/bin" >> /etc/php7/php-fpm.conf

RUN \
  echo "**** prepare root ****" && \
  rm -rf /var/www/html && \
  echo "catch_workers_output = yes" >> /etc/php7/php-fpm.d/www.conf && \
  echo "**** cleanup ****" && \
  rm -rf \
    /tmp/*

COPY root/ /
COPY src/ /var/www/html
COPY --from=builder /src/version.php /var/www/html

VOLUME /config
EXPOSE 80
