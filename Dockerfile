#
# JBZoo Toolbox - Mock-Server.
#
# This file is part of the JBZoo Toolbox project.
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.
#
# @license    MIT
# @copyright  Copyright (C) JBZoo.com, All rights reserved.
# @see        https://github.com/JBZoo/Mock-Server
#

FROM php:7.4-cli-alpine
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions  \
    && sync                   \
    && install-php-extensions \
        opcache               \
        zip                   \
        gd                    \
        pcntl                 \
        ev                    \
        @composer

COPY . /app
RUN cd /app                                                          \
    && composer install --no-dev --optimize-autoloader --no-progress \
    && chmod +x /app/jbzoo-mock-server                               \
    && /app/jbzoo-mock-server --help

ENV MOCK_SERVER_IN_DOCKER=1
VOLUME /app/mocks
EXPOSE 8089 8090
ENTRYPOINT ["/app/jbzoo-mock-server"]

#HEALTHCHECK --interval=30s --timeout=3s --start-period=3s --retries=2 CMD curl -f http://localhost:8089 || exit 1
