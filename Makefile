.PHONY: install test reset

install:
	composer install
	php bin/console doctrine:migrations:migrate --no-interaction

test:
	./vendor/bin/phpunit --testdox

reset:
	rm -f var/data.db
	php bin/console doctrine:migrations:migrate --no-interaction
