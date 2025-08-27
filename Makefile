.DEFAULT_GOAL := help

DOCKER_COMPOSE=docker compose $*

.PHONY: help
help:
	@echo "\033[1;36mAVAILABLE COMMANDS :\033[0m"
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z_0-9-]+:.*?##/ { printf "  \033[32m%-20s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[33m%s\033[0m\n", substr($$0, 5) } ' Makefile

##@ Base commands
.PHONY: install
install: build start vendors-install ## Start the docker stack and prepare the application
	@echo "\033[32m🥳 EVERYTHING IS RUNNING! 🥳\033[0m"
	@echo "\033[32mLaunch "bin/console retumador" to continue.\033[0m"
	@echo "\033[32mGenerated feeds will be available on http://localhost:8084.\033[0m"

.PHONY: vendors-install
vendors-install: ## Install vendors
	@$(DOCKER_COMPOSE) run --rm php composer ins

.PHONY: vendors-update
vendors-update: ## Update all vendors
	@$(DOCKER_COMPOSE) run --rm php composer up
	@$(DOCKER_COMPOSE) run --rm php composer --working-dir tools/php-cs-fixer up
	@$(DOCKER_COMPOSE) run --rm php composer --working-dir tools/phpstan up

##@ Docker commands
.PHONY: build
build: ## Build docker stack
	@$(DOCKER_COMPOSE) build

.PHONY: start
start: ## Start the whole docker stack
	@$(DOCKER_COMPOSE) up --detach --remove-orphans

.PHONY: stop
stop: ## Stop the docker stack
	@$(DOCKER_COMPOSE) stop

.PHONY: destroy
destroy: ## Destroy all containers, volumes, networks, ...
	@$(DOCKER_COMPOSE) down --remove-orphans --volumes --rmi=local

##@ Quality commands
.PHONY: sanitize-and-check
sanitize-and-check: cs-fix phpstan test ## Run PHP-CS-fixer, PHPStan & tests

.PHONY: test
test: ## Run all tests
	@$(DOCKER_COMPOSE) run --rm php vendor/bin/phpunit

.PHONY: phpstan
phpstan: tools/phpstan/vendor ## Run PHPStan
	@$(DOCKER_COMPOSE) run --rm php tools/phpstan/vendor/bin/phpstan analyse

.PHONY: cs-lint
cs-lint: tools/php-cs-fixer/vendor tools/twig-cs-fixer/vendor ## Lint all files
	@$(DOCKER_COMPOSE) run --rm php bin/console lint:yaml config/
	@$(DOCKER_COMPOSE) run --rm php tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --dry-run --diff
	@$(DOCKER_COMPOSE) run --rm php tools/twig-cs-fixer/vendor/bin/twig-cs-fixer lint

.PHONY: cs-fix
cs-fix: tools/php-cs-fixer/vendor tools/twig-cs-fixer/vendor ## Fix CS using PHP-CS
	@$(DOCKER_COMPOSE) run --rm php tools/php-cs-fixer/vendor/bin/php-cs-fixer fix
	@$(DOCKER_COMPOSE) run --rm php tools/twig-cs-fixer/vendor/bin/twig-cs-fixer fix

tools/php-cs-fixer/vendor:
	@$(DOCKER_COMPOSE) run --rm php composer install --working-dir=tools/php-cs-fixer
tools/phpstan/vendor:
	@$(DOCKER_COMPOSE) run --rm php composer install --working-dir=tools/phpstan
tools/twig-cs-fixer/vendor:
	@$(DOCKER_COMPOSE) run --rm php composer install --working-dir=tools/twig-cs-fixer
