FROM php:7.4.3-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN echo "file_uploads = On\n" \
    "memory_limit = 500M\n" \
    "upload_max_filesize = 500M\n" \
    "post_max_size = 500M\n" \
    "max_execution_time = 600\n" \
    > /usr/local/etc/php/conf.d/uploads.ini
# RUN a2enmod rewrite
# RUN service apache2 restart
EXPOSE 8082