##
# Dockerfile
##

# Base image from https://github.com/serversideup/docker-php - Production-ready Docker images for PHP
FROM serversideup/php:8.1-fpm-nginx

# Disable PHP-FPM and Nginx logs so that they will not clutter up Docker
# container logs and make it hard to sift out the application logs which are
# also output to Docker container logs via stdout
RUN echo "access.log = /dev/null" >> /etc/php/current_version/fpm/pool.d/www.conf
RUN sed --in-place 's/\(access\|error\)_log .*/\1_log \/dev\/null;/' /etc/nginx/nginx.conf

# Install additional system packages not found in base image
RUN apt-get update \
    && apt-get install --yes --no-install-recommends php8.1-imap vim \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Enable additional PHP extensions
RUN phpenmod imap

# Create subfolders for application (/var/www/html created in base image)
RUN mkdir -p /var/www/html/public \
    && mkdir -p /var/www/html/src \
    && mkdir -p /var/www/html/tmp
WORKDIR /var/www/html

# Copy only essential files and folders - Docker recommends using COPY instruction over ADD
# Placing the copy commands explicitly here is easier to troubleshoot
# than using .dockerignore. Do NOT copy .env inside here, use docker-compose.yml
# or Docker CLI to set environment variables for the container instead.
COPY public/ /var/www/html/public/
COPY src/ /var/www/html/src/
COPY composer.* /var/www/html/

# Install production dependencies for application
RUN composer install --no-dev

# Command for VERSION.txt placed last as it always changes, making Docker always rebuild this layer and subsequent ones
COPY VERSION.txt /var/www/html/

# No need for ENTRYPOINT or CMD at the end of this Dockerfile as the base image will automatically
# start up the Nginx web server and expose the standard ports 80/443
