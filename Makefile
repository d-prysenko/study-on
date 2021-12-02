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

composer_install:
	${COMPOSER} install

install: env_create up composer_install encore_dev
