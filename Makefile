# Environment variables
ENV_FILE ?= .env
COMPOSE_FILE ?= docker-compose.yml

# Docker compose commands
DC = docker compose --env-file $(ENV_FILE) -f $(COMPOSE_FILE)

# Targets
init: docker-down \
	app-clear \
	docker-pull docker-build docker-up \
	app-init

prod: ENV_FILE = .env.prod
prod: COMPOSE_FILE = docker-compose.prod.yml
prod: docker-down \
	app-clear \
	docker-pull docker-build docker-up \
	app-init

prod-init: ENV_FILE = .env.prod
prod-init: COMPOSE_FILE = docker-compose.prod.yml
prod-init: docker-down \
	app-clear \
	docker-pull docker-build docker-up \
	app-init

up: docker-up
down: docker-down
restart: down up

app-clear:
	docker run --rm -v ${PWD}:/app -w /app alpine sh -c 'rm -rf var/cache/* var/log/* var/test/*'	
	docker run --rm -v ${PWD}:/app -w /app alpine sh -c 'rm -rf frontend/runtime/* backend/runtime/cache/*'

app-init: app-permissions app-composer-install app-yii-init \
	app-migrations

app-permissions:
	docker run --rm -v ${PWD}:/app -w /app alpine chmod 777 var/cache var/log var/test

app-yii-init:
	$(DC) run --rm frontend php init-actions --interactive=0

app-composer-install:
	$(DC) run --rm frontend composer install

app-composer-update:
	$(DC) run --rm frontend composer update

app-migrations:
	$(DC) run --rm frontend php yii migrate --interactive=0
# 	$(DC) run --rm frontend php yii migrate-rbac --interactive=0

docker-up:
	$(DC) up -d

docker-down:
	$(DC) down --remove-orphans

docker-down-clear:
	$(DC) down -v --remove-orphans

docker-pull:
	$(DC) pull

docker-build:
	DOCKER_BUILDKIT=1 COMPOSE_DOCKER_CLI_BUILD=1 $(DC) build --build-arg BUILDKIT_INLINE_CACHE=1 --pull