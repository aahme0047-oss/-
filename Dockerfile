FROM php:8.2-cli
COPY . /var/www/html
WORKDIR /var/www/html
EXPOSE 10000
CMD ["php", "-S", "0.0.0.0:10000"]
