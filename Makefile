.PHONY: install validate start lint cbf test test-coverage test-coverage-text

PORT ?= 8000

install:
	composer install

validate:
	composer validate

start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src public

cbf:
	composer exec --verbose phpcbf -- standard=PSR12 src public

test:
	composer exec --verbose phpunit

test-coverage:
	XDEBUG_MODE=coverage composer exec --verbose phpunit -- --coverage-clover=build/logs/clover.xml

test-coverage-text:
	XDEBUG_MODE=coverage composer exec --verbose phpunit -- --coverage-text