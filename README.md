# JBZoo / Mock-Server

[![CI](https://github.com/JBZoo/Mock-Server/actions/workflows/main.yml/badge.svg?branch=master)](https://github.com/JBZoo/Mock-Server/actions/workflows/main.yml?query=branch%3Amaster)    [![Coverage Status](https://coveralls.io/repos/github/JBZoo/Mock-Server/badge.svg?branch=master)](https://coveralls.io/github/JBZoo/Mock-Server?branch=master)    [![Psalm Coverage](https://shepherd.dev/github/JBZoo/Mock-Server/coverage.svg)](https://shepherd.dev/github/JBZoo/Mock-Server)    [![Psalm Level](https://shepherd.dev/github/JBZoo/Mock-Server/level.svg)](https://shepherd.dev/github/JBZoo/Mock-Server)    [![CodeFactor](https://www.codefactor.io/repository/github/jbzoo/mock-server/badge)](https://www.codefactor.io/repository/github/jbzoo/mock-server/issues)    
[![Stable Version](https://poser.pugx.org/jbzoo/mock-server/version)](https://packagist.org/packages/jbzoo/mock-server/)    [![Total Downloads](https://poser.pugx.org/jbzoo/mock-server/downloads)](https://packagist.org/packages/jbzoo/mock-server/stats)    [![Dependents](https://poser.pugx.org/jbzoo/mock-server/dependents)](https://packagist.org/packages/jbzoo/mock-server/dependents?order_by=downloads)    [![Visitors](https://visitor-badge.glitch.me/badge?page_id=jbzoo.mock-server)]()    [![GitHub License](https://img.shields.io/github/license/jbzoo/mock-server)](https://github.com/JBZoo/Mock-Server/blob/master/LICENSE)



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
