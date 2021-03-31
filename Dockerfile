FROM php:7.4-cli-alpine

RUN $(php -r '$extensionInstalled = array_map("strtolower", \get_loaded_extensions(false));$requiredExtensions = ["zlib", "pcntl", "filter", "filter", "json", "json", "json", "json", "openssl", "openssl", "dom", "gd", "posix"];$extensionsToInstall = array_diff($requiredExtensions, $extensionInstalled);if ([] !== $extensionsToInstall) {echo \sprintf("docker-php-ext-install %s", implode(" ", $extensionsToInstall));}echo "echo \"No extensions\"";')

COPY build/jbzoo-mock-server.phar /jbzoo-mock-server.phar

ENTRYPOINT ["/jbzoo-mock-server.phar"]
