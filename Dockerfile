#
# JBZoo Toolbox - Mock-Server
#
# This file is part of the JBZoo Toolbox project.
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.
#
# @package    Mock-Server
# @license    MIT
# @copyright  Copyright (C) JBZoo.com, All rights reserved.
# @link       https://github.com/JBZoo/Mock-Server
#

FROM php:7.4-cli-alpine

RUN apk add --no-cache libpng libpng-dev  \
    && docker-php-ext-install gd          \
    && apk del libpng-dev                 \
    && docker-php-ext-install pcntl       \
    && docker-php-ext-install filter      \
    && docker-php-ext-install json

COPY build/jbzoo-mock-server.phar /jbzoo-mock-server.phar

ENTRYPOINT ["/jbzoo-mock-server.phar"]
