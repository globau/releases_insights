FROM php:8.1-fpm-alpine as builder

RUN apk update && \
    apk upgrade

# use install-php-extensions to install required php extensions and composer
RUN curl https://raw.githubusercontent.com/mlocati/docker-php-extension-installer/11f97c8b6c422762c5a8ca7829ab9bcc7b7ba3ab/install-php-extensions -o /usr/local/bin/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions
RUN /usr/local/bin/install-php-extensions mbstring intl curl dom @composer

# run composer to download and build dependencies/assets
RUN mkdir -p /app/public/assets
COPY composer* /app/
RUN cd /app && \
    composer install --no-dev --no-interaction

#

FROM php:8.1-fpm-alpine as runner

RUN apk update && \
    apk upgrade && \
    apk add nginx supervisor

RUN addgroup -g 10001 app && \
    adduser -D -u 10001 -G app app -h /app

# copy compiled libraries and php extensions from builder
COPY --from=builder /usr/lib /usr/lib
COPY --from=builder /usr/local/php /usr/local/php
COPY --from=builder /usr/local/lib/php /usr/local/lib/php
COPY --from=builder /usr/local/etc/php /usr/local/etc/php
COPY --from=builder /usr/local/include/php /usr/local/include/php

# deploy php, nginx, and supervisord configurations
RUN rm /usr/local/etc/php-fpm.d/*
COPY docker/php-fpm.ini /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# prepare for running as the app user
RUN mkdir -p /app/cache && \
    chown -R app:app /app /run /var/lib/nginx /var/log/nginx

# copy source from local, and composer-built assets from builder
COPY --chown=app:app . /app
COPY --from=builder --chown=app:app /app/public/assets/bootstrap /app/public/assets/bootstrap
COPY --from=builder --chown=app:app /app/public/assets/jquery /app/public/assets/jquery
COPY --from=builder --chown=app:app /app/vendor /app/vendor

# configure container
STOPSIGNAL SIGINT
EXPOSE 8000
USER app
WORKDIR /app
HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping

# dockerflow support
ARG source
ARG version
ARG commit
ARG build
RUN php docker/version.php "$source" "$version" "$commit" "$build" > public/version.json
COPY docker/heartbeat.php /app/public

# run supervisord, which will start nginx and php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
