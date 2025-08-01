.PHONY: help start stop restart build install test clean logs

help: ## Pokaż pomoc
	@echo "Dostępne komendy:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

start: ## Uruchom środowisko deweloperskie
	@echo "🚀 Uruchamianie Call Center Management System..."
	docker-compose up -d
	@echo "✅ Środowisko uruchomione!"
	@echo "🌐 Frontend: http://localhost:3000"
	@echo "🔧 Backend: http://localhost:8000"

stop: ## Zatrzymaj środowisko
	@echo "🛑 Zatrzymywanie środowiska..."
	docker-compose down

restart: stop start ## Restart środowiska

build: ## Zbuduj obrazy Docker
	@echo "🔨 Budowanie obrazów Docker..."
	docker-compose build

install: ## Zainstaluj zależności
	@echo "📦 Instalowanie zależności Symfony..."
	docker-compose exec php composer install
	@echo "📦 Instalowanie zależności React..."
	docker-compose exec frontend npm install

test: ## Uruchom testy
	@echo "🧪 Uruchamianie testów..."
	docker-compose exec php php bin/phpunit
	docker-compose exec frontend npm test

logs: ## Pokaż logi kontenerów
	docker-compose logs -f

clean: ## Wyczyść cache i logi
	@echo "🧹 Czyszczenie cache..."
	docker-compose exec php php bin/console cache:clear
	@echo "🧹 Czyszczenie logów..."
	docker-compose exec php rm -rf var/log/*

migrate: ## Uruchom migracje bazy danych
	@echo "🗄️ Uruchamianie migracji..."
	docker-compose exec php php bin/console doctrine:migrations:migrate

shell-php: ## Otwórz shell PHP
	docker-compose exec php bash

shell-frontend: ## Otwórz shell frontend
	docker-compose exec frontend bash

shell-mysql: ## Otwórz shell MySQL
	docker-compose exec mysql mysql -u root -ppassword call_center

status: ## Pokaż status kontenerów
	docker-compose ps 