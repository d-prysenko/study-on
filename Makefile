COMPOSE=docker-compose
PHP=$(COMPOSE) exec php
CONSOLE=$(PHP) bin/console
COMPOSER=$(PHP) composer

up:
	@${COMPOSE} up -d

down:
	@${COMPOSE} down

clear:
	@${CONSOLE} cache:clear

migration:
	@${CONSOLE} make:migration

migrate:
	@${CONSOLE} doctrine:migrations:migrate

fixtload:
	@${CONSOLE} doctrine:fixtures:load

require:
	@${COMPOSER} require $2

encore_install:
	${COMPOSE} run node yarn install
	${COMPOSE} run node yarn add @symfony/webpack-encore --dev
	${COMPOSE} run node yarn add jquery @popperjs/core --dev


encore_dev:
	@${COMPOSE} run node yarn encore dev

encore_prod:
	@${COMPOSE} run node yarn encore production

phpunit:
	@${PHP} bin/phpunit

env_create:
	touch .env.local
	echo "APP_SECRET=c273489cb9049bef9e63280f61e09f07" >> .env.local
	echo "DATABASE_URL=pgsql://pguser:pguser@study-on_postgres_1:5432/study_on" >> .env.local
	touch .env.test.local
	echo "DATABASE_URL=pgsql://pguser:pguser@study-on_postgres_1:5432/study_on_test" >> .env.test.local

db_up:
	docker-compose exec php bin/console doctrine:database:create --if-not-exists
	docker-compose exec php bin/console doctrine:migrations:migrate --no-interaction
	docker-compose exec php bin/console doctrine:database:create --env=test --if-not-exists
	docker-compose exec php bin/console doctrine:migrations:migrate --env=test --no-interaction

composer_install:
	${COMPOSER} install

install: env_create up composer_install db_up encore_install encore_dev