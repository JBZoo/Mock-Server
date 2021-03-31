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


ifneq (, $(wildcard ./vendor/jbzoo/codestyle/src/init.Makefile))
    include ./vendor/jbzoo/codestyle/src/init.Makefile
endif

MOCK_SERVER_HOST ?= 0.0.0.0
MOCK_SERVER_PORT ?= 8089
MOCK_SERVER_LOG  ?= `pwd`/build/server.log
MOCK_SERVER_BIN  ?= $(PHP_BIN) `pwd`/jbzoo-mock-server

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
	@echo "Composer flags: $(JBZOO_COMPOSER_UPDATE_FLAGS)"
	@composer install --optimize-autoloader
	@make build-phar


build-phar:
	@wget $(PHAR_BOX_SOURCE)                                  \
        --output-document="$(PATH_ROOT)/vendor/bin/box.phar"  \
        --no-check-certificate                                \
        --quiet                                               || true
	@$(PHAR_BOX) --version
	@$(PHAR_BOX) validate `pwd`/box.json.dist -vvv
	@$(PHAR_BOX) compile --working-dir="`pwd`" -vv
	@$(PHAR_BOX) info $(PHAR_FILE) --metadata


update: ##@Project Update all 3rd party dependencies
	$(call title,"Install/Update all 3rd party dependencies")
	@echo "Composer flags: $(JBZOO_COMPOSER_UPDATE_FLAGS)"
	@composer update --optimize-autoloader


test-all: ##@Project Run all project tests at once
	@make test
	@make codestyle


test-bench:
	@apib -1 http://$(MOCK_SERVER_HOST):$(MOCK_SERVER_PORT)/testMinimalMock
	@apib -c 100 -d 10 http://$(MOCK_SERVER_HOST):$(MOCK_SERVER_PORT)/testMinimalMock


restart:
	@make down
	@make up


up:
	@$(MOCK_SERVER_BIN)                 \
        --host=$(MOCK_SERVER_HOST)      \
        --port=$(MOCK_SERVER_PORT)      \
        --mocks=tests/mocks             \
        --ansi                          \
        -vvv


down:
	@-pgrep -f "jbzoo-mock-server" | xargs kill -15 || true
	@echo "Mock Server killed"


up-bg:
	@AMP_LOG_COLOR=true make up   \
        1>> "$(MOCK_SERVER_LOG)"  \
        2>> "$(MOCK_SERVER_LOG)"  \
        &

up-phar:
	@MOCK_SERVER_BIN=$(PHAR_FILE) make up


up-phar-bg:
	@MOCK_SERVER_BIN=$(PHAR_FILE) make up-bg


dev-watcher:
	@make down
	@make up-bg
