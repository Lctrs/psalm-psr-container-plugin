MIN_COVERED_MSI:=100
MIN_MSI:=100

.PHONY: it
it: coding-standards dependency-analysis static-code-analysis tests ## Runs the coding-standards, dependency-analysis, static-code-analysis, and tests targets

.PHONY: code-coverage
code-coverage: vendor ## Collects coverage from running unit tests with phpunit/phpunit
	vendor/bin/phpunit --configuration=test/Unit/phpunit.xml.dist --coverage-text

.PHONY: coding-standards
coding-standards: vendor ## Fixes code style issues with doctrine/coding-standard
	mkdir -p .build/php_codesniffer
	vendor/bin/phpcbf
	vendor/bin/phpcs

.PHONY: dependency-analysis
dependency-analysis: vendor ## Runs a dependency analysis with maglnet/composer-require-checker
	vendor/bin/composer-require-checker

.PHONY: help
help: ## Displays this list of targets with descriptions
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: mutation-tests
mutation-tests: vendor ## Runs mutation tests with infection/infection
	mkdir -p .build/infection
	vendor/bin/infection --ignore-msi-with-no-mutations --min-covered-msi=${MIN_COVERED_MSI} --min-msi=${MIN_MSI}

.PHONY: static-code-analysis
static-code-analysis: vendor ## Runs a static code analysis with phpstan/phpstan and vimeo/psalm
	mkdir -p .build/phpstan
	vendor/bin/phpstan analyse --configuration=phpstan.neon.dist
	mkdir -p .build/psalm
	vendor/bin/psalm --config=psalm.xml --show-info=false --stats

.PHONY: static-code-analysis-baseline
static-code-analysis-baseline: vendor ## Generates a baseline for static code analysis with phpstan/phpstan and vimeo/psalm
	mkdir -p .build/phpstan
	echo '' > phpstan-baseline.neon
	vendor/bin/phpstan analyze --configuration=phpstan.neon.dist --error-format=baselineNeon > phpstan-baseline.neon || true
	mkdir -p .build/psalm
	vendor/bin/psalm --config=psalm.xml --set-baseline=psalm-baseline.xml

.PHONY: tests
tests: vendor ## Runs auto-review, unit, and integration tests with phpunit/phpunit
	mkdir -p .build/phpunit
	vendor/bin/phpunit --configuration=test/AutoReview/phpunit.xml.dist
	vendor/bin/phpunit --configuration=test/Unit/phpunit.xml.dist
	vendor/bin/phpunit --configuration=test/Integration/phpunit.xml.dist

vendor: composer.json composer.lock
	composer validate --strict
	composer install --no-interaction --no-progress --no-suggest
	composer normalize
