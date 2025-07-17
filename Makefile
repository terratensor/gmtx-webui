init: docker-down \
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
	app-migrations \
	#app-console-run \
#	app-index-create app-index-indexer	

app-permissions:
	docker run --rm -v ${PWD}:/app -w /app alpine chmod 777 var/cache var/log var/test

app-yii-init: # инициализация yii framework
	docker compose run --rm frontend php init-actions --interactive=0

app-composer-install:
	docker compose run --rm frontend composer install

app-composer-update:
	docker compose run --rm frontend composer update

# app-console-run:
# 	docker compose run --rm frontend php yii rules/bootstrap

app-migrations:
	docker compose run --rm frontend php yii migrate --interactive=0
	docker compose run --rm frontend php yii migrate-rbac --interactive=0

docker-up:
	docker compose up -d

docker-down:
	docker compose down --remove-orphans

docker-down-clear:
	docker compose down -v --remove-orphans

docker-pull:
	docker compose pull

docker-build:
	DOCKER_BUILDKIT=1 COMPOSE_DOCKER_CLI_BUILD=1 docker compose build --build-arg BUILDKIT_INLINE_CACHE=1 --pull