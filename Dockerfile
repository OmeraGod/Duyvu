# Dùng PHP 8.2 + Apache
FROM php:8.2-apache

# Copy toàn bộ source code vào thư mục web của Apache
COPY . /var/www/html/

# Nếu cần kết nối MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Bật mod_rewrite (nếu bạn dùng rewrite URL)
RUN a2enmod rewrite
