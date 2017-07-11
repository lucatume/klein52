FROM tommylau/php-5.2

LABEL maintainer "luca@theaveragedev.com"

VOLUME /var/www/html

# change the ownership of the /var/www/html folder to www-data
# to allow the application to write files 
RUN chown -R www-data: /var/www/html

# install dependencies we need
RUN apt-get update && \
    apt-get install -y wget zip build-essential php5-dev --no-install-recommends

# download and build XDebug
RUN wget https://xdebug.org/files/xdebug-2.2.7.tgz -P $(pwd) && \
    tar -xvzf xdebug-2.2.7.tgz && \
    (cd xdebug-2.2.7; \
    phpize; \
    ./configure > /dev/null; \
    make > /dev/null)

# move Xdebug to the modules folder
RUN mkdir -p /usr/local/etc/php/modules && \
    cp xdebug-2.2.7/modules/xdebug.so /usr/local/etc/php/modules && \
    rm -rf $(pwd)/xdebug*

# install Opcache
RUN pecl install zendopcache-7.0.2

# install Memcache extension
RUN pecl install memcache

# clean up dependencies
RUN apt-get --purge remove -y wget zip build-essential php5-dev && \
    apt-get clean -y

COPY entrypoint /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint

# activate Xdebug on php
COPY conf/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# activate Opcache on php
COPY conf/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# activate Memcache on php
COPY conf/memcache.ini /usr/local/etc/php/conf.d/memcache.ini

ENTRYPOINT ["entrypoint"]
