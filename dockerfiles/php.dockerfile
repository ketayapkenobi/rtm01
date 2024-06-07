FROM php:8.2-rc-fpm-alpine

ARG UID
ARG GID

ENV UID=${UID}
ENV GID=${GID}

RUN mkdir -p /var/www/html

WORKDIR /var/www/html

# MacOS staff group's gid is 20, so is the dialout group in alpine linux. We're not using it, let's just remove it.
RUN delgroup dialout

RUN addgroup -g ${GID} --system laravel
RUN adduser -G laravel --system -D -s /bin/sh -u ${UID} laravel

# ADD --chown=1000:1000 . /var/www/html

RUN chown -R laravel:laravel /var/www/html

RUN sed -i "s/user = www-data/user = laravel/g" /usr/local/etc/php-fpm.d/www.conf
RUN sed -i "s/group = www-data/group = laravel/g" /usr/local/etc/php-fpm.d/www.conf
RUN echo "php_admin_flag[log_errors] = on" >> /usr/local/etc/php-fpm.d/www.conf

RUN docker-php-ext-install pdo pdo_mysql

RUN apk add --no-cache \
      freetype \
      libjpeg-turbo \
      libpng \
      freetype-dev \
      libjpeg-turbo-dev \
      libpng-dev \
    && docker-php-ext-configure gd \
      --with-freetype=/usr/include/ \      
      --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-enable gd \
    && apk del --no-cache \
      freetype-dev \
      libjpeg-turbo-dev \
      libpng-dev \
    && rm -rf /tmp/*

# #RUN mkdir -p /usr/src/php/ext/redis \
# #    && curl -L https://github.com/phpredis/phpredis/archive/5.3.4.tar.gz | tar xvz -C /usr/src/php/ext/redis --strip 1 \
# #    && echo 'redis' >> /usr/src/php-available-exts \
# #    && docker-php-ext-install redis

# # Install Oracle Instantclient
# ENV LD_LIBRARY_PATH /usr/local/instantclient
# ENV ORACLE_HOME /usr/local/instantclient

# # Download and unarchive Instant Client v11
# RUN apk add --update libaio libnsl && \
#   curl -o /tmp/instaclient-basic.zip https://raw.githubusercontent.com/bumpx/oracle-instantclient/master/instantclient-basic-linux.x64-11.2.0.4.0.zip && \
#   curl -o /tmp/instaclient-sdk.zip https://raw.githubusercontent.com/bumpx/oracle-instantclient/master/instantclient-sdk-linux.x64-11.2.0.4.0.zip && \
#   curl -o /tmp/instaclient-sqlplus.zip https://raw.githubusercontent.com/bumpx/oracle-instantclient/master/instantclient-sqlplus-linux.x64-11.2.0.4.0.zip && \
#   unzip -d /usr/local/ /tmp/instaclient-basic.zip && \
#   unzip -d /usr/local/ /tmp/instaclient-sdk.zip && \
#   unzip -d /usr/local/ /tmp/instaclient-sqlplus.zip && \
#   ln -s /usr/local/instantclient_11_2 ${ORACLE_HOME} && \
#   ln -s ${ORACLE_HOME}/libclntsh.so.* ${ORACLE_HOME}/libclntsh.so && \
#   ln -s ${ORACLE_HOME}/libocci.so.* ${ORACLE_HOME}/libocci.so && \
#   ln -s ${ORACLE_HOME}/lib* /usr/lib && \
#   ln -s ${ORACLE_HOME}/sqlplus /usr/bin/sqlplus && \
#   ln -s /usr/lib/libnsl.so.2  /usr/lib/libnsl.so.1 && \
#   docker-php-ext-configure oci8 --with-oci8=instantclient,$ORACLE_HOME && \
#   docker-php-ext-install oci8 && \
#   docker-php-ext-enable oci8

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]