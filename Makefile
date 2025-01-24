.PHONY: ${TARGETS}
.DEFAULT_GOAL := help

DOCKER_COMPOSE=docker compose $*

help:
	@echo "\033[1;36mAVAILABLE COMMANDS :\033[0m"
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z_0-9-]+:.*?##/ { printf "  \033[32m%-20s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[33m%s\033[0m\n", substr($$0, 5) } ' Makefile

##@ Base commands
install: build start vendors-install ## Start the docker stack and prepare the application
	@echo "\033[32m🥳 EVERYTHING IS RUNNING! 🥳\033[0m"
	@echo "\033[32mLaunch "bin/console retumador" to continue.\033[0m"

vendors-install: ## Install vendors
	@$(DOCKER_COMPOSE) exec php composer ins

vendors-update: ## Update all vendors
	@$(DOCKER_COMPOSE) run php composer up
	@$(DOCKER_COMPOSE) run php composer --working-dir tools/php-cs-fixer up
	@$(DOCKER_COMPOSE) run php composer --working-dir tools/phpstan up

##@ Docker commands
build: ## Build docker stack
	@$(DOCKER_COMPOSE) build

start: ## Start the whole docker stack
	@$(DOCKER_COMPOSE) up --detach --remove-orphans

stop: ## Stop the docker stack
	@$(DOCKER_COMPOSE) stop

destroy: ## Destroy all containers, volumes, networks, ...
	@$(DOCKER_COMPOSE) down --remove-orphans --volumes --rmi=local

bash: ## Enter in the application container directly
	@$(DOCKER_COMPOSE) exec php /bin/bash

##@ Quality commands
test: ## Run all tests
	@$(DOCKER_COMPOSE) run php vendor/bin/phpunit

phpstan: tools/phpstan/vendor ## Run PHPStan
	@$(DOCKER_COMPOSE) run php tools/phpstan/vendor/bin/phpstan analyse --memory-limit=512M

cs-lint: tools/php-cs-fixer/vendor ## Lint all files
	@$(DOCKER_COMPOSE) run php bin/console lint:yaml config/
	@$(DOCKER_COMPOSE) run php tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: tools/php-cs-fixer/vendor ## Fix CS using PHP-CS
	@$(DOCKER_COMPOSE) run php tools/php-cs-fixer/vendor/bin/php-cs-fixer fix

tools/php-cs-fixer/vendor: tools/php-cs-fixer/composer.json tools/php-cs-fixer/composer.lock
	@$(DOCKER_COMPOSE) run php composer install --working-dir=tools/php-cs-fixer
tools/phpstan/vendor: tools/phpstan/composer.json tools/phpstan/composer.lock
	@$(DOCKER_COMPOSE) run php composer install --working-dir=tools/phpstan
