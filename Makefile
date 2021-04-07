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

.PHONY: build

ifneq (, $(wildcard ./vendor/jbzoo/codestyle/src/init.Makefile))
    include ./vendor/jbzoo/codestyle/src/init.Makefile
endif

MOCK_SERVER_HOST     ?= 0.0.0.0
MOCK_SERVER_PORT     ?= 8089
MOCK_SERVER_HOST_TLS ?= localhost
MOCK_SERVER_PORT_TLS ?= 8090
MOCK_SERVER_LOG      ?= `pwd`/build/server.log
MOCK_SERVER_BIN      ?= $(PHP_BIN) `pwd`/jbzoo-mock-server

PHAR_BOX      ?= $(PHP_BIN) `pwd`/vendor/bin/box.phar
PHAR_FILE     ?= `pwd`/build/jbzoo-mock-server.phar
PHAR_FILE_BIN ?= $(PHP_BIN) $(PHAR_FILE)

ifeq ($(strip $(PHP_VERSION_ALIAS)),72)
	PHAR_BOX_SOURCE ?= https://github.com/box-project/box/releases/download/3.9.1/box.phar
else
	PHAR_BOX_SOURCE ?= https://github.com/box-project/box/releases/download/3.11.1/box.phar
endif


build: ##@Project Install all 3rd party dependencies
	$(call title,"Install/Update all 3rd party dependencies")
	@composer install --optimize-autoloader --no-progress
	@make build-phar


update: ##@Project Update all 3rd party dependencies
	$(call title,"Update all 3rd party dependencies")
	@composer update --optimize-autoloader


test-all: ##@Tests Run all project tests at once
	@make test
	@make codestyle


test-bench: ##@Tests Benchmarking and testing concurrency based on "wrk" tool
	$(call title,"wrk: Connections=1000 Duration=10sec Treads=10")
	@wrk -d10 -t10 -c1000 --latency http://$(MOCK_SERVER_HOST):$(MOCK_SERVER_PORT)/testMinimalMock


up: ##@Project Start mock server (interactive mode)
	@$(MOCK_SERVER_BIN)                     \
        --host=$(MOCK_SERVER_HOST)          \
        --port=$(MOCK_SERVER_PORT)          \
        --host-tls=$(MOCK_SERVER_HOST_TLS)  \
        --port-tls=$(MOCK_SERVER_PORT_TLS)  \
        --mocks=tests/mocks                 \
        --ansi                              \
        -vvv

up-bg: ##@Project Start mock server (non-interactive mode)
	@AMP_LOG_COLOR=true make up 1>> "$(MOCK_SERVER_LOG)" 2>> "$(MOCK_SERVER_LOG)" &


down: ##@Project Force killing Mock Server
	@-pgrep -f "jbzoo-mock-server" | xargs kill -15 || true
	@echo "Mock Server killed"


restart: ##@Project To kill and start mock server (interactive mode)
	@make down
	@make up

restart-bg: ##@Project Restart server (non-interactive mode)
	@make down
	@make up-bg


up-phar: ##@Project Start mock server via phar file (interactive mode)
	@MOCK_SERVER_BIN=$(PHAR_FILE) make up


up-phar-bg: ##@Project Start mock server via phar file (non-interactive mode)
	@MOCK_SERVER_BIN=$(PHAR_FILE) make up-bg

sleep: ##@Project Start mock server via phar file (non-interactive mode)
	@sleep 2
