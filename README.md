# JBZoo / Mock-Server

[![Build Status](https://travis-ci.org/JBZoo/Mock-Server.svg)](https://travis-ci.org/JBZoo/Mock-Server)    [![Docker Cloud Build Status](https://img.shields.io/docker/cloud/build/jbzoo/mock-server.svg)](https://hub.docker.com/r/jbzoo/mock-server)    [![Coverage Status](https://coveralls.io/repos/JBZoo/Mock-Server/badge.svg)](https://coveralls.io/github/JBZoo/Mock-Server)    [![Psalm Coverage](https://shepherd.dev/github/JBZoo/Mock-Server/coverage.svg)](https://shepherd.dev/github/JBZoo/Mock-Server)    [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jbzoo/mock-server/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jbzoo/mock-server/?branch=master)    [![CodeFactor](https://www.codefactor.io/repository/github/jbzoo/mock-server/badge)](https://www.codefactor.io/repository/github/jbzoo/mock-server/issues)    [![PHP Strict Types](https://img.shields.io/badge/strict__types-%3D1-brightgreen)](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.strict)    
[![Stable Version](https://poser.pugx.org/jbzoo/mock-server/version)](https://packagist.org/packages/jbzoo/mock-server)    [![Dependents](https://poser.pugx.org/jbzoo/mock-server/dependents)](https://packagist.org/packages/jbzoo/mock-server/dependents?order_by=downloads)    [![GitHub Issues](https://img.shields.io/github/issues/jbzoo/mock-server)](https://github.com/JBZoo/Mock-Server/issues)    [![Total Downloads](https://poser.pugx.org/jbzoo/mock-server/downloads)](https://packagist.org/packages/jbzoo/mock-server/stats)    [![Docker Pulls](https://img.shields.io/docker/pulls/jbzoo/mock-server.svg)](https://hub.docker.com/r/jbzoo/mock-server)    [![GitHub License](https://img.shields.io/github/license/jbzoo/mock-server)](https://github.com/JBZoo/Mock-Server/blob/master/LICENSE)



### Installing

```sh
# Build it into you project
composer require jbzoo/mock-server

# OR use phar file. Replace <VERSION> to the last version. See releases page
wget https://github.com/JBZoo/Mock-Server/releases/download/<VERSION>/mock-server.phar 

# OR just pul docker image
docker pull jbzoo/mock-server:latest
```


### Usage

```shell
# Mock-Server is built-in into your project
php `pwd`/jbzoo-mock-server     \
    --host=0.0.0.0              \
    --port=8089                 \
    --host-tls=localhost        \
    --port-tls=8090             \
    --mocks=./mocks             \
    --ansi                      \
    -vvv

# Or Docker image
docker run                      \
    --rm                        \
    --name="mock-server"        \
    -v `pwd`/tests/mocks:/mocks \
    -p 8089:8089                \
    -p 8090:8090                \
    jbzoo/mock-server:latest    \
    --ansi                      \
    -vvv
```


## Unit tests and check code style
```sh
make update
make test-all
```


### License

MIT
