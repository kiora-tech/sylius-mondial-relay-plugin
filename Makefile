.PHONY: help install test phpstan cs-fix cs-check rector check clean

# Colors for output
BLUE=\033[0;34m
GREEN=\033[0;32m
RED=\033[0;31m
NC=\033[0m # No Color

help: ## Display this help message
	@echo "$(BLUE)Kiora Sylius Mondial Relay Plugin - Development Commands$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2}'
	@echo ""

install: ## Install composer dependencies
	@echo "$(BLUE)Installing dependencies...$(NC)"
	composer install
	@echo "$(GREEN)✓ Dependencies installed$(NC)"

update: ## Update composer dependencies
	@echo "$(BLUE)Updating dependencies...$(NC)"
	composer update
	@echo "$(GREEN)✓ Dependencies updated$(NC)"

test: ## Run PHPUnit tests
	@echo "$(BLUE)Running tests...$(NC)"
	vendor/bin/phpunit
	@echo "$(GREEN)✓ Tests passed$(NC)"

test-coverage: ## Run tests with coverage report
	@echo "$(BLUE)Running tests with coverage...$(NC)"
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html coverage
	@echo "$(GREEN)✓ Coverage report generated in coverage/$(NC)"

phpstan: ## Run PHPStan static analysis
	@echo "$(BLUE)Running PHPStan analysis...$(NC)"
	vendor/bin/phpstan analyse --memory-limit=1G
	@echo "$(GREEN)✓ Static analysis passed$(NC)"

phpstan-baseline: ## Generate PHPStan baseline
	@echo "$(BLUE)Generating PHPStan baseline...$(NC)"
	vendor/bin/phpstan analyse --generate-baseline --memory-limit=1G
	@echo "$(GREEN)✓ Baseline generated$(NC)"

cs-check: ## Check code style (dry-run)
	@echo "$(BLUE)Checking code style...$(NC)"
	vendor/bin/php-cs-fixer fix --dry-run --diff --verbose
	@echo "$(GREEN)✓ Code style check complete$(NC)"

cs-fix: ## Fix code style issues
	@echo "$(BLUE)Fixing code style...$(NC)"
	vendor/bin/php-cs-fixer fix --verbose
	@echo "$(GREEN)✓ Code style fixed$(NC)"

rector: ## Run Rector for automated refactoring
	@echo "$(BLUE)Running Rector...$(NC)"
	vendor/bin/rector process --dry-run
	@echo "$(GREEN)✓ Rector analysis complete$(NC)"

rector-fix: ## Apply Rector refactoring
	@echo "$(BLUE)Applying Rector refactoring...$(NC)"
	vendor/bin/rector process
	@echo "$(GREEN)✓ Rector refactoring applied$(NC)"

check: phpstan cs-check test ## Run all quality checks (PHPStan, CS, tests)
	@echo ""
	@echo "$(GREEN)✓✓✓ All checks passed ✓✓✓$(NC)"

fix: cs-fix ## Fix all auto-fixable issues

clean: ## Clean cache and generated files
	@echo "$(BLUE)Cleaning cache and generated files...$(NC)"
	rm -rf var/cache/* var/log/*
	rm -rf .phpunit.cache .phpstan.cache .php-cs-fixer.cache
	rm -rf coverage/ build/
	@echo "$(GREEN)✓ Cleaned$(NC)"

security-check: ## Check for security vulnerabilities
	@echo "$(BLUE)Checking for security vulnerabilities...$(NC)"
	composer audit
	@echo "$(GREEN)✓ Security check complete$(NC)"

validate: ## Validate composer.json
	@echo "$(BLUE)Validating composer.json...$(NC)"
	composer validate --strict
	@echo "$(GREEN)✓ composer.json is valid$(NC)"

outdated: ## Show outdated dependencies
	@echo "$(BLUE)Checking for outdated dependencies...$(NC)"
	composer outdated

init: install ## Initialize project (install + setup)
	@echo "$(GREEN)✓ Project initialized$(NC)"

ci: validate phpstan cs-check test ## Run CI pipeline locally
	@echo ""
	@echo "$(GREEN)✓✓✓ CI pipeline completed successfully ✓✓✓$(NC)"

.DEFAULT_GOAL := help
